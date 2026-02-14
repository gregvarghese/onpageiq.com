<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Models\Scan;
use App\Services\AccessibilityCheckService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAccessibilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    public function __construct(
        public Scan $scan
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AccessibilityCheckService $accessibilityService): void
    {
        $result = $this->scan->result;

        if (! $result) {
            Log::info('No scan result for accessibility check', ['scan_id' => $this->scan->id]);

            return;
        }

        $html = $result->content_snapshot ?? '';

        if (empty($html)) {
            Log::info('No HTML content for accessibility check', ['scan_id' => $this->scan->id]);

            return;
        }

        // Run accessibility analysis
        $analysis = $accessibilityService->analyze($html);

        // Store accessibility score in result metadata
        $metadata = $result->metadata ?? [];
        $metadata['accessibility'] = [
            'score' => $analysis['score'],
            'total_issues' => $analysis['total_issues'],
            'summary' => $analysis['summary'],
            'checked_at' => now()->toIso8601String(),
        ];
        $result->update(['metadata' => $metadata]);

        // Create issues for each accessibility problem
        foreach ($analysis['issues'] as $issue) {
            Issue::create([
                'scan_result_id' => $result->id,
                'category' => 'accessibility',
                'severity' => $issue['severity'],
                'text_excerpt' => $issue['message'],
                'description' => $issue['recommendation'],
                'suggestion' => $issue['recommendation'],
                'context' => $issue['element'] ?? null,
                'metadata' => [
                    'check' => $issue['check'],
                    'wcag' => $issue['wcag'],
                    'level' => $issue['level'],
                    'selector' => $issue['selector'] ?? null,
                ],
            ]);
        }

        Log::info('Accessibility check completed', [
            'scan_id' => $this->scan->id,
            'score' => $analysis['score'],
            'issues_found' => $analysis['total_issues'],
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Accessibility check job failed', [
            'scan_id' => $this->scan->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'accessibility',
            'scan:'.$this->scan->id,
            'url:'.$this->scan->url_id,
        ];
    }
}
