<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use App\Models\Semester;
use App\Services\HolidayCalendarService;
use App\Services\HolidayCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(HolidayCatalog $catalog)
    {
        $academicYear = (int) request('academic_year', $this->defaultAcademicYearStart());
        $academicYearEnd = $academicYear + 1;
        $academicYearLabel = "{$academicYear}/{$academicYearEnd}";

        $rangeStart = "{$academicYear}-07-01";
        $rangeEnd = "{$academicYearEnd}-06-30";
        $today = now()->toDateString();
        $calendarInitialDate = ($today >= $rangeStart && $today <= $rangeEnd)
            ? $today
            : $rangeStart;

        $academicYearOptions = Semester::academicYearOptionsForForms();
        if (! isset($academicYearOptions[$academicYear])) {
            $academicYearOptions[$academicYear] = $academicYearLabel;
            ksort($academicYearOptions);
        }

        $semesters = Semester::query()
            ->where('academic_year', $academicYear)
            ->orderBy('number')
            ->get();

        $holidayCatalog = $catalog->forAcademicYear($academicYear);
        foreach ($holidayCatalog as &$row) {
            $calendarYear = (int) substr($row['starts_on'], 0, 4);
            $row['calendar_year'] = $calendarYear;
            $row['activated'] = $catalog->isActivated($row['key'], $calendarYear);
        }
        unset($row);

        $customEvents = CalendarEvent::query()
            ->with('creator')
            ->whereNull('catalog_key')
            ->where('starts_on', '>=', $rangeStart)
            ->where('starts_on', '<=', $rangeEnd)
            ->orderBy('starts_on')
            ->limit(40)
            ->get();

        $canManage = auth()->user() && ! auth()->user()->isStudent()
            && auth()->user()->canModule('calendar', 'create');

        return view('calendar.index', compact(
            'semesters',
            'customEvents',
            'canManage',
            'holidayCatalog',
            'academicYear',
            'academicYearLabel',
            'academicYearOptions',
            'calendarInitialDate',
            'rangeStart',
            'rangeEnd'
        ));
    }

    /** Academic year start (e.g. 2025 for session 2025/2026). */
    private function defaultAcademicYearStart(): int
    {
        return \App\Support\AcademicSession::defaultStartYear();
    }

    public function events(Request $request, HolidayCalendarService $holidays): JsonResponse
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        return response()->json(
            $holidays->eventsBetween($request->input('start'), $request->input('end'))
        )->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Enable a predefined holiday (from catalog) for everyone — no duplicate manual entry.
     */
    public function activateCatalog(Request $request, HolidayCatalog $catalog)
    {
        if (! auth()->user()->canModule('calendar', 'create')) {
            abort(403);
        }

        $validated = $request->validate([
            'catalog_key' => ['required', 'string', 'max:80'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $item = $catalog->find($validated['catalog_key'], (int) $validated['year']);
        if (! $item) {
            return response()->json(['ok' => false, 'message' => 'Holiday not found in catalog.'], 422);
        }

        $event = CalendarEvent::updateOrCreate(
            [
                'catalog_key' => $item['key'],
                'starts_on' => $item['starts_on'],
            ],
            [
                'title' => $item['title'],
                'description' => $item['description'],
                'ends_on' => $item['ends_on'],
                'type' => $item['type'],
                'all_day' => true,
                'color' => CalendarEvent::colorForType($item['type']),
                'created_by' => auth()->id(),
            ]
        );

        $message = '“'.$item['title'].'” is on the calendar for everyone with an explanation.';

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'id' => $event->id]);
        }

        $academicStart = \App\Support\AcademicSession::currentStartYear(
            \Carbon\Carbon::parse($item['starts_on'])
        );

        return redirect()->route('calendar.index', ['academic_year' => $academicStart])->with('success', $message);
    }

    public function storeEvent(Request $request)
    {
        if (! auth()->user()->canModule('calendar', 'create')) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'type' => ['required', 'string', 'in:college,exam,registration,other'],
            'all_day' => ['nullable', 'boolean'],
        ]);

        $event = CalendarEvent::create([
            ...$validated,
            'all_day' => $request->boolean('all_day', true),
            'color' => CalendarEvent::colorForType($validated['type']),
            'created_by' => auth()->id(),
        ]);

        $message = 'College event added.';

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'id' => $event->id]);
        }

        return redirect()->route('calendar.index')->with('success', $message);
    }

    public function destroyEvent(Request $request, CalendarEvent $calendar_event)
    {
        if (! auth()->user()->canModule('calendar', 'delete')) {
            abort(403);
        }

        $calendar_event->delete();

        if ($request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('calendar.index')->with('success', 'Removed from calendar.');
    }
}
