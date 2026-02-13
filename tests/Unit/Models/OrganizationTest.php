<?php

use App\Models\Organization;
use App\Models\User;

it('generates a slug when creating an organization', function () {
    $organization = Organization::factory()->create([
        'name' => 'Test Company',
        'slug' => null,
    ]);

    expect($organization->slug)->toStartWith('test-company-');
});

it('can check if organization has credits', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 100,
    ]);

    expect($organization->hasCredits(50))->toBeTrue();
    expect($organization->hasCredits(100))->toBeTrue();
    expect($organization->hasCredits(101))->toBeFalse();
});

it('can deduct credits from balance', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 100,
        'overdraft_balance' => 0,
    ]);

    $result = $organization->deductCredits(30);

    expect($result)->toBeTrue();
    expect($organization->fresh()->credit_balance)->toBe(70);
});

it('uses overdraft when credits are insufficient', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 10,
        'overdraft_balance' => 0,
    ]);

    $organization->deductCredits(25);

    $organization->refresh();
    expect($organization->credit_balance)->toBe(0);
    expect($organization->overdraft_balance)->toBe(15);
});

it('can add credits to balance', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 50,
        'overdraft_balance' => 0,
    ]);

    $organization->addCredits(100);

    expect($organization->fresh()->credit_balance)->toBe(150);
});

it('pays off overdraft first when adding credits', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 0,
        'overdraft_balance' => 30,
    ]);

    $organization->addCredits(50);

    $organization->refresh();
    expect($organization->overdraft_balance)->toBe(0);
    expect($organization->credit_balance)->toBe(20);
});

it('checks if organization is on free tier', function () {
    $freeOrg = Organization::factory()->create(['subscription_tier' => 'free']);
    $proOrg = Organization::factory()->create(['subscription_tier' => 'pro']);

    expect($freeOrg->isFreeTier())->toBeTrue();
    expect($proOrg->isFreeTier())->toBeFalse();
});

it('checks if organization has team features', function () {
    $freeOrg = Organization::factory()->create(['subscription_tier' => 'free']);
    $teamOrg = Organization::factory()->create(['subscription_tier' => 'team']);
    $enterpriseOrg = Organization::factory()->create(['subscription_tier' => 'enterprise']);

    expect($freeOrg->hasTeamFeatures())->toBeFalse();
    expect($teamOrg->hasTeamFeatures())->toBeTrue();
    expect($enterpriseOrg->hasTeamFeatures())->toBeTrue();
});

it('returns correct history retention days by tier', function () {
    $freeOrg = Organization::factory()->create(['subscription_tier' => 'free']);
    $proOrg = Organization::factory()->create(['subscription_tier' => 'pro']);

    expect($freeOrg->getHistoryRetentionDays())->toBe(30);
    expect($proOrg->getHistoryRetentionDays())->toBeNull(); // Unlimited
});

it('returns correct default checks by tier', function () {
    $freeOrg = Organization::factory()->create(['subscription_tier' => 'free']);
    $proOrg = Organization::factory()->create(['subscription_tier' => 'pro']);

    expect($freeOrg->getDefaultChecks())->toBe(['spelling']);
    expect($proOrg->getDefaultChecks())->toBe(['spelling', 'grammar', 'seo', 'readability']);
});

it('has many users', function () {
    $organization = Organization::factory()->create();
    User::factory()->count(3)->create(['organization_id' => $organization->id]);

    expect($organization->users)->toHaveCount(3);
});
