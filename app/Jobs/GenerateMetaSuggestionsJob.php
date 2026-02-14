<?php

namespace App\Jobs;

use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\AI\Facades\AI;

class GenerateMetaSuggestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    public function __construct(
        public Url $url,
        public ?int $userId = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $organization = $this->url->project->organization;

        // Check and deduct credits
        if (! $organization->hasCreditsFor('meta_suggestion')) {
            Log::warning('Insufficient credits for meta suggestions', [
                'url_id' => $this->url->id,
                'organization_id' => $organization->id,
            ]);

            return;
        }

        // Get current meta data and page content
        $scan = $this->url->latestCompletedScan;
        $metadata = $scan?->result?->metadata ?? [];
        $content = $scan?->result?->content_snapshot ?? '';

        if (empty($content)) {
            Log::info('No content for meta suggestion generation', ['url_id' => $this->url->id]);

            return;
        }

        // Generate suggestions using AI
        $suggestions = $this->generateSuggestions($metadata, $content);

        if (empty($suggestions)) {
            return;
        }

        // Store suggestions in scan result metadata
        $result = $scan->result;
        $existingMetadata = $result->metadata ?? [];
        $existingMetadata['ai_suggestions'] = $suggestions;
        $existingMetadata['suggestions_generated_at'] = now()->toIso8601String();
        $existingMetadata['suggestions_generated_by'] = $this->userId;

        $result->update(['metadata' => $existingMetadata]);

        // Deduct credit
        $organization->deductCredits('meta_suggestion', 1, [
            'url_id' => $this->url->id,
            'user_id' => $this->userId,
        ]);

        Log::info('Meta suggestions generated', [
            'url_id' => $this->url->id,
            'suggestions_count' => count($suggestions),
        ]);
    }

    /**
     * Generate AI suggestions for meta tags.
     *
     * @param  array<string, mixed>  $currentMeta
     * @return array<string, mixed>
     */
    protected function generateSuggestions(array $currentMeta, string $content): array
    {
        $prompt = $this->buildPrompt($currentMeta, $content);

        try {
            $response = AI::chat()
                ->model('gpt-4o-mini')
                ->system('You are an SEO expert specializing in meta tag optimization. Provide concise, actionable suggestions for improving page metadata to boost search engine rankings and click-through rates.')
                ->user($prompt)
                ->asJson()
                ->generate();

            $suggestions = json_decode($response->text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse AI response as JSON', [
                    'url_id' => $this->url->id,
                    'response' => $response->text,
                ]);

                return [];
            }

            return $this->validateSuggestions($suggestions);
        } catch (\Exception $e) {
            Log::error('AI meta suggestion generation failed', [
                'url_id' => $this->url->id,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Build the AI prompt.
     *
     * @param  array<string, mixed>  $currentMeta
     */
    protected function buildPrompt(array $currentMeta, string $content): string
    {
        $contentPreview = substr(strip_tags($content), 0, 2000);

        return <<<PROMPT
Analyze this page and suggest optimized meta tags. Return a JSON object with the following structure:

{
    "title": {
        "current": "current title or null",
        "suggested": "optimized title (50-60 chars)",
        "reasoning": "brief explanation"
    },
    "description": {
        "current": "current description or null",
        "suggested": "optimized description (150-160 chars)",
        "reasoning": "brief explanation"
    },
    "og_title": {
        "current": "current og:title or null",
        "suggested": "optimized og:title",
        "reasoning": "brief explanation"
    },
    "og_description": {
        "current": "current og:description or null",
        "suggested": "optimized og:description",
        "reasoning": "brief explanation"
    },
    "keywords": ["keyword1", "keyword2", "keyword3"],
    "overall_score": 0-100,
    "priority_improvements": ["improvement1", "improvement2"]
}

Current Meta Tags:
- Title: {$currentMeta['title']}
- Description: {$currentMeta['meta_description']}
- OG Title: {$currentMeta['og_title']}
- OG Description: {$currentMeta['og_description']}

Page Content Preview:
{$contentPreview}

Provide specific, actionable suggestions that will improve SEO and click-through rates.
PROMPT;
    }

    /**
     * Validate and sanitize AI suggestions.
     *
     * @param  array<string, mixed>|null  $suggestions
     * @return array<string, mixed>
     */
    protected function validateSuggestions(?array $suggestions): array
    {
        if (empty($suggestions)) {
            return [];
        }

        $validated = [];

        // Validate title suggestion
        if (isset($suggestions['title']['suggested'])) {
            $title = $suggestions['title']['suggested'];
            if (strlen($title) > 10 && strlen($title) <= 70) {
                $validated['title'] = $suggestions['title'];
            }
        }

        // Validate description suggestion
        if (isset($suggestions['description']['suggested'])) {
            $desc = $suggestions['description']['suggested'];
            if (strlen($desc) > 50 && strlen($desc) <= 200) {
                $validated['description'] = $suggestions['description'];
            }
        }

        // Validate OG suggestions
        if (isset($suggestions['og_title']['suggested'])) {
            $validated['og_title'] = $suggestions['og_title'];
        }

        if (isset($suggestions['og_description']['suggested'])) {
            $validated['og_description'] = $suggestions['og_description'];
        }

        // Keywords
        if (isset($suggestions['keywords']) && is_array($suggestions['keywords'])) {
            $validated['keywords'] = array_slice($suggestions['keywords'], 0, 10);
        }

        // Score and improvements
        if (isset($suggestions['overall_score'])) {
            $validated['overall_score'] = max(0, min(100, (int) $suggestions['overall_score']));
        }

        if (isset($suggestions['priority_improvements']) && is_array($suggestions['priority_improvements'])) {
            $validated['priority_improvements'] = array_slice($suggestions['priority_improvements'], 0, 5);
        }

        return $validated;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Meta suggestion generation job failed', [
            'url_id' => $this->url->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'meta-suggestions',
            'ai',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
