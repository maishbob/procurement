<?php

namespace App\Notifications;

use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class RequisitionSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Requisition $requisition,
        protected $approver = null
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];

        if (config('services.slack.webhook_url')) {
            $channels[] = 'slack';
        }

        // Check user preferences for SMS
        if ($notifiable->getUserPreferences()['notification_sms'] ?? false) {
            $channels[] = 'sms';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Requisition #{$this->requisition->requisition_number} Awaiting Approval")
            ->greeting("Hello {$notifiable->name},")
            ->line("A new requisition has been submitted for approval:")
            ->line("**Requisition #:** {$this->requisition->requisition_number}")
            ->line("**Amount:** KES " . number_format($this->requisition->total_amount, 2))
            ->line("**Department:** {$this->requisition->department?->name}")
            ->line("**Requested By:** {$this->requisition->creator?->name}")
            ->line("**Status:** Pending Approval")
            ->action('Review Requisition', route('requisitions.show', $this->requisition))
            ->line('Thank you,')
            ->line('Procurement System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'requisition_submitted',
            'requisition_id' => $this->requisition->id,
            'requisition_number' => $this->requisition->requisition_number,
            'amount' => $this->requisition->total_amount,
            'department' => $this->requisition->department?->name,
            'created_by' => $this->requisition->creator?->name,
            'summary' => "Requisition #{$this->requisition->requisition_number} (KES {$this->requisition->total_amount}) awaiting your approval"
        ];
    }

    /**
     * Get the Slack representation of the notification.
     */
    public function toSlack($notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->success()
            ->from('Procurement Bot')
            ->content("New Requisition for Approval")
            ->attachment(function ($attachment) {
                $attachment->title("Requisition #{$this->requisition->requisition_number}")
                    ->fields([
                        'Amount' => 'KES ' . number_format($this->requisition->total_amount, 2),
                        'Department' => $this->requisition->department?->name,
                        'Requested By' => $this->requisition->creator?->name,
                        'Status' => $this->requisition->status,
                    ])
                    ->action('Review', route('requisitions.show', $this->requisition));
            });
    }

    /**
     * Get the SMS notification message.
     */
    public function toSMS($notifiable): string
    {
        return "Requisition #{$this->requisition->requisition_number} (KES {$this->requisition->total_amount}) " .
            "from {$this->requisition->department?->name} awaiting approval. " .
            "View: " . route('requisitions.show', $this->requisition);
    }
}
