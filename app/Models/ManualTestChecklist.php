<?php

namespace App\Models;

use App\Enums\ManualTestStatus;
use App\Enums\WcagLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ManualTestChecklist extends Model
{
    /** @use HasFactory<\Database\Factories\ManualTestChecklistFactory> */
    use HasFactory;

    protected $fillable = [
        'accessibility_audit_id',
        'vpat_evaluation_id',
        'tester_user_id',
        'criterion_id',
        'wcag_level',
        'test_name',
        'test_description',
        'test_steps',
        'expected_results',
        'status',
        'actual_results',
        'tester_notes',
        'browser',
        'browser_version',
        'assistive_technology',
        'operating_system',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'test_steps' => 'array',
            'expected_results' => 'array',
            'status' => ManualTestStatus::class,
            'wcag_level' => WcagLevel::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the accessibility audit this test belongs to.
     */
    public function accessibilityAudit(): BelongsTo
    {
        return $this->belongsTo(AccessibilityAudit::class);
    }

    /**
     * Get the VPAT evaluation this test belongs to.
     */
    public function vpatEvaluation(): BelongsTo
    {
        return $this->belongsTo(VpatEvaluation::class);
    }

    /**
     * Get the user performing the test.
     */
    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tester_user_id');
    }

    /**
     * Get evidence attached to this test.
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(AuditEvidence::class, 'manual_test_checklist_id');
    }

    /**
     * Start the test.
     */
    public function start(): void
    {
        $this->update([
            'status' => ManualTestStatus::InProgress,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark as passed.
     */
    public function markAsPassed(?string $actualResults = null, ?string $notes = null): void
    {
        $this->update([
            'status' => ManualTestStatus::Passed,
            'actual_results' => $actualResults,
            'tester_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(?string $actualResults = null, ?string $notes = null): void
    {
        $this->update([
            'status' => ManualTestStatus::Failed,
            'actual_results' => $actualResults,
            'tester_notes' => $notes,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark as blocked.
     */
    public function markAsBlocked(?string $reason = null): void
    {
        $this->update([
            'status' => ManualTestStatus::Blocked,
            'tester_notes' => $reason,
            'completed_at' => now(),
        ]);
    }

    /**
     * Skip the test.
     */
    public function skip(?string $reason = null): void
    {
        $this->update([
            'status' => ManualTestStatus::Skipped,
            'tester_notes' => $reason,
            'completed_at' => now(),
        ]);
    }

    /**
     * Check if test is complete.
     */
    public function isComplete(): bool
    {
        return $this->status->isTerminal();
    }

    /**
     * Check if test passed.
     */
    public function isPassed(): bool
    {
        return $this->status->isSuccess();
    }

    /**
     * Get test duration in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Set environment information.
     */
    public function setEnvironment(
        ?string $browser = null,
        ?string $browserVersion = null,
        ?string $assistiveTechnology = null,
        ?string $operatingSystem = null
    ): void {
        $this->update([
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'assistive_technology' => $assistiveTechnology,
            'operating_system' => $operatingSystem,
        ]);
    }

    /**
     * Scope to filter by criterion.
     */
    public function scopeForCriterion($query, string $criterionId)
    {
        return $query->where('criterion_id', $criterionId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, ManualTestStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get incomplete tests.
     */
    public function scopeIncomplete($query)
    {
        return $query->whereIn('status', [
            ManualTestStatus::Pending,
            ManualTestStatus::InProgress,
        ]);
    }

    /**
     * Scope to get completed tests.
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            ManualTestStatus::Passed,
            ManualTestStatus::Failed,
            ManualTestStatus::Blocked,
            ManualTestStatus::Skipped,
        ]);
    }
}
