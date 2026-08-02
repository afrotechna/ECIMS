<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\StudentCardStatus;
use App\Notifications\StudentCardStatusNotification;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class StudentCardStatusController extends Controller
{
    public function card(Student $student): View
    {
        if (auth()->user()->isStudent()) {
            if (! auth()->user()->student || auth()->user()->student->id !== $student->id) {
                abort(403, 'You can only view your own ID card.');
            }
            if (! $student->idCardReady()) {
                abort(403, 'Your ID card is not ready yet. It becomes available once the registrar has processed and printed it.');
            }
        } elseif (! auth()->user()->canModule('student_card_status', 'view')) {
            abort(403);
        }

        $student->load('programme');
        $photoUrl = auth()->user()->isStudent()
            ? auth()->user()->profile_photo_url
            : $student->userAccount?->profile_photo_url;

        return view('student-card-status.card', compact('student', 'photoUrl'));
    }

    public function index(Request $request): View
    {
        $query = Student::query()->where('status', 'active')->with(['programme', 'cardStatuses']);

        if ($request->filled('search')) {
            $term = trim((string) $request->search);
            $query->where(function ($q) use ($term) {
                $q->where('reg_no', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%");
            });
        }
        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }

        // Only registered students who have fully cleared their fee balance (tuition, NHIF, etc.) are eligible for card issuance.
        $eligible = $query->orderBy('reg_no')->get()->filter(fn (Student $s) => $this->hasCompletedPayment($s))->values();

        $perPage = 25;
        $page = (int) $request->integer('page', 1);
        $students = new \Illuminate\Pagination\LengthAwarePaginator(
            $eligible->forPage($page, $perPage),
            $eligible->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $programmes = \App\Models\Programme::orderBy('code')->get();

        return view('student-card-status.index', compact('students', 'programmes'));
    }

    public function downloadPdf(Request $request): Response|RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:students,id'],
        ])->validate();

        $students = Student::whereIn('id', $validated['ids'])->with('programme')->orderBy('reg_no')->get();

        $cards = [];
        $missingPhoto = [];
        foreach ($students as $student) {
            if (! $this->hasCompletedPayment($student)) {
                continue;
            }

            $studentUser = $student->userAccount;
            $photoPath = $studentUser?->profile_photo_path;
            if (! $photoPath || ! Storage::disk('public')->exists($photoPath)) {
                $missingPhoto[] = "{$student->full_name} ({$student->nactvet_reg_no})";

                continue;
            }

            $cards[] = [
                'student' => $student,
                'photoUri' => $this->fileUri(Storage::disk('public')->path($photoPath)),
            ];
        }

        if ($missingPhoto !== []) {
            return back()->with('error', 'Cannot generate the batch PDF yet — these students have not uploaded a profile photo: '.implode(', ', $missingPhoto).'.');
        }

        if ($cards === []) {
            return back()->with('error', 'None of the selected students are eligible (registered and fee balance cleared).');
        }

        if (! class_exists(Dompdf::class)) {
            abort(503, 'PDF export is not available yet: run `composer install` on the server after enabling the PHP zip extension (php.ini: extension=zip).');
        }

        $logoPath = public_path('images/logo.png');
        $emblemPath = public_path('images/national-emblem.png');

        $html = view('student-card-status.batch-pdf', [
            'cards' => $cards,
            'logoUri' => file_exists($logoPath) ? $this->fileUri($logoPath) : null,
            'emblemUri' => file_exists($emblemPath) ? $this->fileUri($emblemPath) : null,
        ])->render();

        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $name = 'student-id-cards-'.now()->format('Y-m-d-His').'.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    /** Dompdf needs a file:// URI (not an http asset URL) to embed local images without enabling remote fetches. */
    private function fileUri(string $absolutePath): string
    {
        return 'file:///'.ltrim(str_replace('\\', '/', $absolutePath), '/');
    }

    public function update(Student $student, Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'document_type' => ['required', 'string', 'in:'.implode(',', array_keys(StudentCardStatus::DOCUMENT_TYPES))],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(StudentCardStatus::STATUSES))],
        ])->validate();

        if (! $this->hasCompletedPayment($student)) {
            return back()->with('error', $this->ineligibilityReason($student));
        }

        $record = $this->applyStatus($student, $validated['document_type'], $validated['status']);

        return back()->with('success', $record->documentTypeLabel().' status updated to '.$record->statusLabel().'.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:students,id'],
            'document_type' => ['required', 'string', 'in:'.implode(',', array_keys(StudentCardStatus::DOCUMENT_TYPES))],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(StudentCardStatus::STATUSES))],
        ])->validate();

        $students = Student::whereIn('id', $validated['ids'])->get();
        $updated = 0;
        $skipped = 0;
        foreach ($students as $student) {
            if (! $this->hasCompletedPayment($student)) {
                $skipped++;

                continue;
            }
            $this->applyStatus($student, $validated['document_type'], $validated['status']);
            $updated++;
        }

        $label = StudentCardStatus::DOCUMENT_TYPES[$validated['document_type']] ?? $validated['document_type'];
        $statusLabel = StudentCardStatus::STATUSES[$validated['status']] ?? $validated['status'];

        $message = "{$label} set to {$statusLabel} for {$updated} student(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} not registered or with an outstanding fee balance.";
        }

        return back()->with($updated > 0 ? 'success' : 'error', $message);
    }

    /** Registered for a semester (billed) AND has cleared whatever was billed — zero ledger activity alone doesn't count as "paid". */
    private function hasCompletedPayment(Student $student): bool
    {
        return $student->hasCompletedSemesterRegistration() && $student->balance <= 0;
    }

    private function ineligibilityReason(Student $student): string
    {
        if (! $student->hasCompletedSemesterRegistration()) {
            return "{$student->full_name} is not registered for a semester yet.";
        }

        return "{$student->full_name} still owes ".number_format($student->balance, 0).' TZS. Clear the fee balance before changing card status.';
    }

    private function applyStatus(Student $student, string $documentType, string $status): StudentCardStatus
    {
        $record = StudentCardStatus::firstOrNew([
            'student_id' => $student->id,
            'document_type' => $documentType,
        ]);

        $statusChanged = $record->status !== $status;
        $record->status = $status;
        $record->updated_by = auth()->id();
        if ($status === 'printed' && ! $record->printed_at) {
            $record->printed_at = now();
        }
        if ($status === 'active' && ! $record->activated_at) {
            $record->activated_at = now();
        }
        $record->save();

        ActivityLog::log(
            'student_card_status.updated',
            StudentCardStatus::class,
            $record->id,
            "{$record->documentTypeLabel()} for {$student->reg_no} set to {$record->statusLabel()}"
        );

        if ($statusChanged && in_array($status, ['printed', 'active'], true) && $student->status === 'active') {
            $studentUser = $student->userAccount;
            if ($studentUser) {
                $studentUser->notify(new StudentCardStatusNotification($record));
            }
        }

        return $record;
    }
}
