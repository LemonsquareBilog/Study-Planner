<?php

use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

if (PHP_OS_FAMILY === 'Windows') {
    // The host demo uses its own SQLite environment and compatible PHP worker.
    DevCommands::register('powershell -NoProfile -ExecutionPolicy Bypass -File scripts/serve.ps1', 'server');
    // .env.development executes jobs synchronously; a default-environment queue
    // listener would repeatedly reconnect to the unrelated Docker database.
    DevCommands::except('queue');
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reminders:study-sessions')->everyFifteenMinutes();
Schedule::command('reminders:deadlines')->hourly();
