<?php

namespace App\Jobs;

use App\Models\Issue;
use App\Models\IssueStateChange;
use App\Models\Scan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyFixedIssuesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    public function __construct(
        public Scan $scan
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $url = $this->scan->url;
        $currentResult = $this->scan->result;

        if (! $currentResult) {
            Log::info('No result for scan, skipping verification', ['scan_id' => $this->scan->id]);

            return;
        }

        // Get issues from previous scan
        $previousScan = $url->scans()
            ->where('id', '<', $this->scan->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        if (! $previousScan?->result) {
            Log::info('No previous scan to compare against', ['scan_id' => $this->scan->id]);

            return;
        }

        $previousIssues = $previousScan->result->issues()->get();
        $currentIssues = $currentResult->issues()->get();

        $verifiedCount = 0;
        $reappearedCount = 0;

        foreach ($previousIssues as $previousIssue) {
            // Skip already resolved/dismissed issues
            if ($previousIssue->isResolved() || $previousIssue->isDismissed()) {
                continue;
            }

            // Check if issue was marked as fixed
            if ($previousIssue->assignment?->status === 'review') {
                $stillExists = $this->issueStillExists($previousIssue, $currentIssues);

                if (! $stillExists) {
                    // Issue is verified fixed
                    $this->markAsVerified($previousIssue);
                    $verifiedCount++;
                } else {
                    // Issue reappeared
                    $this->markAsReappeared($previousIssue);
                    $reappearedCount++;
                }
            }
        }

        Log::info('Issue verification completed', [
            'scan_id' => $this->scan->id,
            'url_id' => $url->id,
            'verified_fixed' => $verifiedCount,
            'reappeared' => $reappearedCount,
        ]);
    }

    /**
     * Check if an issue still exists in the current scan.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, Issue>  $currentIssues
     */
    protected function issueStillExists(Issue $previousIssue, $currentIssues): bool
    {
        foreach ($currentIssues as $currentIssue) {
            // Match by category and text excerpt
            if (
                $currentIssue->category === $previousIssue->category &&
                $this->textMatches($currentIssue->text_excerpt, $previousIssue->text_excerpt)
            ) {
                return true;
            }

            // Also check by context if available
            if (
                $currentIssue->context &&
                $previousIssue->context &&
                $this->textMatches($currentIssue->context, $previousIssue->context)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if two text excerpts match (fuzzy matching).
     */
    protected function textMatches(?string $text1, ?string $text2): bool
    {
        if (empty($text1) || empty($text2)) {
            return false;
        }

        // Normalize
        $normalized1 = strtolower(trim($text1));
        $normalized2 = strtolower(trim($text2));

        // Exact match
        if ($normalized1 === $normalized2) {
            return true;
        }

        // Levenshtein distance for fuzzy matching
        $maxLen = max(strlen($normalized1), strlen($normalized2));
        if ($maxLen <= 0) {
            return false;
        }

        // Only compare if strings are reasonably similar in length
        if (abs(strlen($normalized1) - strlen($normalized2)) > 10) {
            return false;
        }

        $distance = levenshtein($normalized1, $normalized2);
        $similarity = 1 - ($distance / $maxLen);

        return $similarity >= 0.85; // 85% similarity threshold
    }

    /**
     * Mark an issue as verified fixed.
     */
    protected function markAsVerified(Issue $issue): void
    {
        $assignment = $issue->assignment;

        if ($assignment) {
            $oldStatus = $assignment->status;
            $assignment->update([
                'status' => 'resolved',
                'resolved_at' => now(),
            ]);

            // Record state change
            IssueStateChange::create([
                'issue_id' => $issue->id,
                'user_id' => null, // System action
                'from_state' => $oldStatus,
                'to_state' => 'resolved',
                'reason' => 'Automatically verified - issue no longer detected in latest scan',
            ]);
        }
    }

    /**
     * Mark an issue as reappeared.
     */
    protected function markAsReappeared(Issue $issue): void
    {
        $assignment = $issue->assignment;

        if ($assignment) {
            $oldStatus = $assignment->status;
            $assignment->update([
                'status' => 'open',
            ]);

            // Record state change
            IssueStateChange::create([
                'issue_id' => $issue->id,
                'user_id' => null, // System action
                'from_state' => $oldStatus,
                'to_state' => 'open',
                'reason' => 'Issue reappeared in latest scan - fix may not have been deployed',
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Issue verification job failed', [
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
            'verify-issues',
            'scan:'.$this->scan->id,
            'url:'.$this->scan->url_id,
        ];
    }
}
