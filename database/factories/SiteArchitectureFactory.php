<?php

namespace Database\Factories;

use App\Enums\ArchitectureStatus;
use App\Models\Project;
use App\Models\SiteArchitecture;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SiteArchitecture>
 */
class SiteArchitectureFactory extends Factory
{
    protected $model = SiteArchitecture::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'status' => ArchitectureStatus::Ready,
            'total_nodes' => fake()->numberBetween(10, 500),
            'total_links' => fake()->numberBetween(20, 1000),
            'max_depth' => fake()->numberBetween(2, 8),
            'orphan_count' => fake()->numberBetween(0, 10),
            'error_count' => fake()->numberBetween(0, 5),
            'crawl_config' => [
                'max_pages' => 500,
                'max_depth' => 10,
                'respect_robots' => true,
                'include_external' => false,
            ],
            'last_crawled_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArchitectureStatus::Pending,
            'total_nodes' => 0,
            'total_links' => 0,
            'max_depth' => 0,
            'orphan_count' => 0,
            'error_count' => 0,
            'last_crawled_at' => null,
        ]);
    }

    public function crawling(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArchitectureStatus::Crawling,
        ]);
    }

    public function analyzing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArchitectureStatus::Analyzing,
        ]);
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArchitectureStatus::Ready,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ArchitectureStatus::Failed,
        ]);
    }

    public function withIssues(): static
    {
        return $this->state(fn (array $attributes) => [
            'orphan_count' => fake()->numberBetween(5, 20),
            'error_count' => fake()->numberBetween(3, 15),
        ]);
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_nodes' => 0,
            'total_links' => 0,
            'max_depth' => 0,
            'orphan_count' => 0,
            'error_count' => 0,
        ]);
    }
}
