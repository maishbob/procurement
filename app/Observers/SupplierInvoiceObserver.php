<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\SupplierInvoice;

class SupplierInvoiceObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the SupplierInvoice "created" event.
     */
    public function created(SupplierInvoice $invoice): void
    {
        $this->auditService->log(
            action: 'INVOICE_CREATED',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} created from supplier {$invoice->supplier->name}",
            changes: [
                'status' => 'draft',
                'supplier_id' => $invoice->supplier_id,
                'purchase_order_id' => $invoice->purchase_order_id,
                'invoice_amount' => $invoice->total_amount,
                'invoice_date' => $invoice->invoice_date,
            ]
        );
    }

    /**
     * Handle the SupplierInvoice "verified" event (three-way match).
     */
    public function verified(SupplierInvoice $invoice): void
    {
        $this->auditService->log(
            action: 'INVOICE_THREE_WAY_MATCH_VERIFIED',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} passed three-way match verification",
            changes: ['status' => ['from' => 'submitted', 'to' => 'verified']],
            metadata: [
                'verified_by' => auth()?->id(),
                'verification_date' => now(),
                'variance_tolerance' => config('procurement.variance_tolerance'),
            ]
        );
    }

    /**
     * Handle the SupplierInvoice "approved" event (finance approval).
     */
    public function approved(SupplierInvoice $invoice): void
    {
        $this->auditService->log(
            action: 'INVOICE_APPROVED',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} for KES " . number_format($invoice->total_amount, 2) . " approved by finance",
            changes: ['status' => ['from' => 'verified', 'to' => 'approved']],
            metadata: [
                'approved_by' => auth()?->id(),
                'approval_date' => now(),
            ]
        );
    }

    /**
     * Handle the SupplierInvoice "rejected" event.
     */
    public function rejected(SupplierInvoice $invoice): void
    {
        $this->auditService->log(
            action: 'INVOICE_REJECTED',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} rejected",
            changes: ['status' => ['from' => 'submitted', 'to' => 'rejected']],
            metadata: [
                'rejection_reason' => $invoice->rejection_reason,
                'rejected_by' => auth()?->id(),
            ]
        );
    }

    /**
     * Handle the SupplierInvoice "updated" event.
     */
    public function updated(SupplierInvoice $invoice): void
    {
        $changes = [];
        foreach ($invoice->getChanges() as $key => $value) {
            if (!in_array($key, ['updated_at'])) {
                $changes[$key] = ['from' => $invoice->getOriginal($key), 'to' => $value];
            }
        }

        if (!empty($changes)) {
            $this->auditService->log(
                action: 'INVOICE_UPDATED',
                status: 'success',
                model_type: 'SupplierInvoice',
                model_id: $invoice->id,
                description: "Invoice #{$invoice->invoice_number} updated",
                changes: $changes
            );
        }
    }

    /**
     * Handle the SupplierInvoice "deleted" event.
     */
    public function deleted(SupplierInvoice $invoice): void
    {
        $this->auditService->log(
            action: 'INVOICE_DELETED',
            status: 'success',
            model_type: 'SupplierInvoice',
            model_id: $invoice->id,
            description: "Invoice #{$invoice->invoice_number} permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
