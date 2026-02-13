<?php

namespace Database\Factories;

use App\Enums\AIUsageCategory;
use App\Models\AIUsageMonthly;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIUsageMonthly>
 */
class AIUsageMonthlyFactory extends Factory
{
    protected $model = AIUsageMonthly::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => fake()->numberBetween(2024, 2026),
            'month' => fake()->numberBetween(1, 12),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'category' => fake()->randomElement(AIUsageCategory::values()),
            'request_count' => fake()->numberBetween(100, 1000),
            'success_count' => fake()->numberBetween(90, 950),
            'total_tokens' => fake()->numberBetween(100000, 1000000),
            'total_cost' => fake()->randomFloat(6, 1, 100),
        ];
    }
}
