<?php

namespace App\Listeners;

use App\Events\GoodsReceivedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\GoodsReceivedNotification;

class NotifyFinanceListener
{
    public function handle(GoodsReceivedEvent $event): void
    {
        $grn = $event->grn;

        // Finance Manager and Accountant need to know about received goods to process invoices
        $financeUsers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Finance Manager', 'Accountant']))
            ->get();

        if ($financeUsers->isEmpty()) {
            return;
        }

        foreach ($financeUsers as $user) {
            dispatch(new SendEmailNotificationJob(
                $user,
                new GoodsReceivedNotification($grn)
            ));
        }

        app(\App\Core\Audit\AuditService::class)->log(
            action: 'FINANCE_NOTIFIED_GRN',
            model: 'GoodsReceivedNote',
            modelId: $grn->id,
            metadata: [
                'grn_number'         => $grn->grn_number,
                'finance_user_count' => $financeUsers->count(),
                'items_count'        => $grn->items->count(),
            ],
        );
    }
}
