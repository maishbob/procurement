<?php

namespace App\Console\Commands;

use App\Jobs\SendScheduledReportsJob;
use Illuminate\Console\Command;

class SendScheduledReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'procurement:send-scheduled-reports
                            {--async : Queue job asynchronously}';

    /**
     * The console command description.
     */
    protected $description = 'Send all due scheduled reports to recipients';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for scheduled reports to send...');

        try {
            // Get scheduled reports due today
            $reports = \App\Models\ScheduledReport::where('active', true)
                ->where(function ($query) {
                    $query->whereNull('last_sent_at')
                        ->orWhereRaw('DATE_ADD(last_sent_at, INTERVAL frequency DAY) <= NOW()');
                })
                ->get();

            if ($reports->isEmpty()) {
                $this->info('No scheduled reports due at this time.');
                return self::SUCCESS;
            }

            $this->info("Found {$reports->count()} scheduled reports to send");

            if ($this->option('async')) {
                dispatch(new SendScheduledReportsJob());
                $this->info("✓ Report distribution queued (async)");
            } else {
                // Execute synchronously
                $job = new SendScheduledReportsJob();
                $job->handle();
                $this->info("✓ Scheduled reports sent successfully");
            }

            // Show report summary
            $this->table(
                ['Report Type', 'Recipients', 'Format', 'Frequency'],
                $reports->map(fn($r) => [
                    $r->report_type,
                    count(json_decode($r->email_recipients ?? '[]', true)),
                    $r->format,
                    "{$r->frequency} days",
                ])->toArray()
            );

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to send scheduled reports: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
