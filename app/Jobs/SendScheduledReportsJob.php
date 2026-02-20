<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ScheduledReport;
use Illuminate\Support\Facades\Mail;
use App\Mail\ScheduledReportMail;

class SendScheduledReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 2;
    protected int $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Get all scheduled reports due today
            $reports = ScheduledReport::where('active', true)
                ->where(function ($query) {
                    $query->whereNull('last_sent_at')
                        ->orWhereRaw('DATE_ADD(last_sent_at, INTERVAL frequency DAY) <= NOW()');
                })
                ->get();

            foreach ($reports as $report) {
                $this->sendReport($report);
            }

            // Audit log
            app(\App\Core\Audit\AuditService::class)->log(
                'SCHEDULED_REPORTS_SENT',
                'ScheduledReport',
                null,
                null,
                null,
                "Sent {$reports->count()} scheduled reports",
                [
                    'reports_sent' => $reports->count(),
                    'timestamp' => now()->toDateTimeString(),
                ]
            );
        } catch (\Exception $e) {
            app(\App\Core\Audit\AuditService::class)->log(
                'SCHEDULED_REPORTS_FAILED',
                'ScheduledReport',
                null,
                null,
                null,
                'Failed to send scheduled reports: ' . $e->getMessage(),
                [
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Send individual scheduled report
     */
    protected function sendReport(ScheduledReport $report): void
    {
        try {
            // Generate report
            $reportJob = new GenerateReportJob(
                $report->user,
                $report->report_type,
                json_decode($report->filters ?? '{}', true),
                $report->format,
                false // Don't send email from generation job, we'll do it here
            );

            // Generate the report
            $reportJob->handle();

            // Get the generated file path
            $generatedReport = \DB::table('generated_reports')
                ->where('user_id', $report->user_id)
                ->where('report_type', $report->report_type)
                ->orderByDesc('created_at')
                ->first(['filepath']);

            // Send email with report attachment
            if ($generatedReport && $report->email_recipients) {
                Mail::to(json_decode($report->email_recipients, true))
                    ->send(new ScheduledReportMail($report, $generatedReport->filepath));
            }

            // Update last sent time
            $report->update(['last_sent_at' => now()]);
        } catch (\Exception $e) {
            // Log individual report failure
            app(\App\Core\Audit\AuditService::class)->log(
                'SCHEDULED_REPORT_FAILED',
                'ScheduledReport',
                $report->id,
                null,
                null,
                "Failed to send scheduled report '{$report->report_type}': {$e->getMessage()}",
                [
                    'report_id' => $report->id,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'scheduled',
            'reports',
            'distribution',
        ];
    }
}
