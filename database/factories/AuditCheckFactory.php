<?php

namespace Database\Factories;

use App\Enums\AuditCategory;
use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditCheck>
 */
class AuditCheckFactory extends Factory
{
    /**
     * WCAG criteria with their details for realistic data generation.
     *
     * @var array<string, array{name: string, level: string, category: string}>
     */
    private const WCAG_CRITERIA = [
        '1.1.1' => ['name' => 'Non-text Content', 'level' => 'A', 'category' => 'vision'],
        '1.2.1' => ['name' => 'Audio-only and Video-only (Prerecorded)', 'level' => 'A', 'category' => 'hearing'],
        '1.2.2' => ['name' => 'Captions (Prerecorded)', 'level' => 'A', 'category' => 'hearing'],
        '1.2.3' => ['name' => 'Audio Description or Media Alternative', 'level' => 'A', 'category' => 'vision'],
        '1.3.1' => ['name' => 'Info and Relationships', 'level' => 'A', 'category' => 'vision'],
        '1.3.2' => ['name' => 'Meaningful Sequence', 'level' => 'A', 'category' => 'vision'],
        '1.3.3' => ['name' => 'Sensory Characteristics', 'level' => 'A', 'category' => 'general'],
        '1.3.4' => ['name' => 'Orientation', 'level' => 'AA', 'category' => 'motor'],
        '1.3.5' => ['name' => 'Identify Input Purpose', 'level' => 'AA', 'category' => 'cognitive'],
        '1.4.1' => ['name' => 'Use of Color', 'level' => 'A', 'category' => 'vision'],
        '1.4.2' => ['name' => 'Audio Control', 'level' => 'A', 'category' => 'hearing'],
        '1.4.3' => ['name' => 'Contrast (Minimum)', 'level' => 'AA', 'category' => 'vision'],
        '1.4.4' => ['name' => 'Resize Text', 'level' => 'AA', 'category' => 'vision'],
        '1.4.5' => ['name' => 'Images of Text', 'level' => 'AA', 'category' => 'vision'],
        '1.4.10' => ['name' => 'Reflow', 'level' => 'AA', 'category' => 'vision'],
        '1.4.11' => ['name' => 'Non-text Contrast', 'level' => 'AA', 'category' => 'vision'],
        '1.4.12' => ['name' => 'Text Spacing', 'level' => 'AA', 'category' => 'vision'],
        '1.4.13' => ['name' => 'Content on Hover or Focus', 'level' => 'AA', 'category' => 'motor'],
        '2.1.1' => ['name' => 'Keyboard', 'level' => 'A', 'category' => 'motor'],
        '2.1.2' => ['name' => 'No Keyboard Trap', 'level' => 'A', 'category' => 'motor'],
        '2.1.4' => ['name' => 'Character Key Shortcuts', 'level' => 'A', 'category' => 'motor'],
        '2.2.1' => ['name' => 'Timing Adjustable', 'level' => 'A', 'category' => 'cognitive'],
        '2.2.2' => ['name' => 'Pause, Stop, Hide', 'level' => 'A', 'category' => 'cognitive'],
        '2.3.1' => ['name' => 'Three Flashes or Below Threshold', 'level' => 'A', 'category' => 'general'],
        '2.4.1' => ['name' => 'Bypass Blocks', 'level' => 'A', 'category' => 'motor'],
        '2.4.2' => ['name' => 'Page Titled', 'level' => 'A', 'category' => 'general'],
        '2.4.3' => ['name' => 'Focus Order', 'level' => 'A', 'category' => 'motor'],
        '2.4.4' => ['name' => 'Link Purpose (In Context)', 'level' => 'A', 'category' => 'cognitive'],
        '2.4.5' => ['name' => 'Multiple Ways', 'level' => 'AA', 'category' => 'cognitive'],
        '2.4.6' => ['name' => 'Headings and Labels', 'level' => 'AA', 'category' => 'cognitive'],
        '2.4.7' => ['name' => 'Focus Visible', 'level' => 'AA', 'category' => 'motor'],
        '2.5.1' => ['name' => 'Pointer Gestures', 'level' => 'A', 'category' => 'motor'],
        '2.5.2' => ['name' => 'Pointer Cancellation', 'level' => 'A', 'category' => 'motor'],
        '2.5.3' => ['name' => 'Label in Name', 'level' => 'A', 'category' => 'motor'],
        '2.5.4' => ['name' => 'Motion Actuation', 'level' => 'A', 'category' => 'motor'],
        '3.1.1' => ['name' => 'Language of Page', 'level' => 'A', 'category' => 'general'],
        '3.1.2' => ['name' => 'Language of Parts', 'level' => 'AA', 'category' => 'general'],
        '3.2.1' => ['name' => 'On Focus', 'level' => 'A', 'category' => 'cognitive'],
        '3.2.2' => ['name' => 'On Input', 'level' => 'A', 'category' => 'cognitive'],
        '3.2.3' => ['name' => 'Consistent Navigation', 'level' => 'AA', 'category' => 'cognitive'],
        '3.2.4' => ['name' => 'Consistent Identification', 'level' => 'AA', 'category' => 'cognitive'],
        '3.3.1' => ['name' => 'Error Identification', 'level' => 'A', 'category' => 'cognitive'],
        '3.3.2' => ['name' => 'Labels or Instructions', 'level' => 'A', 'category' => 'cognitive'],
        '3.3.3' => ['name' => 'Error Suggestion', 'level' => 'AA', 'category' => 'cognitive'],
        '3.3.4' => ['name' => 'Error Prevention (Legal, Financial, Data)', 'level' => 'AA', 'category' => 'cognitive'],
        '4.1.1' => ['name' => 'Parsing', 'level' => 'A', 'category' => 'general'],
        '4.1.2' => ['name' => 'Name, Role, Value', 'level' => 'A', 'category' => 'vision'],
        '4.1.3' => ['name' => 'Status Messages', 'level' => 'AA', 'category' => 'vision'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $criterionId = fake()->randomElement(array_keys(self::WCAG_CRITERIA));
        $criterion = self::WCAG_CRITERIA[$criterionId];

        return [
            'accessibility_audit_id' => AccessibilityAudit::factory(),
            'criterion_id' => $criterionId,
            'criterion_name' => $criterion['name'],
            'wcag_level' => WcagLevel::from($criterion['level']),
            'category' => AuditCategory::from($criterion['category']),
            'impact' => null,
            'status' => CheckStatus::Pass,
            'element_selector' => null,
            'element_html' => null,
            'element_xpath' => null,
            'message' => null,
            'suggestion' => null,
            'code_snippet' => null,
            'documentation_url' => "https://www.w3.org/WAI/WCAG21/Understanding/{$criterionId}.html",
            'technique_id' => null,
            'fingerprint' => null,
            'is_recurring' => false,
            'metadata' => null,
        ];
    }

    /**
     * Create a check for a specific audit.
     */
    public function forAudit(AccessibilityAudit $audit): static
    {
        return $this->state(fn (array $attributes) => [
            'accessibility_audit_id' => $audit->id,
        ]);
    }

    /**
     * Create a check for a specific criterion.
     */
    public function forCriterion(string $criterionId): static
    {
        $criterion = self::WCAG_CRITERIA[$criterionId] ?? [
            'name' => 'Unknown Criterion',
            'level' => 'A',
            'category' => 'general',
        ];

        return $this->state(fn (array $attributes) => [
            'criterion_id' => $criterionId,
            'criterion_name' => $criterion['name'],
            'wcag_level' => WcagLevel::from($criterion['level']),
            'category' => AuditCategory::from($criterion['category']),
            'documentation_url' => "https://www.w3.org/WAI/WCAG21/Understanding/{$criterionId}.html",
        ]);
    }

    /**
     * Create a passing check.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckStatus::Pass,
            'impact' => null,
            'message' => null,
            'suggestion' => null,
        ]);
    }

    /**
     * Create a failing check.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckStatus::Fail,
            'impact' => fake()->randomElement(ImpactLevel::cases()),
            'element_selector' => fake()->randomElement([
                'img.hero-image',
                'button.submit-btn',
                'a.nav-link',
                'input#email',
                'div.modal',
                'form.contact-form',
            ]),
            'element_html' => '<'.fake()->randomElement(['img', 'button', 'a', 'input', 'div']).' class="'.fake()->word().'">',
            'message' => fake()->sentence(),
            'suggestion' => fake()->sentence(),
            'fingerprint' => fake()->sha256(),
        ]);
    }

    /**
     * Create a warning check.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckStatus::Warning,
            'impact' => ImpactLevel::Minor,
            'message' => fake()->sentence(),
            'suggestion' => fake()->sentence(),
        ]);
    }

    /**
     * Create a check that needs manual review.
     */
    public function manualReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckStatus::ManualReview,
            'message' => 'This criterion requires manual testing to verify compliance.',
        ]);
    }

    /**
     * Create a not applicable check.
     */
    public function notApplicable(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckStatus::NotApplicable,
            'message' => 'This criterion does not apply to the content on this page.',
        ]);
    }

    /**
     * Create an opportunity check (AAA).
     */
    public function opportunity(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CheckStatus::Opportunity,
            'wcag_level' => WcagLevel::AAA,
            'message' => fake()->sentence(),
            'suggestion' => fake()->sentence(),
        ]);
    }

    /**
     * Create a critical impact check.
     */
    public function critical(): static
    {
        return $this->failed()->state(fn (array $attributes) => [
            'impact' => ImpactLevel::Critical,
        ]);
    }

    /**
     * Create a serious impact check.
     */
    public function serious(): static
    {
        return $this->failed()->state(fn (array $attributes) => [
            'impact' => ImpactLevel::Serious,
        ]);
    }

    /**
     * Create a recurring issue.
     */
    public function recurring(): static
    {
        return $this->failed()->state(fn (array $attributes) => [
            'is_recurring' => true,
        ]);
    }

    /**
     * Create a contrast failure check.
     */
    public function contrastFailure(): static
    {
        return $this->forCriterion('1.4.3')->failed()->state(fn (array $attributes) => [
            'impact' => ImpactLevel::Serious,
            'message' => 'Text does not meet minimum contrast ratio of 4.5:1',
            'suggestion' => 'Increase the contrast between the text color and background color to at least 4.5:1 for normal text.',
            'metadata' => [
                'foreground_color' => '#777777',
                'background_color' => '#ffffff',
                'contrast_ratio' => 4.48,
                'required_ratio' => 4.5,
            ],
        ]);
    }

    /**
     * Create a missing alt text check.
     */
    public function missingAltText(): static
    {
        return $this->forCriterion('1.1.1')->failed()->state(fn (array $attributes) => [
            'impact' => ImpactLevel::Critical,
            'element_selector' => 'img.hero-image',
            'element_html' => '<img src="hero.jpg" class="hero-image">',
            'message' => 'Image is missing alternative text',
            'suggestion' => 'Add an alt attribute that describes the image content, or use alt="" if the image is decorative.',
            'code_snippet' => '<img src="hero.jpg" class="hero-image" alt="Description of the image">',
        ]);
    }

    /**
     * Create a keyboard trap check.
     */
    public function keyboardTrap(): static
    {
        return $this->forCriterion('2.1.2')->failed()->state(fn (array $attributes) => [
            'impact' => ImpactLevel::Critical,
            'element_selector' => 'div.modal',
            'message' => 'Keyboard focus is trapped within the modal dialog',
            'suggestion' => 'Ensure users can exit the modal using the Escape key or a visible close button.',
        ]);
    }

    /**
     * Create a focus visible failure check.
     */
    public function focusNotVisible(): static
    {
        return $this->forCriterion('2.4.7')->failed()->state(fn (array $attributes) => [
            'impact' => ImpactLevel::Serious,
            'element_selector' => 'a.nav-link',
            'message' => 'Interactive element has no visible focus indicator',
            'suggestion' => 'Add a visible focus indicator such as an outline or background color change.',
            'code_snippet' => 'a.nav-link:focus { outline: 2px solid #005fcc; outline-offset: 2px; }',
        ]);
    }
}
