<?php

use App\Enums\AIUsageCategory;
use App\Models\AIUsageDaily;
use App\Models\AIUsageLog;
use App\Models\AIUsageMonthly;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
});

test('aggregates daily usage from logs', function () {
    $date = Carbon::today();

    AIUsageLog::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'category' => AIUsageCategory::SpellingCheck,
        'prompt_tokens' => 100,
        'completion_tokens' => 50,
        'cost' => 0.001,
        'success' => true,
        'created_at' => $date,
    ]);

    AIUsageLog::factory()->create([
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
        'provider' => 'openai',
        'model' => 'gpt-4o-mini',
        'category' => AIUsageCategory::SpellingCheck,
        'prompt_tokens' => 100,
        'completion_tokens' => 0,
        'cost' => 0,
        'success' => false,
        'created_at' => $date,
    ]);

    $count = AIUsageDaily::aggregateForDate($date);

    expect($count)->toBe(1);

    $daily = AIUsageDaily::whereDate('date', $date)->first();
    expect($daily->request_count)->toBe(4)
        ->and($daily->success_count)->toBe(3)
        ->and($daily->failure_count)->toBe(1)
        ->and((float) $daily->total_cost)->toBe(0.003);
});

test('aggregates monthly usage from daily records', function () {
    $year = 2026;
    $month = 2;

    AIUsageDaily::factory()->create([
        'date' => Carbon::create($year, $month, 1),
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
        'category' => AIUsageCategory::SpellingCheck->value,
        'request_count' => 10,
        'success_count' => 9,
        'total_tokens' => 1000,
        'total_cost' => 0.01,
    ]);

    AIUsageDaily::factory()->create([
        'date' => Carbon::create($year, $month, 15),
        'organization_id' => $this->organization->id,
        'user_id' => $this->user->id,
        'category' => AIUsageCategory::SpellingCheck->value,
        'request_count' => 20,
        'success_count' => 18,
        'total_tokens' => 2000,
        'total_cost' => 0.02,
    ]);

    $count = AIUsageMonthly::aggregateForMonth($year, $month);

    expect($count)->toBe(1);

    $monthly = AIUsageMonthly::where('year', $year)->where('month', $month)->first();
    expect($monthly->request_count)->toBe(30)
        ->and($monthly->success_count)->toBe(27)
        ->and($monthly->total_tokens)->toBe(3000)
        ->and((float) $monthly->total_cost)->toBe(0.03);
});

test('gets daily costs for charting', function () {
    $today = Carbon::today();

    AIUsageDaily::factory()->create([
        'date' => $today->copy()->subDays(1),
        'total_cost' => 5.00,
        'request_count' => 100,
    ]);

    AIUsageDaily::factory()->create([
        'date' => $today->copy()->subDays(2),
        'total_cost' => 3.00,
        'request_count' => 50,
    ]);

    $costs = AIUsageDaily::getDailyCosts(30);

    expect($costs)->toHaveCount(2)
        ->and((float) $costs->sum('cost'))->toBe(8.00);
});

test('gets costs by provider', function () {
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now();

    AIUsageDaily::factory()->create([
        'date' => $startDate,
        'provider' => 'openai',
        'total_cost' => 10.00,
    ]);

    AIUsageDaily::factory()->create([
        'date' => $startDate,
        'provider' => 'anthropic',
        'total_cost' => 5.00,
    ]);

    $costs = AIUsageDaily::getCostsByProvider($startDate, $endDate);

    expect($costs)->toHaveCount(2)
        ->and((float) $costs->firstWhere('provider', 'openai')->cost)->toBe(10.00)
        ->and((float) $costs->firstWhere('provider', 'anthropic')->cost)->toBe(5.00);
});

test('gets costs by category', function () {
    $startDate = Carbon::now()->startOfMonth();
    $endDate = Carbon::now();

    AIUsageDaily::factory()->create([
        'date' => $startDate,
        'category' => AIUsageCategory::SpellingCheck->value,
        'total_cost' => 7.00,
    ]);

    AIUsageDaily::factory()->create([
        'date' => $startDate,
        'category' => AIUsageCategory::GrammarCheck->value,
        'total_cost' => 3.00,
    ]);

    $costs = AIUsageDaily::getCostsByCategory($startDate, $endDate);

    expect($costs)->toHaveCount(2);
});

test('gets top organizations by spend', function () {
    $year = Carbon::now()->year;
    $month = Carbon::now()->month;

    $org2 = Organization::factory()->create();

    AIUsageMonthly::factory()->create([
        'year' => $year,
        'month' => $month,
        'organization_id' => $this->organization->id,
        'total_cost' => 100.00,
    ]);

    AIUsageMonthly::factory()->create([
        'year' => $year,
        'month' => $month,
        'organization_id' => $org2->id,
        'total_cost' => 50.00,
    ]);

    $topOrgs = AIUsageMonthly::getTopOrganizations($year, $month);

    expect($topOrgs)->toHaveCount(2)
        ->and($topOrgs->first()->organization_id)->toBe($this->organization->id);
});

test('gets top users by spend', function () {
    $year = Carbon::now()->year;
    $month = Carbon::now()->month;

    $user2 = User::factory()->create(['organization_id' => $this->organization->id]);

    AIUsageMonthly::factory()->create([
        'year' => $year,
        'month' => $month,
        'user_id' => $this->user->id,
        'total_cost' => 75.00,
    ]);

    AIUsageMonthly::factory()->create([
        'year' => $year,
        'month' => $month,
        'user_id' => $user2->id,
        'total_cost' => 25.00,
    ]);

    $topUsers = AIUsageMonthly::getTopUsers($year, $month);

    expect($topUsers)->toHaveCount(2)
        ->and($topUsers->first()->user_id)->toBe($this->user->id);
});

test('aggregate daily command works', function () {
    $date = Carbon::yesterday();

    AIUsageLog::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
        'created_at' => $date,
    ]);

    Artisan::call('ai:aggregate-daily', ['--date' => $date->toDateString()]);

    expect(AIUsageDaily::whereDate('date', $date)->exists())->toBeTrue();
});

test('aggregate monthly command works', function () {
    $year = Carbon::now()->subMonth()->year;
    $month = Carbon::now()->subMonth()->month;

    AIUsageDaily::factory()->create([
        'date' => Carbon::create($year, $month, 15),
        'organization_id' => $this->organization->id,
    ]);

    Artisan::call('ai:aggregate-monthly', [
        '--year' => $year,
        '--month' => $month,
    ]);

    expect(AIUsageMonthly::where('year', $year)->where('month', $month)->exists())->toBeTrue();
});

test('reset budgets command works', function () {
    Carbon::setTestNow(Carbon::create(2026, 2, 1));

    \App\Models\AIBudget::factory()->create([
        'organization_id' => $this->organization->id,
        'current_month_usage' => 50.00,
        'current_period_start' => Carbon::create(2026, 1, 1),
    ]);

    Artisan::call('ai:reset-budgets');

    $budget = \App\Models\AIBudget::where('organization_id', $this->organization->id)->first();
    expect((float) $budget->current_month_usage)->toBe(0.00);

    Carbon::setTestNow();
});
