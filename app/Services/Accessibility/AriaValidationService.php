<?php

namespace App\Services\Accessibility;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Validates ARIA usage against WAI-ARIA 1.2 specification.
 *
 * Checks for:
 * - Required ARIA attributes for roles
 * - Valid ARIA attribute values
 * - Prohibited ARIA attribute combinations
 * - Allowed ARIA roles for HTML elements
 * - ARIA relationship integrity (labelledby, describedby, controls, owns)
 */
class AriaValidationService
{
    protected DOMDocument $dom;

    protected DOMXPath $xpath;

    /**
     * Required attributes for ARIA roles (WAI-ARIA 1.2).
     */
    protected array $requiredAttributes = [
        'checkbox' => ['aria-checked'],
        'combobox' => ['aria-expanded'],
        'heading' => ['aria-level'],
        'meter' => ['aria-valuenow'],
        'option' => [], // aria-selected only in certain contexts
        'radio' => ['aria-checked'],
        'scrollbar' => ['aria-controls', 'aria-valuenow'],
        'separator' => [], // aria-valuenow only when focusable
        'slider' => ['aria-valuenow'],
        'spinbutton' => ['aria-valuenow'],
        'switch' => ['aria-checked'],
    ];

    /**
     * Allowed children for ARIA roles.
     */
    protected array $allowedChildren = [
        'list' => ['listitem', 'group'],
        'listbox' => ['option', 'group'],
        'menu' => ['menuitem', 'menuitemcheckbox', 'menuitemradio', 'group'],
        'menubar' => ['menuitem', 'menuitemcheckbox', 'menuitemradio', 'group'],
        'radiogroup' => ['radio'],
        'tablist' => ['tab'],
        'tree' => ['treeitem', 'group'],
        'grid' => ['row', 'rowgroup'],
        'table' => ['row', 'rowgroup'],
        'row' => ['cell', 'gridcell', 'columnheader', 'rowheader'],
        'rowgroup' => ['row'],
    ];

    /**
     * HTML elements that should not have certain roles.
     */
    protected array $prohibitedRoles = [
        'a[href]' => ['button'], // Use actual button instead
        'button' => ['link'], // Use actual link instead
        'input[type="checkbox"]' => ['button', 'link'],
        'input[type="radio"]' => ['button', 'link'],
        'select' => ['button', 'textbox'],
    ];

    /**
     * Abstract roles that should never be used.
     */
    protected array $abstractRoles = [
        'command',
        'composite',
        'input',
        'landmark',
        'range',
        'roletype',
        'section',
        'sectionhead',
        'select',
        'structure',
        'widget',
        'window',
    ];

    /**
     * ARIA attributes that require ID references.
     */
    protected array $idReferenceAttributes = [
        'aria-activedescendant',
        'aria-controls',
        'aria-describedby',
        'aria-details',
        'aria-errormessage',
        'aria-flowto',
        'aria-labelledby',
        'aria-owns',
    ];

    /**
     * Valid values for ARIA state/property attributes.
     */
    protected array $validValues = [
        'aria-autocomplete' => ['inline', 'list', 'both', 'none'],
        'aria-checked' => ['true', 'false', 'mixed'],
        'aria-current' => ['page', 'step', 'location', 'date', 'time', 'true', 'false'],
        'aria-dropeffect' => ['copy', 'execute', 'link', 'move', 'none', 'popup'],
        'aria-expanded' => ['true', 'false'],
        'aria-haspopup' => ['true', 'false', 'menu', 'listbox', 'tree', 'grid', 'dialog'],
        'aria-hidden' => ['true', 'false'],
        'aria-invalid' => ['true', 'false', 'grammar', 'spelling'],
        'aria-live' => ['assertive', 'off', 'polite'],
        'aria-orientation' => ['horizontal', 'vertical'],
        'aria-pressed' => ['true', 'false', 'mixed'],
        'aria-relevant' => ['additions', 'additions text', 'all', 'removals', 'text'],
        'aria-selected' => ['true', 'false'],
        'aria-sort' => ['ascending', 'descending', 'none', 'other'],
    ];

    /**
     * Validate ARIA usage in the given HTML content.
     *
     * @return array{
     *     valid: bool,
     *     issues: array,
     *     summary: array
     * }
     */
    public function validate(string $html): array
    {
        $this->initializeDom($html);

        $issues = [];

        // Run all validation checks
        $issues = array_merge($issues, $this->checkRequiredAttributes());
        $issues = array_merge($issues, $this->checkValidAttributeValues());
        $issues = array_merge($issues, $this->checkIdReferences());
        $issues = array_merge($issues, $this->checkAbstractRoles());
        $issues = array_merge($issues, $this->checkParentChildRelationships());
        $issues = array_merge($issues, $this->checkProhibitedAttributes());
        $issues = array_merge($issues, $this->checkDeprecatedAttributes());
        $issues = array_merge($issues, $this->checkRedundantRoles());

        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'summary' => $this->generateSummary($issues),
        ];
    }

    /**
     * Check for required ARIA attributes based on role.
     */
    public function checkRequiredAttributes(): array
    {
        $issues = [];

        foreach ($this->requiredAttributes as $role => $attributes) {
            $elements = $this->xpath->query("//*[@role='{$role}']");

            foreach ($elements as $element) {
                foreach ($attributes as $attr) {
                    if (! $element->hasAttribute($attr)) {
                        $issues[] = [
                            'type' => 'missing-required-attribute',
                            'criterion' => '4.1.2',
                            'severity' => 'error',
                            'message' => "Element with role=\"{$role}\" is missing required attribute \"{$attr}\"",
                            'element' => $this->getElementDescription($element),
                            'suggestion' => "Add the {$attr} attribute to this element",
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check for valid ARIA attribute values.
     */
    public function checkValidAttributeValues(): array
    {
        $issues = [];

        foreach ($this->validValues as $attr => $allowedValues) {
            $elements = $this->xpath->query("//*[@{$attr}]");

            foreach ($elements as $element) {
                $value = strtolower($element->getAttribute($attr));

                // Handle space-separated token lists
                if ($attr === 'aria-relevant') {
                    $tokens = explode(' ', $value);
                    foreach ($tokens as $token) {
                        if (! in_array(trim($token), ['additions', 'removals', 'text', 'all'])) {
                            $issues[] = $this->createInvalidValueIssue($element, $attr, $value, $allowedValues);
                            break;
                        }
                    }
                } elseif (! in_array($value, $allowedValues)) {
                    $issues[] = $this->createInvalidValueIssue($element, $attr, $value, $allowedValues);
                }
            }
        }

        // Check numeric attributes
        $numericAttrs = ['aria-valuenow', 'aria-valuemin', 'aria-valuemax', 'aria-level', 'aria-posinset', 'aria-setsize', 'aria-colcount', 'aria-colindex', 'aria-colspan', 'aria-rowcount', 'aria-rowindex', 'aria-rowspan'];

        foreach ($numericAttrs as $attr) {
            $elements = $this->xpath->query("//*[@{$attr}]");

            foreach ($elements as $element) {
                $value = $element->getAttribute($attr);
                if (! is_numeric($value)) {
                    $issues[] = [
                        'type' => 'invalid-value',
                        'criterion' => '4.1.2',
                        'severity' => 'error',
                        'message' => "Invalid value \"{$value}\" for {$attr} (must be a number)",
                        'element' => $this->getElementDescription($element),
                        'suggestion' => "Use a valid number for the {$attr} attribute",
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Check that ID references in ARIA attributes point to existing elements.
     */
    public function checkIdReferences(): array
    {
        $issues = [];

        foreach ($this->idReferenceAttributes as $attr) {
            $elements = $this->xpath->query("//*[@{$attr}]");

            foreach ($elements as $element) {
                $idRefs = explode(' ', $element->getAttribute($attr));

                foreach ($idRefs as $id) {
                    $id = trim($id);
                    if (empty($id)) {
                        continue;
                    }

                    $referenced = $this->xpath->query("//*[@id='{$id}']");
                    if ($referenced->length === 0) {
                        $issues[] = [
                            'type' => 'broken-reference',
                            'criterion' => '4.1.2',
                            'severity' => 'error',
                            'message' => "{$attr} references non-existent ID \"{$id}\"",
                            'element' => $this->getElementDescription($element),
                            'suggestion' => "Ensure an element with id=\"{$id}\" exists in the document",
                        ];
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check for use of abstract roles.
     */
    public function checkAbstractRoles(): array
    {
        $issues = [];

        foreach ($this->abstractRoles as $role) {
            $elements = $this->xpath->query("//*[@role='{$role}']");

            foreach ($elements as $element) {
                $issues[] = [
                    'type' => 'abstract-role',
                    'criterion' => '4.1.2',
                    'severity' => 'error',
                    'message' => "Abstract role \"{$role}\" should not be used directly",
                    'element' => $this->getElementDescription($element),
                    'suggestion' => 'Use a concrete role instead of an abstract role',
                ];
            }
        }

        return $issues;
    }

    /**
     * Check parent-child role relationships.
     */
    public function checkParentChildRelationships(): array
    {
        $issues = [];

        foreach ($this->allowedChildren as $parentRole => $allowedChildRoles) {
            $parents = $this->xpath->query("//*[@role='{$parentRole}']");

            foreach ($parents as $parent) {
                // Check direct children with roles
                foreach ($parent->childNodes as $child) {
                    if (! ($child instanceof DOMElement)) {
                        continue;
                    }

                    $childRole = $this->getEffectiveRole($child);
                    if ($childRole && ! in_array($childRole, $allowedChildRoles)) {
                        // Check if it's a presentational element
                        if ($childRole !== 'presentation' && $childRole !== 'none') {
                            $issues[] = [
                                'type' => 'invalid-child-role',
                                'criterion' => '1.3.1',
                                'severity' => 'warning',
                                'message' => "Element with role=\"{$childRole}\" is not a valid child of role=\"{$parentRole}\"",
                                'element' => $this->getElementDescription($child),
                                'suggestion' => 'Valid children are: '.implode(', ', $allowedChildRoles),
                            ];
                        }
                    }
                }
            }
        }

        return $issues;
    }

    /**
     * Check for prohibited ARIA attribute combinations.
     */
    public function checkProhibitedAttributes(): array
    {
        $issues = [];

        // aria-hidden="true" on focusable elements
        $focusableHidden = $this->xpath->query(
            "//*[@aria-hidden='true'][self::a[@href] or self::button or self::input or self::select or self::textarea or @tabindex[not(. = '-1')]]"
        );

        foreach ($focusableHidden as $element) {
            $issues[] = [
                'type' => 'prohibited-attribute',
                'criterion' => '4.1.2',
                'severity' => 'error',
                'message' => 'Focusable element should not have aria-hidden="true"',
                'element' => $this->getElementDescription($element),
                'suggestion' => 'Remove aria-hidden or make the element non-focusable with tabindex="-1"',
            ];
        }

        // role="presentation" or role="none" on focusable elements
        $focusablePresentation = $this->xpath->query(
            "//*[@role='presentation' or @role='none'][self::a[@href] or self::button or self::input or @tabindex[not(. = '-1')]]"
        );

        foreach ($focusablePresentation as $element) {
            $issues[] = [
                'type' => 'prohibited-attribute',
                'criterion' => '4.1.2',
                'severity' => 'warning',
                'message' => 'role="presentation" or role="none" on focusable element will be ignored',
                'element' => $this->getElementDescription($element),
                'suggestion' => 'Remove the presentation role or make the element non-focusable',
            ];
        }

        return $issues;
    }

    /**
     * Check for deprecated ARIA attributes.
     */
    public function checkDeprecatedAttributes(): array
    {
        $issues = [];
        $deprecatedAttrs = ['aria-grabbed', 'aria-dropeffect'];

        foreach ($deprecatedAttrs as $attr) {
            $elements = $this->xpath->query("//*[@{$attr}]");

            foreach ($elements as $element) {
                $issues[] = [
                    'type' => 'deprecated-attribute',
                    'criterion' => '4.1.2',
                    'severity' => 'warning',
                    'message' => "Attribute \"{$attr}\" is deprecated in ARIA 1.2",
                    'element' => $this->getElementDescription($element),
                    'suggestion' => 'Consider using a more appropriate alternative or removing the attribute',
                ];
            }
        }

        return $issues;
    }

    /**
     * Check for redundant ARIA roles on HTML elements.
     */
    public function checkRedundantRoles(): array
    {
        $issues = [];

        $redundantMappings = [
            'a[href]' => 'link',
            'article' => 'article',
            'aside' => 'complementary',
            'button' => 'button',
            'dialog' => 'dialog',
            'footer' => 'contentinfo',
            'form' => 'form',
            'header' => 'banner',
            'img[alt]' => 'img',
            'input[type="checkbox"]' => 'checkbox',
            'input[type="radio"]' => 'radio',
            'input[type="range"]' => 'slider',
            'input[type="search"]' => 'searchbox',
            'li' => 'listitem',
            'main' => 'main',
            'nav' => 'navigation',
            'ol' => 'list',
            'option' => 'option',
            'progress' => 'progressbar',
            'section[aria-label]' => 'region',
            'section[aria-labelledby]' => 'region',
            'select' => 'listbox',
            'table' => 'table',
            'td' => 'cell',
            'textarea' => 'textbox',
            'th' => 'columnheader',
            'tr' => 'row',
            'ul' => 'list',
        ];

        foreach ($redundantMappings as $selector => $implicitRole) {
            // Convert selector to XPath
            $xpath = $this->selectorToXpath($selector);
            $elements = $this->xpath->query("{$xpath}[@role='{$implicitRole}']");

            foreach ($elements as $element) {
                $issues[] = [
                    'type' => 'redundant-role',
                    'criterion' => '4.1.2',
                    'severity' => 'info',
                    'message' => "Redundant role=\"{$implicitRole}\" on <{$element->tagName}> element",
                    'element' => $this->getElementDescription($element),
                    'suggestion' => "The \"{$implicitRole}\" role is implicit for this element and can be removed",
                ];
            }
        }

        return $issues;
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
     * Get the effective role of an element.
     */
    protected function getEffectiveRole(DOMElement $element): string
    {
        $explicitRole = $element->getAttribute('role');
        if ($explicitRole) {
            return strtolower($explicitRole);
        }

        // Return implicit role based on tag
        $tagName = strtolower($element->tagName);

        return match ($tagName) {
            'button' => 'button',
            'a' => $element->hasAttribute('href') ? 'link' : '',
            'img' => 'img',
            'input' => $this->getInputRole($element),
            'select' => 'listbox',
            'textarea' => 'textbox',
            'li' => 'listitem',
            'ul', 'ol' => 'list',
            'table' => 'table',
            'tr' => 'row',
            'td' => 'cell',
            'th' => 'columnheader',
            default => '',
        };
    }

    /**
     * Get the implicit role for an input element.
     */
    protected function getInputRole(DOMElement $element): string
    {
        $type = strtolower($element->getAttribute('type') ?: 'text');

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
     * Convert a simple CSS selector to XPath.
     */
    protected function selectorToXpath(string $selector): string
    {
        // Handle attribute selectors
        if (preg_match('/^(\w+)\[([^=]+)(?:="([^"]+)")?\]$/', $selector, $matches)) {
            $tag = $matches[1];
            $attr = $matches[2];
            $value = $matches[3] ?? null;

            if ($value) {
                return "//{$tag}[@{$attr}='{$value}']";
            }

            return "//{$tag}[@{$attr}]";
        }

        // Simple tag selector
        return "//{$selector}";
    }

    /**
     * Create an invalid value issue.
     */
    protected function createInvalidValueIssue(DOMElement $element, string $attr, string $value, array $allowedValues): array
    {
        return [
            'type' => 'invalid-value',
            'criterion' => '4.1.2',
            'severity' => 'error',
            'message' => "Invalid value \"{$value}\" for {$attr}",
            'element' => $this->getElementDescription($element),
            'suggestion' => 'Valid values are: '.implode(', ', $allowedValues),
        ];
    }

    /**
     * Get a description of an element for error messages.
     */
    protected function getElementDescription(DOMElement $element): array
    {
        return [
            'tagName' => strtolower($element->tagName),
            'id' => $element->getAttribute('id') ?: null,
            'class' => $element->getAttribute('class') ?: null,
            'role' => $element->getAttribute('role') ?: null,
            'text' => substr(trim($element->textContent), 0, 50),
        ];
    }

    /**
     * Generate a summary of validation issues.
     */
    protected function generateSummary(array $issues): array
    {
        $summary = [
            'total' => count($issues),
            'byType' => [],
            'bySeverity' => [
                'error' => 0,
                'warning' => 0,
                'info' => 0,
            ],
        ];

        foreach ($issues as $issue) {
            $type = $issue['type'];
            $severity = $issue['severity'];

            $summary['byType'][$type] = ($summary['byType'][$type] ?? 0) + 1;
            $summary['bySeverity'][$severity]++;
        }

        return $summary;
    }
}
