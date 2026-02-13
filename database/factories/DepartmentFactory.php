<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->randomElement(['Marketing', 'Engineering', 'Sales', 'Support', 'Content', 'Design']),
            'credit_budget' => fake()->numberBetween(50, 200),
            'credit_used' => 0,
        ];
    }

    /**
     * Indicate that the department has used some of its budget.
     */
    public function partiallyUsed(): static
    {
        return $this->state(function (array $attributes) {
            $budget = $attributes['credit_budget'] ?? 100;

            return [
                'credit_used' => fake()->numberBetween(1, $budget - 1),
            ];
        });
    }

    /**
     * Indicate that the department has exhausted its budget.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => [
            'credit_used' => $attributes['credit_budget'] ?? 100,
        ]);
    }
}
