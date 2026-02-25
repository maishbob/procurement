<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // WHT temp file cleanup (daily at 3am)
        $schedule->command('wht:cleanup-temp')->dailyAt('03:00');

        // Budget threshold checks (hourly)
        $schedule->command('procurement:check-budget-thresholds')->hourly();

        // Low stock alerts (every 6 hours)
        $schedule->command('procurement:check-low-stock')->everySixHours();

        // Exchange rate updates (daily at 6am)
        $schedule->command('procurement:update-exchange-rates')->dailyAt('06:00');

        // Audit log archiving (weekly on Sunday at 2am)
        $schedule->command('procurement:archive-logs')->weeklyOn(0, '2:00');

        // Scheduled reports (every day at 7am)
        $schedule->command('procurement:send-scheduled-reports')->dailyAt('07:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
