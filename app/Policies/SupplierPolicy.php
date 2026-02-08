<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Determine if the user can view any suppliers
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('suppliers.view');
    }

    /**
     * Determine if the user can view a specific supplier
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.view');
    }

    /**
     * Determine if the user can create a supplier
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('suppliers.create');
    }

    /**
     * Determine if the user can update a supplier
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.edit');
    }

    /**
     * Determine if the user can delete a supplier
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.delete');
    }

    /**
     * Determine if the user can restore a supplier
     */
    public function restore(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.edit');
    }

    /**
     * Determine if the user can blacklist a supplier
     */
    public function blacklist(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.blacklist');
    }

    /**
     * Determine if the user can unblacklist a supplier
     */
    public function unblacklist(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.blacklist');
    }

    /**
     * Determine if the user can view supplier documents
     */
    public function viewDocuments(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.view');
    }

    /**
     * Determine if the user can upload supplier documents
     */
    public function uploadDocuments(User $user, Supplier $supplier): bool
    {
        return $user->hasPermission('suppliers.edit');
    }
}
