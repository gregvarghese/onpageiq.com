<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Services\Retention\HistoryRetentionService;
use Illuminate\Console\Command;

class CleanupScanHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scans:cleanup
                            {--organization= : Specific organization ID to clean up}
                            {--preview : Preview what would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old scan history based on organization retention policies';

    /**
     * Execute the console command.
     */
    public function handle(HistoryRetentionService $retentionService): int
    {
        $organizationId = $this->option('organization');
        $preview = $this->option('preview');

        if ($organizationId) {
            return $this->handleSingleOrganization($retentionService, $organizationId, $preview);
        }

        return $this->handleAllOrganizations($retentionService, $preview);
    }

    /**
     * Handle cleanup for a single organization.
     */
    protected function handleSingleOrganization(
        HistoryRetentionService $retentionService,
        int $organizationId,
        bool $preview
    ): int {
        $organization = Organization::find($organizationId);

        if (! $organization) {
            $this->error("Organization with ID {$organizationId} not found.");

            return self::FAILURE;
        }

        $retentionDays = $organization->getHistoryRetentionDays();

        $this->info("Organization: {$organization->name}");
        $this->info("Subscription Tier: {$organization->subscription_tier}");
        $this->info('Retention Policy: '.($retentionDays ? "{$retentionDays} days" : 'Unlimited'));

        if ($preview) {
            $stats = $retentionService->previewCleanup($organization);
            $this->newLine();
            $this->info('Preview (no changes will be made):');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Scans to delete', $stats['scans']],
                    ['Results to delete', $stats['results']],
                    ['Issues to delete', $stats['issues']],
                    ['Cutoff date', $stats['cutoff_date']],
                ]
            );

            return self::SUCCESS;
        }

        $result = $retentionService->cleanupForOrganization($organization);

        $this->newLine();
        $this->info('Cleanup completed:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Scans deleted', $result['scans']],
                ['Results deleted', $result['results']],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Handle cleanup for all organizations.
     */
    protected function handleAllOrganizations(
        HistoryRetentionService $retentionService,
        bool $preview
    ): int {
        if ($preview) {
            $this->info('Preview mode - no changes will be made.');
            $this->newLine();

            $organizations = Organization::whereIn('subscription_tier', ['free'])->get();

            $rows = [];
            foreach ($organizations as $org) {
                $stats = $retentionService->previewCleanup($org);
                $rows[] = [
                    $org->id,
                    $org->name,
                    $org->subscription_tier,
                    $org->getHistoryRetentionDays() ?? 'Unlimited',
                    $stats['scans'],
                    $stats['results'],
                ];
            }

            $this->table(
                ['ID', 'Organization', 'Tier', 'Retention', 'Scans', 'Results'],
                $rows
            );

            return self::SUCCESS;
        }

        $this->info('Running cleanup for all organizations with retention limits...');

        $result = $retentionService->cleanupAll();

        $this->newLine();
        $this->info('Cleanup completed:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Organizations processed', $result['organizations']],
                ['Scans deleted', $result['scans']],
                ['Results deleted', $result['results']],
            ]
        );

        return self::SUCCESS;
    }
}
