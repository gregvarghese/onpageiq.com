<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\UrlGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScanSchedule>
 */
class ScanScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frequency = fake()->randomElement(['hourly', 'daily', 'weekly', 'monthly']);

        return [
            'project_id' => Project::factory(),
            'url_group_id' => null,
            'frequency' => $frequency,
            'scan_type' => fake()->randomElement(['quick', 'deep']),
            'preferred_time' => fake()->time('H:i'),
            'day_of_week' => $frequency === 'weekly' ? fake()->numberBetween(0, 6) : null,
            'day_of_month' => $frequency === 'monthly' ? fake()->numberBetween(1, 28) : null,
            'is_active' => true,
            'last_run_at' => null,
            'next_run_at' => fake()->dateTimeBetween('now', '+1 week'),
            'deactivated_at' => null,
            'deactivation_reason' => null,
            'metadata' => null,
        ];
    }

    /**
     * Set schedule as hourly.
     */
    public function hourly(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'hourly',
            'day_of_week' => null,
            'day_of_month' => null,
        ]);
    }

    /**
     * Set schedule as daily.
     */
    public function daily(): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'daily',
            'day_of_week' => null,
            'day_of_month' => null,
        ]);
    }

    /**
     * Set schedule as weekly.
     */
    public function weekly(int $dayOfWeek = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'weekly',
            'day_of_week' => $dayOfWeek,
            'day_of_month' => null,
        ]);
    }

    /**
     * Set schedule as monthly.
     */
    public function monthly(int $dayOfMonth = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'frequency' => 'monthly',
            'day_of_week' => null,
            'day_of_month' => $dayOfMonth,
        ]);
    }

    /**
     * Set schedule as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set schedule as deactivated with reason.
     */
    public function deactivated(string $reason = 'insufficient_credits'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);
    }

    /**
     * Set metadata on the schedule.
     */
    public function withMetadata(array $metadata): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => $metadata,
        ]);
    }

    /**
     * Set schedule for a specific URL group.
     */
    public function forGroup(UrlGroup $group): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $group->project_id,
            'url_group_id' => $group->id,
        ]);
    }
}
