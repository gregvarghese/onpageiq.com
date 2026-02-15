<?php

namespace App\Services\Accessibility;

use App\Models\AriaPattern;
use App\Models\Organization;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Collection;

class PatternMatchingService
{
    protected DOMDocument $dom;

    protected DOMXPath $xpath;

    /**
     * @var Collection<int, AriaPattern>
     */
    protected Collection $patterns;

    /**
     * Analyze HTML for ARIA pattern usage and deviations.
     *
     * @return array{patterns: array, deviations: array, summary: array}
     */
    public function analyze(string $html, ?Organization $organization = null): array
    {
        $this->initializeDom($html);
        $this->loadPatterns($organization);

        $detectedPatterns = $this->detectPatterns();
        $deviations = $this->analyzeDeviations($detectedPatterns);

        return [
            'patterns' => $detectedPatterns,
            'deviations' => $deviations,
            'summary' => $this->generateSummary($detectedPatterns, $deviations),
        ];
    }

    /**
     * Detect a single element's pattern.
     *
     * @param  array<string, mixed>  $elementData
     */
    public function detectElementPattern(array $elementData, ?Organization $organization = null): ?AriaPattern
    {
        $this->loadPatterns($organization);

        foreach ($this->patterns as $pattern) {
            if ($pattern->matchesElement($elementData)) {
                return $pattern;
            }
        }

        return null;
    }

    /**
     * Get all available patterns for an organization.
     *
     * @return Collection<int, AriaPattern>
     */
    public function getPatterns(?Organization $organization = null): Collection
    {
        if ($organization) {
            return AriaPattern::forOrganization($organization)->get();
        }

        return AriaPattern::builtIn()->get();
    }

    /**
     * Initialize the DOM parser.
     */
    protected function initializeDom(string $html): void
    {
        $this->dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $this->dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * Load patterns from database.
     */
    protected function loadPatterns(?Organization $organization = null): void
    {
        $this->patterns = $this->getPatterns($organization);
    }

    /**
     * Detect all patterns in the HTML.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function detectPatterns(): array
    {
        $detected = [];

        foreach ($this->patterns as $pattern) {
            $elements = $this->findElementsMatchingPattern($pattern);

            foreach ($elements as $element) {
                $detected[] = [
                    'pattern' => $pattern,
                    'element' => $element,
                    'elementData' => $this->extractElementData($element),
                    'selector' => $this->generateSelector($element),
                ];
            }
        }

        return $detected;
    }

    /**
     * Find all elements matching a pattern.
     *
     * @return array<int, DOMElement>
     */
    protected function findElementsMatchingPattern(AriaPattern $pattern): array
    {
        $elements = [];

        // Use detection rules to find elements
        foreach ($pattern->detection_rules as $rule) {
            $matchedElements = $this->findElementsByRule($rule);
            foreach ($matchedElements as $element) {
                // Avoid duplicates
                $key = spl_object_hash($element);
                $elements[$key] = $element;
            }
        }

        return array_values($elements);
    }

    /**
     * Find elements by a detection rule.
     *
     * @param  array<string, mixed>  $rule
     * @return array<int, DOMElement>
     */
    protected function findElementsByRule(array $rule): array
    {
        $type = $rule['type'] ?? 'role';

        return match ($type) {
            'role' => $this->findElementsByRole($rule['role'] ?? ''),
            'selector' => $this->findElementsBySelector($rule['selector'] ?? ''),
            'attribute' => $this->findElementsByAttribute($rule['attribute'] ?? '', $rule['value'] ?? null),
            'combined' => $this->findElementsByCombinedRule($rule['conditions'] ?? []),
            default => [],
        };
    }

    /**
     * Find elements by ARIA role.
     *
     * @return array<int, DOMElement>
     */
    protected function findElementsByRole(string $role): array
    {
        if (empty($role)) {
            return [];
        }

        $elements = [];
        $nodeList = $this->xpath->query("//*[@role='{$role}']");

        foreach ($nodeList as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        // Also check implicit roles for native HTML elements
        $implicitElements = $this->findElementsWithImplicitRole($role);
        foreach ($implicitElements as $element) {
            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * Find elements with implicit ARIA role from native HTML.
     *
     * @return array<int, DOMElement>
     */
    protected function findElementsWithImplicitRole(string $role): array
    {
        $elements = [];

        $implicitRoleMappings = [
            'button' => ['button', 'input[@type="button"]', 'input[@type="submit"]', 'input[@type="reset"]'],
            'link' => ['a[@href]', 'area[@href]'],
            'checkbox' => ['input[@type="checkbox"]'],
            'radio' => ['input[@type="radio"]'],
            'textbox' => ['input[@type="text"]', 'input[@type="email"]', 'input[@type="tel"]', 'input[@type="url"]', 'input[@type="search"]', 'textarea'],
            'combobox' => ['select'],
            'slider' => ['input[@type="range"]'],
            'spinbutton' => ['input[@type="number"]'],
            'navigation' => ['nav'],
            'main' => ['main'],
            'banner' => ['header[not(ancestor::article)][not(ancestor::section)]'],
            'contentinfo' => ['footer[not(ancestor::article)][not(ancestor::section)]'],
            'complementary' => ['aside'],
            'region' => ['section[@aria-label or @aria-labelledby]'],
            'search' => ['search'],
            'form' => ['form[@aria-label or @aria-labelledby or @name]'],
            'table' => ['table'],
            'img' => ['img[@alt]'],
            'article' => ['article'],
            'dialog' => ['dialog'],
            'list' => ['ul', 'ol'],
            'listitem' => ['li'],
            'meter' => ['meter'],
            'progressbar' => ['progress'],
            'figure' => ['figure'],
        ];

        if (! isset($implicitRoleMappings[$role])) {
            return [];
        }

        foreach ($implicitRoleMappings[$role] as $xpathSelector) {
            // Don't match elements that have an explicit role attribute
            $nodeList = $this->xpath->query("//{$xpathSelector}[not(@role)]");

            foreach ($nodeList as $node) {
                if ($node instanceof DOMElement) {
                    $elements[] = $node;
                }
            }
        }

        return $elements;
    }

    /**
     * Find elements by CSS-like selector.
     *
     * @return array<int, DOMElement>
     */
    protected function findElementsBySelector(string $selector): array
    {
        if (empty($selector)) {
            return [];
        }

        $elements = [];
        $xpathQuery = $this->selectorToXPath($selector);

        if ($xpathQuery) {
            $nodeList = $this->xpath->query($xpathQuery);

            foreach ($nodeList as $node) {
                if ($node instanceof DOMElement) {
                    $elements[] = $node;
                }
            }
        }

        return $elements;
    }

    /**
     * Find elements by attribute presence or value.
     *
     * @return array<int, DOMElement>
     */
    protected function findElementsByAttribute(string $attribute, ?string $value = null): array
    {
        if (empty($attribute)) {
            return [];
        }

        $elements = [];
        $query = $value !== null
            ? "//*[@{$attribute}='{$value}']"
            : "//*[@{$attribute}]";

        $nodeList = $this->xpath->query($query);

        foreach ($nodeList as $node) {
            if ($node instanceof DOMElement) {
                $elements[] = $node;
            }
        }

        return $elements;
    }

    /**
     * Find elements matching all conditions in a combined rule.
     *
     * @param  array<int, array<string, mixed>>  $conditions
     * @return array<int, DOMElement>
     */
    protected function findElementsByCombinedRule(array $conditions): array
    {
        if (empty($conditions)) {
            return [];
        }

        // Start with all elements matching first condition
        $elements = $this->findElementsByRule($conditions[0]);

        // Filter by subsequent conditions
        for ($i = 1; $i < count($conditions); $i++) {
            $elements = array_filter($elements, function (DOMElement $element) use ($conditions, $i) {
                return $this->elementMatchesRule($element, $conditions[$i]);
            });
        }

        return array_values($elements);
    }

    /**
     * Check if an element matches a rule.
     *
     * @param  array<string, mixed>  $rule
     */
    protected function elementMatchesRule(DOMElement $element, array $rule): bool
    {
        $type = $rule['type'] ?? 'role';

        return match ($type) {
            'role' => $element->getAttribute('role') === ($rule['role'] ?? ''),
            'attribute' => $this->elementHasAttribute($element, $rule['attribute'] ?? '', $rule['value'] ?? null),
            'selector' => $this->elementMatchesSelector($element, $rule['selector'] ?? ''),
            default => false,
        };
    }

    /**
     * Check if element has an attribute.
     */
    protected function elementHasAttribute(DOMElement $element, string $attribute, ?string $value = null): bool
    {
        if (! $element->hasAttribute($attribute)) {
            return false;
        }

        if ($value === null) {
            return true;
        }

        return $element->getAttribute($attribute) === $value;
    }

    /**
     * Check if element matches a selector.
     */
    protected function elementMatchesSelector(DOMElement $element, string $selector): bool
    {
        // Simple tag match
        if (preg_match('/^(\w+)/', $selector, $matches)) {
            if (strtolower($element->tagName) !== strtolower($matches[1])) {
                return false;
            }
        }

        // Attribute match
        if (preg_match('/\[([^\]=]+)(?:="([^"]+)")?\]/', $selector, $matches)) {
            $attr = $matches[1];
            $val = $matches[2] ?? null;

            return $this->elementHasAttribute($element, $attr, $val);
        }

        return true;
    }

    /**
     * Convert a simple CSS selector to XPath.
     */
    protected function selectorToXPath(string $selector): ?string
    {
        // Handle tag[attribute="value"]
        if (preg_match('/^(\w+)\[([^\]=]+)="([^"]+)"\]$/', $selector, $matches)) {
            return "//{$matches[1]}[@{$matches[2]}='{$matches[3]}']";
        }

        // Handle tag[attribute]
        if (preg_match('/^(\w+)\[([^\]]+)\]$/', $selector, $matches)) {
            return "//{$matches[1]}[@{$matches[2]}]";
        }

        // Handle [attribute="value"]
        if (preg_match('/^\[([^\]=]+)="([^"]+)"\]$/', $selector, $matches)) {
            return "//*[@{$matches[1]}='{$matches[2]}']";
        }

        // Handle [attribute]
        if (preg_match('/^\[([^\]]+)\]$/', $selector, $matches)) {
            return "//*[@{$matches[1]}]";
        }

        // Handle simple tag
        if (preg_match('/^(\w+)$/', $selector, $matches)) {
            return "//{$matches[1]}";
        }

        return null;
    }

    /**
     * Extract data from an element for analysis.
     *
     * @return array<string, mixed>
     */
    protected function extractElementData(DOMElement $element): array
    {
        $attributes = [];
        foreach ($element->attributes as $attr) {
            $attributes[$attr->name] = $attr->value;
        }

        return [
            'tag' => $element->tagName,
            'role' => $element->getAttribute('role') ?: $this->getImplicitRole($element),
            'attributes' => $attributes,
            'text' => trim($element->textContent),
            'html' => $this->getOuterHtml($element),
        ];
    }

    /**
     * Get the implicit ARIA role for a native HTML element.
     */
    protected function getImplicitRole(DOMElement $element): ?string
    {
        $tag = strtolower($element->tagName);
        $type = $element->getAttribute('type');

        $implicitRoles = [
            'button' => 'button',
            'nav' => 'navigation',
            'main' => 'main',
            'header' => 'banner',
            'footer' => 'contentinfo',
            'aside' => 'complementary',
            'article' => 'article',
            'section' => 'region',
            'dialog' => 'dialog',
            'form' => 'form',
            'ul' => 'list',
            'ol' => 'list',
            'li' => 'listitem',
            'table' => 'table',
            'meter' => 'meter',
            'progress' => 'progressbar',
            'textarea' => 'textbox',
            'select' => 'combobox',
            'search' => 'search',
        ];

        if (isset($implicitRoles[$tag])) {
            return $implicitRoles[$tag];
        }

        // Handle input types
        if ($tag === 'input') {
            $inputRoles = [
                'button' => 'button',
                'submit' => 'button',
                'reset' => 'button',
                'checkbox' => 'checkbox',
                'radio' => 'radio',
                'range' => 'slider',
                'number' => 'spinbutton',
                'text' => 'textbox',
                'email' => 'textbox',
                'tel' => 'textbox',
                'url' => 'textbox',
                'search' => 'textbox',
            ];

            return $inputRoles[$type] ?? null;
        }

        // Handle anchor with href
        if ($tag === 'a' && $element->hasAttribute('href')) {
            return 'link';
        }

        // Handle img with alt
        if ($tag === 'img' && $element->hasAttribute('alt')) {
            return 'img';
        }

        return null;
    }

    /**
     * Generate a CSS selector for an element.
     */
    protected function generateSelector(DOMElement $element): string
    {
        $parts = [];

        // Use ID if available
        if ($element->hasAttribute('id')) {
            return '#'.$element->getAttribute('id');
        }

        // Build selector from tag and attributes
        $tag = strtolower($element->tagName);
        $parts[] = $tag;

        if ($element->hasAttribute('role')) {
            $parts[] = "[role=\"{$element->getAttribute('role')}\"]";
        }

        if ($element->hasAttribute('class')) {
            $classes = explode(' ', $element->getAttribute('class'));
            $firstClass = trim($classes[0]);
            if ($firstClass) {
                $parts[] = ".{$firstClass}";
            }
        }

        return implode('', $parts);
    }

    /**
     * Get outer HTML of an element.
     */
    protected function getOuterHtml(DOMElement $element): string
    {
        $doc = new DOMDocument;
        $doc->appendChild($doc->importNode($element, true));

        $html = $doc->saveHTML();

        // Truncate long HTML
        if (strlen($html) > 500) {
            $html = substr($html, 0, 500).'...';
        }

        return trim($html);
    }

    /**
     * Analyze deviations from patterns.
     *
     * @param  array<int, array<string, mixed>>  $detectedPatterns
     * @return array<int, array<string, mixed>>
     */
    protected function analyzeDeviations(array $detectedPatterns): array
    {
        $deviations = [];

        foreach ($detectedPatterns as $detected) {
            /** @var AriaPattern $pattern */
            $pattern = $detected['pattern'];
            $elementData = $detected['elementData'];

            $patternDeviations = $pattern->getDeviations($elementData);

            if (! empty($patternDeviations)) {
                $deviations[] = [
                    'pattern' => $pattern->name,
                    'pattern_slug' => $pattern->slug,
                    'element_selector' => $detected['selector'],
                    'element_html' => $elementData['html'],
                    'issues' => $patternDeviations,
                    'documentation_url' => $pattern->documentation_url,
                    'wcag_criteria' => $pattern->wcag_criteria,
                ];
            }
        }

        return $deviations;
    }

    /**
     * Generate a summary of pattern analysis.
     *
     * @param  array<int, array<string, mixed>>  $detectedPatterns
     * @param  array<int, array<string, mixed>>  $deviations
     * @return array<string, mixed>
     */
    protected function generateSummary(array $detectedPatterns, array $deviations): array
    {
        $patternCounts = [];
        foreach ($detectedPatterns as $detected) {
            $name = $detected['pattern']->name;
            $patternCounts[$name] = ($patternCounts[$name] ?? 0) + 1;
        }

        $deviationsByType = [];
        foreach ($deviations as $deviation) {
            foreach ($deviation['issues'] as $issue) {
                $type = $issue['type'];
                $deviationsByType[$type] = ($deviationsByType[$type] ?? 0) + 1;
            }
        }

        $totalIssues = array_sum($deviationsByType);

        return [
            'total_patterns_detected' => count($detectedPatterns),
            'unique_patterns' => count($patternCounts),
            'pattern_counts' => $patternCounts,
            'total_deviations' => count($deviations),
            'total_issues' => $totalIssues,
            'deviations_by_type' => $deviationsByType,
            'compliance_score' => count($detectedPatterns) > 0
                ? max(0, round((1 - ($totalIssues / max(1, count($detectedPatterns) + $totalIssues))) * 100, 2))
                : 100,
        ];
    }
}
