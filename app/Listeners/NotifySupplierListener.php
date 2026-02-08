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
            \App\Core\Audit\AuditService::log(
                action: 'SUPPLIER_NOTIFICATION_SKIPPED',
                status: 'warning',
                model_type: 'PurchaseOrder',
                model_id: $purchaseOrder->id,
                description: 'Supplier has no email address',
                metadata: [
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
        \App\Core\Audit\AuditService::log(
            action: 'SUPPLIER_NOTIFIED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $purchaseOrder->id,
            description: "Notified supplier {$supplier->name} of PO {$purchaseOrder->po_number}",
            metadata: [
                'supplier_id' => $supplier->id,
                'supplier_email' => $supplier->email,
                'po_number' => $purchaseOrder->po_number,
            ]
        );
    }
}
