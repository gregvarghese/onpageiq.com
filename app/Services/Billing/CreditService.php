<?php

namespace App\Services\Billing;

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Add subscription credits to an organization.
     */
    public function addSubscriptionCredits(Organization $organization, int $amount, ?string $description = null): CreditTransaction
    {
        return $this->addCredits(
            organization: $organization,
            amount: $amount,
            type: CreditTransaction::TYPE_SUBSCRIPTION_CREDIT,
            description: $description ?? 'Monthly subscription credits',
        );
    }

    /**
     * Add purchased credits to an organization.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function addPurchasedCredits(
        Organization $organization,
        int $amount,
        ?string $description = null,
        ?array $metadata = null,
    ): CreditTransaction {
        return $this->addCredits(
            organization: $organization,
            amount: $amount,
            type: CreditTransaction::TYPE_PURCHASE,
            description: $description ?? 'Credit pack purchase',
            metadata: $metadata,
        );
    }

    /**
     * Add bonus credits to an organization.
     */
    public function addBonusCredits(Organization $organization, int $amount, ?string $description = null): CreditTransaction
    {
        return $this->addCredits(
            organization: $organization,
            amount: $amount,
            type: CreditTransaction::TYPE_BONUS,
            description: $description ?? 'Bonus credits',
        );
    }

    /**
     * Deduct credits for usage.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function deductCredits(
        Organization $organization,
        int $amount,
        ?string $description = null,
        ?User $user = null,
        ?array $metadata = null,
    ): CreditTransaction {
        return DB::transaction(function () use ($organization, $amount, $description, $user, $metadata) {
            $organization->lockForUpdate();
            $organization->refresh();

            $organization->deductCredits($amount);
            $organization->refresh();

            return CreditTransaction::create([
                'organization_id' => $organization->id,
                'user_id' => $user?->id,
                'type' => CreditTransaction::TYPE_USAGE,
                'amount' => -abs($amount),
                'balance_after' => $organization->credit_balance,
                'description' => $description ?? 'Credit usage',
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Refund credits to an organization.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function refundCredits(
        Organization $organization,
        int $amount,
        ?string $description = null,
        ?array $metadata = null,
    ): CreditTransaction {
        return $this->addCredits(
            organization: $organization,
            amount: $amount,
            type: CreditTransaction::TYPE_REFUND,
            description: $description ?? 'Credit refund',
            metadata: $metadata,
        );
    }

    /**
     * Adjust credits (admin operation).
     */
    public function adjustCredits(
        Organization $organization,
        int $amount,
        string $description,
        ?User $adminUser = null,
    ): CreditTransaction {
        if ($amount >= 0) {
            return $this->addCredits(
                organization: $organization,
                amount: $amount,
                type: CreditTransaction::TYPE_ADJUSTMENT,
                description: $description,
                user: $adminUser,
            );
        }

        return DB::transaction(function () use ($organization, $amount, $description, $adminUser) {
            $organization->lockForUpdate();
            $organization->refresh();

            $organization->deductCredits(abs($amount));
            $organization->refresh();

            return CreditTransaction::create([
                'organization_id' => $organization->id,
                'user_id' => $adminUser?->id,
                'type' => CreditTransaction::TYPE_ADJUSTMENT,
                'amount' => $amount,
                'balance_after' => $organization->credit_balance,
                'description' => $description,
            ]);
        });
    }

    /**
     * Add credits to an organization with a transaction record.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    protected function addCredits(
        Organization $organization,
        int $amount,
        string $type,
        ?string $description = null,
        ?User $user = null,
        ?array $metadata = null,
    ): CreditTransaction {
        return DB::transaction(function () use ($organization, $amount, $type, $description, $user, $metadata) {
            $organization->lockForUpdate();
            $organization->refresh();

            $organization->addCredits($amount);
            $organization->refresh();

            return CreditTransaction::create([
                'organization_id' => $organization->id,
                'user_id' => $user?->id,
                'type' => $type,
                'amount' => abs($amount),
                'balance_after' => $organization->credit_balance,
                'description' => $description,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Check if organization has sufficient credits.
     */
    public function hasCredits(Organization $organization, int $amount = 1): bool
    {
        return $organization->hasCredits($amount);
    }

    /**
     * Get the credit balance for an organization.
     */
    public function getBalance(Organization $organization): int
    {
        return $organization->credit_balance;
    }

    /**
     * Get credit transaction history for an organization.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, CreditTransaction>
     */
    public function getTransactionHistory(Organization $organization, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return $organization->creditTransactions()
            ->with('user:id,name')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Get usage statistics for an organization.
     *
     * @return array<string, mixed>
     */
    public function getUsageStats(Organization $organization, ?string $period = 'month'): array
    {
        $startDate = match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'year' => now()->subYear(),
            default => now()->subMonth(),
        };

        $transactions = $organization->creditTransactions()
            ->where('created_at', '>=', $startDate)
            ->get();

        $creditsAdded = $transactions->where('amount', '>', 0)->sum('amount');
        $creditsUsed = abs($transactions->where('amount', '<', 0)->sum('amount'));

        return [
            'period' => $period,
            'credits_added' => $creditsAdded,
            'credits_used' => $creditsUsed,
            'net_change' => $creditsAdded - $creditsUsed,
            'current_balance' => $organization->credit_balance,
            'transaction_count' => $transactions->count(),
        ];
    }
}
