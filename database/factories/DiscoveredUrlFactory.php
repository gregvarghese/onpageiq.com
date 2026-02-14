<?php

namespace Database\Factories;

use App\Models\Scan;
use App\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiscoveredUrl>
 */
class DiscoveredUrlFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Create a URL first (which has a project)
        $url = Url::factory()->create();
        // Create a scan for that URL
        $scan = Scan::factory()->create(['url_id' => $url->id]);

        return [
            'scan_id' => $scan->id,
            'project_id' => $url->project_id,
            'url' => fake()->url(),
            'found_on_url' => fake()->optional(0.7)->url(),
            'source_url' => fake()->optional(0.5)->url(),
            'link_text' => fake()->optional(0.5)->words(3, true),
            'status' => 'pending',
            'discovered_at' => now(),
        ];
    }

    /**
     * Set the discovered URL as approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    /**
     * Set the discovered URL as rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_at' => now(),
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}
