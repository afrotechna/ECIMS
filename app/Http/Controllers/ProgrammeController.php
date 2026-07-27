<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Programme;
use App\Models\ProgrammeNtaLevelDocument;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgrammeController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $programmes = Programme::orderBy('code')->paginate(10);

        return view('programmes.index', compact('programmes'));
    }

    public function create()
    {
        $catalogue = Programme::catalogue();
        $levelOptions = Programme::levelSelectOptions();
        $durationOptions = Programme::durationYearSelectOptions();

        return view('programmes.create', compact('catalogue', 'levelOptions', 'durationOptions'));
    }

    public function store(Request $request)
    {
        $allowedCodes = collect(Programme::catalogue())->pluck('code')->all();
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', Rule::in($allowedCodes), 'unique:programmes,code'],
            'level' => ['required', 'string', 'max:50', Rule::in(Programme::allowedLevelValues())],
            'duration_years' => ['required', 'integer', Rule::in(Programme::allowedDurationYears())],
        ]);
        $row = Programme::rowForCode($validated['code']);
        if ($row === null) {
            return redirect()->back()->withInput()->with('error', 'Invalid programme selection.');
        }

        Programme::create([
            'code' => $row['code'],
            'name' => $row['name'],
            'level' => $validated['level'],
            'duration_years' => (int) $validated['duration_years'],
        ]);

        return redirect()->route('programmes.index')->with('success', 'Programme created successfully.');
    }

    public function edit(Programme $programme)
    {
        $catalogue = Programme::catalogueOptionsForEdit($programme);
        $levelOptions = Programme::levelSelectOptionsForEdit($programme);
        $durationOptions = Programme::durationYearSelectOptionsForEdit($programme);
        $programme->load(['ntaLevelDocuments.uploader']);
        $docsByLevelAndType = $programme->ntaLevelDocuments->keyBy(
            fn (ProgrammeNtaLevelDocument $d) => $d->nta_level.'|'.$d->document_type
        );

        return view('programmes.edit', compact('programme', 'catalogue', 'levelOptions', 'durationOptions', 'docsByLevelAndType'));
    }

    public function storeNtaLevelDocument(Request $request, Programme $programme)
    {
        $validated = $request->validate([
            'nta_level' => ['required', 'integer', Rule::in([4, 5, 6])],
            'document_type' => ['required', 'string', Rule::in(ProgrammeNtaLevelDocument::DOCUMENT_TYPES)],
            'file' => ['required', 'file', 'max:15360', 'mimes:pdf,doc,docx'],
        ]);

        $path = $request->file('file')->store("programme-nta-docs/{$programme->id}", 'public');
        $originalName = $request->file('file')->getClientOriginalName();

        $existing = ProgrammeNtaLevelDocument::query()
            ->where('programme_id', $programme->id)
            ->where('nta_level', $validated['nta_level'])
            ->where('document_type', $validated['document_type'])
            ->first();

        if ($existing) {
            $existing->delete();
        }

        ProgrammeNtaLevelDocument::create([
            'programme_id' => $programme->id,
            'nta_level' => $validated['nta_level'],
            'document_type' => $validated['document_type'],
            'file_path' => $path,
            'original_name' => $originalName,
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()
            ->to(route('programmes.edit', $programme).'#nta-level-documents')
            ->with('success', 'Supporting document uploaded.');
    }

    public function destroyNtaLevelDocument(Programme $programme, int $document)
    {
        $doc = ProgrammeNtaLevelDocument::query()
            ->where('programme_id', $programme->id)
            ->whereKey($document)
            ->firstOrFail();
        $doc->delete();

        return redirect()
            ->to(route('programmes.edit', $programme).'#nta-level-documents')
            ->with('success', 'Supporting document removed.');
    }

    public function update(Request $request, Programme $programme)
    {
        $allowedCodes = collect(Programme::catalogueOptionsForEdit($programme))->pluck('code')->all();
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::in($allowedCodes),
                Rule::unique('programmes', 'code')->ignore($programme->id),
            ],
            'level' => ['required', 'string', 'max:50', Rule::in(Programme::allowedLevelValuesForEdit($programme))],
            'duration_years' => ['required', 'integer', Rule::in(Programme::allowedDurationYearsForEdit($programme))],
        ]);
        $row = collect(Programme::catalogueOptionsForEdit($programme))->first(
            fn ($r) => strtoupper((string) $r['code']) === strtoupper((string) $validated['code'])
        );
        if ($row === null) {
            return redirect()->back()->withInput()->with('error', 'Invalid programme selection.');
        }

        $programme->update([
            'code' => $row['code'],
            'name' => $row['name'],
            'level' => $validated['level'],
            'duration_years' => (int) $validated['duration_years'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('programmes.index')->with('success', 'Programme updated successfully.');
    }

    public function destroy(Programme $programme)
    {
        if ($programme->students()->exists()) {
            return redirect()->route('programmes.index')->with('error', 'Cannot delete programme with students. Deactivate it instead.');
        }
        $programme->delete();

        return redirect()->route('programmes.index')->with('success', 'Programme deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Programme::class,
            'programmes.index',
            singularLabel: 'programme',
            deleter: function (Programme $programme) {
                if ($programme->students()->exists()) {
                    return false;
                }
                $programme->delete();

                return true;
            },
        );
    }
}
