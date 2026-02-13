<?php

namespace App\Services\AI;

use App\Models\AIBudget;
use App\Models\Organization;
use App\Models\User;
use App\Services\AI\DTOs\BudgetCheckResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Service for managing AI usage budgets.
 */
class AIBudgetService
{
    /**
     * Check budget for an organization.
     */
    public function checkOrganizationBudget(Organization $organization): BudgetCheckResult
    {
        $budget = $this->getOrCreateBudget($organization);

        return $this->checkBudget($budget);
    }

    /**
     * Check budget for a user within an organization.
     */
    public function checkUserBudget(User $user, ?Organization $organization = null): BudgetCheckResult
    {
        $budget = $this->getOrCreateBudget($organization, $user);

        return $this->checkBudget($budget);
    }

    /**
     * Check both organization and user budgets, return the most restrictive.
     */
    public function checkCombinedBudget(User $user, ?Organization $organization = null): BudgetCheckResult
    {
        // Check organization budget first
        if ($organization) {
            $orgResult = $this->checkOrganizationBudget($organization);
            if ($orgResult->isBlocked()) {
                return $orgResult;
            }
        }

        // Check user-specific budget
        $userResult = $this->checkUserBudget($user, $organization);
        if ($userResult->isBlocked()) {
            return $userResult;
        }

        // Return the more restrictive result (over budget takes precedence)
        if ($organization && ! $orgResult->withinBudget) {
            return $orgResult;
        }

        if (! $userResult->withinBudget) {
            return $userResult;
        }

        // Return whichever has higher usage percentage
        if ($organization && $orgResult->usagePercentage !== null && $userResult->usagePercentage !== null) {
            return $orgResult->usagePercentage > $userResult->usagePercentage ? $orgResult : $userResult;
        }

        return $userResult;
    }

    /**
     * Record usage against budgets.
     */
    public function recordUsage(float $cost, ?Organization $organization = null, ?User $user = null): void
    {
        DB::transaction(function () use ($cost, $organization, $user) {
            // Update organization budget if exists
            if ($organization) {
                $this->updateBudgetUsage($organization, null, $cost);
            }

            // Update user budget if exists
            if ($user) {
                $this->updateBudgetUsage($organization, $user, $cost);
            }
        });
    }

    /**
     * Reset budgets for a new month.
     */
    public function resetMonthlyBudgets(): int
    {
        $currentPeriodStart = Carbon::now()->startOfMonth();

        return AIBudget::query()
            ->where('is_active', true)
            ->where(function ($query) use ($currentPeriodStart) {
                $query->whereNull('current_period_start')
                    ->orWhere('current_period_start', '<', $currentPeriodStart);
            })
            ->update([
                'current_month_usage' => 0,
                'current_period_start' => $currentPeriodStart,
            ]);
    }

    /**
     * Get or create a budget for organization/user.
     */
    public function getOrCreateBudget(?Organization $organization = null, ?User $user = null): AIBudget
    {
        return AIBudget::firstOrCreate(
            [
                'organization_id' => $organization?->id,
                'user_id' => $user?->id,
            ],
            [
                'monthly_limit' => null, // Unlimited by default
                'warning_threshold' => 80.00,
                'current_month_usage' => 0,
                'current_period_start' => Carbon::now()->startOfMonth(),
                'is_active' => true,
                'allow_override' => true,
            ]
        );
    }

    /**
     * Set budget limit for organization/user.
     */
    public function setBudgetLimit(
        ?float $monthlyLimit,
        ?Organization $organization = null,
        ?User $user = null,
        float $warningThreshold = 80.00,
        bool $allowOverride = true,
    ): AIBudget {
        $budget = $this->getOrCreateBudget($organization, $user);

        $budget->update([
            'monthly_limit' => $monthlyLimit,
            'warning_threshold' => $warningThreshold,
            'allow_override' => $allowOverride,
        ]);

        return $budget->fresh();
    }

    /**
     * Check a specific budget.
     */
    protected function checkBudget(AIBudget $budget): BudgetCheckResult
    {
        if (! $budget->is_active || $budget->monthly_limit === null) {
            return BudgetCheckResult::unlimited();
        }

        // Ensure we're in the current period
        $this->ensureCurrentPeriod($budget);

        $currentUsage = (float) $budget->current_month_usage;
        $monthlyLimit = (float) $budget->monthly_limit;
        $warningThreshold = (float) $budget->warning_threshold;

        // Check if over budget
        if ($currentUsage >= $monthlyLimit) {
            return BudgetCheckResult::overBudget(
                $currentUsage,
                $monthlyLimit,
                $budget->allow_override,
            );
        }

        // Check if at warning threshold
        $percentage = ($currentUsage / $monthlyLimit) * 100;
        if ($percentage >= $warningThreshold) {
            return BudgetCheckResult::atWarning(
                $currentUsage,
                $monthlyLimit,
                $warningThreshold,
            );
        }

        return BudgetCheckResult::withinBudget($currentUsage, $monthlyLimit);
    }

    /**
     * Update budget usage.
     */
    protected function updateBudgetUsage(?Organization $organization, ?User $user, float $cost): void
    {
        $budget = AIBudget::query()
            ->where('organization_id', $organization?->id)
            ->where('user_id', $user?->id)
            ->first();

        if (! $budget) {
            return;
        }

        $this->ensureCurrentPeriod($budget);

        $budget->increment('current_month_usage', $cost);
    }

    /**
     * Ensure budget is in the current period, reset if needed.
     */
    protected function ensureCurrentPeriod(AIBudget $budget): void
    {
        $currentPeriodStart = Carbon::now()->startOfMonth();

        if ($budget->current_period_start === null || Carbon::parse($budget->current_period_start)->lt($currentPeriodStart)) {
            $budget->update([
                'current_month_usage' => 0,
                'current_period_start' => $currentPeriodStart,
            ]);
        }
    }
}
