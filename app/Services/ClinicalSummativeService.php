<?php

namespace App\Services;

use App\Models\ClinicalProgressionDecision;
use App\Models\Result;
use App\Models\Student;
use Illuminate\Support\Collection;

class ClinicalSummativeService
{
    public function __construct(
        private readonly ClinicalCompetencyService $competency,
        private readonly ClinicalPlacementService $placements,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Student $student, ?int $semesterId = null): array
    {
        $student->loadMissing('programme');
        $semesterId ??= $student->semesterRegistrations()
            ->where('status', 'approved')
            ->orderByDesc('updated_at')
            ->value('semester_id');

        $clinicalResults = Result::query()
            ->with('course')
            ->where('student_id', $student->id)
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->whereHas('course', fn ($c) => $c->where('requires_clinical_rotation', true))
            ->get();

        $failedClinical = $clinicalResults->filter(fn (Result $r) => $r->finalOutcome() === 'fail');
        $passedClinical = $clinicalResults->filter(fn (Result $r) => $r->finalOutcome() === 'pass');

        $progression = $semesterId
            ? ClinicalProgressionDecision::where('student_id', $student->id)->where('semester_id', $semesterId)->first()
            : null;

        return [
            'placement' => $this->placements->placementContext($student),
            'competency' => $this->competency->checklistForStudent($student, $semesterId ? (int) $semesterId : null),
            'competency_met' => $this->competency->overallMet($student, $semesterId ? (int) $semesterId : null),
            'clinical_results' => $clinicalResults,
            'failed_clinical' => $failedClinical,
            'passed_clinical' => $passedClinical,
            'progression' => $progression,
            'semester_id' => $semesterId,
        ];
    }
}
