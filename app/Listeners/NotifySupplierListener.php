<?php

namespace App\Listeners;

use App\Events\PurchaseOrderIssuedEvent;
use App\Jobs\SendEmailNotificationJob;
use App\Notifications\PurchaseOrderIssuedNotification;

class NotifySupplierListener
{
    /**
     * Handle the event.
     */
    public function handle(PurchaseOrderIssuedEvent $event): void
    {
        $purchaseOrder = $event->purchaseOrder;
        $supplier = $purchaseOrder->supplier;

        if (!$supplier || !$supplier->email) {
            app(\App\Core\Audit\AuditService::class)->log(
                'SUPPLIER_NOTIFICATION_SKIPPED',
                'PurchaseOrder',
                $purchaseOrder->id,
                null,
                null,
                'Supplier has no email address',
                [
                    'supplier_id' => $supplier?->id,
                ]
            );
            return;
        }

        // Create a user instance for the supplier email or use admin
        $recipientUser = \App\Models\User::where('email', $supplier->email)->first()
            ?? \App\Models\User::where('role', 'admin')->first();

        if ($recipientUser) {
            dispatch(new SendEmailNotificationJob(
                $recipientUser,
                new PurchaseOrderIssuedNotification($purchaseOrder)
            ));
        }

        // Audit log
        app(\App\Core\Audit\AuditService::class)->log(
            'SUPPLIER_NOTIFIED',
            'PurchaseOrder',
            $purchaseOrder->id,
            null,
            null,
            "Notified supplier {$supplier->name} of PO {$purchaseOrder->po_number}",
            [
                'supplier_id' => $supplier->id,
                'supplier_email' => $supplier->email,
                'po_number' => $purchaseOrder->po_number,
            ]
        );
    }
}
