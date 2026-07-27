<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncCmtNta4PracticumCommand extends Command
{
    protected $signature = 'clinical:sync-cmt4-practicum {--force : Update existing NTA 4 procedures by code}';

    protected $description = 'Sync CMT NTA Level 4 from config catalogue (alias: clinical:sync-cmt-practicum --level=4)';

    public function handle(): int
    {
        return $this->call('clinical:sync-cmt-practicum', ['--level' => 4]);
    }
}
