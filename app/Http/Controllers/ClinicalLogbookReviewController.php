<?php

namespace App\Http\Controllers;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalRemediationPlan;
use App\Models\Student;
use App\Services\ClinicalCompetencyService;
use App\Services\ClinicalNotificationService;
use App\Services\ClinicalSummativeService;
use Illuminate\Http\Request;

class ClinicalLogbookReviewController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', ClinicalLogbookEntry::STATUS_SUBMITTED);

        $entries = ClinicalLogbookEntry::query()
            ->with(['student.programme', 'procedure', 'semester', 'rotationGroup.round'])
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'submitted' => ClinicalLogbookEntry::where('status', ClinicalLogbookEntry::STATUS_SUBMITTED)->count(),
            'approved' => ClinicalLogbookEntry::where('status', ClinicalLogbookEntry::STATUS_APPROVED)->count(),
            'rejected' => ClinicalLogbookEntry::where('status', ClinicalLogbookEntry::STATUS_REJECTED)->count(),
        ];

        return view('clinical.logbook-review-index', compact('entries', 'status', 'counts'));
    }

    public function show(ClinicalLogbookEntry $clinical_logbook_entry)
    {
        $clinical_logbook_entry->load([
            'student.programme',
            'procedure',
            'semester',
            'rotationGroup.round.programme',
            'reviewer',
        ]);

        $student = $clinical_logbook_entry->student;
        $studentSummary = app(\App\Services\ClinicalPlacementService::class)->placementContext($student);
        $competency = app(ClinicalCompetencyService::class)->checklistForStudent($student, $clinical_logbook_entry->semester_id);
        $remediationPlans = ClinicalRemediationPlan::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('clinical.logbook-review-show', [
            'entry' => $clinical_logbook_entry,
            'placementContext' => $studentSummary,
            'competency' => $competency,
            'remediationPlans' => $remediationPlans,
        ]);
    }

    public function approve(Request $request, ClinicalLogbookEntry $clinical_logbook_entry, ClinicalNotificationService $notify)
    {
        if ($clinical_logbook_entry->status !== ClinicalLogbookEntry::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted entries can be approved.');
        }

        $validated = $request->validate([
            'reviewer_feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $clinical_logbook_entry->update([
            'status' => ClinicalLogbookEntry::STATUS_APPROVED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'reviewer_feedback' => $validated['reviewer_feedback'] ?? null,
        ]);

        $notify->logbookApproved($clinical_logbook_entry, auth()->id());

        return redirect()->route('clinical-logbook.index', ['status' => 'submitted'])
            ->with('success', 'Competency signed off for '.$clinical_logbook_entry->student->full_name.'.');
    }

    public function reject(Request $request, ClinicalLogbookEntry $clinical_logbook_entry, ClinicalNotificationService $notify)
    {
        if ($clinical_logbook_entry->status !== ClinicalLogbookEntry::STATUS_SUBMITTED) {
            return back()->with('error', 'Only submitted entries can be returned.');
        }

        $validated = $request->validate([
            'reviewer_feedback' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $clinical_logbook_entry->update([
            'status' => ClinicalLogbookEntry::STATUS_REJECTED,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'reviewer_feedback' => $validated['reviewer_feedback'],
        ]);

        $notify->logbookRejected($clinical_logbook_entry, auth()->id());

        if ($request->boolean('create_remediation')) {
            $plan = ClinicalRemediationPlan::create([
                'student_id' => $clinical_logbook_entry->student_id,
                'semester_id' => $clinical_logbook_entry->semester_id,
                'clinical_logbook_entry_id' => $clinical_logbook_entry->id,
                'title' => 'Revise logbook entry: '.$clinical_logbook_entry->procedure?->name,
                'description' => $validated['reviewer_feedback'],
                'due_date' => $request->date('remediation_due'),
                'status' => ClinicalRemediationPlan::STATUS_OPEN,
                'assigned_by' => auth()->id(),
            ]);
            $notify->remediationAssigned($plan, auth()->id());
        }

        return redirect()->route('clinical-logbook.index', ['status' => 'submitted'])
            ->with('success', 'Entry returned to student for revision.');
    }

    public function framework()
    {
        $level = (int) request('level', 4);
        if (! \App\Support\CmtPracticumCatalog::isSupportedLevel($level)) {
            $level = 4;
        }
        $catalog = \App\Support\CmtPracticumCatalog::forLevel($level);
        $dbCount = \App\Models\ClinicalProcedure::query()
            ->where('nta_level', $level)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('source_type')->orWhere('source_type', '!=', 'checklist_criterion'))
            ->where('min_required_count', '>', 0)
            ->count();

        return view('clinical.framework', [
            'practicum_level' => $level,
            'practicum_source' => $catalog->sourceLabel(),
            'practicum_by_department' => $catalog->proceduresByDepartment(),
            'rotation_area_labels' => $catalog->rotationAreas(),
            'semester_modules' => $catalog->semesterModules(),
            'assessment_methods' => $catalog->assessmentMethods(),
            'procedure_count' => $dbCount > 0 ? $dbCount : count($catalog->procedures()),
        ]);
    }

    public function studentProgress(Student $student, ClinicalSummativeService $summative)
    {
        $student->load('programme');
        $overview = $summative->overview($student);
        $entries = ClinicalLogbookEntry::query()
            ->with(['procedure', 'semester', 'reviewer'])
            ->where('student_id', $student->id)
            ->orderByDesc('performed_on')
            ->limit(50)
            ->get();
        $remediationPlans = ClinicalRemediationPlan::query()
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();
        $semesters = \App\Models\Semester::query()->orderByDesc('academic_year')->limit(8)->get();

        return view('clinical.student-progress', [
            'student' => $student,
            'placement' => $overview['placement'],
            'overview' => $overview,
            'entries' => $entries,
            'remediationPlans' => $remediationPlans,
            'semesters' => $semesters,
        ]);
    }
}
