<?php

use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
|--------------------------------------------------------------------------
| OnPageIQ Channels
|--------------------------------------------------------------------------
*/

// User can listen to scan updates if they belong to the scan's organization
Broadcast::channel('scans.{scanId}', function (User $user, int $scanId) {
    $scan = Scan::find($scanId);

    if (! $scan) {
        return false;
    }

    return $user->organization_id === $scan->url->project->organization_id;
});

// User can listen to organization-wide updates
Broadcast::channel('organizations.{organizationId}', function (User $user, int $organizationId) {
    return $user->organization_id === $organizationId;
});
