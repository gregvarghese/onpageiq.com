<?php

namespace App\Services\AI\DTOs;

/**
 * Result of content redaction operation.
 */
readonly class RedactionResult
{
    /**
     * @param  array<string, int>  $redactionSummary  Count of each redaction type
     */
    public function __construct(
        public string $content,
        public bool $wasRedacted,
        public array $redactionSummary = [],
    ) {}

    /**
     * Create from unredacted content.
     */
    public static function unchanged(string $content): self
    {
        return new self(
            content: $content,
            wasRedacted: false,
            redactionSummary: [],
        );
    }

    /**
     * Create from redacted content.
     *
     * @param  array<string, int>  $summary
     */
    public static function redacted(string $content, array $summary): self
    {
        return new self(
            content: $content,
            wasRedacted: true,
            redactionSummary: $summary,
        );
    }

    /**
     * Get total number of redactions made.
     */
    public function totalRedactions(): int
    {
        return array_sum($this->redactionSummary);
    }
}
