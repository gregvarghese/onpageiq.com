<?php

namespace Database\Factories;

use App\Enums\DeadlineType;
use App\Enums\WcagLevel;
use App\Models\ComplianceDeadline;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplianceDeadline>
 */
class ComplianceDeadlineFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'type' => fake()->randomElement(DeadlineType::cases()),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'deadline_date' => fake()->dateTimeBetween('+1 week', '+6 months'),
            'wcag_level_target' => fake()->randomElement([WcagLevel::A, WcagLevel::AA]),
            'score_target' => fake()->randomFloat(2, 80, 100),
            'reminder_days' => [14, 7, 3, 1],
            'notified_days' => [],
            'is_active' => true,
            'is_met' => false,
            'met_at' => null,
            'metadata' => null,
        ];
    }

    /**
     * WCAG compliance deadline.
     */
    public function wcagCompliance(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DeadlineType::WcagCompliance,
            'title' => 'WCAG 2.1 AA Compliance',
            'wcag_level_target' => WcagLevel::AA,
            'score_target' => 90,
        ]);
    }

    /**
     * Section 508 deadline.
     */
    public function section508(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DeadlineType::Section508,
            'title' => 'Section 508 Compliance',
            'wcag_level_target' => WcagLevel::AA,
            'score_target' => 95,
            'reminder_days' => [30, 14, 7, 3, 1],
        ]);
    }

    /**
     * Overdue deadline.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline_date' => fake()->dateTimeBetween('-1 month', '-1 day'),
            'is_met' => false,
        ]);
    }

    /**
     * Met deadline.
     */
    public function met(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_met' => true,
            'met_at' => now(),
        ]);
    }

    /**
     * Deadline approaching soon.
     */
    public function approachingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'deadline_date' => now()->addDays(3),
            'is_met' => false,
        ]);
    }

    /**
     * Inactive deadline.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
