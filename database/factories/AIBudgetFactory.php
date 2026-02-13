<?php

namespace Database\Factories;

use App\Models\AIBudget;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AIBudget>
 */
class AIBudgetFactory extends Factory
{
    protected $model = AIBudget::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'user_id' => null,
            'monthly_limit' => fake()->randomFloat(2, 10, 500),
            'warning_threshold' => 80.00,
            'current_month_usage' => fake()->randomFloat(6, 0, 50),
            'current_period_start' => Carbon::now()->startOfMonth(),
            'is_active' => true,
            'allow_override' => true,
        ];
    }

    /**
     * Organization-level budget.
     */
    public function forOrganization(Organization $organization): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization->id,
            'user_id' => null,
        ]);
    }

    /**
     * User-level budget.
     */
    public function forUser(User $user, ?Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'organization_id' => $organization?->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Unlimited budget (no limit set).
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'monthly_limit' => null,
        ]);
    }

    /**
     * Budget at warning threshold.
     */
    public function atWarning(): static
    {
        return $this->state(function (array $attributes) {
            $limit = $attributes['monthly_limit'] ?? 100;

            return [
                'monthly_limit' => $limit,
                'current_month_usage' => $limit * 0.85, // 85% used
            ];
        });
    }

    /**
     * Budget over limit.
     */
    public function overBudget(): static
    {
        return $this->state(function (array $attributes) {
            $limit = $attributes['monthly_limit'] ?? 100;

            return [
                'monthly_limit' => $limit,
                'current_month_usage' => $limit * 1.1, // 110% used
            ];
        });
    }

    /**
     * Override not allowed.
     */
    public function noOverride(): static
    {
        return $this->state(fn (array $attributes) => [
            'allow_override' => false,
        ]);
    }

    /**
     * Inactive budget.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
