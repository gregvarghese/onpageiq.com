<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageScreenshot extends Model
{
    /** @use HasFactory<\Database\Factories\PageScreenshotFactory> */
    use HasFactory;

    protected $fillable = [
        'url_id',
        'scan_id',
        'viewport',
        'file_path',
        'file_size',
        'width',
        'height',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Get the URL this screenshot is for.
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the scan this screenshot was taken during.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Check if this is a desktop screenshot.
     */
    public function isDesktop(): bool
    {
        return $this->viewport === 'desktop';
    }

    /**
     * Check if this is a mobile screenshot.
     */
    public function isMobile(): bool
    {
        return $this->viewport === 'mobile';
    }
}
