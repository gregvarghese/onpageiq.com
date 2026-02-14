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
     * Get all dictionary words for this organization.
     */
    public function dictionaryWords(): HasMany
    {
        return $this->hasMany(DictionaryWord::class);
    }

    /**
     * Get the scan templates for this organization.
     */
    public function scanTemplates(): HasMany
    {
        return $this->hasMany(ScanTemplate::class);
    }

    /**
     * Get the default scan template for this organization.
     */
    public function defaultScanTemplate(): ?ScanTemplate
    {
        return $this->scanTemplates()->where('is_default', true)->first();
    }

    /**
     * Get the webhook integrations for this organization.
     */
    public function webhookIntegrations(): HasMany
    {
        return $this->hasMany(WebhookIntegration::class);
    }

    /**
     * Get the dismissed issues for this organization.
     */
    public function dismissedIssues(): HasMany
    {
        return $this->hasMany(DismissedIssue::class);
    }

    /**
     * Get organization-level dictionary words (not project-specific).
     */
    public function organizationDictionaryWords(): HasMany
    {
        return $this->dictionaryWords()->whereNull('project_id');
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

    /**
     * Get the available checks for this organization based on tier.
     *
     * @return array<string, bool>
     */
    public function getAvailableChecks(): array
    {
        $allChecks = ['spelling', 'grammar', 'seo', 'readability'];
        $enabledChecks = $this->getDefaultChecks();

        $available = [];
        foreach ($allChecks as $check) {
            $available[$check] = in_array($check, $enabledChecks);
        }

        return $available;
    }

    /**
     * Get the organization-level dictionary word limit based on subscription tier.
     */
    public function getOrganizationDictionaryWordLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free', 'pro' => null, // No org dictionary for free/pro
            'team' => 1000,
            'enterprise' => null, // Unlimited
            default => null,
        };
    }

    /**
     * Get the project-level dictionary word limit based on subscription tier.
     */
    public function getProjectDictionaryWordLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => null, // No project dictionary for free
            'pro' => 100,
            'team' => 500,
            'enterprise' => null, // Unlimited
            default => null,
        };
    }

    /**
     * Get the industry dictionary limit based on subscription tier.
     */
    public function getIndustryDictionaryLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => null, // No industry dictionaries for free
            'pro' => 1,
            'team' => 3,
            'enterprise' => null, // Unlimited (all)
            default => null,
        };
    }

    /**
     * Check if the organization can use organization-level dictionaries.
     */
    public function canUseOrganizationDictionary(): bool
    {
        return in_array($this->subscription_tier, ['team', 'enterprise']);
    }

    /**
     * Check if the organization can use project-level dictionaries.
     */
    public function canUseProjectDictionary(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Check if the organization can use industry dictionaries.
     */
    public function canUseIndustryDictionaries(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Check if the organization can add more words to the organization dictionary.
     */
    public function canAddOrganizationDictionaryWord(): bool
    {
        if (! $this->canUseOrganizationDictionary()) {
            return false;
        }

        $limit = $this->getOrganizationDictionaryWordLimit();

        if ($limit === null) {
            return true; // Unlimited
        }

        return $this->organizationDictionaryWords()->count() < $limit;
    }

    /**
     * Check if the organization can do bulk import.
     */
    public function canBulkImportDictionary(): bool
    {
        return in_array($this->subscription_tier, ['team', 'enterprise']);
    }

    /**
     * Get the URL groups limit based on subscription tier.
     */
    public function getUrlGroupsLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => 0,
            'pro' => 5,
            'team' => 20,
            'enterprise' => null, // Unlimited
            default => 0,
        };
    }

    /**
     * Check if the organization can create URL groups.
     */
    public function canCreateUrlGroups(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Get the scheduled scans limit based on subscription tier.
     */
    public function getScheduledScansLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => 0,
            'pro' => 1,
            'team' => 5,
            'enterprise' => null, // Unlimited
            default => 0,
        };
    }

    /**
     * Check if the organization can create scheduled scans.
     */
    public function canCreateScheduledScans(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Get the scan templates limit based on subscription tier.
     */
    public function getScanTemplatesLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => 0,
            'pro' => 3,
            'team' => 10,
            'enterprise' => null, // Unlimited
            default => 0,
        };
    }

    /**
     * Check if the organization can create scan templates.
     */
    public function canCreateScanTemplates(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Get the webhook integrations limit based on subscription tier.
     */
    public function getWebhookIntegrationsLimit(): ?int
    {
        return match ($this->subscription_tier) {
            'free' => 0,
            'pro' => 1,
            'team' => 5,
            'enterprise' => null, // Unlimited
            default => 0,
        };
    }

    /**
     * Check if the organization can create webhook integrations.
     */
    public function canCreateWebhookIntegrations(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Check if the organization can use issue assignments.
     */
    public function canUseIssueAssignments(): bool
    {
        return in_array($this->subscription_tier, ['team', 'enterprise']);
    }

    /**
     * Check if the organization can use white-label PDF reports.
     */
    public function canUseWhiteLabelPdf(): bool
    {
        return $this->subscription_tier === 'enterprise';
    }

    /**
     * Check if the organization can use cross-URL comparison.
     */
    public function canUseCrossUrlComparison(): bool
    {
        return in_array($this->subscription_tier, ['pro', 'team', 'enterprise']);
    }

    /**
     * Get the remaining organization dictionary word slots.
     */
    public function getRemainingOrganizationDictionarySlots(): ?int
    {
        $limit = $this->getOrganizationDictionaryWordLimit();

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $this->organizationDictionaryWords()->count());
    }

    /**
     * Get the total dictionary words limit (org + project) based on subscription tier.
     */
    public function getDictionaryWordsLimit(): ?int
    {
        $orgLimit = $this->getOrganizationDictionaryWordLimit();
        $projectLimit = $this->getProjectDictionaryWordLimit();

        // If both are null, unlimited
        if ($orgLimit === null && $projectLimit === null) {
            return null;
        }

        // If only one is set, return it
        if ($orgLimit === null) {
            return $projectLimit;
        }
        if ($projectLimit === null) {
            return $orgLimit;
        }

        // Return the sum for combined limit
        return $orgLimit + $projectLimit;
    }
}
