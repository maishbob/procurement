<?php

namespace App\Listeners;

use App\Events\GoodsReceivedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\GoodsReceivedNotification;

class NotifyFinanceListener
{
    /**
     * Handle the event.
     */
    public function handle(GoodsReceivedEvent $event): void
    {
        $grn = $event->grn;

        // Find finance team members
        $financeUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['finance', 'accounts', 'finance_manager']);
        })->get();

        if ($financeUsers->isEmpty()) {
            return;
        }

        // Send notifications to all finance team members
        foreach ($financeUsers as $user) {
            dispatch(new SendEmailNotificationJob(
                $user,
                new GoodsReceivedNotification($grn)
            ));
        }

        // Audit log
        \App\Core\Audit\AuditService::log(
            action: 'FINANCE_NOTIFIED',
            status: 'success',
            model_type: 'GoodsReceivedNote',
            model_id: $grn->id,
            description: "Notified {$financeUsers->count()} finance team members of GRN {$grn->grn_number}",
            metadata: [
                'grn_id' => $grn->id,
                'finance_users_count' => $financeUsers->count(),
                'items_count' => $grn->items->count(),
            ]
        );
    }
}
