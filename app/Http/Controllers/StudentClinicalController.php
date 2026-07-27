<?php

namespace App\Http\Controllers;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalProcedure;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use App\Notifications\StaffClinicalLogbookSubmittedNotification;
use App\Services\ClinicalCompetencyService;
use App\Services\ClinicalPlacementService;
use App\Services\ClinicalPracticumGuideService;
use App\Services\ClinicalSummativeService;
use App\Support\CmtPracticumCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentClinicalController extends Controller
{
    public function placement(ClinicalPlacementService $placements)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }

        $context = $placements->placementContext($student);

        return view('clinical.student-placement', array_merge(
            ['student' => $student],
            $context,
            $this->practicumViewData($student, $context['semester']?->id)
        ));
    }

    public function logbookIndex(Request $request, ClinicalPlacementService $placements)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }

        $context = $placements->placementContext($student);
        $semesterId = $request->integer('semester_id') ?: $context['semester']?->id;

        $entries = ClinicalLogbookEntry::query()
            ->with(['procedure', 'rotationGroup.round', 'reviewer'])
            ->where('student_id', $student->id)
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->orderByDesc('performed_on')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $semester = $semesterId ? Semester::find($semesterId) : null;

        return view('clinical.student-logbook-index', array_merge([
            'student' => $student,
            'entries' => $entries,
            'semester' => $semester,
            'placement' => $context['primary'],
            'logbook_counts' => $context['logbook_counts'],
        ], $this->practicumViewData($student, $semester?->id)));
    }

    public function logbookCreate(ClinicalPlacementService $placements)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }

        $context = $placements->placementContext($student);
        $procedures = $this->proceduresForStudent($student);

        return view('clinical.student-logbook-form', array_merge([
            'student' => $student,
            'entry' => new ClinicalLogbookEntry([
                'performed_on' => now()->toDateString(),
                'department_code' => $context['primary']?->department_code,
                'hospital_code' => $context['primary']?->hospital_code,
            ]),
            'procedures' => $procedures,
            'procedure_select_groups' => $this->procedureSelectGroups($procedures),
            'placement' => $context['primary'],
            'semester' => $context['semester'],
            'departmentLabels' => $this->departmentLabelsForStudent($student),
        ], $this->practicumViewData($student, $context['semester']?->id)));
    }

    public function logbookStore(Request $request, ClinicalPlacementService $placements)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }

        $validated = $this->validateEntry($request, $student);
        $context = $placements->placementContext($student);
        $group = $placements->resolvePlacementGroup($student, $request->integer('clinical_rotation_group_id') ?: null);

        $semesterId = $context['semester']?->id ?? $validated['semester_id'] ?? $this->resolveSemesterId($student);
        if (! $semesterId) {
            return back()->withInput()->with('error', 'No active clinical semester found. Complete semester registration first.');
        }

        $entry = ClinicalLogbookEntry::create([
            ...$validated,
            'student_id' => $student->id,
            'semester_id' => $semesterId,
            'clinical_rotation_group_id' => $group?->id,
            'status' => $request->boolean('submit') ? ClinicalLogbookEntry::STATUS_SUBMITTED : ClinicalLogbookEntry::STATUS_DRAFT,
            'submitted_at' => $request->boolean('submit') ? now() : null,
        ]);

        $message = $entry->status === ClinicalLogbookEntry::STATUS_SUBMITTED
            ? 'Logbook entry submitted for Clinical Instructor review.'
            : 'Logbook entry saved as draft.';

        if ($entry->status === ClinicalLogbookEntry::STATUS_SUBMITTED) {
            $entry->loadMissing('student', 'procedure');
            User::query()->where('role', 'clinical_instructor')->get()
                ->each->notify(new StaffClinicalLogbookSubmittedNotification($entry));
        }

        return redirect()->route('my.clinical.logbook.show', $entry)->with('success', $message);
    }

    public function logbookShow(ClinicalLogbookEntry $clinical_logbook_entry)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }
        $this->authorizeStudentEntry($student, $clinical_logbook_entry);

        $clinical_logbook_entry->load(['procedure', 'semester', 'rotationGroup.round', 'reviewer']);

        return view('clinical.student-logbook-show', [
            'student' => $student,
            'entry' => $clinical_logbook_entry,
        ]);
    }

    public function logbookEdit(ClinicalLogbookEntry $clinical_logbook_entry, ClinicalPlacementService $placements)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }
        $this->authorizeStudentEntry($student, $clinical_logbook_entry);

        if (! $clinical_logbook_entry->isEditableByStudent()) {
            return redirect()->route('my.clinical.logbook.show', $clinical_logbook_entry)
                ->with('error', 'This entry can no longer be edited.');
        }

        $context = $placements->placementContext($student);
        $procedures = $this->proceduresForStudent($student);

        return view('clinical.student-logbook-form', array_merge([
            'student' => $student,
            'entry' => $clinical_logbook_entry,
            'procedures' => $procedures,
            'procedure_select_groups' => $this->procedureSelectGroups($procedures),
            'placement' => $context['primary'],
            'semester' => $clinical_logbook_entry->semester,
            'departmentLabels' => $this->departmentLabelsForStudent($student),
        ], $this->practicumViewData($student, $clinical_logbook_entry->semester_id)));
    }

    public function logbookUpdate(Request $request, ClinicalLogbookEntry $clinical_logbook_entry, ClinicalPlacementService $placements)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }
        $this->authorizeStudentEntry($student, $clinical_logbook_entry);

        if (! $clinical_logbook_entry->isEditableByStudent()) {
            return redirect()->route('my.clinical.logbook.show', $clinical_logbook_entry)
                ->with('error', 'This entry can no longer be edited.');
        }

        $validated = $this->validateEntry($request, $student);
        $submit = $request->boolean('submit');

        $clinical_logbook_entry->update([
            ...$validated,
            'status' => $submit ? ClinicalLogbookEntry::STATUS_SUBMITTED : ClinicalLogbookEntry::STATUS_DRAFT,
            'submitted_at' => $submit ? now() : null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'reviewer_feedback' => null,
        ]);

        return redirect()->route('my.clinical.logbook.show', $clinical_logbook_entry)
            ->with('success', $submit ? 'Entry submitted for review.' : 'Draft updated.');
    }

    public function remediationIndex()
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }

        $plans = \App\Models\ClinicalRemediationPlan::query()
            ->with('logbookEntry')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('clinical.student-remediation', compact('student', 'plans'));
    }

    public function printLogbook(Request $request, ClinicalSummativeService $summative)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }

        $semesterId = $request->integer('semester_id') ?: null;
        $entries = ClinicalLogbookEntry::query()
            ->with(['procedure', 'reviewer', 'semester'])
            ->where('student_id', $student->id)
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->orderBy('performed_on')
            ->get();

        $overview = $summative->overview($student, $semesterId);

        return view('clinical.print-logbook', compact('student', 'entries', 'overview'));
    }

    public function logbookSubmit(ClinicalLogbookEntry $clinical_logbook_entry)
    {
        $student = $this->studentOrRedirect();
        if ($student instanceof \Illuminate\Http\RedirectResponse) {
            return $student;
        }
        $this->authorizeStudentEntry($student, $clinical_logbook_entry);

        if (! $clinical_logbook_entry->isEditableByStudent()) {
            return back()->with('error', 'Entry cannot be submitted.');
        }

        $clinical_logbook_entry->update([
            'status' => ClinicalLogbookEntry::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Submitted to Clinical Instructor for sign-off.');
    }

    private function studentOrRedirect(): Student|\Illuminate\Http\RedirectResponse
    {
        $student = auth()->user()?->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }

        return $student;
    }

    private function authorizeStudentEntry(Student $student, ClinicalLogbookEntry $entry): void
    {
        abort_unless($entry->student_id === $student->id, 403);
    }

    /**
     * @return array<string, string>
     */
    private function departmentLabelsForStudent(Student $student): array
    {
        $nta = (int) ($student->nta_level ?? 5);

        return \App\Support\ClinicalRotationCatalog::departmentLabelsForNtaLevel($nta);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ClinicalProcedure>  $procedures
     * @return array<string, \Illuminate\Support\Collection<int, ClinicalProcedure>>
     */
    private function procedureSelectGroups($procedures): array
    {
        $groups = [];

        foreach ($procedures as $p) {
            if ($p->source_type === 'checklist_criterion') {
                continue;
            }
            $moduleCode = CmtNta4PracticumCatalog::moduleCodeForProcedure($p);
            $label = CmtNta4PracticumCatalog::moduleGroupLabel($moduleCode);
            $groups[$label] = ($groups[$label] ?? collect())->push($p);
        }

        uksort($groups, function (string $a, string $b) use ($groups) {
            $codeA = $this->moduleCodeFromGroupLabel($a, $groups[$a]->first());
            $codeB = $this->moduleCodeFromGroupLabel($b, $groups[$b]->first());

            return CmtNta4PracticumCatalog::compareModuleCodes($codeA, $codeB);
        });

        foreach ($groups as $label => $collection) {
            $groups[$label] = $collection->sortBy([
                ['sort_order', 'asc'],
                ['code', 'asc'],
            ])->values();
        }

        return $groups;
    }

    private function moduleCodeFromGroupLabel(string $label, ClinicalProcedure $sample): string
    {
        if (preg_match('/^(CMT\d+|SEMESTER_I)/', $label, $m)) {
            return $m[1];
        }

        return CmtNta4PracticumCatalog::moduleCodeForProcedure($sample);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, ClinicalProcedure>
     */
    private function proceduresForStudent(\App\Models\Student $student)
    {
        $nta = (int) ($student->nta_level ?? 5);

        return ClinicalProcedure::query()
            ->where('is_active', true)
            ->where('nta_level', $nta)
            ->when($student->programme_id, fn ($q) => $q->where(fn ($q2) => $q2
                ->whereNull('programme_id')
                ->orWhere('programme_id', $student->programme_id)))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateEntry(Request $request, Student $student): array
    {
        $nta = (int) ($student->nta_level ?? 5);

        return $request->validate([
            'clinical_procedure_id' => [
                'required',
                Rule::exists('clinical_procedures', 'id')->where(fn ($q) => $q
                    ->where('is_active', true)
                    ->where('nta_level', $nta)
                    ->where(fn ($q2) => $q2
                        ->whereNull('source_type')
                        ->orWhere('source_type', '!=', 'checklist_criterion'))),
            ],
            'semester_id' => ['nullable', 'exists:semesters,id'],
            'performed_on' => ['required', 'date', 'before_or_equal:today'],
            'department_code' => ['nullable', 'string', 'max:64'],
            'hospital_code' => ['nullable', 'string', 'max:32'],
            'case_reference' => ['nullable', 'string', 'max:64'],
            'case_summary' => ['required', 'string', 'min:20', 'max:5000'],
            'skills_notes' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function resolveSemesterId(Student $student): ?int
    {
        $id = $student->semesterRegistrations()
            ->where('status', 'approved')
            ->whereNull('wizard_step')
            ->whereHas('semester', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('updated_at')
            ->value('semester_id');

        return $id ? (int) $id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function practicumViewData(Student $student, ?int $semesterId = null): array
    {
        $nta = (int) ($student->nta_level ?? 0);
        if (! CmtPracticumCatalog::isSupportedLevel($nta)) {
            return [
                'practicum_guide' => null,
                'competency_checklist' => [],
                'practicum_by_department' => [],
                'assessment_methods' => [],
                'practicum_nta_level' => $nta,
            ];
        }

        $catalog = CmtPracticumCatalog::forLevel($nta);
        $checklist = app(ClinicalCompetencyService::class)->checklistForStudent($student, $semesterId);
        $practicumGuide = app(ClinicalPracticumGuideService::class)->guideForStudent($student);

        return [
            'practicum_guide' => $practicumGuide,
            'competency_checklist' => $checklist,
            'competency_checklist_groups' => $this->groupCompetencyChecklist($checklist, $nta),
            'procedure_guides' => $this->procedureGuidesForLevel($nta, $practicumGuide['url'] ?? null),
            'practicum_by_department' => $catalog->proceduresByDepartment(),
            'rotation_area_labels' => $catalog->rotationAreas(),
            'assessment_methods' => $catalog->assessmentMethods(),
            'practicum_nta_level' => $nta,
        ];
    }

    /**
     * Instruction guides keyed by clinical_procedure id (for modal popup).
     *
     * @return array<int, array{code: string, title: string, steps: list<string>, assessment_modes: list<string>, notes: ?string}>
     */
    private function procedureGuidesForLevel(int $nta, ?string $pdfUrl = null): array
    {
        $stepsByParent = ClinicalProcedure::query()
            ->where('is_active', true)
            ->where('nta_level', $nta)
            ->where('source_type', 'checklist_criterion')
            ->whereNotNull('parent_code')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['parent_code', 'name'])
            ->groupBy('parent_code');

        $procedures = ClinicalProcedure::query()
            ->where('is_active', true)
            ->where('nta_level', $nta)
            ->whereIn('source_type', ['checklist', 'module_competency'])
            ->orderBy('sort_order')
            ->get();

        $guides = [];
        foreach ($procedures as $p) {
            $steps = [];
            if ($p->source_type === 'checklist') {
                $steps = ($stepsByParent[$p->code] ?? collect())
                    ->map(fn ($s) => $s->name)
                    ->values()
                    ->all();
            }

            $guides[$p->id] = [
                'code' => $p->code,
                'title' => $p->name,
                'steps' => $steps,
                'assessment_modes' => $p->assessmentModesList(),
                'notes' => $p->description,
                'pdf_url' => $pdfUrl,
            ];
        }

        return $guides;
    }

    /**
     * @param  list<array{procedure: ClinicalProcedure, required: int, approved: int, met: bool}>  $items
     * @return list<array{key: string, title: string, items: list<array{procedure: ClinicalProcedure, required: int, approved: int, met: bool}>, subsections: list<array{key: string, title: string, header_item: array{procedure: ClinicalProcedure, required: int, approved: int, met: bool}, steps: list<array{name: string, code: string}>, met: int, total: int}>, met: int, total: int}>
     */
    private function groupCompetencyChecklist(array $items, int $nta): array
    {
        $catalog = CmtPracticumCatalog::forLevel($nta);
        $stepsByParent = ClinicalProcedure::query()
            ->where('is_active', true)
            ->where('nta_level', $nta)
            ->where('source_type', 'checklist_criterion')
            ->whereNotNull('parent_code')
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['parent_code', 'name', 'code'])
            ->groupBy('parent_code');

        $modules = [];
        foreach ($items as $item) {
            $p = $item['procedure'];
            $moduleCode = $catalog->moduleCodeForProcedure($p);
            if (! isset($modules[$moduleCode])) {
                $modules[$moduleCode] = [
                    'key' => 'mod-'.$moduleCode,
                    'title' => $catalog->moduleGroupLabel($moduleCode),
                    'items' => [],
                    'subsections' => [],
                ];
            }

            if ($p->source_type === 'checklist') {
                $steps = ($stepsByParent[$p->code] ?? collect())
                    ->map(fn ($s) => ['code' => $s->code, 'name' => $s->name])
                    ->values()
                    ->all();
                $modules[$moduleCode]['subsections'][$p->code] = [
                    'key' => $p->code,
                    'title' => $p->name,
                    'header_item' => $item,
                    'steps' => $steps,
                ];

                continue;
            }

            if ($p->source_type === 'module_competency') {
                $modules[$moduleCode]['items'][] = $item;
            }
        }

        $groups = [];
        foreach ($modules as $moduleCode => $mod) {
            $scorable = $mod['items'];
            foreach ($mod['subsections'] as $sub) {
                $scorable[] = $sub['header_item'];
            }
            $mod['total'] = count($scorable);
            $mod['met'] = collect($scorable)->filter(fn ($i) => $i['met'])->count();

            $subsections = [];
            foreach ($mod['subsections'] as $sub) {
                $header = $sub['header_item'];
                $sub['total'] = 1;
                $sub['met'] = $header['met'] ? 1 : 0;
                $subsections[] = $sub;
            }
            usort($subsections, fn ($a, $b) => strcmp($a['key'], $b['key']));
            $mod['subsections'] = $subsections;
            $mod['_module_code'] = $moduleCode;
            $groups[] = $mod;
        }

        usort($groups, fn ($a, $b) => $catalog->compareModuleCodes(
            $a['_module_code'],
            $b['_module_code']
        ));

        foreach ($groups as &$g) {
            unset($g['_module_code']);
        }

        return $groups;
    }
}
