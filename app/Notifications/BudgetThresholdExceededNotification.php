<?php

namespace App\Notifications;

use App\Models\BudgetLine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetThresholdExceededNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected BudgetLine $budgetLine,
        protected float $percentageUsed,
        protected string $threshold = '80%'
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $remaining = $this->budgetLine->amount_allocated - $this->budgetLine->amount_executed;
        $department = $this->budgetLine->department;

        return (new MailMessage)
            ->subject("⚠ Budget Alert - {$department?->name} {$this->budgetLine->description}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A department budget has exceeded the {$this->threshold} threshold:")
            ->line("**Department:** {$department?->name}")
            ->line("**Budget:** {$this->budgetLine->description}")
            ->line("**Allocated:** KES " . number_format($this->budgetLine->amount_allocated, 2))
            ->line("**Committed:** KES " . number_format($this->budgetLine->amount_committed, 2))
            ->line("**Executed:** KES " . number_format($this->budgetLine->amount_executed, 2))
            ->line("**Remaining:** KES " . number_format($remaining, 2))
            ->line("**Usage:** " . round($this->percentageUsed, 1) . "% of allocated")
            ->action('View Budget Details', route('budgets.show', $this->budgetLine))
            ->line('Please review budget status and plan accordingly.')
            ->line('Thank you,')
            ->line('Finance System');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'budget_threshold_exceeded',
            'budget_line_id' => $this->budgetLine->id,
            'department_name' => $this->budgetLine->department?->name,
            'budget_description' => $this->budgetLine->description,
            'amount_allocated' => $this->budgetLine->amount_allocated,
            'amount_executed' => $this->budgetLine->amount_executed,
            'percentage_used' => round($this->percentageUsed, 2),
            'threshold' => $this->threshold,
            'summary' => "{$this->budgetLine->department?->name} budget {$this->percentageUsed}% used (threshold: {$this->threshold})"
        ];
    }
}
