<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrokenLink extends Model
{
    /** @use HasFactory<\Database\Factories\BrokenLinkFactory> */
    use HasFactory;

    protected $fillable = [
        'url_id',
        'scan_id',
        'link_url',
        'link_text',
        'link_type',
        'status_code',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
        ];
    }

    /**
     * Get the URL where this broken link was found.
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the scan where this broken link was detected.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Check if this is an internal link.
     */
    public function isInternal(): bool
    {
        return $this->link_type === 'internal';
    }

    /**
     * Check if this is an external link.
     */
    public function isExternal(): bool
    {
        return $this->link_type === 'external';
    }

    /**
     * Check if this is an anchor link.
     */
    public function isAnchor(): bool
    {
        return $this->link_type === 'anchor';
    }
}
