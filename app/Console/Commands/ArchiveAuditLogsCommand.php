<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveAuditLogsJob;
use Illuminate\Console\Command;

class ArchiveAuditLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'procurement:archive-logs
                            {--days=90 : Number of days old to archive}
                            {--delete : Delete archived logs}
                            {--force : Force without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Archive audit logs older than specified days to storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $daysOld = (int)$this->option('days');
        $delete = $this->option('delete');
        $force = $this->option('force');

        $cutoffDate = now()->subDays($daysOld)->toDateString();

        $this->info("Archiving audit logs older than {$daysOld} days ({$cutoffDate})");

        if (!$force && !$this->confirm('This will archive logs to storage. Continue?')) {
            return self::FAILURE;
        }

        try {
            // Dispatch job
            dispatch(new ArchiveAuditLogsJob($daysOld, $delete));

            $this->info("✓ Archive job queued successfully");

            if ($delete) {
                $this->warn("⚠ Logs will be deleted after archival");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to queue archive job: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}
