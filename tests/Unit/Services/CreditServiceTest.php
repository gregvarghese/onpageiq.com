<?php

use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\User;
use App\Services\Billing\CreditService;

beforeEach(function () {
    $this->creditService = app(CreditService::class);
});

it('adds subscription credits to organization', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 0,
    ]);

    $this->creditService->addSubscriptionCredits($organization, 100);

    expect($organization->fresh()->credit_balance)->toBe(100);
    expect(CreditTransaction::where('organization_id', $organization->id)->count())->toBe(1);
    expect(CreditTransaction::first()->type)->toBe(CreditTransaction::TYPE_SUBSCRIPTION_CREDIT);
});

it('adds purchased credits to organization', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 50,
    ]);

    $this->creditService->addPurchasedCredits(
        $organization,
        100,
        'Credit pack purchase',
        ['payment_intent' => 'pi_123']
    );

    expect($organization->fresh()->credit_balance)->toBe(150);

    $transaction = CreditTransaction::where('type', CreditTransaction::TYPE_PURCHASE)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(100);
});

it('deducts credits for usage', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 100,
    ]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $this->creditService->deductCredits($organization, 10, 'Scan usage', $user);

    expect($organization->fresh()->credit_balance)->toBe(90);

    $transaction = CreditTransaction::where('type', CreditTransaction::TYPE_USAGE)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(-10);
    expect($transaction->user_id)->toBe($user->id);
});

it('refunds credits to organization', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 50,
    ]);

    $this->creditService->refundCredits($organization, 25, 'Failed scan refund');

    expect($organization->fresh()->credit_balance)->toBe(75);

    $transaction = CreditTransaction::where('type', CreditTransaction::TYPE_REFUND)->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(25);
});

it('returns transaction history', function () {
    $organization = Organization::factory()->create(['credit_balance' => 100]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    $this->creditService->addSubscriptionCredits($organization, 50);
    $this->creditService->deductCredits($organization, 10, 'Test usage', $user);

    $history = $this->creditService->getTransactionHistory($organization);

    expect($history)->toHaveCount(2);
});

it('calculates usage stats correctly', function () {
    $organization = Organization::factory()->create(['credit_balance' => 100]);
    $user = User::factory()->create(['organization_id' => $organization->id]);

    // Add credits
    $this->creditService->addSubscriptionCredits($organization, 100);
    $this->creditService->addPurchasedCredits($organization, 50, 'Purchase');

    // Use credits
    $this->creditService->deductCredits($organization, 30, 'Usage 1', $user);
    $this->creditService->deductCredits($organization, 20, 'Usage 2', $user);

    $stats = $this->creditService->getUsageStats($organization);

    expect($stats['credits_added'])->toBe(150);
    expect($stats['credits_used'])->toBe(50);
    expect($stats['net_change'])->toBe(100);
});

it('checks if organization has credits', function () {
    $organization = Organization::factory()->create(['credit_balance' => 50]);

    expect($this->creditService->hasCredits($organization, 25))->toBeTrue();
    expect($this->creditService->hasCredits($organization, 100))->toBeFalse();
});

it('returns correct balance', function () {
    $organization = Organization::factory()->create(['credit_balance' => 75]);

    expect($this->creditService->getBalance($organization))->toBe(75);
});
