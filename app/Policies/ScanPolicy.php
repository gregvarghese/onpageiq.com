<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Scan;
use App\Models\User;

class ScanPolicy
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
    public function view(User $user, Scan $scan): bool
    {
        return $user->organization_id === $scan->url->project->organization_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check if user has permission and organization has credits
        if (! $user->hasAnyRole([
            Role::Owner->value,
            Role::Admin->value,
            Role::Manager->value,
            Role::Member->value,
        ])) {
            return false;
        }

        return $user->organization->hasCredits();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Scan $scan): bool
    {
        if ($user->organization_id !== $scan->url->project->organization_id) {
            return false;
        }

        return $user->hasAnyRole([
            Role::Owner->value,
            Role::Admin->value,
            Role::Manager->value,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Scan $scan): bool
    {
        if ($user->organization_id !== $scan->url->project->organization_id) {
            return false;
        }

        return $user->hasAnyRole([
            Role::Owner->value,
            Role::Admin->value,
        ]);
    }

    /**
     * Determine whether the user can export the scan.
     */
    public function export(User $user, Scan $scan): bool
    {
        return $this->view($user, $scan);
    }

    /**
     * Determine whether the user can compare scans.
     */
    public function compare(User $user, Scan $scan): bool
    {
        return $this->view($user, $scan);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Scan $scan): bool
    {
        return $user->hasAnyRole([Role::Owner->value, Role::Admin->value])
            && $user->organization_id === $scan->url->project->organization_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Scan $scan): bool
    {
        return false;
    }
}
