<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryPolicy
{
    /**
     * Determine if the user can view any inventory items
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('inventory.view');
    }

    /**
     * Determine if the user can view a specific inventory item
     */
    public function view(User $user, InventoryItem $item): bool
    {
        // Store managers, finance, procurement, and admin can view all items
        return $user->hasAnyRole(['store_manager', 'finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can create inventory items
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('inventory.manage');
    }

    /**
     * Determine if the user can update inventory items
     */
    public function update(User $user, InventoryItem $item): bool
    {
        return $user->hasPermission('inventory.manage');
    }

    /**
     * Determine if the user can delete inventory items
     */
    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->hasPermission('inventory.manage');
    }

    /**
     * Determine if the user can adjust inventory (quantity adjustments)
     */
    public function adjust(User $user, InventoryItem $item): bool
    {
        // Store managers and admins can adjust inventory
        return $user->hasAnyRole(['store_manager', 'admin', 'super_admin'])
            && $user->hasPermission('inventory.adjust');
    }

    /**
     * Determine if the user can issue items from inventory
     */
    public function issue(User $user, InventoryItem $item): bool
    {
        // Store managers, requisitioners and admins can issue items
        return $user->hasAnyRole(['store_manager', 'requisitioner', 'admin', 'super_admin'])
            && $user->hasPermission('inventory.issue');
    }

    /**
     * Determine if the user can transfer items between stores
     */
    public function transfer(User $user, InventoryItem $item): bool
    {
        // Store managers and admins can transfer items
        return $user->hasAnyRole(['store_manager', 'admin', 'super_admin'])
            && $user->hasPermission('inventory.transfer');
    }

    /**
     * Determine if the user can view inventory reports
     */
    public function viewReports(User $user): bool
    {
        return $user->hasAnyRole(['store_manager', 'finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can view low stock alerts
     */
    public function viewLowStockAlerts(User $user): bool
    {
        return $user->hasAnyRole(['store_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can export inventory data
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can view asset register
     */
    public function viewAssetRegister(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }
}
