<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run FBR notice fetching every hour
        $schedule->command('app:fetch-fbr-notices')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('FBR notice fetching failed');
            });

        // Run service deadline reminders every hour
        $schedule->command('app:process-reminders')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Reminder processing failed');
            });

        // Fetch tax news daily at 9am
        $schedule->command('app:fetch-tax-news')
            ->dailyAt('09:00')
            ->withoutOverlapping();

        // Run scheduled task rules every hour (command checks which rules are due)
        $schedule->command('app:run-scheduled-tasks')
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('Scheduled tasks execution failed');
            });
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
