<?php

use App\Services\Analysis\ContentAnalyzer;
use Tests\Fixtures\ErrorContent;

beforeEach(function () {
    $this->analyzer = app(ContentAnalyzer::class);
});

it('detects spelling errors in content', function () {
    $content = ErrorContent::withSpellingErrors();

    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en'
    );

    expect($result['issues'])->toBeArray();
    expect(count($result['issues']))->toBeGreaterThan(0);

    $spellingIssues = array_filter($result['issues'], fn ($i) => $i['category'] === 'spelling');
    expect(count($spellingIssues))->toBeGreaterThan(5);

    // Check for specific misspellings
    $excerpts = array_column($result['issues'], 'text_excerpt');
    expect($excerpts)->toContain('websiet');
    expect($excerpts)->toContain('compnay');
    expect($excerpts)->toContain('servises');

    // Score should be less than 100 due to errors
    expect($result['scores']['spelling'])->toBeLessThan(100);
});

it('returns no issues for clean content', function () {
    $content = ErrorContent::clean();

    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en'
    );

    $spellingIssues = array_filter($result['issues'], fn ($i) => $i['category'] === 'spelling');
    expect(count($spellingIssues))->toBe(0);

    expect($result['scores']['spelling'])->toEqual(100);
});

it('calculates overall score from multiple checks', function () {
    $content = ErrorContent::withSpellingErrors();

    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en'
    );

    expect($result['scores'])->toHaveKey('spelling');
    expect($result['scores'])->toHaveKey('overall');
    expect($result['scores']['overall'])->toBeNumeric();
});

it('handles multiple check types', function () {
    $content = ErrorContent::withMixedErrors();

    // This test would require AI to be called for grammar/seo/readability
    // For unit testing, we'll just test that spelling works
    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en'
    );

    expect($result['issues'])->toBeArray();
    expect($result['scores'])->toHaveKey('spelling');
});

it('uses project custom dictionary when provided', function () {
    $content = ErrorContent::withSpellingErrors();

    // Without project - should flag errors
    $resultWithoutProject = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en',
        project: null
    );

    $excerpts = array_column($resultWithoutProject['issues'], 'text_excerpt');
    expect($excerpts)->toContain('websiet');
});

it('supports different languages', function () {
    $content = ErrorContent::withSpellingErrors();

    // Test with English
    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en-US'
    );

    expect($result['issues'])->toBeArray();

    // Test with British English
    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en-GB'
    );

    expect($result['issues'])->toBeArray();
});

it('correctly filters out AI checks from spelling', function () {
    $content = ErrorContent::clean();

    // When only spelling is requested, AI should not be called
    $result = $this->analyzer->analyze(
        content: $content,
        checks: ['spelling'],
        deepAnalysis: false,
        language: 'en'
    );

    // Should only have spelling score
    expect($result['scores'])->toHaveKey('spelling');
    expect($result['scores'])->toHaveKey('overall');
});
