<?php

namespace App\Http\Controllers\Webhooks;

use App\Models\Organization;
use App\Services\Billing\CreditService;
use App\Services\Billing\SubscriptionService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Stripe\Checkout\Session;

class StripeWebhookController extends CashierWebhookController
{
    public function __construct(
        protected CreditService $creditService,
        protected SubscriptionService $subscriptionService,
    ) {}

    /**
     * Handle customer subscription created event.
     */
    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $organization = $this->getOrganizationByStripeId($subscription['customer']);

        if (! $organization) {
            return;
        }

        // Determine tier from price ID
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $tier = $this->getTierByPriceId($priceId);

        if ($tier) {
            $organization->update(['subscription_tier' => $tier]);

            // Add monthly credits
            $monthlyCredits = $this->subscriptionService->getMonthlyCredits($tier);
            if ($monthlyCredits > 0) {
                $this->creditService->addSubscriptionCredits(
                    $organization,
                    $monthlyCredits,
                    "Monthly credits for {$this->subscriptionService->getTierName($tier)} subscription"
                );
            }
        }
    }

    /**
     * Handle customer subscription updated event.
     */
    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $organization = $this->getOrganizationByStripeId($subscription['customer']);

        if (! $organization) {
            return;
        }

        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
        $tier = $this->getTierByPriceId($priceId);

        if ($tier && $tier !== $organization->subscription_tier) {
            $organization->update(['subscription_tier' => $tier]);
        }
    }

    /**
     * Handle customer subscription deleted event.
     */
    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'];
        $organization = $this->getOrganizationByStripeId($subscription['customer']);

        if (! $organization) {
            return;
        }

        $organization->update(['subscription_tier' => 'free']);
    }

    /**
     * Handle invoice payment succeeded event (for recurring subscription payments).
     */
    protected function handleInvoicePaymentSucceeded(array $payload): void
    {
        $invoice = $payload['data']['object'];

        // Only process subscription invoices (not one-time)
        if (empty($invoice['subscription'])) {
            return;
        }

        $organization = $this->getOrganizationByStripeId($invoice['customer']);

        if (! $organization) {
            return;
        }

        // Add monthly credits on successful payment
        $tier = $organization->subscription_tier;
        $monthlyCredits = $this->subscriptionService->getMonthlyCredits($tier);

        if ($monthlyCredits > 0 && $invoice['billing_reason'] === 'subscription_cycle') {
            $this->creditService->addSubscriptionCredits(
                $organization,
                $monthlyCredits,
                "Monthly credits for {$this->subscriptionService->getTierName($tier)} subscription"
            );
        }
    }

    /**
     * Handle checkout session completed event (for credit purchases).
     */
    protected function handleCheckoutSessionCompleted(array $payload): void
    {
        $session = $payload['data']['object'];
        $metadata = $session['metadata'] ?? [];

        // Check if this is a credit purchase
        if (($metadata['type'] ?? null) !== 'credit_purchase') {
            return;
        }

        $organization = $this->getOrganizationByStripeId($session['customer']);

        if (! $organization) {
            return;
        }

        $packSlug = $metadata['pack'] ?? null;
        $credits = (int) ($metadata['credits'] ?? 0);

        if ($credits > 0) {
            $this->creditService->addPurchasedCredits(
                $organization,
                $credits,
                "Credit pack purchase ({$packSlug})",
                [
                    'stripe_session_id' => $session['id'],
                    'pack' => $packSlug,
                ]
            );
        }
    }

    /**
     * Get organization by Stripe customer ID.
     */
    protected function getOrganizationByStripeId(string $stripeId): ?Organization
    {
        return Organization::where('stripe_id', $stripeId)->first();
    }

    /**
     * Get tier slug by Stripe price ID.
     */
    protected function getTierByPriceId(?string $priceId): ?string
    {
        if (! $priceId) {
            return null;
        }

        $tiers = $this->subscriptionService->getTiers();

        foreach ($tiers as $slug => $tier) {
            if (($tier['stripe_price_id'] ?? null) === $priceId) {
                return $slug;
            }
        }

        return null;
    }
}
