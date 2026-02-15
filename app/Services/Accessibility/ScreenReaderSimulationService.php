<?php

namespace App\Services\Accessibility;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Simulates screen reader output for accessibility testing.
 *
 * This service generates a text-based representation of how a screen reader
 * would announce page content, including:
 * - Reading order simulation
 * - Landmark navigation preview
 * - Heading navigation preview
 * - Form field announcements
 * - Link and button announcements
 */
class ScreenReaderSimulationService
{
    protected DOMDocument $dom;

    protected DOMXPath $xpath;

    /**
     * ARIA role to human-readable descriptions.
     */
    protected array $roleDescriptions = [
        'banner' => 'banner',
        'navigation' => 'navigation',
        'main' => 'main',
        'complementary' => 'complementary',
        'contentinfo' => 'content information',
        'search' => 'search',
        'form' => 'form',
        'region' => 'region',
        'article' => 'article',
        'dialog' => 'dialog',
        'alertdialog' => 'alert dialog',
        'alert' => 'alert',
        'status' => 'status',
        'button' => 'button',
        'link' => 'link',
        'checkbox' => 'checkbox',
        'radio' => 'radio button',
        'textbox' => 'edit text',
        'listbox' => 'list box',
        'option' => 'option',
        'combobox' => 'combo box',
        'menu' => 'menu',
        'menuitem' => 'menu item',
        'menubar' => 'menu bar',
        'tab' => 'tab',
        'tablist' => 'tab list',
        'tabpanel' => 'tab panel',
        'tree' => 'tree',
        'treeitem' => 'tree item',
        'grid' => 'grid',
        'row' => 'row',
        'gridcell' => 'cell',
        'columnheader' => 'column header',
        'rowheader' => 'row header',
        'progressbar' => 'progress bar',
        'slider' => 'slider',
        'spinbutton' => 'spin button',
        'switch' => 'switch',
        'tooltip' => 'tooltip',
        'img' => 'image',
        'figure' => 'figure',
        'list' => 'list',
        'listitem' => 'list item',
        'table' => 'table',
        'group' => 'group',
        'heading' => 'heading',
    ];

    /**
     * Generate a screen reader simulation for the given HTML content.
     *
     * @return array{
     *     readingOrder: array,
     *     landmarks: array,
     *     headings: array,
     *     formFields: array,
     *     links: array,
     *     images: array,
     *     tables: array,
     *     announcements: array
     * }
     */
    public function simulate(string $html): array
    {
        $this->initializeDom($html);

        return [
            'readingOrder' => $this->generateReadingOrder(),
            'landmarks' => $this->extractLandmarks(),
            'headings' => $this->extractHeadings(),
            'formFields' => $this->extractFormFields(),
            'links' => $this->extractLinks(),
            'images' => $this->extractImages(),
            'tables' => $this->extractTables(),
            'announcements' => $this->generateAnnouncements(),
        ];
    }

    /**
     * Generate the reading order as a screen reader would announce it.
     */
    public function generateReadingOrder(): array
    {
        $order = [];
        $body = $this->xpath->query('//body')->item(0);

        if ($body) {
            $this->walkDom($body, $order);
        }

        return $order;
    }

    /**
     * Generate announcement text for an element.
     */
    public function generateAnnouncementForElement(DOMElement $element): ?array
    {
        $tagName = strtolower($element->tagName);
        $role = $element->getAttribute('role') ?: $this->getImplicitRole($tagName);
        $accessibleName = $this->getAccessibleName($element);

        // Skip elements that wouldn't be announced
        if ($this->shouldSkipElement($element)) {
            return null;
        }

        $announcement = [
            'element' => $tagName,
            'role' => $role,
            'name' => $accessibleName,
            'announcement' => '',
            'states' => [],
        ];

        // Build the announcement based on role
        $announcement['announcement'] = $this->buildAnnouncement($element, $role, $accessibleName);
        $announcement['states'] = $this->getAriaStates($element);

        return $announcement;
    }

    /**
     * Extract landmarks for landmark navigation simulation.
     */
    public function extractLandmarks(): array
    {
        $landmarks = [];

        // Explicit ARIA landmarks
        $landmarkRoles = ['banner', 'navigation', 'main', 'complementary', 'contentinfo', 'search', 'form', 'region'];

        foreach ($landmarkRoles as $role) {
            $elements = $this->xpath->query("//*[@role='{$role}']");
            foreach ($elements as $element) {
                $landmarks[] = $this->formatLandmark($element, $role, true);
            }
        }

        // Implicit HTML5 landmarks
        $implicitLandmarks = [
            'header' => 'banner',
            'nav' => 'navigation',
            'main' => 'main',
            'aside' => 'complementary',
            'footer' => 'contentinfo',
        ];

        foreach ($implicitLandmarks as $tag => $role) {
            $elements = $this->xpath->query("//{$tag}[not(@role)]");
            foreach ($elements as $element) {
                // Skip header/footer if nested in sectioning content
                if (($tag === 'header' || $tag === 'footer') && $this->isNestedInSectioningContent($element)) {
                    continue;
                }
                $landmarks[] = $this->formatLandmark($element, $role, false);
            }
        }

        // Sort landmarks by document order would require tracking position
        return $landmarks;
    }

    /**
     * Extract headings for heading navigation simulation.
     */
    public function extractHeadings(): array
    {
        $headings = [];

        // H1-H6 elements
        $elements = $this->xpath->query('//h1|//h2|//h3|//h4|//h5|//h6|//*[@role="heading"]');

        foreach ($elements as $element) {
            $level = $element->getAttribute('aria-level');
            if (! $level) {
                $tagName = strtolower($element->tagName);
                $level = preg_match('/h([1-6])/', $tagName, $matches) ? $matches[1] : '2';
            }

            $headings[] = [
                'level' => (int) $level,
                'text' => $this->getTextContent($element),
                'announcement' => "heading level {$level}, ".$this->getTextContent($element),
            ];
        }

        return $headings;
    }

    /**
     * Extract form fields for forms mode simulation.
     */
    public function extractFormFields(): array
    {
        $fields = [];

        $elements = $this->xpath->query('//input|//select|//textarea|//button|//*[@role="textbox"]|//*[@role="checkbox"]|//*[@role="radio"]|//*[@role="combobox"]|//*[@role="button"]');

        foreach ($elements as $element) {
            $field = $this->formatFormField($element);
            if ($field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Extract links for link navigation simulation.
     */
    public function extractLinks(): array
    {
        $links = [];

        $elements = $this->xpath->query('//a[@href]|//*[@role="link"]');

        foreach ($elements as $element) {
            $accessibleName = $this->getAccessibleName($element);
            $href = $element->getAttribute('href');

            $links[] = [
                'text' => $accessibleName ?: '[No accessible name]',
                'href' => $href,
                'announcement' => 'link, '.($accessibleName ?: 'unnamed'),
                'hasAccessibleName' => ! empty($accessibleName),
            ];
        }

        return $links;
    }

    /**
     * Extract images for image navigation simulation.
     */
    public function extractImages(): array
    {
        $images = [];

        $elements = $this->xpath->query('//img|//*[@role="img"]');

        foreach ($elements as $element) {
            $alt = $element->getAttribute('alt');
            $ariaLabel = $element->getAttribute('aria-label');
            $src = $element->getAttribute('src');

            $accessibleName = $ariaLabel ?: $alt;
            $isDecorative = $alt === '' && ! $ariaLabel;

            if (! $isDecorative) {
                $images[] = [
                    'alt' => $alt,
                    'src' => $src ? basename($src) : null,
                    'announcement' => $accessibleName ? "image, {$accessibleName}" : 'image, no description',
                    'hasDescription' => ! empty($accessibleName),
                ];
            }
        }

        return $images;
    }

    /**
     * Extract tables for table navigation simulation.
     */
    public function extractTables(): array
    {
        $tables = [];

        $elements = $this->xpath->query('//table|//*[@role="table"]|//*[@role="grid"]');

        foreach ($elements as $element) {
            $caption = '';
            $captionEl = $this->xpath->query('.//caption', $element)->item(0);
            if ($captionEl) {
                $caption = $this->getTextContent($captionEl);
            }

            $rows = $this->xpath->query('.//tr|.//*[@role="row"]', $element)->length;
            $cols = 0;
            $firstRow = $this->xpath->query('.//tr|.//*[@role="row"]', $element)->item(0);
            if ($firstRow) {
                $cols = $this->xpath->query('.//th|.//td|.//*[@role="gridcell"]|.//*[@role="columnheader"]', $firstRow)->length;
            }

            $tables[] = [
                'caption' => $caption,
                'rows' => $rows,
                'columns' => $cols,
                'announcement' => ($caption ? "{$caption}, " : '')."table with {$rows} rows and {$cols} columns",
            ];
        }

        return $tables;
    }

    /**
     * Generate a list of all announcements in reading order.
     */
    public function generateAnnouncements(): array
    {
        $announcements = [];
        $body = $this->xpath->query('//body')->item(0);

        if ($body) {
            $this->collectAnnouncements($body, $announcements);
        }

        return array_slice($announcements, 0, 100); // Limit to first 100
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
     * Walk the DOM tree and collect reading order.
     */
    protected function walkDom(DOMElement $element, array &$order, int $depth = 0): void
    {
        if ($depth > 50) {
            return;
        } // Prevent infinite recursion

        // Skip aria-hidden elements and all their descendants
        if ($this->isHiddenSubtree($element)) {
            return;
        }

        $announcement = $this->generateAnnouncementForElement($element);
        if ($announcement && ! empty($announcement['announcement'])) {
            $order[] = $announcement;
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->walkDom($child, $order, $depth + 1);
            }
        }
    }

    /**
     * Collect announcements recursively.
     */
    protected function collectAnnouncements(DOMElement $element, array &$announcements, int $depth = 0): void
    {
        if ($depth > 50) {
            return;
        }

        // Skip aria-hidden elements and all their descendants
        if ($this->isHiddenSubtree($element)) {
            return;
        }

        $announcement = $this->generateAnnouncementForElement($element);
        if ($announcement && ! empty($announcement['announcement'])) {
            $announcements[] = $announcement['announcement'];
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->collectAnnouncements($child, $announcements, $depth + 1);
            }
        }
    }

    /**
     * Check if element is in a hidden subtree (aria-hidden or display:none).
     */
    protected function isHiddenSubtree(DOMElement $element): bool
    {
        // Check aria-hidden
        if ($element->getAttribute('aria-hidden') === 'true') {
            return true;
        }

        // Check hidden attribute
        if ($element->hasAttribute('hidden')) {
            return true;
        }

        // Check inline style for display:none
        $style = $element->getAttribute('style');
        if ($style && (str_contains($style, 'display:none') || str_contains($style, 'display: none'))) {
            return true;
        }

        return false;
    }

    /**
     * Build the announcement text for an element.
     */
    protected function buildAnnouncement(DOMElement $element, string $role, string $accessibleName): string
    {
        $parts = [];

        // Add role description if applicable
        if (isset($this->roleDescriptions[$role])) {
            $parts[] = $this->roleDescriptions[$role];
        }

        // Add accessible name
        if ($accessibleName) {
            $parts[] = $accessibleName;
        }

        // Add states
        $states = $this->getAriaStates($element);
        foreach ($states as $state => $value) {
            if ($value === 'true') {
                $parts[] = $state;
            } elseif ($value === 'false' && in_array($state, ['expanded', 'checked', 'selected', 'pressed'])) {
                $parts[] = "not {$state}";
            }
        }

        return implode(', ', array_filter($parts));
    }

    /**
     * Get the accessible name for an element.
     */
    protected function getAccessibleName(DOMElement $element): string
    {
        // aria-labelledby takes precedence
        $labelledby = $element->getAttribute('aria-labelledby');
        if ($labelledby) {
            $ids = explode(' ', $labelledby);
            $texts = [];
            foreach ($ids as $id) {
                $referenced = $this->xpath->query("//*[@id='{$id}']")->item(0);
                if ($referenced) {
                    $texts[] = $this->getTextContent($referenced);
                }
            }

            return implode(' ', $texts);
        }

        // aria-label
        $ariaLabel = $element->getAttribute('aria-label');
        if ($ariaLabel) {
            return $ariaLabel;
        }

        // For inputs, check for associated label
        $tagName = strtolower($element->tagName);
        if (in_array($tagName, ['input', 'select', 'textarea'])) {
            $id = $element->getAttribute('id');
            if ($id) {
                $label = $this->xpath->query("//label[@for='{$id}']")->item(0);
                if ($label) {
                    return $this->getTextContent($label);
                }
            }
        }

        // alt text for images
        if ($tagName === 'img') {
            $alt = $element->getAttribute('alt');
            if ($alt) {
                return $alt;
            }
        }

        // title attribute as fallback
        $title = $element->getAttribute('title');
        if ($title) {
            return $title;
        }

        // Text content for buttons, links, and headings
        if (in_array($tagName, ['button', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
            return trim($this->getTextContent($element));
        }

        return '';
    }

    /**
     * Get the implicit ARIA role for an HTML element.
     */
    protected function getImplicitRole(string $tagName): string
    {
        return match ($tagName) {
            'header' => 'banner',
            'nav' => 'navigation',
            'main' => 'main',
            'aside' => 'complementary',
            'footer' => 'contentinfo',
            'form' => 'form',
            'article' => 'article',
            'section' => 'region',
            'button' => 'button',
            'a' => 'link',
            'img' => 'img',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => 'heading',
            'ul', 'ol' => 'list',
            'li' => 'listitem',
            'table' => 'table',
            'tr' => 'row',
            'th' => 'columnheader',
            'td' => 'cell',
            'input' => 'textbox',
            'select' => 'combobox',
            'textarea' => 'textbox',
            default => '',
        };
    }

    /**
     * Get ARIA states for an element.
     */
    protected function getAriaStates(DOMElement $element): array
    {
        $states = [];
        $stateAttributes = [
            'aria-expanded',
            'aria-selected',
            'aria-checked',
            'aria-pressed',
            'aria-disabled',
            'aria-hidden',
            'aria-required',
            'aria-invalid',
            'aria-busy',
            'aria-current',
        ];

        foreach ($stateAttributes as $attr) {
            $value = $element->getAttribute($attr);
            if ($value !== '') {
                $stateName = str_replace('aria-', '', $attr);
                $states[$stateName] = $value;
            }
        }

        // Check native states
        if ($element->hasAttribute('disabled')) {
            $states['disabled'] = 'true';
        }
        if ($element->hasAttribute('required')) {
            $states['required'] = 'true';
        }
        if ($element->hasAttribute('checked')) {
            $states['checked'] = 'true';
        }

        return $states;
    }

    /**
     * Check if an element should be skipped in announcements.
     */
    protected function shouldSkipElement(DOMElement $element): bool
    {
        // Skip aria-hidden elements
        if ($element->getAttribute('aria-hidden') === 'true') {
            return true;
        }

        // Skip hidden elements
        if ($element->getAttribute('hidden') !== '') {
            return true;
        }

        // Skip elements with display:none or visibility:hidden in style
        $style = $element->getAttribute('style');
        if ($style && (str_contains($style, 'display:none') || str_contains($style, 'display: none') ||
                       str_contains($style, 'visibility:hidden') || str_contains($style, 'visibility: hidden'))) {
            return true;
        }

        // Skip script, style, template elements
        $tagName = strtolower($element->tagName);
        if (in_array($tagName, ['script', 'style', 'template', 'noscript', 'meta', 'link'])) {
            return true;
        }

        // Skip presentational elements without accessible name
        $role = $element->getAttribute('role');
        if ($role === 'presentation' || $role === 'none') {
            return true;
        }

        return false;
    }

    /**
     * Check if element is nested in sectioning content.
     */
    protected function isNestedInSectioningContent(DOMElement $element): bool
    {
        $parent = $element->parentNode;
        while ($parent instanceof DOMElement) {
            $tagName = strtolower($parent->tagName);
            if (in_array($tagName, ['article', 'aside', 'main', 'nav', 'section'])) {
                return true;
            }
            $parent = $parent->parentNode;
        }

        return false;
    }

    /**
     * Format a landmark for output.
     */
    protected function formatLandmark(DOMElement $element, string $role, bool $explicit): array
    {
        $accessibleName = $this->getAccessibleName($element);
        $roleDescription = $this->roleDescriptions[$role] ?? $role;

        return [
            'role' => $role,
            'name' => $accessibleName,
            'explicit' => $explicit,
            'tagName' => strtolower($element->tagName),
            'announcement' => $accessibleName
                ? "{$roleDescription}, {$accessibleName}"
                : $roleDescription,
        ];
    }

    /**
     * Format a form field for output.
     */
    protected function formatFormField(DOMElement $element): ?array
    {
        $tagName = strtolower($element->tagName);
        $type = $element->getAttribute('type') ?: 'text';
        $accessibleName = $this->getAccessibleName($element);
        $role = $element->getAttribute('role') ?: $this->getInputRole($tagName, $type);

        // Skip hidden inputs
        if ($type === 'hidden') {
            return null;
        }

        $field = [
            'type' => $type,
            'name' => $accessibleName ?: '[No label]',
            'role' => $role,
            'required' => $element->hasAttribute('required') || $element->getAttribute('aria-required') === 'true',
            'hasLabel' => ! empty($accessibleName),
        ];

        // Build announcement
        $announcement = [];
        if ($accessibleName) {
            $announcement[] = $accessibleName;
        }
        $announcement[] = $this->roleDescriptions[$role] ?? $role;

        if ($field['required']) {
            $announcement[] = 'required';
        }

        $field['announcement'] = implode(', ', $announcement);

        return $field;
    }

    /**
     * Get the role for an input element.
     */
    protected function getInputRole(string $tagName, string $type): string
    {
        if ($tagName === 'select') {
            return 'combobox';
        }
        if ($tagName === 'textarea') {
            return 'textbox';
        }
        if ($tagName === 'button') {
            return 'button';
        }

        return match ($type) {
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'button', 'submit', 'reset' => 'button',
            'range' => 'slider',
            'search' => 'searchbox',
            default => 'textbox',
        };
    }

    /**
     * Get text content of an element.
     */
    protected function getTextContent(DOMElement $element): string
    {
        return trim(preg_replace('/\s+/', ' ', $element->textContent));
    }
}
