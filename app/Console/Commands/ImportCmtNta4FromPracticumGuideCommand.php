<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportCmtNta4FromPracticumGuideCommand extends Command
{
    protected $signature = 'clinical:import-cmt4-from-guide
                            {--json= : Path to parsed JSON (default: storage/app/cmt4-practicum-import.json)}
                            {--reparse : Re-run Python parser on PDF text first}';

    protected $description = 'Import NTA 4 clinical procedures (alias: clinical:import-cmt-practicum --level=4)';

    public function handle(): int
    {
        return $this->call('clinical:import-cmt-practicum', [
            '--level' => 4,
            '--json' => $this->option('json'),
            '--reparse' => $this->option('reparse'),
        ]);
    }
}
