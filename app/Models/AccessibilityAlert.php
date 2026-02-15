<?php

namespace App\Models;

use App\Enums\AlertType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessibilityAlert extends Model
{
    /** @use HasFactory<\Database\Factories\AccessibilityAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'accessibility_audit_id',
        'compliance_deadline_id',
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'is_dismissed',
        'email_sent',
        'read_at',
        'dismissed_at',
        'email_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => AlertType::class,
            'data' => 'array',
            'is_read' => 'boolean',
            'is_dismissed' => 'boolean',
            'email_sent' => 'boolean',
            'read_at' => 'datetime',
            'dismissed_at' => 'datetime',
            'email_sent_at' => 'datetime',
        ];
    }

    /**
     * Get the project this alert belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the audit this alert is related to.
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(AccessibilityAudit::class, 'accessibility_audit_id');
    }

    /**
     * Get the deadline this alert is related to.
     */
    public function deadline(): BelongsTo
    {
        return $this->belongsTo(ComplianceDeadline::class, 'compliance_deadline_id');
    }

    /**
     * Get the user this alert is for.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the alert as read.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark the alert as dismissed.
     */
    public function dismiss(): void
    {
        $this->update([
            'is_dismissed' => true,
            'dismissed_at' => now(),
        ]);
    }

    /**
     * Mark email as sent.
     */
    public function markEmailSent(): void
    {
        $this->update([
            'email_sent' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Check if this alert should send an email.
     */
    public function shouldSendEmail(): bool
    {
        return ! $this->email_sent && $this->type->shouldEmail();
    }

    /**
     * Get the severity of this alert.
     */
    public function getSeverity(): string
    {
        return $this->type->severity();
    }

    /**
     * Get the color for UI display.
     */
    public function getColor(): string
    {
        return $this->type->color();
    }

    /**
     * Get the icon for UI display.
     */
    public function getIcon(): string
    {
        return $this->type->icon();
    }

    /**
     * Scope to get unread alerts.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope to get non-dismissed alerts.
     */
    public function scopeNotDismissed($query)
    {
        return $query->where('is_dismissed', false);
    }

    /**
     * Scope to get alerts for a user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get alerts by type.
     */
    public function scopeOfType($query, AlertType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get alerts needing email.
     */
    public function scopeNeedingEmail($query)
    {
        return $query->where('email_sent', false)
            ->whereIn('type', collect(AlertType::cases())
                ->filter(fn ($type) => $type->shouldEmail())
                ->map(fn ($type) => $type->value)
                ->toArray());
    }

    /**
     * Create a score threshold alert.
     */
    public static function createScoreThresholdAlert(
        AccessibilityAudit $audit,
        float $score,
        float $threshold
    ): self {
        return self::create([
            'project_id' => $audit->project_id,
            'accessibility_audit_id' => $audit->id,
            'type' => AlertType::ScoreThreshold,
            'title' => 'Accessibility Score Below Threshold',
            'message' => sprintf(
                'The accessibility score (%.1f%%) has dropped below the threshold of %.1f%%.',
                $score,
                $threshold
            ),
            'data' => [
                'score' => $score,
                'threshold' => $threshold,
                'audit_id' => $audit->id,
            ],
        ]);
    }

    /**
     * Create a regression alert.
     */
    public static function createRegressionAlert(
        AccessibilityAudit $audit,
        array $regressionData
    ): self {
        return self::create([
            'project_id' => $audit->project_id,
            'accessibility_audit_id' => $audit->id,
            'type' => AlertType::Regression,
            'title' => 'Accessibility Regression Detected',
            'message' => sprintf(
                '%d new issues detected. Score dropped from %.1f%% to %.1f%%.',
                $regressionData['new_issues_count'] ?? 0,
                $regressionData['previous_score'] ?? 0,
                $regressionData['current_score'] ?? 0
            ),
            'data' => $regressionData,
        ]);
    }

    /**
     * Create a deadline reminder alert.
     */
    public static function createDeadlineReminder(
        ComplianceDeadline $deadline,
        int $daysUntil
    ): self {
        return self::create([
            'project_id' => $deadline->project_id,
            'compliance_deadline_id' => $deadline->id,
            'type' => AlertType::DeadlineReminder,
            'title' => 'Compliance Deadline Approaching',
            'message' => sprintf(
                '%s deadline "%s" is in %d day(s).',
                $deadline->type->label(),
                $deadline->title,
                $daysUntil
            ),
            'data' => [
                'deadline_id' => $deadline->id,
                'days_until' => $daysUntil,
                'deadline_date' => $deadline->deadline_date->toDateString(),
            ],
        ]);
    }

    /**
     * Create a new critical issue alert.
     */
    public static function createCriticalIssueAlert(
        AccessibilityAudit $audit,
        AuditCheck $check
    ): self {
        return self::create([
            'project_id' => $audit->project_id,
            'accessibility_audit_id' => $audit->id,
            'type' => AlertType::NewCriticalIssue,
            'title' => 'New Critical Accessibility Issue',
            'message' => sprintf(
                'Critical issue found: %s (%s)',
                $check->message,
                $check->criterion_id
            ),
            'data' => [
                'check_id' => $check->id,
                'criterion_id' => $check->criterion_id,
                'criterion_name' => $check->criterion_name,
                'impact' => $check->impact->value,
            ],
        ]);
    }
}
