<?php

namespace App\Services\AI\DTOs;

/**
 * Result of budget check operation.
 */
readonly class BudgetCheckResult
{
    public function __construct(
        public bool $withinBudget,
        public bool $requiresConfirmation,
        public bool $allowOverride,
        public ?float $currentUsage = null,
        public ?float $monthlyLimit = null,
        public ?float $usagePercentage = null,
        public ?string $message = null,
    ) {}

    /**
     * Create result for when no budget is set (unlimited).
     */
    public static function unlimited(): self
    {
        return new self(
            withinBudget: true,
            requiresConfirmation: false,
            allowOverride: true,
            message: 'No budget limit set',
        );
    }

    /**
     * Create result for when within budget.
     */
    public static function withinBudget(
        float $currentUsage,
        float $monthlyLimit,
    ): self {
        $percentage = ($currentUsage / $monthlyLimit) * 100;

        return new self(
            withinBudget: true,
            requiresConfirmation: false,
            allowOverride: true,
            currentUsage: $currentUsage,
            monthlyLimit: $monthlyLimit,
            usagePercentage: $percentage,
        );
    }

    /**
     * Create result for when at warning threshold.
     */
    public static function atWarning(
        float $currentUsage,
        float $monthlyLimit,
        float $warningThreshold,
    ): self {
        $percentage = ($currentUsage / $monthlyLimit) * 100;

        return new self(
            withinBudget: true,
            requiresConfirmation: false,
            allowOverride: true,
            currentUsage: $currentUsage,
            monthlyLimit: $monthlyLimit,
            usagePercentage: $percentage,
            message: sprintf('Warning: %.1f%% of monthly budget used (threshold: %.0f%%)', $percentage, $warningThreshold),
        );
    }

    /**
     * Create result for when over budget.
     */
    public static function overBudget(
        float $currentUsage,
        float $monthlyLimit,
        bool $allowOverride,
    ): self {
        $percentage = ($currentUsage / $monthlyLimit) * 100;

        return new self(
            withinBudget: false,
            requiresConfirmation: $allowOverride,
            allowOverride: $allowOverride,
            currentUsage: $currentUsage,
            monthlyLimit: $monthlyLimit,
            usagePercentage: $percentage,
            message: $allowOverride
                ? 'Monthly budget exceeded. Confirmation required to proceed.'
                : 'Monthly budget exceeded. Further AI usage is blocked.',
        );
    }

    /**
     * Check if budget is blocked (over budget and no override allowed).
     */
    public function isBlocked(): bool
    {
        return ! $this->withinBudget && ! $this->allowOverride;
    }

    /**
     * Get remaining budget amount.
     */
    public function remainingBudget(): ?float
    {
        if ($this->monthlyLimit === null || $this->currentUsage === null) {
            return null;
        }

        return max(0, $this->monthlyLimit - $this->currentUsage);
    }
}
