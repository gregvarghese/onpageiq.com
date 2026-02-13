<?php

use App\Models\AIBudget;
use App\Models\Organization;
use App\Models\User;
use App\Services\AI\AIBudgetService;
use Carbon\Carbon;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->budgetService = app(AIBudgetService::class);
});

test('returns unlimited when no budget is set', function () {
    $result = $this->budgetService->checkOrganizationBudget($this->organization);

    expect($result->withinBudget)->toBeTrue()
        ->and($result->requiresConfirmation)->toBeFalse()
        ->and($result->message)->toBe('No budget limit set');
});

test('returns within budget when under limit', function () {
    AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'current_month_usage' => 50.00,
    ]);

    $result = $this->budgetService->checkOrganizationBudget($this->organization);

    expect($result->withinBudget)->toBeTrue()
        ->and($result->requiresConfirmation)->toBeFalse()
        ->and($result->usagePercentage)->toBe(50.0);
});

test('returns warning when at threshold', function () {
    AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'warning_threshold' => 80.00,
        'current_month_usage' => 85.00,
    ]);

    $result = $this->budgetService->checkOrganizationBudget($this->organization);

    expect($result->withinBudget)->toBeTrue()
        ->and($result->message)->toContain('Warning');
});

test('returns over budget with override allowed', function () {
    AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'current_month_usage' => 110.00,
        'allow_override' => true,
    ]);

    $result = $this->budgetService->checkOrganizationBudget($this->organization);

    expect($result->withinBudget)->toBeFalse()
        ->and($result->requiresConfirmation)->toBeTrue()
        ->and($result->allowOverride)->toBeTrue()
        ->and($result->isBlocked())->toBeFalse();
});

test('returns blocked when override not allowed', function () {
    AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'current_month_usage' => 110.00,
        'allow_override' => false,
    ]);

    $result = $this->budgetService->checkOrganizationBudget($this->organization);

    expect($result->withinBudget)->toBeFalse()
        ->and($result->requiresConfirmation)->toBeFalse()
        ->and($result->allowOverride)->toBeFalse()
        ->and($result->isBlocked())->toBeTrue();
});

test('records usage against budget', function () {
    $budget = AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'current_month_usage' => 50.00,
    ]);

    $this->budgetService->recordUsage(10.00, $this->organization);

    $budget->refresh();
    expect((float) $budget->current_month_usage)->toBe(60.00);
});

test('resets monthly budgets', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 1));

    $budget = AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'current_month_usage' => 75.00,
        'current_period_start' => Carbon::create(2026, 1, 1),
    ]);

    $count = $this->budgetService->resetMonthlyBudgets();

    $budget->refresh();
    expect($count)->toBe(1)
        ->and((float) $budget->current_month_usage)->toBe(0.00)
        ->and($budget->current_period_start->month)->toBe(2);

    Carbon::setTestNow();
});

test('sets budget limit', function () {
    $budget = $this->budgetService->setBudgetLimit(
        monthlyLimit: 200.00,
        organization: $this->organization,
        warningThreshold: 75.00,
        allowOverride: false,
    );

    expect((float) $budget->monthly_limit)->toBe(200.00)
        ->and((float) $budget->warning_threshold)->toBe(75.00)
        ->and($budget->allow_override)->toBeFalse();
});

test('checks combined user and organization budget', function () {
    // Organization at warning
    AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'warning_threshold' => 80.00,
        'current_month_usage' => 85.00,
    ]);

    // User over budget
    AIBudget::factory()->forUser($this->user, $this->organization)->create([
        'monthly_limit' => 20.00,
        'current_month_usage' => 25.00,
        'allow_override' => true,
    ]);

    $result = $this->budgetService->checkCombinedBudget($this->user, $this->organization);

    // Should return the user's over-budget result (more restrictive)
    expect($result->withinBudget)->toBeFalse()
        ->and($result->requiresConfirmation)->toBeTrue();
});

test('remaining budget calculation', function () {
    AIBudget::factory()->forOrganization($this->organization)->create([
        'monthly_limit' => 100.00,
        'current_month_usage' => 75.00,
    ]);

    $result = $this->budgetService->checkOrganizationBudget($this->organization);

    expect($result->remainingBudget())->toBe(25.00);
});
