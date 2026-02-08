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
}
