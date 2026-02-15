<?php

namespace App\Models;

use App\Enums\DeadlineType;
use App\Enums\WcagLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplianceDeadline extends Model
{
    /** @use HasFactory<\Database\Factories\ComplianceDeadlineFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'created_by_user_id',
        'type',
        'title',
        'description',
        'deadline_date',
        'wcag_level_target',
        'score_target',
        'reminder_days',
        'notified_days',
        'is_active',
        'is_met',
        'met_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => DeadlineType::class,
            'wcag_level_target' => WcagLevel::class,
            'deadline_date' => 'date',
            'score_target' => 'decimal:2',
            'reminder_days' => 'array',
            'notified_days' => 'array',
            'is_active' => 'boolean',
            'is_met' => 'boolean',
            'met_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the project this deadline belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this deadline.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get alerts related to this deadline.
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(AccessibilityAlert::class);
    }

    /**
     * Get days until deadline.
     */
    public function getDaysUntilDeadline(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->deadline_date, false);
    }

    /**
     * Check if deadline is overdue.
     */
    public function isOverdue(): bool
    {
        return ! $this->is_met && $this->deadline_date->isPast();
    }

    /**
     * Check if deadline is approaching (within reminder window).
     */
    public function isApproaching(): bool
    {
        if ($this->is_met || $this->deadline_date->isPast()) {
            return false;
        }

        $daysUntil = $this->getDaysUntilDeadline();
        $reminderDays = $this->reminder_days ?? $this->type->defaultReminderDays();

        return in_array($daysUntil, $reminderDays);
    }

    /**
     * Check if we should send a reminder for this day.
     */
    public function shouldSendReminder(): bool
    {
        if ($this->is_met || ! $this->is_active) {
            return false;
        }

        $daysUntil = $this->getDaysUntilDeadline();
        $reminderDays = $this->reminder_days ?? $this->type->defaultReminderDays();
        $notifiedDays = $this->notified_days ?? [];

        return in_array($daysUntil, $reminderDays) && ! in_array($daysUntil, $notifiedDays);
    }

    /**
     * Mark a reminder day as notified.
     */
    public function markReminderSent(int $daysUntil): void
    {
        $notifiedDays = $this->notified_days ?? [];
        $notifiedDays[] = $daysUntil;
        $this->update(['notified_days' => array_unique($notifiedDays)]);
    }

    /**
     * Mark deadline as met.
     */
    public function markAsMet(): void
    {
        $this->update([
            'is_met' => true,
            'met_at' => now(),
        ]);
    }

    /**
     * Check if a score meets the target.
     */
    public function meetsScoreTarget(float $score): bool
    {
        if (! $this->score_target) {
            return true;
        }

        return $score >= $this->score_target;
    }

    /**
     * Scope to get active deadlines.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get upcoming deadlines.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('deadline_date', '>=', now()->startOfDay())
            ->where('is_met', false);
    }

    /**
     * Scope to get overdue deadlines.
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline_date', '<', now()->startOfDay())
            ->where('is_met', false);
    }

    /**
     * Scope to get deadlines needing reminders.
     */
    public function scopeNeedingReminder($query)
    {
        return $query->active()
            ->upcoming()
            ->get()
            ->filter(fn ($deadline) => $deadline->shouldSendReminder());
    }
}
