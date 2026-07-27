<?php

namespace App\Services;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalProcedure;
use App\Models\Student;
use Illuminate\Support\Collection;

class ClinicalCompetencyService
{
    /**
     * @return list<array{procedure: ClinicalProcedure, required: int, approved: int, met: bool}>
     */
    public function checklistForStudent(Student $student, ?int $semesterId = null): array
    {
        $nta = (int) ($student->nta_level ?? 5);
        $semesterId ??= $this->resolveSemesterId($student);

        $procedures = ClinicalProcedure::query()
            ->where('is_active', true)
            ->where('nta_level', $nta)
            ->whereNotNull('min_required_count')
            ->where('min_required_count', '>', 0)
            ->where(fn ($q) => $q
                ->whereNull('source_type')
                ->orWhere('source_type', '!=', 'checklist_criterion'))
            ->orderBy('sort_order')
            ->get();

        if ($procedures->isEmpty()) {
            return [];
        }

        $counts = ClinicalLogbookEntry::query()
            ->where('student_id', $student->id)
            ->where('status', ClinicalLogbookEntry::STATUS_APPROVED)
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->selectRaw('clinical_procedure_id, count(*) as total')
            ->groupBy('clinical_procedure_id')
            ->pluck('total', 'clinical_procedure_id');

        $items = [];
        foreach ($procedures as $procedure) {
            $required = (int) $procedure->min_required_count;
            $approved = (int) ($counts[$procedure->id] ?? 0);
            $items[] = [
                'procedure' => $procedure,
                'required' => $required,
                'approved' => $approved,
                'met' => $approved >= $required,
            ];
        }

        return $items;
    }

    public function overallMet(Student $student, ?int $semesterId = null): bool
    {
        $items = $this->checklistForStudent($student, $semesterId);
        if ($items === []) {
            return true;
        }

        return collect($items)->every(fn ($i) => $i['met']);
    }

    private function resolveSemesterId(Student $student): ?int
    {
        $id = $student->semesterRegistrations()
            ->where('status', 'approved')
            ->whereNull('wizard_step')
            ->orderByDesc('updated_at')
            ->value('semester_id');

        return $id ? (int) $id : null;
    }
}
