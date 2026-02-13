<?php

namespace Database\Factories;

use App\Enums\AIUsageCategory;
use App\Models\AIUsageDaily;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIUsageDaily>
 */
class AIUsageDailyFactory extends Factory
{
    protected $model = AIUsageDaily::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(AIUsageCategory::values()),
            'provider' => fake()->randomElement(['openai', 'anthropic']),
            'model' => fake()->randomElement(['gpt-4o-mini', 'gpt-4o', 'claude-3-sonnet']),
            'request_count' => fake()->numberBetween(10, 100),
            'success_count' => fake()->numberBetween(8, 95),
            'failure_count' => fake()->numberBetween(0, 5),
            'total_prompt_tokens' => fake()->numberBetween(10000, 100000),
            'total_completion_tokens' => fake()->numberBetween(5000, 50000),
            'total_tokens' => fake()->numberBetween(15000, 150000),
            'total_cost' => fake()->randomFloat(6, 0.1, 10),
            'total_duration_ms' => fake()->numberBetween(50000, 500000),
        ];
    }
}
