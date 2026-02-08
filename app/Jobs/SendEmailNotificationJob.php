<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Notification;
use App\Models\User;

class SendEmailNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tries = 3;
    protected int $backoff = 60;

    public function __construct(
        protected User $user,
        protected Notification $notification,
        protected string $channel = 'mail'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send notification through specified channel
            $this->user->notify($this->notification);

            // Log successful delivery
            \App\Core\Audit\AuditService::log(
                action: 'EMAIL_SENT',
                status: 'success',
                model_type: 'Notification',
                model_id: $this->user->id,
                description: "Email notification sent to {$this->user->email}",
                metadata: [
                    'notification_class' => get_class($this->notification),
                    'channel' => $this->channel,
                    'recipient' => $this->user->email,
                ]
            );
        } catch (\Exception $e) {
            // Log failure
            \App\Core\Audit\AuditService::log(
                action: 'EMAIL_FAILED',
                status: 'failed',
                model_type: 'Notification',
                model_id: $this->user->id,
                description: "Failed to send email to {$this->user->email}: {$e->getMessage()}",
                metadata: [
                    'notification_class' => get_class($this->notification),
                    'error' => $e->getMessage(),
                ]
            );

            // Retry or fail
            if ($this->attempts() < $this->tries) {
                $this->release(60); // Retry after 60 seconds
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'notification',
            'email',
            'user:' . $this->user->id,
        ];
    }
}
