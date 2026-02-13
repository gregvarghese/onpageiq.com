<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use Billable, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'subscription_tier',
        'stripe_id',
        'stripe_subscription_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'subscription_ends_at',
        'credit_balance',
        'overdraft_balance',
        'free_credits_used',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'subscription_ends_at' => 'datetime',
            'free_credits_used' => 'boolean',
            'settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Organization $organization) {
            if (empty($organization->slug)) {
                $organization->slug = Str::slug($organization->name).'-'.Str::random(6);
            }
        });
    }

    /**
     * Get the users belonging to this organization.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the departments in this organization.
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get the projects in this organization.
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Get the credit transactions for this organization.
     */
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(CreditTransaction::class);
    }

    /**
     * Get the webhook endpoints for this organization.
     */
    public function webhookEndpoints(): HasMany
    {
        return $this->hasMany(WebhookEndpoint::class);
    }

    /**
     * Check if the organization has team features (Team or Enterprise tier).
     */
    public function hasTeamFeatures(): bool
    {
        return in_array($this->subscription_tier, ['team', 'enterprise']);
    }

    /**
     * Check if the organization is on the free tier.
     */
    public function isFreeTier(): bool
    {
        return $this->subscription_tier === 'free';
    }

    /**
     * Check if the organization has available credits.
     */
    public function hasCredits(int $amount = 1): bool
    {
        return $this->credit_balance >= $amount;
    }

    /**
     * Deduct credits from the organization balance.
     */
    public function deductCredits(int $amount): bool
    {
        if ($this->credit_balance >= $amount) {
            $this->decrement('credit_balance', $amount);

            return true;
        }

        // Use overdraft if allowed
        $shortfall = $amount - $this->credit_balance;
        $this->update([
            'credit_balance' => 0,
            'overdraft_balance' => $this->overdraft_balance + $shortfall,
        ]);

        return true;
    }

    /**
     * Add credits to the organization balance.
     */
    public function addCredits(int $amount): void
    {
        // First pay off any overdraft
        if ($this->overdraft_balance > 0) {
            $payoff = min($amount, $this->overdraft_balance);
            $this->decrement('overdraft_balance', $payoff);
            $amount -= $payoff;
        }

        if ($amount > 0) {
            $this->increment('credit_balance', $amount);
        }
    }

    /**
     * Get the history retention days based on subscription tier.
     */
    public function getHistoryRetentionDays(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => 30,
            'pro', 'team', 'enterprise' => null, // Unlimited
            default => 30,
        };
    }

    /**
     * Get the enabled checks for this organization based on tier.
     *
     * @return array<string>
     */
    public function getDefaultChecks(): array
    {
        return match ($this->subscription_tier) {
            'free' => ['spelling'],
            default => ['spelling', 'grammar', 'seo', 'readability'],
        };
    }
}
