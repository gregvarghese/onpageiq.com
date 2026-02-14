<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
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
    public function view(User $user, Organization $organization): bool
    {
        return $user->organization_id === $organization->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool
    {
        if ($user->organization_id !== $organization->id) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool
    {
        if ($user->organization_id !== $organization->id) {
            return false;
        }

        return $user->hasRole(Role::Owner->value);
    }

    /**
     * Determine whether the user can manage team members.
     */
    public function manageMembers(User $user, Organization $organization): bool
    {
        if ($user->organization_id !== $organization->id) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value, Role::Manager->value]);
    }

    /**
     * Determine whether the user can invite new members.
     */
    public function inviteMembers(User $user, Organization $organization): bool
    {
        if ($user->organization_id !== $organization->id) {
            return false;
        }

        if (! $organization->hasTeamFeatures()) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value]);
    }

    /**
     * Determine whether the user can manage billing.
     */
    public function manageBilling(User $user, Organization $organization): bool
    {
        if ($user->organization_id !== $organization->id) {
            return false;
        }

        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool
    {
        return $user->hasRole(Role::Owner->value) && $user->organization_id === $organization->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }
}
