<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Student;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;

class TimetableSlotController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $hodProgrammeId = auth()->user()->hodProgrammeId();
        $programmeId = $hodProgrammeId ?: $request->get('programme_id');

        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $programmes = Programme::where('is_active', true)
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('id', $pid))
            ->orderBy('code')->get();

        $query = TimetableSlot::with(['semester', 'course'])
            ->when($hodProgrammeId, fn ($q, $pid) => $q->whereHas('course', fn ($cq) => $cq->where('programme_id', $pid)));
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        if ($programmeId) {
            $query->whereHas('course', fn ($cq) => $cq->where('programme_id', $programmeId));
        }
        $slots = $query->with('course')->orderBy('day_of_week')->orderBy('start_time')->get();

        // A department's weekly timetable is scoped to one programme AND one NTA level (its
        // own cohort) — mixing either dimension together would let unrelated classes silently
        // overwrite each other's grid cell. Build one panel per (programme, level) combination
        // that actually has modules defined, instead of one shared grid.
        $combos = Course::where('is_active', true)
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->when($programmeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->whereNotNull('nta_level')
            ->select('programme_id', 'nta_level')
            ->distinct()
            ->get()
            ->sortBy([['programme_id', 'asc'], ['nta_level', 'asc']])
            ->values();

        $programmesById = Programme::whereIn('id', $combos->pluck('programme_id')->unique())->get()->keyBy('id');
        $selectedSemester = $semesterId ? $semesters->firstWhere('id', (int) $semesterId) : null;

        $panels = [];
        foreach ($combos as $combo) {
            $level = (int) $combo->nta_level;
            $comboSlots = $slots->filter(fn ($s) => $s->course
                && (int) $s->course->programme_id === (int) $combo->programme_id
                && (int) $s->course->nta_level === $level)->values();

            $grid = [];
            $gridSlotIds = [];
            foreach (TimetableSlot::WEEK_DAYS as $day) {
                foreach (TimetableSlot::DAILY_SESSIONS as $session) {
                    $match = $comboSlots->first(fn ($s) => (int) $s->day_of_week === $day
                        && substr((string) $s->start_time, 0, 5) === $session['start']
                        && substr((string) $s->end_time, 0, 5) === $session['end']);
                    $grid[$day][$session['start']] = $match;
                    if ($match) {
                        $gridSlotIds[] = $match->id;
                    }
                }
            }

            $panels[] = [
                'programme' => $programmesById->get($combo->programme_id),
                'level' => $level,
                'level_label' => Student::NTA_LEVELS[$level] ?? "NTA Level {$level}",
                'grid' => $grid,
                'other_slots' => $comboSlots->reject(fn ($s) => in_array($s->id, $gridSlotIds, true))->values(),
            ];
        }

        return view('timetable-slots.index', compact('slots', 'semesters', 'semesterId', 'programmes', 'programmeId', 'panels', 'selectedSemester'));
    }

    public function autoGenerate(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $hodProgrammeId = auth()->user()->hodProgrammeId();

        $courses = Course::where('is_active', true)
            ->whereIn('id', $validated['course_ids'])
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $semester->id))
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->orderBy('code')
            ->get();

        $redirectParams = ['semester_id' => $semester->id];

        if ($courses->isEmpty()) {
            return redirect()->route('timetable-slots.index', $redirectParams)
                ->with('error', 'None of the selected modules belong to this semester — nothing to schedule.');
        }

        $cells = [];
        foreach (TimetableSlot::WEEK_DAYS as $day) {
            foreach (TimetableSlot::DAILY_SESSIONS as $session) {
                $cells[] = ['day' => $day, 'start' => $session['start'], 'end' => $session['end']];
            }
        }
        $maxModules = (int) floor(count($cells) / 2);

        if ($courses->count() > $maxModules) {
            return redirect()->route('timetable-slots.index', $redirectParams)
                ->with('error', "Auto-generate fits up to {$maxModules} modules a week (2 sessions each across ".count($cells)." weekly slots). This selection has {$courses->count()} modules — uncheck some or add extra slots manually.");
        }

        TimetableSlot::where('semester_id', $semester->id)
            ->whereIn('course_id', $courses->pluck('id'))
            ->delete();

        // True random placement: shuffle the weekly slot pool, then for each module
        // draw two cells that aren't on the same day where possible.
        $pool = $cells;
        shuffle($pool);

        foreach ($courses->shuffle()->values() as $course) {
            $first = array_shift($pool);
            $sameDayIndex = collect($pool)->search(fn ($cell) => $cell['day'] === $first['day']);
            if ($sameDayIndex !== false && count($pool) > 1) {
                // Prefer a cell on a different day for the second session when one is available.
                $differentDayIndex = collect($pool)->search(fn ($cell) => $cell['day'] !== $first['day']);
                $second = $differentDayIndex !== false
                    ? array_splice($pool, $differentDayIndex, 1)[0]
                    : array_shift($pool);
            } else {
                $second = array_shift($pool);
            }

            foreach ([$first, $second] as $cell) {
                TimetableSlot::create([
                    'semester_id' => $semester->id,
                    'course_id' => $course->id,
                    'day_of_week' => $cell['day'],
                    'start_time' => $cell['start'],
                    'end_time' => $cell['end'],
                ]);
            }
        }

        return redirect()->route('timetable-slots.index', $redirectParams)
            ->with('success', 'Weekly timetable randomly generated for '.$courses->count().' module(s) — two sessions each, Monday to Friday.');
    }

    /** JSON list of active modules under a semester (optionally narrowed by NTA level), for the semester-driven course pickers. */
    public function coursesForSemester(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'nta_level' => ['nullable', 'integer', 'min:4', 'max:6'],
            'programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
        ]);

        $hodProgrammeId = auth()->user()->hodProgrammeId();

        $courses = Course::with('programme:id,code')
            ->where('is_active', true)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $validated['semester_id']))
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->when($validated['programme_id'] ?? null, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->when($validated['nta_level'] ?? null, fn ($q, $level) => $q->where('nta_level', $level))
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'programme_id'])
            ->map(fn (Course $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'programme_code' => $c->programme->code ?? null,
            ]);

        return response()->json($courses);
    }

    public function create(Request $request)
    {
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->get();
        $programmes = Programme::where('is_active', true)->orderBy('code')->get(['id', 'code']);

        return view('timetable-slots.create', compact('semesters', 'programmes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_ids' => ['required', 'array', 'min:1'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'lecturer' => ['nullable', 'string', 'max:150'],
            'room' => ['nullable', 'string', 'max:100'],
            'venue' => ['nullable', 'string', 'max:150'],
        ]);

        $created = 0;
        foreach ($validated['course_ids'] as $courseId) {
            foreach ($validated['days'] as $day) {
                TimetableSlot::create([
                    'semester_id' => $validated['semester_id'],
                    'course_id' => $courseId,
                    'lecturer' => $validated['lecturer'] ?? null,
                    'day_of_week' => $day,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'room' => $validated['room'] ?? null,
                    'venue' => $validated['venue'] ?? null,
                ]);
                $created++;
            }
        }

        return redirect()->route('timetable-slots.index')->with('success', $created.' timetable slot(s) added.');
    }

    public function destroy(Request $request, TimetableSlot $timetable_slot)
    {
        $timetable_slot->delete();

        return redirect()
            ->route('timetable-slots.index', array_filter(['semester_id' => $request->input('semester_id', $request->query('semester_id'))]))
            ->with('success', 'Timetable slot removed.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            TimetableSlot::class,
            'timetable-slots.index',
            fn (Request $r) => array_filter(['semester_id' => $r->input('semester_id', $r->query('semester_id'))]),
            singularLabel: 'timetable slot',
        );
    }
}
