<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        return $user->organization_id === $department->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        $organization = $user->organization;

        if (! $organization->hasTeamFeatures()) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        if ($user->organization_id !== $department->organization_id) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        if ($user->organization_id !== $department->organization_id) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value]);
    }

    /**
     * Determine whether the user can manage department members.
     */
    public function manageMembers(User $user, Department $department): bool
    {
        if ($user->organization_id !== $department->organization_id) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value, Role::Manager->value]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Department $department): bool
    {
        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value])
            && $user->organization_id === $department->organization_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
