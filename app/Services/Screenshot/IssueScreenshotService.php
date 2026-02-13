<?php

namespace App\Services\Screenshot;

use App\Models\Issue;
use App\Models\ScanResult;
use App\Services\Browser\BrowserServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IssueScreenshotService
{
    public function __construct(
        protected BrowserServiceInterface $browserService
    ) {}

    /**
     * Capture screenshots for all issues in a scan result.
     *
     * @return array<int, string> Map of issue ID to screenshot path
     */
    public function captureForScanResult(ScanResult $scanResult, string $url): array
    {
        $issues = $scanResult->issues()->whereNotNull('dom_selector')->get();

        if ($issues->isEmpty()) {
            return [];
        }

        return $this->captureForIssues($issues, $url);
    }

    /**
     * Capture screenshots for a collection of issues.
     *
     * @param  Collection<int, Issue>  $issues
     * @return array<int, string> Map of issue ID to screenshot path
     */
    public function captureForIssues(Collection $issues, string $url): array
    {
        $screenshots = [];

        // Group issues by selector to avoid duplicate screenshots
        $grouped = $issues->groupBy('dom_selector');

        foreach ($grouped as $selector => $groupedIssues) {
            try {
                $screenshotPath = $this->captureScreenshot($url, $selector);

                if ($screenshotPath) {
                    // Assign the same screenshot to all issues with this selector
                    foreach ($groupedIssues as $issue) {
                        $screenshots[$issue->id] = $screenshotPath;

                        // Update the issue with the screenshot path
                        $issue->update(['screenshot_path' => $screenshotPath]);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to capture screenshot for issue', [
                    'selector' => $selector,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $screenshots;
    }

    /**
     * Capture a single screenshot for an issue.
     */
    public function captureForIssue(Issue $issue, string $url): ?string
    {
        if (! $issue->dom_selector) {
            return null;
        }

        try {
            $screenshotPath = $this->captureScreenshot($url, $issue->dom_selector);

            if ($screenshotPath) {
                $issue->update(['screenshot_path' => $screenshotPath]);
            }

            return $screenshotPath;
        } catch (\Exception $e) {
            Log::warning('Failed to capture screenshot for issue', [
                'issue_id' => $issue->id,
                'selector' => $issue->dom_selector,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Capture a screenshot with highlighted element.
     */
    protected function captureScreenshot(string $url, string $selector): ?string
    {
        $path = $this->browserService->screenshotWithHighlight($url, $selector);

        // Verify the file was created
        if (Storage::disk('local')->exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Delete screenshots for a scan result.
     */
    public function deleteForScanResult(ScanResult $scanResult): void
    {
        $screenshots = $scanResult->issues()
            ->whereNotNull('screenshot_path')
            ->pluck('screenshot_path')
            ->unique();

        foreach ($screenshots as $path) {
            $this->deleteScreenshot($path);
        }

        // Clear the screenshot paths
        $scanResult->issues()->update(['screenshot_path' => null]);
    }

    /**
     * Delete a single screenshot file.
     */
    protected function deleteScreenshot(string $path): void
    {
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * Get the public URL for a screenshot.
     */
    public function getScreenshotUrl(string $path): string
    {
        return Storage::disk('local')->url($path);
    }

    /**
     * Clean up old screenshots that are no longer referenced.
     */
    public function cleanupOrphanedScreenshots(int $daysOld = 7): int
    {
        $cutoff = now()->subDays($daysOld);
        $deleted = 0;

        $files = Storage::disk('local')->files('screenshots');

        foreach ($files as $file) {
            $lastModified = Storage::disk('local')->lastModified($file);

            if ($lastModified < $cutoff->timestamp) {
                // Check if any issue references this screenshot
                $isReferenced = Issue::where('screenshot_path', $file)->exists();

                if (! $isReferenced) {
                    Storage::disk('local')->delete($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }
}
