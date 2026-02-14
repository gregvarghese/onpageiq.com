<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UrlGroup extends Model
{
    /** @use HasFactory<\Database\Factories\UrlGroupFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'sort_order',
    ];

    /**
     * Get the project this group belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the URLs in this group.
     */
    public function urls(): HasMany
    {
        return $this->hasMany(Url::class);
    }

    /**
     * Get the scan schedules for this group.
     */
    public function scanSchedules(): HasMany
    {
        return $this->hasMany(ScanSchedule::class);
    }

    /**
     * Get the URL count for this group.
     */
    public function getUrlCount(): int
    {
        return $this->urls()->count();
    }
}
