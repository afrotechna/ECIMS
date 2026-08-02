<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Course;
use App\Models\Semester;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;

class TimetableSlotController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $semesterId = $request->get('semester_id');
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $query = TimetableSlot::with(['semester', 'course'])
            ->when(auth()->user()->hodProgrammeId(), fn ($q, $pid) => $q->whereHas('course', fn ($cq) => $cq->where('programme_id', $pid)));
        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        $slots = $query->orderBy('day_of_week')->orderBy('start_time')->get();
        return view('timetable-slots.index', compact('slots', 'semesters', 'semesterId'));
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
