<?php

namespace Database\Factories;

use App\Enums\VpatConformanceLevel;
use App\Enums\VpatStatus;
use App\Models\AccessibilityAudit;
use App\Models\User;
use App\Models\VpatEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VpatEvaluation>
 */
class VpatEvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'accessibility_audit_id' => AccessibilityAudit::factory(),
            'created_by_user_id' => User::factory(),
            'product_name' => fake()->company().' Web Application',
            'product_version' => fake()->semver(),
            'product_description' => fake()->paragraph(),
            'vendor_name' => fake()->company(),
            'vendor_contact' => fake()->email(),
            'evaluation_date' => fake()->dateTimeBetween('-1 month', 'now'),
            'evaluation_methods' => 'Automated testing using accessibility audit tools, manual testing with screen readers (NVDA, VoiceOver), keyboard-only navigation testing.',
            'vpat_version' => '2.4',
            'report_types' => ['wcag21'],
            'wcag_evaluations' => [],
            'section508_evaluations' => null,
            'en301549_evaluations' => null,
            'legal_disclaimer' => 'This document is provided for informational purposes only and is subject to change.',
            'notes' => null,
            'status' => VpatStatus::Draft,
            'approved_by_user_id' => null,
            'approved_at' => null,
            'published_at' => null,
        ];
    }

    /**
     * With WCAG and Section 508 report types.
     */
    public function withSection508(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_types' => ['wcag21', 'section508'],
        ]);
    }

    /**
     * With all report types.
     */
    public function withAllReportTypes(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_types' => ['wcag21', 'section508', 'en301549'],
        ]);
    }

    /**
     * In review status.
     */
    public function inReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VpatStatus::InReview,
        ]);
    }

    /**
     * Approved status.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VpatStatus::Approved,
            'approved_by_user_id' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Published status.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VpatStatus::Published,
            'approved_by_user_id' => User::factory(),
            'approved_at' => now()->subDay(),
            'published_at' => now(),
        ]);
    }

    /**
     * With sample WCAG evaluations.
     */
    public function withSampleEvaluations(): static
    {
        return $this->state(fn (array $attributes) => [
            'wcag_evaluations' => [
                '1.1.1' => [
                    'level' => VpatConformanceLevel::Supports->value,
                    'remarks' => 'All images have appropriate alt text.',
                    'updated_at' => now()->toIso8601String(),
                ],
                '1.4.3' => [
                    'level' => VpatConformanceLevel::PartiallySupports->value,
                    'remarks' => 'Some interactive elements have contrast below 4.5:1.',
                    'updated_at' => now()->toIso8601String(),
                ],
                '2.1.1' => [
                    'level' => VpatConformanceLevel::Supports->value,
                    'remarks' => 'All functionality is accessible via keyboard.',
                    'updated_at' => now()->toIso8601String(),
                ],
                '2.4.7' => [
                    'level' => VpatConformanceLevel::Supports->value,
                    'remarks' => 'Focus indicators are visible on all interactive elements.',
                    'updated_at' => now()->toIso8601String(),
                ],
            ],
        ]);
    }
}
