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
 * Tests interactive component lifecycle (modals, accordions, tabs).
 *
 * WCAG Criteria Tested:
 * - 4.1.2 Name, Role, Value (Level A)
 * - 2.4.3 Focus Order (Level A)
 * - 1.3.1 Info and Relationships (Level A)
 */
class ComponentLifecycleJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 180;

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
            new WithoutOverlapping("component-lifecycle-{$this->audit->id}"),
        ];
    }

    public function handle(PlaywrightAccessibilityService $playwrightService): void
    {
        try {
            event(new AccessibilityAuditProgress($this->audit, 'Testing interactive components...', 75));

            $result = $playwrightService->testComponentLifecycle($this->url);

            // Process component lifecycle issues
            foreach ($result['issues'] as $issue) {
                $this->createAuditCheck($issue);
            }

            // Analyze components for common patterns
            $this->analyzeComponentPatterns($result['components']);

            // Store component analysis in audit metadata
            $this->audit->update([
                'metadata' => array_merge($this->audit->metadata ?? [], [
                    'component_lifecycle' => [
                        'components_detected' => $this->countComponentTypes($result['components']),
                        'state_changes_tested' => count($result['stateChanges']),
                        'focus_management_tested' => count($result['focusManagement']),
                        'aria_updates_verified' => count($result['ariaUpdates']),
                        'issues_found' => count($result['issues']),
                    ],
                ]),
            ]);

            Log::info('Component lifecycle test completed', [
                'audit_id' => $this->audit->id,
                'components_found' => count($result['components']),
                'issues_found' => count($result['issues']),
            ]);

        } catch (\Throwable $e) {
            Log::error('Component lifecycle test failed', [
                'audit_id' => $this->audit->id,
                'error' => $e->getMessage(),
            ]);

            // Create a warning check instead of failing the whole audit
            AuditCheck::create([
                'accessibility_audit_id' => $this->audit->id,
                'criterion_id' => '4.1.2',
                'status' => 'manual_review',
                'wcag_level' => 'A',
                'category' => AuditCategory::General,
                'impact' => 'moderate',
                'message' => 'Automated component testing could not be completed. Manual testing recommended.',
                'suggestion' => 'Test interactive components manually: verify ARIA states update on interaction, focus is managed in dialogs, and component roles are appropriate.',
            ]);
        }
    }

    /**
     * Analyze detected components for accessibility patterns.
     */
    protected function analyzeComponentPatterns(array $components): void
    {
        foreach ($components as $component) {
            match ($component['type']) {
                'dialog' => $this->analyzeDialog($component),
                'tabs' => $this->analyzeTabs($component),
                'accordion' => $this->analyzeAccordion($component),
                'menu' => $this->analyzeMenu($component),
                'combobox' => $this->analyzeCombobox($component),
                default => null,
            };
        }
    }

    /**
     * Analyze dialog/modal components.
     */
    protected function analyzeDialog(array $dialog): void
    {
        // Check for aria-modal
        if (($dialog['ariaModal'] ?? null) !== 'true') {
            $this->createPatternIssue(
                '4.1.2',
                'Dialog missing aria-modal attribute',
                'Add aria-modal="true" to dialog elements to properly trap focus and indicate modal behavior to screen readers.',
                'serious'
            );
        }

        // Check for aria-labelledby
        if (empty($dialog['ariaLabelledby'])) {
            $this->createPatternIssue(
                '4.1.2',
                'Dialog missing accessible name',
                'Add aria-labelledby referencing the dialog title, or use aria-label to provide an accessible name.',
                'serious'
            );
        }
    }

    /**
     * Analyze tab components.
     */
    protected function analyzeTabs(array $tabs): void
    {
        // Check tab and panel count match
        if (($tabs['tabCount'] ?? 0) !== ($tabs['panelCount'] ?? 0)) {
            $this->createPatternIssue(
                '1.3.1',
                'Tab count does not match panel count',
                'Ensure each tab has a corresponding tabpanel. Use aria-controls on tabs and aria-labelledby on panels.',
                'serious'
            );
        }

        // Check for aria-selected
        if (! ($tabs['hasAriaSelected'] ?? false)) {
            $this->createPatternIssue(
                '4.1.2',
                'Tabs missing aria-selected state',
                'Add aria-selected="true" to the active tab and aria-selected="false" to inactive tabs.',
                'serious'
            );
        }

        // Check for aria-controls
        if (! ($tabs['hasAriaControls'] ?? false)) {
            $this->createPatternIssue(
                '1.3.1',
                'Tabs missing aria-controls relationship',
                'Add aria-controls on each tab pointing to its associated tabpanel id.',
                'moderate'
            );
        }
    }

    /**
     * Analyze accordion components.
     */
    protected function analyzeAccordion(array $accordion): void
    {
        // Check for aria-expanded
        if (! ($accordion['hasAriaExpanded'] ?? false)) {
            $this->createPatternIssue(
                '4.1.2',
                'Accordion header missing aria-expanded state',
                'Add aria-expanded="true" or "false" to accordion headers to indicate expansion state.',
                'serious'
            );
        }

        // Check for aria-controls
        if (! ($accordion['hasAriaControls'] ?? false)) {
            $this->createPatternIssue(
                '1.3.1',
                'Accordion header missing aria-controls relationship',
                'Add aria-controls on accordion headers pointing to the controlled content region.',
                'moderate'
            );
        }
    }

    /**
     * Analyze menu components.
     */
    protected function analyzeMenu(array $menu): void
    {
        // Check item count
        if (($menu['itemCount'] ?? 0) === 0) {
            $this->createPatternIssue(
                '4.1.2',
                'Menu has no menuitem children',
                'Add role="menuitem", role="menuitemcheckbox", or role="menuitemradio" to menu items.',
                'serious'
            );
        }
    }

    /**
     * Analyze combobox components.
     */
    protected function analyzeCombobox(array $combobox): void
    {
        // Check for aria-expanded
        if (! ($combobox['hasAriaExpanded'] ?? false)) {
            $this->createPatternIssue(
                '4.1.2',
                'Combobox missing aria-expanded state',
                'Add aria-expanded to indicate whether the listbox popup is displayed.',
                'serious'
            );
        }

        // Check for aria-controls
        if (! ($combobox['hasAriaControls'] ?? false)) {
            $this->createPatternIssue(
                '1.3.1',
                'Combobox missing aria-controls relationship',
                'Add aria-controls pointing to the listbox element id.',
                'moderate'
            );
        }
    }

    /**
     * Create an issue for a pattern problem.
     */
    protected function createPatternIssue(string $criterionId, string $message, string $suggestion, string $impact): void
    {
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
            'status' => 'fail',
            'wcag_level' => 'A',
            'category' => AuditCategory::General,
            'impact' => $impact,
            'message' => $message,
            'suggestion' => $suggestion,
            'fingerprint' => $fingerprint,
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/',
        ]);
    }

    /**
     * Create an audit check from a detected issue.
     */
    protected function createAuditCheck(array $issue): void
    {
        $criterionId = $issue['criterion'] ?? '4.1.2';

        $fingerprint = md5(implode('|', [
            $criterionId,
            $issue['type'] ?? 'component',
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
            'status' => 'fail',
            'wcag_level' => 'A',
            'category' => AuditCategory::General,
            'impact' => $this->getImpact($issue['type'] ?? 'component'),
            'message' => $issue['message'] ?? 'Component accessibility issue detected',
            'suggestion' => $this->getSuggestion($issue['type'] ?? 'component'),
            'fingerprint' => $fingerprint,
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/',
        ]);
    }

    /**
     * Count components by type.
     */
    protected function countComponentTypes(array $components): array
    {
        $counts = [];
        foreach ($components as $component) {
            $type = $component['type'] ?? 'unknown';
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }

        return $counts;
    }

    protected function getImpact(string $issueType): string
    {
        return match ($issueType) {
            'focus-management' => 'critical',
            'aria-state' => 'serious',
            'aria-attribute' => 'moderate',
            default => 'moderate',
        };
    }

    protected function getSuggestion(string $issueType): string
    {
        return match ($issueType) {
            'focus-management' => 'Manage focus properly when opening/closing dialogs. Focus should move to the dialog on open and return to the trigger on close.',
            'aria-state' => 'Ensure ARIA states (aria-expanded, aria-selected, etc.) update dynamically when component state changes.',
            'aria-attribute' => 'Add required ARIA attributes to establish proper relationships between components.',
            default => 'Review the WAI-ARIA Authoring Practices Guide for the correct pattern implementation.',
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
            'component-lifecycle',
            'audit:'.$this->audit->id,
        ];
    }
}
