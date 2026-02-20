<?php

namespace App\Listeners;

use App\Events\BudgetThresholdExceededEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\BudgetThresholdExceededNotification;

class NotifyBudgetOwnerListener
{
    public function handle(BudgetThresholdExceededEvent $event): void
    {
        $budgetLine = $event->budgetLine;
        $department = $budgetLine->department;

        // Find Budget Owner or HOD for this department
        $budgetOwnerQuery = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Budget Owner', 'Head of Department']));

        if ($department) {
            $budgetOwner = $budgetOwnerQuery->where('department_id', $department->id)->first();
        }

        // If no department-specific owner, pick any Budget Owner
        if (empty($budgetOwner)) {
            $budgetOwner = $budgetOwnerQuery->first();
        }

        $notification = new BudgetThresholdExceededNotification($budgetLine, $event->percentageUsed, $event->threshold);

        if ($budgetOwner) {
            dispatch(new SendEmailNotificationJob($budgetOwner, $notification));
        }

        // Also notify Finance Manager and Principal so they have visibility
        $financeAndPrincipal = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Finance Manager', 'Principal', 'Super Administrator']))
            ->get();

        foreach ($financeAndPrincipal as $user) {
            if (!$budgetOwner || $user->id !== $budgetOwner->id) {
                dispatch(new SendEmailNotificationJob($user, $notification));
            }
        }

        app(\App\Core\Audit\AuditService::class)->log(
            action: 'BUDGET_THRESHOLD_ALERT_SENT',
            model: 'BudgetLine',
            modelId: $budgetLine->id,
            metadata: [
                'percentage_used'     => round($event->percentageUsed, 2),
                'threshold'           => $event->threshold,
                'budget_owner_email'  => $budgetOwner?->email,
                'finance_notified'    => $financeAndPrincipal->count(),
            ],
        );
    }
}
