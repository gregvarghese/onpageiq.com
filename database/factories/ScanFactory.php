<?php

namespace Database\Factories;

use App\Models\Url;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Scan>
 */
class ScanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url_id' => Url::factory(),
            'triggered_by_user_id' => User::factory(),
            'scan_type' => 'quick',
            'status' => 'pending',
            'credits_charged' => 1,
            'started_at' => null,
            'completed_at' => null,
            'error_message' => null,
        ];
    }

    /**
     * Create a quick scan.
     */
    public function quick(): static
    {
        return $this->state(fn (array $attributes) => [
            'scan_type' => 'quick',
            'credits_charged' => 1,
        ]);
    }

    /**
     * Create a deep scan.
     */
    public function deep(): static
    {
        return $this->state(fn (array $attributes) => [
            'scan_type' => 'deep',
            'credits_charged' => 3,
        ]);
    }

    /**
     * Mark as pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Mark as processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'started_at' => now()->subMinutes(1),
            'completed_at' => now(),
            'error_message' => 'Failed to fetch URL content',
        ]);
    }
}
