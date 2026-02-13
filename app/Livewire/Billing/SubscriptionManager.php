<?php

namespace App\Livewire\Billing;

use App\Services\Billing\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubscriptionManager extends Component
{
    public string $selectedTier = '';

    public bool $showUpgradeModal = false;

    public bool $showDowngradeModal = false;

    protected SubscriptionService $subscriptionService;

    public function boot(SubscriptionService $subscriptionService): void
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function mount(): void
    {
        $organization = Auth::user()->organization;
        $this->selectedTier = $organization->subscription_tier;
    }

    public function selectTier(string $tier): void
    {
        $organization = Auth::user()->organization;

        if ($this->subscriptionService->canUpgradeTo($organization, $tier)) {
            $this->selectedTier = $tier;
            $this->showUpgradeModal = true;
        } elseif ($this->subscriptionService->canDowngradeTo($organization, $tier)) {
            $this->selectedTier = $tier;
            $this->showDowngradeModal = true;
        }
    }

    public function confirmUpgrade(): void
    {
        $organization = Auth::user()->organization;
        $tier = $this->subscriptionService->getTier($this->selectedTier);

        if (! $tier || ! $tier['stripe_price_id']) {
            $this->addError('upgrade', 'This tier is not available for subscription.');

            return;
        }

        // Redirect to Stripe Checkout
        $checkout = $organization->newSubscription('default', $tier['stripe_price_id'])
            ->checkout([
                'success_url' => route('billing.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('billing.index'),
            ]);

        $this->redirect($checkout->url);
    }

    public function confirmDowngrade(): void
    {
        $organization = Auth::user()->organization;
        $tier = $this->subscriptionService->getTier($this->selectedTier);

        if (! $tier) {
            $this->addError('downgrade', 'Invalid tier selected.');

            return;
        }

        if ($this->selectedTier === 'free') {
            // Cancel subscription
            $organization->subscription('default')?->cancel();
            $organization->update(['subscription_tier' => 'free']);
        } else {
            // Swap to lower tier
            $organization->subscription('default')?->swap($tier['stripe_price_id']);
            $organization->update(['subscription_tier' => $this->selectedTier]);
        }

        $this->showDowngradeModal = false;
        $this->dispatch('subscription-updated');
    }

    public function cancelModal(): void
    {
        $this->showUpgradeModal = false;
        $this->showDowngradeModal = false;
        $this->selectedTier = Auth::user()->organization->subscription_tier;
    }

    public function render(): View
    {
        $organization = Auth::user()->organization;

        return view('livewire.billing.subscription-manager', [
            'organization' => $organization,
            'tiers' => $this->subscriptionService->getTiers(),
            'currentTier' => $organization->subscription_tier,
            'currentTierConfig' => $this->subscriptionService->getOrganizationTier($organization),
        ]);
    }
}
