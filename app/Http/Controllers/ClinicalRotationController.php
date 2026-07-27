<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\ClinicalRotationAttendanceMark;
use App\Models\ClinicalRotationGroup;
use App\Models\ClinicalRotationRound;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Student;
use App\Services\StudentModuleEnrollmentService;
use App\Support\ClinicalRotationCatalog;
use App\Support\ClinicalRotationFullRoster;
use App\Support\ClinicalRotationRosterCsv;
use App\Support\ClinicalRotationScheduleExport;
use App\Support\ClinicalRotationScheduleTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClinicalRotationController extends Controller
{
    use BulkDestroysRecords;

    public function index()
    {
        $rounds = ClinicalRotationRound::query()
            ->with(['semester', 'programme', 'groups'])
            ->latest()
            ->paginate(15);
        $programmes = Programme::query()->where('is_active', true)->orderBy('code')->get();
        $defaultScheduleMonday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');

        return view('clinical-rotations.index', compact('rounds', 'programmes', 'defaultScheduleMonday'));
    }

    public function create()
    {
        $semesters = Semester::query()
            ->where('number', Semester::PERIOD_SECOND)
            ->where('is_active', true)
            ->orderByDesc('academic_year')
            ->orderBy('number')
            ->get();
        $programmes = Programme::query()->where('is_active', true)->orderBy('code')->get();

        return view('clinical-rotations.create', compact('semesters', 'programmes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'nta_level' => ['required', 'integer', Rule::in([4, 5, 6])],
            'rotation_week_monday' => ['required', 'date'],
            'rotation_week_friday' => ['required', 'date'],
            'schedule_weeks_per_block' => ['required', 'integer', Rule::in([1, 2])],
            'title' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $monday = Carbon::parse($validated['rotation_week_monday'])->startOfDay();
        $friday = Carbon::parse($validated['rotation_week_friday'])->startOfDay();
        if (! $monday->isMonday()) {
            throw ValidationException::withMessages([
                'rotation_week_monday' => 'Rotation week must start on a Monday.',
            ]);
        }
        if (! $friday->isFriday()) {
            throw ValidationException::withMessages([
                'rotation_week_friday' => 'Rotation week must end on a Friday.',
            ]);
        }
        if (! $monday->copy()->addDays(4)->isSameDay($friday)) {
            throw ValidationException::withMessages([
                'rotation_week_friday' => 'Use the Friday in the same week as the Monday you chose (exactly four days after that Monday).',
            ]);
        }

        $semester = Semester::findOrFail($validated['semester_id']);
        if ((int) $semester->number !== Semester::PERIOD_SECOND) {
            throw ValidationException::withMessages([
                'semester_id' => 'Select Semester II — clinical rotations are tracked for the second teaching period.',
            ]);
        }

        $round = DB::transaction(function () use ($validated, $monday, $friday) {
            $round = ClinicalRotationRound::create([
                'semester_id' => $validated['semester_id'],
                'programme_id' => $validated['programme_id'],
                'nta_level' => $validated['nta_level'],
                'rotation_week_monday' => $monday->toDateString(),
                'rotation_week_friday' => $friday->toDateString(),
                'schedule_weeks_per_block' => (int) $validated['schedule_weeks_per_block'],
                'title' => $validated['title'] ?? null,
                'capacity_per_group' => null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $slotCount = ClinicalRotationCatalog::groupSlotCountForNta((int) $validated['nta_level']);
            $deptKeys = array_keys(ClinicalRotationCatalog::departmentLabelsForNtaLevel((int) $validated['nta_level']));
            shuffle($deptKeys);
            $deptKeys = array_values(array_slice($deptKeys, 0, $slotCount));

            for ($slot = 1; $slot <= $slotCount; $slot++) {
                ClinicalRotationGroup::create([
                    'clinical_rotation_round_id' => $round->id,
                    'slot_number' => $slot,
                    'name' => 'Rotation Group '.$slot,
                    'department_code' => $deptKeys[$slot - 1],
                    'hospital_code' => null,
                ]);
            }

            $this->allocateStudents($round->fresh(['groups']), app(StudentModuleEnrollmentService::class));

            return $round;
        });

        $n = ClinicalRotationCatalog::groupSlotCountForNta((int) $validated['nta_level']);

        return redirect()
            ->route('clinical-rotations.show', $round)
            ->with('success', $n.' groups were created with '.$n.' different departments (random order). Active students who registered at least one rotation module for this semester were placed in exactly one group. Assign hospitals on this page when ready.');
    }

    public function show(ClinicalRotationRound $clinical_rotation_round, StudentModuleEnrollmentService $enrollmentService)
    {
        $clinical_rotation_round->load([
            'semester',
            'programme',
            'groups.students',
        ]);

        $rotationEligibleQuery = $enrollmentService->rotationEligibleStudentsQuery($clinical_rotation_round);

        $eligibleTotal = (clone $rotationEligibleQuery)->count();
        $activeAtLevel = Student::query()
            ->where('programme_id', $clinical_rotation_round->programme_id)
            ->where('nta_level', $clinical_rotation_round->nta_level)
            ->where('status', 'active')
            ->count();
        $excludedFromRotation = max(0, $activeAtLevel - $eligibleTotal);

        $assigned = (int) Student::query()
            ->whereIn('id', function ($q) use ($clinical_rotation_round) {
                $q->select('cgs.student_id')
                    ->from('crt_group_students as cgs')
                    ->join('clinical_rotation_groups as cg', 'cg.id', '=', 'cgs.clinical_rotation_group_id')
                    ->where('cg.clinical_rotation_round_id', $clinical_rotation_round->id);
            })
            ->count();

        $departmentLabels = ClinicalRotationCatalog::departmentLabelsForNtaLevel((int) $clinical_rotation_round->nta_level);
        $hospitalLabels = ClinicalRotationCatalog::hospitalLabels();
        $groupSizes = $clinical_rotation_round->groups->map(fn (ClinicalRotationGroup $g) => $g->students->count())->values()->all();
        $scheduleWeeksPerBlock = $clinical_rotation_round->schedule_weeks_per_block
            ?? ClinicalRotationScheduleTemplate::defaultWeeksPerBlockForNta((int) $clinical_rotation_round->nta_level);

        return view('clinical-rotations.show', compact(
            'clinical_rotation_round',
            'eligibleTotal',
            'activeAtLevel',
            'excludedFromRotation',
            'assigned',
            'groupSizes',
            'departmentLabels',
            'hospitalLabels',
            'scheduleWeeksPerBlock'
        ));
    }

    public function updateGroup(Request $request, ClinicalRotationRound $clinical_rotation_round, ClinicalRotationGroup $clinical_rotation_group)
    {
        $this->assertGroupBelongsToRound($clinical_rotation_round, $clinical_rotation_group);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'department_code' => [
                'required',
                'string',
                Rule::in(array_keys(ClinicalRotationCatalog::departmentLabelsForNtaLevel((int) $clinical_rotation_round->nta_level))),
                Rule::unique('clinical_rotation_groups', 'department_code')
                    ->where('clinical_rotation_round_id', $clinical_rotation_round->id)
                    ->ignore($clinical_rotation_group->id),
            ],
            'hospital_code' => ['nullable', 'string', Rule::in(array_keys(ClinicalRotationCatalog::hospitalLabels()))],
        ]);

        $hospital = $validated['hospital_code'] ?? null;
        if ($hospital !== null && $hospital !== '') {
            $hospital = ClinicalRotationCatalog::normalizeHospitalCode($hospital);
        } else {
            $hospital = null;
        }

        $clinical_rotation_group->update([
            'name' => $validated['name'],
            'department_code' => $validated['department_code'],
            'hospital_code' => $hospital,
        ]);

        return redirect()
            ->route('clinical-rotations.show', $clinical_rotation_round)
            ->with('success', 'Group '.$clinical_rotation_group->slot_number.' updated.');
    }

    public function regenerate(ClinicalRotationRound $clinical_rotation_round)
    {
        DB::transaction(function () use ($clinical_rotation_round) {
            $clinical_rotation_round->update(['capacity_per_group' => null]);
            $groupIds = $clinical_rotation_round->groups()->pluck('id');
            ClinicalRotationAttendanceMark::query()->whereIn('clinical_rotation_group_id', $groupIds)->delete();
            $this->allocateStudents($clinical_rotation_round->fresh(['groups']), app(StudentModuleEnrollmentService::class));
        });

        $n = ClinicalRotationCatalog::groupSlotCountForNta((int) $clinical_rotation_round->nta_level);

        return redirect()
            ->route('clinical-rotations.show', $clinical_rotation_round)
            ->with('success', 'Students re-divided across the '.$n.' groups using the current active list for this programme and NTA level. Weekly Mon–Fri attendance for this round was cleared.');
    }

    public function editAttendance(Request $request, ClinicalRotationRound $clinical_rotation_round, ClinicalRotationGroup $clinical_rotation_group)
    {
        $this->assertGroupBelongsToRound($clinical_rotation_round, $clinical_rotation_group);

        $clinical_rotation_group->load(['students']);
        $weekStart = $this->resolveAttendanceWeekStart($request, $clinical_rotation_round)->toDateString();

        $marks = ClinicalRotationAttendanceMark::query()
            ->where('clinical_rotation_group_id', $clinical_rotation_group->id)
            ->whereDate('week_starting', $weekStart)
            ->get()
            ->keyBy('student_id');

        return view('clinical-rotations.attendance', compact(
            'clinical_rotation_round',
            'clinical_rotation_group',
            'weekStart',
            'marks'
        ));
    }

    public function storeAttendance(Request $request, ClinicalRotationRound $clinical_rotation_round, ClinicalRotationGroup $clinical_rotation_group)
    {
        $this->assertGroupBelongsToRound($clinical_rotation_round, $clinical_rotation_group);

        $clinical_rotation_group->load('students');
        if ($clinical_rotation_group->students->isEmpty()) {
            return redirect()
                ->route('clinical-rotations.attendance.edit', [$clinical_rotation_round, $clinical_rotation_group])
                ->with('warning', 'This group has no students yet.');
        }

        $rows = collect($request->input('rows', []))->map(function (array $row) {
            foreach (['mon', 'tue', 'wed', 'thu', 'fri'] as $d) {
                if (! isset($row[$d]) || $row[$d] === '') {
                    $row[$d] = null;
                }
            }

            return $row;
        })->values()->all();
        $request->merge(['rows' => $rows]);

        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.student_id' => ['required', 'exists:students,id'],
            'rows.*.mon' => ['nullable', Rule::in(['1', '0'])],
            'rows.*.tue' => ['nullable', Rule::in(['1', '0'])],
            'rows.*.wed' => ['nullable', Rule::in(['1', '0'])],
            'rows.*.thu' => ['nullable', Rule::in(['1', '0'])],
            'rows.*.fri' => ['nullable', Rule::in(['1', '0'])],
        ]);

        $week = Carbon::parse($validated['week_start'])->startOfDay();
        if (! $week->isMonday()) {
            throw ValidationException::withMessages([
                'week_start' => 'Week start must be a Monday.',
            ]);
        }

        $allowedIds = $clinical_rotation_group->students->pluck('id')->all();

        DB::transaction(function () use ($validated, $clinical_rotation_group, $week, $allowedIds) {
            foreach ($validated['rows'] as $row) {
                $sid = (int) $row['student_id'];
                if (! in_array($sid, $allowedIds, true)) {
                    continue;
                }
                ClinicalRotationAttendanceMark::query()->updateOrCreate(
                    [
                        'clinical_rotation_group_id' => $clinical_rotation_group->id,
                        'student_id' => $sid,
                        'week_starting' => $week->toDateString(),
                    ],
                    [
                        'mon_present' => $this->parseDay($row['mon'] ?? null),
                        'tue_present' => $this->parseDay($row['tue'] ?? null),
                        'wed_present' => $this->parseDay($row['wed'] ?? null),
                        'thu_present' => $this->parseDay($row['thu'] ?? null),
                        'fri_present' => $this->parseDay($row['fri'] ?? null),
                    ]
                );
            }
        });

        return redirect()
            ->route('clinical-rotations.attendance.edit', [
                'clinical_rotation_round' => $clinical_rotation_round,
                'clinical_rotation_group' => $clinical_rotation_group,
                'week_start' => $week->toDateString(),
            ])
            ->with('success', 'Attendance saved for week of '.$week->format('d M Y').'.');
    }

    public function destroy(ClinicalRotationRound $clinical_rotation_round)
    {
        $clinical_rotation_round->delete();

        return redirect()
            ->route('clinical-rotations.index')
            ->with('success', 'Clinical rotation round deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            ClinicalRotationRound::class,
            'clinical-rotations.index',
            singularLabel: 'rotation round',
        );
    }

    public function printRoster(ClinicalRotationRound $clinical_rotation_round)
    {
        $clinical_rotation_round->load([
            'semester',
            'programme',
            'groups.students',
        ]);
        $departmentLabels = ClinicalRotationCatalog::departmentLabelsForNtaLevel((int) $clinical_rotation_round->nta_level);

        return view('clinical-rotations.roster-print', compact('clinical_rotation_round', 'departmentLabels'));
    }

    /**
     * Complete student roster for every Mon–Fri week in the rotation cycle (all groups, all weeks).
     */
    public function printFullRoster(Request $request, ClinicalRotationRound $clinical_rotation_round)
    {
        if (! in_array((int) $clinical_rotation_round->nta_level, [4, 5, 6], true)) {
            return redirect()
                ->route('clinical-rotations.show', $clinical_rotation_round)
                ->with('warning', 'The full multi-week roster is only available for NTA Levels 4, 5, and 6.');
        }

        [$startMonday, $usedDefaultStart] = $this->resolveScheduleTemplateStart($request, $clinical_rotation_round);
        $weeksPerBlock = $this->resolveScheduleWeeksPerBlock($request, $clinical_rotation_round);

        try {
            $roster = ClinicalRotationFullRoster::build($clinical_rotation_round, $startMonday, $weeksPerBlock);
        } catch (\Throwable $e) {
            return redirect()
                ->route('clinical-rotations.show', $clinical_rotation_round)
                ->with('warning', $e->getMessage());
        }

        return view('clinical-rotations.roster-all-weeks-print', compact('roster', 'usedDefaultStart'));
    }

    public function downloadFullRosterCsv(Request $request, ClinicalRotationRound $clinical_rotation_round)
    {
        if (! in_array((int) $clinical_rotation_round->nta_level, [4, 5, 6], true)) {
            return redirect()
                ->route('clinical-rotations.show', $clinical_rotation_round)
                ->with('warning', 'The full multi-week roster is only available for NTA Levels 4, 5, and 6.');
        }

        [$startMonday] = $this->resolveScheduleTemplateStart($request, $clinical_rotation_round);
        $weeksPerBlock = $this->resolveScheduleWeeksPerBlock($request, $clinical_rotation_round);

        try {
            $roster = ClinicalRotationFullRoster::build($clinical_rotation_round, $startMonday, $weeksPerBlock);
        } catch (\Throwable $e) {
            return redirect()
                ->route('clinical-rotations.show', $clinical_rotation_round)
                ->with('warning', $e->getMessage());
        }

        $code = Str::slug($clinical_rotation_round->programme?->code ?? 'programme', '-');
        $filename = 'clinical-roster-all-weeks-'.$code.'-nta'.$clinical_rotation_round->nta_level.'-'.$startMonday->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($roster) {
            $out = fopen('php://output', 'w');
            ClinicalRotationRosterCsv::writeBom($out);
            ClinicalRotationRosterCsv::writeFullWeeksRoster($out, $roster);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * NTA-style rotation schedule grid (print or save as PDF from the browser).
     */
    public function printScheduleTemplate(Request $request, ClinicalRotationRound $clinical_rotation_round)
    {
        if (! in_array((int) $clinical_rotation_round->nta_level, [4, 5, 6], true)) {
            return redirect()
                ->route('clinical-rotations.show', $clinical_rotation_round)
                ->with('warning', 'The rotation schedule template is only available for NTA Levels 4, 5, and 6.');
        }

        $clinical_rotation_round->load(['semester', 'programme']);

        [$startMonday, $usedDefaultStart] = $this->resolveScheduleTemplateStart($request, $clinical_rotation_round);
        $weeksPerBlock = $this->resolveScheduleWeeksPerBlock($request, $clinical_rotation_round);

        $payload = ClinicalRotationScheduleTemplate::documentPayload($startMonday, (int) $clinical_rotation_round->nta_level, $weeksPerBlock);

        return view('clinical-rotations.schedule-print', compact(
            'clinical_rotation_round',
            'payload',
            'usedDefaultStart'
        ));
    }

    public function exportScheduleRound(Request $request, ClinicalRotationRound $clinical_rotation_round)
    {
        if (! in_array((int) $clinical_rotation_round->nta_level, [4, 5, 6], true)) {
            return redirect()
                ->route('clinical-rotations.show', $clinical_rotation_round)
                ->with('warning', 'Schedule export is only available for NTA Levels 4, 5, and 6.');
        }

        $validated = $request->validate([
            'format' => ['required', 'string', Rule::in(['docx', 'pdf'])],
            'weeks_per_block' => ['nullable', 'integer', Rule::in([1, 2])],
            'start' => ['nullable', 'date'],
        ]);

        $clinical_rotation_round->load(['semester', 'programme']);

        [$startMonday] = $this->resolveScheduleTemplateStart($request, $clinical_rotation_round);
        $weeksPerBlock = $this->resolveScheduleWeeksPerBlock($request, $clinical_rotation_round);
        $payload = ClinicalRotationScheduleTemplate::documentPayload($startMonday, (int) $clinical_rotation_round->nta_level, $weeksPerBlock);

        $programmeName = $clinical_rotation_round->programme?->name.' ('.$clinical_rotation_round->programme?->code.')';
        $semesterLabel = $clinical_rotation_round->semester?->label;

        return $validated['format'] === 'pdf'
            ? ClinicalRotationScheduleExport::pdfResponse($payload, $programmeName, $semesterLabel)
            : ClinicalRotationScheduleExport::wordDownloadResponse($payload, $programmeName, $semesterLabel);
    }

    public function exportScheduleStandalone(Request $request)
    {
        $validated = $request->validate([
            'nta_level' => ['required', 'integer', Rule::in([4, 5, 6])],
            'start' => ['required', 'date'],
            'format' => ['required', 'string', Rule::in(['docx', 'pdf'])],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'weeks_per_block' => ['nullable', 'integer', Rule::in([1, 2])],
        ]);

        $startMonday = Carbon::parse($validated['start'])->startOfDay();
        if (! $startMonday->isMonday()) {
            throw ValidationException::withMessages([
                'start' => 'Schedule start must be a Monday.',
            ]);
        }

        $weeksPerBlock = $request->filled('weeks_per_block')
            ? (int) $validated['weeks_per_block']
            : ClinicalRotationScheduleTemplate::defaultWeeksPerBlockForNta((int) $validated['nta_level']);

        $payload = ClinicalRotationScheduleTemplate::documentPayload($startMonday, (int) $validated['nta_level'], $weeksPerBlock);
        $programme = isset($validated['programme_id']) ? Programme::find($validated['programme_id']) : null;
        $programmeName = $programme ? $programme->name.' ('.$programme->code.')' : null;
        $semesterLabel = null;

        return $validated['format'] === 'pdf'
            ? ClinicalRotationScheduleExport::pdfResponse($payload, $programmeName, $semesterLabel)
            : ClinicalRotationScheduleExport::wordDownloadResponse($payload, $programmeName, $semesterLabel);
    }

    public function downloadRosterCsv(ClinicalRotationRound $clinical_rotation_round)
    {
        $clinical_rotation_round->load(['programme', 'semester', 'groups.students']);
        $code = Str::slug($clinical_rotation_round->programme?->code ?? 'programme', '-');
        $filename = 'clinical-roster-'.$code.'-nta'.$clinical_rotation_round->nta_level.'.csv';

        return response()->streamDownload(function () use ($clinical_rotation_round) {
            $out = fopen('php://output', 'w');
            ClinicalRotationRosterCsv::writeBom($out);
            ClinicalRotationRosterCsv::writeRoundPostingRoster($out, $clinical_rotation_round);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadGroupAttendanceCsv(Request $request, ClinicalRotationRound $clinical_rotation_round, ClinicalRotationGroup $clinical_rotation_group)
    {
        $this->assertGroupBelongsToRound($clinical_rotation_round, $clinical_rotation_group);
        $week = $this->resolveAttendanceWeekStart($request, $clinical_rotation_round);
        $clinical_rotation_group->load('students');

        $marks = ClinicalRotationAttendanceMark::query()
            ->where('clinical_rotation_group_id', $clinical_rotation_group->id)
            ->whereDate('week_starting', $week->toDateString())
            ->get()
            ->keyBy('student_id');

        $code = Str::slug($clinical_rotation_round->programme?->code ?? 'programme', '-');
        $filename = 'attendance-g'.$clinical_rotation_group->slot_number.'-'.$week->format('Y-m-d').'-'.$code.'.csv';

        return response()->streamDownload(function () use ($clinical_rotation_group, $week, $marks) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Group',
                'Week starting (Monday)',
                'Student name',
                'Registration no.',
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
            ]);
            foreach ($clinical_rotation_group->students as $stu) {
                $m = $marks->get($stu->id);
                fputcsv($out, [
                    $clinical_rotation_group->name,
                    $week->format('Y-m-d'),
                    $stu->full_name,
                    $stu->registrationNumberDisplay(),
                    $this->attendanceCellLabel($m?->mon_present),
                    $this->attendanceCellLabel($m?->tue_present),
                    $this->attendanceCellLabel($m?->wed_present),
                    $this->attendanceCellLabel($m?->thu_present),
                    $this->attendanceCellLabel($m?->fri_present),
                ]);
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadRoundAttendanceCsv(Request $request, ClinicalRotationRound $clinical_rotation_round)
    {
        $request->validate([
            'week_start' => ['required', 'date'],
        ]);
        $week = Carbon::parse($request->input('week_start'))->startOfDay();
        if (! $week->isMonday()) {
            throw ValidationException::withMessages([
                'week_start' => 'Week start must be a Monday.',
            ]);
        }

        $clinical_rotation_round->load(['programme', 'groups.students']);
        $groupIds = $clinical_rotation_round->groups->pluck('id');
        $marksByGroup = ClinicalRotationAttendanceMark::query()
            ->whereIn('clinical_rotation_group_id', $groupIds)
            ->whereDate('week_starting', $week->toDateString())
            ->get()
            ->groupBy('clinical_rotation_group_id')
            ->map(fn ($rows) => $rows->keyBy('student_id'));

        $code = Str::slug($clinical_rotation_round->programme?->code ?? 'programme', '-');
        $filename = 'attendance-all-groups-'.$week->format('Y-m-d').'-'.$code.'.csv';

        return response()->streamDownload(function () use ($clinical_rotation_round, $week, $marksByGroup) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                'Group slot',
                'Group name',
                'Week starting (Monday)',
                'Student name',
                'Registration no.',
                'Mon',
                'Tue',
                'Wed',
                'Thu',
                'Fri',
            ]);
            foreach ($clinical_rotation_round->groups->sortBy('slot_number') as $group) {
                $marks = $marksByGroup->get((int) $group->id, collect());
                foreach ($group->students as $stu) {
                    $m = $marks->get($stu->id);
                    fputcsv($out, [
                        $group->slot_number,
                        $group->name,
                        $week->format('Y-m-d'),
                        $stu->full_name,
                        $stu->registrationNumberDisplay(),
                        $this->attendanceCellLabel($m?->mon_present),
                        $this->attendanceCellLabel($m?->tue_present),
                        $this->attendanceCellLabel($m?->wed_present),
                        $this->attendanceCellLabel($m?->thu_present),
                        $this->attendanceCellLabel($m?->fri_present),
                    ]);
                }
            }
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function resolveAttendanceWeekStart(Request $request, ClinicalRotationRound $round): Carbon
    {
        $weekStart = $request->get('week_start');
        if ($weekStart) {
            $d = Carbon::parse($weekStart)->startOfDay();
            if (! $d->isMonday()) {
                throw ValidationException::withMessages([
                    'week_start' => 'Week start must be a Monday.',
                ]);
            }

            return $d;
        }
        if ($round->rotation_week_monday) {
            return Carbon::parse($round->rotation_week_monday)->startOfDay();
        }

        return Carbon::now()->startOfWeek(Carbon::MONDAY);
    }

    private function attendanceCellLabel(?bool $present): string
    {
        if ($present === true) {
            return 'P';
        }
        if ($present === false) {
            return 'A';
        }

        return '';
    }

    private function assertGroupBelongsToRound(ClinicalRotationRound $round, ClinicalRotationGroup $clinical_rotation_group): void
    {
        if ((int) $clinical_rotation_group->clinical_rotation_round_id !== (int) $round->id) {
            abort(404);
        }
    }

    private function parseDay(mixed $v): ?bool
    {
        if ($v === null || $v === '') {
            return null;
        }

        return match ((string) $v) {
            '1' => true,
            '0' => false,
            default => null,
        };
    }

    private function allocateStudents(ClinicalRotationRound $round, StudentModuleEnrollmentService $enrollmentService): void
    {
        $round->loadMissing('groups');
        $groups = $round->groups()->orderBy('slot_number')->get();
        $n = ClinicalRotationCatalog::groupSlotCountForNta((int) $round->nta_level);
        if ($groups->count() !== $n) {
            return;
        }

        foreach ($groups as $g) {
            $g->students()->detach();
        }

        $students = $enrollmentService
            ->rotationEligibleStudentsQuery($round)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $ids = $students->pluck('id')->values();
        $t = $ids->count();
        if ($t === 0) {
            return;
        }

        // Split all students evenly across n groups (first (t mod n) groups get one extra).
        $base = intdiv($t, $n);
        $extra = $t % $n;
        $buckets = array_fill(0, $n, []);
        $cursor = 0;
        for ($i = 0; $i < $n; $i++) {
            $size = $base + ($i < $extra ? 1 : 0);
            for ($j = 0; $j < $size; $j++) {
                $buckets[$i][] = $ids[$cursor++];
            }
        }
        foreach ($groups as $i => $g) {
            $pos = 0;
            foreach ($buckets[$i] as $sid) {
                $g->students()->attach($sid, ['position' => $pos++]);
            }
        }
    }

    private function resolveScheduleWeeksPerBlock(Request $request, ClinicalRotationRound $round): int
    {
        $raw = $request->query('weeks_per_block');
        if ($raw !== null && $raw !== '') {
            $w = (int) $raw;
            if (! in_array($w, [1, 2], true)) {
                throw ValidationException::withMessages([
                    'weeks_per_block' => 'Weeks per department must be 1 or 2.',
                ]);
            }

            return $w;
        }

        if ($round->schedule_weeks_per_block !== null) {
            return (int) $round->schedule_weeks_per_block;
        }

        return ClinicalRotationScheduleTemplate::defaultWeeksPerBlockForNta((int) $round->nta_level);
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: bool} Monday start and whether today’s Monday was used as fallback
     */
    private function resolveScheduleTemplateStart(Request $request, ClinicalRotationRound $round): array
    {
        $startParam = $request->query('start');
        if ($startParam) {
            $startMonday = Carbon::parse($startParam)->startOfDay();
            if (! $startMonday->isMonday()) {
                throw ValidationException::withMessages([
                    'start' => 'Schedule start must be a Monday (use start=YYYY-MM-DD).',
                ]);
            }

            return [$startMonday, false];
        }
        if ($round->rotation_week_monday) {
            return [Carbon::parse($round->rotation_week_monday)->startOfDay(), false];
        }

        return [Carbon::now()->startOfWeek(Carbon::MONDAY), true];
    }
}
