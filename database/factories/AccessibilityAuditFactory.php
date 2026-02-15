<?php

namespace Database\Factories;

use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use App\Enums\ComplianceFramework;
use App\Enums\WcagLevel;
use App\Models\Project;
use App\Models\Url;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AccessibilityAudit>
 */
class AccessibilityAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'url_id' => null,
            'triggered_by_user_id' => User::factory(),
            'wcag_level_target' => WcagLevel::AA,
            'framework' => ComplianceFramework::Wcag21,
            'status' => AuditStatus::Pending,
            'error_message' => null,
            'overall_score' => null,
            'scores_by_category' => null,
            'checks_total' => 0,
            'checks_passed' => 0,
            'checks_failed' => 0,
            'checks_warning' => 0,
            'checks_manual' => 0,
            'checks_not_applicable' => 0,
            'started_at' => null,
            'completed_at' => null,
            'metadata' => null,
        ];
    }

    /**
     * Create an audit for a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project->id,
        ]);
    }

    /**
     * Create an audit for a specific URL.
     */
    public function forUrl(Url $url): static
    {
        return $this->state(fn (array $attributes) => [
            'url_id' => $url->id,
            'project_id' => $url->project_id,
        ]);
    }

    /**
     * Create an audit triggered by a specific user.
     */
    public function triggeredBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'triggered_by_user_id' => $user->id,
        ]);
    }

    /**
     * Create a pending audit.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Pending,
            'started_at' => null,
            'completed_at' => null,
        ]);
    }

    /**
     * Create a running audit.
     */
    public function running(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Running,
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    /**
     * Create a completed audit with scores.
     */
    public function completed(): static
    {
        $passed = fake()->numberBetween(30, 50);
        $failed = fake()->numberBetween(0, 10);
        $warning = fake()->numberBetween(0, 5);
        $manual = fake()->numberBetween(0, 5);
        $notApplicable = fake()->numberBetween(0, 10);
        $total = $passed + $failed + $warning + $manual + $notApplicable;

        $applicable = $total - $notApplicable;
        $overallScore = $applicable > 0 ? round(($passed / $applicable) * 100, 2) : 100;

        $scoresByCategory = [];
        foreach (AuditCategory::cases() as $category) {
            $scoresByCategory[$category->value] = fake()->randomFloat(2, 60, 100);
        }

        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Completed,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 10)),
            'completed_at' => now(),
            'overall_score' => $overallScore,
            'scores_by_category' => $scoresByCategory,
            'checks_total' => $total,
            'checks_passed' => $passed,
            'checks_failed' => $failed,
            'checks_warning' => $warning,
            'checks_manual' => $manual,
            'checks_not_applicable' => $notApplicable,
        ]);
    }

    /**
     * Create a failed audit.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Failed,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 5)),
            'completed_at' => now(),
            'error_message' => fake()->sentence(),
        ]);
    }

    /**
     * Create an audit targeting WCAG Level A.
     */
    public function levelA(): static
    {
        return $this->state(fn (array $attributes) => [
            'wcag_level_target' => WcagLevel::A,
        ]);
    }

    /**
     * Create an audit targeting WCAG Level AA.
     */
    public function levelAA(): static
    {
        return $this->state(fn (array $attributes) => [
            'wcag_level_target' => WcagLevel::AA,
        ]);
    }

    /**
     * Create an audit targeting WCAG Level AAA.
     */
    public function levelAAA(): static
    {
        return $this->state(fn (array $attributes) => [
            'wcag_level_target' => WcagLevel::AAA,
        ]);
    }

    /**
     * Create an audit for Section 508 compliance.
     */
    public function section508(): static
    {
        return $this->state(fn (array $attributes) => [
            'framework' => ComplianceFramework::Section508,
        ]);
    }

    /**
     * Create an audit for EN 301 549 compliance.
     */
    public function en301549(): static
    {
        return $this->state(fn (array $attributes) => [
            'framework' => ComplianceFramework::En301549,
        ]);
    }

    /**
     * Create an audit with perfect scores.
     */
    public function perfect(): static
    {
        $total = fake()->numberBetween(40, 60);
        $notApplicable = fake()->numberBetween(0, 5);

        $scoresByCategory = [];
        foreach (AuditCategory::cases() as $category) {
            $scoresByCategory[$category->value] = 100;
        }

        return $this->state(fn (array $attributes) => [
            'status' => AuditStatus::Completed,
            'started_at' => now()->subMinutes(fake()->numberBetween(1, 5)),
            'completed_at' => now(),
            'overall_score' => 100,
            'scores_by_category' => $scoresByCategory,
            'checks_total' => $total,
            'checks_passed' => $total - $notApplicable,
            'checks_failed' => 0,
            'checks_warning' => 0,
            'checks_manual' => 0,
            'checks_not_applicable' => $notApplicable,
        ]);
    }
}
