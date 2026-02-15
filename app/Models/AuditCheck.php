<?php

namespace App\Models;

use App\Enums\AuditCategory;
use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditCheck extends Model
{
    /** @use HasFactory<\Database\Factories\AuditCheckFactory> */
    use HasFactory;

    protected $fillable = [
        'accessibility_audit_id',
        'criterion_id',
        'criterion_name',
        'wcag_level',
        'category',
        'impact',
        'status',
        'element_selector',
        'element_html',
        'element_xpath',
        'message',
        'suggestion',
        'code_snippet',
        'documentation_url',
        'technique_id',
        'fingerprint',
        'is_recurring',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'wcag_level' => WcagLevel::class,
            'category' => AuditCategory::class,
            'impact' => ImpactLevel::class,
            'status' => CheckStatus::class,
            'is_recurring' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (AuditCheck $check) {
            if (empty($check->fingerprint) && $check->status === CheckStatus::Fail) {
                $check->fingerprint = $check->generateFingerprint();
            }
        });
    }

    /**
     * Get the audit this check belongs to.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(AccessibilityAudit::class, 'accessibility_audit_id');
    }

    /**
     * Get the evidence for this check.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(AuditEvidence::class);
    }

    /**
     * Check if this check passed.
     */
    public function isPassed(): bool
    {
        return $this->status === CheckStatus::Pass;
    }

    /**
     * Check if this check failed.
     */
    public function isFailed(): bool
    {
        return $this->status === CheckStatus::Fail;
    }

    /**
     * Check if this check is a warning.
     */
    public function isWarning(): bool
    {
        return $this->status === CheckStatus::Warning;
    }

    /**
     * Check if this check needs manual review.
     */
    public function needsManualReview(): bool
    {
        return $this->status === CheckStatus::ManualReview;
    }

    /**
     * Check if this check requires attention.
     */
    public function requiresAttention(): bool
    {
        return $this->status->requiresAttention();
    }

    /**
     * Generate a stable fingerprint for this issue.
     */
    public function generateFingerprint(): string
    {
        $components = [
            $this->criterion_id,
            $this->element_selector ?? '',
            $this->status->value,
            $this->message ?? '',
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Get the WCAG documentation URL.
     */
    public function getWcagUrl(): string
    {
        if ($this->documentation_url) {
            return $this->documentation_url;
        }

        // Generate default WCAG 2.1 documentation URL
        $criterionSlug = str_replace('.', '', $this->criterion_id);

        return "https://www.w3.org/WAI/WCAG21/Understanding/{$this->criterion_id}.html";
    }

    /**
     * Get the severity color for UI display.
     */
    public function getSeverityColor(): string
    {
        if ($this->impact) {
            return $this->impact->color();
        }

        return $this->status->color();
    }

    /**
     * Get the severity icon for UI display.
     */
    public function getSeverityIcon(): string
    {
        return $this->status->icon();
    }

    /**
     * Check if this issue was seen in the previous audit.
     */
    public function checkIfRecurring(): bool
    {
        if (! $this->fingerprint) {
            return false;
        }

        $audit = $this->audit;
        if (! $audit) {
            return false;
        }

        // Find the previous audit for the same project/URL
        $previousAudit = AccessibilityAudit::query()
            ->where('project_id', $audit->project_id)
            ->where('url_id', $audit->url_id)
            ->where('id', '<', $audit->id)
            ->orderByDesc('id')
            ->first();

        if (! $previousAudit) {
            return false;
        }

        return AuditCheck::query()
            ->where('accessibility_audit_id', $previousAudit->id)
            ->where('fingerprint', $this->fingerprint)
            ->exists();
    }

    /**
     * Mark this check as recurring.
     */
    public function markAsRecurring(): void
    {
        $this->update(['is_recurring' => true]);
    }

    /**
     * Get a truncated version of the element HTML for display.
     */
    public function getTruncatedHtml(int $maxLength = 200): ?string
    {
        if (! $this->element_html) {
            return null;
        }

        if (strlen($this->element_html) <= $maxLength) {
            return $this->element_html;
        }

        return substr($this->element_html, 0, $maxLength).'...';
    }
}
