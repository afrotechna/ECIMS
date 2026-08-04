<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Semester;
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
        $slots = $query->orderBy('day_of_week')->orderBy('start_time')->get();

        // Build the standard Mon–Fri / 3-session grid; anything with non-standard times falls
        // into $otherSlots below the grid instead of being silently dropped.
        $grid = [];
        $gridSlotIds = [];
        foreach (TimetableSlot::WEEK_DAYS as $day) {
            foreach (TimetableSlot::DAILY_SESSIONS as $session) {
                $match = $slots->first(fn ($s) => (int) $s->day_of_week === $day
                    && substr((string) $s->start_time, 0, 5) === $session['start']
                    && substr((string) $s->end_time, 0, 5) === $session['end']);
                $grid[$day][$session['start']] = $match;
                if ($match) {
                    $gridSlotIds[] = $match->id;
                }
            }
        }
        $otherSlots = $slots->reject(fn ($s) => in_array($s->id, $gridSlotIds, true))->values();

        return view('timetable-slots.index', compact('slots', 'semesters', 'semesterId', 'programmes', 'programmeId', 'grid', 'otherSlots'));
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
        ]);

        $hodProgrammeId = auth()->user()->hodProgrammeId();

        $courses = Course::where('is_active', true)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $validated['semester_id']))
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->when($validated['nta_level'] ?? null, fn ($q, $level) => $q->where('nta_level', $level))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json($courses);
    }

    public function create(Request $request)
    {
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->get();
        $courses = Course::where('is_active', true)->orderBy('code')->get();
        return view('timetable-slots.create', compact('semesters', 'courses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'day_of_week' => ['required', 'integer', 'min:1', 'max:7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:100'],
            'venue' => ['nullable', 'string', 'max:150'],
        ]);
        TimetableSlot::create($validated);
        return redirect()->route('timetable-slots.index')->with('success', 'Timetable slot added.');
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
