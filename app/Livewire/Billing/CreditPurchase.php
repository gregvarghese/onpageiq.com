<?php

namespace App\Livewire\Billing;

use App\Services\Billing\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreditPurchase extends Component
{
    public string $selectedPack = '';

    public bool $showConfirmModal = false;

    protected SubscriptionService $subscriptionService;

    public function boot(SubscriptionService $subscriptionService): void
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function selectPack(string $packSlug): void
    {
        $this->selectedPack = $packSlug;
        $this->showConfirmModal = true;
    }

    public function confirmPurchase(): void
    {
        $pack = $this->subscriptionService->getCreditPack($this->selectedPack);

        if (! $pack || ! $pack['stripe_price_id']) {
            $this->addError('purchase', 'This credit pack is not available.');

            return;
        }

        $organization = Auth::user()->organization;

        // Create a one-time payment checkout session
        $checkout = $organization->checkout($pack['stripe_price_id'], [
            'success_url' => route('billing.credits.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('billing.credits'),
            'metadata' => [
                'type' => 'credit_purchase',
                'pack' => $this->selectedPack,
                'credits' => $pack['credits'],
            ],
        ]);

        $this->redirect($checkout->url);
    }

    public function cancelModal(): void
    {
        $this->showConfirmModal = false;
        $this->selectedPack = '';
    }

    public function render(): View
    {
        $organization = Auth::user()->organization;

        return view('livewire.billing.credit-purchase', [
            'organization' => $organization,
            'packs' => $this->subscriptionService->getCreditPacks(),
        ]);
    }
}
