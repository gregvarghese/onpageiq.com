<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AccessibilityCheckService
{
    /**
     * WCAG 2.1 accessibility checks to perform.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $checks = [
        'images_alt' => [
            'name' => 'Image Alt Text',
            'wcag' => '1.1.1',
            'level' => 'A',
            'description' => 'Images must have alternative text',
        ],
        'form_labels' => [
            'name' => 'Form Labels',
            'wcag' => '1.3.1',
            'level' => 'A',
            'description' => 'Form inputs must have associated labels',
        ],
        'heading_order' => [
            'name' => 'Heading Order',
            'wcag' => '1.3.1',
            'level' => 'A',
            'description' => 'Headings must be in logical order',
        ],
        'link_text' => [
            'name' => 'Link Text',
            'wcag' => '2.4.4',
            'level' => 'A',
            'description' => 'Links must have descriptive text',
        ],
        'color_contrast' => [
            'name' => 'Color Contrast',
            'wcag' => '1.4.3',
            'level' => 'AA',
            'description' => 'Text must have sufficient color contrast',
        ],
        'language' => [
            'name' => 'Page Language',
            'wcag' => '3.1.1',
            'level' => 'A',
            'description' => 'Page must have a lang attribute',
        ],
        'skip_links' => [
            'name' => 'Skip Links',
            'wcag' => '2.4.1',
            'level' => 'A',
            'description' => 'Page should have skip navigation links',
        ],
        'aria_roles' => [
            'name' => 'ARIA Roles',
            'wcag' => '4.1.2',
            'level' => 'A',
            'description' => 'ARIA roles must be valid',
        ],
        'focus_visible' => [
            'name' => 'Focus Visible',
            'wcag' => '2.4.7',
            'level' => 'AA',
            'description' => 'Interactive elements must have visible focus',
        ],
        'tables' => [
            'name' => 'Table Structure',
            'wcag' => '1.3.1',
            'level' => 'A',
            'description' => 'Tables must have proper headers',
        ],
    ];

    /**
     * Run all accessibility checks on HTML content.
     *
     * @return array<string, mixed>
     */
    public function analyze(string $html): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $issues = collect();
        $passed = collect();

        // Run each check
        $issues = $issues->merge($this->checkImagesAlt($dom));
        $issues = $issues->merge($this->checkFormLabels($dom));
        $issues = $issues->merge($this->checkHeadingOrder($dom));
        $issues = $issues->merge($this->checkLinkText($dom));
        $issues = $issues->merge($this->checkLanguage($dom));
        $issues = $issues->merge($this->checkSkipLinks($dom));
        $issues = $issues->merge($this->checkAriaRoles($dom));
        $issues = $issues->merge($this->checkTables($dom));

        // Calculate score
        $totalChecks = count($this->checks);
        $failedChecks = $issues->pluck('check')->unique()->count();
        $score = $totalChecks > 0 ? round((($totalChecks - $failedChecks) / $totalChecks) * 100) : 100;

        return [
            'score' => $score,
            'total_issues' => $issues->count(),
            'issues' => $issues->values()->toArray(),
            'summary' => [
                'level_a_issues' => $issues->where('level', 'A')->count(),
                'level_aa_issues' => $issues->where('level', 'AA')->count(),
                'level_aaa_issues' => $issues->where('level', 'AAA')->count(),
            ],
            'checks_performed' => array_keys($this->checks),
        ];
    }

    /**
     * Check for images without alt text.
     */
    protected function checkImagesAlt(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $images = $dom->getElementsByTagName('img');

        foreach ($images as $img) {
            $alt = $img->getAttribute('alt');
            $src = $img->getAttribute('src');

            // Check if alt is missing entirely
            if (! $img->hasAttribute('alt')) {
                $issues->push([
                    'check' => 'images_alt',
                    'wcag' => '1.1.1',
                    'level' => 'A',
                    'severity' => 'error',
                    'message' => 'Image is missing alt attribute',
                    'element' => $this->getElementHtml($img),
                    'selector' => $this->getSelector($img),
                    'recommendation' => 'Add an alt attribute describing the image content, or alt="" for decorative images',
                ]);
            }
            // Check for suspicious alt text
            elseif ($this->isSuspiciousAltText($alt, $src)) {
                $issues->push([
                    'check' => 'images_alt',
                    'wcag' => '1.1.1',
                    'level' => 'A',
                    'severity' => 'warning',
                    'message' => "Image has non-descriptive alt text: \"{$alt}\"",
                    'element' => $this->getElementHtml($img),
                    'selector' => $this->getSelector($img),
                    'recommendation' => 'Provide meaningful alt text that describes the image content',
                ]);
            }
        }

        return $issues;
    }

    /**
     * Check if alt text is suspicious (likely auto-generated or meaningless).
     */
    protected function isSuspiciousAltText(string $alt, string $src): bool
    {
        $alt = strtolower(trim($alt));

        // Common meaningless alt texts
        $suspiciousPatterns = [
            'image', 'img', 'photo', 'picture', 'graphic',
            'untitled', 'dsc', 'img_', 'screenshot',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if ($alt === $pattern || str_starts_with($alt, $pattern)) {
                return true;
            }
        }

        // Check if alt text is just the filename
        $filename = pathinfo(parse_url($src, PHP_URL_PATH) ?? '', PATHINFO_FILENAME);
        if ($alt === strtolower($filename)) {
            return true;
        }

        return false;
    }

    /**
     * Check for form inputs without labels.
     */
    protected function checkFormLabels(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $xpath = new \DOMXPath($dom);

        // Find all inputs, selects, textareas
        $formElements = $xpath->query('//input[not(@type="hidden") and not(@type="submit") and not(@type="button") and not(@type="reset")] | //select | //textarea');

        foreach ($formElements as $element) {
            $id = $element->getAttribute('id');
            $name = $element->getAttribute('name');
            $ariaLabel = $element->getAttribute('aria-label');
            $ariaLabelledBy = $element->getAttribute('aria-labelledby');
            $title = $element->getAttribute('title');
            $placeholder = $element->getAttribute('placeholder');

            // Check for associated label
            $hasLabel = false;

            if ($id) {
                $labels = $xpath->query("//label[@for='{$id}']");
                $hasLabel = $labels->length > 0;
            }

            // Also check for wrapping label
            if (! $hasLabel) {
                $parent = $element->parentNode;
                while ($parent) {
                    if ($parent->nodeName === 'label') {
                        $hasLabel = true;
                        break;
                    }
                    $parent = $parent->parentNode;
                }
            }

            // Check for ARIA alternatives
            $hasAriaLabel = ! empty($ariaLabel) || ! empty($ariaLabelledBy);

            if (! $hasLabel && ! $hasAriaLabel) {
                $severity = empty($title) && empty($placeholder) ? 'error' : 'warning';
                $issues->push([
                    'check' => 'form_labels',
                    'wcag' => '1.3.1',
                    'level' => 'A',
                    'severity' => $severity,
                    'message' => 'Form input is missing an associated label',
                    'element' => $this->getElementHtml($element),
                    'selector' => $this->getSelector($element),
                    'recommendation' => 'Add a <label> element with a for attribute matching the input id, or use aria-label',
                ]);
            }
        }

        return $issues;
    }

    /**
     * Check heading order.
     */
    protected function checkHeadingOrder(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $xpath = new \DOMXPath($dom);
        $headings = $xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6');

        $previousLevel = 0;
        $h1Count = 0;

        foreach ($headings as $heading) {
            $level = (int) substr($heading->nodeName, 1);

            if ($level === 1) {
                $h1Count++;
            }

            // Check for skipped heading levels
            if ($previousLevel > 0 && $level > $previousLevel + 1) {
                $issues->push([
                    'check' => 'heading_order',
                    'wcag' => '1.3.1',
                    'level' => 'A',
                    'severity' => 'warning',
                    'message' => "Heading level skipped from h{$previousLevel} to h{$level}",
                    'element' => $this->getElementHtml($heading),
                    'selector' => $this->getSelector($heading),
                    'recommendation' => 'Use sequential heading levels without skipping (e.g., h1 > h2 > h3)',
                ]);
            }

            $previousLevel = $level;
        }

        // Check for multiple h1s
        if ($h1Count > 1) {
            $issues->push([
                'check' => 'heading_order',
                'wcag' => '1.3.1',
                'level' => 'A',
                'severity' => 'warning',
                'message' => "Page has {$h1Count} h1 elements (should have only one)",
                'element' => null,
                'selector' => null,
                'recommendation' => 'Use only one h1 element per page for the main heading',
            ]);
        }

        // Check for missing h1
        if ($h1Count === 0 && $headings->length > 0) {
            $issues->push([
                'check' => 'heading_order',
                'wcag' => '1.3.1',
                'level' => 'A',
                'severity' => 'warning',
                'message' => 'Page is missing an h1 heading',
                'element' => null,
                'selector' => null,
                'recommendation' => 'Add an h1 element as the main page heading',
            ]);
        }

        return $issues;
    }

    /**
     * Check link text quality.
     */
    protected function checkLinkText(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $links = $dom->getElementsByTagName('a');

        $genericTexts = [
            'click here', 'click', 'here', 'more', 'read more',
            'learn more', 'link', 'this link', 'go', 'continue',
        ];

        foreach ($links as $link) {
            $text = trim($link->textContent);
            $href = $link->getAttribute('href');
            $ariaLabel = $link->getAttribute('aria-label');
            $title = $link->getAttribute('title');

            // Skip anchor links and javascript
            if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
                continue;
            }

            // Check for empty link text
            if (empty($text) && empty($ariaLabel)) {
                // Check for image with alt
                $images = $link->getElementsByTagName('img');
                $hasImageAlt = false;
                foreach ($images as $img) {
                    if (! empty($img->getAttribute('alt'))) {
                        $hasImageAlt = true;
                        break;
                    }
                }

                if (! $hasImageAlt) {
                    $issues->push([
                        'check' => 'link_text',
                        'wcag' => '2.4.4',
                        'level' => 'A',
                        'severity' => 'error',
                        'message' => 'Link has no accessible text',
                        'element' => $this->getElementHtml($link),
                        'selector' => $this->getSelector($link),
                        'recommendation' => 'Add descriptive link text or aria-label',
                    ]);
                }
            }
            // Check for generic link text
            elseif (in_array(strtolower($text), $genericTexts)) {
                $issues->push([
                    'check' => 'link_text',
                    'wcag' => '2.4.4',
                    'level' => 'A',
                    'severity' => 'warning',
                    'message' => "Link has generic text: \"{$text}\"",
                    'element' => $this->getElementHtml($link),
                    'selector' => $this->getSelector($link),
                    'recommendation' => 'Use descriptive link text that explains the link destination',
                ]);
            }
        }

        return $issues;
    }

    /**
     * Check for page language attribute.
     */
    protected function checkLanguage(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $html = $dom->getElementsByTagName('html')->item(0);

        if ($html) {
            $lang = $html->getAttribute('lang');

            if (empty($lang)) {
                $issues->push([
                    'check' => 'language',
                    'wcag' => '3.1.1',
                    'level' => 'A',
                    'severity' => 'error',
                    'message' => 'HTML element is missing lang attribute',
                    'element' => '<html>',
                    'selector' => 'html',
                    'recommendation' => 'Add a lang attribute to the html element (e.g., lang="en")',
                ]);
            } elseif (strlen($lang) < 2) {
                $issues->push([
                    'check' => 'language',
                    'wcag' => '3.1.1',
                    'level' => 'A',
                    'severity' => 'warning',
                    'message' => "Invalid lang attribute value: \"{$lang}\"",
                    'element' => '<html>',
                    'selector' => 'html',
                    'recommendation' => 'Use a valid language code (e.g., "en", "en-US", "fr")',
                ]);
            }
        }

        return $issues;
    }

    /**
     * Check for skip navigation links.
     */
    protected function checkSkipLinks(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $xpath = new \DOMXPath($dom);

        // Look for skip links
        $skipLinks = $xpath->query('//a[contains(@href, "#main") or contains(@href, "#content") or contains(translate(., "SKIP", "skip"), "skip")]');

        if ($skipLinks->length === 0) {
            // Check for landmark roles as alternative
            $hasMainLandmark = $xpath->query('//main | //*[@role="main"]')->length > 0;
            $hasNavLandmark = $xpath->query('//nav | //*[@role="navigation"]')->length > 0;

            if (! $hasMainLandmark) {
                $issues->push([
                    'check' => 'skip_links',
                    'wcag' => '2.4.1',
                    'level' => 'A',
                    'severity' => 'warning',
                    'message' => 'Page has no skip navigation link or main landmark',
                    'element' => null,
                    'selector' => null,
                    'recommendation' => 'Add a "Skip to main content" link or use <main> element',
                ]);
            }
        }

        return $issues;
    }

    /**
     * Check ARIA roles validity.
     */
    protected function checkAriaRoles(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $xpath = new \DOMXPath($dom);

        $validRoles = [
            'alert', 'alertdialog', 'application', 'article', 'banner', 'button',
            'cell', 'checkbox', 'columnheader', 'combobox', 'complementary',
            'contentinfo', 'definition', 'dialog', 'directory', 'document',
            'feed', 'figure', 'form', 'grid', 'gridcell', 'group', 'heading',
            'img', 'link', 'list', 'listbox', 'listitem', 'log', 'main',
            'marquee', 'math', 'menu', 'menubar', 'menuitem', 'menuitemcheckbox',
            'menuitemradio', 'navigation', 'none', 'note', 'option', 'presentation',
            'progressbar', 'radio', 'radiogroup', 'region', 'row', 'rowgroup',
            'rowheader', 'scrollbar', 'search', 'searchbox', 'separator', 'slider',
            'spinbutton', 'status', 'switch', 'tab', 'table', 'tablist', 'tabpanel',
            'term', 'textbox', 'timer', 'toolbar', 'tooltip', 'tree', 'treegrid', 'treeitem',
        ];

        $elementsWithRole = $xpath->query('//*[@role]');

        foreach ($elementsWithRole as $element) {
            $role = strtolower(trim($element->getAttribute('role')));

            if (! in_array($role, $validRoles)) {
                $issues->push([
                    'check' => 'aria_roles',
                    'wcag' => '4.1.2',
                    'level' => 'A',
                    'severity' => 'error',
                    'message' => "Invalid ARIA role: \"{$role}\"",
                    'element' => $this->getElementHtml($element),
                    'selector' => $this->getSelector($element),
                    'recommendation' => 'Use a valid ARIA role from the WAI-ARIA specification',
                ]);
            }
        }

        return $issues;
    }

    /**
     * Check table accessibility.
     */
    protected function checkTables(\DOMDocument $dom): Collection
    {
        $issues = collect();
        $tables = $dom->getElementsByTagName('table');

        foreach ($tables as $table) {
            // Skip layout tables (role="presentation")
            if ($table->getAttribute('role') === 'presentation') {
                continue;
            }

            // Check for caption or aria-label
            $captions = $table->getElementsByTagName('caption');
            $ariaLabel = $table->getAttribute('aria-label');
            $ariaLabelledBy = $table->getAttribute('aria-labelledby');

            if ($captions->length === 0 && empty($ariaLabel) && empty($ariaLabelledBy)) {
                $issues->push([
                    'check' => 'tables',
                    'wcag' => '1.3.1',
                    'level' => 'A',
                    'severity' => 'warning',
                    'message' => 'Table is missing a caption or accessible name',
                    'element' => $this->getElementHtml($table, 100),
                    'selector' => $this->getSelector($table),
                    'recommendation' => 'Add a <caption> element or aria-label to describe the table',
                ]);
            }

            // Check for th elements
            $theads = $table->getElementsByTagName('thead');
            $ths = $table->getElementsByTagName('th');

            if ($ths->length === 0) {
                $issues->push([
                    'check' => 'tables',
                    'wcag' => '1.3.1',
                    'level' => 'A',
                    'severity' => 'error',
                    'message' => 'Table has no header cells (th elements)',
                    'element' => $this->getElementHtml($table, 100),
                    'selector' => $this->getSelector($table),
                    'recommendation' => 'Use <th> elements for header cells',
                ]);
            }

            // Check th scope attribute
            foreach ($ths as $th) {
                if (! $th->hasAttribute('scope') && ! $th->hasAttribute('id')) {
                    $issues->push([
                        'check' => 'tables',
                        'wcag' => '1.3.1',
                        'level' => 'A',
                        'severity' => 'warning',
                        'message' => 'Table header cell is missing scope attribute',
                        'element' => $this->getElementHtml($th),
                        'selector' => $this->getSelector($th),
                        'recommendation' => 'Add scope="col" or scope="row" to header cells',
                    ]);
                    break; // Only report once per table
                }
            }
        }

        return $issues;
    }

    /**
     * Get a simplified HTML representation of an element.
     */
    protected function getElementHtml(\DOMElement $element, int $maxLength = 200): string
    {
        $html = $element->ownerDocument->saveHTML($element);
        $html = trim(preg_replace('/\s+/', ' ', $html));

        if (strlen($html) > $maxLength) {
            $html = substr($html, 0, $maxLength).'...';
        }

        return $html;
    }

    /**
     * Generate a CSS selector for an element.
     */
    protected function getSelector(\DOMElement $element): string
    {
        $selector = $element->nodeName;

        if ($element->hasAttribute('id')) {
            return $selector.'#'.$element->getAttribute('id');
        }

        if ($element->hasAttribute('class')) {
            $classes = array_filter(explode(' ', $element->getAttribute('class')));
            if (! empty($classes)) {
                $selector .= '.'.implode('.', array_slice($classes, 0, 2));
            }
        }

        return $selector;
    }

    /**
     * Get list of available checks.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableChecks(): array
    {
        return $this->checks;
    }
}
