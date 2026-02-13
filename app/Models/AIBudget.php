<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIBudget extends Model
{
    use HasFactory;

    protected $table = 'ai_budgets';

    protected $fillable = [
        'organization_id',
        'user_id',
        'monthly_limit',
        'warning_threshold',
        'current_month_usage',
        'current_period_start',
        'is_active',
        'allow_override',
    ];

    protected function casts(): array
    {
        return [
            'monthly_limit' => 'decimal:2',
            'warning_threshold' => 'decimal:2',
            'current_month_usage' => 'decimal:6',
            'current_period_start' => 'date',
            'is_active' => 'boolean',
            'allow_override' => 'boolean',
        ];
    }

    /**
     * Get the organization this budget belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user this budget belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is an organization-level budget.
     */
    public function isOrganizationBudget(): bool
    {
        return $this->organization_id !== null && $this->user_id === null;
    }

    /**
     * Check if this is a user-level budget.
     */
    public function isUserBudget(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * Get the usage percentage.
     */
    public function getUsagePercentageAttribute(): ?float
    {
        if ($this->monthly_limit === null || $this->monthly_limit == 0) {
            return null;
        }

        return ($this->current_month_usage / $this->monthly_limit) * 100;
    }

    /**
     * Get remaining budget amount.
     */
    public function getRemainingBudgetAttribute(): ?float
    {
        if ($this->monthly_limit === null) {
            return null;
        }

        return max(0, $this->monthly_limit - $this->current_month_usage);
    }

    /**
     * Check if budget is over limit.
     */
    public function isOverBudget(): bool
    {
        if ($this->monthly_limit === null) {
            return false;
        }

        return $this->current_month_usage >= $this->monthly_limit;
    }

    /**
     * Check if budget is at warning threshold.
     */
    public function isAtWarning(): bool
    {
        if ($this->monthly_limit === null || $this->warning_threshold === null) {
            return false;
        }

        $percentage = ($this->current_month_usage / $this->monthly_limit) * 100;

        return $percentage >= $this->warning_threshold && ! $this->isOverBudget();
    }

    /**
     * Get a human-readable label for the budget.
     */
    public function getLabelAttribute(): string
    {
        if ($this->isOrganizationBudget()) {
            return $this->organization?->name ?? 'Organization Budget';
        }

        if ($this->isUserBudget()) {
            $label = $this->user?->name ?? 'User Budget';
            if ($this->organization) {
                $label .= ' ('.$this->organization->name.')';
            }

            return $label;
        }

        return 'Global Budget';
    }

    /**
     * Scope to get active budgets.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get organization-level budgets.
     */
    public function scopeOrganizationLevel($query)
    {
        return $query->whereNotNull('organization_id')->whereNull('user_id');
    }

    /**
     * Scope to get user-level budgets.
     */
    public function scopeUserLevel($query)
    {
        return $query->whereNotNull('user_id');
    }
}
