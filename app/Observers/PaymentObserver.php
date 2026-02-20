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
            'PAYMENT_CREATED',
            'Payment',
            $payment->id,
            null,
            [
                'status' => 'draft',
                'total_amount' => $payment->total_amount,
                'wht_amount' => $payment->wht_amount,
                'payment_method' => $payment->payment_method,
            ],
            "Payment created for {$payment->invoices()->count()} invoice(s) totaling KES " . number_format($payment->total_amount, 2)
        );
    }

    /**
     * Handle the Payment "submitted" event (draft → pending_approval).
     */
    public function submitted(Payment $payment): void
    {
        $this->auditService->log(
            'PAYMENT_SUBMITTED',
            'Payment',
            $payment->id,
            ['status' => ['from' => 'draft', 'to' => 'pending_approval']],
            null,
            "Payment submitted for approval. Total: KES " . number_format($payment->total_amount, 2) . ", WHT: KES " . number_format($payment->wht_amount, 2),
            [
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
            'PAYMENT_APPROVED',
            'Payment',
            $payment->id,
            ['status' => ['from' => 'pending_approval', 'to' => 'approved']],
            null,
            "Payment approved. Total: KES " . number_format($payment->total_amount, 2),
            [
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
            'PAYMENT_REJECTED',
            'Payment',
            $payment->id,
            ['status' => ['from' => 'pending_approval', 'to' => 'rejected']],
            null,
            "Payment rejected",
            [
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
            'PAYMENT_PROCESSED',
            'Payment',
            $payment->id,
            ['status' => ['from' => 'approved', 'to' => 'processed']],
            null,
            "Payment processed via {$payment->payment_method}. Total: KES " . number_format($payment->total_amount, 2),
            [
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
            'PAYMENT_RECONCILED',
            'Payment',
            $payment->id,
            ['status' => ['from' => 'processed', 'to' => 'reconciled']],
            null,
            "Payment reconciled with bank statement",
            [
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
                'PAYMENT_UPDATED',
                'Payment',
                $payment->id,
                $changes,
                null,
                "Payment updated"
            );
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        $this->auditService->log(
            'PAYMENT_DELETED',
            'Payment',
            $payment->id,
            ['deleted_by' => auth()?->id()],
            null,
            "Payment permanently deleted"
        );
    }
}
