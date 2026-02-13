<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScanResult extends Model
{
    /** @use HasFactory<\Database\Factories\ScanResultFactory> */
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'content_snapshot',
        'scores',
        'screenshots',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'scores' => 'array',
            'screenshots' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the scan this result belongs to.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Get all issues found in this scan.
     */
    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Get issues by category.
     */
    public function issuesByCategory(string $category): HasMany
    {
        return $this->issues()->where('category', $category);
    }

    /**
     * Get issues by severity.
     */
    public function issuesBySeverity(string $severity): HasMany
    {
        return $this->issues()->where('severity', $severity);
    }

    /**
     * Get the total issue count.
     */
    public function getTotalIssueCount(): int
    {
        return $this->issues()->count();
    }

    /**
     * Get the error count.
     */
    public function getErrorCount(): int
    {
        return $this->issues()->where('severity', 'error')->count();
    }

    /**
     * Get the warning count.
     */
    public function getWarningCount(): int
    {
        return $this->issues()->where('severity', 'warning')->count();
    }

    /**
     * Get the readability score.
     */
    public function getReadabilityScore(): ?float
    {
        return $this->scores['readability'] ?? null;
    }

    /**
     * Get the SEO score.
     */
    public function getSeoScore(): ?float
    {
        return $this->scores['seo'] ?? null;
    }

    /**
     * Get the overall score.
     */
    public function getOverallScore(): ?float
    {
        return $this->scores['overall'] ?? null;
    }
}
