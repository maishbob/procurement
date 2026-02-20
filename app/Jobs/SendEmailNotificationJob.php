<?php

namespace App\Jobs;

use App\Core\Audit\AuditService;
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

    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        protected User         $user,
        protected Notification $notification,
        protected string       $channel = 'mail'
    ) {}

    public function handle(AuditService $auditService): void
    {
        try {
            $this->user->notify($this->notification);

            $auditService->log(
                action: 'EMAIL_SENT',
                model: 'Notification',
                modelId: $this->user->id,
                metadata: [
                    'notification_class' => get_class($this->notification),
                    'channel'            => $this->channel,
                    'recipient'          => $this->user->email,
                ],
            );
        } catch (\Exception $e) {
            $auditService->log(
                action: 'EMAIL_FAILED',
                model: 'Notification',
                modelId: $this->user->id,
                metadata: [
                    'notification_class' => get_class($this->notification),
                    'recipient'          => $this->user->email,
                    'error'              => $e->getMessage(),
                ],
            );

            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff);
            } else {
                $this->fail($e);
            }
        }
    }

    public function tags(): array
    {
        return [
            'notification',
            'email',
            'user:' . $this->user->id,
            'class:' . class_basename($this->notification),
        ];
    }
}
