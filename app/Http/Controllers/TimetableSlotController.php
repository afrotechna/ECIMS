<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Semester;
use App\Models\Student;
use App\Models\TimetableSlot;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use ZipArchive as PhpZipArchive;

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

            $grid = TimetableSlot::buildWeekGrid($comboSlots);

            $panels[] = [
                'programme' => $programmesById->get($combo->programme_id),
                'level' => $level,
                'level_label' => Student::NTA_LEVELS[$level] ?? "NTA Level {$level}",
                'grid' => $grid,
                // Word/Print links need a concrete semester even when the page-level filter is
                // left on "All" — take it from the panel's own slots so the buttons never
                // silently disappear just because the admin didn't pick a semester up top.
                'semester_id' => optional($comboSlots->first())->semester_id ?: $semesterId,
            ];
        }

        return view('timetable-slots.index', compact('slots', 'semesters', 'semesterId', 'programmes', 'programmeId', 'panels', 'selectedSemester'));
    }

    /**
     * Resolve and authorize the (semester, programme, level) triple named by the request's
     * semester_id/programme_id/nta_level params, and build that combo's weekly grid. Shared by
     * the Word export and the print view so both always show the exact same data.
     *
     * @return array{semester: Semester, programme: Programme, level: int, grid: array}
     */
    private function resolvePanelContext(Request $request): array
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'nta_level' => ['required', 'integer', 'min:4', 'max:6'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $programme = Programme::findOrFail($validated['programme_id']);
        $level = (int) $validated['nta_level'];

        $hodProgrammeId = auth()->user()->hodProgrammeId();
        abort_if($hodProgrammeId && (int) $hodProgrammeId !== $programme->id, 403);

        $slots = TimetableSlot::with('course')
            ->where('semester_id', $semester->id)
            ->whereHas('course', fn ($q) => $q->where('programme_id', $programme->id)->where('nta_level', $level))
            ->get();

        $grid = TimetableSlot::buildWeekGrid($slots);

        return compact('semester', 'programme', 'level', 'grid');
    }

    /** Printable, letterhead-styled single-page view of one department/level's weekly grid (Print / Save-as-PDF). */
    public function print(Request $request)
    {
        ['semester' => $semester, 'programme' => $programme, 'level' => $level, 'grid' => $grid] = $this->resolvePanelContext($request);

        $dayLabels = collect(TimetableSlot::WEEK_DAYS)->mapWithKeys(fn ($d) => [$d => TimetableSlot::DAYS[$d]]);

        return view('timetable-slots.print', compact('semester', 'programme', 'level', 'grid', 'dayLabels'));
    }

    /**
     * Same printable letterhead view as print(), but for a student viewing their own timetable —
     * scope is derived entirely from the logged-in student's own programme/level, never from
     * request input, so a student can't print another department's schedule.
     */
    public function printMine(Request $request)
    {
        $student = auth()->user()->student;
        abort_unless($student, 403);
        $student->loadMissing('programme');
        abort_unless($student->programme_id, 404, 'No programme is set on your student record yet.');

        $level = (int) ($student->nta_level ?? 0);
        if ($level < 4 || $level > 6) {
            $level = (int) (Course::where('programme_id', $student->programme_id)
                ->where('is_active', true)
                ->whereNotNull('nta_level')
                ->value('nta_level') ?? 4);
        }

        $academicYearStart = \App\Support\AcademicSession::resolveStartYear(
            $request->filled('academic_year') ? $request->integer('academic_year') : null
        );
        $termNumber = (int) $request->get('term', Semester::PERIOD_FIRST);

        $semester = Semester::where('academic_year', $academicYearStart)->where('number', $termNumber)->first();
        abort_unless($semester, 404, 'No timetable found for that term yet.');

        $programme = $student->programme;

        $slots = TimetableSlot::with('course')
            ->where('semester_id', $semester->id)
            ->whereHas('course', fn ($q) => $q->where('programme_id', $programme->id)->where('nta_level', $level))
            ->get();

        $grid = TimetableSlot::buildWeekGrid($slots);
        $dayLabels = collect(TimetableSlot::WEEK_DAYS)->mapWithKeys(fn ($d) => [$d => TimetableSlot::DAYS[$d]]);
        $backUrl = route('my.timetable', ['academic_year' => $academicYearStart]);

        return view('timetable-slots.print', compact('semester', 'programme', 'level', 'grid', 'dayLabels', 'backUrl'));
    }

    /** Download one department/level's weekly grid as a Word document, matching the college's printed letterhead. */
    public function downloadWord(Request $request)
    {
        ['semester' => $semester, 'programme' => $programme, 'level' => $level, 'grid' => $grid] = $this->resolvePanelContext($request);

        if (! extension_loaded('zip') || ! class_exists(PhpZipArchive::class)) {
            Settings::setZipClass(Settings::PCLZIP);
        }

        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);
        $phpWord->addFontStyle('tt_heading', ['name' => 'Times New Roman', 'size' => 12, 'bold' => true]);
        $phpWord->addFontStyle('tt_sub', ['name' => 'Times New Roman', 'size' => 11]);
        $phpWord->addFontStyle('tt_cell_bold', ['name' => 'Times New Roman', 'size' => 10, 'bold' => true]);
        $phpWord->addFontStyle('tt_cell', ['name' => 'Times New Roman', 'size' => 10]);
        $phpWord->addParagraphStyle('tt_center', ['alignment' => 'center', 'spaceAfter' => 40, 'lineHeight' => 1.0]);

        $section = $phpWord->addSection([
            'marginTop' => 850,
            'marginBottom' => 850,
            'marginLeft' => 850,
            'marginRight' => 850,
            'orientation' => 'landscape',
        ]);

        $section->addText('MINISTRY OF HEALTH', 'tt_heading', 'tt_center');
        $section->addText('MUSOMA CLINICAL OFFICER TRAINING CENTRE', 'tt_heading', 'tt_center');
        $section->addText('DEPARTMENT OF '.strtoupper($programme->name), 'tt_heading', 'tt_center');
        $section->addText('ACADEMIC YEAR: '.$semester->academicYearRange(), 'tt_heading', 'tt_center');
        $section->addText(strtoupper($semester->periodName()), 'tt_heading', 'tt_center');
        $section->addText('NTA LEVEL '.$level, 'tt_heading', 'tt_center');
        if ($semester->start_date && $semester->end_date) {
            $section->addText(
                'FROM '.strtoupper($semester->start_date->format('jS F Y')).' – '.strtoupper($semester->end_date->format('jS F Y')),
                'tt_sub',
                'tt_center'
            );
        }
        $section->addTextBreak();

        $dayLabels = collect(TimetableSlot::WEEK_DAYS)->mapWithKeys(fn ($d) => [$d => TimetableSlot::DAYS[$d]]);
        $timeColWidth = 1300;
        $dayColWidth = 1750;

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60]);
        $table->addRow();
        $table->addCell($timeColWidth)->addText('TIME', 'tt_cell_bold', ['alignment' => 'center']);
        foreach ($dayLabels as $label) {
            $table->addCell($dayColWidth)->addText(strtoupper($label), 'tt_cell_bold', ['alignment' => 'center']);
        }

        foreach (TimetableSlot::DAILY_SESSIONS as $sessionIndex => $session) {
            $table->addRow();
            $table->addCell($timeColWidth)->addText($session['label'], 'tt_cell_bold', ['alignment' => 'center']);
            foreach ($dayLabels as $day => $label) {
                $cellSlot = $grid[$day][$session['start']] ?? null;
                $cell = $table->addCell($dayColWidth);
                if ($cellSlot) {
                    if ($cellSlot->course?->code) {
                        $cell->addText(strtoupper($cellSlot->course->code), 'tt_cell_bold', ['alignment' => 'center']);
                    }
                    $cell->addText(strtoupper($cellSlot->course->name ?? ''), 'tt_cell', ['alignment' => 'center']);
                    $cell->addText($cellSlot->lecturer ? 'Tutor: '.$cellSlot->lecturer : ' ', 'tt_cell_bold', ['alignment' => 'center']);
                } else {
                    $cell->addText('—', 'tt_cell', ['alignment' => 'center']);
                }
            }

            if ($break = TimetableSlot::BREAKS[$sessionIndex] ?? null) {
                $table->addRow();
                $table->addCell($timeColWidth)->addText($break['start'].' – '.$break['end'], 'tt_cell_bold', ['alignment' => 'center']);
                $table->addCell($dayColWidth * count($dayLabels), ['gridSpan' => count($dayLabels)])
                    ->addText($break['label'], 'tt_cell_bold', ['alignment' => 'center']);
            }
        }

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        $tempFile = $tempDir.DIRECTORY_SEPARATOR.uniqid('timetable_docx_', true).'.docx';
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $fileName = strtoupper($programme->code).'-level-'.$level.'-timetable.docx';

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
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
        $totalCells = count($cells);

        if ($courses->count() > $totalCells) {
            return redirect()->route('timetable-slots.index', $redirectParams)
                ->with('error', "Auto-generate fits up to {$totalCells} modules a week (one session minimum each). This selection has {$courses->count()} modules — uncheck some or add extra slots manually.");
        }

        TimetableSlot::where('semester_id', $semester->id)
            ->whereIn('course_id', $courses->pluck('id'))
            ->delete();

        $this->generateFullWeek($semester, $courses, $cells);

        return redirect()->route('timetable-slots.index', $redirectParams)
            ->with('success', 'Weekly timetable randomly generated for '.$courses->count().' module(s), filling all '.$totalCells.' weekly sessions (heavier-credit modules get more sessions) — no empty slots left.');
    }

    /**
     * Fill every weekly cell — no empty "—" left — by giving each module a share of the
     * week proportional to its credit load (heavier modules meet more often), then placing
     * one session per day per module where possible so the same module doesn't repeat on
     * the same day.
     *
     * @param  \Illuminate\Support\Collection<int, Course>  $courses
     * @param  list<array{day: int, start: string, end: string}>  $cells
     */
    private function generateFullWeek(Semester $semester, $courses, array $cells): void
    {
        $totalCells = count($cells);

        $weights = [];
        foreach ($courses as $course) {
            $weights[$course->id] = max((float) ($course->credits ?? 0), 1.0);
        }
        $totalWeight = array_sum($weights);

        $sessionsPerCourse = [];
        $allocated = 0;
        foreach ($weights as $courseId => $weight) {
            $n = max(1, (int) round($weight / $totalWeight * $totalCells));
            $sessionsPerCourse[$courseId] = $n;
            $allocated += $n;
        }

        // Rounding can over/under-shoot the total — nudge counts back to exactly $totalCells.
        $courseIds = array_keys($sessionsPerCourse);
        $diff = $totalCells - $allocated;
        for ($i = 0; $diff !== 0 && $i < $totalCells * 4; $i++) {
            $id = $courseIds[$i % count($courseIds)];
            if ($diff > 0) {
                $sessionsPerCourse[$id]++;
                $diff--;
            } elseif ($sessionsPerCourse[$id] > 1) {
                $sessionsPerCourse[$id]--;
                $diff++;
            }
        }

        $bag = [];
        foreach ($sessionsPerCourse as $courseId => $n) {
            for ($i = 0; $i < $n; $i++) {
                $bag[] = $courseId;
            }
        }
        shuffle($bag);

        $days = TimetableSlot::WEEK_DAYS;
        shuffle($days);

        foreach ($days as $day) {
            $usedToday = [];
            foreach (TimetableSlot::DAILY_SESSIONS as $session) {
                if ($bag === []) {
                    break;
                }
                $pickIndex = 0;
                foreach ($bag as $i => $candidateId) {
                    if (! in_array($candidateId, $usedToday, true)) {
                        $pickIndex = $i;
                        break;
                    }
                }
                $courseId = array_splice($bag, $pickIndex, 1)[0];
                $usedToday[] = $courseId;

                TimetableSlot::create([
                    'semester_id' => $semester->id,
                    'course_id' => $courseId,
                    'day_of_week' => $day,
                    'start_time' => $session['start'],
                    'end_time' => $session['end'],
                ]);
            }
        }
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
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
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
