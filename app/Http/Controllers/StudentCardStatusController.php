<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\StudentCardStatus;
use App\Notifications\StudentCardStatusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class StudentCardStatusController extends Controller
{
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

        $students = $query->orderBy('reg_no')->paginate(25)->withQueryString();
        $programmes = \App\Models\Programme::orderBy('code')->get();

        return view('student-card-status.index', compact('students', 'programmes'));
    }

    public function update(Student $student, Request $request): RedirectResponse
    {
        $validated = Validator::make($request->all(), [
            'document_type' => ['required', 'string', 'in:'.implode(',', array_keys(StudentCardStatus::DOCUMENT_TYPES))],
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(StudentCardStatus::STATUSES))],
        ])->validate();

        $record = StudentCardStatus::firstOrNew([
            'student_id' => $student->id,
            'document_type' => $validated['document_type'],
        ]);

        $statusChanged = $record->status !== $validated['status'];
        $record->status = $validated['status'];
        $record->updated_by = auth()->id();
        if ($validated['status'] === 'printed' && ! $record->printed_at) {
            $record->printed_at = now();
        }
        if ($validated['status'] === 'active' && ! $record->activated_at) {
            $record->activated_at = now();
        }
        $record->save();

        ActivityLog::log(
            'student_card_status.updated',
            StudentCardStatus::class,
            $record->id,
            "{$record->documentTypeLabel()} for {$student->reg_no} set to {$record->statusLabel()}"
        );

        if ($statusChanged && in_array($validated['status'], ['printed', 'active'], true) && $student->status === 'active') {
            $studentUser = $student->userAccount;
            if ($studentUser) {
                $studentUser->notify(new StudentCardStatusNotification($record));
            }
        }

        return back()->with('success', $record->documentTypeLabel().' status updated to '.$record->statusLabel().'.');
    }
}
