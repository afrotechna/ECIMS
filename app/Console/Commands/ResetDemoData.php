<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ResetDemoData extends Command
{
    protected $signature = 'demo:reset-data 
                            {--force : Required to execute (prevents accidental runs)}
                            {--yes : Skip the confirmation prompt (non-interactive)}
                            {--full : Also wipe local uploads, temp files, framework cache/sessions/views, and run optimize:clear}';

    protected $description = 'Delete all application data except system administrator user account(s). Use --full to also clear storage files and Laravel caches.';

    public function handle(): int
    {
        if (! $this->option('force')) {
            $this->error('Refusing to reset: pass --force to confirm.');

            return self::FAILURE;
        }

        $adminIds = User::query()->get()->filter(fn (User $u) => $u->isAdmin())->pluck('id');
        if ($adminIds->isEmpty()) {
            $this->error('No system administrator found (role administrator). Create one before resetting, or you will be locked out.');

            return self::FAILURE;
        }

        $this->warn('Keeping admin user id(s): '.$adminIds->implode(', '));
        if (! $this->option('yes') && ! $this->confirm('This will PERMANENTLY delete all other data. Continue?')) {
            return self::FAILURE;
        }

        $driver = Schema::getConnection()->getDriverName();

        $tableNames = match ($driver) {
            'mysql' => collect(DB::select('SHOW TABLES'))
                ->map(fn ($row) => array_values((array) $row)[0])
                ->values()
                ->all(),
            'sqlite' => collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"))
                ->pluck('name')
                ->values()
                ->all(),
            default => null,
        };

        if ($tableNames === null) {
            $this->error('Unsupported database driver: '.$driver.'. Use mysql or sqlite.');

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tableNames as $table) {
                if ($table === 'migrations') {
                    continue;
                }
                if ($table === 'users') {
                    $deleted = DB::table('users')->whereNotIn('id', $adminIds->all())->delete();
                    $this->line("users: removed {$deleted} non-admin row(s).");

                    continue;
                }
                if (! Schema::hasTable($table)) {
                    continue;
                }
                DB::table($table)->truncate();
                $this->line("truncated: {$table}");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $remaining = User::count();

        if ($this->option('full')) {
            $this->newLine();
            $this->warn('Full clean: removing uploads, temp files, sessions, compiled views, file caches…');
            $this->purgeApplicationStorage();
            $this->purgeFrameworkRuntimeFiles();
            Artisan::call('optimize:clear');
            $this->line(Artisan::output());
        }

        $this->info("Done. {$remaining} user row(s) kept (administrator account).".
            ($this->option('full') ? ' Storage and caches cleared.' : ' Run php artisan cache:clear if needed.'));

        return self::SUCCESS;
    }

    /**
     * Remove user-uploaded and generated files under storage/app (keeps .gitignore placeholders).
     */
    private function purgeApplicationStorage(): void
    {
        $app = storage_path('app');
        $public = $app.DIRECTORY_SEPARATOR.'public';

        if (File::isDirectory($public)) {
            foreach (File::directories($public) as $dir) {
                File::deleteDirectory($dir);
                $this->line('removed dir: storage/app/public/'.basename($dir));
            }
            foreach (File::files($public) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                File::delete($file->getPathname());
                $this->line('removed file: storage/app/public/'.$file->getFilename());
            }
        }

        foreach (['temp', 'student-documents', 'livewire-tmp'] as $name) {
            $path = $app.DIRECTORY_SEPARATOR.$name;
            if (File::isDirectory($path)) {
                File::deleteDirectory($path);
                $this->line("removed: storage/app/{$name}");
            }
        }
    }

    /**
     * Clear file sessions, compiled Blade, and file-cache payloads (safe with DB already truncated).
     */
    private function purgeFrameworkRuntimeFiles(): void
    {
        $framework = storage_path('framework');

        $sessions = $framework.DIRECTORY_SEPARATOR.'sessions';
        if (File::isDirectory($sessions)) {
            foreach (File::files($sessions) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                File::delete($file->getPathname());
            }
            $this->line('cleared: storage/framework/sessions (except .gitignore)');
        }

        $views = $framework.DIRECTORY_SEPARATOR.'views';
        if (File::isDirectory($views)) {
            foreach (File::files($views) as $file) {
                if ($file->getFilename() === '.gitignore') {
                    continue;
                }
                File::delete($file->getPathname());
            }
            $this->line('cleared: storage/framework/views (except .gitignore)');
        }

        $cacheData = $framework.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'data';
        if (File::isDirectory($cacheData)) {
            foreach (File::allFiles($cacheData) as $file) {
                File::delete($file->getPathname());
            }
            $this->line('cleared: storage/framework/cache/data');
        }

        $log = storage_path('logs'.DIRECTORY_SEPARATOR.'laravel.log');
        if (File::exists($log)) {
            File::put($log, '');
            $this->line('truncated: storage/logs/laravel.log');
        }
    }
}
