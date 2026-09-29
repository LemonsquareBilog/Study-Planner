<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SendStudySessionReminders::class,
        \App\Console\Commands\SendDeadlineReminders::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run every minute to check for upcoming study sessions
        $schedule->command('study:sessions:remind')->everyMinute();

        // Run daily at 8 AM for deadline reminders
        $schedule->command('deadlines:remind')->dailyAt('08:00');

        // Weekly progress report every Monday at 9 AM
        $schedule->command('progress:weekly-report')->weeklyOn(1, '09:00');

        // Clean up old notifications weekly
        $schedule->command('notifications:cleanup')->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}