<?php

namespace App\Policies;

use App\Models\Requisition;
use App\Models\User;

class RequisitionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Requisition $requisition): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Requisition $requisition): bool
    {
        return true;
    }

    public function delete(User $user, Requisition $requisition): bool
    {
        return true;
    }

    public function restore(User $user, Requisition $requisition): bool
    {
        return true;
    }

    public function forceDelete(User $user, Requisition $requisition): bool
    {
        return true;
    }

    /**
     * Requester can submit their own draft requisitions.
     */
    public function submit(User $user, Requisition $requisition): bool
    {
        return $user->id === $requisition->requested_by
            && $requisition->status === 'draft';
    }

    /**
     * HOD can approve department requisitions they did not create.
     * Principal / super-admin can approve anything.
     */
    public function approve(User $user, Requisition $requisition): bool
    {
        // Segregation of duties — requester cannot approve their own
        if ($user->id === $requisition->requested_by) {
            return false;
        }

        // HOD approves department requisitions awaiting HOD sign-off
        if (
            $user->hasPermissionTo('requisitions.approve-hod')
            && $user->department_id === $requisition->department_id
            && in_array($requisition->status, ['submitted', 'hod_review'])
        ) {
            return true;
        }

        // Principal / super-admin can approve at their level
        if (
            $user->hasRole(['principal', 'super-admin'])
            && $user->hasPermissionTo('requisitions.approve-principal')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Same actors who can approve can also reject.
     */
    public function reject(User $user, Requisition $requisition): bool
    {
        return $this->approve($user, $requisition);
    }

    /**
     * Requester can cancel their own requisition while it is still early in
     * the workflow; HOD can cancel department requisitions in HOD-review stage.
     */
    public function cancel(User $user, Requisition $requisition): bool
    {
        if (
            $user->id === $requisition->requested_by
            && in_array($requisition->status, ['draft', 'submitted'])
        ) {
            return true;
        }

        if (
            $user->hasRole('hod')
            && $user->department_id === $requisition->department_id
            && in_array($requisition->status, ['submitted', 'hod_review'])
        ) {
            return true;
        }

        return false;
    }
}
