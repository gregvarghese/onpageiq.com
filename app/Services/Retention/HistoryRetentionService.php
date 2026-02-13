<?php

namespace App\Services\Retention;

use App\Models\Organization;
use App\Models\Scan;
use App\Models\ScanResult;
use App\Services\Screenshot\IssueScreenshotService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HistoryRetentionService
{
    public function __construct(
        protected IssueScreenshotService $screenshotService
    ) {}

    /**
     * Clean up old scan history based on organization retention policies.
     *
     * @return array{organizations: int, scans: int, results: int}
     */
    public function cleanupAll(): array
    {
        $stats = [
            'organizations' => 0,
            'scans' => 0,
            'results' => 0,
        ];

        // Get organizations with retention limits (non-null retention days)
        $organizations = Organization::query()
            ->whereIn('subscription_tier', ['free']) // Only tiers with limits
            ->get();

        foreach ($organizations as $organization) {
            $result = $this->cleanupForOrganization($organization);
            $stats['organizations']++;
            $stats['scans'] += $result['scans'];
            $stats['results'] += $result['results'];
        }

        return $stats;
    }

    /**
     * Clean up old scan history for a specific organization.
     *
     * @return array{scans: int, results: int}
     */
    public function cleanupForOrganization(Organization $organization): array
    {
        $retentionDays = $organization->getHistoryRetentionDays();

        // Null means unlimited retention
        if ($retentionDays === null) {
            return ['scans' => 0, 'results' => 0];
        }

        $cutoffDate = now()->subDays($retentionDays);

        // Get scan IDs to delete
        $scansToDelete = Scan::query()
            ->whereHas('url.project', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->where('created_at', '<', $cutoffDate)
            ->pluck('id');

        if ($scansToDelete->isEmpty()) {
            return ['scans' => 0, 'results' => 0];
        }

        $scansCount = $scansToDelete->count();
        $resultsCount = 0;

        DB::transaction(function () use ($scansToDelete, &$resultsCount) {
            // Delete screenshots first
            $results = ScanResult::whereIn('scan_id', $scansToDelete)->get();
            $resultsCount = $results->count();

            foreach ($results as $result) {
                $this->screenshotService->deleteForScanResult($result);
            }

            // Delete issues (cascades from scan_results)
            DB::table('issues')
                ->whereIn('scan_result_id', $results->pluck('id'))
                ->delete();

            // Delete scan results
            ScanResult::whereIn('scan_id', $scansToDelete)->delete();

            // Delete scans
            Scan::whereIn('id', $scansToDelete)->delete();
        });

        Log::info('History retention cleanup completed for organization', [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'retention_days' => $retentionDays,
            'scans_deleted' => $scansCount,
            'results_deleted' => $resultsCount,
        ]);

        return [
            'scans' => $scansCount,
            'results' => $resultsCount,
        ];
    }

    /**
     * Get retention statistics for an organization.
     *
     * @return array{retention_days: int|null, oldest_scan: ?string, scans_at_risk: int}
     */
    public function getRetentionStats(Organization $organization): array
    {
        $retentionDays = $organization->getHistoryRetentionDays();

        $oldestScan = Scan::query()
            ->whereHas('url.project', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->orderBy('created_at')
            ->first();

        $scansAtRisk = 0;
        if ($retentionDays !== null) {
            $cutoffDate = now()->subDays($retentionDays);
            $scansAtRisk = Scan::query()
                ->whereHas('url.project', function ($q) use ($organization) {
                    $q->where('organization_id', $organization->id);
                })
                ->where('created_at', '<', $cutoffDate)
                ->count();
        }

        return [
            'retention_days' => $retentionDays,
            'oldest_scan' => $oldestScan?->created_at?->toIso8601String(),
            'scans_at_risk' => $scansAtRisk,
        ];
    }

    /**
     * Preview what would be deleted for an organization.
     *
     * @return array{scans: int, results: int, issues: int, cutoff_date: string}
     */
    public function previewCleanup(Organization $organization): array
    {
        $retentionDays = $organization->getHistoryRetentionDays();

        if ($retentionDays === null) {
            return [
                'scans' => 0,
                'results' => 0,
                'issues' => 0,
                'cutoff_date' => 'N/A (unlimited retention)',
            ];
        }

        $cutoffDate = now()->subDays($retentionDays);

        $scanIds = Scan::query()
            ->whereHas('url.project', function ($q) use ($organization) {
                $q->where('organization_id', $organization->id);
            })
            ->where('created_at', '<', $cutoffDate)
            ->pluck('id');

        $resultIds = ScanResult::whereIn('scan_id', $scanIds)->pluck('id');

        $issueCount = DB::table('issues')
            ->whereIn('scan_result_id', $resultIds)
            ->count();

        return [
            'scans' => $scanIds->count(),
            'results' => $resultIds->count(),
            'issues' => $issueCount,
            'cutoff_date' => $cutoffDate->toIso8601String(),
        ];
    }
}
