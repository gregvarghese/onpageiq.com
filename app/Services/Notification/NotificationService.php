<?php

namespace App\Services\Notification;

use App\Models\Organization;
use App\Models\Scan;
use App\Models\User;
use App\Notifications\CreditsDepletedNotification;
use App\Notifications\CreditsLowNotification;
use App\Notifications\ScanCompletedNotification;
use App\Notifications\TeamInviteNotification;

class NotificationService
{
    /**
     * Credit threshold for low balance warning.
     */
    protected const LOW_CREDIT_THRESHOLD = 10;

    /**
     * Notify users when a scan completes.
     */
    public function notifyScanCompleted(Scan $scan, int $issueCount = 0): void
    {
        $user = $scan->triggeredBy;

        if ($user) {
            $user->notify(new ScanCompletedNotification($scan, $issueCount));
        }
    }

    /**
     * Check credit balance and send notifications if needed.
     */
    public function checkCreditBalance(Organization $organization): void
    {
        $balance = $organization->credit_balance;

        if ($balance <= 0) {
            $this->notifyCreditsDepeleted($organization);
        } elseif ($balance <= self::LOW_CREDIT_THRESHOLD) {
            $this->notifyCreditsLow($organization, $balance);
        }
    }

    /**
     * Notify organization admins about low credits.
     */
    public function notifyCreditsLow(Organization $organization, int $remainingCredits): void
    {
        $admins = $this->getOrganizationAdmins($organization);

        foreach ($admins as $admin) {
            // Only notify once per threshold crossing (check if already notified recently)
            if (! $this->hasRecentNotification($admin, 'credits_low', hours: 24)) {
                $admin->notify(new CreditsLowNotification(
                    $organization,
                    $remainingCredits,
                    self::LOW_CREDIT_THRESHOLD
                ));
            }
        }
    }

    /**
     * Notify organization admins about depleted credits.
     */
    public function notifyCreditsDepeleted(Organization $organization): void
    {
        $admins = $this->getOrganizationAdmins($organization);

        foreach ($admins as $admin) {
            if (! $this->hasRecentNotification($admin, 'credits_depleted', hours: 24)) {
                $admin->notify(new CreditsDepletedNotification($organization));
            }
        }
    }

    /**
     * Notify a user about a team invitation.
     */
    public function notifyTeamInvite(User $invitee, Organization $organization, User $inviter, string $role = 'member'): void
    {
        $invitee->notify(new TeamInviteNotification($organization, $inviter, $role));
    }

    /**
     * Get organization admins (owners and admins).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    protected function getOrganizationAdmins(Organization $organization): \Illuminate\Database\Eloquent\Collection
    {
        return $organization->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Owner', 'Admin']);
            })
            ->get();
    }

    /**
     * Check if a user has received a specific notification type recently.
     */
    protected function hasRecentNotification(User $user, string $type, int $hours = 24): bool
    {
        return $user->notifications()
            ->where('created_at', '>=', now()->subHours($hours))
            ->whereJsonContains('data->type', $type)
            ->exists();
    }
}
