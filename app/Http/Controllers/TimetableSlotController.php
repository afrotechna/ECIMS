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
            'programme_id' => ['required', 'exists:programmes,id'],
        ]);

        if (auth()->user()->hodProgrammeId() && (int) $validated['programme_id'] !== auth()->user()->hodProgrammeId()) {
            abort(403);
        }

        $semester = Semester::findOrFail($validated['semester_id']);
        $courses = Course::where('programme_id', $validated['programme_id'])
            ->where('is_active', true)
            ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $semester->id))
            ->orderBy('code')
            ->get();

        $redirectParams = ['semester_id' => $semester->id, 'programme_id' => $validated['programme_id']];

        if ($courses->isEmpty()) {
            return redirect()->route('timetable-slots.index', $redirectParams)
                ->with('error', 'No modules found for that programme in this semester — nothing to schedule.');
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
                ->with('error', "Auto-generate fits up to {$maxModules} modules a week (2 sessions each across ".count($cells)." weekly slots). This selection has {$courses->count()} modules — trim the list or add extra slots manually.");
        }

        TimetableSlot::where('semester_id', $semester->id)
            ->whereIn('course_id', $courses->pluck('id'))
            ->delete();

        $secondOccurrenceOffset = intdiv(count($cells), 2);
        foreach ($courses->values() as $i => $course) {
            foreach ([$i, $i + $secondOccurrenceOffset] as $cellIndex) {
                $cell = $cells[$cellIndex];
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
            ->with('success', 'Weekly timetable generated for '.$courses->count().' module(s) — two sessions each, Monday to Friday.');
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
