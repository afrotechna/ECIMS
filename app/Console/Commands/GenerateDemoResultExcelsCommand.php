<?php

namespace App\Console\Commands;

use App\Models\Programme;
use App\Models\Semester;
use App\Services\ResultImportCsvTemplate;
use App\Services\ResultImportSpreadsheetExport;
use Illuminate\Console\Command;

class GenerateDemoResultExcelsCommand extends Command
{
    protected $signature = 'cohas:generate-demo-result-excels
                            {--semester= : Semester ID (default: latest active)}
                            {--programme= : Programme ID (default: first active)}
                            {--output= : Output directory (default: storage/app/demo-results)}';

    protected $description = 'Generate NTA Level 4–6 demo result Excel files (CA and final) with sample marks';

    public function handle(ResultImportCsvTemplate $templates, ResultImportSpreadsheetExport $export): int
    {
        $semester = $this->resolveSemester();
        $programme = $this->resolveProgramme();

        if (! $semester || ! $programme) {
            $this->error('Need at least one active semester and programme in the database.');

            return self::FAILURE;
        }

        $outputDir = $this->option('output') ?: storage_path('app/demo-results');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $publicDir = public_path('demo-results');
        if (! is_dir($publicDir)) {
            mkdir($publicDir, 0755, true);
        }

        $this->info("Semester: {$semester->label}");
        $this->info("Programme: {$programme->name} ({$programme->code})");
        $this->newLine();

        foreach ([4, 5, 6] as $ntaLevel) {
            $courses = \App\Services\ResultImportCourseQuery::forTemplate($semester, $programme, $ntaLevel);
            if ($courses->isEmpty()) {
                $this->warn("NTA Level {$ntaLevel}: no modules — skipped.");

                continue;
            }

            foreach (['ca' => 'demoRowsForCa', 'final' => 'demoRowsForFinal'] as $type => $method) {
                $rows = $templates->{$method}($semester, $programme, $ntaLevel);
                $basename = "nta{$ntaLevel}-{$type}-results-{$programme->code}-sem{$semester->number}-{$semester->academic_year}.xls";
                $path = $outputDir.DIRECTORY_SEPARATOR.$basename;
                $sheetName = $type === 'ca' ? 'CA Results' : 'Final Results';
                $export->save($path, $rows, $sheetName);
                copy($path, $publicDir.DIRECTORY_SEPARATOR.$basename);
                $this->line("  <info>✓</info> {$basename} ({$courses->count()} modules)");
            }
        }

        $this->newLine();
        $this->info("Files saved to: {$outputDir}");
        $this->info("Public copies: {$publicDir}");

        return self::SUCCESS;
    }

    private function resolveSemester(): ?Semester
    {
        if ($id = $this->option('semester')) {
            return Semester::find($id);
        }

        return Semester::where('is_active', true)
            ->orderByDesc('academic_year')
            ->orderByDesc('number')
            ->first();
    }

    private function resolveProgramme(): ?Programme
    {
        if ($id = $this->option('programme')) {
            return Programme::find($id);
        }

        return Programme::where('is_active', true)->orderBy('name')->first();
    }
}
