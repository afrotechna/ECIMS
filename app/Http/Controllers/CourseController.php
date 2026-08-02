<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Course;
use App\Models\Programme;
use App\Models\Semester;
use App\Support\CurriculumCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $semestersForFilter = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $programmesForFilter = Programme::where('is_active', true)->orderBy('code')->get();

        $semesterId = $request->get('semester_id');
        $programmeId = $request->get('programme_id');

        $filteredCourses = collect();
        $filteredCreditsTotal = 0.0;
        $currentSemester = null;
        $catalogueFilterActive = false;
        $sort = $request->get('sort', 'year_of_study');
        $direction = $request->get('direction') === 'desc' ? 'desc' : 'asc';
        $sortable = ['code', 'name', 'year_of_study', 'ca_weight', 'exam_weight', 'credits'];
        if (! in_array($sort, $sortable, true)) {
            $sort = 'year_of_study';
        }

        if ($semesterId !== null && $semesterId !== '') {
            $currentSemester = Semester::find($semesterId);
            if ($currentSemester) {
                $catalogueFilterActive = true;
                $query = Course::with('programme')
                    ->whereHas('semesters', fn ($q) => $q->where('semesters.id', $semesterId));
                if ($programmeId) {
                    $query->where('programme_id', $programmeId);
                }
                $filteredCreditsTotal = (float) (clone $query)->sum('credits');
                $query->orderBy($sort, $direction);
                if ($sort !== 'code') {
                    $query->orderBy('code');
                }
                $filteredCourses = $query->paginate(25)->withQueryString();
            }
        }

        $programmesTree = [];
        if (! $catalogueFilterActive) {
            $courses = Course::with(['programme', 'semesters'])
                ->orderBy('code')
                ->get();

            $programmesById = Programme::orderBy('code')->get()->keyBy('id');

            $levelOrder = [4, 5, 6];
            $semesterSectionOrder = [1, 2, 0];

            $programmeIds = $courses->pluck('programme_id')->unique()->sort(function ($a, $b) use ($programmesById) {
                $codeA = $programmesById->get($a)?->code ?? '';
                $codeB = $programmesById->get($b)?->code ?? '';

                return strcmp((string) $codeA, (string) $codeB);
            })->values();

            foreach ($programmeIds as $pid) {
                $programme = $programmesById->get($pid);
                if (! $programme) {
                    continue;
                }
                $progCourses = $courses->where('programme_id', $pid);

                $levels = [];
                foreach ($levelOrder as $level) {
                    $atLevel = $progCourses->filter(fn (Course $c) => $c->resolvedNtaLevel() === $level);
                    if ($atLevel->isEmpty()) {
                        continue;
                    }

                    $semesters = [];
                    foreach ($semesterSectionOrder as $semNum) {
                        $inSection = $atLevel->filter(function (Course $course) use ($semNum) {
                            $nums = $course->semesterTermNumbers();
                            if ($semNum === 0) {
                                return $nums->isEmpty();
                            }

                            return $nums->contains($semNum);
                        })->unique('id')->values();

                        if ($inSection->isEmpty()) {
                            continue;
                        }
                        $semesters[$semNum] = $inSection;
                    }

                    if ($semesters !== []) {
                        $levels[$level] = $semesters;
                    }
                }

                if ($levels !== []) {
                    $programmesTree[] = [
                        'programme' => $programme,
                        'levels' => $levels,
                    ];
                }
            }
        }

        return view('courses.index', compact(
            'programmesTree',
            'semestersForFilter',
            'programmesForFilter',
            'filteredCourses',
            'filteredCreditsTotal',
            'currentSemester',
            'semesterId',
            'programmeId',
            'catalogueFilterActive',
            'sort',
            'direction'
        ));
    }

    public function create(Request $request)
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $semesters = Semester::where('is_active', true)->orderBy('academic_year')->orderBy('number')->get();
        $selectedProgrammeId = $request->integer('programme_id') ?: null;
        if ($selectedProgrammeId && ! $programmes->firstWhere('id', $selectedProgrammeId)) {
            $selectedProgrammeId = null;
        }
        $selectedNtaLevel = $request->integer('nta_level') ?: null;
        if (! in_array($selectedNtaLevel, [4, 5, 6], true)) {
            $selectedNtaLevel = null;
        }

        return view('courses.create', compact('programmes', 'semesters', 'selectedProgrammeId', 'selectedNtaLevel'));
    }

    public function store(Request $request)
    {
        $curriculumTokens = array_values(array_filter(
            $request->input('curriculum_modules', []),
            fn ($t) => $t !== null && $t !== ''
        ));
        if ($curriculumTokens !== []) {
            return $this->storeCurriculumBulk($request, $curriculumTokens);
        }

        $validated = $request->validate([
            'programme_id' => ['required', 'exists:programmes,id'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('courses')->where('programme_id', $request->programme_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'credits' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'ca_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:6'],
            'nta_level' => ['required', 'integer', Rule::in([4, 5, 6])],
            'has_practical' => ['nullable', 'boolean'],
            'practical_assessment_type' => ['nullable', Rule::in(['practical', 'ospe', 'osce'])],
            'requires_clinical_rotation' => ['nullable', 'boolean'],
            'semester_ids' => ['nullable', 'array'],
            'semester_ids.*' => ['exists:semesters,id'],
        ]);

        $validated['year_of_study'] = (int) ($validated['year_of_study'] ?? 1);
        $validated['nta_level'] = (int) $validated['nta_level'];
        $validated['credits'] = (float) ($validated['credits'] ?? 0);
        $validated['has_practical'] = $request->boolean('has_practical');
        $validated['requires_clinical_rotation'] = $request->boolean('requires_clinical_rotation', true);
        if (! $validated['has_practical']) {
            $validated['practical_assessment_type'] = null;
        }

        $semesterIds = $validated['semester_ids'] ?? [];
        unset($validated['semester_ids']);

        $course = Course::create($validated);
        $course->semesters()->sync($semesterIds);

        return redirect()->route('courses.create', [
            'programme_id' => $validated['programme_id'],
            'nta_level' => $validated['nta_level'],
        ])->with('success', "Module {$course->code} added. Add another for the same programme, or go to the module catalogue when done.");
    }

    /**
     * Create multiple modules from approved curriculum tables (NTA level + semester encoded in each token).
     *
     * @param  list<string>  $tokens  Each: "{nta_level}|{semester_term}|{module_code}"
     */
    protected function storeCurriculumBulk(Request $request, array $tokens): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'programme_id' => ['required', 'exists:programmes,id'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $programme = Programme::findOrFail($validated['programme_id']);
        $yearOfStudy = (int) ($validated['year_of_study'] ?? 1);

        $created = 0;
        $skipped = [];

        DB::transaction(function () use ($tokens, $programme, $yearOfStudy, &$created, &$skipped) {
            foreach ($tokens as $token) {
                if (! preg_match('/^([456])\|([12])\|(.+)$/', (string) $token, $m)) {
                    throw ValidationException::withMessages([
                        'curriculum_modules' => ['Invalid module selection format.'],
                    ]);
                }
                $ntaLevel = (int) $m[1];
                $semesterTerm = (int) $m[2];
                $moduleCode = trim($m[3]);

                $row = CurriculumCatalog::findModule($programme, $ntaLevel, $semesterTerm, $moduleCode);
                if ($row === null) {
                    throw ValidationException::withMessages([
                        'curriculum_modules' => ["Module {$moduleCode} is not in the approved curriculum for this programme (NTA {$ntaLevel}, Semester {$semesterTerm})."],
                    ]);
                }

                $exists = Course::query()
                    ->where('programme_id', $programme->id)
                    ->whereRaw('UPPER(code) = ?', [strtoupper($moduleCode)])
                    ->exists();
                if ($exists) {
                    $skipped[] = $moduleCode;

                    continue;
                }

                $w = CurriculumCatalog::defaultWeights();
                $hasPractical = (bool) ($row['has_practical'] ?? false);

                $course = Course::create([
                    'programme_id' => $programme->id,
                    'code' => $moduleCode,
                    'name' => (string) ($row['title'] ?? ''),
                    'credits' => (float) ($row['credits'] ?? 0),
                    'ca_weight' => $w['ca_weight'],
                    'exam_weight' => $w['exam_weight'],
                    'year_of_study' => $yearOfStudy,
                    'nta_level' => $ntaLevel,
                    'has_practical' => $hasPractical,
                    'practical_assessment_type' => $hasPractical
                        ? ($row['practical_assessment_type'] ?? 'practical')
                        : null,
                ]);

                $semesterIds = Semester::query()
                    ->where('is_active', true)
                    ->where('number', $semesterTerm)
                    ->pluck('id')
                    ->all();
                $course->semesters()->sync($semesterIds);
                $created++;
            }
        });

        if ($created === 0 && $skipped !== []) {
            return redirect()->back()->withInput()->with(
                'error',
                'No new modules were added. Already registered: '.implode(', ', $skipped).'.'
            );
        }

        $msg = $created === 1
            ? '1 module added from the curriculum.'
            : "{$created} modules added from the curriculum.";
        if ($skipped !== []) {
            $msg .= ' Skipped (already exist): '.implode(', ', $skipped).'.';
        }
        $msg .= ' Add more for the same programme, or go to the module catalogue when done.';

        return redirect()->route('courses.create', ['programme_id' => $programme->id])->with('success', $msg);
    }

    public function edit(Request $request, Course $course)
    {
        $programmes = Programme::query()
            ->where(function ($q) use ($course) {
                $q->where('is_active', true)
                    ->orWhere('id', $course->programme_id);
            })
            ->orderBy('code')
            ->get();
        $semesters = Semester::where('is_active', true)->orderBy('academic_year')->orderBy('number')->get();

        $course->load('semesters');

        $returnSemesterId = $request->filled('return_semester_id') ? (int) $request->query('return_semester_id') : null;
        $returnProgrammeId = $request->filled('return_programme_id') ? (int) $request->query('return_programme_id') : null;

        return view('courses.edit', compact('course', 'programmes', 'semesters', 'returnSemesterId', 'returnProgrammeId'));
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'programme_id' => ['required', 'exists:programmes,id'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('courses')->where('programme_id', $request->programme_id)->ignore($course->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'credits' => ['nullable', 'numeric', 'min:0', 'max:999.99'],
            'ca_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'exam_weight' => ['required', 'integer', 'min:0', 'max:100'],
            'year_of_study' => ['nullable', 'integer', 'min:1', 'max:6'],
            'nta_level' => ['required', 'integer', Rule::in([4, 5, 6])],
            'has_practical' => ['nullable', 'boolean'],
            'practical_assessment_type' => ['nullable', Rule::in(['practical', 'ospe', 'osce'])],
            'requires_clinical_rotation' => ['nullable', 'boolean'],
            'semester_ids' => ['nullable', 'array'],
            'semester_ids.*' => ['exists:semesters,id'],
        ]);

        $validated['year_of_study'] = (int) ($validated['year_of_study'] ?? 1);
        $validated['nta_level'] = (int) $validated['nta_level'];
        $validated['credits'] = (float) ($validated['credits'] ?? 0);
        $validated['has_practical'] = $request->boolean('has_practical');
        $validated['requires_clinical_rotation'] = $request->boolean('requires_clinical_rotation', true);
        if (! $validated['has_practical']) {
            $validated['practical_assessment_type'] = null;
        }
        $semesterIds = $validated['semester_ids'] ?? [];
        unset($validated['semester_ids']);

        $course->update($validated);

        // Only sync semesters when the edit form included the semester section (avoids wiping
        // links when the field was absent from POST). Nested invalid HTML forms could omit IDs.
        if ($request->boolean('semester_ids_submitted')) {
            $course->semesters()->sync($semesterIds);
        }

        $course->refresh();
        $course->load('semesters');

        $to = $this->intendedAcademicsListUrl($request, $course);

        return redirect()->to($to)->with('success', 'Course updated successfully.');
    }

    /**
     * After saving a module, return to module catalogue filtered by semester when possible.
     */
    protected function intendedAcademicsListUrl(Request $request, Course $course): string
    {
        $returnSemesterId = $request->filled('return_semester_id') ? (int) $request->input('return_semester_id') : null;
        $returnProgrammeId = $request->filled('return_programme_id') ? (int) $request->input('return_programme_id') : null;

        if ($returnSemesterId !== null
            && Semester::query()->where('id', $returnSemesterId)->where('is_active', true)->exists()
            && $course->semesters->contains(fn ($s) => (int) $s->id === $returnSemesterId)) {
            $pid = $returnProgrammeId ?: (int) $course->programme_id;

            return route('courses.index', array_filter([
                'semester_id' => $returnSemesterId,
                'programme_id' => $pid,
            ]));
        }

        $resolved = $this->resolveWorkingSemesterIdForCourse($course);
        if ($resolved !== null) {
            return route('courses.index', [
                'semester_id' => $resolved,
                'programme_id' => $course->programme_id,
            ]);
        }

        return route('courses.index');
    }

    /**
     * Prefer a semester whose dates cover today; otherwise the latest academic year / period among linked semesters.
     */
    protected function resolveWorkingSemesterIdForCourse(Course $course): ?int
    {
        if ($course->semesters->isEmpty()) {
            return null;
        }

        $today = now()->startOfDay();
        $ordered = $course->semesters->sortByDesc(fn ($s) => sprintf('%08d-%02d', $s->academic_year, $s->number))->values();

        foreach ($ordered as $s) {
            if ($s->start_date && $s->end_date) {
                if ($today->between($s->start_date->startOfDay(), $s->end_date->endOfDay())) {
                    return (int) $s->id;
                }
            }
        }

        return (int) $ordered->first()->id;
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return redirect()->route('courses.index')->with('success', 'Course deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Course::class,
            'courses.index',
            fn (Request $r) => array_filter([
                'semester_id' => $r->input('semester_id', $r->query('semester_id')),
                'programme_id' => $r->input('programme_id', $r->query('programme_id')),
            ]),
            singularLabel: 'module',
        );
    }
}
