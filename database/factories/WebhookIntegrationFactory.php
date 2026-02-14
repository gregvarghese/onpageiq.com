<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookIntegration>
 */
class WebhookIntegrationFactory extends Factory
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
            'type' => fake()->randomElement(['generic', 'slack', 'jira', 'github', 'linear']),
            'name' => fake()->words(2, true).' Integration',
            'webhook_url' => fake()->url(),
            'config' => [],
            'events' => ['scan.completed', 'issues.found'],
            'is_active' => true,
        ];
    }

    /**
     * Set type to Slack.
     */
    public function slack(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'slack',
            'name' => 'Slack Notifications',
            'config' => [
                'channel' => '#spell-check-alerts',
            ],
        ]);
    }

    /**
     * Set type to Jira.
     */
    public function jira(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'jira',
            'name' => 'Jira Issues',
            'config' => [
                'project_key' => 'SPELL',
                'issue_type' => 'Task',
            ],
        ]);
    }

    /**
     * Set type to GitHub.
     */
    public function github(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'github',
            'name' => 'GitHub Issues',
            'config' => [
                'repo' => 'owner/repo',
                'labels' => ['spelling', 'content'],
            ],
        ]);
    }

    /**
     * Set type to Linear.
     */
    public function linear(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'linear',
            'name' => 'Linear Issues',
            'config' => [
                'team_id' => fake()->uuid(),
            ],
        ]);
    }

    /**
     * Set type to generic webhook.
     */
    public function generic(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'generic',
            'name' => 'Custom Webhook',
            'config' => [
                'headers' => [
                    'X-Custom-Header' => 'value',
                ],
            ],
        ]);
    }

    /**
     * Set as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
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
        ]);
    }
}
