<?php

namespace App\Services\Security;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GdprService
{
    /**
     * Export all user data for GDPR compliance.
     *
     * @return string Path to the exported zip file
     */
    public function exportUserData(User $user): string
    {
        $organization = $user->organization;
        $exportPath = "exports/gdpr/{$user->id}";
        $timestamp = now()->format('Y-m-d_H-i-s');
        $zipFileName = "user_data_export_{$timestamp}.zip";

        Storage::makeDirectory($exportPath);

        // Collect all data
        $data = $this->collectUserData($user, $organization);

        // Write JSON files
        foreach ($data as $key => $content) {
            Storage::put("{$exportPath}/{$key}.json", json_encode($content, JSON_PRETTY_PRINT));
        }

        // Create ZIP archive
        $zipPath = storage_path("app/{$exportPath}/{$zipFileName}");
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            foreach ($data as $key => $content) {
                $zip->addFromString("{$key}.json", json_encode($content, JSON_PRETTY_PRINT));
            }
            $zip->close();
        }

        // Clean up individual JSON files
        foreach ($data as $key => $content) {
            Storage::delete("{$exportPath}/{$key}.json");
        }

        return $zipPath;
    }

    /**
     * Collect all user data.
     *
     * @return array<string, mixed>
     */
    protected function collectUserData(User $user, Organization $organization): array
    {
        return [
            'user_profile' => $this->getUserProfile($user),
            'organization' => $this->getOrganizationData($organization),
            'projects' => $this->getProjectsData($organization),
            'scans' => $this->getScansData($organization),
            'billing' => $this->getBillingData($organization),
            'audit_logs' => $this->getAuditLogs($user),
        ];
    }

    /**
     * Get user profile data.
     *
     * @return array<string, mixed>
     */
    protected function getUserProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'created_at' => $user->created_at->toIso8601String(),
            'updated_at' => $user->updated_at->toIso8601String(),
            'provider' => $user->provider,
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }

    /**
     * Get organization data.
     *
     * @return array<string, mixed>
     */
    protected function getOrganizationData(Organization $organization): array
    {
        return [
            'id' => $organization->id,
            'name' => $organization->name,
            'slug' => $organization->slug,
            'subscription_tier' => $organization->subscription_tier,
            'credit_balance' => $organization->credit_balance,
            'created_at' => $organization->created_at->toIso8601String(),
            'team_members' => $organization->users()->select('id', 'name', 'email', 'created_at')->get()->toArray(),
        ];
    }

    /**
     * Get projects data.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getProjectsData(Organization $organization): array
    {
        return $organization->projects()
            ->with('urls:id,project_id,url,status,created_at')
            ->get()
            ->map(fn ($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'language' => $project->language,
                'check_config' => $project->check_config,
                'created_at' => $project->created_at->toIso8601String(),
                'urls' => $project->urls->toArray(),
            ])
            ->toArray();
    }

    /**
     * Get scans data (limited to recent).
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getScansData(Organization $organization): array
    {
        $projectIds = $organization->projects()->pluck('id');

        return \App\Models\Scan::query()
            ->whereHas('url', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->with(['url:id,url', 'result:id,scan_id,scores'])
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn ($scan) => [
                'id' => $scan->id,
                'url' => $scan->url->url,
                'scan_type' => $scan->scan_type,
                'status' => $scan->status,
                'credits_charged' => $scan->credits_charged,
                'scores' => $scan->result?->scores,
                'created_at' => $scan->created_at->toIso8601String(),
                'completed_at' => $scan->completed_at?->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Get billing data.
     *
     * @return array<string, mixed>
     */
    protected function getBillingData(Organization $organization): array
    {
        return [
            'subscription_tier' => $organization->subscription_tier,
            'credit_balance' => $organization->credit_balance,
            'transactions' => $organization->creditTransactions()
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn ($tx) => [
                    'id' => $tx->id,
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'balance_after' => $tx->balance_after,
                    'description' => $tx->description,
                    'created_at' => $tx->created_at->toIso8601String(),
                ])
                ->toArray(),
        ];
    }

    /**
     * Get audit logs for user.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getAuditLogs(User $user): array
    {
        return \App\Models\AuditLog::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit(500)
            ->get()
            ->map(fn ($log) => [
                'action' => $log->action,
                'description' => $log->getDescription(),
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->toIso8601String(),
            ])
            ->toArray();
    }

    /**
     * Delete all user data for GDPR "right to be forgotten".
     */
    public function deleteUserData(User $user): void
    {
        $organization = $user->organization;

        // Only delete organization if user is the only member
        if ($organization && $organization->users()->count() === 1) {
            $this->deleteOrganizationData($organization);
        }

        // Delete user
        $user->tokens()->delete();
        $user->delete();
    }

    /**
     * Delete all organization data.
     */
    public function deleteOrganizationData(Organization $organization): void
    {
        // Delete projects and related data
        foreach ($organization->projects as $project) {
            foreach ($project->urls as $url) {
                // Delete scans and results
                foreach ($url->scans as $scan) {
                    $scan->result?->delete();
                    $scan->delete();
                }
                $url->delete();
            }
            $project->delete();
        }

        // Delete webhooks
        $organization->load('webhookEndpoints');
        foreach ($organization->webhookEndpoints ?? [] as $endpoint) {
            $endpoint->deliveries()->delete();
            $endpoint->delete();
        }

        // Delete credit transactions
        $organization->creditTransactions()->delete();

        // Delete audit logs
        \App\Models\AuditLog::where('organization_id', $organization->id)->delete();

        // Cancel any active subscriptions
        if ($organization->subscribed('default')) {
            $organization->subscription('default')?->cancelNow();
        }

        // Delete organization
        $organization->delete();
    }

    /**
     * Schedule data deletion (with grace period).
     */
    public function scheduleDeletion(User $user, int $gracePeriodDays = 30): void
    {
        $user->update([
            'scheduled_deletion_at' => now()->addDays($gracePeriodDays),
        ]);

        // TODO: Send confirmation email
    }

    /**
     * Cancel scheduled deletion.
     */
    public function cancelScheduledDeletion(User $user): void
    {
        $user->update([
            'scheduled_deletion_at' => null,
        ]);
    }
}
