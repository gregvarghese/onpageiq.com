<?php

namespace App\Jobs;

use App\Models\DuplicateContent;
use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DetectDuplicateContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Minimum similarity threshold (0-100) to consider as duplicate.
     */
    protected int $similarityThreshold = 70;

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

        if (! $text) {
            // Get content from latest scan result
            $scanResult = $this->url->latestCompletedScan?->result;
            $text = $scanResult?->content_snapshot ?? '';
        }

        if (empty(trim($text))) {
            Log::info('No text content for duplicate detection', ['url_id' => $this->url->id]);

            return;
        }

        // Generate content hash for exact match detection
        $contentHash = $this->generateContentHash($text);

        // Get all other URLs in the same project
        $otherUrls = $this->url->project->urls()
            ->where('id', '!=', $this->url->id)
            ->where('status', 'completed')
            ->with('latestCompletedScan.result')
            ->get();

        // Clear previous duplicate detections for this URL
        DuplicateContent::where('url_id', $this->url->id)
            ->orWhere('duplicate_url_id', $this->url->id)
            ->delete();

        foreach ($otherUrls as $otherUrl) {
            $otherText = $otherUrl->latestCompletedScan?->result?->content_snapshot ?? '';

            if (empty(trim($otherText))) {
                continue;
            }

            $otherHash = $this->generateContentHash($otherText);

            // Check for exact match
            if ($contentHash === $otherHash) {
                $this->recordDuplicate($otherUrl, 100, 'exact');

                continue;
            }

            // Calculate similarity percentage
            $similarity = $this->calculateSimilarity($text, $otherText);

            if ($similarity >= $this->similarityThreshold) {
                $matchType = $similarity >= 90 ? 'near_exact' : 'similar';
                $this->recordDuplicate($otherUrl, $similarity, $matchType);
            }
        }

        Log::info('Duplicate content detection completed', [
            'url_id' => $this->url->id,
            'duplicates_found' => DuplicateContent::where('url_id', $this->url->id)->count(),
        ]);
    }

    /**
     * Generate a normalized hash of the content.
     */
    protected function generateContentHash(string $text): string
    {
        // Normalize: lowercase, remove extra whitespace, remove punctuation
        $normalized = strtolower($text);
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);

        return md5($normalized);
    }

    /**
     * Calculate similarity between two texts using multiple methods.
     */
    protected function calculateSimilarity(string $text1, string $text2): int
    {
        // Normalize texts
        $words1 = $this->extractWords($text1);
        $words2 = $this->extractWords($text2);

        if (empty($words1) || empty($words2)) {
            return 0;
        }

        // Method 1: Jaccard similarity (word overlap)
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        $jaccardSimilarity = $union > 0 ? ($intersection / $union) * 100 : 0;

        // Method 2: Cosine similarity using word frequency
        $cosineSimilarity = $this->calculateCosineSimilarity($words1, $words2);

        // Method 3: Shingle-based similarity (n-grams)
        $shingleSimilarity = $this->calculateShingleSimilarity($text1, $text2);

        // Weight and combine similarities
        $weightedSimilarity = (
            ($jaccardSimilarity * 0.3) +
            ($cosineSimilarity * 0.4) +
            ($shingleSimilarity * 0.3)
        );

        return (int) round($weightedSimilarity);
    }

    /**
     * Extract normalized words from text.
     *
     * @return array<string>
     */
    protected function extractWords(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', '', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those', 'it', 'its'];

        return array_values(array_diff($words, $stopWords));
    }

    /**
     * Calculate cosine similarity between word frequency vectors.
     *
     * @param  array<string>  $words1
     * @param  array<string>  $words2
     */
    protected function calculateCosineSimilarity(array $words1, array $words2): float
    {
        $freq1 = array_count_values($words1);
        $freq2 = array_count_values($words2);

        $allWords = array_unique(array_merge(array_keys($freq1), array_keys($freq2)));

        $dotProduct = 0;
        $magnitude1 = 0;
        $magnitude2 = 0;

        foreach ($allWords as $word) {
            $v1 = $freq1[$word] ?? 0;
            $v2 = $freq2[$word] ?? 0;

            $dotProduct += $v1 * $v2;
            $magnitude1 += $v1 * $v1;
            $magnitude2 += $v2 * $v2;
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        return ($dotProduct / ($magnitude1 * $magnitude2)) * 100;
    }

    /**
     * Calculate similarity using shingles (n-grams).
     */
    protected function calculateShingleSimilarity(string $text1, string $text2, int $n = 3): float
    {
        $shingles1 = $this->generateShingles($text1, $n);
        $shingles2 = $this->generateShingles($text2, $n);

        if (empty($shingles1) || empty($shingles2)) {
            return 0;
        }

        $intersection = count(array_intersect($shingles1, $shingles2));
        $union = count(array_unique(array_merge($shingles1, $shingles2)));

        return $union > 0 ? ($intersection / $union) * 100 : 0;
    }

    /**
     * Generate n-gram shingles from text.
     *
     * @return array<string>
     */
    protected function generateShingles(string $text, int $n): array
    {
        $text = strtolower(preg_replace('/\s+/', ' ', trim($text)));
        $words = explode(' ', $text);

        if (count($words) < $n) {
            return [implode(' ', $words)];
        }

        $shingles = [];
        for ($i = 0; $i <= count($words) - $n; $i++) {
            $shingles[] = implode(' ', array_slice($words, $i, $n));
        }

        return array_unique($shingles);
    }

    /**
     * Record a duplicate content entry.
     */
    protected function recordDuplicate(Url $duplicateUrl, int $similarity, string $matchType): void
    {
        DuplicateContent::create([
            'url_id' => $this->url->id,
            'duplicate_url_id' => $duplicateUrl->id,
            'scan_id' => $this->scanId,
            'similarity_percentage' => $similarity,
            'match_type' => $matchType,
            'detected_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Duplicate content detection job failed', [
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
            'duplicate-content',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
