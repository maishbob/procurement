<?php

namespace App\Listeners;

use App\Events\BudgetThresholdExceededEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\BudgetThresholdExceededNotification;

class NotifyBudgetOwnerListener
{
    /**
     * Handle the event.
     */
    public function handle(BudgetThresholdExceededEvent $event): void
    {
        $budgetLine = $event->budgetLine;
        $department = $budgetLine->department;

        if (!$department) {
            return;
        }

        // Find budget owner/department head
        $budgetOwner = \App\Models\User::where('department_id', $department->id)
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['department_head', 'budget_owner', 'manager']);
            })
            ->first();

        if (!$budgetOwner) {
            return;
        }

        // Send alert notification
        dispatch(new SendEmailNotificationJob(
            $budgetOwner,
            new BudgetThresholdExceededNotification($budgetLine, $event->percentageUsed, $event->threshold)
        ));

        // Also notify finance team
        $financeUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['finance', 'finance_manager']);
        })->get();

        foreach ($financeUsers as $user) {
            dispatch(new SendEmailNotificationJob(
                $user,
                new BudgetThresholdExceededNotification($budgetLine, $event->percentageUsed, $event->threshold)
            ));
        }

        // Audit log
        \App\Core\Audit\AuditService::log(
            action: 'BUDGET_THRESHOLD_ALERT_SENT',
            status: 'success',
            model_type: 'BudgetLine',
            model_id: $budgetLine->id,
            description: "Budget threshold alert sent to {$budgetOwner->name}",
            metadata: [
                'budget_id' => $budgetLine->id,
                'percentage_used' => round($event->percentageUsed, 2),
                'threshold' => $event->threshold,
                'recipients' => [
                    'budget_owner' => $budgetOwner->email,
                    'finance_count' => $financeUsers->count(),
                ]
            ]
        );
    }
}
