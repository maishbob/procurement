<?php

namespace App\Notifications;

use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class RequisitionApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Requisition $requisition,
        protected string $approvalLevel = '',
        protected string $approverName = ''
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
            ->subject("✓ Requisition #{$this->requisition->requisition_number} Approved")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your requisition has been approved:")
            ->line("**Requisition #:** {$this->requisition->requisition_number}")
            ->line("**Amount:** KES " . number_format($this->requisition->total_amount, 2))
            ->line("**Approval Level:** {$this->approvalLevel}")
            ->line("**Approved By:** {$this->approverName}")
            ->line("**Status:** " . ucfirst($this->requisition->status))
            ->action('View Requisition', route('requisitions.show', $this->requisition))
            ->line('Thank you,')
            ->line('Procurement System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'requisition_approved',
            'requisition_id' => $this->requisition->id,
            'requisition_number' => $this->requisition->requisition_number,
            'amount' => $this->requisition->total_amount,
            'approval_level' => $this->approvalLevel,
            'approver_name' => $this->approverName,
            'summary' => "Requisition #{$this->requisition->requisition_number} approved by {$this->approverName}"
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
            ->content("Requisition Approved ✓")
            ->attachment(function ($attachment) {
                $attachment->title("Requisition #{$this->requisition->requisition_number}")
                    ->fields([
                        'Amount' => 'KES ' . number_format($this->requisition->total_amount, 2),
                        'Approval Level' => $this->approvalLevel,
                        'Approved By' => $this->approverName,
                        'Status' => $this->requisition->status,
                    ])
                    ->action('View', route('requisitions.show', $this->requisition));
            });
    }

    /**
     * Get the SMS notification message.
     */
    public function toSMS($notifiable): string
    {
        return "✓ Requisition #{$this->requisition->requisition_number} approved by " .
            "{$this->approverName} at {$this->approvalLevel}. Status: {$this->requisition->status}";
    }
}
