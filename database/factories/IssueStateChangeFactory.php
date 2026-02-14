<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IssueStateChange>
 */
class IssueStateChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $states = ['open', 'in_progress', 'review', 'resolved'];

        return [
            'issue_id' => Issue::factory(),
            'user_id' => User::factory(),
            'from_state' => fake()->randomElement($states),
            'to_state' => fake()->randomElement($states),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
