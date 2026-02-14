<?php

namespace App\Services\Analysis;

use PhpSpellcheck\MisspellingInterface;
use PhpSpellcheck\Spellchecker\Hunspell;
use PhpSpellcheck\Spellchecker\SpellcheckerInterface;
use PhpSpellcheck\Utils\CommandLine;

class SpellChecker
{
    protected ?SpellcheckerInterface $spellchecker = null;

    /**
     * Common words to ignore (brand names, technical terms, etc.)
     *
     * @var array<string>
     */
    protected array $ignoreWords = [
        // Common tech terms
        'url', 'urls', 'api', 'apis', 'html', 'css', 'javascript', 'php',
        'json', 'xml', 'http', 'https', 'www', 'ftp', 'smtp', 'sql',
        'mysql', 'postgresql', 'mongodb', 'redis', 'cdn', 'ssl', 'tls',
        'oauth', 'jwt', 'csrf', 'xss', 'seo', 'saas', 'paas', 'iaas',
        'devops', 'cicd', 'kubernetes', 'docker', 'nginx', 'apache',
        'webpack', 'npm', 'yarn', 'git', 'github', 'gitlab', 'bitbucket',
        'linkedin', 'youtube', 'instagram', 'facebook', 'twitter',
        'ios', 'macos', 'linux', 'ubuntu', 'debian', 'centos',
        'laravel', 'symfony', 'wordpress', 'drupal', 'magento',
        'tailwind', 'tailwindcss', 'bootstrap', 'vue', 'vuejs', 'react',
        'reactjs', 'angular', 'nextjs', 'nuxt', 'svelte', 'alpine',
        'livewire', 'inertia', 'filament', 'nova', 'ai', 'ml',

        // Common abbreviations
        'inc', 'llc', 'ltd', 'corp', 'etc', 'vs', 'eg', 'ie',

        // Email-related
        'email', 'emails', 'mailto',

        // Numbers and units
        'px', 'em', 'rem', 'vh', 'vw', 'kb', 'mb', 'gb', 'tb',

        // Common marketing terms
        'roi', 'cta', 'crm', 'erp', 'kpi', 'b2b', 'b2c', 'ecommerce',

        // Business/industry terms (often not in dictionaries)
        'oem', 'oems', 'scalable', 'scalability', 'onboarding', 'offboarding',
        'uptime', 'downtime', 'workflow', 'workflows', 'signup', 'signups',
        'login', 'logins', 'logout', 'username', 'usernames', 'dropdown',
        'checkbox', 'checkboxes', 'tooltip', 'tooltips', 'popup', 'popups',
        'homepage', 'webpage', 'webpages', 'website', 'websites',
        'plugin', 'plugins', 'addon', 'addons', 'backend', 'frontend',
        'middleware', 'microservices', 'serverless', 'blockchain',
        'cryptocurrency', 'fintech', 'healthtech', 'edtech', 'proptech',
        'automate', 'automating', 'automates', 'automated', 'automations',
        'analytics', 'metadata', 'dataset', 'datasets', 'webhook', 'webhooks',
        'timestamp', 'timestamps', 'codebase', 'refactor', 'refactoring',
        'dealership', 'dealerships', 'warranty', 'warranties',

        // Automotive/warranty industry terms
        'warrcloud', 'warr', 'preauthorization', 'preauthorizations',
        'reimbursement', 'reimbursements', 'claimable', 'submittable',
        'trackable', 'integrable', 'customizable', 'configurable',

        // Common adjectives often flagged
        'actionable', 'impactful', 'streamlined', 'optimized', 'optimizing',
        'leveraging', 'leveraged', 'synergies', 'synergy', 'incentivize',
        'incentivized', 'monetize', 'monetized', 'monetization',
        'gamechanger', 'gamechanging', 'transformative', 'disruptive',
    ];

    public function __construct()
    {
        // Find hunspell binary path
        $hunspellPath = $this->findHunspellPath();

        if ($hunspellPath !== null) {
            $this->spellchecker = new Hunspell(new CommandLine($hunspellPath));
        }
    }

    /**
     * Find the hunspell binary path.
     */
    protected function findHunspellPath(): ?string
    {
        $possiblePaths = [
            '/opt/homebrew/bin/hunspell', // macOS ARM (Homebrew)
            '/usr/local/bin/hunspell',    // macOS Intel (Homebrew)
            '/usr/bin/hunspell',          // Linux
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        // Try to find via which command
        $output = shell_exec('which hunspell 2>/dev/null');
        if ($output !== null && trim($output) !== '') {
            return trim($output);
        }

        return null;
    }

    /**
     * Check if spell checker is available.
     */
    public function isAvailable(): bool
    {
        return $this->spellchecker !== null;
    }

    /**
     * Check text for spelling errors.
     *
     * @return array<array{word: string, suggestions: array<string>, offset: int, line: int}>
     */
    public function check(string $text, string $language = 'en_US'): array
    {
        // If spell checker is not available, return empty results
        if (! $this->isAvailable()) {
            return [];
        }

        // Clean the text - remove URLs, email addresses, code blocks
        $cleanedText = $this->cleanText($text);

        if (empty(trim($cleanedText))) {
            return [];
        }

        try {
            $misspellings = $this->spellchecker->check($cleanedText, [$language]);

            return $this->processMisspellings($misspellings);
        } catch (\Exception $e) {
            report($e);

            return [];
        }
    }

    /**
     * Clean text by removing elements that shouldn't be spell-checked.
     */
    protected function cleanText(string $text): string
    {
        // Remove URLs
        $text = preg_replace('/https?:\/\/[^\s]+/i', '', $text);

        // Remove email addresses
        $text = preg_replace('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/i', '', $text);

        // Remove code-like content (anything with underscores, camelCase patterns)
        $text = preg_replace('/\b[a-z]+[A-Z][a-zA-Z]*\b/', '', $text); // camelCase
        $text = preg_replace('/\b[a-zA-Z]+_[a-zA-Z_]+\b/', '', $text); // snake_case

        // Remove numbers and alphanumeric codes
        $text = preg_replace('/\b[A-Z0-9]{2,}\b/', '', $text); // ALL CAPS codes
        $text = preg_replace('/\b\d+[a-zA-Z]+\b/', '', $text); // 10px, 2xl, etc.
        $text = preg_replace('/\b[a-zA-Z]+\d+[a-zA-Z]*\b/', '', $text); // v2, h1, etc.

        // Remove file extensions
        $text = preg_replace('/\.\w{2,4}\b/', '', $text);

        // Remove hex colors
        $text = preg_replace('/#[0-9a-fA-F]{3,8}\b/', '', $text);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Process misspellings and filter out false positives.
     *
     * @param  iterable<MisspellingInterface>  $misspellings
     * @return array<array{word: string, suggestions: array<string>, offset: int, line: int}>
     */
    protected function processMisspellings(iterable $misspellings): array
    {
        $results = [];
        $wordCounts = [];

        // First pass: collect all misspellings and count occurrences
        $allMisspellings = [];
        foreach ($misspellings as $misspelling) {
            $word = $misspelling->getWord();
            $wordCounts[$word] = ($wordCounts[$word] ?? 0) + 1;
            $allMisspellings[] = $misspelling;
        }

        // Track which words we've already reported (to avoid duplicates)
        $reportedWords = [];

        foreach ($allMisspellings as $misspelling) {
            $word = $misspelling->getWord();

            // Skip if we've already reported this word
            if (isset($reportedWords[strtolower($word)])) {
                continue;
            }

            // Skip if in ignore list (case-insensitive)
            if (in_array(strtolower($word), $this->ignoreWords, true)) {
                continue;
            }

            // Skip single characters
            if (mb_strlen($word) <= 1) {
                continue;
            }

            // Skip words that are all uppercase (likely acronyms)
            if ($word === strtoupper($word) && mb_strlen($word) <= 5) {
                continue;
            }

            // Skip CamelCase or PascalCase words (likely brand names or technical terms)
            if (preg_match('/^[A-Z][a-z]+[A-Z]/', $word)) {
                continue;
            }

            // Skip words with mixed case that aren't at sentence start (likely brand names)
            if (preg_match('/[a-z][A-Z]/', $word)) {
                continue;
            }

            // Skip words that appear 3+ times (likely intentional brand names/proper nouns)
            if (($wordCounts[$word] ?? 0) >= 3) {
                continue;
            }

            // Skip words that start with uppercase and look like proper nouns
            if (preg_match('/^[A-Z][a-z]+$/', $word)) {
                // Only flag common words that should be lowercase
                $commonWords = ['The', 'And', 'But', 'For', 'Are', 'Was', 'Were', 'This', 'That'];
                if (! in_array($word, $commonWords)) {
                    continue;
                }
            }

            // Skip words with numbers
            if (preg_match('/\d/', $word)) {
                continue;
            }

            // Skip possessives
            if (preg_match("/('s|'t|'re|'ve|'ll|'d)$/i", $word)) {
                continue;
            }

            $results[] = [
                'word' => $word,
                'suggestions' => array_slice($misspelling->getSuggestions(), 0, 3),
                'offset' => $misspelling->getOffset() ?? 0,
                'line' => $misspelling->getLineNumber() ?? 1,
            ];

            // Mark this word as reported
            $reportedWords[strtolower($word)] = true;
        }

        return $results;
    }

    /**
     * Add words to the ignore list.
     *
     * @param  array<string>  $words
     */
    public function addIgnoreWords(array $words): self
    {
        $this->ignoreWords = array_merge(
            $this->ignoreWords,
            array_map('strtolower', $words)
        );

        return $this;
    }

    /**
     * Convert spell check results to issue format.
     *
     * @param  array<array{word: string, suggestions: array<string>, offset: int, line: int}>  $misspellings
     * @return array<array{category: string, severity: string, text_excerpt: string, suggestion: string, source_tool: string, confidence: int}>
     */
    public function toIssues(array $misspellings): array
    {
        return array_map(function ($misspelling) {
            $suggestion = ! empty($misspelling['suggestions'])
                ? implode(' or ', array_slice($misspelling['suggestions'], 0, 2))
                : 'Check spelling';

            return [
                'category' => 'spelling',
                'severity' => 'error',
                'text_excerpt' => $misspelling['word'],
                'suggestion' => $suggestion,
                'source_tool' => 'hunspell',
                'confidence' => 95, // Hunspell is deterministic - high confidence
            ];
        }, $misspellings);
    }

    /**
     * Calculate spelling score based on unique misspellings.
     */
    public function calculateScore(int $wordCount, int $misspellingCount): int
    {
        if ($wordCount === 0) {
            return 100;
        }

        // Use a more forgiving formula
        // 0 errors = 100
        // 1-2 errors = 95-98
        // 3-5 errors = 85-94
        // 6-10 errors = 70-84
        // 10+ errors = below 70

        if ($misspellingCount === 0) {
            return 100;
        }

        // Each unique error costs points, but with diminishing impact
        $penalty = min(50, $misspellingCount * 5);

        return max(50, 100 - $penalty);
    }
}
