<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentService
{
    /**
     * Get all payments with filters
     */
    public function getAllPayments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Payment::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['invoice_id']) && $filters['invoice_id']) {
            $query->where('invoice_id', $filters['invoice_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->where('reference_number', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new payment
     */
    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    /**
     * Get a specific payment
     */
    public function getById(int $id): Payment
    {
        return Payment::findOrFail($id);
    }

    /**
     * Update a payment
     */
    public function update(int $id, array $data): Payment
    {
        $payment = $this->getById($id);
        $payment->update($data);
        return $payment;
    }

    /**
     * Delete a payment
     */
    public function delete(int $id): bool
    {
        $payment = $this->getById($id);
        return $payment->delete();
    }

    /**
     * Create a new payment
     */
    public function createPayment(array $data, $user): Payment
    {
        $data['created_by'] = $user->id;
        $data['status'] = 'draft';
        return Payment::create($data);
    }

    /**
     * Update a payment
     */
    public function updatePayment(Payment $payment, array $data): Payment
    {
        $payment->update($data);
        return $payment;
    }

    /**
     * Submit a payment
     */
    public function submitPayment(Payment $payment): Payment
    {
        $payment->update(['status' => 'submitted']);
        return $payment;
    }

    /**
     * Approve a payment (approver must differ from submitter — enforced by PaymentPolicy)
     */
    public function approvePayment(Payment $payment, $user, string $notes = ''): Payment
    {
        $payment->update([
            'status'         => 'approved',
            'approved_by'    => $user->id,
            'approved_at'    => now(),
            'approval_notes' => $notes,
        ]);
        return $payment;
    }

    /**
     * Reject a payment (rejector must differ from submitter — enforced by PaymentPolicy)
     */
    public function rejectPayment(Payment $payment, $user, string $reason): Payment
    {
        $payment->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
            'rejected_by'      => $user->id,
            'rejected_at'      => now(),
        ]);
        return $payment;
    }

    /**
     * Process a payment (processor must differ from submitter & approver — enforced by PaymentPolicy)
     */
    public function processPayment(Payment $payment, $user, string $referenceNumber = '', string $notes = ''): Payment
    {
        $payment->update([
            'status'           => 'processed',
            'processed_by'     => $user->id,
            'processed_date'   => now(),
            'reference_number' => $referenceNumber ?: $payment->reference_number,
            'processing_notes' => $notes,
        ]);
        return $payment;
    }

    /**
     * Get WHT payments for a period
     */
    public function getWHTPayments(array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Payment::where('withholding_tax_amount', '>', 0)
            ->with(['invoices.supplier']);

        if (!empty($filters['date_from'])) {
            $query->whereDate('paid_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('paid_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('paid_at', 'desc')->get();
    }
}
