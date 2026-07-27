<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Semester;
use App\Models\Student;
use App\Models\TimetableSlot;
use Illuminate\Support\Collection;

class StudentModuleCatalogService
{
    /**
     * Modules for the student's programme and NTA level, grouped by teaching term (Semester I / II).
     *
     * @return array{
     *     academic_year_start: int,
     *     academic_year_label: string,
     *     programme_name: string,
     *     programme_code: string,
     *     nta_level: int,
     *     semester_one: \Illuminate\Support\Collection<int, Course>,
     *     semester_two: \Illuminate\Support\Collection<int, Course>,
     *     semester_one_credits: float,
     *     semester_two_credits: float,
     * }
     */
    public function catalogForStudent(Student $student, ?int $academicYearStart = null): array
    {
        $student->loadMissing('programme');

        $academicYearStart ??= \App\Support\AcademicSession::defaultStartYear();
        $academicYearLabel = $academicYearStart.'/'.($academicYearStart + 1);

        $semesterOne = collect();
        $semesterTwo = collect();

        if (! $student->programme_id) {
            return $this->emptyCatalog($student, $academicYearStart, $academicYearLabel, $semesterOne, $semesterTwo);
        }

        $ntaLevel = (int) ($student->nta_level ?? 0);
        if ($ntaLevel < 4 || $ntaLevel > 6) {
            $sample = Course::query()
                ->where('programme_id', $student->programme_id)
                ->where('is_active', true)
                ->whereNotNull('nta_level')
                ->value('nta_level');
            $ntaLevel = (int) ($sample ?? 4);
        }

        $yearSemesterIds = Semester::query()
            ->where('academic_year', $academicYearStart)
            ->pluck('id');

        $courses = Course::query()
            ->with(['programme', 'semesters'])
            ->where('programme_id', $student->programme_id)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->filter(fn (Course $c) => $c->resolvedNtaLevel() === $ntaLevel);

        $forYear = $courses->filter(
            fn (Course $c) => $c->semesters->whereIn('id', $yearSemesterIds)->isNotEmpty()
        );

        $catalog = $forYear->isNotEmpty() ? $forYear : $courses;

        $semesterOne = $this->modulesInTerm($catalog, Semester::PERIOD_FIRST);
        $semesterTwo = $this->modulesInTerm($catalog, Semester::PERIOD_SECOND);

        return $this->emptyCatalog($student, $academicYearStart, $academicYearLabel, $semesterOne, $semesterTwo, $ntaLevel);
    }

    /**
     * @return array{
     *     slots_semester_one: Collection,
     *     slots_semester_two: Collection,
     * }
     */
    public function timetableByTerm(Student $student, int $academicYearStart): array
    {
        $semesters = Semester::query()
            ->where('academic_year', $academicYearStart)
            ->orderBy('number')
            ->get();

        $semesterOneId = $semesters->firstWhere('number', Semester::PERIOD_FIRST)?->id;
        $semesterTwoId = $semesters->firstWhere('number', Semester::PERIOD_SECOND)?->id;

        $approvedIds = $student->semesterRegistrations()
            ->where('status', 'approved')
            ->pluck('semester_id');

        $allowedIds = $semesters->pluck('id')->merge($approvedIds)->unique()->filter();

        $fetch = function (?int $semesterId) use ($allowedIds) {
            if (! $semesterId) {
                return collect();
            }

            return TimetableSlot::query()
                ->with(['semester', 'course'])
                ->where('semester_id', $semesterId)
                ->whereIn('semester_id', $allowedIds)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
        };

        return [
            'slots_semester_one' => $fetch($semesterOneId),
            'slots_semester_two' => $fetch($semesterTwoId),
        ];
    }

    /**
     * @param  Collection<int, Course>  $catalog
     * @return Collection<int, Course>
     */
    private function modulesInTerm(Collection $catalog, int $termNumber): Collection
    {
        return $catalog
            ->filter(function (Course $course) use ($termNumber) {
                return $course->semesterTermNumbers()->contains($termNumber);
            })
            ->unique('id')
            ->sortBy('code')
            ->values();
    }

    /**
     * @param  Collection<int, Course>  $semesterOne
     * @param  Collection<int, Course>  $semesterTwo
     * @return array<string, mixed>
     */
    private function emptyCatalog(
        Student $student,
        int $academicYearStart,
        string $academicYearLabel,
        Collection $semesterOne,
        Collection $semesterTwo,
        ?int $ntaLevel = null
    ): array {
        $level = $ntaLevel ?? (int) ($student->nta_level ?: 0);

        return [
            'academic_year_start' => $academicYearStart,
            'academic_year_label' => $academicYearLabel,
            'programme_name' => $student->programme?->name ?? '—',
            'programme_code' => $student->programme?->code ?? '—',
            'nta_level' => $level,
            'nta_level_label' => Student::NTA_LEVELS[$level] ?? ('NTA Level '.$level),
            'semester_one' => $semesterOne,
            'semester_two' => $semesterTwo,
            'semester_one_credits' => round((float) $semesterOne->sum(fn (Course $c) => (float) $c->credits), 2),
            'semester_two_credits' => round((float) $semesterTwo->sum(fn (Course $c) => (float) $c->credits), 2),
        ];
    }
}
