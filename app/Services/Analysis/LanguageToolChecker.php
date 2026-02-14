<?php

namespace App\Services\Analysis;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LanguageToolChecker
{
    protected string $baseUrl;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('onpageiq.languagetool.url', 'http://localhost:8082');
        $this->timeout = config('onpageiq.languagetool.timeout', 30);
    }

    /**
     * Check if LanguageTool is available.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/v2/languages");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check text for spelling and grammar errors.
     *
     * @return array<array{word: string, message: string, suggestions: array<string>, offset: int, length: int, rule: string, category: string}>
     */
    public function check(string $text, string $language = 'en-US'): array
    {
        if (empty(trim($text))) {
            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post("{$this->baseUrl}/v2/check", [
                    'text' => $text,
                    'language' => $language,
                    'enabledOnly' => 'false',
                ]);

            if (! $response->successful()) {
                Log::warning('LanguageTool request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $this->processMatches($response->json('matches', []));
        } catch (\Exception $e) {
            Log::error('LanguageTool check failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Process LanguageTool matches into our format.
     *
     * @return array<array{word: string, message: string, suggestions: array<string>, offset: int, length: int, rule: string, category: string}>
     */
    protected function processMatches(array $matches): array
    {
        $results = [];

        foreach ($matches as $match) {
            // Skip certain rule categories that tend to be noisy
            $category = $match['rule']['category']['id'] ?? '';
            if (in_array($category, ['CASING', 'COMPOUNDING', 'REDUNDANCY'])) {
                continue;
            }

            $suggestions = array_slice(
                array_column($match['replacements'] ?? [], 'value'),
                0,
                3
            );

            $results[] = [
                'word' => $match['context']['text'] ?? substr($match['sentence'] ?? '', $match['offset'] ?? 0, $match['length'] ?? 10),
                'message' => $match['message'] ?? 'Issue detected',
                'suggestions' => $suggestions,
                'offset' => $match['offset'] ?? 0,
                'length' => $match['length'] ?? 0,
                'rule' => $match['rule']['id'] ?? 'UNKNOWN',
                'category' => $this->mapCategory($category),
            ];
        }

        return $results;
    }

    /**
     * Map LanguageTool category to our category.
     */
    protected function mapCategory(string $ltCategory): string
    {
        return match ($ltCategory) {
            'TYPOS', 'MISSPELLING' => 'spelling',
            'GRAMMAR', 'MISC' => 'grammar',
            'STYLE', 'REDUNDANCY' => 'readability',
            'PUNCTUATION' => 'grammar',
            default => 'grammar',
        };
    }

    /**
     * Convert LanguageTool results to issue format.
     *
     * @return array<array{category: string, severity: string, text_excerpt: string, suggestion: string, source_tool: string, confidence: int}>
     */
    public function toIssues(array $matches): array
    {
        return array_map(function ($match) {
            $suggestion = ! empty($match['suggestions'])
                ? $match['suggestions'][0]
                : $match['message'];

            return [
                'category' => $match['category'],
                'severity' => $match['category'] === 'spelling' ? 'error' : 'warning',
                'text_excerpt' => $match['word'],
                'suggestion' => $suggestion,
                'source_tool' => 'languagetool',
                'confidence' => 90, // LanguageTool is rule-based - high confidence
            ];
        }, $matches);
    }
}
