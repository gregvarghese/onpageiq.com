<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssueAssignment extends Model
{
    /** @use HasFactory<\Database\Factories\IssueAssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'issue_id',
        'assigned_by_user_id',
        'assigned_to_user_id',
        'due_date',
        'status',
        'resolution_note',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Get the issue this assignment is for.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class);
    }

    /**
     * Get the user who created this assignment.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Get the user this issue is assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Check if the assignment is open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Check if the assignment is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    /**
     * Check if the assignment is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if the assignment is dismissed.
     */
    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    /**
     * Check if the assignment is overdue.
     */
    public function isOverdue(): bool
    {
        if ($this->due_date === null) {
            return false;
        }

        if ($this->isResolved() || $this->isDismissed()) {
            return false;
        }

        return $this->due_date->isPast();
    }

    /**
     * Mark the assignment as resolved.
     */
    public function markAsResolved(?string $note = null): void
    {
        $this->update([
            'status' => 'resolved',
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Mark the assignment as dismissed.
     */
    public function markAsDismissed(?string $note = null): void
    {
        $this->update([
            'status' => 'dismissed',
            'resolution_note' => $note,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'open' => 'gray',
            'in_progress' => 'blue',
            'resolved' => 'green',
            'dismissed' => 'yellow',
            default => 'gray',
        };
    }
}
