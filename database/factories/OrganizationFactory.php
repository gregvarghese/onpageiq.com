<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
            'subscription_tier' => 'free',
            'credit_balance' => 5,
            'overdraft_balance' => 0,
            'free_credits_used' => false,
            'settings' => [],
        ];
    }

    /**
     * Indicate that the organization is on the Pro tier.
     */
    public function pro(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'pro',
            'credit_balance' => 100,
        ]);
    }

    /**
     * Indicate that the organization is on the Team tier.
     */
    public function team(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'team',
            'credit_balance' => 500,
        ]);
    }

    /**
     * Indicate that the organization is on the Enterprise tier.
     */
    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'subscription_tier' => 'enterprise',
            'credit_balance' => 2000,
        ]);
    }

    /**
     * Indicate that the free credits have been used.
     */
    public function freeCreditsUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'free_credits_used' => true,
            'credit_balance' => 0,
        ]);
    }
}
