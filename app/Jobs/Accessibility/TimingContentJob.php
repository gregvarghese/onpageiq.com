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
 * Tests timing-related accessibility (auto-play, carousels, animations).
 *
 * WCAG Criteria Tested:
 * - 1.4.2 Audio Control (Level A)
 * - 2.2.1 Timing Adjustable (Level A)
 * - 2.2.2 Pause, Stop, Hide (Level A)
 * - 2.3.3 Animation from Interactions (Level AAA)
 */
class TimingContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

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
            new WithoutOverlapping("timing-content-{$this->audit->id}"),
        ];
    }

    public function handle(PlaywrightAccessibilityService $playwrightService): void
    {
        try {
            event(new AccessibilityAuditProgress($this->audit, 'Testing timing and motion content...', 80));

            $result = $playwrightService->detectTimingContent($this->url);

            // Process timing content issues
            foreach ($result['issues'] as $issue) {
                $this->createAuditCheck($issue);
            }

            // Additional analysis for detected content
            $this->analyzeAutoPlayingMedia($result['autoPlayingMedia']);
            $this->analyzeCarousels($result['carousels']);
            $this->analyzeAnimations($result['animations']);
            $this->analyzeLiveRegions($result['liveRegions']);

            // Store timing analysis in audit metadata
            $this->audit->update([
                'metadata' => array_merge($this->audit->metadata ?? [], [
                    'timing_content' => [
                        'auto_playing_media_count' => count($result['autoPlayingMedia']),
                        'carousels_count' => count($result['carousels']),
                        'animations_count' => count($result['animations']),
                        'live_regions_count' => count($result['liveRegions']),
                        'timers_count' => count($result['timers'] ?? []),
                        'issues_found' => count($result['issues']),
                    ],
                ]),
            ]);

            Log::info('Timing content test completed', [
                'audit_id' => $this->audit->id,
                'auto_playing_media' => count($result['autoPlayingMedia']),
                'carousels' => count($result['carousels']),
                'issues_found' => count($result['issues']),
            ]);

        } catch (\Throwable $e) {
            Log::error('Timing content test failed', [
                'audit_id' => $this->audit->id,
                'error' => $e->getMessage(),
            ]);

            // Create a warning check instead of failing the whole audit
            AuditCheck::create([
                'accessibility_audit_id' => $this->audit->id,
                'criterion_id' => '2.2.2',
                'status' => 'manual_review',
                'wcag_level' => 'A',
                'category' => AuditCategory::Vision,
                'impact' => 'moderate',
                'message' => 'Automated timing content testing could not be completed. Manual testing recommended.',
                'suggestion' => 'Manually check for auto-playing media, carousels, and animations. Verify pause controls exist and respect prefers-reduced-motion.',
            ]);
        }
    }

    /**
     * Analyze auto-playing media elements.
     */
    protected function analyzeAutoPlayingMedia(array $mediaElements): void
    {
        foreach ($mediaElements as $media) {
            // Auto-playing audio/video should have controls
            if ($media['autoplay'] && ! $media['controls']) {
                // Check if it's muted video (more acceptable)
                if ($media['type'] === 'video' && $media['muted']) {
                    // Muted auto-play is less problematic but should still have controls
                    $this->createIssue(
                        '2.2.2',
                        'warning',
                        'AA',
                        'Muted auto-playing video without visible controls',
                        'While muted auto-play is less disruptive, consider adding visible controls for users who want to stop the video.',
                        'moderate',
                        AuditCategory::Vision
                    );
                }
            }

            // Looping media should be pausable
            if (($media['loop'] ?? false) && ! $media['controls']) {
                $this->createIssue(
                    '2.2.2',
                    'fail',
                    'A',
                    'Looping media without pause control',
                    'Add controls to looping media so users can pause or stop playback.',
                    'serious',
                    AuditCategory::Vision
                );
            }

            // Long auto-playing audio is problematic
            if ($media['type'] === 'audio' && $media['autoplay'] && ! $media['muted']) {
                $this->createIssue(
                    '1.4.2',
                    'fail',
                    'A',
                    'Auto-playing audio detected',
                    'Avoid auto-playing audio, or ensure it stops within 3 seconds and provides controls to pause/stop.',
                    'critical',
                    AuditCategory::Vision
                );
            }
        }
    }

    /**
     * Analyze carousel/slider components.
     */
    protected function analyzeCarousels(array $carousels): void
    {
        foreach ($carousels as $carousel) {
            // Check for navigation
            if (! ($carousel['hasNavigation'] ?? false) && ($carousel['slideCount'] ?? 0) > 1) {
                $this->createIssue(
                    '2.1.1',
                    'fail',
                    'A',
                    'Carousel lacks navigation controls',
                    'Add previous/next buttons and/or slide indicators to allow keyboard navigation through carousel content.',
                    'serious',
                    AuditCategory::Motor
                );
            }

            // Check aria-live for dynamic content updates
            if (! ($carousel['ariaLive'] ?? null)) {
                $this->createIssue(
                    '4.1.3',
                    'warning',
                    'AA',
                    'Carousel may not announce slide changes to screen readers',
                    'Add aria-live="polite" to the carousel region so screen reader users are notified of content changes.',
                    'moderate',
                    AuditCategory::Vision
                );
            }
        }
    }

    /**
     * Analyze CSS animations.
     */
    protected function analyzeAnimations(array $animations): void
    {
        $infiniteAnimations = array_filter($animations, fn ($a) => $a['infinite'] ?? false);

        if (count($infiniteAnimations) > 3) {
            $this->createIssue(
                '2.3.3',
                'warning',
                'AAA',
                'Multiple infinite animations detected',
                'Consider reducing the number of continuous animations and supporting prefers-reduced-motion media query.',
                'moderate',
                AuditCategory::Cognitive
            );
        }

        // Check for potentially distracting animations
        foreach ($animations as $animation) {
            if (($animation['type'] ?? '') === 'css-animation' && ($animation['infinite'] ?? false)) {
                // Check animation duration - very fast animations can be problematic
                $duration = $this->parseDuration($animation['duration'] ?? '0s');
                if ($duration < 0.5 && $duration > 0) {
                    $this->createIssue(
                        '2.3.1',
                        'warning',
                        'A',
                        'Rapid animation detected (flashing potential)',
                        'Ensure animations do not flash more than 3 times per second. Consider slowing down rapid animations.',
                        'serious',
                        AuditCategory::Vision
                    );
                    break; // Only report once
                }
            }
        }
    }

    /**
     * Analyze ARIA live regions.
     */
    protected function analyzeLiveRegions(array $liveRegions): void
    {
        $assertiveRegions = array_filter($liveRegions, fn ($r) => ($r['ariaLive'] ?? '') === 'assertive');

        // Too many assertive regions can be disruptive
        if (count($assertiveRegions) > 2) {
            $this->createIssue(
                '4.1.3',
                'warning',
                'AA',
                'Multiple assertive live regions detected',
                'Use aria-live="assertive" sparingly. Most updates should use aria-live="polite" to avoid interrupting users.',
                'moderate',
                AuditCategory::Vision
            );
        }

        // Check for empty live regions with aria-busy not set
        foreach ($liveRegions as $region) {
            if (! ($region['hasContent'] ?? false) && ($region['ariaBusy'] ?? null) !== 'true') {
                // Empty live region without busy state might indicate timing issue
                $this->createIssue(
                    '4.1.3',
                    'warning',
                    'AA',
                    'Empty live region detected',
                    'If the live region content loads asynchronously, use aria-busy="true" while loading.',
                    'minor',
                    AuditCategory::Vision
                );
                break; // Only report once
            }
        }
    }

    /**
     * Parse CSS duration string to seconds.
     */
    protected function parseDuration(string $duration): float
    {
        if (str_ends_with($duration, 'ms')) {
            return (float) str_replace('ms', '', $duration) / 1000;
        }
        if (str_ends_with($duration, 's')) {
            return (float) str_replace('s', '', $duration);
        }

        return 0;
    }

    /**
     * Create an issue/audit check.
     */
    protected function createIssue(
        string $criterionId,
        string $status,
        string $wcagLevel,
        string $message,
        string $suggestion,
        string $impact,
        AuditCategory $category
    ): void {
        $fingerprint = md5(implode('|', [$criterionId, $message]));

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
            'status' => $status,
            'wcag_level' => $wcagLevel,
            'category' => $category,
            'impact' => $impact,
            'message' => $message,
            'suggestion' => $suggestion,
            'fingerprint' => $fingerprint,
            'documentation_url' => "https://www.w3.org/WAI/WCAG21/Understanding/{$this->getUnderstandingPath($criterionId)}",
        ]);
    }

    /**
     * Create an audit check from a detected issue.
     */
    protected function createAuditCheck(array $issue): void
    {
        $criterionId = $issue['criterion'] ?? '2.2.2';
        $wcagLevel = $issue['wcagLevel'] ?? 'A';

        $fingerprint = md5(implode('|', [
            $criterionId,
            $issue['type'] ?? 'timing',
            $issue['message'] ?? '',
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
            'category' => $this->getCategory($issue['type'] ?? 'timing'),
            'impact' => $this->getImpact($issue['type'] ?? 'timing'),
            'message' => $issue['message'] ?? 'Timing-related accessibility issue detected',
            'suggestion' => $this->getSuggestion($issue['type'] ?? 'timing'),
            'fingerprint' => $fingerprint,
            'documentation_url' => "https://www.w3.org/WAI/WCAG21/Understanding/{$this->getUnderstandingPath($criterionId)}",
        ]);
    }

    protected function getCategory(string $issueType): AuditCategory
    {
        return match ($issueType) {
            'motion' => AuditCategory::Cognitive,
            default => AuditCategory::Vision,
        };
    }

    protected function getImpact(string $issueType): string
    {
        return match ($issueType) {
            'auto-play' => 'serious',
            'carousel' => 'moderate',
            'motion' => 'moderate',
            'timing' => 'serious',
            default => 'moderate',
        };
    }

    protected function getSuggestion(string $issueType): string
    {
        return match ($issueType) {
            'auto-play' => 'Add visible controls to pause/stop auto-playing content, or prevent auto-play entirely.',
            'carousel' => 'Add pause controls and ensure carousels do not auto-advance, or auto-pause on hover/focus.',
            'motion' => 'Respect the prefers-reduced-motion media query and provide options to disable animations.',
            'timing' => 'Provide mechanisms to extend or disable time limits. Allow users to control timing.',
            default => 'Ensure users can control timing-related content.',
        };
    }

    protected function getUnderstandingPath(string $criterionId): string
    {
        return match ($criterionId) {
            '1.4.2' => 'audio-control',
            '2.2.1' => 'timing-adjustable',
            '2.2.2' => 'pause-stop-hide',
            '2.3.1' => 'three-flashes-or-below-threshold',
            '2.3.3' => 'animation-from-interactions',
            '4.1.3' => 'status-messages',
            default => 'pause-stop-hide',
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
            'timing-content',
            'audit:'.$this->audit->id,
        ];
    }
}
