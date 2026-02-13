<?php

namespace Database\Factories;

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CreditTransaction>
 */
class CreditTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomElement([10, 25, 50, 100, -1, -3, -5]);

        return [
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'type' => fake()->randomElement([
                CreditTransaction::TYPE_SUBSCRIPTION_CREDIT,
                CreditTransaction::TYPE_PURCHASE,
                CreditTransaction::TYPE_USAGE,
            ]),
            'amount' => $amount,
            'balance_after' => fake()->numberBetween(0, 500),
            'description' => fake()->sentence(),
            'metadata' => null,
        ];
    }

    public function subscriptionCredit(int $amount = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_SUBSCRIPTION_CREDIT,
            'amount' => $amount,
            'description' => 'Monthly subscription credits',
        ]);
    }

    public function purchase(int $amount = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_PURCHASE,
            'amount' => $amount,
            'description' => 'Credit pack purchase',
        ]);
    }

    public function usage(int $amount = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_USAGE,
            'amount' => -abs($amount),
            'description' => 'Scan credit usage',
        ]);
    }

    public function refund(int $amount = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CreditTransaction::TYPE_REFUND,
            'amount' => abs($amount),
            'description' => 'Credit refund',
        ]);
    }
}
