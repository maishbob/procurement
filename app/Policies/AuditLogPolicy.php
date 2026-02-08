<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine if the user can view any audit logs (view-only access)
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    /**
     * Determine if the user can view a specific audit log
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        // Finance and admin can view all audit logs
        if ($user->hasAnyRole(['finance_manager', 'admin', 'super_admin'])) {
            return true;
        }

        // Department heads can view audit logs for their department activities
        if ($user->hasRole('department_head')) {
            return $auditLog->related_department_id === $user->department_id;
        }

        return false;
    }

    /**
     * Determine if the user can export audit logs (admin only)
     */
    public function export(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'admin', 'super_admin']);
    }

    /**
     * Determine if the user can filter audit logs by user/action/date
     */
    public function filter(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }

    /**
     * Determine if the user can view user's activity logs specifically
     */
    public function viewUserActivity(User $user, User $targetUser): bool
    {
        // Users can view their own activity
        if ($user->id === $targetUser->id) {
            return true;
        }

        // Only admins can view other users' activity
        return $user->hasAnyRole(['admin', 'super_admin'])
            && $user->hasPermission('audit.view');
    }

    /**
     * Determine if the user can view transaction audit trail (immutable)
     */
    public function viewTransactionTrail(User $user): bool
    {
        return $user->hasAnyRole(['finance_manager', 'procurement_officer', 'admin', 'super_admin']);
    }

    /**
     * Audit logs cannot be created, updated, or deleted manually (immutable)
     * They are only created via observers
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Audit logs cannot be updated
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false;
    }

    /**
     * Audit logs cannot be deleted
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false;
    }
}
