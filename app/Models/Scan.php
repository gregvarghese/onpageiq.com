<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Scan extends Model
{
    /** @use HasFactory<\Database\Factories\ScanFactory> */
    use HasFactory;

    protected $fillable = [
        'url_id',
        'triggered_by_user_id',
        'scan_type',
        'status',
        'credits_charged',
        'started_at',
        'completed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'credits_charged' => 'integer',
        ];
    }

    /**
     * Get the URL being scanned.
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the user who triggered the scan.
     */
    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    /**
     * Get the scan result.
     */
    public function result(): HasOne
    {
        return $this->hasOne(ScanResult::class);
    }

    /**
     * Get the discovered URLs from this scan.
     */
    public function discoveredUrls(): HasMany
    {
        return $this->hasMany(DiscoveredUrl::class);
    }

    /**
     * Get the screenshots captured during this scan.
     */
    public function screenshots(): HasMany
    {
        return $this->hasMany(PageScreenshot::class);
    }

    /**
     * Get the broken links found during this scan.
     */
    public function brokenLinks(): HasMany
    {
        return $this->hasMany(BrokenLink::class);
    }

    /**
     * Get the duplicate content detected in this scan.
     */
    public function duplicateContents(): HasMany
    {
        return $this->hasMany(DuplicateContent::class);
    }

    /**
     * Get the page metrics collected during this scan.
     */
    public function pageMetrics(): HasMany
    {
        return $this->hasMany(PageMetrics::class);
    }

    /**
     * Get the schema validations from this scan.
     */
    public function schemaValidations(): HasMany
    {
        return $this->hasMany(SchemaValidation::class);
    }

    /**
     * Check if this is a deep scan (uses GPT-4o).
     */
    public function isDeepScan(): bool
    {
        return $this->scan_type === 'deep';
    }

    /**
     * Check if this is a quick scan (uses GPT-4o-mini).
     */
    public function isQuickScan(): bool
    {
        return $this->scan_type === 'quick';
    }

    /**
     * Check if the scan is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the scan is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if the scan is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if the scan failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark the scan as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the scan as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark the scan as failed.
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get the credit multiplier based on scan type.
     */
    public function getCreditMultiplier(): int
    {
        return $this->isDeepScan() ? 3 : 1;
    }
}
