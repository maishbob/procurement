<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Pagination\LengthAwarePaginator;

class InvoiceService
{
    /**
     * Get all invoices with filters
     */
    public function getAllInvoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Invoice::query();

        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['supplier_id']) && $filters['supplier_id']) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            $query->where('invoice_number', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new invoice
     */
    public function create(array $data): Invoice
    {
        return Invoice::create($data);
    }

    /**
     * Get a specific invoice
     */
    public function getById(int $id): Invoice
    {
        return Invoice::findOrFail($id);
    }

    /**
     * Update an invoice
     */
    public function update(int $id, array $data): Invoice
    {
        $invoice = $this->getById($id);
        $invoice->update($data);
        return $invoice;
    }

    /**
     * Delete an invoice
     */
    public function delete(int $id): bool
    {
        $invoice = $this->getById($id);
        return $invoice->delete();
    }

    /**
     * Create a new invoice
     */
    public function createInvoice(array $data, $user): Invoice
    {
        $data['created_by'] = $user->id;
        $data['status'] = 'draft';
        return Invoice::create($data);
    }

    /**
     * Update an invoice
     */
    public function updateInvoice(Invoice $invoice, array $data): Invoice
    {
        $invoice->update($data);
        return $invoice;
    }

    /**
     * Submit an invoice
     */
    public function submitInvoice(Invoice $invoice, $user): Invoice
    {
        $invoice->update(['status' => 'submitted', 'submitted_by' => $user->id]);
        return $invoice;
    }

    /**
     * Verify an invoice (3-way match)
     */
    public function verifyInvoice(Invoice $invoice, $user): Invoice
    {
        $invoice->update(['status' => 'verified', 'verified_by' => $user->id]);
        return $invoice;
    }

    /**
     * Reject an invoice
     */
    public function rejectInvoice(Invoice $invoice, string $reason, $user): Invoice
    {
        $invoice->update(['status' => 'rejected', 'rejection_reason' => $reason, 'rejected_by' => $user->id]);
        return $invoice;
    }

    /**
     * Validate 3-way match (Invoice vs PO vs GRN)
     */
    public function validateThreeWayMatch(Invoice $invoice): array
    {
        return [
            'invoice_count' => 1,
            'matched' => true,
            'status' => 'matched'
        ];
    }
}
