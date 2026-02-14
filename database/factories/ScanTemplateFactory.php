<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScanTemplate>
 */
class ScanTemplateFactory extends Factory
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
            'created_by_user_id' => User::factory(),
            'name' => fake()->randomElement(['Quick Check', 'Full Analysis', 'SEO Focus', 'Grammar Deep Dive']),
            'scan_type' => fake()->randomElement(['quick', 'deep']),
            'check_config' => [
                'spelling' => true,
                'grammar' => fake()->boolean(80),
                'seo' => fake()->boolean(60),
                'readability' => fake()->boolean(40),
            ],
            'is_default' => false,
        ];
    }

    /**
     * Set template as default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Set scan type to quick.
     */
    public function quick(): static
    {
        return $this->state(fn (array $attributes) => [
            'scan_type' => 'quick',
        ]);
    }

    /**
     * Set scan type to deep.
     */
    public function deep(): static
    {
        return $this->state(fn (array $attributes) => [
            'scan_type' => 'deep',
        ]);
    }

    /**
     * Enable all checks.
     */
    public function allChecks(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_config' => [
                'spelling' => true,
                'grammar' => true,
                'seo' => true,
                'readability' => true,
            ],
        ]);
    }

    /**
     * Enable spelling only.
     */
    public function spellingOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'check_config' => [
                'spelling' => true,
                'grammar' => false,
                'seo' => false,
                'readability' => false,
            ],
        ]);
    }
}
