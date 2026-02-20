<?php

namespace App\Listeners;

use App\Events\PaymentProcessedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Models\User;
use App\Notifications\PaymentProcessedNotification;

class NotifyPaymentPartiesListener
{
    public function handle(PaymentProcessedEvent $event): void
    {
        $payment = $event->payment;
        $notification = new PaymentProcessedNotification($payment);

        // 1. Notify Finance Manager and Accountant (processing confirmation)
        $financeUsers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Finance Manager', 'Accountant']))
            ->get();

        foreach ($financeUsers as $user) {
            dispatch(new SendEmailNotificationJob($user, $notification));
        }

        // 2. Notify the original requisitioner so they know the payment cleared
        $originatingUserId = $payment->invoice?->purchaseOrder?->requisition?->created_by
            ?? $payment->invoice?->purchaseOrder?->requisition?->requested_by
            ?? null;

        if ($originatingUserId) {
            $requester = User::active()->find($originatingUserId);
            if ($requester && !$financeUsers->contains('id', $requester->id)) {
                dispatch(new SendEmailNotificationJob($requester, $notification));
            }
        }

        app(\App\Core\Audit\AuditService::class)->log(
            action: 'PAYMENT_PROCESSED_NOTIFICATIONS_SENT',
            model: 'Payment',
            modelId: $payment->id,
            metadata: [
                'finance_count'      => $financeUsers->count(),
                'requester_notified' => isset($requester),
                'amount'             => $payment->amount,
            ],
        );
    }
}
