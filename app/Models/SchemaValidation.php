<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemaValidation extends Model
{
    /** @use HasFactory<\Database\Factories\SchemaValidationFactory> */
    use HasFactory;

    protected $fillable = [
        'url_id',
        'scan_id',
        'schema_type',
        'schema_data',
        'is_valid',
        'validation_errors',
        'rich_results_eligible',
    ];

    protected function casts(): array
    {
        return [
            'schema_data' => 'array',
            'validation_errors' => 'array',
            'is_valid' => 'boolean',
            'rich_results_eligible' => 'boolean',
        ];
    }

    /**
     * Get the URL this schema validation belongs to.
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the scan this validation was performed during.
     */
    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    /**
     * Check if this schema has validation errors.
     */
    public function hasErrors(): bool
    {
        return ! $this->is_valid;
    }
}
