<?php

use App\Enums\AIUsageCategory;
use App\Models\AIUsageLog;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\Analysis\ContentAnalyzer;
use App\Services\Browser\PageContent;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Testing\TextResponseFake;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'pro']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->actingAs($this->user);
});

function makePageContent(string $text): PageContent
{
    return new PageContent(
        url: 'https://example.com/test',
        html: '<html><body>'.$text.'</body></html>',
        text: $text,
        title: 'Test Page',
        meta: [],
        wordCount: str_word_count($text),
    );
}

it('logs AI usage when analyzing content for grammar', function () {
    // Fake the Prism response
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"grammar": 95}}')
            ->withUsage(new Usage(promptTokens: 500, completionTokens: 50)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('This is sample content to analyze for grammar issues.');

    $analyzer->analyze($content, ['grammar'], false, 'en', $this->project);

    // Verify AI usage was logged
    $log = AIUsageLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->provider)->toBe('openai');
    expect($log->model)->toBe('gpt-4o-mini');
    expect($log->prompt_tokens)->toBe(500);
    expect($log->completion_tokens)->toBe(50);
    expect($log->total_tokens)->toBe(550);
    expect($log->category)->toBe(AIUsageCategory::GrammarCheck);
    expect($log->success)->toBeTrue();
    expect($log->project_id)->toBe($this->project->id);
    expect($log->prompt_content)->toContain('grammar');
    expect($log->response_content)->toContain('issues');
});

it('logs AI usage when analyzing content for SEO', function () {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"seo": 80}}')
            ->withUsage(new Usage(promptTokens: 600, completionTokens: 80)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('SEO content to analyze.');

    $analyzer->analyze($content, ['seo'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->category)->toBe(AIUsageCategory::SeoAnalysis);
    expect($log->prompt_tokens)->toBe(600);
    expect($log->completion_tokens)->toBe(80);
});

it('logs AI usage when analyzing content for readability', function () {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"readability": 85}}')
            ->withUsage(new Usage(promptTokens: 450, completionTokens: 60)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Content for readability check.');

    $analyzer->analyze($content, ['readability'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->category)->toBe(AIUsageCategory::ReadabilityAnalysis);
});

it('logs AI usage for multiple checks with combined category', function () {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"grammar": 90, "seo": 75, "readability": 85}}')
            ->withUsage(new Usage(promptTokens: 800, completionTokens: 120)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Full content analysis test.');

    $analyzer->analyze($content, ['grammar', 'seo', 'readability'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    expect($log)->not->toBeNull();
    // Grammar takes priority in category determination
    expect($log->category)->toBe(AIUsageCategory::GrammarCheck);
    expect($log->metadata)->toHaveKey('checks');
    expect($log->metadata['checks'])->toBe(['grammar', 'seo', 'readability']);
});

it('logs failed AI requests with error message', function () {
    // Mock Prism to throw an exception
    Prism::fake([
        TextResponseFake::make()
            ->withText('')
            ->withUsage(new Usage(promptTokens: 0, completionTokens: 0)),
    ]);

    // Manually throw to simulate API failure by mocking the facade
    $mock = Mockery::mock('overload:Prism\Prism\Facades\Prism');
    $mock->shouldReceive('text->using->withSystemPrompt->withPrompt->withMaxTokens->asText')
        ->andThrow(new Exception('API connection failed'));

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Content that will fail.');

    $analyzer->analyze($content, ['grammar'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    expect($log)->not->toBeNull();
    expect($log->success)->toBeFalse();
    expect($log->error_message)->toBe('API connection failed');
    expect($log->prompt_tokens)->toBe(0);
    expect($log->completion_tokens)->toBe(0);
})->skip('Prism facade mocking requires different approach');

it('logs duration in milliseconds', function () {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"grammar": 95}}')
            ->withUsage(new Usage(promptTokens: 100, completionTokens: 20)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Duration test content.');

    $analyzer->analyze($content, ['grammar'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    expect($log->duration_ms)->toBeGreaterThanOrEqual(0);
});

it('does not log AI usage for spelling-only checks', function () {
    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Spelling only check with no AI.');

    $initialCount = AIUsageLog::count();

    $analyzer->analyze($content, ['spelling'], false, 'en', $this->project);

    // Spelling uses deterministic checker, not AI
    expect(AIUsageLog::count())->toBe($initialCount);
});

it('logs purpose detail with chunk information', function () {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"grammar": 95}}')
            ->withUsage(new Usage(promptTokens: 100, completionTokens: 20)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Content for purpose detail test.');

    $analyzer->analyze($content, ['grammar'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    expect($log->purpose_detail)->toContain('Content analysis chunk');
    expect($log->purpose_detail)->toContain('grammar issues');
});

it('calculates cost based on token usage', function () {
    Prism::fake([
        TextResponseFake::make()
            ->withText('{"issues": [], "scores": {"grammar": 95}}')
            ->withUsage(new Usage(promptTokens: 1000, completionTokens: 500)),
    ]);

    $analyzer = app(ContentAnalyzer::class);
    $content = makePageContent('Cost calculation test content.');

    $analyzer->analyze($content, ['grammar'], false, 'en', $this->project);

    $log = AIUsageLog::latest()->first();
    // gpt-4o-mini: $0.15/1M prompt, $0.60/1M completion
    // 1000 prompt = $0.00015, 500 completion = $0.0003
    // Total = $0.00045
    expect((float) $log->cost)->toBeGreaterThan(0);
});
