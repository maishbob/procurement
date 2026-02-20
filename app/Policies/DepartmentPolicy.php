<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepartmentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any departments.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('departments.manage');
    }

    /**
     * Determine whether the user can view the department.
     */
    public function view(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }

    /**
     * Determine whether the user can create departments.
     */
    public function create(User $user): bool
    {
        return $user->can('departments.manage');
    }

    /**
     * Determine whether the user can update the department.
     */
    public function update(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }

    /**
     * Determine whether the user can delete the department.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }

    /**
     * Determine whether the user can restore the department.
     */
    public function restore(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }

    /**
     * Determine whether the user can permanently delete the department.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return $user->can('departments.manage');
    }
}
