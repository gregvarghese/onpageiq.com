<?php

namespace Database\Factories;

use App\Enums\AIUsageCategory;
use App\Models\AIUsageLog;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIUsageLog>
 */
class AIUsageLogFactory extends Factory
{
    protected $model = AIUsageLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $promptTokens = fake()->numberBetween(100, 2000);
        $completionTokens = fake()->numberBetween(50, 1000);

        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'project_id' => null,
            'provider' => fake()->randomElement(['openai', 'anthropic']),
            'model' => fake()->randomElement(['gpt-4o-mini', 'gpt-4o', 'claude-3-sonnet']),
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $promptTokens + $completionTokens,
            'cost' => fake()->randomFloat(6, 0.001, 0.5),
            'duration_ms' => fake()->numberBetween(500, 5000),
            'prompt_content' => fake()->paragraph(),
            'response_content' => fake()->paragraphs(2, true),
            'content_redacted' => false,
            'redaction_summary' => null,
            'category' => fake()->randomElement(AIUsageCategory::cases()),
            'purpose_detail' => fake()->optional()->sentence(),
            'task_type' => null,
            'loggable_type' => null,
            'loggable_id' => null,
            'success' => true,
            'error_message' => null,
            'budget_override' => false,
            'budget_override_by' => null,
            'metadata' => null,
        ];
    }

    /**
     * Indicate the request failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'success' => false,
            'error_message' => fake()->sentence(),
            'completion_tokens' => 0,
            'cost' => 0,
        ]);
    }

    /**
     * Indicate content was redacted.
     */
    public function redacted(): static
    {
        return $this->state(fn (array $attributes) => [
            'content_redacted' => true,
            'redaction_summary' => [
                'email' => fake()->numberBetween(1, 3),
                'phone' => fake()->numberBetween(0, 2),
            ],
        ]);
    }

    /**
     * Indicate budget was overridden.
     */
    public function withBudgetOverride(): static
    {
        return $this->state(fn (array $attributes) => [
            'budget_override' => true,
            'budget_override_by' => User::factory(),
        ]);
    }

    /**
     * Set specific category.
     */
    public function withCategory(AIUsageCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }

    /**
     * Use GPT-4o-mini model.
     */
    public function gpt4oMini(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
        ]);
    }

    /**
     * Use GPT-4o model.
     */
    public function gpt4o(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'openai',
            'model' => 'gpt-4o',
        ]);
    }
}
