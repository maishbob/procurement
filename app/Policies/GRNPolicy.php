<?php

namespace App\Policies;

use App\Modules\GRN\Models\GoodsReceivedNote;
use App\Models\User;

class GRNPolicy
{
    /**
     * Determine if the user can view any GRNs
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('grn.view');
    }

    /**
     * Determine if the user can view a specific GRN
     */
    public function view(User $user, GoodsReceivedNote $grn): bool
    {
        // Procurement, store, and finance can view all GRNs
        if ($user->hasAnyRole(['Procurement Officer', 'Stores Manager', 'Finance Manager', 'admin', 'Super Administrator'])) {
            return true;
        }

        // Requisitioners can view GRNs for their requisitions
        if ($user->hasRole('requisitioner')) {
            return $grn->purchaseOrder()->first()?->requisition?->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine if the user can create a GRN (receiving goods)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Stores Manager', 'Procurement Officer', 'admin', 'Super Administrator'])
            && $user->hasPermission('grn.create');
    }

    /**
     * Determine if the user can update a pending GRN (before inspection)
     */
    public function update(User $user, GoodsReceivedNote $grn): bool
    {
        // Can only update GRNs in pending_inspection status
        if ($grn->status !== 'pending_inspection') {
            return false;
        }

        return $user->hasAnyRole(['Stores Manager', 'Procurement Officer', 'admin', 'Super Administrator']);
    }

    /**
     * Determine if the user can record inspection (pending_inspection → inspected)
     */
    public function recordInspection(User $user, GoodsReceivedNote $grn): bool
    {
        if ($grn->status !== 'pending_inspection') {
            return false;
        }

        // Only quality inspectors and above can record inspections
        return $user->hasAnyRole(['Stores Manager', 'Procurement Officer', 'admin', 'Super Administrator'])
            && $user->hasPermission('grn.inspect');
    }

    /**
     * Determine if the user can post GRN to inventory (inspected → posted)
     */
    public function postToInventory(User $user, GoodsReceivedNote $grn): bool
    {
        // Can only post inspected GRNs
        if ($grn->status !== 'inspected' && $grn->status !== 'inspection_complete') {
            return false;
        }

        return $user->hasAnyRole(['Stores Manager', 'admin', 'Super Administrator'])
            && $user->hasPermission('grn.post');
    }

    /**
     * Determine if the user can record discrepancies
     */
    public function recordDiscrepancies(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->hasAnyRole(['Stores Manager', 'Stores Manager', 'Procurement Officer', 'admin', 'Super Administrator'])
            && in_array($grn->status, ['pending_inspection', 'inspected', 'inspection_complete']);
    }

    /**
     * Determine if the user can delete a GRN (only draft/received, no transactions)
     */
    public function delete(User $user, GoodsReceivedNote $grn): bool
    {
        // Can't delete if already posted to inventory or invoiced
        if ($grn->status === 'posted' || $grn->invoices()->exists()) {
            return false;
        }

        return $user->hasRole('Super Administrator');
    }

    /**
     * Determine if the user can view GRN discrepancies
     */
    public function viewDiscrepancies(User $user, GoodsReceivedNote $grn): bool
    {
        return $user->hasAnyRole(['Stores Manager', 'Stores Manager', 'Procurement Officer', 'Finance Manager', 'admin', 'Super Administrator']);
    }
}
