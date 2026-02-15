<?php

namespace App\Models;

use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use App\Enums\CheckStatus;
use App\Enums\ComplianceFramework;
use App\Enums\WcagLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccessibilityAudit extends Model
{
    /** @use HasFactory<\Database\Factories\AccessibilityAuditFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'url_id',
        'triggered_by_user_id',
        'wcag_level_target',
        'framework',
        'status',
        'error_message',
        'overall_score',
        'scores_by_category',
        'checks_total',
        'checks_passed',
        'checks_failed',
        'checks_warning',
        'checks_manual',
        'checks_not_applicable',
        'started_at',
        'completed_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => AuditStatus::class,
            'wcag_level_target' => WcagLevel::class,
            'framework' => ComplianceFramework::class,
            'overall_score' => 'decimal:2',
            'scores_by_category' => 'array',
            'checks_total' => 'integer',
            'checks_passed' => 'integer',
            'checks_failed' => 'integer',
            'checks_warning' => 'integer',
            'checks_manual' => 'integer',
            'checks_not_applicable' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the project this audit belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the specific URL being audited (if applicable).
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the user who triggered the audit.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /**
     * Get all checks for this audit.
     */
    public function checks(): HasMany
    {
        return $this->hasMany(AuditCheck::class);
    }

    /**
     * Get the VPAT evaluation for this audit.
     */
    public function vpatEvaluation(): HasOne
    {
        return $this->hasOne(VpatEvaluation::class);
    }

    /**
     * Get only failed checks.
     */
    public function failedChecks(): HasMany
    {
        return $this->checks()->where('status', CheckStatus::Fail);
    }

    /**
     * Get checks requiring attention (fail, warning, manual review).
     */
    public function checksRequiringAttention(): HasMany
    {
        return $this->checks()->whereIn('status', [
            CheckStatus::Fail,
            CheckStatus::Warning,
            CheckStatus::ManualReview,
        ]);
    }

    /**
     * Check if the audit is pending.
     */
    public function isPending(): bool
    {
        return $this->status === AuditStatus::Pending;
    }

    /**
     * Check if the audit is running.
     */
    public function isRunning(): bool
    {
        return $this->status === AuditStatus::Running;
    }

    /**
     * Check if the audit is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === AuditStatus::Completed;
    }

    /**
     * Check if the audit failed.
     */
    public function isFailed(): bool
    {
        return $this->status === AuditStatus::Failed;
    }

    /**
     * Check if the audit is in a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Mark the audit as running.
     */
    public function markAsRunning(): void
    {
        $this->update([
            'status' => AuditStatus::Running,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the audit as completed and calculate scores.
     */
    public function markAsCompleted(): void
    {
        $this->recalculateScores();
        $this->update([
            'status' => AuditStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the audit as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => AuditStatus::Failed,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the audit as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => AuditStatus::Cancelled,
            'completed_at' => now(),
        ]);
    }

    /**
     * Recalculate all scores from checks.
     */
    public function recalculateScores(): void
    {
        $checks = $this->checks()->get();

        $total = $checks->count();
        $passed = $checks->where('status', CheckStatus::Pass)->count();
        $failed = $checks->where('status', CheckStatus::Fail)->count();
        $warning = $checks->where('status', CheckStatus::Warning)->count();
        $manual = $checks->where('status', CheckStatus::ManualReview)->count();
        $notApplicable = $checks->where('status', CheckStatus::NotApplicable)->count();

        // Calculate overall score (percentage of passing checks, excluding N/A)
        $applicableChecks = $total - $notApplicable;
        $overallScore = $applicableChecks > 0
            ? round(($passed / $applicableChecks) * 100, 2)
            : 100;

        // Calculate scores by category
        $scoresByCategory = [];
        foreach (AuditCategory::cases() as $category) {
            $categoryChecks = $checks->where('category', $category->value);
            $categoryTotal = $categoryChecks->count();
            $categoryNotApplicable = $categoryChecks->where('status', CheckStatus::NotApplicable)->count();
            $categoryPassed = $categoryChecks->where('status', CheckStatus::Pass)->count();

            $categoryApplicable = $categoryTotal - $categoryNotApplicable;
            $scoresByCategory[$category->value] = $categoryApplicable > 0
                ? round(($categoryPassed / $categoryApplicable) * 100, 2)
                : 100;
        }

        $this->update([
            'checks_total' => $total,
            'checks_passed' => $passed,
            'checks_failed' => $failed,
            'checks_warning' => $warning,
            'checks_manual' => $manual,
            'checks_not_applicable' => $notApplicable,
            'overall_score' => $overallScore,
            'scores_by_category' => $scoresByCategory,
        ]);
    }

    /**
     * Get the score for a specific category.
     */
    public function getCategoryScore(AuditCategory $category): float
    {
        return $this->scores_by_category[$category->value] ?? 0;
    }

    /**
     * Get the compliance percentage for the target WCAG level.
     */
    public function getCompliancePercentage(): float
    {
        $targetLevels = match ($this->wcag_level_target) {
            WcagLevel::A => [WcagLevel::A],
            WcagLevel::AA => [WcagLevel::A, WcagLevel::AA],
            WcagLevel::AAA => [WcagLevel::A, WcagLevel::AA, WcagLevel::AAA],
        };

        $levelValues = array_map(fn ($l) => $l->value, $targetLevels);

        $checks = $this->checks()
            ->whereIn('wcag_level', $levelValues)
            ->get();

        $total = $checks->count();
        $notApplicable = $checks->where('status', CheckStatus::NotApplicable)->count();
        $passed = $checks->where('status', CheckStatus::Pass)->count();

        $applicable = $total - $notApplicable;

        return $applicable > 0 ? round(($passed / $applicable) * 100, 2) : 100;
    }

    /**
     * Get the duration of the audit in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get a human-readable duration.
     */
    public function getFormattedDuration(): ?string
    {
        $seconds = $this->getDurationInSeconds();

        if ($seconds === null) {
            return null;
        }

        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return "{$minutes}m {$remainingSeconds}s";
    }
}
