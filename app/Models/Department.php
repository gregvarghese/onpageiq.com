<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    /** @use HasFactory<\Database\Factories\DepartmentFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'credit_budget',
        'credit_used',
    ];

    /**
     * Get the organization this department belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the users in this department.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get remaining credit budget.
     */
    public function getRemainingBudget(): int
    {
        return max(0, $this->credit_budget - $this->credit_used);
    }

    /**
     * Check if department has budget available.
     */
    public function hasBudget(int $amount = 1): bool
    {
        return $this->getRemainingBudget() >= $amount;
    }

    /**
     * Use credits from department budget.
     */
    public function useCredits(int $amount): bool
    {
        if (! $this->hasBudget($amount)) {
            return false;
        }

        $this->increment('credit_used', $amount);

        return true;
    }
}
