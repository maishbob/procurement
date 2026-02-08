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
     * Approve a payment
     */
    public function approvePayment(Payment $payment, $user): Payment
    {
        $payment->update(['status' => 'approved', 'approved_by' => $user->id]);
        return $payment;
    }

    /**
     * Reject a payment
     */
    public function rejectPayment(Payment $payment, string $reason, $user): Payment
    {
        $payment->update(['status' => 'rejected', 'rejection_reason' => $reason, 'rejected_by' => $user->id]);
        return $payment;
    }

    /**
     * Process a payment
     */
    public function processPayment(Payment $payment, $user): Payment
    {
        $payment->update(['status' => 'processed', 'processed_by' => $user->id, 'processed_date' => now()]);
        return $payment;
    }

    /**
     * Get WHT payments
     */
    public function getWHTPayments(array $filters = []): array
    {
        return [];
    }
}
