<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IssueAssignment>
 */
class IssueAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'assigned_by_user_id' => User::factory(),
            'assigned_to_user_id' => User::factory(),
            'due_date' => fake()->optional(0.7)->dateTimeBetween('now', '+2 weeks'),
            'status' => fake()->randomElement(['open', 'in_progress', 'resolved', 'dismissed']),
            'resolution_note' => null,
            'resolved_at' => null,
        ];
    }

    /**
     * Indicate the assignment is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'resolution_note' => null,
            'resolved_at' => null,
        ]);
    }

    /**
     * Indicate the assignment is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'resolution_note' => null,
            'resolved_at' => null,
        ]);
    }

    /**
     * Indicate the assignment is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'resolved',
            'resolution_note' => fake()->sentence(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate the assignment is dismissed.
     */
    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'dismissed',
            'resolution_note' => fake()->sentence(),
            'resolved_at' => now(),
        ]);
    }

    /**
     * Indicate the assignment is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'open',
            'due_date' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }
}
