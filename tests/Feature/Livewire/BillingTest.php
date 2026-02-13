<?php

use App\Livewire\Billing\BillingHistory;
use App\Livewire\Billing\CreditPurchase;
use App\Livewire\Billing\SubscriptionManager;
use App\Models\CreditTransaction;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'credit_balance' => 250,
        'subscription_tier' => 'pro',
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('renders subscription manager', function () {
    Livewire::actingAs($this->user)
        ->test(SubscriptionManager::class)
        ->assertStatus(200)
        ->assertSee('Subscription');
});

it('displays current subscription tier', function () {
    Livewire::actingAs($this->user)
        ->test(SubscriptionManager::class)
        ->assertSee('Pro');
});

it('renders credit purchase page', function () {
    Livewire::actingAs($this->user)
        ->test(CreditPurchase::class)
        ->assertStatus(200)
        ->assertSee('Credit');
});

it('displays available credit packs', function () {
    Livewire::actingAs($this->user)
        ->test(CreditPurchase::class)
        ->assertSee('50 Credits')
        ->assertSee('150 Credits')
        ->assertSee('500 Credits');
});

it('renders billing history', function () {
    Livewire::actingAs($this->user)
        ->test(BillingHistory::class)
        ->assertStatus(200)
        ->assertSee('Billing History');
});

it('displays credit transactions in history', function () {
    CreditTransaction::factory()->create([
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
        'type' => CreditTransaction::TYPE_PURCHASE,
        'amount' => 100,
        'description' => 'Purchased credit pack',
    ]);

    Livewire::actingAs($this->user)
        ->test(BillingHistory::class)
        ->assertSee('Purchased credit pack')
        ->assertSee('+100');
});

it('shows usage transactions with negative amounts', function () {
    CreditTransaction::factory()->create([
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
        'type' => CreditTransaction::TYPE_USAGE,
        'amount' => -10,
        'description' => 'Scan usage',
    ]);

    Livewire::actingAs($this->user)
        ->test(BillingHistory::class)
        ->assertSee('Scan usage')
        ->assertSee('-10');
});

it('paginates transaction history', function () {
    CreditTransaction::factory()->count(20)->create([
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(BillingHistory::class)
        ->assertStatus(200);
});

it('requires authentication for billing pages', function () {
    $this->get(route('billing.index'))
        ->assertRedirect(route('login'));

    $this->get(route('billing.credits'))
        ->assertRedirect(route('login'));

    $this->get(route('billing.history'))
        ->assertRedirect(route('login'));
});
