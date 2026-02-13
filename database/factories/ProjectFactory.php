<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
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
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'language' => 'en',
            'check_config' => [
                'spelling' => true,
                'grammar' => true,
                'seo' => true,
                'readability' => true,
            ],
        ];
    }

    /**
     * Create a project for a specific organization.
     */
    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * Create a project with spelling check only (free tier).
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

    /**
     * Create a project with all checks enabled.
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
}
