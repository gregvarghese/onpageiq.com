<?php

namespace App\Models;

use App\Enums\VpatConformanceLevel;
use App\Enums\VpatStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VpatEvaluation extends Model
{
    /** @use HasFactory<\Database\Factories\VpatEvaluationFactory> */
    use HasFactory;

    protected $fillable = [
        'accessibility_audit_id',
        'created_by_user_id',
        'product_name',
        'product_version',
        'product_description',
        'vendor_name',
        'vendor_contact',
        'evaluation_date',
        'evaluation_methods',
        'vpat_version',
        'report_types',
        'wcag_evaluations',
        'section508_evaluations',
        'en301549_evaluations',
        'legal_disclaimer',
        'notes',
        'status',
        'approved_by_user_id',
        'approved_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'evaluation_date' => 'date',
            'report_types' => 'array',
            'wcag_evaluations' => 'array',
            'section508_evaluations' => 'array',
            'en301549_evaluations' => 'array',
            'status' => VpatStatus::class,
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the accessibility audit this evaluation is based on.
     */
    public function accessibilityAudit(): BelongsTo
    {
        return $this->belongsTo(AccessibilityAudit::class);
    }

    /**
     * Get the user who created this evaluation.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the user who approved this evaluation.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Get all manual test checklists for this evaluation.
     */
    public function manualTests(): HasMany
    {
        return $this->hasMany(ManualTestChecklist::class);
    }

    /**
     * Set evaluation for a specific WCAG criterion.
     */
    public function setWcagEvaluation(string $criterionId, VpatConformanceLevel $level, ?string $remarks = null): void
    {
        $evaluations = $this->wcag_evaluations ?? [];
        $evaluations[$criterionId] = [
            'level' => $level->value,
            'remarks' => $remarks,
            'updated_at' => now()->toIso8601String(),
        ];
        $this->wcag_evaluations = $evaluations;
    }

    /**
     * Get evaluation for a specific WCAG criterion.
     *
     * @return array{level: string, remarks: ?string, updated_at: ?string}|null
     */
    public function getWcagEvaluation(string $criterionId): ?array
    {
        return $this->wcag_evaluations[$criterionId] ?? null;
    }

    /**
     * Get conformance level for a WCAG criterion.
     */
    public function getWcagConformanceLevel(string $criterionId): ?VpatConformanceLevel
    {
        $evaluation = $this->getWcagEvaluation($criterionId);

        if (! $evaluation) {
            return null;
        }

        return VpatConformanceLevel::tryFrom($evaluation['level']);
    }

    /**
     * Check if the VPAT is editable.
     */
    public function isEditable(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Submit for review.
     */
    public function submitForReview(): void
    {
        $this->update(['status' => VpatStatus::InReview]);
    }

    /**
     * Approve the VPAT.
     */
    public function approve(User $approver): void
    {
        $this->update([
            'status' => VpatStatus::Approved,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Publish the VPAT.
     */
    public function publish(): void
    {
        $this->update([
            'status' => VpatStatus::Published,
            'published_at' => now(),
        ]);
    }

    /**
     * Get completion percentage for WCAG evaluations.
     */
    public function getWcagCompletionPercentage(): float
    {
        $evaluations = $this->wcag_evaluations ?? [];
        $totalCriteria = count(config('wcag.criteria', []));

        if ($totalCriteria === 0) {
            return 100;
        }

        $evaluated = count(array_filter($evaluations, fn ($e) => $e['level'] !== VpatConformanceLevel::NotEvaluated->value));

        return round(($evaluated / $totalCriteria) * 100, 2);
    }

    /**
     * Get conformance summary for WCAG.
     *
     * @return array<string, int>
     */
    public function getWcagConformanceSummary(): array
    {
        $summary = [];
        foreach (VpatConformanceLevel::cases() as $level) {
            $summary[$level->value] = 0;
        }

        foreach ($this->wcag_evaluations ?? [] as $evaluation) {
            $level = $evaluation['level'] ?? VpatConformanceLevel::NotEvaluated->value;
            $summary[$level] = ($summary[$level] ?? 0) + 1;
        }

        return $summary;
    }

    /**
     * Check if includes a specific report type.
     */
    public function hasReportType(string $type): bool
    {
        return in_array($type, $this->report_types ?? []);
    }

    /**
     * Get the project through the audit.
     */
    public function getProjectAttribute(): ?Project
    {
        return $this->accessibilityAudit?->project;
    }
}
