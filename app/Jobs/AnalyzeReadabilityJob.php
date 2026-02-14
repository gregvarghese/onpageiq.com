<?php

namespace App\Jobs;

use App\Models\PageMetrics;
use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnalyzeReadabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    public function __construct(
        public Url $url,
        public ?int $scanId = null,
        public ?string $textContent = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $text = $this->textContent;

        // Fetch page content if not provided
        if (! $text) {
            try {
                $response = Http::timeout(30)->get($this->url->url);
                $text = $this->extractTextFromHtml($response->body());
            } catch (\Exception $e) {
                Log::warning('Failed to fetch page for readability analysis', [
                    'url_id' => $this->url->id,
                    'error' => $e->getMessage(),
                ]);

                return;
            }
        }

        if (empty(trim($text))) {
            Log::info('No text content to analyze', ['url_id' => $this->url->id]);

            return;
        }

        // Calculate readability metrics
        $metrics = $this->calculateReadability($text);

        // Update or create page metrics
        PageMetrics::updateOrCreate(
            [
                'url_id' => $this->url->id,
                'scan_id' => $this->scanId,
            ],
            [
                'word_count' => $metrics['word_count'],
                'sentence_count' => $metrics['sentence_count'],
                'paragraph_count' => $metrics['paragraph_count'],
                'readability_grade' => $metrics['grade_level'],
                'readability_ease' => $metrics['reading_ease'],
                'avg_words_per_sentence' => $metrics['avg_words_per_sentence'],
                'avg_syllables_per_word' => $metrics['avg_syllables_per_word'],
            ]
        );

        Log::info('Readability analysis completed', [
            'url_id' => $this->url->id,
            'grade_level' => $metrics['grade_level'],
            'reading_ease' => $metrics['reading_ease'],
        ]);
    }

    /**
     * Extract text content from HTML.
     */
    protected function extractTextFromHtml(string $html): string
    {
        // Remove script and style elements
        $html = preg_replace('/<script[^>]*>.*?<\/script>/si', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/si', '', $html);

        // Remove HTML tags
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Calculate readability metrics.
     *
     * @return array<string, mixed>
     */
    protected function calculateReadability(string $text): array
    {
        // Count words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $wordCount = count($words);

        // Count sentences (roughly)
        $sentenceCount = preg_match_all('/[.!?]+/', $text, $matches);
        $sentenceCount = max($sentenceCount, 1);

        // Count paragraphs (double newlines or single sentences for simple texts)
        $paragraphCount = max(1, preg_match_all('/\n\s*\n/', $text, $matches) + 1);

        // Count syllables
        $totalSyllables = 0;
        foreach ($words as $word) {
            $totalSyllables += $this->countSyllables($word);
        }

        // Calculate averages
        $avgWordsPerSentence = $wordCount / $sentenceCount;
        $avgSyllablesPerWord = $wordCount > 0 ? $totalSyllables / $wordCount : 0;

        // Flesch Reading Ease Score (0-100, higher is easier)
        // Formula: 206.835 - 1.015 * (words/sentences) - 84.6 * (syllables/words)
        $readingEase = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $avgSyllablesPerWord);
        $readingEase = max(0, min(100, round($readingEase, 1)));

        // Flesch-Kincaid Grade Level
        // Formula: 0.39 * (words/sentences) + 11.8 * (syllables/words) - 15.59
        $gradeLevel = (0.39 * $avgWordsPerSentence) + (11.8 * $avgSyllablesPerWord) - 15.59;
        $gradeLevel = max(0, min(18, round($gradeLevel, 1)));

        return [
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'paragraph_count' => $paragraphCount,
            'total_syllables' => $totalSyllables,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'avg_syllables_per_word' => round($avgSyllablesPerWord, 2),
            'reading_ease' => $readingEase,
            'grade_level' => $gradeLevel,
        ];
    }

    /**
     * Count syllables in a word.
     */
    protected function countSyllables(string $word): int
    {
        $word = strtolower(trim($word));
        $word = preg_replace('/[^a-z]/', '', $word);

        if (strlen($word) <= 3) {
            return 1;
        }

        // Remove silent e at end
        $word = preg_replace('/e$/', '', $word);

        // Count vowel groups
        preg_match_all('/[aeiouy]+/', $word, $matches);
        $count = count($matches[0]);

        return max(1, $count);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Readability analysis job failed', [
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
            'readability',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
