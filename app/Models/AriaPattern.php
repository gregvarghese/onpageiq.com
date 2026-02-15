<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AriaPattern extends Model
{
    /** @use HasFactory<\Database\Factories\AriaPatternFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'category',
        'required_roles',
        'optional_roles',
        'required_attributes',
        'optional_attributes',
        'keyboard_interactions',
        'focus_management',
        'html_selectors',
        'detection_rules',
        'documentation_url',
        'wcag_criteria',
        'is_custom',
        'organization_id',
    ];

    protected function casts(): array
    {
        return [
            'required_roles' => 'array',
            'optional_roles' => 'array',
            'required_attributes' => 'array',
            'optional_attributes' => 'array',
            'keyboard_interactions' => 'array',
            'focus_management' => 'array',
            'html_selectors' => 'array',
            'detection_rules' => 'array',
            'is_custom' => 'boolean',
        ];
    }

    /**
     * Pattern categories for WAI-ARIA APG.
     */
    public const CATEGORY_WIDGET = 'widget';

    public const CATEGORY_COMPOSITE = 'composite';

    public const CATEGORY_LANDMARK = 'landmark';

    public const CATEGORY_STRUCTURE = 'structure';

    public const CATEGORY_LIVE_REGION = 'live-region';

    /**
     * Get the organization that owns this custom pattern.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Scope to get only built-in (non-custom) patterns.
     */
    public function scopeBuiltIn($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Scope to get custom patterns for an organization.
     */
    public function scopeForOrganization($query, Organization $organization)
    {
        return $query->where(function ($q) use ($organization) {
            $q->where('is_custom', false)
                ->orWhere('organization_id', $organization->id);
        });
    }

    /**
     * Scope to filter by category.
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Check if an element matches this pattern's selectors.
     *
     * @param  array<string, mixed>  $elementData  Element data with tag, attributes, roles
     */
    public function matchesElement(array $elementData): bool
    {
        foreach ($this->detection_rules as $rule) {
            if ($this->evaluateRule($rule, $elementData)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Evaluate a single detection rule against element data.
     *
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $elementData
     */
    protected function evaluateRule(array $rule, array $elementData): bool
    {
        $type = $rule['type'] ?? 'role';

        return match ($type) {
            'role' => $this->evaluateRoleRule($rule, $elementData),
            'attribute' => $this->evaluateAttributeRule($rule, $elementData),
            'selector' => $this->evaluateSelectorRule($rule, $elementData),
            'combined' => $this->evaluateCombinedRule($rule, $elementData),
            default => false,
        };
    }

    /**
     * Evaluate a role-based detection rule.
     */
    protected function evaluateRoleRule(array $rule, array $elementData): bool
    {
        $requiredRole = $rule['role'] ?? null;
        $elementRole = $elementData['role'] ?? null;

        return $requiredRole && $elementRole === $requiredRole;
    }

    /**
     * Evaluate an attribute-based detection rule.
     */
    protected function evaluateAttributeRule(array $rule, array $elementData): bool
    {
        $attribute = $rule['attribute'] ?? null;
        $value = $rule['value'] ?? null;
        $elementAttributes = $elementData['attributes'] ?? [];

        if (! $attribute) {
            return false;
        }

        if (! isset($elementAttributes[$attribute])) {
            return false;
        }

        if ($value === null) {
            return true; // Just check for presence
        }

        return $elementAttributes[$attribute] === $value;
    }

    /**
     * Evaluate a CSS selector-based detection rule.
     */
    protected function evaluateSelectorRule(array $rule, array $elementData): bool
    {
        $selector = $rule['selector'] ?? null;
        $tag = $elementData['tag'] ?? '';
        $attributes = $elementData['attributes'] ?? [];

        if (! $selector) {
            return false;
        }

        // Simple selector matching (tag, class, id)
        if (preg_match('/^(\w+)?(?:\[([^\]]+)\])?(?:\.([^\s]+))?(?:#([^\s]+))?$/', $selector, $matches)) {
            $selectorTag = $matches[1] ?? null;
            $selectorAttr = $matches[2] ?? null;
            $selectorClass = $matches[3] ?? null;
            $selectorId = $matches[4] ?? null;

            if ($selectorTag && strtolower($tag) !== strtolower($selectorTag)) {
                return false;
            }

            if ($selectorAttr) {
                if (str_contains($selectorAttr, '=')) {
                    [$attrName, $attrValue] = explode('=', $selectorAttr, 2);
                    $attrValue = trim($attrValue, '"\'');
                    if (! isset($attributes[$attrName]) || $attributes[$attrName] !== $attrValue) {
                        return false;
                    }
                } else {
                    if (! isset($attributes[$selectorAttr])) {
                        return false;
                    }
                }
            }

            if ($selectorClass) {
                $classes = explode(' ', $attributes['class'] ?? '');
                if (! in_array($selectorClass, $classes)) {
                    return false;
                }
            }

            if ($selectorId && ($attributes['id'] ?? '') !== $selectorId) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Evaluate a combined (AND) detection rule.
     */
    protected function evaluateCombinedRule(array $rule, array $elementData): bool
    {
        $conditions = $rule['conditions'] ?? [];

        foreach ($conditions as $condition) {
            if (! $this->evaluateRule($condition, $elementData)) {
                return false;
            }
        }

        return ! empty($conditions);
    }

    /**
     * Get deviations from this pattern for an element.
     *
     * @param  array<string, mixed>  $elementData
     * @return array<string, mixed>
     */
    public function getDeviations(array $elementData): array
    {
        $deviations = [];

        // Check required roles
        $elementRole = $elementData['role'] ?? null;
        if (! empty($this->required_roles) && ! in_array($elementRole, $this->required_roles)) {
            $deviations[] = [
                'type' => 'missing_role',
                'expected' => $this->required_roles,
                'actual' => $elementRole,
                'message' => sprintf(
                    'Element should have one of the following roles: %s',
                    implode(', ', $this->required_roles)
                ),
            ];
        }

        // Check required attributes for the role
        $attributes = $elementData['attributes'] ?? [];
        $requiredAttrs = $this->required_attributes[$elementRole] ?? $this->required_attributes['*'] ?? [];

        foreach ($requiredAttrs as $attr) {
            if (! isset($attributes[$attr])) {
                $deviations[] = [
                    'type' => 'missing_attribute',
                    'attribute' => $attr,
                    'message' => sprintf('Missing required attribute: %s', $attr),
                ];
            }
        }

        // Check keyboard interactions
        $supportedKeys = $elementData['keyboard_support'] ?? [];
        foreach ($this->keyboard_interactions as $key => $description) {
            if (is_string($key) && ! in_array($key, $supportedKeys)) {
                $deviations[] = [
                    'type' => 'missing_keyboard',
                    'key' => $key,
                    'description' => $description,
                    'message' => sprintf('Missing keyboard interaction: %s - %s', $key, $description),
                ];
            }
        }

        return $deviations;
    }

    /**
     * Get the APG documentation URL with anchor.
     */
    public function getDocumentationUrlAttribute(): string
    {
        return $this->attributes['documentation_url'] ?? sprintf(
            'https://www.w3.org/WAI/ARIA/apg/patterns/%s/',
            $this->slug
        );
    }
}
