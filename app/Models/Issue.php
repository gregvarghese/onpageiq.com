<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    /** @use HasFactory<\Database\Factories\IssueFactory> */
    use HasFactory;

    protected $fillable = [
        'scan_result_id',
        'category',
        'severity',
        'text_excerpt',
        'suggestion',
        'dom_selector',
        'screenshot_path',
        'position',
        'source_tool',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'array',
        ];
    }

    /**
     * Get the scan result this issue belongs to.
     */
    public function scanResult(): BelongsTo
    {
        return $this->belongsTo(ScanResult::class);
    }

    /**
     * Check if this is a spelling issue.
     */
    public function isSpelling(): bool
    {
        return $this->category === 'spelling';
    }

    /**
     * Check if this is a grammar issue.
     */
    public function isGrammar(): bool
    {
        return $this->category === 'grammar';
    }

    /**
     * Check if this is an SEO issue.
     */
    public function isSeo(): bool
    {
        return $this->category === 'seo';
    }

    /**
     * Check if this is a readability issue.
     */
    public function isReadability(): bool
    {
        return $this->category === 'readability';
    }

    /**
     * Check if this is an error severity.
     */
    public function isError(): bool
    {
        return $this->severity === 'error';
    }

    /**
     * Check if this is a warning severity.
     */
    public function isWarning(): bool
    {
        return $this->severity === 'warning';
    }

    /**
     * Check if this is a suggestion severity.
     */
    public function isSuggestion(): bool
    {
        return $this->severity === 'suggestion';
    }

    /**
     * Get the severity color for UI display.
     */
    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'error' => 'red',
            'warning' => 'yellow',
            'suggestion' => 'blue',
            default => 'gray',
        };
    }

    /**
     * Get the category icon for UI display.
     */
    public function getCategoryIcon(): string
    {
        return match ($this->category) {
            'spelling' => 'spell-check',
            'grammar' => 'text',
            'seo' => 'search',
            'readability' => 'book-open',
            default => 'exclamation',
        };
    }

    /**
     * Get human-readable source tool name.
     */
    public function getSourceToolName(): string
    {
        return match ($this->source_tool) {
            'hunspell' => 'Hunspell',
            'languagetool' => 'LanguageTool',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
            default => ucfirst($this->source_tool ?? 'AI'),
        };
    }

    /**
     * Get confidence color for UI display.
     */
    public function getConfidenceColor(): string
    {
        $confidence = $this->confidence ?? 80;

        return match (true) {
            $confidence >= 90 => 'green',
            $confidence >= 75 => 'yellow',
            default => 'orange',
        };
    }

    /**
     * Get confidence label for UI display.
     */
    public function getConfidenceLabel(): string
    {
        $confidence = $this->confidence ?? 80;

        return match (true) {
            $confidence >= 90 => 'High',
            $confidence >= 75 => 'Medium',
            default => 'Low',
        };
    }
}
