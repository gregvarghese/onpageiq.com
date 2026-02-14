<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UrlGroup>
 */
class UrlGroupFactory extends Factory
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
            'name' => fake()->randomElement(['Blog', 'Products', 'Landing Pages', 'Documentation', 'Marketing']),
            'color' => fake()->hexColor(),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
