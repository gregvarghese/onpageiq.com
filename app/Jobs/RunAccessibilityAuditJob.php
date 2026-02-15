<?php

namespace App\Jobs;

use App\Events\AccessibilityAuditCompleted;
use App\Events\AccessibilityAuditFailed;
use App\Events\AccessibilityAuditProgress;
use App\Jobs\Accessibility\ComponentLifecycleJob;
use App\Jobs\Accessibility\KeyboardJourneyJob;
use App\Jobs\Accessibility\MobileSimulationJob;
use App\Jobs\Accessibility\PatternAnalysisJob;
use App\Jobs\Accessibility\TimingContentJob;
use App\Models\AccessibilityAudit;
use App\Services\Accessibility\AccessibilityAuditService;
use App\Services\Browser\BrowserServiceManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class RunAccessibilityAuditJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600; // 10 minutes for comprehensive audits

    public int $backoff = 60;

    public function __construct(
        protected AccessibilityAudit $audit
    ) {
        // Set queue based on organization subscription tier
        $this->onQueue($this->determineQueue());
    }

    /**
     * Determine the queue based on organization subscription tier.
     */
    protected function determineQueue(): string
    {
        $organization = $this->audit->project->organization;

        return match ($organization->subscription_tier) {
            'enterprise' => 'high',
            'team', 'pro' => 'default',
            default => 'low',
        };
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        // Prevent overlapping audits for the same URL or project
        $key = $this->audit->url_id
            ? "accessibility-audit-url-{$this->audit->url_id}"
            : "accessibility-audit-project-{$this->audit->project_id}";

        return [
            new WithoutOverlapping($key),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(
        BrowserServiceManager $browserService,
        AccessibilityAuditService $auditService
    ): void {
        $url = $this->audit->url ?? $this->audit->project->urls()->first();

        if (! $url) {
            $this->handleFailure(new \RuntimeException('No URL found to audit'));

            return;
        }

        try {
            $this->broadcastProgress('Starting accessibility audit...', 5);

            // Mark audit as running
            $this->audit->markAsRunning();

            $this->broadcastProgress('Checking URL accessibility...', 10);

            // Check if URL is reachable
            if (! $browserService->isReachable($url->url)) {
                throw new \RuntimeException('URL is not reachable or does not return HTML content');
            }

            $this->broadcastProgress('Rendering page...', 20);

            // Get page content
            $pageContent = $browserService->getPageContent($url->url);

            $this->broadcastProgress('Analyzing accessibility...', 35);

            // Run the accessibility audit
            $auditService->runAudit(
                audit: $this->audit,
                htmlContent: $pageContent->html,
                pageUrl: $url->url
            );

            // Dispatch Phase 2 advanced browser testing jobs for enterprise tier
            $this->dispatchAdvancedTestingJobs($url->url);

            // Check for regression against previous audit
            $this->broadcastProgress('Checking for regressions...', 85);
            $previousAudit = $auditService->getPreviousAudits($this->audit, 1)->first();
            $regression = $auditService->detectRegression($this->audit, $previousAudit);

            // Store regression data in metadata
            if ($regression['new'] > 0 || $regression['fixed'] > 0) {
                $this->audit->update([
                    'metadata' => array_merge($this->audit->metadata ?? [], [
                        'regression' => $regression,
                        'previous_audit_id' => $previousAudit?->id,
                    ]),
                ]);
            }

            $this->broadcastProgress('Audit completed!', 100);

            // Broadcast completion event
            event(new AccessibilityAuditCompleted($this->audit));

            Log::info('Accessibility audit completed successfully', [
                'audit_id' => $this->audit->id,
                'url' => $url->url,
                'overall_score' => $this->audit->fresh()->overall_score,
                'checks_failed' => $this->audit->fresh()->checks_failed,
                'regression' => $regression,
            ]);

        } catch (\Throwable $e) {
            $this->handleFailure($e);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $this->handleFailure($exception);
    }

    /**
     * Handle failure and update records.
     */
    protected function handleFailure(?\Throwable $exception): void
    {
        $errorMessage = $exception?->getMessage() ?? 'Unknown error';

        // Only update if not already in a terminal state
        if (! $this->audit->isTerminal()) {
            $this->audit->markAsFailed($errorMessage);
        }

        event(new AccessibilityAuditFailed($this->audit, $errorMessage));

        Log::error('Accessibility audit failed', [
            'audit_id' => $this->audit->id,
            'project_id' => $this->audit->project_id,
            'url_id' => $this->audit->url_id,
            'error' => $errorMessage,
            'trace' => $exception?->getTraceAsString(),
        ]);
    }

    /**
     * Broadcast progress update.
     */
    protected function broadcastProgress(string $message, int $percentage): void
    {
        event(new AccessibilityAuditProgress($this->audit, $message, $percentage));
    }

    /**
     * Dispatch advanced browser testing jobs for enterprise tier.
     *
     * Phase 2-4 jobs run advanced accessibility tests:
     * - Keyboard journey and focus management (Phase 2)
     * - Mobile viewport and touch target testing (Phase 2)
     * - Interactive component lifecycle testing (Phase 2)
     * - Timing/motion content detection (Phase 2)
     * - WAI-ARIA APG pattern analysis (Phase 4)
     */
    protected function dispatchAdvancedTestingJobs(string $url): void
    {
        $organization = $this->audit->project->organization;

        // Only run advanced testing for enterprise tier
        if ($organization->subscription_tier !== 'enterprise') {
            Log::info('Skipping advanced accessibility testing (enterprise tier required)', [
                'audit_id' => $this->audit->id,
                'tier' => $organization->subscription_tier,
            ]);

            return;
        }

        $this->broadcastProgress('Running advanced accessibility tests...', 50);

        // Dispatch Phase 2-4 jobs in parallel using a batch
        Bus::batch([
            new KeyboardJourneyJob($this->audit, $url),
            new MobileSimulationJob($this->audit, $url),
            new ComponentLifecycleJob($this->audit, $url),
            new TimingContentJob($this->audit, $url),
            new PatternAnalysisJob($this->audit, $url),
        ])
            ->name("Accessibility Audit Phase 2 - {$this->audit->id}")
            ->allowFailures() // Don't fail the whole audit if one job fails
            ->onQueue('default')
            ->dispatch();

        Log::info('Dispatched Phase 2-4 advanced accessibility testing jobs', [
            'audit_id' => $this->audit->id,
            'url' => $url,
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'accessibility-audit',
            'audit:'.$this->audit->id,
            'project:'.$this->audit->project_id,
        ];
    }
}
