<?php

namespace App\Console\Commands;

use App\Models\ClinicalProcedure;
use App\Models\Programme;
use App\Support\CmtPracticumCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportCmtPracticumFromGuideCommand extends Command
{
    protected $signature = 'clinical:import-cmt-practicum
                            {--level=4 : NTA level 4, 5, or 6}
                            {--json= : Path to parsed JSON}
                            {--reparse : Re-run PDF extract + Python parser first}';

    protected $description = 'Import CMT clinical procedures from parsed practicum guide (levels 4–6)';

    public function handle(): int
    {
        $level = (int) $this->option('level');
        if (! CmtPracticumCatalog::isSupportedLevel($level)) {
            $this->error('Level must be 4, 5, or 6.');

            return self::FAILURE;
        }

        if ($this->option('reparse')) {
            $this->reparsePdf($level);
        }

        $path = $this->option('json') ?: storage_path("app/cmt{$level}-practicum-import.json");
        if (! File::isFile($path)) {
            $this->error("Import file not found: {$path}. Run with --reparse or: py scripts/extract_practicum_pdf.py --level {$level}");

            return self::FAILURE;
        }

        $data = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $rows = $data['procedures'] ?? [];
        if ($rows === []) {
            $this->error('No procedures in JSON.');

            return self::FAILURE;
        }

        $programme = Programme::query()->whereRaw('UPPER(code) = ?', ['CMT'])->first();
        $nta = (int) ($data['nta_level'] ?? $level);
        $codes = [];
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            $code = $row['code'] ?? null;
            if (! $code) {
                continue;
            }
            $codes[] = $code;
            $payload = [
                'programme_id' => $programme?->id,
                'nta_level' => $nta,
                'department_code' => $row['department_code'] ?? null,
                'code' => $code,
                'parent_code' => $row['parent_code'] ?? null,
                'name' => mb_substr((string) ($row['name'] ?? $code), 0, 255),
                'description' => $row['description'] ?? null,
                'assessment_modes' => $row['assessment_modes'] ?? null,
                'practicum_section' => $row['practicum_section'] ?? null,
                'source_type' => $row['source'] ?? 'guide_import',
                'min_required_count' => ($row['source'] ?? '') === 'checklist_criterion'
                    ? 0
                    : max(1, (int) ($row['min_required_count'] ?? 1)),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'is_active' => true,
            ];

            $existing = ClinicalProcedure::query()
                ->where('nta_level', $nta)
                ->where('code', $code)
                ->first();

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
            ->whereNotIn('code', $codes)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $this->info($data['source'] ?? "CMT NTA {$nta} Practicum Guide import");
        $this->info("Imported: {$created} created, {$updated} updated, {$deactivated} deactivated.");
        $this->info('Total active procedures: '.ClinicalProcedure::query()->where('nta_level', $nta)->where('is_active', true)->count());

        return self::SUCCESS;
    }

    private function reparsePdf(int $level): void
    {
        $extract = base_path('scripts/extract_practicum_pdf.py');
        $parse = base_path('scripts/parse_practicum_guide.py');

        exec('py '.escapeshellarg($extract).' --level '.$level.' 2>&1', $out1, $c1);
        if ($c1 !== 0) {
            $this->warn('PDF extract: '.implode("\n", $out1));
        }
        exec('py '.escapeshellarg($parse).' --level '.$level.' 2>&1', $out2, $c2);
        if ($c2 !== 0) {
            $this->error('Parse failed: '.implode("\n", $out2));
        } else {
            $this->info(implode("\n", $out2));
        }
    }
}
