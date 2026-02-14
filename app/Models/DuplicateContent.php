<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateContent extends Model
{
    /** @use HasFactory<\Database\Factories\DuplicateContentFactory> */
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'url_id',
        'duplicate_url_id',
        'content_snippet',
        'similarity_score',
        'is_excluded',
    ];

    protected function casts(): array
    {
        return [
            'similarity_score' => 'float',
            'is_excluded' => 'boolean',
        ];
    }

    /**
     * Get the scan where this duplicate was detected.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Get the primary URL where this content appears.
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the duplicate URL where this content also appears.
     */
    public function duplicateUrl(): BelongsTo
    {
        return $this->belongsTo(Url::class, 'duplicate_url_id');
    }

    /**
     * Check if this duplicate is significant (high similarity).
     */
    public function isSignificant(): bool
    {
        return $this->similarity_score >= 0.8;
    }
}
