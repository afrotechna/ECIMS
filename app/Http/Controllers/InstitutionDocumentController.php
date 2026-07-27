<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\InstitutionDocument;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstitutionDocumentController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        return $this->folderBrowser($request, publicOnly: false);
    }

    public function studentIndex(Request $request)
    {
        abort_unless(auth()->user()->isStudent(), 403);

        return $this->folderBrowser($request, publicOnly: true);
    }

    private function folderBrowser(Request $request, bool $publicOnly): \Illuminate\View\View
    {
        $studentPortal = $publicOnly;
        $student = $studentPortal ? auth()->user()->student : null;
        $studentProgrammeId = $student?->programme_id;
        $indexRoute = $studentPortal ? 'college-documents.index' : 'institution-documents.index';
        $category = $request->string('category')->toString();

        $visibleCategories = $studentPortal
            ? InstitutionDocument::categoriesForStudents()
            : InstitutionDocument::CATEGORIES;

        if ($category !== '' && array_key_exists($category, InstitutionDocument::CATEGORIES)) {
            if ($studentPortal && ! InstitutionDocument::isStudentCategory($category)) {
                abort(404);
            }

            $query = InstitutionDocument::query()
                ->when($publicOnly, fn ($q) => $q->publicOnly()->inStudentFolders()->visibleToProgramme($studentProgrammeId))
                ->with(['uploader', 'programme'])
                ->where('category', $category)
                ->orderByDesc('created_at');

            if ($request->filled('q')) {
                $q = '%'.$request->string('q').'%';
                $query->where(function ($b) use ($q) {
                    $b->where('title', 'like', $q)->orWhere('description', 'like', $q);
                });
            }
            $documents = $query->paginate(15)->withQueryString();
            $folderLabel = InstitutionDocument::CATEGORIES[$category];
            $folderKey = $category;

            return view('institution-documents.folder', compact(
                'documents', 'folderKey', 'folderLabel', 'studentPortal', 'indexRoute'
            ));
        }

        $countsQuery = InstitutionDocument::query()
            ->when($publicOnly, fn ($q) => $q->publicOnly()->inStudentFolders()->visibleToProgramme($studentProgrammeId))
            ->selectRaw('category, count(*) as total')
            ->groupBy('category');

        $counts = $countsQuery->pluck('total', 'category');

        $folders = collect($visibleCategories)->map(function (string $label, string $key) use ($counts) {
            return [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
                'icon' => InstitutionDocument::folderIcons()[$key] ?? 'bi-folder2',
            ];
        })->values();

        return view('institution-documents.index', compact('folders', 'studentPortal', 'indexRoute'));
    }

    public function create(Request $request)
    {
        $folderKey = $request->string('category')->toString();
        if ($folderKey !== '' && ! array_key_exists($folderKey, InstitutionDocument::CATEGORIES)) {
            $folderKey = '';
        }
        $folderLabel = $folderKey !== '' ? InstitutionDocument::CATEGORIES[$folderKey] : null;

        $programmes = Programme::query()->where('is_active', true)->orderBy('code')->get();

        return view('institution-documents.create', compact('folderKey', 'folderLabel', 'programmes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(InstitutionDocument::CATEGORIES))],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document' => ['required', 'file', 'max:20480'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $file = $request->file('document');
        $path = $file->store(
            'institution-documents/'.$validated['category'].'/'.date('Y'),
            'local'
        );

        InstitutionDocument::create([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'programme_id' => $this->resolveProgrammeId($validated['category'], $validated['programme_id'] ?? null),
            'description' => $validated['description'] ?? null,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_public' => $this->studentAccessFlag($request, $validated['category']),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('institution-documents.index', ['category' => $validated['category']])
            ->with('success', 'Document uploaded to '.(InstitutionDocument::CATEGORIES[$validated['category']] ?? 'folder').'.');
    }

    public function edit(InstitutionDocument $institution_document)
    {
        $folderKey = $institution_document->category;
        $folderLabel = InstitutionDocument::CATEGORIES[$folderKey] ?? null;

        $programmes = Programme::query()->where('is_active', true)->orderBy('code')->get();

        return view('institution-documents.edit', [
            'document' => $institution_document,
            'folderKey' => $folderKey,
            'folderLabel' => $folderLabel,
            'programmes' => $programmes,
        ]);
    }

    public function update(Request $request, InstitutionDocument $institution_document)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'in:'.implode(',', array_keys(InstitutionDocument::CATEGORIES))],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'document' => ['nullable', 'file', 'max:20480'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $data = [
            'title' => $validated['title'],
            'category' => $validated['category'],
            'programme_id' => $this->resolveProgrammeId($validated['category'], $validated['programme_id'] ?? null),
            'description' => $validated['description'] ?? null,
            'is_public' => $this->studentAccessFlag($request, $validated['category']),
        ];

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            if (Storage::disk('local')->exists($institution_document->file_path)) {
                Storage::disk('local')->delete($institution_document->file_path);
            }
            $data['file_path'] = $file->store(
                'institution-documents/'.$validated['category'].'/'.date('Y'),
                'local'
            );
            $data['original_name'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['size'] = $file->getSize();
        }

        $institution_document->update($data);

        return redirect()
            ->route('institution-documents.index', ['category' => $validated['category']])
            ->with('success', 'Document updated.');
    }

    public function download(InstitutionDocument $institution_document)
    {
        if (auth()->user()->isStudent()) {
            if (! $institution_document->canStudentAccess(auth()->user()->student)) {
                abort(403, 'This document is not available for your programme.');
            }
        } elseif (auth()->user()->isGuardian()) {
            $student = auth()->user()->linkedStudent;
            if (! $student || ! $institution_document->canStudentAccess($student)) {
                abort(403);
            }
        }
        if (! Storage::disk('local')->exists($institution_document->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $institution_document->file_path,
            $institution_document->original_name
        );
    }

    public function destroy(InstitutionDocument $institution_document)
    {
        $category = $institution_document->category;
        if (Storage::disk('local')->exists($institution_document->file_path)) {
            Storage::disk('local')->delete($institution_document->file_path);
        }
        $institution_document->delete();

        return redirect()
            ->route('institution-documents.index', ['category' => $category])
            ->with('success', 'Document removed.');
    }

    private function studentAccessFlag(Request $request, string $category): bool
    {
        if (! InstitutionDocument::isStudentCategory($category)) {
            return false;
        }

        return $request->boolean('is_public');
    }

    private function resolveProgrammeId(string $category, mixed $programmeId): ?int
    {
        if (! InstitutionDocument::isStudentCategory($category)) {
            return null;
        }

        if ($programmeId === null || $programmeId === '') {
            return null;
        }

        return (int) $programmeId;
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            InstitutionDocument::class,
            'institution-documents.index',
            fn (Request $r) => array_filter([
                'category' => $r->input('category', $r->query('category')),
                'q' => $r->input('q', $r->query('q')),
            ], fn ($v) => $v !== null && $v !== ''),
            singularLabel: 'document',
            deleter: function (InstitutionDocument $doc) {
                if (Storage::disk('local')->exists($doc->file_path)) {
                    Storage::disk('local')->delete($doc->file_path);
                }
                $doc->delete();

                return true;
            },
        );
    }
}
