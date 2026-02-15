<?php

namespace App\Jobs\Accessibility;

use App\Enums\AuditCategory;
use App\Events\AccessibilityAuditProgress;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Services\Accessibility\PlaywrightAccessibilityService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;

/**
 * Tests mobile accessibility including touch targets and orientation.
 *
 * WCAG Criteria Tested:
 * - 1.3.4 Orientation (Level AA)
 * - 1.4.10 Reflow (Level AA)
 * - 2.5.5 Target Size (Level AAA)
 */
class MobileSimulationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    /**
     * Mobile viewport configurations to test.
     */
    protected array $viewports = [
        ['width' => 375, 'height' => 667, 'name' => 'iPhone SE'],
        ['width' => 390, 'height' => 844, 'name' => 'iPhone 14'],
        ['width' => 768, 'height' => 1024, 'name' => 'iPad'],
    ];

    public function __construct(
        protected AccessibilityAudit $audit,
        protected string $url
    ) {
        $this->onQueue('default');
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new WithoutOverlapping("mobile-simulation-{$this->audit->id}"),
        ];
    }

    public function handle(PlaywrightAccessibilityService $playwrightService): void
    {
        try {
            event(new AccessibilityAuditProgress($this->audit, 'Testing mobile accessibility...', 70));

            $allIssues = [];
            $touchTargetStats = [];

            // Test each viewport
            foreach ($this->viewports as $viewport) {
                $result = $playwrightService->testMobileAccessibility(
                    $this->url,
                    $viewport['width'],
                    $viewport['height']
                );

                // Collect issues from this viewport
                foreach ($result['issues'] as $issue) {
                    $issue['viewport'] = $viewport['name'];
                    $allIssues[] = $issue;
                }

                $touchTargetStats[$viewport['name']] = [
                    'total_targets' => count($result['touchTargets']),
                    'small_targets' => count($result['smallTargets']),
                    'orientation_supported' => $result['orientationSupport'],
                ];
            }

            // Deduplicate issues (same issue may appear in multiple viewports)
            $uniqueIssues = $this->deduplicateIssues($allIssues);

            // Create audit checks for unique issues
            foreach ($uniqueIssues as $issue) {
                $this->createAuditCheck($issue);
            }

            // Store mobile testing data in audit metadata
            $this->audit->update([
                'metadata' => array_merge($this->audit->metadata ?? [], [
                    'mobile_simulation' => [
                        'viewports_tested' => array_column($this->viewports, 'name'),
                        'touch_target_stats' => $touchTargetStats,
                        'total_issues' => count($uniqueIssues),
                    ],
                ]),
            ]);

            Log::info('Mobile simulation test completed', [
                'audit_id' => $this->audit->id,
                'viewports_tested' => count($this->viewports),
                'issues_found' => count($uniqueIssues),
            ]);

        } catch (\Throwable $e) {
            Log::error('Mobile simulation test failed', [
                'audit_id' => $this->audit->id,
                'error' => $e->getMessage(),
            ]);

            // Create a warning check instead of failing the whole audit
            AuditCheck::create([
                'accessibility_audit_id' => $this->audit->id,
                'criterion_id' => '1.4.10',
                'status' => 'manual_review',
                'wcag_level' => 'AA',
                'category' => AuditCategory::Vision,
                'impact' => 'moderate',
                'message' => 'Automated mobile testing could not be completed. Manual testing recommended.',
                'suggestion' => 'Test the page on mobile devices or use browser DevTools mobile simulation to verify touch targets and responsive layout.',
            ]);
        }
    }

    /**
     * Deduplicate issues that appear in multiple viewports.
     */
    protected function deduplicateIssues(array $issues): array
    {
        $seen = [];
        $unique = [];

        foreach ($issues as $issue) {
            // Create a key based on the issue type and element
            $key = $issue['type'].'-'.($issue['element']['selector'] ?? $issue['element']['tagName'] ?? 'unknown');

            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $issue;
            }
        }

        return $unique;
    }

    /**
     * Create an audit check from a detected issue.
     */
    protected function createAuditCheck(array $issue): void
    {
        $criterionId = $issue['criterion'] ?? '1.4.10';
        $wcagLevel = $issue['wcagLevel'] ?? $this->getWcagLevel($criterionId);

        // Generate fingerprint for this issue
        $fingerprint = md5(implode('|', [
            $criterionId,
            $issue['type'] ?? 'mobile',
            $issue['element']['tagName'] ?? '',
            $issue['element']['selector'] ?? '',
        ]));

        // Check for existing check with same fingerprint
        $exists = AuditCheck::where('accessibility_audit_id', $this->audit->id)
            ->where('fingerprint', $fingerprint)
            ->exists();

        if ($exists) {
            return;
        }

        AuditCheck::create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => $criterionId,
            'status' => $wcagLevel === 'AAA' ? 'warning' : 'fail',
            'wcag_level' => $wcagLevel,
            'category' => $this->getCategory($issue['type'] ?? 'mobile'),
            'impact' => $this->getImpact($issue['type'] ?? 'mobile'),
            'element_selector' => $issue['element']['selector'] ?? null,
            'element_html' => isset($issue['element']) ? json_encode($issue['element']) : null,
            'message' => $issue['message'] ?? $this->getMessage($issue['type'] ?? 'mobile'),
            'suggestion' => $this->getSuggestion($issue['type'] ?? 'mobile'),
            'fingerprint' => $fingerprint,
            'documentation_url' => "https://www.w3.org/WAI/WCAG21/Understanding/{$this->getUnderstandingPath($criterionId)}",
        ]);
    }

    protected function getWcagLevel(string $criterionId): string
    {
        return match ($criterionId) {
            '2.5.5' => 'AAA',
            default => 'AA',
        };
    }

    protected function getCategory(string $issueType): AuditCategory
    {
        return match ($issueType) {
            'touch-target-size' => AuditCategory::Motor,
            default => AuditCategory::Vision,
        };
    }

    protected function getImpact(string $issueType): string
    {
        return match ($issueType) {
            'touch-target-size' => 'moderate',
            'orientation-lock' => 'serious',
            'reflow' => 'serious',
            default => 'moderate',
        };
    }

    protected function getMessage(string $issueType): string
    {
        return match ($issueType) {
            'touch-target-size' => 'Touch target size is too small for easy activation',
            'orientation-lock' => 'Content restricts display orientation',
            'reflow' => 'Content requires horizontal scrolling at narrow viewport widths',
            default => 'Mobile accessibility issue detected',
        };
    }

    protected function getSuggestion(string $issueType): string
    {
        return match ($issueType) {
            'touch-target-size' => 'Increase the touch target size to at least 44x44 CSS pixels. Use padding to increase hit area without changing visual size.',
            'orientation-lock' => 'Remove orientation restrictions from viewport meta tag. Content should work in both portrait and landscape orientations.',
            'reflow' => 'Ensure content reflows without horizontal scrolling at 320px width. Use responsive design techniques and avoid fixed widths.',
            default => 'Ensure the page works well on mobile devices with touch input.',
        };
    }

    protected function getUnderstandingPath(string $criterionId): string
    {
        return match ($criterionId) {
            '1.3.4' => 'orientation',
            '1.4.10' => 'reflow',
            '2.5.5' => 'target-size',
            default => 'reflow',
        };
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
            'mobile-simulation',
            'audit:'.$this->audit->id,
        ];
    }
}
