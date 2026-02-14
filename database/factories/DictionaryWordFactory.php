<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DictionaryWord>
 */
class DictionaryWordFactory extends Factory
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
            'added_by_user_id' => User::factory(),
            'word' => fake()->unique()->word(),
            'source' => 'custom',
            'notes' => null,
        ];
    }

    /**
     * Set the word to a specific value.
     */
    public function withWord(string $word): static
    {
        return $this->state(fn (array $attributes) => [
            'word' => $word,
        ]);
    }

    /**
     * Make this a project-specific word.
     */
    public function forProject(?Project $project = null): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => $project?->id ?? Project::factory(),
        ]);
    }

    /**
     * Make this an organization-level word (no project).
     */
    public function organizationLevel(): static
    {
        return $this->state(fn (array $attributes) => [
            'project_id' => null,
        ]);
    }

    /**
     * Set the source to imported.
     */
    public function imported(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'imported',
        ]);
    }

    /**
     * Set the source to scan_suggestion.
     */
    public function fromScan(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'scan_suggestion',
        ]);
    }
}
