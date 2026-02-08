<?php

namespace App\Observers;

use App\Core\Audit\AuditService;
use App\Models\Payment;

class PaymentObserver
{
    public function __construct(private AuditService $auditService) {}

    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_CREATED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment created for {$payment->invoices()->count()} invoice(s) totaling KES " . number_format($payment->total_amount, 2),
            changes: [
                'status' => 'draft',
                'total_amount' => $payment->total_amount,
                'wht_amount' => $payment->wht_amount,
                'payment_method' => $payment->payment_method,
            ]
        );
    }

    /**
     * Handle the Payment "submitted" event (draft → pending_approval).
     */
    public function submitted(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_SUBMITTED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment submitted for approval. Total: KES " . number_format($payment->total_amount, 2) . ", WHT: KES " . number_format($payment->wht_amount, 2),
            changes: ['status' => ['from' => 'draft', 'to' => 'pending_approval']],
            metadata: [
                'submitted_by' => auth()?->id(),
                'submission_date' => now(),
            ]
        );
    }

    /**
     * Handle the Payment "approved" event (pending_approval → approved).
     */
    public function approved(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_APPROVED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment approved. Total: KES " . number_format($payment->total_amount, 2),
            changes: ['status' => ['from' => 'pending_approval', 'to' => 'approved']],
            metadata: [
                'approved_by' => auth()?->id(),
                'approval_date' => now(),
            ]
        );
    }

    /**
     * Handle the Payment "rejected" event.
     */
    public function rejected(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_REJECTED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment rejected",
            changes: ['status' => ['from' => 'pending_approval', 'to' => 'rejected']],
            metadata: [
                'rejection_reason' => $payment->rejection_reason,
                'rejected_by' => auth()?->id(),
            ]
        );
    }

    /**
     * Handle the Payment "processed" event (approved → processed).
     */
    public function processed(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_PROCESSED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment processed via {$payment->payment_method}. Total: KES " . number_format($payment->total_amount, 2),
            changes: ['status' => ['from' => 'approved', 'to' => 'processed']],
            metadata: [
                'processed_by' => auth()?->id(),
                'processing_date' => now(),
                'payment_reference' => $payment->payment_reference,
            ]
        );
    }

    /**
     * Handle the Payment "reconciled" event.
     */
    public function reconciled(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_RECONCILED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment reconciled with bank statement",
            changes: ['status' => ['from' => 'processed', 'to' => 'reconciled']],
            metadata: [
                'reconciled_by' => auth()?->id(),
                'reconciliation_date' => now(),
            ]
        );
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        $changes = [];
        foreach ($payment->getChanges() as $key => $value) {
            if (!in_array($key, ['updated_at'])) {
                $changes[$key] = ['from' => $payment->getOriginal($key), 'to' => $value];
            }
        }

        if (!empty($changes)) {
            $this->auditService->log(
                action: 'PAYMENT_UPDATED',
                status: 'success',
                model_type: 'Payment',
                model_id: $payment->id,
                description: "Payment updated",
                changes: $changes
            );
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->auditService->log(
            action: 'PAYMENT_DELETED',
            status: 'success',
            model_type: 'Payment',
            model_id: $payment->id,
            description: "Payment permanently deleted",
            changes: ['deleted_by' => auth()?->id()]
        );
    }
}
