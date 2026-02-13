<?php

use App\Enums\AIUsageCategory;
use App\Models\AIUsageLog;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->actingAs($this->user);
});

test('logs ai usage with basic fields', function () {
    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o-mini',
        promptTokens: 100,
        completionTokens: 50,
        durationMs: 500,
    );

    expect($log)->toBeInstanceOf(AIUsageLog::class)
        ->and($log->provider)->toBe('openai')
        ->and($log->model)->toBe('gpt-4o-mini')
        ->and($log->prompt_tokens)->toBe(100)
        ->and($log->completion_tokens)->toBe(50)
        ->and($log->total_tokens)->toBe(150)
        ->and($log->duration_ms)->toBe(500)
        ->and($log->success)->toBeTrue()
        ->and($log->organization_id)->toBe($this->organization->id)
        ->and($log->user_id)->toBe($this->user->id);
});

test('calculates cost for openai models', function () {
    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o-mini',
        promptTokens: 1000000,
        completionTokens: 1000000,
    );

    // gpt-4o-mini: prompt $0.15/1M, completion $0.60/1M
    expect((float) $log->cost)->toBe(0.75);
});

test('logs failed requests', function () {
    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o',
        promptTokens: 100,
        completionTokens: 0,
        success: false,
        errorMessage: 'Rate limit exceeded',
    );

    expect($log->success)->toBeFalse()
        ->and($log->error_message)->toBe('Rate limit exceeded');
});

test('logs with category and purpose', function () {
    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o-mini',
        promptTokens: 100,
        completionTokens: 50,
        category: AIUsageCategory::SpellingCheck,
        purposeDetail: 'Checking homepage content',
    );

    expect($log->category)->toBe(AIUsageCategory::SpellingCheck)
        ->and($log->purpose_detail)->toBe('Checking homepage content');
});

test('logs with prompt and response content', function () {
    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o-mini',
        promptTokens: 100,
        completionTokens: 50,
        promptContent: 'Check this text for errors',
        responseContent: 'No errors found',
    );

    expect($log->prompt_content)->toBe('Check this text for errors')
        ->and($log->response_content)->toBe('No errors found')
        ->and($log->content_redacted)->toBeFalse();
});

test('redacts sensitive data from content', function () {
    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o-mini',
        promptTokens: 100,
        completionTokens: 50,
        promptContent: 'Contact john@example.com for help',
        responseContent: 'Email sent to jane@test.com',
    );

    expect($log->content_redacted)->toBeTrue()
        ->and($log->prompt_content)->toContain('[REDACTED:email]')
        ->and($log->response_content)->toContain('[REDACTED:email]')
        ->and($log->redaction_summary)->toHaveKey('email');
});

test('logs budget override', function () {
    $approver = User::factory()->create(['organization_id' => $this->organization->id]);

    $log = AIUsageLog::logUsage(
        provider: 'openai',
        model: 'gpt-4o',
        promptTokens: 100,
        completionTokens: 50,
        budgetOverride: true,
        budgetOverrideBy: $approver->id,
    );

    expect($log->budget_override)->toBeTrue()
        ->and($log->budget_override_by)->toBe($approver->id);
});

test('scopes filter by organization', function () {
    $otherOrg = Organization::factory()->create();
    AIUsageLog::factory()->count(3)->create(['organization_id' => $this->organization->id]);
    AIUsageLog::factory()->count(2)->create(['organization_id' => $otherOrg->id]);

    $logs = AIUsageLog::forOrganization($this->organization)->get();

    expect($logs)->toHaveCount(3);
});

test('scopes filter by category', function () {
    AIUsageLog::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
        'category' => AIUsageCategory::SpellingCheck,
    ]);
    AIUsageLog::factory()->count(2)->create([
        'organization_id' => $this->organization->id,
        'category' => AIUsageCategory::GrammarCheck,
    ]);

    $logs = AIUsageLog::forCategory(AIUsageCategory::SpellingCheck)->get();

    expect($logs)->toHaveCount(3);
});

test('scopes filter successful and failed', function () {
    AIUsageLog::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
        'success' => true,
    ]);
    AIUsageLog::factory()->count(2)->failed()->create([
        'organization_id' => $this->organization->id,
    ]);

    expect(AIUsageLog::successful()->count())->toBe(3)
        ->and(AIUsageLog::failed()->count())->toBe(2);
});
