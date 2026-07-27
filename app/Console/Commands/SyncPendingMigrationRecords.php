<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mark pending migrations as run when their tables already exist (legacy DBs created before migration records were logged).
 */
class SyncPendingMigrationRecords extends Command
{
    protected $signature = 'cohas:sync-migration-records
                            {--dry-run : Show what would be recorded without writing}';

    protected $description = 'Record pending migrations in the migrations table when target schema already exists';

    /** @var array<string, string> */
    private array $schemaChecks = [
        '2026_02_23_123916_create_announcements_table' => 'announcementsExists',
        '2026_02_23_123935_create_activity_log_table' => 'activityLogExists',
        '2026_05_01_120000_create_programme_nta_level_documents_table' => 'programmeNtaDocsExists',
        '2026_05_01_120001_add_unique_to_programme_nta_level_documents_table' => 'programmeNtaDocsExists',
        '2026_05_04_100000_create_clinical_rotation_tables' => 'clinicalRotationTablesExist',
    ];

    public function handle(): int
    {
        /** @var Migrator $migrator */
        $migrator = $this->laravel->make('migrator');
        $files = $migrator->getMigrationFiles(database_path('migrations'));
        $ran = $migrator->getRepository()->getRan();
        $pending = array_values(array_diff(array_keys($files), $ran));

        if ($pending === []) {
            $this->info('No pending migrations.');

            return self::SUCCESS;
        }

        $batch = (int) DB::table('migrations')->max('batch') + 1;
        $recorded = 0;

        foreach ($pending as $migration) {
            if (! isset($this->schemaChecks[$migration])) {
                continue;
            }
            $check = $this->schemaChecks[$migration];
            if (! $this->{$check}()) {
                $this->line("Skip {$migration}: required schema not found.");

                continue;
            }
            if ($this->option('dry-run')) {
                $this->info("[dry-run] Would record: {$migration} (batch {$batch})");
                $recorded++;

                continue;
            }
            if (in_array($migration, $ran, true)) {
                continue;
            }
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);
            $ran[] = $migration;
            $this->info("Recorded: {$migration} (batch {$batch})");
            $recorded++;
        }

        $stillPending = array_values(array_diff(array_keys($files), $ran));
        if ($stillPending !== []) {
            $this->warn('Still pending (run php artisan migrate):');
            foreach ($stillPending as $m) {
                $this->line('  - '.$m);
            }
        }

        if ($recorded === 0 && ! $this->option('dry-run')) {
            $this->comment('Nothing to sync. Run php artisan migrate for remaining pending migrations.');
        }

        return self::SUCCESS;
    }

    private function announcementsExists(): bool
    {
        return Schema::hasTable('announcements');
    }

    private function activityLogExists(): bool
    {
        return Schema::hasTable('activity_log');
    }

    private function programmeNtaDocsExists(): bool
    {
        return Schema::hasTable('programme_nta_level_documents');
    }

    private function clinicalRotationTablesExist(): bool
    {
        return Schema::hasTable('clinical_rotation_rounds')
            && Schema::hasTable('clinical_rotation_groups')
            && Schema::hasTable('crt_group_students');
    }
}
