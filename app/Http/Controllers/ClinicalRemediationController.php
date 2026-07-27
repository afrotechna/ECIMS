<?php

namespace App\Http\Controllers;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalRemediationPlan;
use App\Models\Student;
use App\Services\ClinicalNotificationService;
use Illuminate\Http\Request;

class ClinicalRemediationController extends Controller
{
    public function store(Request $request, Student $student, ClinicalNotificationService $notify)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'clinical_logbook_entry_id' => ['nullable', 'exists:clinical_logbook_entries,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:3000'],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        if (! empty($validated['clinical_logbook_entry_id'])) {
            $entry = ClinicalLogbookEntry::findOrFail($validated['clinical_logbook_entry_id']);
            abort_unless($entry->student_id === $student->id, 403);
        }

        $plan = ClinicalRemediationPlan::create([
            ...$validated,
            'student_id' => $student->id,
            'status' => ClinicalRemediationPlan::STATUS_OPEN,
            'assigned_by' => auth()->id(),
        ]);

        $notify->remediationAssigned($plan, auth()->id());

        return back()->with('success', 'Remediation plan assigned.');
    }

    public function complete(Request $request, ClinicalRemediationPlan $clinical_remediation_plan)
    {
        $validated = $request->validate([
            'completion_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $clinical_remediation_plan->update([
            'status' => ClinicalRemediationPlan::STATUS_COMPLETED,
            'completed_at' => now(),
            'completion_notes' => $validated['completion_notes'] ?? null,
        ]);

        return back()->with('success', 'Remediation marked complete.');
    }
}
