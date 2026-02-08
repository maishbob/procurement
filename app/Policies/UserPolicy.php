<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('users.manage');
    }

    /**
     * Determine if the user can view a specific user
     */
    public function view(User $user, User $userModel): bool
    {
        // Users can view their own profile
        if ($user->id === $userModel->id) {
            return true;
        }

        // Admins can view all users
        return $user->hasAnyRole(['admin', 'super_admin'])
            && $user->hasPermission('users.manage');
    }

    /**
     * Determine if the user can create a user (admin only)
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super_admin'])
            && $user->hasPermission('users.manage');
    }

    /**
     * Determine if the user can update a user (segregation of duties for role changes)
     */
    public function update(User $user, User $userModel): bool
    {
        // Super admin can update anyone
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Users can only update their own profile (not roles/permissions)
        if ($user->id === $userModel->id) {
            return true;
        }

        // Admin can update other users (but not super_admin users)
        if ($user->hasRole('admin')) {
            return !$userModel->hasRole('super_admin')
                && $user->hasPermission('users.manage');
        }

        return false;
    }

    /**
     * Determine if the user can update a user's roles
     */
    public function updateRoles(User $user, User $userModel): bool
    {
        // Only super_admin can assign/change roles
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can change roles except for super_admin users
        if ($user->hasRole('admin')) {
            return !$userModel->hasRole('super_admin')
                && $user->hasPermission('users.manage');
        }

        return false;
    }

    /**
     * Determine if the user can reset another user's password
     */
    public function resetPassword(User $user, User $userModel): bool
    {
        // Super admin can reset anyone's password
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can reset other users' passwords (except super_admin)
        if ($user->hasRole('admin')) {
            return !$userModel->hasRole('super_admin')
                && $user->hasPermission('users.manage');
        }

        return false;
    }

    /**
     * Determine if the user can activate/deactivate a user
     */
    public function toggleActive(User $user, User $userModel): bool
    {
        // Only super_admin can deactivate users
        if ($user->hasRole('super_admin')) {
            return true;
        }

        // Admin can toggle users (except super_admin)
        if ($user->hasRole('admin')) {
            return !$userModel->hasRole('super_admin')
                && $user->hasPermission('users.manage');
        }

        return false;
    }

    /**
     * Determine if the user can delete a user (only if no transactions)
     */
    public function delete(User $user, User $userModel): bool
    {
        // Can't delete if user has transactions
        if ($userModel->hasTransactions()) {
            return false;
        }

        return $user->hasRole('super_admin');
    }

    /**
     * Determine if the user can view user activity logs
     */
    public function viewActivityLogs(User $user, User $userModel): bool
    {
        // Users can view their own activity
        if ($user->id === $userModel->id) {
            return true;
        }

        // Only admins can view other users' activity
        return $user->hasAnyRole(['admin', 'super_admin'])
            && $user->hasPermission('audit.view');
    }

    /**
     * Determine if the user can view other user's approval limits
     */
    public function viewApprovalLimit(User $user, User $userModel): bool
    {
        // Users can view their own approval limit
        if ($user->id === $userModel->id) {
            return true;
        }

        // Only finance and admin can view approval limits
        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can update another user's approval limit
     */
    public function updateApprovalLimit(User $user, User $userModel): bool
    {
        // Only super_admin can change approval limits
        return $user->hasRole('super_admin');
    }
}
