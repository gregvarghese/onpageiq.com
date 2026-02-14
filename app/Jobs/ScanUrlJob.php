<?php

namespace App\Jobs;

use App\Events\ScanCompleted;
use App\Events\ScanFailed;
use App\Events\ScanProgress;
use App\Models\Scan;
use App\Models\ScanResult;
use App\Services\Analysis\ContentAnalyzer;
use App\Services\Browser\BrowserServiceManager;
use App\Services\Browser\PageContent;
use App\Services\Screenshot\IssueScreenshotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

class ScanUrlJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300;

    public int $backoff = 30;

    public function __construct(
        protected Scan $scan
    ) {
        // Set queue based on organization subscription tier
        $this->onQueue($this->determineQueue());
    }

    /**
     * Determine the queue based on organization subscription tier.
     */
    protected function determineQueue(): string
    {
        $organization = $this->scan->url->project->organization;

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
        return [
            new WithoutOverlapping($this->scan->url_id),
        ];
    }

    public function handle(
        BrowserServiceManager $browserService,
        ContentAnalyzer $analyzer,
        IssueScreenshotService $screenshotService
    ): void {
        $url = $this->scan->url;
        $project = $url->project;
        $organization = $project->organization;

        try {
            // Mark scan as processing
            $this->scan->markAsProcessing();
            $url->markAsScanning();

            $this->broadcastProgress('Checking URL accessibility...', 10);

            // Check if URL is reachable
            if (! $browserService->isReachable($url->url)) {
                throw new \RuntimeException('URL is not reachable or does not return HTML content');
            }

            $this->broadcastProgress('Rendering page...', 25);

            // Render the page and extract content
            $pageContent = $browserService->getPageContent($url->url);

            $this->broadcastProgress('Taking screenshots...', 40);

            // Take full-page screenshot
            $screenshotPath = $browserService->fullPageScreenshot($url->url);

            $this->broadcastProgress('Analyzing content...', 55);

            // Calculate credits based on content size
            $creditsRequired = $this->calculateCredits($pageContent);

            // Verify organization has enough credits
            if (! $organization->hasCredits($creditsRequired)) {
                throw new \RuntimeException('Insufficient credits for this scan');
            }

            // Deduct credits
            $organization->deductCredits($creditsRequired);
            $this->scan->update(['credits_charged' => $creditsRequired]);

            // Perform analysis
            $checks = $project->getEnabledChecks();
            $analysis = $analyzer->analyze(
                content: $pageContent,
                checks: $checks,
                deepAnalysis: $this->scan->isDeepScan(),
                language: $project->language,
                project: $project
            );

            $this->broadcastProgress('Storing results...', 85);

            // Create scan result
            $scanResult = ScanResult::create([
                'scan_id' => $this->scan->id,
                'content_snapshot' => $pageContent->text,
                'scores' => $analysis['scores'],
                'screenshots' => [$screenshotPath],
                'metadata' => [
                    'word_count' => $pageContent->wordCount,
                    'page_title' => $pageContent->title,
                    'meta' => $pageContent->meta,
                    'checks_performed' => $checks,
                ],
            ]);

            // Store issues
            $analyzer->storeResults($scanResult, $analysis);

            // Capture screenshots for issues (if enabled and has issues with selectors)
            if (config('onpageiq.scanning.capture_issue_screenshots', true)) {
                $this->broadcastProgress('Capturing issue screenshots...', 90);
                $screenshotService->captureForScanResult($scanResult, $url->url);
            }

            // Mark as completed
            $this->scan->markAsCompleted();
            $url->markAsCompleted();

            $this->broadcastProgress('Scan completed!', 100);

            // Broadcast completion event
            event(new ScanCompleted($this->scan));

            Log::info('Scan completed successfully', [
                'scan_id' => $this->scan->id,
                'url' => $url->url,
                'issues_found' => count($analysis['issues']),
                'credits_charged' => $creditsRequired,
            ]);

        } catch (\Exception $e) {
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

        $this->scan->markAsFailed($errorMessage);
        $this->scan->url->markAsFailed();

        event(new ScanFailed($this->scan, $errorMessage));

        Log::error('Scan failed', [
            'scan_id' => $this->scan->id,
            'url' => $this->scan->url->url,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Calculate credits required based on content size and scan type.
     */
    protected function calculateCredits(PageContent $content): int
    {
        $baseCredits = $this->scan->isDeepScan()
            ? config('onpageiq.credits.deep_scan', 3)
            : config('onpageiq.credits.quick_scan', 1);

        $sizeMultiplier = $content->getCreditMultiplier(
            config('onpageiq.scanning.large_page_threshold', 50000)
        );

        return $baseCredits * $sizeMultiplier;
    }

    /**
     * Broadcast progress update.
     */
    protected function broadcastProgress(string $message, int $percentage): void
    {
        event(new ScanProgress($this->scan, $message, $percentage));
    }
}
