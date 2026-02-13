<?php

namespace Database\Factories;

use App\Models\ScanResult;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['spelling', 'grammar', 'seo', 'readability'];
        $severities = ['error', 'warning', 'suggestion'];

        return [
            'scan_result_id' => ScanResult::factory(),
            'category' => fake()->randomElement($categories),
            'severity' => fake()->randomElement($severities),
            'text_excerpt' => fake()->sentence(),
            'suggestion' => fake()->sentence(),
            'dom_selector' => null,
            'screenshot_path' => null,
            'position' => [
                'start' => fake()->numberBetween(0, 500),
                'end' => fake()->numberBetween(501, 600),
            ],
        ];
    }

    /**
     * Create a spelling issue.
     */
    public function spelling(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'spelling',
            'text_excerpt' => 'The word "recieve" is misspelled.',
            'suggestion' => 'Change to "receive".',
        ]);
    }

    /**
     * Create a grammar issue.
     */
    public function grammar(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'grammar',
            'text_excerpt' => 'Subject-verb agreement error.',
            'suggestion' => 'Use "are" instead of "is" for plural subjects.',
        ]);
    }

    /**
     * Create an SEO issue.
     */
    public function seo(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'seo',
            'text_excerpt' => 'Missing meta description.',
            'suggestion' => 'Add a meta description between 150-160 characters.',
        ]);
    }

    /**
     * Create a readability issue.
     */
    public function readability(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'readability',
            'text_excerpt' => 'Sentence too long (45 words).',
            'suggestion' => 'Consider breaking this into shorter sentences.',
        ]);
    }

    /**
     * Create an error severity.
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'error',
        ]);
    }

    /**
     * Create a warning severity.
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'warning',
        ]);
    }

    /**
     * Create a suggestion severity.
     */
    public function suggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'severity' => 'suggestion',
        ]);
    }
}
