<?php

namespace App\Policies;

use App\Models\ProcurementProcess;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProcurementProcessPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProcurementProcess $procurementProcess): bool
    {
        return $user->can('procurement.view');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('procurement.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProcurementProcess $procurementProcess): bool
    {
        // Only draft processes can be edited
        if ($procurementProcess->status !== 'draft') {
            return false;
        }
        return $user->can('procurement.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProcurementProcess $procurementProcess): bool
    {
        // Only draft processes can be deleted
        if ($procurementProcess->status !== 'draft') {
            return false;
        }
        return $user->can('procurement.edit'); // Using edit permission for delete as well for now
    }

    /**
     * Determine whether the user can publish the model.
     */
    public function publish(User $user, ProcurementProcess $procurementProcess): bool
    {
        return $user->can('procurement.issue-rfq');
    }

    /**
     * Determine whether the user can evaluate bids.
     */
    public function evaluate(User $user, ProcurementProcess $procurementProcess): bool
    {
        return $user->can('procurement.evaluate-bids');
    }

    /**
     * Determine whether the user can award the contract.
     */
    public function award(User $user, ProcurementProcess $procurementProcess): bool
    {
        return $user->can('procurement.recommend-award') || $user->can('procurement.approve-award');
    }
}
