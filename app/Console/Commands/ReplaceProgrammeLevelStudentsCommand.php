<?php

namespace App\Console\Commands;

use App\Services\StudentBulkImportService;
use App\Support\SimpleXlsxReader;
use Illuminate\Console\Command;

class ReplaceProgrammeLevelStudentsCommand extends Command
{
    protected $signature = 'srs:replace-programme-level-students
                            {file : Path to programme LEVEL .xlsx file (e.g. CMT LEVEL 4.xlsx)}
                            {--programme= : Programme code (CMT or MLT); inferred from filename if omitted}
                            {--level= : NTA level (4, 5, or 6); inferred from filename if omitted}
                            {--intake-year= : Default intake year if not in registration number}
                            {--dry-run : Parse file only; do not delete or import}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete existing programme students for an NTA level and import a new list from Excel (.xlsx).';

    public function handle(StudentBulkImportService $importer): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            $this->error('File not found: '.$path);

            return self::FAILURE;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (! in_array($ext, ['xlsx', 'xls'], true)) {
            $this->error('Expected an Excel file (.xlsx).');

            return self::FAILURE;
        }

        $programme = $this->resolveProgramme($path);
        if ($programme === null) {
            $this->error('Could not determine programme. Use --programme=CMT or --programme=MLT.');

            return self::FAILURE;
        }

        $level = $this->resolveLevel($path);
        if ($level === null) {
            $this->error('Could not determine NTA level. Use --level=4, --level=5, or --level=6.');

            return self::FAILURE;
        }

        if (! in_array($level, [4, 5, 6], true)) {
            $this->error('NTA level must be 4, 5, or 6.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $rows = SimpleXlsxReader::sheetRows($path);
            $parsed = $importer->parseEnrolledListRows($rows);
            $this->info("Dry run: {$programme} NTA Level {$level} — would import ".count($parsed).' student(s).');
            $this->table(['Registration', 'First name', 'Last name', 'Gender'], array_map(fn ($r) => [
                $r['nactvet_reg_no'],
                $r['first_name'],
                $r['last_name'],
                $r['gender'] ?? '—',
            ], array_slice($parsed, 0, 10)));
            if (count($parsed) > 10) {
                $this->line('… and '.(count($parsed) - 10).' more.');
            }

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("This will DELETE all current {$programme} NTA Level {$level} students (and linked portal accounts) and import the new list. Continue?")) {
            return self::FAILURE;
        }

        $intakeYear = $this->option('intake-year') ? (int) $this->option('intake-year') : null;

        try {
            $result = $importer->replaceProgrammeLevelFromSpreadsheet($path, $programme, $level, $intakeYear);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Removed {$result['deleted']} previous {$programme} Level {$level} student(s).");
        $this->info("Imported {$result['created']} new student(s).");

        if ($result['errors'] !== []) {
            $this->warn(count($result['errors']).' issue(s):');
            foreach (array_slice($result['errors'], 0, 15) as $err) {
                $this->line('  • '.$err);
            }
            if (count($result['errors']) > 15) {
                $this->line('  …');
            }
        }

        return self::SUCCESS;
    }

    private function resolveProgramme(string $path): ?string
    {
        if ($this->option('programme') !== null && $this->option('programme') !== '') {
            return strtoupper((string) $this->option('programme'));
        }

        $basename = strtoupper(pathinfo($path, PATHINFO_FILENAME));
        if (preg_match('/\b(CMT|MLT)\b/', $basename, $m)) {
            return $m[1];
        }

        return null;
    }

    private function resolveLevel(string $path): ?int
    {
        if ($this->option('level') !== null && $this->option('level') !== '') {
            return (int) $this->option('level');
        }

        $basename = strtolower(pathinfo($path, PATHINFO_FILENAME));
        if (preg_match('/level\s*([456])\b/', $basename, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
