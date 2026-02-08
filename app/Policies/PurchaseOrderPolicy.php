<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    /**
     * Determine if the user can view any purchase orders
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('purchase-orders.view');
    }

    /**
     * Determine if the user can view a specific purchase order
     */
    public function view(User $user, PurchaseOrder $po): bool
    {
        // Procurement, finance, and admin can view all POs
        if ($user->hasAnyRole(['procurement_officer', 'finance_manager', 'admin', 'super_admin'])) {
            return true;
        }

        // Requisitioners can view POs they created
        if ($user->hasRole('requisitioner') && $po->requester_id === $user->id) {
            return true;
        }

        // Store managers can view POs
        if ($user->hasRole('store_manager')) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can create a purchase order from requisition
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['procurement_officer', 'admin', 'super_admin'])
            && $user->hasPermission('purchase-orders.create');
    }

    /**
     * Determine if the user can update a draft purchase order
     */
    public function update(User $user, PurchaseOrder $po): bool
    {
        // Can only update draft POs
        if ($po->status !== 'draft') {
            return false;
        }

        return $user->hasAnyRole(['procurement_officer', 'admin', 'super_admin'])
            && $user->hasPermission('purchase-orders.edit');
    }

    /**
     * Determine if the user can issue a purchase order (draft → issued)
     */
    public function issue(User $user, PurchaseOrder $po): bool
    {
        // Can only issue draft POs
        if ($po->status !== 'draft') {
            return false;
        }

        // Check approval authority (based on PO amount and user's approval limit)
        $userApprovalLimit = $user->approval_limit ?? 0;
        if ($po->total_amount > $userApprovalLimit && !$user->hasAnyRole(['admin', 'super_admin'])) {
            return false;
        }

        return $user->hasAnyRole(['procurement_officer', 'admin', 'super_admin'])
            && $user->hasPermission('purchase-orders.issue');
    }

    /**
     * Determine if the user can cancel a purchase order
     */
    public function cancel(User $user, PurchaseOrder $po): bool
    {
        // Can't cancel already received or cancelled POs
        if (in_array($po->status, ['received', 'cancelled', 'closed'])) {
            return false;
        }

        // Check approval authority
        $userApprovalLimit = $user->approval_limit ?? 0;
        if ($po->total_amount > $userApprovalLimit && !$user->hasAnyRole(['admin', 'super_admin'])) {
            return false;
        }

        return $user->hasAnyRole(['procurement_officer', 'admin', 'super_admin'])
            && $user->hasPermission('purchase-orders.cancel');
    }

    /**
     * Determine if the user can acknowledge a purchase order (issued → acknowledged)
     */
    public function acknowledge(User $user, PurchaseOrder $po): bool
    {
        // Only suppliers/store managers can acknowledge (via store_id matching)
        if ($po->status !== 'issued') {
            return false;
        }

        return $user->hasAnyRole(['store_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can view PO receives (GRN creation)
     */
    public function viewReceives(User $user, PurchaseOrder $po): bool
    {
        return $user->hasAnyRole(['procurement_officer', 'store_manager', 'finance_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can download PO as PDF
     */
    public function downloadPDF(User $user, PurchaseOrder $po): bool
    {
        return $this->view($user, $po);
    }

    /**
     * Determine if the user can email PO to supplier
     */
    public function emailSupplier(User $user, PurchaseOrder $po): bool
    {
        return $user->hasAnyRole(['procurement_officer', 'admin', 'super_admin'])
            && in_array($po->status, ['draft', 'issued']);
    }
}
