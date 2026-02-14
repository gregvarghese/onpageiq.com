<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageMetrics extends Model
{
    /** @use HasFactory<\Database\Factories\PageMetricsFactory> */
    use HasFactory;

    protected $fillable = [
        'url_id',
        'scan_id',
        'lcp_score',
        'fid_score',
        'cls_score',
        'load_time',
        'page_size',
        'request_count',
        'word_count',
        'flesch_kincaid_grade',
        'flesch_reading_ease',
    ];

    protected function casts(): array
    {
        return [
            'lcp_score' => 'float',
            'fid_score' => 'float',
            'cls_score' => 'float',
            'load_time' => 'integer',
            'page_size' => 'integer',
            'request_count' => 'integer',
            'word_count' => 'integer',
            'flesch_kincaid_grade' => 'float',
            'flesch_reading_ease' => 'float',
        ];
    }

    /**
     * Get the URL these metrics belong to.
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the scan these metrics were collected during.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Get the Core Web Vitals rating for LCP.
     */
    public function getLcpRating(): string
    {
        if ($this->lcp_score <= 2.5) {
            return 'good';
        }
        if ($this->lcp_score <= 4.0) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * Get the Core Web Vitals rating for FID.
     */
    public function getFidRating(): string
    {
        if ($this->fid_score <= 100) {
            return 'good';
        }
        if ($this->fid_score <= 300) {
            return 'needs_improvement';
        }

        return 'poor';
    }

    /**
     * Get the Core Web Vitals rating for CLS.
     */
    public function getClsRating(): string
    {
        if ($this->cls_score <= 0.1) {
            return 'good';
        }
        if ($this->cls_score <= 0.25) {
            return 'needs_improvement';
        }

        return 'poor';
    }
}
