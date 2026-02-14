<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Url extends Model
{
    /** @use HasFactory<\Database\Factories\UrlFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'url_group_id',
        'url',
        'status',
        'last_scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'last_scanned_at' => 'datetime',
        ];
    }

    /**
     * Get the project this URL belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the URL group this URL belongs to.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(UrlGroup::class, 'url_group_id');
    }

    /**
     * Get the dismissed issues for this URL.
     */
    public function dismissedIssues(): HasMany
    {
        return $this->hasMany(DismissedIssue::class);
    }

    /**
     * Get the screenshots for this URL.
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(PageScreenshot::class);
    }

    /**
     * Get the broken links found on this URL.
     */
    public function brokenLinks(): HasMany
    {
        return $this->hasMany(BrokenLink::class);
    }

    /**
     * Get the duplicate content records for this URL.
     */
    public function duplicateContents(): HasMany
    {
        return $this->hasMany(DuplicateContent::class);
    }

    /**
     * Get the metrics for this URL.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(PageMetrics::class);
    }

    /**
     * Get the schema validations for this URL.
     */
    public function schemaValidations(): HasMany
    {
        return $this->hasMany(SchemaValidation::class);
    }

    /**
     * Get the latest metrics for this URL.
     */
    public function latestMetrics(): HasOne
    {
        return $this->hasOne(PageMetrics::class)->latestOfMany();
    }

    /**
     * Get all scans for this URL.
     */
    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    /**
     * Get the latest scan for this URL.
     */
    public function latestScan(): HasOne
    {
        return $this->hasOne(Scan::class)->latestOfMany();
    }

    /**
     * Get the latest completed scan for this URL.
     */
    public function latestCompletedScan(): HasOne
    {
        return $this->hasOne(Scan::class)
            ->where('status', 'completed')
            ->latestOfMany();
    }

    /**
     * Check if the URL is currently being scanned.
     */
    public function isScanning(): bool
    {
        return $this->status === 'scanning';
    }

    /**
     * Check if the URL has never been scanned.
     */
    public function isNew(): bool
    {
        return $this->status === 'pending' && $this->last_scanned_at === null;
    }

    /**
     * Mark the URL as pending scan.
     */
    public function markAsPending(): void
    {
        $this->update(['status' => 'pending']);
    }

    /**
     * Mark the URL as currently scanning.
     */
    public function markAsScanning(): void
    {
        $this->update(['status' => 'scanning']);
    }

    /**
     * Mark the URL as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'last_scanned_at' => now(),
        ]);
    }

    /**
     * Mark the URL as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }
}
