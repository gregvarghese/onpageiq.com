<?php

namespace App\Services\Accessibility;

use App\Enums\CheckStatus;
use App\Enums\FixComplexity;
use App\Enums\ImpactLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RemediationService
{
    /**
     * Fix suggestion templates by criterion.
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $fixTemplates = [
        '1.1.1' => [
            'title' => 'Add Alternative Text',
            'description' => 'Images must have alternative text that describes their content or purpose.',
            'code_template' => '<img src="..." alt="[Descriptive text here]">',
            'techniques' => ['H37', 'H67', 'G94'],
        ],
        '1.3.1' => [
            'title' => 'Add Semantic Structure',
            'description' => 'Use semantic HTML elements and ARIA to convey information and relationships.',
            'code_template' => '<label for="input-id">Label Text</label>\n<input id="input-id" type="text">',
            'techniques' => ['H44', 'H48', 'H51', 'ARIA1'],
        ],
        '1.4.3' => [
            'title' => 'Fix Color Contrast',
            'description' => 'Ensure text has a contrast ratio of at least 4.5:1 (3:1 for large text).',
            'code_template' => '/* Increase contrast */\n.element {\n  color: #1a1a1a; /* Darker text */\n  background: #ffffff; /* Lighter background */\n}',
            'techniques' => ['G18', 'G145', 'G174'],
        ],
        '1.4.11' => [
            'title' => 'Fix Non-text Contrast',
            'description' => 'UI components and graphics must have a contrast ratio of at least 3:1.',
            'code_template' => '/* Increase border/icon contrast */\n.button {\n  border: 2px solid #1a1a1a;\n}\n.icon {\n  fill: #1a1a1a;\n}',
            'techniques' => ['G195', 'G207'],
        ],
        '2.1.1' => [
            'title' => 'Enable Keyboard Access',
            'description' => 'All functionality must be operable with a keyboard.',
            'code_template' => '<!-- Use native interactive elements -->\n<button type="button" onclick="doAction()">Action</button>\n\n<!-- Or add keyboard support -->\n<div role="button" tabindex="0" \n     onclick="doAction()" \n     onkeydown="if(event.key===\'Enter\'||event.key===\' \')doAction()">Action</div>',
            'techniques' => ['G202', 'H91', 'SCR20'],
        ],
        '2.4.1' => [
            'title' => 'Add Skip Link',
            'description' => 'Provide a way to bypass repeated content.',
            'code_template' => '<a href="#main-content" class="skip-link">Skip to main content</a>\n<!-- ... navigation ... -->\n<main id="main-content">',
            'techniques' => ['G1', 'G123', 'G124'],
        ],
        '2.4.4' => [
            'title' => 'Improve Link Purpose',
            'description' => 'Link text should describe the destination or purpose.',
            'code_template' => '<!-- Avoid -->\n<a href="...">Click here</a>\n\n<!-- Better -->\n<a href="...">View our accessibility policy</a>\n\n<!-- Or with context -->\n<a href="..." aria-label="Download annual report (PDF, 2MB)">Download</a>',
            'techniques' => ['G91', 'G189', 'H30'],
        ],
        '2.4.7' => [
            'title' => 'Add Visible Focus Indicator',
            'description' => 'Keyboard focus must be visible on all interactive elements.',
            'code_template' => '/* Visible focus indicator */\n:focus {\n  outline: 2px solid #005fcc;\n  outline-offset: 2px;\n}\n\n/* Or custom focus style */\n:focus-visible {\n  box-shadow: 0 0 0 3px rgba(0, 95, 204, 0.5);\n}',
            'techniques' => ['G149', 'G165', 'C15'],
        ],
        '3.1.1' => [
            'title' => 'Add Page Language',
            'description' => 'Specify the default language of the page.',
            'code_template' => '<html lang="en">',
            'techniques' => ['H57'],
        ],
        '3.3.2' => [
            'title' => 'Add Labels and Instructions',
            'description' => 'Form inputs must have labels and clear instructions.',
            'code_template' => '<label for="email">Email address (required)</label>\n<input id="email" type="email" required aria-describedby="email-hint">\n<p id="email-hint">We\'ll never share your email.</p>',
            'techniques' => ['G131', 'H44', 'ARIA1'],
        ],
        '4.1.2' => [
            'title' => 'Add Name, Role, Value',
            'description' => 'Custom components must expose name, role, and value to assistive technology.',
            'code_template' => '<!-- Custom checkbox example -->\n<div role="checkbox" \n     aria-checked="false" \n     aria-labelledby="label-id"\n     tabindex="0"\n     onclick="toggleCheckbox(this)"\n     onkeydown="handleKeydown(event)">',
            'techniques' => ['ARIA4', 'ARIA5', 'G108'],
        ],
    ];

    /**
     * Generate AI-powered fix suggestion for an audit check.
     *
     * @return array<string, mixed>
     */
    public function generateFixSuggestion(AuditCheck $check): array
    {
        $template = $this->fixTemplates[$check->criterion_id] ?? null;
        $complexity = FixComplexity::fromCriterion($check->criterion_id);

        $suggestion = [
            'criterion_id' => $check->criterion_id,
            'criterion_name' => $check->criterion_name,
            'complexity' => $complexity->value,
            'complexity_label' => $complexity->label(),
            'effort_minutes' => $complexity->effortMinutes(),
            'title' => $template['title'] ?? "Fix {$check->criterion_name}",
            'description' => $template['description'] ?? $check->suggestion,
            'techniques' => $template['techniques'] ?? [],
            'code_snippet' => null,
            'ai_suggestion' => null,
        ];

        // Add template code snippet if available
        if ($template && isset($template['code_template'])) {
            $suggestion['code_snippet'] = $template['code_template'];
        }

        // Generate AI-powered contextual suggestion if element HTML is available
        if ($check->element_html) {
            try {
                $suggestion['ai_suggestion'] = $this->generateAiSuggestion($check);
            } catch (\Exception $e) {
                Log::warning('Failed to generate AI suggestion', [
                    'check_id' => $check->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $suggestion;
    }

    /**
     * Generate AI-powered contextual fix suggestion.
     */
    protected function generateAiSuggestion(AuditCheck $check): ?string
    {
        // Check if Laravel AI is available
        if (! class_exists('Laravel\AI\Facades\AI')) {
            return null;
        }

        $prompt = $this->buildAiPrompt($check);

        try {
            $response = \Laravel\AI\Facades\AI::chat()
                ->systemPrompt('You are an accessibility expert. Provide concise, specific code fixes. Return only the corrected HTML/CSS code with brief inline comments explaining the changes. Keep responses under 200 words.')
                ->send($prompt);

            return $response->text();
        } catch (\Exception $e) {
            Log::warning('AI suggestion failed', [
                'check_id' => $check->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build AI prompt for fix suggestion.
     */
    protected function buildAiPrompt(AuditCheck $check): string
    {
        $elementHtml = $check->getTruncatedHtml(500);
        $requirement = $this->fixTemplates[$check->criterion_id]['description'] ?? 'Meet WCAG requirements';
        $criterionId = $check->criterion_id;
        $criterionName = $check->criterion_name;
        $message = $check->message;

        return <<<PROMPT
Fix this WCAG {$criterionId} ({$criterionName}) accessibility issue.

**Issue:** {$message}

**Current Code:**
```html
{$elementHtml}
```

**Requirement:** {$requirement}

Provide the corrected code with minimal changes.
PROMPT;
    }

    /**
     * Generate prioritized fix roadmap for an audit.
     *
     * @return array<string, mixed>
     */
    public function generateFixRoadmap(AccessibilityAudit $audit): array
    {
        $checks = $audit->checks()
            ->where('status', CheckStatus::Fail)
            ->get();

        $grouped = $this->groupByComplexity($checks);
        $prioritized = $this->prioritizeIssues($checks);

        // Calculate effort totals
        $totalEffort = 0;
        foreach ($grouped as $complexity => $issues) {
            $complexityEnum = FixComplexity::tryFrom($complexity);
            if ($complexityEnum) {
                $totalEffort += count($issues) * $complexityEnum->effortMinutes();
            }
        }

        return [
            'audit_id' => $audit->id,
            'total_issues' => $checks->count(),
            'total_effort_minutes' => $totalEffort,
            'total_effort_hours' => round($totalEffort / 60, 1),
            'by_complexity' => $grouped,
            'prioritized' => $prioritized,
            'quick_wins' => $this->getQuickWins($checks),
            'high_impact' => $this->getHighImpactFixes($checks),
            'phases' => $this->generatePhases($grouped),
        ];
    }

    /**
     * Group issues by fix complexity.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    protected function groupByComplexity(Collection $checks): array
    {
        $grouped = [];

        foreach (FixComplexity::cases() as $complexity) {
            $grouped[$complexity->value] = [];
        }

        foreach ($checks as $check) {
            $complexity = FixComplexity::fromCriterion($check->criterion_id);
            $grouped[$complexity->value][] = [
                'id' => $check->id,
                'criterion_id' => $check->criterion_id,
                'criterion_name' => $check->criterion_name,
                'message' => $check->message,
                'impact' => $check->impact?->value,
                'element_selector' => $check->element_selector,
            ];
        }

        return $grouped;
    }

    /**
     * Prioritize issues by impact and complexity.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function prioritizeIssues(Collection $checks): array
    {
        return $checks->map(function (AuditCheck $check) {
            $complexity = FixComplexity::fromCriterion($check->criterion_id);
            $impactWeight = match ($check->impact) {
                ImpactLevel::Critical => 1,
                ImpactLevel::Serious => 2,
                ImpactLevel::Moderate => 3,
                ImpactLevel::Minor => 4,
                default => 5,
            };

            // Priority score: lower is higher priority
            // Formula: impact * 2 + complexity (prioritize high impact, then easy fixes)
            $priorityScore = ($impactWeight * 2) + $complexity->priorityWeight();

            return [
                'id' => $check->id,
                'criterion_id' => $check->criterion_id,
                'criterion_name' => $check->criterion_name,
                'message' => $check->message,
                'impact' => $check->impact?->value,
                'complexity' => $complexity->value,
                'priority_score' => $priorityScore,
                'effort_minutes' => $complexity->effortMinutes(),
            ];
        })
            ->sortBy('priority_score')
            ->values()
            ->toArray();
    }

    /**
     * Get quick wins - high impact, low effort fixes.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getQuickWins(Collection $checks): array
    {
        return $checks->filter(function (AuditCheck $check) {
            $complexity = FixComplexity::fromCriterion($check->criterion_id);

            return in_array($complexity, [FixComplexity::Quick, FixComplexity::Easy])
                && in_array($check->impact, [ImpactLevel::Critical, ImpactLevel::Serious]);
        })
            ->take(10)
            ->map(fn (AuditCheck $check) => [
                'id' => $check->id,
                'criterion_id' => $check->criterion_id,
                'criterion_name' => $check->criterion_name,
                'message' => $check->message,
                'impact' => $check->impact?->value,
                'suggestion' => $this->fixTemplates[$check->criterion_id]['title'] ?? null,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get high impact fixes regardless of complexity.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getHighImpactFixes(Collection $checks): array
    {
        return $checks->filter(function (AuditCheck $check) {
            return $check->impact === ImpactLevel::Critical;
        })
            ->take(10)
            ->map(fn (AuditCheck $check) => [
                'id' => $check->id,
                'criterion_id' => $check->criterion_id,
                'criterion_name' => $check->criterion_name,
                'message' => $check->message,
                'complexity' => FixComplexity::fromCriterion($check->criterion_id)->value,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Generate implementation phases.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function generatePhases(array $grouped): array
    {
        $phases = [];

        // Phase 1: Quick wins
        if (! empty($grouped['quick']) || ! empty($grouped['easy'])) {
            $phases[] = [
                'phase' => 1,
                'name' => 'Quick Wins',
                'description' => 'Address simple fixes that can be completed quickly',
                'issue_count' => count($grouped['quick'] ?? []) + count($grouped['easy'] ?? []),
                'complexities' => ['quick', 'easy'],
            ];
        }

        // Phase 2: Medium effort
        if (! empty($grouped['medium'])) {
            $phases[] = [
                'phase' => 2,
                'name' => 'Component Updates',
                'description' => 'Update individual components with moderate changes',
                'issue_count' => count($grouped['medium']),
                'complexities' => ['medium'],
            ];
        }

        // Phase 3: Complex changes
        if (! empty($grouped['complex'])) {
            $phases[] = [
                'phase' => 3,
                'name' => 'Complex Fixes',
                'description' => 'Address issues requiring multiple component changes',
                'issue_count' => count($grouped['complex']),
                'complexities' => ['complex'],
            ];
        }

        // Phase 4: Architectural
        if (! empty($grouped['architectural'])) {
            $phases[] = [
                'phase' => 4,
                'name' => 'Architectural Changes',
                'description' => 'Design-level changes requiring planning and coordination',
                'issue_count' => count($grouped['architectural']),
                'complexities' => ['architectural'],
            ];
        }

        return $phases;
    }

    /**
     * Get fix template for a criterion.
     *
     * @return array<string, mixed>|null
     */
    public function getFixTemplate(string $criterionId): ?array
    {
        return $this->fixTemplates[$criterionId] ?? null;
    }

    /**
     * Get all fix templates.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getAllFixTemplates(): array
    {
        return $this->fixTemplates;
    }

    /**
     * Batch generate suggestions for multiple checks.
     *
     * @return array<int, array<string, mixed>>
     */
    public function batchGenerateSuggestions(Collection $checks, bool $includeAi = false): array
    {
        return $checks->map(function (AuditCheck $check) use ($includeAi) {
            $suggestion = $this->generateFixSuggestion($check);

            if (! $includeAi) {
                unset($suggestion['ai_suggestion']);
            }

            return $suggestion;
        })->toArray();
    }
}
