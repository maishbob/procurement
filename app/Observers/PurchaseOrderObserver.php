<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\PurchaseOrder;

class PurchaseOrderObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the PurchaseOrder "created" event.
     */
    public function created(PurchaseOrder $po): void
    {
        $this->auditService->log(
            action: 'PURCHASE_ORDER_CREATED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $po->id,
            description: "Purchase Order #{$po->po_number} created with total amount KES " . number_format($po->total_amount, 2),
            changes: [
                'status' => 'draft',
                'supplier_id' => $po->supplier_id,
                'requisition_id' => $po->requisition_id,
                'total_amount' => $po->total_amount,
            ]
        );
    }

    /**
     * Handle the PurchaseOrder "updated" event.
     */
    public function updated(PurchaseOrder $po): void
    {
        $changes = [];
        foreach ($po->getChanges() as $key => $value) {
            $changes[$key] = [
                'from' => $po->getOriginal($key),
                'to' => $value,
            ];
        }

        $this->auditService->log(
            action: 'PURCHASE_ORDER_UPDATED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $po->id,
            description: "Purchase Order #{$po->po_number} updated",
            changes: $changes
        );
    }

    /**
     * Handle the PurchaseOrder "issued" event (draft → issued status change).
     */
    public function issued(PurchaseOrder $po): void
    {
        $this->auditService->log(
            action: 'PURCHASE_ORDER_ISSUED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $po->id,
            description: "Purchase Order #{$po->po_number} issued to supplier {$po->supplier->name}",
            changes: ['status' => ['from' => 'draft', 'to' => 'issued']],
            metadata: [
                'supplier_id' => $po->supplier_id,
                'issued_date' => now(),
            ]
        );
    }

    /**
     * Handle the PurchaseOrder "cancelled" event.
     */
    public function cancelled(PurchaseOrder $po): void
    {
        $this->auditService->log(
            action: 'PURCHASE_ORDER_CANCELLED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $po->id,
            description: "Purchase Order #{$po->po_number} cancelled",
            changes: ['status' => ['from' => $po->getOriginal('status'), 'to' => 'cancelled']],
            metadata: [
                'cancellation_reason' => $po->cancellation_reason,
                'cancelled_date' => now(),
            ]
        );
    }

    /**
     * Handle the PurchaseOrder "deleted" event.
     */
    public function deleted(PurchaseOrder $po): void
    {
        $this->auditService->log(
            action: 'PURCHASE_ORDER_DELETED',
            status: 'success',
            model_type: 'PurchaseOrder',
            model_id: $po->id,
            description: "Purchase Order #{$po->po_number} permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
