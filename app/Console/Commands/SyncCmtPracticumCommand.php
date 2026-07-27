<?php

namespace App\Console\Commands;

use App\Models\ClinicalProcedure;
use App\Models\Programme;
use App\Support\CmtPracticumCatalog;
use Illuminate\Console\Command;

class SyncCmtPracticumCommand extends Command
{
    protected $signature = 'clinical:sync-cmt-practicum
                            {--level=4 : NTA level 4, 5, or 6}';

    protected $description = 'Sync CMT clinical logbook procedures from config catalogue (curated subset)';

    public function handle(): int
    {
        $level = (int) $this->option('level');
        if (! CmtPracticumCatalog::isSupportedLevel($level)) {
            $this->error('Level must be 4, 5, or 6.');

            return self::FAILURE;
        }

        $catalog = CmtPracticumCatalog::forLevel($level);
        $nta = $catalog->ntaLevel();
        $programme = Programme::query()
            ->whereRaw('UPPER(code) = ?', ['CMT'])
            ->first();

        $catalogCodes = [];
        $order = 0;
        $created = 0;
        $updated = 0;

        foreach ($catalog->procedures() as $row) {
            $catalogCodes[] = $row['code'];
            $existing = ClinicalProcedure::query()
                ->where('nta_level', $nta)
                ->where('code', $row['code'])
                ->first();

            $payload = [
                'programme_id' => $programme?->id,
                'nta_level' => $nta,
                'department_code' => $row['department_code'],
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => $row['description'],
                'assessment_modes' => $row['assessment_modes'] ?? null,
                'practicum_section' => $row['practicum_section'] ?? null,
                'min_required_count' => $row['min_required_count'],
                'sort_order' => $order++,
                'is_active' => true,
            ];

            if ($existing) {
                $existing->update($payload);
                $updated++;
            } else {
                ClinicalProcedure::create($payload);
                $created++;
            }
        }

        $deactivated = ClinicalProcedure::query()
            ->where('nta_level', $nta)
            ->whereNotIn('code', $catalogCodes)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->info($catalog->sourceLabel());
        $this->info("NTA {$nta}: {$created} created, {$updated} updated, {$deactivated} deactivated (not in catalogue).");

        return self::SUCCESS;
    }
}
