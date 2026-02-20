<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Quality\Models\CapaAction;

class CapaPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // all authenticated users can list CAPAs
    }

    public function view(User $user, CapaAction $capa): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            'super-admin', 'procurement-officer', 'stores-manager',
            'hod', 'finance-manager', 'principal', 'auditor',
        ]);
    }

    public function update(User $user, CapaAction $capa): bool
    {
        // Assigned user or privileged roles
        if ($capa->assigned_to === $user->id) {
            return true;
        }

        return $user->hasAnyRole(['super-admin', 'procurement-officer', 'stores-manager', 'hod']);
    }

    public function submit(User $user, CapaAction $capa): bool
    {
        return $this->update($user, $capa);
    }

    public function approve(User $user, CapaAction $capa): bool
    {
        return $user->hasAnyRole(['super-admin', 'principal', 'finance-manager']);
    }

    public function verify(User $user, CapaAction $capa): bool
    {
        return $user->hasAnyRole(['super-admin', 'procurement-officer', 'stores-manager', 'auditor']);
    }

    public function delete(User $user, CapaAction $capa): bool
    {
        return $user->hasRole('super-admin');
    }
}
