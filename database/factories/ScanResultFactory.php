<?php

namespace Database\Factories;

use App\Models\Scan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScanResult>
 */
class ScanResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'scan_id' => Scan::factory()->completed(),
            'content_snapshot' => fake()->paragraphs(5, true),
            'scores' => [
                'overall' => fake()->numberBetween(60, 100),
                'spelling' => fake()->numberBetween(70, 100),
                'grammar' => fake()->numberBetween(70, 100),
                'readability' => fake()->numberBetween(50, 100),
                'seo' => fake()->numberBetween(40, 100),
            ],
            'screenshots' => [],
            'metadata' => [
                'word_count' => fake()->numberBetween(100, 5000),
                'page_title' => fake()->sentence(4),
            ],
        ];
    }

    /**
     * Create a result with perfect scores.
     */
    public function perfect(): static
    {
        return $this->state(fn (array $attributes) => [
            'scores' => [
                'overall' => 100,
                'spelling' => 100,
                'grammar' => 100,
                'readability' => 100,
                'seo' => 100,
            ],
        ]);
    }

    /**
     * Create a result with poor scores.
     */
    public function poor(): static
    {
        return $this->state(fn (array $attributes) => [
            'scores' => [
                'overall' => fake()->numberBetween(20, 50),
                'spelling' => fake()->numberBetween(30, 60),
                'grammar' => fake()->numberBetween(30, 60),
                'readability' => fake()->numberBetween(20, 50),
                'seo' => fake()->numberBetween(10, 40),
            ],
        ]);
    }
}
