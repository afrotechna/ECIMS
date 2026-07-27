<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReplaceCmtLevelStudentsCommand extends Command
{
    protected $signature = 'srs:replace-cmt-level-students
                            {file : Path to CMT LEVEL .xlsx file}
                            {--level= : NTA level (4, 5, or 6); inferred from filename if omitted}
                            {--intake-year= : Default intake year if not in registration number}
                            {--dry-run : Parse file only; do not delete or import}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete existing CMT students for an NTA level and import a new list from Excel (.xlsx).';

    public function handle(): int
    {
        return $this->call('srs:replace-programme-level-students', [
            'file' => $this->argument('file'),
            '--programme' => 'CMT',
            '--level' => $this->option('level'),
            '--intake-year' => $this->option('intake-year'),
            '--dry-run' => $this->option('dry-run'),
            '--force' => $this->option('force'),
        ]);
    }
}
