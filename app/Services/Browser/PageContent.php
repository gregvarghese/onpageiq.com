<?php

namespace App\Services\Browser;

class PageContent
{
    public function __construct(
        public readonly string $url,
        public readonly string $html,
        public readonly string $text,
        public readonly string $title,
        public readonly array $meta,
        public readonly int $wordCount,
    ) {}

    /**
     * Check if this is a large page (over threshold).
     */
    public function isLarge(int $threshold = 50000): bool
    {
        return $this->wordCount > $threshold;
    }

    /**
     * Get the credit multiplier based on content size.
     */
    public function getCreditMultiplier(int $threshold = 50000): int
    {
        if ($this->wordCount <= $threshold) {
            return 1;
        }

        if ($this->wordCount <= $threshold * 2) {
            return 2;
        }

        return 3;
    }

    /**
     * Split the text content into chunks for AI processing.
     *
     * @return array<string>
     */
    public function splitIntoChunks(int $chunkSize = 50000): array
    {
        if (strlen($this->text) <= $chunkSize) {
            return [$this->text];
        }

        $chunks = [];
        $words = explode(' ', $this->text);
        $currentChunk = '';

        foreach ($words as $word) {
            if (strlen($currentChunk) + strlen($word) + 1 > $chunkSize) {
                $chunks[] = trim($currentChunk);
                $currentChunk = $word;
            } else {
                $currentChunk .= ' '.$word;
            }
        }

        if (trim($currentChunk) !== '') {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }

    /**
     * Create from rendered page data.
     *
     * @param  array{content: string, title: string, meta: array, html: string}  $data
     */
    public static function fromRenderedData(string $url, array $data): self
    {
        $text = $data['content'] ?? '';
        $wordCount = str_word_count($text);

        return new self(
            url: $url,
            html: $data['html'] ?? '',
            text: $text,
            title: $data['title'] ?? '',
            meta: $data['meta'] ?? [],
            wordCount: $wordCount,
        );
    }
}
