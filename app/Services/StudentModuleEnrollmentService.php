<?php

namespace App\Services;

use App\Models\ClinicalRotationRound;
use App\Models\Course;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentModuleEnrollment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StudentModuleEnrollmentService
{
    public function __construct(
        private readonly StudentModuleCatalogService $catalogService,
    ) {}

    /**
     * @return array{
     *     semester: Semester|null,
     *     semesters: Collection<int, Semester>,
     *     available: Collection<int, Course>,
     *     enrolled_ids: array<int, int>,
     *     carry_repeat_ids: array<int, int>,
     *     requires_rotation_ids: array<int, int>,
     *     can_register: bool,
     *     block_reason: string|null,
     *     repeat_suggestions: Collection<int, Course>,
     * }
     */
    public function registrationContext(Student $student, ?int $semesterId = null): array
    {
        $student->loadMissing('programme');

        $semesters = $this->selectableSemesters($student);

        $semester = null;
        if ($semesterId) {
            $semester = $semesters->firstWhere('id', $semesterId);
        }
        $semester ??= $this->defaultSemesterForRegistration($student, $semesters);

        $approvedForSemester = $semester && $this->hasApprovedRegistration($student, $semester);
        $canRegister = $approvedForSemester && $student->programme_id;
        $blockReason = null;
        if (! $student->programme_id) {
            $blockReason = 'Your programme is not set on your student record. Contact the registry office.';
        } elseif (! $semester) {
            $blockReason = 'No active semester is available for module registration. Contact the registry office.';
        } elseif (! $approvedForSemester) {
            $blockReason = 'Complete and obtain approval for semester registration before selecting modules.';
        }

        $available = $semester
            ? $this->availableCoursesForSemester($student, $semester)
            : collect();

        $enrollments = $semester
            ? $student->moduleEnrollments()->where('semester_id', $semester->id)->get()
            : collect();

        $repeatSuggestions = $this->failedModulesForRepeat($student, $semester);

        return [
            'semester' => $semester,
            'semesters' => $semesters,
            'available' => $available,
            'enrolled_ids' => $enrollments->pluck('course_id')->map(fn ($id) => (int) $id)->all(),
            'carry_repeat_ids' => $enrollments->where('is_carry_repeat', true)->pluck('course_id')->map(fn ($id) => (int) $id)->all(),
            'requires_rotation_ids' => $available->where('requires_clinical_rotation', true)->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'repeat_suggestions' => $repeatSuggestions,
            'repeat_suggestion_ids' => $repeatSuggestions->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'can_register' => $canRegister,
            'block_reason' => $blockReason,
        ];
    }

    /**
     * Courses the student failed previously (for repeat registration hints).
     *
     * @return Collection<int, Course>
     */
    public function failedModulesForRepeat(Student $student, ?Semester $forSemester = null): Collection
    {
        if (! $student->programme_id) {
            return collect();
        }

        $failedCourseIds = Result::query()
            ->where('student_id', $student->id)
            ->whereHas('course', fn ($c) => $c->where('programme_id', $student->programme_id))
            ->get()
            ->filter(fn (Result $r) => $r->finalOutcome() === 'fail')
            ->pluck('course_id')
            ->unique();

        if ($failedCourseIds->isEmpty()) {
            return collect();
        }

        $catalog = $forSemester
            ? $this->availableCoursesForSemester($student, $forSemester)
            : collect();

        return Course::query()
            ->whereIn('id', $failedCourseIds)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->filter(fn (Course $c) => $catalog->isEmpty() || $catalog->contains('id', $c->id));
    }

    /**
     * @param  list<int>  $courseIds
     * @param  list<int>  $carryRepeatCourseIds
     */
    public function saveForSemester(Student $student, Semester $semester, array $courseIds, array $carryRepeatCourseIds = []): void
    {
        $context = $this->registrationContext($student, $semester->id);
        if (! $context['can_register']) {
            throw ValidationException::withMessages([
                'course_ids' => [$context['block_reason'] ?? 'You cannot register modules for this semester.'],
            ]);
        }

        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));
        $carrySet = array_flip(array_map('intval', $carryRepeatCourseIds));

        if ($courseIds === []) {
            throw ValidationException::withMessages([
                'course_ids' => ['Select at least one module for this semester.'],
            ]);
        }

        $allowedIds = $context['available']->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invalid = array_diff($courseIds, $allowedIds);
        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'course_ids' => ['One or more selected modules are not offered for your programme, NTA level, and semester.'],
            ]);
        }

        $student->moduleEnrollments()->where('semester_id', $semester->id)->delete();

        foreach ($courseIds as $courseId) {
            StudentModuleEnrollment::create([
                'student_id' => $student->id,
                'course_id' => $courseId,
                'semester_id' => $semester->id,
                'is_carry_repeat' => isset($carrySet[$courseId]),
            ]);
        }
    }

    /** Active students who should be placed in clinical rotation groups for this round. */
    public function rotationEligibleStudentsQuery(ClinicalRotationRound $round): Builder
    {
        return Student::query()
            ->where('programme_id', $round->programme_id)
            ->where('nta_level', $round->nta_level)
            ->where('status', 'active')
            ->whereHas('moduleEnrollments', function (Builder $q) use ($round) {
                $q->where('semester_id', $round->semester_id)
                    ->whereHas('course', fn (Builder $c) => $c->where('requires_clinical_rotation', true));
            });
    }

    /**
     * @return Collection<int, Semester>
     */
    public function selectableSemesters(Student $student): Collection
    {
        $approvedIds = $student->semesterRegistrations()
            ->where('status', 'approved')
            ->whereNull('wizard_step')
            ->pluck('semester_id');

        return Semester::query()
            ->where('is_active', true)
            ->whereIn('id', $approvedIds)
            ->orderByDesc('academic_year')
            ->orderByDesc('number')
            ->get();
    }

    /**
     * @return Collection<int, Course>
     */
    public function availableCoursesForSemester(Student $student, Semester $semester): Collection
    {
        $catalog = $this->catalogService->catalogForStudent($student, (int) $semester->academic_year);
        $termNumber = (int) $semester->number;

        $modules = match ($termNumber) {
            Semester::PERIOD_FIRST => $catalog['semester_one'],
            Semester::PERIOD_SECOND => $catalog['semester_two'],
            default => collect(),
        };

        return $modules->values();
    }

    private function defaultSemesterForRegistration(Student $student, Collection $selectable): ?Semester
    {
        if ($selectable->isEmpty()) {
            return Semester::query()->where('is_active', true)->orderByDesc('academic_year')->orderByDesc('number')->first();
        }

        $active = Semester::query()->where('is_active', true)->orderByDesc('number')->first();
        if ($active && $selectable->contains('id', $active->id)) {
            return $active;
        }

        return $selectable->first();
    }

    private function hasApprovedRegistration(Student $student, Semester $semester): bool
    {
        return $student->semesterRegistrations()
            ->where('semester_id', $semester->id)
            ->where('status', 'approved')
            ->whereNull('wizard_step')
            ->exists();
    }
}
