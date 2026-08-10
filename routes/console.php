<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly database backup (config/backup.php: DB-only, gzip-compressed, local disk).
Schedule::command('backup:run --only-db')->daily()->at('02:00')->onOneServer();

// Prune old backups per the retention policy in config/backup.php.
Schedule::command('backup:clean')->daily()->at('02:30')->onOneServer();

// Alerts (via config/backup.php notifications) if no backup exists within the last day.
Schedule::command('backup:monitor')->daily()->at('03:00')->onOneServer();
