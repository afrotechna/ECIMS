<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReplaceCmtLevel4StudentsCommand extends Command
{
    protected $signature = 'srs:replace-cmt-level4-students
                            {file : Path to CMT LEVEL 4 .xlsx file}
                            {--intake-year= : Default intake year if not in registration number}
                            {--dry-run : Parse file only; do not delete or import}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete existing CMT NTA Level 4 students and import a new list from Excel (.xlsx).';

    public function handle(): int
    {
        return $this->call('srs:replace-programme-level-students', [
            'file' => $this->argument('file'),
            '--programme' => 'CMT',
            '--level' => 4,
            '--intake-year' => $this->option('intake-year'),
            '--dry-run' => $this->option('dry-run'),
            '--force' => $this->option('force'),
        ]);
    }
}
