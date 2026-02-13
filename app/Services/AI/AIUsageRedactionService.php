<?php

namespace App\Services\AI;

use App\Services\AI\DTOs\RedactionResult;

/**
 * Service to redact sensitive data from AI prompts and responses.
 */
class AIUsageRedactionService
{
    /**
     * Patterns for sensitive data detection and redaction.
     *
     * @var array<string, string>
     */
    protected array $patterns = [
        'email' => '/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/',
        'phone' => '/(\+?1[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/',
        'ssn' => '/\b\d{3}-\d{2}-\d{4}\b/',
        'credit_card' => '/\b(?:\d{4}[-\s]?){3}\d{4}\b/',
        'api_key' => '/\b(sk|pk|api|key|token|secret)[-_]?[a-zA-Z0-9]{20,}\b/i',
        'aws_key' => '/\b(AKIA|ABIA|ACCA|ASIA)[A-Z0-9]{16}\b/',
        'private_key' => '/-----BEGIN\s+(RSA\s+)?PRIVATE\s+KEY-----[\s\S]*?-----END\s+(RSA\s+)?PRIVATE\s+KEY-----/',
        'ip_address' => '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
        'jwt' => '/\beyJ[a-zA-Z0-9_-]*\.eyJ[a-zA-Z0-9_-]*\.[a-zA-Z0-9_-]*\b/',
    ];

    /**
     * Redact sensitive content from a string.
     */
    public function redact(?string $content): RedactionResult
    {
        if ($content === null || $content === '') {
            return RedactionResult::unchanged('');
        }

        $redactedContent = $content;
        $summary = [];

        foreach ($this->patterns as $type => $pattern) {
            $count = 0;
            $redactedContent = preg_replace_callback(
                $pattern,
                function () use ($type, &$count) {
                    $count++;

                    return "[REDACTED:{$type}]";
                },
                $redactedContent
            );

            if ($count > 0) {
                $summary[$type] = $count;
            }
        }

        if (empty($summary)) {
            return RedactionResult::unchanged($content);
        }

        return RedactionResult::redacted($redactedContent, $summary);
    }

    /**
     * Check if content contains sensitive data without redacting.
     */
    public function containsSensitiveData(?string $content): bool
    {
        if ($content === null || $content === '') {
            return false;
        }

        foreach ($this->patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add a custom pattern for redaction.
     */
    public function addPattern(string $name, string $pattern): self
    {
        $this->patterns[$name] = $pattern;

        return $this;
    }

    /**
     * Remove a pattern from redaction.
     */
    public function removePattern(string $name): self
    {
        unset($this->patterns[$name]);

        return $this;
    }

    /**
     * Get all configured patterns.
     *
     * @return array<string, string>
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }
}
