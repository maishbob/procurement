<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic queued notification used by NotificationService for ad-hoc alerts
 * that do not warrant a dedicated Notification class.
 */
class GenericNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected array $data) {}

    public function via($notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->getUserPreferences()['notification_email'] ?? true) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->data['title'] ?? 'System Notification')
            ->greeting("Hello {$notifiable->name},")
            ->line($this->data['message'] ?? '');

        if (!empty($this->data['action_url'])) {
            $message->action('View Details', $this->data['action_url']);
        }

        return $message->line('This is an automated notification from the Procurement System.');
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type'           => $this->data['type'] ?? 'generic',
            'title'          => $this->data['title'] ?? 'Notification',
            'message'        => $this->data['message'] ?? '',
            'reference_id'   => $this->data['reference_id'] ?? null,
            'reference_type' => $this->data['reference_type'] ?? null,
            'action_url'     => $this->data['action_url'] ?? null,
        ];
    }
}
