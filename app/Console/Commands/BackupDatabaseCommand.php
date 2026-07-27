<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'cohas:backup-database {--keep=14 : Number of daily backups to retain}';

    protected $description = 'Create a MySQL dump in storage/app/backups (requires mysqldump on PATH)';

    public function handle(): int
    {
        $connection = config('database.connections.'.config('database.default'));
        if (($connection['driver'] ?? '') !== 'mysql') {
            $this->error('Backup command supports MySQL only.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $filename = 'cohas-'.date('Y-m-d-His').'.sql';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $host = escapeshellarg($connection['host'] ?? '127.0.0.1');
        $port = escapeshellarg((string) ($connection['port'] ?? '3306'));
        $database = escapeshellarg($connection['database'] ?? '');
        $username = escapeshellarg($connection['username'] ?? '');
        $password = $connection['password'] ?? '';

        $passwordArg = $password !== '' ? '-p'.escapeshellarg($password) : '';

        $command = sprintf(
            'mysqldump -h %s -P %s -u %s %s %s > %s 2>&1',
            $host,
            $port,
            $username,
            $passwordArg,
            $database,
            escapeshellarg($path)
        );

        exec($command, $output, $code);

        if ($code !== 0 || ! is_file($path) || filesize($path) < 100) {
            $this->error('Backup failed. Ensure mysqldump is installed and database credentials are correct.');
            if (! empty($output)) {
                $this->line(implode("\n", $output));
            }
            @unlink($path);

            return self::FAILURE;
        }

        $this->info('Backup saved: '.$path);
        $this->pruneOldBackups($dir, (int) $this->option('keep'));

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $dir, int $keep): void
    {
        $files = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
        }
    }
}
