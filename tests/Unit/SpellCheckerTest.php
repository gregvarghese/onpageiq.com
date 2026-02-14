<?php

use App\Services\Analysis\SpellChecker;

beforeEach(function () {
    $this->spellChecker = app(SpellChecker::class);
});

it('is available when hunspell is installed', function () {
    // Skip if hunspell is not installed
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed on this system');
    }

    expect($this->spellChecker->isAvailable())->toBeTrue();
});

it('detects misspelled words', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $text = 'This is a tset with mispelled words and errrors.';
    $result = $this->spellChecker->check($text, 'en_US');

    expect($result)->toBeArray();
    expect(count($result))->toBeGreaterThanOrEqual(3);

    $words = array_column($result, 'word');
    expect($words)->toContain('tset');
    expect($words)->toContain('mispelled');
    expect($words)->toContain('errrors');
});

it('provides suggestions for misspelled words', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $text = 'This is a tset.';
    $result = $this->spellChecker->check($text, 'en_US');

    expect($result)->toHaveCount(1);
    expect($result[0]['word'])->toBe('tset');
    expect($result[0]['suggestions'])->toContain('test');
});

it('ignores URLs and email addresses', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $text = 'Visit https://websiet.com or email info@compnay.com for more info.';
    $result = $this->spellChecker->check($text, 'en_US');

    // Should not flag websiet or compnay since they're in URLs/emails
    $words = array_column($result, 'word');
    expect($words)->not->toContain('websiet');
    expect($words)->not->toContain('compnay');
});

it('ignores words in the ignore list', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $text = 'We use Laravel and Tailwind for our SaaS platform.';
    $result = $this->spellChecker->check($text, 'en_US');

    $words = array_column($result, 'word');
    expect($words)->not->toContain('Laravel');
    expect($words)->not->toContain('Tailwind');
    expect($words)->not->toContain('SaaS');
});

it('can add custom ignore words', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $this->spellChecker->addIgnoreWords(['customword', 'anotherword']);

    $text = 'This has customword and anotherword in it.';
    $result = $this->spellChecker->check($text, 'en_US');

    $words = array_column($result, 'word');
    expect($words)->not->toContain('customword');
    expect($words)->not->toContain('anotherword');
});

it('converts misspellings to issue format', function () {
    $misspellings = [
        ['word' => 'tset', 'suggestions' => ['test', 'set'], 'offset' => 10, 'line' => 1],
        ['word' => 'errror', 'suggestions' => ['error'], 'offset' => 20, 'line' => 1],
    ];

    $issues = $this->spellChecker->toIssues($misspellings);

    expect($issues)->toHaveCount(2);
    expect($issues[0])->toMatchArray([
        'category' => 'spelling',
        'severity' => 'error',
        'text_excerpt' => 'tset',
        'suggestion' => 'test or set',
    ]);
});

it('calculates spelling score correctly', function () {
    // 0 errors = 100
    expect($this->spellChecker->calculateScore(100, 0))->toBe(100);

    // 1 error = 95
    expect($this->spellChecker->calculateScore(100, 1))->toBe(95);

    // 5 errors = 75
    expect($this->spellChecker->calculateScore(100, 5))->toBe(75);

    // 10+ errors = 50 (minimum)
    expect($this->spellChecker->calculateScore(100, 15))->toBe(50);
});

it('handles empty text gracefully', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $result = $this->spellChecker->check('', 'en_US');
    expect($result)->toBeArray()->toBeEmpty();

    $result = $this->spellChecker->check('   ', 'en_US');
    expect($result)->toBeArray()->toBeEmpty();
});

it('deduplicates repeated misspellings', function () {
    if (! $this->spellChecker->isAvailable()) {
        $this->markTestSkipped('Hunspell is not installed');
    }

    $text = 'The tset is a tset and another tset appears here.';
    $result = $this->spellChecker->check($text, 'en_US');

    // Should only report 'tset' once since it appears 3+ times (intentional brand name)
    // Actually, it appears 3 times so it will be skipped as likely intentional
    // Let's use a word that appears twice
    $text2 = 'The errror is an errror.';
    $result2 = $this->spellChecker->check($text2, 'en_US');

    $words = array_column($result2, 'word');
    // Should only have one entry for 'errror' even though it appears twice
    expect(array_count_values($words)['errror'] ?? 0)->toBeLessThanOrEqual(1);
});
