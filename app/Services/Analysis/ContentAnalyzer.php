<?php

namespace App\Services\Analysis;

use App\Enums\AIUsageCategory;
use App\Models\AIUsageLog;
use App\Models\Issue;
use App\Models\Project;
use App\Models\ScanResult;
use App\Services\Browser\PageContent;
use App\Services\DictionaryService;
use Prism\Prism\Facades\Prism;

class ContentAnalyzer
{
    protected string $quickModel;

    protected string $deepModel;

    protected int $maxTokens;

    protected int $chunkSize;

    protected SpellChecker $spellChecker;

    protected LanguageToolChecker $languageToolChecker;

    protected DictionaryService $dictionaryService;

    public function __construct(
        SpellChecker $spellChecker,
        LanguageToolChecker $languageToolChecker,
        DictionaryService $dictionaryService
    ) {
        $this->quickModel = config('onpageiq.ai.quick_model', 'gpt-4o-mini');
        $this->deepModel = config('onpageiq.ai.deep_model', 'gpt-4o');
        $this->maxTokens = config('onpageiq.ai.max_tokens', 4096);
        $this->chunkSize = config('onpageiq.ai.chunk_size', 50000);
        $this->spellChecker = $spellChecker;
        $this->languageToolChecker = $languageToolChecker;
        $this->dictionaryService = $dictionaryService;
    }

    /**
     * Analyze page content and return issues.
     *
     * @param  array<string>  $checks
     * @return array{issues: array, scores: array}
     */
    public function analyze(
        PageContent $content,
        array $checks,
        bool $deepAnalysis = false,
        string $language = 'en',
        ?Project $project = null
    ): array {
        $model = $deepAnalysis ? $this->deepModel : $this->quickModel;

        $allIssues = [];
        $allScores = [];

        // FIRST PASS: Use deterministic spell checker for spelling
        if (in_array('spelling', $checks)) {
            $spellingResult = $this->analyzeSpelling($content, $language, $project);
            $allIssues = array_merge($allIssues, $spellingResult['issues']);
            $allScores['spelling'] = [$spellingResult['score']];
        }

        // SECOND PASS: Use AI for grammar, SEO, readability (not spelling)
        $aiChecks = array_filter($checks, fn ($check) => $check !== 'spelling');

        if (! empty($aiChecks)) {
            $chunks = $content->splitIntoChunks($this->chunkSize);

            foreach ($chunks as $index => $chunk) {
                $result = $this->analyzeChunk($chunk, $aiChecks, $model, $language, $index + 1, count($chunks), $project);

                $allIssues = array_merge($allIssues, $result['issues'] ?? []);

                // Aggregate scores (average across chunks)
                if (isset($result['scores'])) {
                    foreach ($result['scores'] as $key => $value) {
                        if (! isset($allScores[$key])) {
                            $allScores[$key] = [];
                        }
                        $allScores[$key][] = $value;
                    }
                }
            }
        }

        // Calculate average scores
        $finalScores = [];
        foreach ($allScores as $key => $values) {
            $finalScores[$key] = round(array_sum($values) / count($values), 1);
        }

        // Calculate overall score
        if (! empty($finalScores)) {
            $finalScores['overall'] = round(array_sum($finalScores) / count($finalScores), 1);
        }

        return [
            'issues' => $allIssues,
            'scores' => $finalScores,
        ];
    }

    /**
     * Analyze spelling using deterministic spell checker and LanguageTool.
     *
     * @return array{issues: array, score: int}
     */
    protected function analyzeSpelling(PageContent $content, string $language, ?Project $project = null): array
    {
        // Map language code to Hunspell locale
        $locale = match ($language) {
            'en' => 'en_US',
            'en-US' => 'en_US',
            'en-GB' => 'en_GB',
            'de' => 'de_DE',
            'fr' => 'fr_FR',
            'es' => 'es_ES',
            default => 'en_US',
        };

        // Add custom dictionary words if project is provided
        if ($project !== null) {
            $customWords = $this->dictionaryService->getWordsForProject($project);
            $this->spellChecker->addIgnoreWords($customWords);
        }

        // First layer: Hunspell spell checker
        $misspellings = $this->spellChecker->check($content->text, $locale);
        $issues = $this->spellChecker->toIssues($misspellings, $content->text);

        // Second layer: LanguageTool (if enabled and available)
        if (config('onpageiq.languagetool.enabled', false) && $this->languageToolChecker->isAvailable()) {
            $ltLanguage = match ($language) {
                'en', 'en-US' => 'en-US',
                'en-GB' => 'en-GB',
                'de' => 'de-DE',
                'fr' => 'fr-FR',
                'es' => 'es-ES',
                default => 'en-US',
            };

            $ltMatches = $this->languageToolChecker->check($content->text, $ltLanguage);
            $ltIssues = $this->languageToolChecker->toIssues($ltMatches);

            // Merge issues, avoiding duplicates based on text_excerpt
            $existingExcerpts = array_map(fn ($i) => strtolower($i['text_excerpt']), $issues);
            foreach ($ltIssues as $ltIssue) {
                if (! in_array(strtolower($ltIssue['text_excerpt']), $existingExcerpts, true)) {
                    $issues[] = $ltIssue;
                }
            }
        }

        $score = $this->spellChecker->calculateScore($content->wordCount, count($issues));

        return [
            'issues' => $issues,
            'score' => $score,
        ];
    }

    /**
     * Analyze a single chunk of content.
     *
     * @param  array<string>  $checks
     * @return array{issues: array, scores: array}
     */
    protected function analyzeChunk(
        string $chunk,
        array $checks,
        string $model,
        string $language,
        int $chunkNumber,
        int $totalChunks,
        ?Project $project = null
    ): array {
        $checksDescription = $this->getChecksDescription($checks);
        $chunkInfo = $totalChunks > 1 ? " (chunk {$chunkNumber} of {$totalChunks})" : '';

        $systemPrompt = $this->buildSystemPrompt($checks, $language);
        $userPrompt = $this->buildUserPrompt($chunk, $checksDescription, $chunkInfo);

        $startTime = hrtime(true);
        $success = true;
        $errorMessage = null;
        $response = null;

        try {
            $response = Prism::text()
                ->using('openai', $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withMaxTokens($this->maxTokens)
                ->asText();

            $result = $this->parseResponse($response->text, $model);
        } catch (\Exception $e) {
            report($e);
            $success = false;
            $errorMessage = $e->getMessage();
            $result = [
                'issues' => [],
                'scores' => [],
            ];
        }

        $durationMs = (int) ((hrtime(true) - $startTime) / 1_000_000);

        // Determine the primary category from the checks
        $category = $this->determineCategory($checks);

        // Log the AI usage
        $fullPrompt = "System: {$systemPrompt}\n\nUser: {$userPrompt}";
        AIUsageLog::logUsage(
            provider: 'openai',
            model: $model,
            promptTokens: $response?->usage->promptTokens ?? 0,
            completionTokens: $response?->usage->completionTokens ?? 0,
            durationMs: $durationMs,
            taskType: 'content_analysis',
            loggable: $project,
            success: $success,
            errorMessage: $errorMessage,
            metadata: [
                'checks' => $checks,
                'chunk_number' => $chunkNumber,
                'total_chunks' => $totalChunks,
                'language' => $language,
            ],
            promptContent: $fullPrompt,
            responseContent: $response?->text,
            category: $category,
            purposeDetail: "Content analysis chunk {$chunkNumber}/{$totalChunks} for: {$checksDescription}",
            projectId: $project?->id,
        );

        return $result;
    }

    /**
     * Determine the primary AI usage category from checks.
     */
    protected function determineCategory(array $checks): AIUsageCategory
    {
        if (in_array('grammar', $checks)) {
            return AIUsageCategory::GrammarCheck;
        }
        if (in_array('seo', $checks)) {
            return AIUsageCategory::SeoAnalysis;
        }
        if (in_array('readability', $checks)) {
            return AIUsageCategory::ReadabilityAnalysis;
        }

        return AIUsageCategory::DocumentAnalysis;
    }

    /**
     * Build the system prompt for content analysis.
     *
     * @param  array<string>  $checks
     */
    protected function buildSystemPrompt(array $checks, string $language): string
    {
        $checksJson = json_encode($checks);

        return <<<PROMPT
You are a precise content proofreader. Only flag CLEAR, UNAMBIGUOUS errors. When in doubt, DO NOT flag.

Language: {$language}
Checks: {$checksJson}

RULES:
1. Spelling is handled separately - NEVER flag spelling issues
2. Your suggestion MUST be different from the original text
3. If you cannot provide a concrete fix, do not flag it
4. Marketing copy, brand names, and stylistic choices are NOT errors

**GRAMMAR** - Only flag these specific errors (severity: "error"):
- Subject-verb disagreement: "he don't" → "he doesn't"
- Wrong homophones: "their going" → "they're going", "its good" → "it's good"
- Double negatives: "don't have no" → "don't have any"
- Missing verbs: "She a doctor" → "She is a doctor"
- Dangling modifiers that change meaning

DO NOT FLAG as grammar:
- ALL CAPS text (this is intentional for headlines)
- Sentence fragments in marketing copy
- Starting sentences with "And" or "But"
- Informal tone or contractions
- Brand names or product names

**SEO** - Only flag (severity: "warning"):
- Title tag over 60 chars or under 30 chars
- Missing meta description
- Multiple H1 tags
- Skipped heading levels (H1 → H3, missing H2)

DO NOT FLAG as SEO:
- "Not descriptive enough" - this is subjective
- Keyword suggestions - we don't know target keywords
- Content length opinions

**READABILITY** - Only flag (severity: "suggestion"):
- Sentences over 40 words that could be split
- Paragraphs over 200 words without breaks
- Jargon without context for general audience

DO NOT FLAG as readability:
- Headlines or titles
- Marketing slogans
- Technical documentation for technical audiences

OUTPUT FORMAT (JSON only):
{
  "issues": [
    {
      "category": "grammar",
      "severity": "error",
      "text_excerpt": "exact problematic text",
      "suggestion": "corrected text that is DIFFERENT from original"
    }
  ],
  "scores": {
    "grammar": 95,
    "seo": 85,
    "readability": 80
  }
}

CRITICAL:
- Return empty issues array [] if no clear errors found
- Score 90+ means excellent, 70-89 good, below 70 needs work
- NEVER return an issue where text_excerpt equals suggestion
PROMPT;
    }

    /**
     * Build the user prompt with content.
     */
    protected function buildUserPrompt(string $chunk, string $checksDescription, string $chunkInfo): string
    {
        return <<<PROMPT
Analyze the following web page content{$chunkInfo} for: {$checksDescription}

CONTENT TO ANALYZE:
---
{$chunk}
---

Provide your analysis as JSON only.
PROMPT;
    }

    /**
     * Get human-readable description of checks.
     *
     * @param  array<string>  $checks
     */
    protected function getChecksDescription(array $checks): string
    {
        $descriptions = [
            'spelling' => 'spelling errors',
            'grammar' => 'grammar issues',
            'seo' => 'SEO improvements',
            'readability' => 'readability concerns',
        ];

        $enabled = array_map(
            fn ($check) => $descriptions[$check] ?? $check,
            $checks
        );

        return implode(', ', $enabled);
    }

    /**
     * Parse the AI response into structured data.
     *
     * @return array{issues: array, scores: array}
     */
    protected function parseResponse(string $text, string $model = 'gpt-4o-mini'): array
    {
        // Extract JSON from the response (in case there's extra text)
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $text = $matches[0];
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            return [
                'issues' => [],
                'scores' => [],
            ];
        }

        // Filter out invalid issues and add AI source metadata
        $issues = $this->filterInvalidIssues($data['issues'] ?? [], $model);

        return [
            'issues' => $issues,
            'scores' => $data['scores'] ?? [],
        ];
    }

    /**
     * Filter out invalid or duplicate issues and add AI source metadata.
     */
    protected function filterInvalidIssues(array $issues, string $model = 'gpt-4o-mini'): array
    {
        $filtered = array_filter($issues, function ($issue) {
            // Skip if missing required fields
            if (empty($issue['text_excerpt']) || empty($issue['suggestion'])) {
                return false;
            }

            // Skip if text_excerpt equals suggestion (no actual change)
            $excerpt = trim(strtolower($issue['text_excerpt']));
            $suggestion = trim(strtolower($issue['suggestion']));
            if ($excerpt === $suggestion) {
                return false;
            }

            // Skip if suggestion is just vague advice (no concrete fix)
            $vaguePatterns = [
                '/^consider\s/i',
                '/^try\s/i',
                '/^you\s(should|could|might)/i',
                '/^it\s(would|could)\sbe\sbetter/i',
                '/more\sdescriptive/i',
                '/use\s(relevant|appropriate)\skeywords/i',
            ];
            foreach ($vaguePatterns as $pattern) {
                if (preg_match($pattern, $issue['suggestion'])) {
                    return false;
                }
            }

            // Skip overly short excerpts (likely false positives)
            if (strlen($issue['text_excerpt']) < 3) {
                return false;
            }

            return true;
        });

        // Add AI source metadata to filtered issues
        return array_values(array_map(function ($issue) use ($model) {
            return array_merge($issue, [
                'source_tool' => $issue['source_tool'] ?? $model,
                'confidence' => $issue['confidence'] ?? ($model === 'gpt-4o' ? 85 : 75),
            ]);
        }, $filtered));
    }

    /**
     * Store analysis results in the database.
     *
     * @param  array{issues: array, scores: array}  $analysis
     */
    public function storeResults(ScanResult $scanResult, array $analysis): void
    {
        // Update scan result with scores
        $scanResult->update([
            'scores' => $analysis['scores'],
        ]);

        // Create issue records
        foreach ($analysis['issues'] as $issueData) {
            Issue::create([
                'scan_result_id' => $scanResult->id,
                'category' => $issueData['category'] ?? 'unknown',
                'severity' => $issueData['severity'] ?? 'warning',
                'text_excerpt' => $issueData['text_excerpt'] ?? '',
                'suggestion' => $issueData['suggestion'] ?? null,
                'context' => $issueData['context'] ?? null,
                'dom_selector' => $issueData['dom_selector'] ?? null,
                'screenshot_path' => null,
                'position' => $issueData['position'] ?? null,
                'source_tool' => $issueData['source_tool'] ?? 'ai',
                'confidence' => $issueData['confidence'] ?? 80,
            ]);
        }
    }
}
