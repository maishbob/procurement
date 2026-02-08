<?php

namespace App\Modules\Requisitions\Policies;

use App\Models\User;
use App\Modules\Requisitions\Models\Requisition;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Requisition Policy
 * 
 * Authorization rules enforcing segregation of duties
 */
class RequisitionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if user can view any requisitions
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'requisitions.view',
            'requisitions.view_all',
        ]);
    }

    /**
     * Determine if user can view the requisition
     */
    public function view(User $user, Requisition $requisition): bool
    {
        // Super admin and auditor can view all
        if ($user->hasRole(['super-admin', 'auditor'])) {
            return true;
        }

        // Users with view_all permission can view all
        if ($user->hasPermissionTo('requisitions.view_all')) {
            return true;
        }

        // Users can view requisitions in their department
        if ($user->department_id === $requisition->department_id) {
            return true;
        }

        // Users can view their own requisitions
        if ($user->id === $requisition->requested_by) {
            return true;
        }

        return false;
    }

    /**
     * Determine if user can create requisitions
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('requisitions.create') && $user->is_active;
    }

    /**
     * Determine if user can update the requisition
     */
    public function update(User $user, Requisition $requisition): bool
    {
        // Must have permission
        if (!$user->hasPermissionTo('requisitions.update')) {
            return false;
        }

        // Can only edit own requisitions
        if ($user->id !== $requisition->requested_by) {
            return false;
        }

        // Can only edit in draft state
        if (!$requisition->isEditable()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can submit requisition
     */
    public function submit(User $user, Requisition $requisition): bool
    {
        // Must have permission
        if (!$user->hasPermissionTo('requisitions.submit')) {
            return false;
        }

        // Can only submit own requisitions
        if ($user->id !== $requisition->requested_by) {
            return false;
        }

        // Must be in draft state
        if (!$requisition->canBeSubmitted()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can approve requisition
     */
    public function approve(User $user, Requisition $requisition): bool
    {
        // Must have permission
        if (!$user->hasPermissionTo('requisitions.approve')) {
            return false;
        }

        // Cannot approve own requisition (segregation of duties)
        if ($user->id === $requisition->requested_by) {
            return false;
        }

        // Cannot approve if user created the requisition in the system
        if ($user->id === $requisition->created_by) {
            return false;
        }

        // Check approval level requirements
        if ($requisition->requires_hod_approval) {
            // Must be HOD of the department
            if (!$user->isHODOf($requisition->department_id)) {
                // Unless they're Principal or Deputy Principal
                if (!$user->hasRole(['principal', 'deputy-principal'])) {
                    return false;
                }
            }
        }

        if ($requisition->requires_principal_approval) {
            // Must be Principal or above
            if (!$user->hasRole(['principal', 'super-admin'])) {
                return false;
            }
        }

        // Check approval amount limits
        if ($user->max_approval_amount !== null) {
            if ($requisition->estimated_total_base > $user->max_approval_amount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if user can reject requisition
     */
    public function reject(User $user, Requisition $requisition): bool
    {
        // Same rules as approve
        return $this->approve($user, $requisition);
    }

    /**
     * Determine if user can cancel requisition
     */
    public function cancel(User $user, Requisition $requisition): bool
    {
        // Super admin can cancel anything
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Requester can cancel their own before approval
        if ($user->id === $requisition->requested_by) {
            return in_array($requisition->status, ['draft', 'submitted']);
        }

        // HOD can cancel department requisitions
        if ($user->isHODOf($requisition->department_id)) {
            return in_array($requisition->status, ['draft', 'submitted', 'hod_review']);
        }

        return false;
    }

    /**
     * Determine if user can delete requisition
     */
    public function delete(User $user, Requisition $requisition): bool
    {
        // Only super admin can delete
        if (!$user->hasRole('super-admin')) {
            return false;
        }

        // Can only delete draft requisitions
        if ($requisition->status !== 'draft') {
            return false;
        }

        return true;
    }

    /**
     * Determine if user can export requisitions
     */
    public function export(User $user): bool
    {
        return $user->hasAnyPermission([
            'requisitions.export',
            'reports.generate',
        ]);
    }
}
