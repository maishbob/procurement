<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;

class ArchiveAuditLogsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $timeout = 600; // 10 minutes

    public function __construct(
        protected int $daysOld = 90,
        protected bool $deleteAfterArchive = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $cutoffDate = now()->subDays($this->daysOld);

            // Fetch logs to archive
            $logs = AuditLog::where('created_at', '<', $cutoffDate)
                ->orderBy('created_at')
                ->chunk(1000, function ($chunk) use ($cutoffDate) {
                    $this->archiveChunk($chunk, $cutoffDate);
                });

            // Count archived records
            $archivedCount = AuditLog::where('created_at', '<', $cutoffDate)
                ->where('archived', true)
                ->count();

            // Optionally delete archived logs
            if ($this->deleteAfterArchive) {
                AuditLog::where('created_at', '<', $cutoffDate)
                    ->where('archived', true)
                    ->delete();
            }

            // Audit log
            app(\App\Core\Audit\AuditService::class)->log(
                'AUDIT_LOGS_ARCHIVED',
                'AuditLog',
                null,
                null,
                null,
                "Archived {$archivedCount} audit logs older than {$this->daysOld} days",
                [
                    'cutoff_date' => $cutoffDate->toDateString(),
                    'archived_count' => $archivedCount,
                    'deleted' => $this->deleteAfterArchive,
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'AUDIT_LOGS_ARCHIVE_FAILED',
                'AuditLog',
                null,
                null,
                null,
                'Failed to archive audit logs: ' . $e->getMessage(),
                [
                    'days_old' => $this->daysOld,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Archive a chunk of audit logs to storage
     */
    protected function archiveChunk($chunk, $cutoffDate): void
    {
        $month = $cutoffDate->format('Y-m');
        $filename = "audit-logs-{$month}.json";
        $path = "archives/audit-logs/{$filename}";

        // Serialize logs to JSON
        $data = $chunk->map(fn($log) => [
            'id' => $log->id,
            'user_id' => $log->user_id,
            'action' => $log->action,
            'status' => $log->status,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'description' => $log->description,
            'metadata' => $log->metadata,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at,
        ])->toArray();

        // Store to archive
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT));

        // Mark as archived
        AuditLog::whereIn('id', $chunk->pluck('id'))
            ->update(['archived' => true]);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scheduled',
            'maintenance',
            'audit-logs',
            'days-old:' . $this->daysOld,
        ];
    }
}
