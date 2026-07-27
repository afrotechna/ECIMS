<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Course;
use App\Models\ExamSlot;
use App\Models\Programme;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Element\Table as WordTableElement;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Style\Cell as WordCellStyle;
use ZipArchive as PhpZipArchive;

class ExamSlotController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $semesterId = $request->filled('semester_id') ? $request->integer('semester_id') : null;
        $programmeId = $request->filled('programme_id') ? $request->integer('programme_id') : null;
        $assessmentType = $request->get('assessment_type');
        if (! in_array($assessmentType, ['cat1', 'cat2', ''], true)) {
            $assessmentType = '';
        }

        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();

        $query = ExamSlot::with(['semester', 'course.programme']);

        if ($semesterId) {
            $query->where('semester_id', $semesterId);
        }
        if ($programmeId) {
            $query->whereHas('course', fn (Builder $c) => $c->where('programme_id', $programmeId));
        }
        if ($assessmentType !== '') {
            $query->where('assessment_type', $assessmentType);
        }

        $selectedSemester = $semesterId ? Semester::find($semesterId) : null;
        $selectedProgramme = $programmeId ? Programme::find($programmeId) : null;

        if ($semesterId && $programmeId) {
            $allSlots = (clone $query)->get();
            $allSlots = $this->sortSlotsForTimetable($allSlots);
            $timetableSections = $this->buildTimetableSections($allSlots);
            $timetableMonthYear = $this->timetableMonthYearLabel($allSlots, $selectedSemester);
            $timetableFooterMonthYear = $this->timetableFooterMonthYear($allSlots, $selectedSemester);
            $slots = new LengthAwarePaginator($allSlots, $allSlots->count(), max(1, $allSlots->count()), 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } elseif ($semesterId) {
            $allSlots = $this->sortSlotsForTimetable((clone $query)->get());
            $timetableSections = [];
            $timetableMonthYear = $this->timetableMonthYearLabel($allSlots, $selectedSemester);
            $timetableFooterMonthYear = $this->timetableFooterMonthYear($allSlots, $selectedSemester);
            $slots = new LengthAwarePaginator($allSlots, $allSlots->count(), max(1, $allSlots->count()), 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        } else {
            $allSlots = collect();
            $timetableSections = [];
            $timetableMonthYear = '';
            $timetableFooterMonthYear = '';
            $slots = $query->orderBy('exam_date')->orderBy('start_time')->paginate(30)->withQueryString();
        }

        $mainTitleLine = $this->mainTitleLineForFilters($assessmentType);

        return view('exam-slots.index', compact(
            'slots',
            'semesters',
            'programmes',
            'semesterId',
            'programmeId',
            'assessmentType',
            'selectedSemester',
            'selectedProgramme',
            'allSlots',
            'timetableSections',
            'timetableMonthYear',
            'timetableFooterMonthYear',
            'mainTitleLine'
        ));
    }

    /**
     * Modules linked to the chosen semester (catalogue), optionally scoped to a programme.
     */
    public function coursesForSemester(Request $request)
    {
        $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['nullable', 'exists:programmes,id'],
        ]);

        $courses = $this->sortCoursesForPicker(
            $this->coursesQueryForSemester(
                $request->integer('semester_id'),
                $request->filled('programme_id') ? $request->integer('programme_id') : null
            )->get()
        );

        return response()->json([
            'courses' => $courses->map(fn (Course $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'programme_id' => $c->programme_id,
                'nta_level' => $c->resolvedNtaLevel(),
            ]),
        ]);
    }

    public function exportDocx(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'assessment_type' => ['required', 'in:cat1,cat2'],
        ]);

        $semester = Semester::findOrFail($validated['semester_id']);
        $programme = Programme::findOrFail($validated['programme_id']);

        $slots = $this->sortSlotsForTimetable(
            ExamSlot::with(['course.programme'])
                ->where('semester_id', $semester->id)
                ->where('assessment_type', $validated['assessment_type'])
                ->whereHas('course', fn (Builder $c) => $c->where('programme_id', $programme->id))
                ->get()
        );

        if ($slots->isEmpty()) {
            return redirect()->route('exam-slots.index', [
                'semester_id' => $semester->id,
                'programme_id' => $programme->id,
                'assessment_type' => $validated['assessment_type'],
            ])->with('error', 'No exam slots to export for this programme and assessment type.');
        }

        if (extension_loaded('zip') && class_exists(PhpZipArchive::class)) {
            Settings::setZipClass(Settings::ZIPARCHIVE);
        } else {
            Settings::setZipClass(Settings::PCLZIP);
        }

        $timetableSections = $this->buildTimetableSections($slots);
        $monthYearHeader = $this->sanitizeWordPlainText($this->timetableMonthYearLabel($slots, $semester));
        $monthYearFooter = $this->sanitizeWordPlainText($this->timetableFooterMonthYear($slots, $semester));
        $footerCaption = $this->sanitizeWordPlainText(
            $this->footerCaptionLine($validated['assessment_type'], $monthYearFooter)
        );

        $prevOutputEscaping = Settings::isOutputEscapingEnabled();
        Settings::setOutputEscapingEnabled(true);

        try {
            $phpWord = new PhpWord;
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);

            $section = $phpWord->addSection([
                'marginTop' => 1134,
                'marginBottom' => 1134,
                'marginLeft' => 1134,
                'marginRight' => 1134,
            ]);

            $programmeTitleLine = $this->sanitizeWordPlainText(
                strtoupper($programme->name).' (NTA Level 4, 5 & 6)'
            );
            $mainExamTitle = $this->sanitizeWordPlainText(strtoupper($this->examTitleForAssessment($validated['assessment_type'])));

            foreach ([
                'The United Republic of Tanzania',
                'Ministry of Health',
                'Musoma Clinical Officer Training Center',
                $programmeTitleLine,
                $mainExamTitle,
                $monthYearHeader,
            ] as $line) {
                $section->addText($line, ['bold' => true, 'size' => 12], ['alignment' => 'center', 'spaceAfter' => 120]);
            }

            $section->addTextBreak(1);

            foreach ($timetableSections as $sectionBlock) {
                if (! empty($sectionBlock['section_label'])) {
                    $section->addText(
                        $this->sanitizeWordPlainText(strtoupper($sectionBlock['section_label'])),
                        ['bold' => true, 'size' => 12],
                        ['spaceBefore' => 200, 'spaceAfter' => 120]
                    );
                }
                $table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 100]);
                $this->writeExamTimetableWordTableBody($table, $sectionBlock['days']);
                $section->addTextBreak(1);
            }

            $footer = $section->addFooter();
            $footerTable = $footer->addTable([
                'borderTopSize' => 12,
                'borderTopColor' => '000000',
            ]);
            $footerTable->addRow();
            $footerTable->addCell(9200)->addText($footerCaption, ['size' => 10], ['alignment' => 'center', 'spaceBefore' => 120]);

            $tempDir = storage_path('app/temp');
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFile = $tempDir.DIRECTORY_SEPARATOR.uniqid('exam_timetable_', true).'.docx';

            try {
                IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);
            } catch (\Throwable $e) {
                Log::error('Exam timetable DOCX export failed', ['exception' => $e]);

                abort(500, 'Could not generate the Word file. Ensure PHP has the zip extension enabled (extension=zip in php.ini), then restart the web server.');
            }
        } finally {
            Settings::setOutputEscapingEnabled($prevOutputEscaping);
        }

        if (! is_file($tempFile) || filesize($tempFile) < 100) {
            abort(500, 'Could not generate timetable document.');
        }

        $assessSlug = $validated['assessment_type'] === ExamSlot::ASSESSMENT_CAT2 ? 'cat-ii' : 'cat-i';
        $fileName = Str::slug(
            'exam-timetable-'.$programme->code.'-'.$assessSlug.'-'.$semester->name.'-'.$semester->academic_year,
            '-'
        ).'.docx';

        return response()->download($tempFile, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    public function create(Request $request)
    {
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->get();
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $semesterId = $request->filled('semester_id') ? $request->integer('semester_id') : null;
        $programmeId = $request->filled('programme_id') ? $request->integer('programme_id') : null;
        $courses = $semesterId
            ? $this->sortCoursesForPicker($this->coursesQueryForSemester($semesterId, $programmeId)->get())
            : collect();

        return view('exam-slots.create', compact('semesters', 'programmes', 'courses', 'semesterId', 'programmeId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_ids' => ['required', 'array', 'min:1', 'max:50'],
            'course_ids.*' => ['integer', 'exists:courses,id'],
            'assessment_type' => ['required', 'in:cat1,cat2'],
            'exam_format' => ['required', 'in:theory,practical,osce,ospe'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $courseIds = array_values(array_unique(array_map('intval', $validated['course_ids'])));
        if (count($courseIds) !== count($validated['course_ids'])) {
            throw ValidationException::withMessages([
                'course_ids' => 'Select each module once only.',
            ]);
        }

        $semesterId = (int) $validated['semester_id'];
        foreach ($courseIds as $courseId) {
            $this->assertCourseOfferedInSemester($courseId, $semesterId, 'course_ids');
        }

        $count = 0;
        DB::transaction(function () use ($validated, $courseIds, &$count) {
            foreach ($courseIds as $courseId) {
                ExamSlot::create([
                    'semester_id' => $validated['semester_id'],
                    'course_id' => $courseId,
                    'assessment_type' => $validated['assessment_type'],
                    'exam_format' => ExamSlot::normalizeFormat($validated['exam_format']),
                    'exam_date' => $validated['exam_date'],
                    'start_time' => $validated['start_time'] ?? null,
                    'end_time' => $validated['end_time'] ?? null,
                    'room' => $validated['room'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
                $count++;
            }
        });

        $msg = $count === 1
            ? 'Exam slot added.'
            : "Added {$count} exam slots for the same date and time.";

        return redirect()->route('exam-slots.index')->with('success', $msg);
    }

    public function edit(ExamSlot $exam_slot)
    {
        $exam_slot->load(['semester', 'course.programme']);
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->get();
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $courses = $this->coursesQueryForSemester($exam_slot->semester_id, $exam_slot->course?->programme_id)
            ->orderBy('code')
            ->get();
        if ($courses->where('id', $exam_slot->course_id)->isEmpty() && $exam_slot->course) {
            $courses = $courses->push($exam_slot->course)->sortBy('code')->values();
        }

        return view('exam-slots.edit', compact('exam_slot', 'semesters', 'programmes', 'courses'));
    }

    public function update(Request $request, ExamSlot $exam_slot)
    {
        $validated = $request->validate([
            'semester_id' => ['required', 'exists:semesters,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'assessment_type' => ['required', 'in:cat1,cat2'],
            'exam_format' => ['required', 'in:theory,practical,osce,ospe'],
            'exam_date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'room' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $this->assertCourseOfferedInSemester((int) $validated['course_id'], (int) $validated['semester_id'], 'course_id');

        $validated['exam_format'] = ExamSlot::normalizeFormat($validated['exam_format']);
        $exam_slot->update($validated);

        return redirect()->route('exam-slots.index')->with('success', 'Exam slot updated.');
    }

    public function destroy(Request $request, ExamSlot $exam_slot)
    {
        $exam_slot->delete();

        return redirect()
            ->route('exam-slots.index', $this->examSlotIndexFilters($request))
            ->with('success', 'Exam slot removed.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            ExamSlot::class,
            'exam-slots.index',
            fn (Request $r) => $this->examSlotIndexFilters($r),
            singularLabel: 'exam slot',
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function examSlotIndexFilters(Request $request): array
    {
        return array_filter([
            'semester_id' => $request->input('semester_id', $request->query('semester_id')),
            'programme_id' => $request->input('programme_id', $request->query('programme_id')),
            'assessment_type' => $request->input('assessment_type', $request->query('assessment_type')),
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function assertCourseOfferedInSemester(int $courseId, int $semesterId, string $errorKey = 'course_id'): void
    {
        $ok = Course::whereKey($courseId)
            ->whereHas('semesters', fn (Builder $s) => $s->where('semesters.id', $semesterId))
            ->exists();
        if (! $ok) {
            $code = Course::whereKey($courseId)->value('code') ?? (string) $courseId;
            throw ValidationException::withMessages([
                $errorKey => "Module {$code} is not linked to the selected semester in the module catalogue.",
            ]);
        }
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return Collection<int, Course>
     */
    private function sortCoursesForPicker(Collection $courses): Collection
    {
        return $courses->sort(function (Course $a, Course $b) {
            $la = $a->resolvedNtaLevel();
            $lb = $b->resolvedNtaLevel();
            if ($la !== $lb) {
                return $la <=> $lb;
            }

            return strcmp(strtoupper($a->code), strtoupper($b->code));
        })->values();
    }

    /**
     * @return Builder<Course>
     */
    private function coursesQueryForSemester(?int $semesterId, ?int $programmeId): Builder
    {
        $q = Course::query()->where('is_active', true)->with('programme');
        if ($semesterId) {
            $q->whereHas('semesters', fn (Builder $s) => $s->where('semesters.id', $semesterId));
        }
        if ($programmeId) {
            $q->where('programme_id', $programmeId);
        }

        return $q;
    }

    private function mainTitleLineForFilters(string $assessmentType): string
    {
        if ($assessmentType === ExamSlot::ASSESSMENT_CAT2) {
            return 'Continuous Assessment II Examinations Timetable';
        }
        if ($assessmentType === ExamSlot::ASSESSMENT_CAT1) {
            return 'Continuous Assessment I Examinations Timetable';
        }

        return 'Continuous Assessment Examinations Timetable';
    }

    private function examTitleForAssessment(string $type): string
    {
        return match ($type) {
            ExamSlot::ASSESSMENT_CAT2 => 'Continuous Assessment II Examinations Timetable',
            default => 'Continuous Assessment I Examinations Timetable',
        };
    }

    private function footerCaptionLine(string $assessmentType, string $monthYearPlain): string
    {
        $base = match ($assessmentType) {
            ExamSlot::ASSESSMENT_CAT2 => 'Continuous Assessment II Time Table',
            default => 'Continuous Assessment I Time Table',
        };

        return $base.' '.$monthYearPlain;
    }

    /**
     * Theory examinations first, then practical / OSCE / OSPE.
     *
     * @return Collection<int, ExamSlot>
     */
    private function sortSlotsForTimetable(Collection $slots): Collection
    {
        return $slots->sort(function (ExamSlot $a, ExamSlot $b) {
            $fa = ExamSlot::formatSortOrder($a->exam_format);
            $fb = ExamSlot::formatSortOrder($b->exam_format);
            if ($fa !== $fb) {
                return $fa <=> $fb;
            }
            $da = $a->exam_date->format('Y-m-d');
            $db = $b->exam_date->format('Y-m-d');
            if ($da !== $db) {
                return $da <=> $db;
            }

            return strcmp((string) ($a->start_time ?? ''), (string) ($b->start_time ?? ''));
        })->values();
    }

    /**
     * @return list<array{section_label: string, days: list<array{day_upper: string, date_display: string, row_count: int, sessions: list<array{time_label: string, slots: Collection<int, ExamSlot>}>}>}>
     */
    private function buildTimetableSections(Collection $slots): array
    {
        if ($slots->isEmpty()) {
            return [];
        }

        $theory = $slots->filter(fn (ExamSlot $s) => ExamSlot::normalizeFormat($s->exam_format) === ExamSlot::FORMAT_THEORY);
        $clinical = $slots->filter(fn (ExamSlot $s) => ExamSlot::normalizeFormat($s->exam_format) !== ExamSlot::FORMAT_THEORY);

        $sections = [];
        if ($theory->isNotEmpty()) {
            $sections[] = [
                'section_label' => 'Theory examinations',
                'days' => $this->buildTimetableDays($theory),
            ];
        }
        if ($clinical->isNotEmpty()) {
            $sections[] = [
                'section_label' => 'Practical, OSCE & OSPE examinations',
                'days' => $this->buildTimetableDays($clinical),
            ];
        }

        return $sections;
    }

    /**
     * @return list<array{day_upper: string, date_display: string, row_count: int, sessions: list<array{time_label: string, slots: Collection<int, ExamSlot>}>}>
     */
    private function buildTimetableDays(Collection $slots): array
    {
        if ($slots->isEmpty()) {
            return [];
        }

        $byDay = $slots->groupBy(fn (ExamSlot $s) => $s->exam_date->format('Y-m-d'))->sortKeys();
        $out = [];

        foreach ($byDay as $dateStr => $daySlots) {
            /** @var Collection<int, ExamSlot> $daySlots */
            $date = Carbon::parse($dateStr);
            $grouped = $daySlots->groupBy(function (ExamSlot $s) {
                $a = $s->start_time ?? '';
                $b = $s->end_time ?? '';

                return $a.'|'.$b;
            });

            $sessions = [];
            foreach ($grouped as $group) {
                /** @var Collection<int, ExamSlot> $group */
                $sorted = $group->sort(function (ExamSlot $a, ExamSlot $b) {
                    $fa = ExamSlot::formatSortOrder($a->exam_format);
                    $fb = ExamSlot::formatSortOrder($b->exam_format);
                    if ($fa !== $fb) {
                        return $fa <=> $fb;
                    }
                    $la = $a->course?->resolvedNtaLevel() ?? 99;
                    $lb = $b->course?->resolvedNtaLevel() ?? 99;
                    if ($la !== $lb) {
                        return $la <=> $lb;
                    }

                    return strcmp(
                        strtoupper((string) ($a->course?->code ?? '')),
                        strtoupper((string) ($b->course?->code ?? ''))
                    );
                })->values();
                $first = $sorted->first();
                $sessions[] = [
                    'time_label' => $this->formatExamTimeRange($first->start_time, $first->end_time),
                    'slots' => $sorted,
                ];
            }
            usort($sessions, function ($a, $b) {
                $ta = $a['slots']->first()->start_time ?? '';
                $tb = $b['slots']->first()->start_time ?? '';

                return strcmp((string) $ta, (string) $tb);
            });

            $rowCount = 0;
            foreach ($sessions as $sess) {
                $rowCount += count($sess['slots']);
            }

            $out[] = [
                'day_upper' => strtoupper($date->format('l')),
                'date_display' => $date->format('d/m/Y'),
                'sessions' => $sessions,
                'row_count' => $rowCount,
            ];
        }

        return $out;
    }

    private function timetableMonthYearLabel(Collection $slots, ?Semester $semester): string
    {
        if ($slots->isNotEmpty()) {
            return strtoupper($slots->first()->exam_date->format('F, Y'));
        }
        if ($semester?->start_date) {
            return strtoupper(Carbon::parse($semester->start_date)->format('F, Y'));
        }

        return strtoupper(now()->format('F, Y'));
    }

    private function timetableFooterMonthYear(Collection $slots, ?Semester $semester): string
    {
        if ($slots->isNotEmpty()) {
            return $slots->first()->exam_date->format('F Y');
        }
        if ($semester?->start_date) {
            return Carbon::parse($semester->start_date)->format('F Y');
        }

        return now()->format('F Y');
    }

    /**
     * Write exam timetable rows using vertical merge (rowspan) for Day and Time, matching the HTML grid on exam-slots index.
     *
     * @param  array<int, array{day_upper: string, date_display: string, row_count: int, sessions: list<array{time_label: string, slots: Collection<int, ExamSlot>}>}>  $timetableDays
     */
    private function writeExamTimetableWordTableBody(WordTableElement $table, array $timetableDays): void
    {
        $widths = [2200, 2800, 1800, 5200];
        $headers = ['Day', 'Time', 'Module code', 'Module name'];

        $table->addRow();
        foreach ($headers as $i => $heading) {
            $table->addCell($widths[$i], [
                'valign' => 'center',
                'shading' => [
                    'fill' => 'E9ECEF',
                    'pattern' => 'solid',
                ],
            ])->addText(
                $this->sanitizeWordPlainText($heading),
                ['bold' => true, 'size' => 11],
                ['alignment' => 'center']
            );
        }

        foreach ($timetableDays as $day) {
            $firstRowOfDay = true;
            foreach ($day['sessions'] as $session) {
                $firstRowOfSession = true;
                foreach ($session['slots'] as $slot) {
                    $table->addRow();

                    if ($firstRowOfDay) {
                        $dayCell = $table->addCell($widths[0], [
                            'vMerge' => WordCellStyle::VMERGE_RESTART,
                            'valign' => 'top',
                        ]);
                        $dayCell->addText($this->sanitizeWordPlainText($day['day_upper']), ['bold' => true, 'size' => 12]);
                        $dayCell->addTextBreak();
                        $dayCell->addText(
                            $this->sanitizeWordPlainText($day['date_display']),
                            ['size' => 11, 'color' => '666666']
                        );
                        $firstRowOfDay = false;
                    } else {
                        $table->addCell($widths[0], ['vMerge' => WordCellStyle::VMERGE_CONTINUE]);
                    }

                    if ($firstRowOfSession) {
                        $table->addCell($widths[1], [
                            'vMerge' => WordCellStyle::VMERGE_RESTART,
                            'valign' => 'top',
                        ])->addText($this->sanitizeWordPlainText($session['time_label']), ['size' => 12]);
                        $firstRowOfSession = false;
                    } else {
                        $table->addCell($widths[1], ['vMerge' => WordCellStyle::VMERGE_CONTINUE]);
                    }

                    $code = $this->sanitizeWordPlainText(strtoupper((string) ($slot->course?->code ?? '')));
                    $table->addCell($widths[2], ['valign' => 'top'])
                        ->addText($code, ['size' => 12, 'name' => 'Courier New']);

                    $nameCell = $table->addCell($widths[3], ['valign' => 'top']);
                    $nameCell->addText(
                        $this->sanitizeWordPlainText(strtoupper((string) ($slot->course?->name ?? ''))),
                        ['size' => 12]
                    );
                    $nameCell->addTextBreak();
                    $formatLine = $slot->formatDisplayLabel();
                    if ($slot->notes) {
                        $formatLine .= ' · '.strtoupper((string) $slot->notes);
                    }
                    $nameCell->addText(
                        $this->sanitizeWordPlainText($formatLine),
                        ['size' => 11, 'color' => '666666']
                    );
                }
            }
        }
    }

    private function formatExamTimeRange($start, $end): string
    {
        if ($start === null || $start === '') {
            return 'Time TBA';
        }
        $s = Carbon::parse($start)->format('g:i A');
        if ($end === null || $end === '') {
            return $s;
        }
        $e = Carbon::parse($end)->format('g:i A');

        return "{$s} – {$e}";
    }

    /**
     * Remove characters that are not allowed in XML 1.0 text nodes (Word rejects the package otherwise).
     */
    private function sanitizeWordPlainText(?string $text): string
    {
        $text = (string) $text;

        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
    }
}
