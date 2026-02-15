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
 * Tests keyboard navigation and focus management.
 *
 * WCAG Criteria Tested:
 * - 2.1.1 Keyboard (Level A)
 * - 2.1.2 No Keyboard Trap (Level A)
 * - 2.4.3 Focus Order (Level A)
 * - 2.4.7 Focus Visible (Level AA)
 */
class KeyboardJourneyJob implements ShouldQueue
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
            new WithoutOverlapping("keyboard-journey-{$this->audit->id}"),
        ];
    }

    public function handle(PlaywrightAccessibilityService $playwrightService): void
    {
        try {
            event(new AccessibilityAuditProgress($this->audit, 'Testing keyboard navigation...', 60));

            $result = $playwrightService->testKeyboardJourney($this->url);

            // Process keyboard accessibility issues
            foreach ($result['issues'] as $issue) {
                $this->createAuditCheck($issue);
            }

            // Also test focus visibility
            $focusResult = $playwrightService->testFocusVisibility($this->url);

            foreach ($focusResult['issues'] as $issue) {
                $this->createAuditCheck($issue);
            }

            // Store the keyboard journey data in audit metadata
            $this->audit->update([
                'metadata' => array_merge($this->audit->metadata ?? [], [
                    'keyboard_journey' => [
                        'focusable_elements_count' => count($result['focusableElements']),
                        'tab_order_length' => count($result['tabOrder']),
                        'focus_traps_detected' => count($result['focusTraps']),
                        'keyboard_accessible' => $result['keyboardAccessible'],
                        'elements_with_visible_focus' => count($focusResult['elementsWithVisibleFocus']),
                        'elements_without_visible_focus' => count($focusResult['elementsWithoutVisibleFocus']),
                    ],
                ]),
            ]);

            Log::info('Keyboard journey test completed', [
                'audit_id' => $this->audit->id,
                'issues_found' => count($result['issues']) + count($focusResult['issues']),
            ]);

        } catch (\Throwable $e) {
            Log::error('Keyboard journey test failed', [
                'audit_id' => $this->audit->id,
                'error' => $e->getMessage(),
            ]);

            // Create a warning check instead of failing the whole audit
            AuditCheck::create([
                'accessibility_audit_id' => $this->audit->id,
                'criterion_id' => '2.1.1',
                'status' => 'manual_review',
                'wcag_level' => 'A',
                'category' => AuditCategory::Motor,
                'impact' => 'moderate',
                'message' => 'Automated keyboard testing could not be completed. Manual testing recommended.',
                'suggestion' => 'Test keyboard navigation manually: use Tab to navigate, Enter/Space to activate, Escape to close dialogs.',
            ]);
        }
    }

    /**
     * Create an audit check from a detected issue.
     */
    protected function createAuditCheck(array $issue): void
    {
        $criterionId = $issue['criterion'] ?? '2.1.1';
        $wcagLevel = $this->getWcagLevel($criterionId);

        // Generate fingerprint for this issue
        $fingerprint = md5(implode('|', [
            $criterionId,
            $issue['type'] ?? 'keyboard',
            $issue['element']['tagName'] ?? '',
            $issue['element']['id'] ?? '',
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
            'status' => 'fail',
            'wcag_level' => $wcagLevel,
            'category' => AuditCategory::Motor,
            'impact' => $this->getImpact($issue['type'] ?? 'keyboard'),
            'element_selector' => $issue['element']['selector'] ?? null,
            'element_html' => isset($issue['element']) ? json_encode($issue['element']) : null,
            'message' => $issue['message'] ?? 'Keyboard accessibility issue detected',
            'suggestion' => $this->getSuggestion($issue['type'] ?? 'keyboard'),
            'fingerprint' => $fingerprint,
            'documentation_url' => "https://www.w3.org/WAI/WCAG21/Understanding/{$this->getUnderstandingPath($criterionId)}",
        ]);
    }

    protected function getWcagLevel(string $criterionId): string
    {
        return match ($criterionId) {
            '2.4.7' => 'AA',
            default => 'A',
        };
    }

    protected function getImpact(string $issueType): string
    {
        return match ($issueType) {
            'focus-trap' => 'critical',
            'unreachable' => 'serious',
            'focus-visibility', 'focus-style-removed' => 'serious',
            default => 'moderate',
        };
    }

    protected function getSuggestion(string $issueType): string
    {
        return match ($issueType) {
            'focus-trap' => 'Ensure users can navigate out of all interactive elements using keyboard only. Add a mechanism to close modals/dialogs with Escape key.',
            'unreachable' => 'Ensure all interactive elements can be reached using Tab key navigation. Check tabindex values and element visibility.',
            'focus-visibility' => 'Add visible focus indicators using CSS :focus or :focus-visible pseudo-classes. Use outline, box-shadow, or background color changes.',
            'focus-style-removed' => 'Do not remove focus outlines without providing an alternative visible focus indicator. Replace "outline: none" with custom focus styles.',
            default => 'Ensure all interactive elements are keyboard accessible and have visible focus indicators.',
        };
    }

    protected function getUnderstandingPath(string $criterionId): string
    {
        return match ($criterionId) {
            '2.1.1' => 'keyboard',
            '2.1.2' => 'no-keyboard-trap',
            '2.4.3' => 'focus-order',
            '2.4.7' => 'focus-visible',
            default => 'keyboard',
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
            'keyboard-journey',
            'audit:'.$this->audit->id,
        ];
    }
}
