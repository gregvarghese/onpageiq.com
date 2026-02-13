<?php

namespace App\Services\Billing;

use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

class SubscriptionService
{
    /**
     * Cache TTL for tier configurations (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get all available subscription tiers.
     *
     * @return array<string, array>
     */
    public function getTiers(): array
    {
        return Cache::remember('subscription_tiers', self::CACHE_TTL, function () {
            return config('onpageiq.tiers', []);
        });
    }

    /**
     * Get a specific tier configuration.
     *
     * @return array<string, mixed>|null
     */
    public function getTier(string $tierSlug): ?array
    {
        return Cache::remember("subscription_tier_{$tierSlug}", self::CACHE_TTL, function () use ($tierSlug) {
            return config("onpageiq.tiers.{$tierSlug}");
        });
    }

    /**
     * Get the tier configuration for an organization.
     *
     * @return array<string, mixed>
     */
    public function getOrganizationTier(Organization $organization): array
    {
        return $this->getTier($organization->subscription_tier) ?? $this->getTier('free');
    }

    /**
     * Clear cached tier configurations.
     */
    public function clearCache(): void
    {
        Cache::forget('subscription_tiers');
        foreach (['free', 'pro', 'team', 'enterprise'] as $tier) {
            Cache::forget("subscription_tier_{$tier}");
        }
    }

    /**
     * Check if an organization has a specific feature.
     */
    public function hasFeature(Organization $organization, string $feature): bool
    {
        $tier = $this->getOrganizationTier($organization);

        return $tier['features'][$feature] ?? false;
    }

    /**
     * Get the project limit for an organization.
     */
    public function getProjectLimit(Organization $organization): ?int
    {
        $tier = $this->getOrganizationTier($organization);

        return $tier['projects_limit'] ?? 1;
    }

    /**
     * Get the team size limit for an organization.
     */
    public function getTeamSizeLimit(Organization $organization): ?int
    {
        $tier = $this->getOrganizationTier($organization);

        return $tier['team_size'] ?? 1;
    }

    /**
     * Get the enabled checks for an organization based on tier.
     *
     * @return array<string>
     */
    public function getEnabledChecks(Organization $organization): array
    {
        $tier = $this->getOrganizationTier($organization);

        return $tier['checks'] ?? ['spelling'];
    }

    /**
     * Get the queue priority for an organization.
     */
    public function getQueuePriority(Organization $organization): string
    {
        $tier = $this->getOrganizationTier($organization);

        return $tier['queue_priority'] ?? 'low';
    }

    /**
     * Get monthly credits for a tier.
     */
    public function getMonthlyCredits(string $tierSlug): int
    {
        $tier = $this->getTier($tierSlug);

        return $tier['credits_monthly'] ?? 0;
    }

    /**
     * Check if an organization can add more projects.
     */
    public function canAddProject(Organization $organization): bool
    {
        $limit = $this->getProjectLimit($organization);

        if ($limit === null) {
            return true; // Unlimited
        }

        return $organization->projects()->count() < $limit;
    }

    /**
     * Check if an organization can add more team members.
     */
    public function canAddTeamMember(Organization $organization): bool
    {
        $limit = $this->getTeamSizeLimit($organization);

        if ($limit === null) {
            return true; // Unlimited
        }

        return $organization->users()->count() < $limit;
    }

    /**
     * Get available credit packs.
     *
     * @return array<string, array>
     */
    public function getCreditPacks(): array
    {
        return config('onpageiq.credit_packs', []);
    }

    /**
     * Get a specific credit pack.
     *
     * @return array<string, mixed>|null
     */
    public function getCreditPack(string $packSlug): ?array
    {
        return config("onpageiq.credit_packs.{$packSlug}");
    }

    /**
     * Check if organization can upgrade to a tier.
     */
    public function canUpgradeTo(Organization $organization, string $targetTier): bool
    {
        $currentTier = $organization->subscription_tier;
        $tierOrder = ['free' => 0, 'pro' => 1, 'team' => 2, 'enterprise' => 3];

        $currentLevel = $tierOrder[$currentTier] ?? 0;
        $targetLevel = $tierOrder[$targetTier] ?? 0;

        return $targetLevel > $currentLevel;
    }

    /**
     * Check if organization can downgrade to a tier.
     */
    public function canDowngradeTo(Organization $organization, string $targetTier): bool
    {
        $currentTier = $organization->subscription_tier;
        $tierOrder = ['free' => 0, 'pro' => 1, 'team' => 2, 'enterprise' => 3];

        $currentLevel = $tierOrder[$currentTier] ?? 0;
        $targetLevel = $tierOrder[$targetTier] ?? 0;

        return $targetLevel < $currentLevel;
    }

    /**
     * Get the display name for a tier.
     */
    public function getTierName(string $tierSlug): string
    {
        $tier = $this->getTier($tierSlug);

        return $tier['name'] ?? ucfirst($tierSlug);
    }

    /**
     * Format price for display.
     */
    public function formatPrice(int $priceInCents): string
    {
        return '$'.number_format($priceInCents / 100, 2);
    }
}
