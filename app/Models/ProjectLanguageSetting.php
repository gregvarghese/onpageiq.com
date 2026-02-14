<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLanguageSetting extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectLanguageSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'primary_language',
        'regional_variant',
        'target_reading_level',
        'thin_content_threshold',
        'stale_content_months',
    ];

    protected function casts(): array
    {
        return [
            'target_reading_level' => 'integer',
            'thin_content_threshold' => 'integer',
            'stale_content_months' => 'integer',
        ];
    }

    /**
     * Get the project these settings belong to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the full language label.
     */
    public function getFullLanguageLabel(): string
    {
        if ($this->regional_variant) {
            return "{$this->regional_variant} {$this->primary_language}";
        }

        return $this->primary_language;
    }
}
