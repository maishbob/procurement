<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentProcessedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['mail', 'database'];

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
            ->subject("✓ Payment Processed - Reference: {$this->payment->reference_number}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A payment has been successfully processed:")
            ->line("**Supplier:** {$this->payment->supplier?->name}")
            ->line("**Payment Reference:** {$this->payment->reference_number}")
            ->line("**Gross Amount:** KES " . number_format($this->payment->amount, 2))
            ->line("**WHT Deducted:** KES " . number_format($this->payment->withholding_tax_amount, 2))
            ->line("**Net Amount Paid:** KES " . number_format($this->payment->net_amount, 2))
            ->line("**Payment Method:** " . ucfirst(str_replace('_', ' ', $this->payment->payment_method)))
            ->line("**Payment Date:** {$this->payment->processed_date?->format('d/m/Y H:i')}")
            ->action('View Payment Details', route('payments.show', $this->payment))
            ->line('Thank you,')
            ->line('Finance Department');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'payment_processed',
            'payment_id' => $this->payment->id,
            'reference_number' => $this->payment->reference_number,
            'supplier_name' => $this->payment->supplier?->name,
            'gross_amount' => $this->payment->amount,
            'wht_amount' => $this->payment->withholding_tax_amount,
            'net_amount' => $this->payment->net_amount,
            'payment_method' => $this->payment->payment_method,
            'summary' => "Payment {$this->payment->reference_number} processed for KES {$this->payment->net_amount}"
        ];
    }

    /**
     * Get the SMS notification message.
     */
    public function toSMS($notifiable): string
    {
        return "✓ Payment {$this->payment->reference_number} to {$this->payment->supplier?->name} " .
            "for KES {$this->payment->net_amount} processed successfully.";
    }
}
