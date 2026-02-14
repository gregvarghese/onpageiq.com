<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\Url;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DismissedIssue>
 */
class DismissedIssueFactory extends Factory
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
            'project_id' => null,
            'url_id' => null,
            'dismissed_by_user_id' => User::factory(),
            'scope' => 'url',
            'category' => fake()->randomElement(['spelling', 'grammar', 'seo', 'readability']),
            'text_pattern' => fake()->word(),
            'reason' => fake()->optional(0.5)->sentence(),
        ];
    }

    /**
     * Scope to a specific URL.
     */
    public function forUrl(Url $url): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $url->project->organization_id,
            'project_id' => $url->project_id,
            'url_id' => $url->id,
            'scope' => 'url',
        ]);
    }

    /**
     * Scope to a specific project.
     */
    public function forProject(Project $project): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'url_id' => null,
            'scope' => 'project',
        ]);
    }

    /**
     * Set as pattern-based dismissal.
     */
    public function pattern(): static
    {
        return $this->state(fn (array $attributes) => [
            'scope' => 'pattern',
        ]);
    }
}
