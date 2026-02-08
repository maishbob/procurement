<?php

namespace App\Policies;

use App\Models\SupplierInvoice;
use App\Models\User;

class InvoicePolicy
{
    /**
     * Determine if the user can view any invoices
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('invoices.view');
    }

    /**
     * Determine if the user can view a specific invoice
     */
    public function view(User $user, SupplierInvoice $invoice): bool
    {
        // Finance, procurement, and admin can view all invoices
        if ($user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin'])) {
            return true;
        }

        // Requisitioners can only view invoices for their requisitions
        if ($user->hasRole('requisitioner')) {
            return $invoice->purchaseOrder()->first()?->requisition?->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create an invoice (from GRN)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin'])
            && $user->hasPermission('invoices.create');
    }

    /**
     * Determine if the user can update a draft invoice
     */
    public function update(User $user, SupplierInvoice $invoice): bool
    {
        // Can only update draft invoices
        if ($invoice->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can submit an invoice (draft → submitted)
     */
    public function submit(User $user, SupplierInvoice $invoice): bool
    {
        if ($invoice->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can verify three-way match (submitted → verified)
     */
    public function verify(User $user, SupplierInvoice $invoice): bool
    {
        // Only finance can verify invoices
        if ($invoice->status !== 'submitted') {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])
            && $user->hasPermission('invoices.verify');
    }

    /**
     * Determine if the user can reject an invoice (submitted → rejected)
     */
    public function reject(User $user, SupplierInvoice $invoice): bool
    {
        if (!in_array($invoice->status, ['submitted', 'verified'])) {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can approve an invoice (verified → approved)
     * Requires segregation of duties: verifier ≠ approver
     */
    public function approve(User $user, SupplierInvoice $invoice): bool
    {
        // Can only approve verified invoices
        if ($invoice->status !== 'verified') {
            return false;
        }

        // Current user must be different from verifier (segregation of duties)
        if ($invoice->verified_by === $user->id) {
            return false;
        }

        // Check approval authority (based on invoice amount)
        $userApprovalLimit = $user->approval_limit ?? 0;
        if ($invoice->total_amount > $userApprovalLimit && !$user->hasRole('super_admin')) {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])
            && $user->hasPermission('invoices.approve');
    }

    /**
     * Determine if the user can record invoice as paid (approved → paid)
     */
    public function recordAsPaid(User $user, SupplierInvoice $invoice): bool
    {
        if ($invoice->status !== 'approved') {
            return false;
        }

        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can delete a draft invoice
     */
    public function delete(User $user, SupplierInvoice $invoice): bool
    {
        // Can only delete draft invoices with no transactions
        if ($invoice->status !== 'draft' || $invoice->payments()->exists()) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can view three-way match details
     */
    public function viewthreeWayMatch(User $user, SupplierInvoice $invoice): bool
    {
        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can upload invoice attachments
     */
    public function uploadAttachments(User $user, SupplierInvoice $invoice): bool
    {
        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin'])
            && in_array($invoice->status, ['draft', 'submitted']);
    }
}
