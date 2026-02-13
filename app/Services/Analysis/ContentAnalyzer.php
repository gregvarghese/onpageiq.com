<?php

namespace App\Services\Analysis;

use App\Models\Issue;
use App\Models\ScanResult;
use App\Services\Browser\PageContent;
use Prism\Prism\Facades\Prism;

class ContentAnalyzer
{
    protected string $quickModel;

    protected string $deepModel;

    protected int $maxTokens;

    protected int $chunkSize;

    public function __construct()
    {
        $this->quickModel = config('onpageiq.ai.quick_model', 'gpt-4o-mini');
        $this->deepModel = config('onpageiq.ai.deep_model', 'gpt-4o');
        $this->maxTokens = config('onpageiq.ai.max_tokens', 4096);
        $this->chunkSize = config('onpageiq.ai.chunk_size', 50000);
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
        string $language = 'en'
    ): array {
        $model = $deepAnalysis ? $this->deepModel : $this->quickModel;

        $allIssues = [];
        $allScores = [];

        // Handle large pages by chunking
        $chunks = $content->splitIntoChunks($this->chunkSize);

        foreach ($chunks as $index => $chunk) {
            $result = $this->analyzeChunk($chunk, $checks, $model, $language, $index + 1, count($chunks));

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
        int $totalChunks
    ): array {
        $checksDescription = $this->getChecksDescription($checks);
        $chunkInfo = $totalChunks > 1 ? " (chunk {$chunkNumber} of {$totalChunks})" : '';

        $systemPrompt = $this->buildSystemPrompt($checks, $language);
        $userPrompt = $this->buildUserPrompt($chunk, $checksDescription, $chunkInfo);

        try {
            $response = Prism::text()
                ->using('openai', $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($userPrompt)
                ->withMaxTokens($this->maxTokens)
                ->asText();

            return $this->parseResponse($response->text);
        } catch (\Exception $e) {
            report($e);

            return [
                'issues' => [],
                'scores' => [],
            ];
        }
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
You are an expert content analyzer specializing in web content quality assessment.
Your task is to analyze text content and identify issues based on the requested check types.

The content is expected to be in: {$language}

Enabled checks: {$checksJson}

For each issue found, provide:
- category: one of "spelling", "grammar", "seo", "readability"
- severity: one of "error", "warning", "suggestion"
- text_excerpt: the problematic text (max 100 chars)
- suggestion: how to fix it

Also provide scores (0-100) for each enabled category.

Respond ONLY with valid JSON in this exact format:
{
  "issues": [
    {
      "category": "spelling",
      "severity": "error",
      "text_excerpt": "the problematic text",
      "suggestion": "how to fix it"
    }
  ],
  "scores": {
    "spelling": 95,
    "grammar": 90,
    "seo": 75,
    "readability": 80
  }
}

Important:
- Only include scores for enabled checks
- Be thorough but avoid false positives
- Focus on actual errors, not stylistic preferences
- For SEO, check for meta descriptions, heading structure, keyword usage
- For readability, consider sentence length, complex words, passive voice
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
    protected function parseResponse(string $text): array
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

        return [
            'issues' => $data['issues'] ?? [],
            'scores' => $data['scores'] ?? [],
        ];
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
                'dom_selector' => $issueData['dom_selector'] ?? null,
                'screenshot_path' => null,
                'position' => $issueData['position'] ?? null,
            ]);
        }
    }
}
