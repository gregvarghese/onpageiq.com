<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscoveredUrl extends Model
{
    /** @use HasFactory<\Database\Factories\DiscoveredUrlFactory> */
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'project_id',
        'url',
        'found_on_url',
        'source_url',
        'link_text',
        'status',
        'discovered_at',
        'approved_at',
        'approved_by_user_id',
        'reviewed_at',
        'reviewed_by_user_id',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'discovered_at' => 'datetime',
            'approved_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the scan where this URL was discovered.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Get the project this URL was discovered in.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who approved this URL.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    /**
     * Check if this URL is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if this URL is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get the user who reviewed this URL.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    /**
     * Check if this URL is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Mark this URL as approved and add to project.
     */
    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by_user_id' => $user->id,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $user->id,
        ]);

        // Add URL to project
        $this->project->urls()->firstOrCreate([
            'url' => $this->url,
        ], [
            'status' => 'pending',
            'discovered_from_url_id' => $this->id,
        ]);
    }

    /**
     * Mark this URL as rejected.
     */
    public function reject(User $user, ?string $reason = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $user->id,
            'rejection_reason' => $reason,
        ]);
    }
}
