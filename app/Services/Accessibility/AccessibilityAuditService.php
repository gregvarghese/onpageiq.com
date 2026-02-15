<?php

namespace App\Services\Accessibility;

use App\Enums\AuditStatus;
use App\Enums\CheckStatus;
use App\Enums\ComplianceFramework;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\Project;
use App\Models\Url;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AccessibilityAuditService
{
    /**
     * WCAG criteria configuration.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $criteria;

    public function __construct()
    {
        $this->criteria = config('wcag.criteria', []);
    }

    /**
     * Create a new accessibility audit.
     */
    public function createAudit(
        Project $project,
        ?Url $url = null,
        ?User $triggeredBy = null,
        WcagLevel $level = WcagLevel::AA,
        ComplianceFramework $framework = ComplianceFramework::Wcag21,
    ): AccessibilityAudit {
        return AccessibilityAudit::create([
            'project_id' => $project->id,
            'url_id' => $url?->id,
            'triggered_by_user_id' => $triggeredBy?->id,
            'wcag_level_target' => $level,
            'framework' => $framework,
            'status' => AuditStatus::Pending,
        ]);
    }

    /**
     * Run the accessibility audit on HTML content.
     */
    public function runAudit(AccessibilityAudit $audit, string $htmlContent, string $pageUrl): void
    {
        $audit->markAsRunning();

        try {
            $dom = $this->parseHtml($htmlContent);
            $xpath = new DOMXPath($dom);

            // Get criteria to test based on target level
            $criteriaToTest = $this->getCriteriaForLevel($audit->wcag_level_target);

            foreach ($criteriaToTest as $criterionId) {
                $this->runCriterionCheck($audit, $criterionId, $dom, $xpath, $htmlContent, $pageUrl);
            }

            $audit->markAsCompleted();
        } catch (\Throwable $e) {
            Log::error('Accessibility audit failed', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $audit->markAsFailed($e->getMessage());
        }
    }

    /**
     * Parse HTML content into a DOMDocument.
     */
    protected function parseHtml(string $html): DOMDocument
    {
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        return $dom;
    }

    /**
     * Get all criteria IDs for a given WCAG level (inclusive).
     *
     * @return array<string>
     */
    protected function getCriteriaForLevel(WcagLevel $level): array
    {
        $levels = config('wcag.levels', []);
        $criteria = [];

        // Level A is always included
        $criteria = array_merge($criteria, $levels['A'] ?? []);

        if ($level === WcagLevel::AA || $level === WcagLevel::AAA) {
            $criteria = array_merge($criteria, $levels['AA'] ?? []);
        }

        if ($level === WcagLevel::AAA) {
            $criteria = array_merge($criteria, $levels['AAA'] ?? []);
        }

        return $criteria;
    }

    /**
     * Run a single criterion check.
     */
    protected function runCriterionCheck(
        AccessibilityAudit $audit,
        string $criterionId,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $criterionConfig = $this->criteria[$criterionId] ?? null;

        if (! $criterionConfig) {
            return;
        }

        // Get the check method for this criterion
        $methodName = 'check'.str_replace('.', '_', $criterionId);

        if (method_exists($this, $methodName)) {
            $this->$methodName($audit, $criterionConfig, $dom, $xpath, $html, $pageUrl);
        } else {
            // If no specific check method exists, create a manual review check
            $this->createManualReviewCheck($audit, $criterionId, $criterionConfig);
        }
    }

    /**
     * Create a check result.
     */
    protected function createCheck(
        AccessibilityAudit $audit,
        string $criterionId,
        array $criterionConfig,
        CheckStatus $status,
        ?string $elementSelector = null,
        ?string $elementHtml = null,
        ?string $message = null,
        ?string $suggestion = null,
        ?string $codeSnippet = null,
        ?array $metadata = null,
    ): AuditCheck {
        return AuditCheck::create([
            'accessibility_audit_id' => $audit->id,
            'criterion_id' => $criterionId,
            'criterion_name' => $criterionConfig['name'],
            'wcag_level' => $criterionConfig['level'],
            'category' => $criterionConfig['category'],
            'impact' => $status === CheckStatus::Fail ? $criterionConfig['impact'] : null,
            'status' => $status,
            'element_selector' => $elementSelector,
            'element_html' => $elementHtml,
            'message' => $message,
            'suggestion' => $suggestion,
            'code_snippet' => $codeSnippet,
            'documentation_url' => $criterionConfig['documentation_url'] ?? null,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Create a manual review check for criteria that can't be automated.
     */
    protected function createManualReviewCheck(
        AccessibilityAudit $audit,
        string $criterionId,
        array $criterionConfig,
    ): AuditCheck {
        return $this->createCheck(
            audit: $audit,
            criterionId: $criterionId,
            criterionConfig: $criterionConfig,
            status: CheckStatus::ManualReview,
            message: 'This criterion requires manual testing to verify compliance.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | WCAG Level A Checks
    |--------------------------------------------------------------------------
    */

    /**
     * 1.1.1 Non-text Content - Check for images without alt text.
     */
    protected function check1_1_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $images = $xpath->query('//img');
        $hasIssues = false;

        foreach ($images as $img) {
            $alt = $img->getAttribute('alt');
            $src = $img->getAttribute('src');
            $role = $img->getAttribute('role');

            // Images with role="presentation" or role="none" don't need alt
            if (in_array($role, ['presentation', 'none'])) {
                continue;
            }

            // Check if alt attribute exists
            if (! $img->hasAttribute('alt')) {
                $hasIssues = true;
                $this->createCheck(
                    audit: $audit,
                    criterionId: '1.1.1',
                    criterionConfig: $config,
                    status: CheckStatus::Fail,
                    elementSelector: $this->generateSelector($img),
                    elementHtml: $dom->saveHTML($img),
                    message: 'Image is missing alt attribute.',
                    suggestion: 'Add an alt attribute that describes the image content, or use alt="" if the image is purely decorative.',
                    codeSnippet: '<img src="'.$src.'" alt="Description of the image">',
                );
            }
        }

        // Check for other non-text content: area, input[type=image], object, svg
        $areas = $xpath->query('//area[not(@alt)]');
        foreach ($areas as $area) {
            $hasIssues = true;
            $this->createCheck(
                audit: $audit,
                criterionId: '1.1.1',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($area),
                elementHtml: $dom->saveHTML($area),
                message: 'Image map area is missing alt attribute.',
                suggestion: 'Add an alt attribute that describes the destination or function of the area.',
            );
        }

        $inputImages = $xpath->query('//input[@type="image"][not(@alt)]');
        foreach ($inputImages as $input) {
            $hasIssues = true;
            $this->createCheck(
                audit: $audit,
                criterionId: '1.1.1',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($input),
                elementHtml: $dom->saveHTML($input),
                message: 'Image button is missing alt attribute.',
                suggestion: 'Add an alt attribute that describes the button action.',
            );
        }

        if (! $hasIssues) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.1.1',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'All images have appropriate text alternatives.',
            );
        }
    }

    /**
     * 1.3.1 Info and Relationships - Check semantic HTML structure.
     */
    protected function check1_3_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for data tables without headers
        $tables = $xpath->query('//table');
        foreach ($tables as $table) {
            $role = $table->getAttribute('role');
            if ($role === 'presentation' || $role === 'none') {
                continue;
            }

            $headers = $xpath->query('.//th', $table);
            if ($headers->length === 0) {
                $issues[] = [
                    'element' => $table,
                    'message' => 'Data table is missing header cells (th elements).',
                    'suggestion' => 'Add th elements to identify column and/or row headers.',
                ];
            }
        }

        // Check for form inputs without labels
        $inputs = $xpath->query('//input[@type!="hidden" and @type!="submit" and @type!="button" and @type!="reset" and @type!="image"]|//select|//textarea');
        foreach ($inputs as $input) {
            $id = $input->getAttribute('id');
            $ariaLabel = $input->getAttribute('aria-label');
            $ariaLabelledby = $input->getAttribute('aria-labelledby');
            $title = $input->getAttribute('title');

            // Check if there's an associated label
            $hasLabel = false;
            if ($id) {
                $label = $xpath->query('//label[@for="'.$id.'"]');
                $hasLabel = $label->length > 0;
            }

            // Also check for implicit labels (input inside label)
            if (! $hasLabel) {
                $parent = $input->parentNode;
                while ($parent) {
                    if ($parent->nodeName === 'label') {
                        $hasLabel = true;
                        break;
                    }
                    $parent = $parent->parentNode;
                }
            }

            if (! $hasLabel && ! $ariaLabel && ! $ariaLabelledby && ! $title) {
                $issues[] = [
                    'element' => $input,
                    'message' => 'Form input is missing an associated label.',
                    'suggestion' => 'Add a label element with a for attribute matching the input id, or use aria-label.',
                ];
            }
        }

        // Check for proper heading hierarchy
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        $previousLevel = 0;
        foreach ($headings as $heading) {
            $level = (int) substr($heading->nodeName, 1);
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $issues[] = [
                    'element' => $heading,
                    'message' => "Heading level skipped from h{$previousLevel} to h{$level}.",
                    'suggestion' => 'Ensure heading levels are sequential and don\'t skip levels.',
                ];
            }
            $previousLevel = $level;
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.1',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.1',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Information and relationships are properly conveyed through semantic HTML.',
            );
        }
    }

    /**
     * 2.4.1 Bypass Blocks - Check for skip links and landmarks.
     */
    protected function check2_4_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Check for skip link
        $skipLinks = $xpath->query('//a[contains(@href, "#main") or contains(@href, "#content") or contains(translate(., "SKIPMANCOTENT", "skipmancotent"), "skip")]');
        $hasSkipLink = $skipLinks->length > 0;

        // Check for main landmark
        $mainLandmark = $xpath->query('//main|//*[@role="main"]');
        $hasMainLandmark = $mainLandmark->length > 0;

        // Check for navigation landmark
        $navLandmark = $xpath->query('//nav|//*[@role="navigation"]');
        $hasNavLandmark = $navLandmark->length > 0;

        if ($hasSkipLink || $hasMainLandmark) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.1',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: $hasSkipLink
                    ? 'Skip link found for bypassing repeated content.'
                    : 'Main landmark found for navigation.',
            );
        } else {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.1',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                message: 'No mechanism found to bypass blocks of repeated content.',
                suggestion: 'Add a skip link at the beginning of the page (e.g., "Skip to main content") or use landmark roles (main, nav, etc.).',
                codeSnippet: '<a href="#main-content" class="sr-only focus:not-sr-only">Skip to main content</a>',
            );
        }
    }

    /**
     * 2.4.2 Page Titled - Check for page title.
     */
    protected function check2_4_2(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $titles = $xpath->query('//title');
        $title = $titles->length > 0 ? trim($titles->item(0)->textContent) : '';

        if (empty($title)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.2',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                message: 'Page is missing a title element.',
                suggestion: 'Add a descriptive title element in the head section.',
                codeSnippet: '<title>Page Title - Site Name</title>',
            );
        } elseif (strlen($title) < 5) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.2',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: 'Page title may be too short to be descriptive.',
                suggestion: 'Ensure the title describes the page topic or purpose.',
                metadata: ['current_title' => $title],
            );
        } else {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.2',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Page has a descriptive title.',
                metadata: ['title' => $title],
            );
        }
    }

    /**
     * 3.1.1 Language of Page - Check for lang attribute.
     */
    protected function check3_1_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $htmlElements = $xpath->query('//html');

        if ($htmlElements->length === 0) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.1.1',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: 'Could not find html element to check language attribute.',
            );

            return;
        }

        $htmlElement = $htmlElements->item(0);
        $lang = $htmlElement->getAttribute('lang');
        $xmlLang = $htmlElement->getAttribute('xml:lang');

        if (empty($lang) && empty($xmlLang)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.1.1',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementHtml: '<html>',
                message: 'Page is missing a language attribute on the html element.',
                suggestion: 'Add a lang attribute to the html element specifying the primary language.',
                codeSnippet: '<html lang="en">',
            );
        } elseif (! preg_match('/^[a-z]{2,3}(-[A-Za-z]{2,4})?$/', $lang ?: $xmlLang)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.1.1',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: 'Language attribute may not be a valid language code.',
                suggestion: 'Use a valid BCP 47 language tag (e.g., "en", "en-US", "fr").',
                metadata: ['current_lang' => $lang ?: $xmlLang],
            );
        } else {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.1.1',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Page has a valid language attribute.',
                metadata: ['lang' => $lang ?: $xmlLang],
            );
        }
    }

    /**
     * 3.3.2 Labels or Instructions - Check form inputs have labels.
     */
    protected function check3_3_2(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $inputs = $xpath->query('//input[@type!="hidden" and @type!="submit" and @type!="button" and @type!="reset"]|//select|//textarea');
        $issues = [];

        foreach ($inputs as $input) {
            $id = $input->getAttribute('id');
            $name = $input->getAttribute('name');
            $type = $input->getAttribute('type') ?: 'text';
            $ariaLabel = $input->getAttribute('aria-label');
            $ariaLabelledby = $input->getAttribute('aria-labelledby');
            $placeholder = $input->getAttribute('placeholder');
            $title = $input->getAttribute('title');

            $hasLabel = false;

            // Check for associated label by for attribute
            if ($id) {
                $labels = $xpath->query('//label[@for="'.$id.'"]');
                $hasLabel = $labels->length > 0;
            }

            // Check for implicit label (input inside label)
            if (! $hasLabel) {
                $parent = $input->parentNode;
                while ($parent) {
                    if ($parent->nodeName === 'label') {
                        $hasLabel = true;
                        break;
                    }
                    $parent = $parent->parentNode;
                }
            }

            // Check for ARIA labeling
            $hasAriaLabel = ! empty($ariaLabel) || ! empty($ariaLabelledby);

            if (! $hasLabel && ! $hasAriaLabel && ! $title) {
                $issues[] = [
                    'element' => $input,
                    'message' => "Form {$input->nodeName}".($type ? " (type=\"{$type}\")" : '').' is missing a label.',
                    'suggestion' => 'Add a label element with for attribute, aria-label, or aria-labelledby.',
                ];

                // Warn about placeholder-only labeling
                if ($placeholder && ! $hasLabel && ! $hasAriaLabel) {
                    $issues[] = [
                        'element' => $input,
                        'message' => 'Placeholder text is used as the only label.',
                        'suggestion' => 'Placeholder text disappears when the user enters data. Add a visible label or aria-label.',
                    ];
                }
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.2',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.2',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'All form inputs have associated labels or instructions.',
            );
        }
    }

    /**
     * 4.1.1 Parsing - Check for valid HTML structure.
     */
    protected function check4_1_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for duplicate IDs
        $allElements = $xpath->query('//*[@id]');
        $ids = [];
        foreach ($allElements as $element) {
            $id = $element->getAttribute('id');
            if (isset($ids[$id])) {
                $issues[] = [
                    'element' => $element,
                    'message' => "Duplicate ID found: \"{$id}\"",
                    'suggestion' => 'Ensure all ID values are unique within the page.',
                ];
            }
            $ids[$id] = true;
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.1',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.1',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No parsing issues found (unique IDs, proper nesting).',
            );
        }
    }

    /**
     * 4.1.2 Name, Role, Value - Check interactive elements have accessible names.
     */
    protected function check4_1_2(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check buttons
        $buttons = $xpath->query('//button|//input[@type="button"]|//input[@type="submit"]|//*[@role="button"]');
        foreach ($buttons as $button) {
            $name = $this->getAccessibleName($button, $xpath);
            if (empty($name)) {
                $issues[] = [
                    'element' => $button,
                    'message' => 'Button is missing an accessible name.',
                    'suggestion' => 'Add text content, aria-label, or aria-labelledby to the button.',
                ];
            }
        }

        // Check links
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $name = $this->getAccessibleName($link, $xpath);
            if (empty($name)) {
                $issues[] = [
                    'element' => $link,
                    'message' => 'Link is missing an accessible name.',
                    'suggestion' => 'Add text content, aria-label, or aria-labelledby to the link.',
                ];
            }
        }

        // Check custom interactive elements with roles
        $customElements = $xpath->query('//*[@role="checkbox" or @role="radio" or @role="switch" or @role="slider" or @role="textbox" or @role="combobox" or @role="listbox"]');
        foreach ($customElements as $element) {
            $name = $this->getAccessibleName($element, $xpath);
            $role = $element->getAttribute('role');

            if (empty($name)) {
                $issues[] = [
                    'element' => $element,
                    'message' => "Custom {$role} element is missing an accessible name.",
                    'suggestion' => 'Add aria-label or aria-labelledby to provide an accessible name.',
                ];
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.2',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.2',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'All interactive elements have accessible names, roles, and values.',
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | WCAG Level AA Checks
    |--------------------------------------------------------------------------
    */

    /**
     * 2.5.3 Label in Name - Check that visible labels are in the accessible name.
     */
    protected function check2_5_3(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check buttons with aria-label that might not include visible text
        $buttons = $xpath->query('//button[@aria-label]|//input[@type="button" or @type="submit"][@aria-label]|//*[@role="button"][@aria-label]');
        foreach ($buttons as $button) {
            $ariaLabel = strtolower(trim($button->getAttribute('aria-label')));
            $visibleText = strtolower(trim($button->textContent));

            // Get value for input buttons
            if ($button->nodeName === 'input') {
                $visibleText = strtolower(trim($button->getAttribute('value')));
            }

            if ($visibleText && $ariaLabel && ! str_contains($ariaLabel, $visibleText)) {
                $issues[] = [
                    'element' => $button,
                    'message' => 'Accessible name does not contain the visible label text.',
                    'suggestion' => "The aria-label \"{$ariaLabel}\" should contain the visible text \"{$visibleText}\".",
                ];
            }
        }

        // Check links with aria-label
        $links = $xpath->query('//a[@aria-label]');
        foreach ($links as $link) {
            $ariaLabel = strtolower(trim($link->getAttribute('aria-label')));
            $visibleText = strtolower(trim($link->textContent));

            if ($visibleText && $ariaLabel && strlen($visibleText) > 2 && ! str_contains($ariaLabel, $visibleText)) {
                $issues[] = [
                    'element' => $link,
                    'message' => 'Link accessible name does not contain the visible label text.',
                    'suggestion' => 'The aria-label should contain the visible text for voice control users.',
                ];
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.5.3',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.5.3',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Visible labels are included in accessible names.',
            );
        }
    }

    /**
     * 3.2.1 On Focus - Check for context changes on focus.
     */
    protected function check3_2_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for onfocus handlers that might cause context changes
        $elementsWithOnFocus = $xpath->query('//*[@onfocus]');
        foreach ($elementsWithOnFocus as $element) {
            $handler = $element->getAttribute('onfocus');
            // Look for patterns that might indicate context change
            if (preg_match('/(location|submit|window\.open|href|navigate)/i', $handler)) {
                $issues[] = [
                    'element' => $element,
                    'message' => 'Element has onfocus handler that may cause a context change.',
                    'suggestion' => 'Avoid changing context (navigating, submitting, opening windows) on focus events.',
                ];
            }
        }

        // Check for autofocus on elements that might cause issues
        $autoFocusElements = $xpath->query('//*[@autofocus]');
        if ($autoFocusElements->length > 1) {
            $issues[] = [
                'element' => $autoFocusElements->item(0),
                'message' => 'Multiple elements have autofocus attribute.',
                'suggestion' => 'Only one element should have autofocus to avoid confusion.',
            ];
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.2.1',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.2.1',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No unexpected context changes on focus detected.',
            );
        }
    }

    /**
     * 3.2.2 On Input - Check for context changes on input.
     */
    protected function check3_2_2(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for onchange handlers on selects that might auto-submit
        $selects = $xpath->query('//select[@onchange]');
        foreach ($selects as $select) {
            $handler = $select->getAttribute('onchange');
            if (preg_match('/(submit|location|window\.open|href|navigate)/i', $handler)) {
                $issues[] = [
                    'element' => $select,
                    'message' => 'Select element changes context when an option is selected.',
                    'suggestion' => 'Provide a submit button rather than auto-submitting on selection change.',
                ];
            }
        }

        // Check for oninput handlers that might cause issues
        $inputsWithHandlers = $xpath->query('//input[@oninput or @onchange]');
        foreach ($inputsWithHandlers as $input) {
            $handler = $input->getAttribute('oninput') ?: $input->getAttribute('onchange');
            if (preg_match('/(submit|location|window\.open|navigate)/i', $handler)) {
                $issues[] = [
                    'element' => $input,
                    'message' => 'Input element may cause unexpected context change.',
                    'suggestion' => 'Avoid automatic navigation or form submission on input changes.',
                ];
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.2.2',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.2.2',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No unexpected context changes on input detected.',
            );
        }
    }

    /**
     * 3.3.1 Error Identification - Check for form validation and error messaging.
     */
    protected function check3_3_1(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];
        $hasRequiredFields = false;

        // Check for required fields
        $requiredInputs = $xpath->query('//input[@required]|//select[@required]|//textarea[@required]|//*[@aria-required="true"]');
        $hasRequiredFields = $requiredInputs->length > 0;

        // Check that required fields have aria-invalid or validation patterns
        foreach ($requiredInputs as $input) {
            $hasAriaDescribedby = $input->hasAttribute('aria-describedby');
            $hasAriaInvalid = $input->hasAttribute('aria-invalid');
            $hasPattern = $input->hasAttribute('pattern');

            // Check for associated error message element
            $id = $input->getAttribute('id');
            $describedby = $input->getAttribute('aria-describedby');

            if (! $hasAriaDescribedby && ! $hasAriaInvalid) {
                // This is informational - error handling needs JS/server-side
                $issues[] = [
                    'element' => $input,
                    'message' => 'Required field may need aria-describedby for error messages.',
                    'suggestion' => 'Use aria-describedby to associate error messages and aria-invalid to indicate validation state.',
                    'severity' => 'info',
                ];
            }
        }

        // Check for aria-live regions for dynamic errors
        $liveRegions = $xpath->query('//*[@aria-live="polite" or @aria-live="assertive" or @role="alert" or @role="status"]');
        $hasLiveRegion = $liveRegions->length > 0;

        if ($hasRequiredFields && ! $hasLiveRegion) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.1',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: 'Form has required fields but no ARIA live region for error announcements.',
                suggestion: 'Add a live region (role="alert" or aria-live) to announce validation errors to screen readers.',
                codeSnippet: '<div role="alert" aria-live="assertive" class="error-messages"></div>',
            );

            return;
        }

        $this->createCheck(
            audit: $audit,
            criterionId: '3.3.1',
            criterionConfig: $config,
            status: $hasRequiredFields ? CheckStatus::Pass : CheckStatus::NotApplicable,
            message: $hasRequiredFields
                ? 'Form validation structure appears accessible.'
                : 'No required form fields detected.',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | WCAG Level AA Checks
    |--------------------------------------------------------------------------
    */

    /**
     * 1.4.3 Contrast (Minimum) - Check text contrast ratios.
     * Note: Full contrast checking requires computed styles from browser.
     * This is a partial check for inline styles only.
     */
    protected function check1_4_3(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Full contrast checking requires browser rendering to get computed styles.
        // Mark as manual review with guidance.
        $this->createCheck(
            audit: $audit,
            criterionId: '1.4.3',
            criterionConfig: $config,
            status: CheckStatus::ManualReview,
            message: 'Color contrast requires visual verification. Minimum ratios: 4.5:1 for normal text, 3:1 for large text (18pt or 14pt bold).',
            suggestion: 'Use browser dev tools or a contrast checker to verify text has sufficient contrast against its background.',
            metadata: [
                'normal_text_ratio' => 4.5,
                'large_text_ratio' => 3.0,
            ],
        );
    }

    /**
     * 2.4.7 Focus Visible - Check for focus indicator suppression.
     */
    protected function check2_4_7(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Check for outline: none or outline: 0 in inline styles
        $elementsWithOutlineNone = $xpath->query('//*[contains(@style, "outline:") or contains(@style, "outline :")]');
        $issues = [];

        foreach ($elementsWithOutlineNone as $element) {
            $style = $element->getAttribute('style');
            if (preg_match('/outline\s*:\s*(none|0)/i', $style)) {
                $issues[] = [
                    'element' => $element,
                    'message' => 'Element has outline removed via inline style.',
                    'suggestion' => 'Ensure a visible focus indicator is provided when the default outline is removed.',
                ];
            }
        }

        // Check style tags for :focus { outline: none }
        $styleTags = $xpath->query('//style');
        $hasOutlineRemoval = false;
        foreach ($styleTags as $styleTag) {
            $css = $styleTag->textContent;
            if (preg_match('/:focus[^{]*\{[^}]*outline\s*:\s*(none|0)/i', $css)) {
                $hasOutlineRemoval = true;
            }
        }

        if ($hasOutlineRemoval) {
            $issues[] = [
                'element' => null,
                'message' => 'CSS rule found that removes focus outline. Alternative focus indicator may be needed.',
                'suggestion' => 'When removing the default outline, provide a visible alternative focus indicator (e.g., box-shadow, border, background change).',
            ];
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.7',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $issue['element'] ? $this->generateSelector($issue['element']) : null,
                elementHtml: $issue['element'] ? $dom->saveHTML($issue['element']) : null,
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.7',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No evidence of focus indicator suppression found.',
            );
        }
    }

    /**
     * 1.3.4 Orientation - Check for orientation restrictions.
     * Note: Full check requires CSS analysis and viewport testing.
     */
    protected function check1_3_4(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Check for orientation media queries in style tags
        $styleTags = $xpath->query('//style');
        $hasOrientationLock = false;

        foreach ($styleTags as $styleTag) {
            $css = $styleTag->textContent;
            // Look for patterns that might indicate orientation lock
            // This is a heuristic - full testing requires viewport simulation
            if (preg_match('/@media[^{]*orientation\s*:\s*(portrait|landscape)[^{]*\{[^}]*display\s*:\s*none/i', $css)) {
                $hasOrientationLock = true;
            }
        }

        if ($hasOrientationLock) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.4',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: 'CSS may be hiding content based on orientation.',
                suggestion: 'Ensure content is accessible in both portrait and landscape orientations unless a specific orientation is essential.',
            );
        } else {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.4',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No orientation restrictions detected in inline styles.',
            );
        }
    }

    /**
     * 1.4.11 Non-text Contrast - Check UI component contrast.
     * Note: Full check requires computed styles from browser.
     */
    protected function check1_4_11(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $this->createCheck(
            audit: $audit,
            criterionId: '1.4.11',
            criterionConfig: $config,
            status: CheckStatus::ManualReview,
            message: 'UI component and graphical object contrast requires visual verification. Minimum ratio: 3:1.',
            suggestion: 'Verify that form controls, focus indicators, and meaningful graphics have at least 3:1 contrast ratio.',
            metadata: [
                'required_ratio' => 3.0,
            ],
        );
    }

    /**
     * 1.4.4 Resize Text - Check for text resize handling.
     */
    protected function check1_4_4(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for viewport meta that prevents zoom
        $viewportMeta = $xpath->query('//meta[@name="viewport"]');
        foreach ($viewportMeta as $meta) {
            $content = $meta->getAttribute('content');
            if (preg_match('/user-scalable\s*=\s*(no|0)/i', $content)) {
                $issues[] = [
                    'element' => $meta,
                    'message' => 'Viewport meta tag prevents user scaling.',
                    'suggestion' => 'Remove user-scalable=no to allow users to zoom.',
                ];
            }
            if (preg_match('/maximum-scale\s*=\s*1(\.0)?/i', $content)) {
                $issues[] = [
                    'element' => $meta,
                    'message' => 'Viewport meta tag limits maximum scale to 1.',
                    'suggestion' => 'Allow maximum-scale of at least 2.0 or remove the restriction.',
                ];
            }
        }

        // Check for fixed font sizes in pixels (inline styles only)
        $elementsWithPxFont = $xpath->query('//*[contains(@style, "font-size")]');
        $pxFontCount = 0;
        foreach ($elementsWithPxFont as $element) {
            $style = $element->getAttribute('style');
            if (preg_match('/font-size\s*:\s*\d+px/i', $style)) {
                $pxFontCount++;
            }
        }

        if ($pxFontCount > 5) {
            $issues[] = [
                'element' => null,
                'message' => "Found {$pxFontCount} elements with pixel-based font sizes in inline styles.",
                'suggestion' => 'Use relative units (rem, em, %) for font sizes to support text resizing.',
            ];
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.4',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $issue['element'] ? $this->generateSelector($issue['element']) : null,
                elementHtml: $issue['element'] ? $dom->saveHTML($issue['element']) : null,
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.4',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No issues detected that would prevent text resizing up to 200%.',
            );
        }
    }

    /**
     * 1.4.10 Reflow - Check for horizontal scrolling at 320px width.
     */
    protected function check1_4_10(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for fixed widths in inline styles that might cause horizontal scroll
        $fixedWidthElements = $xpath->query('//*[contains(@style, "width")]');
        foreach ($fixedWidthElements as $element) {
            $style = $element->getAttribute('style');
            if (preg_match('/width\s*:\s*(\d+)px/i', $style, $matches)) {
                $width = (int) $matches[1];
                if ($width > 320) {
                    $issues[] = [
                        'element' => $element,
                        'message' => "Element has fixed width of {$width}px which may cause horizontal scrolling at 320px viewport.",
                        'suggestion' => 'Use relative widths (%, vw, max-width) or responsive design patterns.',
                    ];
                }
            }
        }

        // Check viewport meta for width restrictions
        $viewportMeta = $xpath->query('//meta[@name="viewport"]');
        $hasResponsiveViewport = false;
        foreach ($viewportMeta as $meta) {
            $content = $meta->getAttribute('content');
            if (str_contains($content, 'width=device-width')) {
                $hasResponsiveViewport = true;
            }
        }

        if (! $hasResponsiveViewport && $viewportMeta->length > 0) {
            $issues[] = [
                'element' => $viewportMeta->item(0),
                'message' => 'Viewport meta tag may not be configured for responsive design.',
                'suggestion' => 'Use width=device-width to enable responsive behavior.',
            ];
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.10',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $issue['element'] ? $this->generateSelector($issue['element']) : null,
                elementHtml: $issue['element'] ? $dom->saveHTML($issue['element']) : null,
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.10',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No fixed width elements detected that would prevent reflow at 320px.',
            );
        }
    }

    /**
     * 1.4.12 Text Spacing - Check for text spacing override support.
     */
    protected function check1_4_12(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for !important on text spacing properties in style tags
        $styleTags = $xpath->query('//style');
        foreach ($styleTags as $styleTag) {
            $css = $styleTag->textContent;
            if (preg_match('/(line-height|letter-spacing|word-spacing)\s*:[^;]*!important/i', $css)) {
                $issues[] = [
                    'element' => null,
                    'message' => 'CSS uses !important on text spacing properties which may prevent user overrides.',
                    'suggestion' => 'Avoid !important on line-height, letter-spacing, and word-spacing to allow user customization.',
                ];
                break;
            }
        }

        // Check for fixed height containers that might clip text
        $fixedHeightElements = $xpath->query('//*[contains(@style, "height") and contains(@style, "overflow")]');
        foreach ($fixedHeightElements as $element) {
            $style = $element->getAttribute('style');
            if (preg_match('/overflow\s*:\s*hidden/i', $style) && preg_match('/height\s*:\s*\d+/i', $style)) {
                $issues[] = [
                    'element' => $element,
                    'message' => 'Container with fixed height and overflow:hidden may clip text when spacing is increased.',
                    'suggestion' => 'Use min-height instead of height, or overflow:auto to allow content to expand.',
                ];
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.12',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $issue['element'] ? $this->generateSelector($issue['element']) : null,
                elementHtml: $issue['element'] ? $dom->saveHTML($issue['element']) : null,
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.12',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No issues detected that would prevent text spacing adjustments.',
            );
        }
    }

    /**
     * 1.3.5 Identify Input Purpose - Check for autocomplete attributes.
     */
    protected function check1_3_5(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Input types that should have autocomplete
        $personalDataPatterns = [
            'name' => ['autocomplete' => 'name', 'types' => ['text']],
            'email' => ['autocomplete' => 'email', 'types' => ['email', 'text']],
            'tel' => ['autocomplete' => 'tel', 'types' => ['tel', 'text']],
            'address' => ['autocomplete' => 'street-address', 'types' => ['text']],
            'city' => ['autocomplete' => 'address-level2', 'types' => ['text']],
            'zip' => ['autocomplete' => 'postal-code', 'types' => ['text']],
            'postal' => ['autocomplete' => 'postal-code', 'types' => ['text']],
            'country' => ['autocomplete' => 'country-name', 'types' => ['text']],
            'cc-name' => ['autocomplete' => 'cc-name', 'types' => ['text']],
            'cc-number' => ['autocomplete' => 'cc-number', 'types' => ['text']],
            'password' => ['autocomplete' => 'current-password', 'types' => ['password']],
        ];

        $inputs = $xpath->query('//input[@type="text" or @type="email" or @type="tel" or @type="password" or not(@type)]');
        foreach ($inputs as $input) {
            $name = strtolower($input->getAttribute('name'));
            $id = strtolower($input->getAttribute('id'));
            $autocomplete = $input->getAttribute('autocomplete');

            if ($autocomplete) {
                continue; // Already has autocomplete
            }

            // Check if input name/id suggests personal data
            foreach ($personalDataPatterns as $pattern => $data) {
                if (str_contains($name, $pattern) || str_contains($id, $pattern)) {
                    $issues[] = [
                        'element' => $input,
                        'message' => "Input appears to collect personal data ({$pattern}) but lacks autocomplete attribute.",
                        'suggestion' => "Add autocomplete=\"{$data['autocomplete']}\" to help users with autofill.",
                    ];
                    break;
                }
            }
        }

        if (count($issues) > 5) {
            // Summarize if too many issues
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.5',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: count($issues).' form inputs collecting personal data are missing autocomplete attributes.',
                suggestion: 'Add appropriate autocomplete values to help users autofill personal information.',
            );

            return;
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.5',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.3.5',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Input fields have appropriate autocomplete attributes.',
            );
        }
    }

    /**
     * 2.4.5 Multiple Ways - Check for multiple navigation mechanisms.
     */
    protected function check2_4_5(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $navigationMethods = [];

        // Check for navigation menu
        $nav = $xpath->query('//nav|//*[@role="navigation"]');
        if ($nav->length > 0) {
            $navigationMethods[] = 'navigation menu';
        }

        // Check for search form
        $search = $xpath->query('//form[@role="search"]|//input[@type="search"]|//*[contains(@class, "search")]//input');
        if ($search->length > 0) {
            $navigationMethods[] = 'search functionality';
        }

        // Check for sitemap link
        $sitemap = $xpath->query('//a[contains(translate(., "SITEMAP", "sitemap"), "sitemap") or contains(@href, "sitemap")]');
        if ($sitemap->length > 0) {
            $navigationMethods[] = 'sitemap link';
        }

        // Check for table of contents
        $toc = $xpath->query('//*[contains(@class, "toc") or contains(@id, "toc") or contains(@class, "table-of-contents")]');
        if ($toc->length > 0) {
            $navigationMethods[] = 'table of contents';
        }

        // Check for breadcrumbs
        $breadcrumbs = $xpath->query('//*[@aria-label="breadcrumb" or contains(@class, "breadcrumb")]|//nav[contains(@class, "breadcrumb")]');
        if ($breadcrumbs->length > 0) {
            $navigationMethods[] = 'breadcrumbs';
        }

        if (count($navigationMethods) >= 2) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.5',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Multiple ways to navigate found: '.implode(', ', $navigationMethods).'.',
            );
        } elseif (count($navigationMethods) === 1) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.5',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: 'Only one navigation method detected: '.implode(', ', $navigationMethods).'.',
                suggestion: 'Consider adding additional navigation methods like search, sitemap, or table of contents.',
            );
        } else {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.5',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                message: 'No clear navigation mechanisms detected.',
                suggestion: 'Add navigation menu, search functionality, sitemap, or other ways to locate content.',
            );
        }
    }

    /**
     * 2.4.6 Headings and Labels - Check for descriptive headings and labels.
     */
    protected function check2_4_6(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for empty headings
        $headings = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');
        foreach ($headings as $heading) {
            $text = trim($heading->textContent);
            if (empty($text)) {
                $issues[] = [
                    'element' => $heading,
                    'message' => 'Empty heading element found.',
                    'suggestion' => 'Add descriptive text to the heading or remove it.',
                ];
            } elseif (strlen($text) < 3) {
                $issues[] = [
                    'element' => $heading,
                    'message' => 'Heading text may be too short to be descriptive.',
                    'suggestion' => 'Use headings that describe the content of the section.',
                ];
            }
        }

        // Check for generic/non-descriptive headings
        $genericHeadings = ['click here', 'read more', 'more', 'info', 'details', 'link', 'here'];
        foreach ($headings as $heading) {
            $text = strtolower(trim($heading->textContent));
            if (in_array($text, $genericHeadings)) {
                $issues[] = [
                    'element' => $heading,
                    'message' => "Heading \"{$text}\" is not descriptive.",
                    'suggestion' => 'Use headings that describe the topic or purpose of the section.',
                ];
            }
        }

        // Check for empty labels
        $labels = $xpath->query('//label');
        foreach ($labels as $label) {
            $text = trim($label->textContent);
            if (empty($text) && ! $label->getElementsByTagName('img')->length) {
                $issues[] = [
                    'element' => $label,
                    'message' => 'Empty label element found.',
                    'suggestion' => 'Add descriptive text to the label.',
                ];
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.6',
                criterionConfig: $config,
                status: CheckStatus::Fail,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '2.4.6',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Headings and labels appear descriptive.',
            );
        }
    }

    /**
     * 1.4.5 Images of Text - Check for text rendered as images.
     */
    protected function check1_4_5(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for images that might contain text (based on naming patterns)
        $images = $xpath->query('//img');
        foreach ($images as $img) {
            $src = strtolower($img->getAttribute('src'));
            $alt = $img->getAttribute('alt');

            // Check for patterns suggesting text images
            $textImagePatterns = ['text', 'logo', 'banner', 'header', 'title', 'button', 'nav', 'menu'];
            foreach ($textImagePatterns as $pattern) {
                if (str_contains($src, $pattern) && ! empty($alt) && strlen($alt) > 20) {
                    $issues[] = [
                        'element' => $img,
                        'message' => 'Image may contain text based on filename pattern. Consider using real text with CSS styling.',
                        'suggestion' => 'Use CSS to style text instead of images of text for better accessibility and scalability.',
                    ];
                    break;
                }
            }
        }

        // Check for SVGs used as text
        $svgText = $xpath->query('//svg[.//text]');
        foreach ($svgText as $svg) {
            $textContent = '';
            $textElements = $xpath->query('.//text', $svg);
            foreach ($textElements as $text) {
                $textContent .= trim($text->textContent);
            }

            if (strlen($textContent) > 50) {
                $issues[] = [
                    'element' => $svg,
                    'message' => 'SVG contains significant text content. Consider using HTML text with CSS styling.',
                    'suggestion' => 'Use real HTML text for better accessibility unless the visual presentation is essential.',
                ];
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.5',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.5',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No obvious images of text detected.',
            );
        }
    }

    /**
     * 1.4.13 Content on Hover or Focus - Check for dismissible/hoverable content.
     */
    protected function check1_4_13(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for title attributes (browser tooltips)
        $elementsWithTitle = $xpath->query('//*[@title]');
        $titleCount = $elementsWithTitle->length;

        if ($titleCount > 10) {
            $issues[] = [
                'element' => null,
                'message' => "{$titleCount} elements use title attributes for tooltips which cannot be dismissed or hovered.",
                'suggestion' => 'Consider using custom tooltips with ARIA that can be dismissed with Escape and remain visible when hovered.',
            ];
        }

        // Check for CSS-only hover content (inline styles)
        $hoverPatterns = $xpath->query('//*[contains(@style, ":hover")]');
        if ($hoverPatterns->length > 0) {
            $issues[] = [
                'element' => null,
                'message' => 'Inline styles may create hover-triggered content.',
                'suggestion' => 'Ensure hover content is dismissible (Escape), hoverable, and persistent.',
            ];
        }

        // Check style tags for hover-triggered visibility
        $styleTags = $xpath->query('//style');
        foreach ($styleTags as $styleTag) {
            $css = $styleTag->textContent;
            if (preg_match('/:hover[^{]*\{[^}]*(visibility|display|opacity)/i', $css)) {
                $issues[] = [
                    'element' => null,
                    'message' => 'CSS shows/hides content on hover.',
                    'suggestion' => 'Ensure hover-triggered content can be dismissed without moving pointer, stays visible when hovered, and persists until dismissed.',
                ];
                break;
            }
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.13',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $issue['element'] ? $this->generateSelector($issue['element']) : null,
                elementHtml: $issue['element'] ? $dom->saveHTML($issue['element']) : null,
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '1.4.13',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'No issues detected with hover/focus triggered content.',
            );
        }
    }

    /**
     * 3.1.2 Language of Parts - Check for lang attributes on content in different languages.
     */
    protected function check3_1_2(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Check if any elements have lang attributes for different language sections
        $elementsWithLang = $xpath->query('//*[@lang][not(self::html)]');

        // Check for common multilingual patterns without lang attribute
        $foreignPatterns = [
            'hreflang' => $xpath->query('//a[@hreflang]'),
            'blockquote_cite' => $xpath->query('//blockquote[@cite]'),
        ];

        $hasMultiLanguageSupport = $elementsWithLang->length > 0;

        if ($hasMultiLanguageSupport) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.1.2',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: $elementsWithLang->length.' element(s) with lang attribute found for language sections.',
            );
        } else {
            // This is informational - we can't automatically detect foreign language text
            $this->createCheck(
                audit: $audit,
                criterionId: '3.1.2',
                criterionConfig: $config,
                status: CheckStatus::ManualReview,
                message: 'No inline language attributes detected. Verify any content in languages different from the page language has appropriate lang attributes.',
                suggestion: 'Use lang attribute on elements containing text in a different language: <span lang="fr">Bonjour</span>',
            );
        }
    }

    /**
     * 3.3.3 Error Suggestion - Check for error suggestions on form fields.
     */
    protected function check3_3_3(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        $issues = [];

        // Check for fields with validation constraints
        $constrainedInputs = $xpath->query('//input[@pattern or @min or @max or @minlength or @maxlength or @type="email" or @type="url" or @type="tel" or @type="number"]');

        foreach ($constrainedInputs as $input) {
            $hasDescribedby = $input->hasAttribute('aria-describedby');
            $hasTitle = $input->hasAttribute('title');
            $pattern = $input->getAttribute('pattern');
            $type = $input->getAttribute('type');

            // Check if there's guidance about the expected format
            if ($pattern && ! $hasDescribedby && ! $hasTitle) {
                $issues[] = [
                    'element' => $input,
                    'message' => 'Input has pattern constraint but no guidance about expected format.',
                    'suggestion' => 'Use aria-describedby to reference text explaining the expected format, or add a title attribute.',
                ];
            }

            // Check email/url/tel inputs for format guidance
            if (in_array($type, ['email', 'url', 'tel']) && ! $hasDescribedby && ! $hasTitle) {
                // Check for associated label with format hint
                $id = $input->getAttribute('id');
                $hasFormatHint = false;
                if ($id) {
                    $label = $xpath->query("//label[@for='{$id}']")->item(0);
                    if ($label && preg_match('/(format|example|e\.g\.|ex:|like)/i', $label->textContent)) {
                        $hasFormatHint = true;
                    }
                }

                if (! $hasFormatHint) {
                    $issues[] = [
                        'element' => $input,
                        'message' => "Input type \"{$type}\" may benefit from format guidance.",
                        'suggestion' => 'Consider adding a format example in the label or using aria-describedby.',
                    ];
                }
            }
        }

        if (count($issues) > 5) {
            // Summarize if too many
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.3',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: count($issues).' form inputs with constraints may lack format guidance.',
                suggestion: 'Provide format examples or descriptions for inputs with validation patterns.',
            );

            return;
        }

        foreach ($issues as $issue) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.3',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                elementSelector: $this->generateSelector($issue['element']),
                elementHtml: $dom->saveHTML($issue['element']),
                message: $issue['message'],
                suggestion: $issue['suggestion'],
            );
        }

        if (empty($issues)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.3',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Form inputs with constraints have appropriate error guidance.',
            );
        }
    }

    /**
     * 3.3.4 Error Prevention (Legal, Financial, Data) - Check for confirmation patterns.
     */
    protected function check3_3_4(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Detect forms that may involve legal/financial/data transactions
        $sensitivePatterns = [
            'payment' => '(payment|credit.?card|billing|checkout|purchase|order|buy)',
            'legal' => '(agreement|contract|terms|consent|signature|legal)',
            'account' => '(delete.?account|close.?account|cancel.?(subscription|membership))',
            'data' => '(submit|send|export|transfer)',
        ];

        $forms = $xpath->query('//form');
        $sensitiveFormsFound = [];

        foreach ($forms as $form) {
            $formHtml = strtolower($dom->saveHTML($form));
            $action = strtolower($form->getAttribute('action'));

            foreach ($sensitivePatterns as $type => $pattern) {
                if (preg_match("/{$pattern}/i", $formHtml) || preg_match("/{$pattern}/i", $action)) {
                    $sensitiveFormsFound[] = [
                        'form' => $form,
                        'type' => $type,
                    ];
                    break;
                }
            }
        }

        if (empty($sensitiveFormsFound)) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.4',
                criterionConfig: $config,
                status: CheckStatus::NotApplicable,
                message: 'No forms with legal, financial, or data-sensitive operations detected.',
            );

            return;
        }

        // Check if sensitive forms have review/confirmation patterns
        foreach ($sensitiveFormsFound as $found) {
            $form = $found['form'];
            $formHtml = strtolower($dom->saveHTML($form));

            // Look for confirmation patterns
            $hasConfirmation = preg_match('/(confirm|review|verify|check|summary)/i', $formHtml);
            $hasCheckbox = $xpath->query('.//input[@type="checkbox"]', $form)->length > 0;

            if (! $hasConfirmation && ! $hasCheckbox) {
                $this->createCheck(
                    audit: $audit,
                    criterionId: '3.3.4',
                    criterionConfig: $config,
                    status: CheckStatus::Warning,
                    elementSelector: $this->generateSelector($form),
                    message: "Form appears to handle {$found['type']} data but may lack confirmation step.",
                    suggestion: 'Provide a way to review data before submission, or add a confirmation checkbox for legal/financial transactions.',
                );
            }
        }

        // If we didn't create any warning checks, pass
        $existingChecks = $audit->checks()->where('criterion_id', '3.3.4')->count();
        if ($existingChecks === 0) {
            $this->createCheck(
                audit: $audit,
                criterionId: '3.3.4',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: 'Sensitive forms appear to have confirmation or review mechanisms.',
            );
        }
    }

    /**
     * 4.1.3 Status Messages - Check for ARIA live regions for status updates.
     */
    protected function check4_1_3(
        AccessibilityAudit $audit,
        array $config,
        DOMDocument $dom,
        DOMXPath $xpath,
        string $html,
        string $pageUrl,
    ): void {
        // Check for ARIA live regions
        $liveRegions = $xpath->query('//*[@aria-live]|//*[@role="alert"]|//*[@role="status"]|//*[@role="log"]|//*[@role="progressbar"]');

        // Check for common status message patterns
        $statusPatterns = $xpath->query('//*[contains(@class, "toast") or contains(@class, "notification") or contains(@class, "alert") or contains(@class, "message") or contains(@class, "flash")]');

        $statusWithoutLive = [];
        foreach ($statusPatterns as $element) {
            $hasLive = $element->hasAttribute('aria-live') ||
                       $element->getAttribute('role') === 'alert' ||
                       $element->getAttribute('role') === 'status';

            if (! $hasLive) {
                $statusWithoutLive[] = $element;
            }
        }

        if (count($statusWithoutLive) > 0) {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.3',
                criterionConfig: $config,
                status: CheckStatus::Warning,
                message: count($statusWithoutLive).' potential status message containers found without ARIA live region attributes.',
                suggestion: 'Add role="status" or aria-live="polite" to containers that display status messages.',
                codeSnippet: '<div role="status" aria-live="polite">Operation completed successfully.</div>',
            );

            return;
        }

        if ($liveRegions->length > 0) {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.3',
                criterionConfig: $config,
                status: CheckStatus::Pass,
                message: $liveRegions->length.' ARIA live region(s) found for status announcements.',
            );
        } else {
            $this->createCheck(
                audit: $audit,
                criterionId: '4.1.3',
                criterionConfig: $config,
                status: CheckStatus::NotApplicable,
                message: 'No status message regions detected.',
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Generate a CSS selector for an element.
     */
    protected function generateSelector(\DOMNode $element): string
    {
        if (! $element instanceof \DOMElement) {
            return '';
        }

        $parts = [];
        $current = $element;

        while ($current instanceof \DOMElement) {
            $selector = $current->nodeName;

            // Add ID if present
            $id = $current->getAttribute('id');
            if ($id) {
                $selector .= '#'.$id;
                $parts[] = $selector;
                break; // ID is unique, no need to go further
            }

            // Add classes
            $class = $current->getAttribute('class');
            if ($class) {
                $classes = array_filter(explode(' ', $class));
                if (! empty($classes)) {
                    $selector .= '.'.implode('.', array_slice($classes, 0, 2));
                }
            }

            $parts[] = $selector;
            $current = $current->parentNode;

            // Stop at body or after 4 levels
            if ($current->nodeName === 'body' || count($parts) >= 4) {
                break;
            }
        }

        return implode(' > ', array_reverse($parts));
    }

    /**
     * Get the accessible name of an element.
     */
    protected function getAccessibleName(\DOMElement $element, DOMXPath $xpath): string
    {
        // Check aria-labelledby first
        $labelledby = $element->getAttribute('aria-labelledby');
        if ($labelledby) {
            $ids = explode(' ', $labelledby);
            $names = [];
            foreach ($ids as $id) {
                $labelElement = $xpath->query('//*[@id="'.trim($id).'"]')->item(0);
                if ($labelElement) {
                    $names[] = trim($labelElement->textContent);
                }
            }
            $name = implode(' ', $names);
            if ($name) {
                return $name;
            }
        }

        // Check aria-label
        $ariaLabel = $element->getAttribute('aria-label');
        if ($ariaLabel) {
            return trim($ariaLabel);
        }

        // Check for associated label (for form elements)
        $id = $element->getAttribute('id');
        if ($id) {
            $label = $xpath->query('//label[@for="'.$id.'"]')->item(0);
            if ($label) {
                return trim($label->textContent);
            }
        }

        // Check for value attribute (for input buttons)
        $value = $element->getAttribute('value');
        if ($value && in_array($element->getAttribute('type'), ['submit', 'button', 'reset'])) {
            return trim($value);
        }

        // Check alt attribute (for images inside links/buttons)
        $img = $xpath->query('.//img[@alt]', $element)->item(0);
        if ($img) {
            return trim($img->getAttribute('alt'));
        }

        // Check title attribute (last resort)
        $title = $element->getAttribute('title');
        if ($title) {
            return trim($title);
        }

        // Return text content
        return trim($element->textContent);
    }

    /**
     * Get previous audits for regression comparison.
     *
     * @return Collection<int, AccessibilityAudit>
     */
    public function getPreviousAudits(AccessibilityAudit $audit, int $limit = 10): Collection
    {
        return AccessibilityAudit::query()
            ->where('project_id', $audit->project_id)
            ->where('url_id', $audit->url_id)
            ->where('id', '<', $audit->id)
            ->where('status', AuditStatus::Completed)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Detect regression by comparing with previous audit.
     *
     * @return array{new: int, fixed: int, recurring: int}
     */
    public function detectRegression(AccessibilityAudit $currentAudit, ?AccessibilityAudit $previousAudit): array
    {
        if (! $previousAudit) {
            return ['new' => 0, 'fixed' => 0, 'recurring' => 0];
        }

        $currentFingerprints = $currentAudit->checks()
            ->whereNotNull('fingerprint')
            ->pluck('fingerprint')
            ->toArray();

        $previousFingerprints = $previousAudit->checks()
            ->whereNotNull('fingerprint')
            ->pluck('fingerprint')
            ->toArray();

        $newIssues = array_diff($currentFingerprints, $previousFingerprints);
        $fixedIssues = array_diff($previousFingerprints, $currentFingerprints);
        $recurringIssues = array_intersect($currentFingerprints, $previousFingerprints);

        // Mark recurring issues
        $currentAudit->checks()
            ->whereIn('fingerprint', $recurringIssues)
            ->update(['is_recurring' => true]);

        return [
            'new' => count($newIssues),
            'fixed' => count($fixedIssues),
            'recurring' => count($recurringIssues),
        ];
    }
}
