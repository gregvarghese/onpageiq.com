<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ScanTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'name',
        'scan_type',
        'check_config',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'check_config' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the organization this template belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created this template.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope to find the default template for an organization.
     */
    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    /**
     * Set this template as the default for the organization.
     */
    public function setAsDefault(): void
    {
        // Unset any existing default
        static::where('organization_id', $this->organization_id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    /**
     * Get the enabled checks from the config.
     *
     * @return array<string>
     */
    public function getEnabledChecks(): array
    {
        if (empty($this->check_config)) {
            return [];
        }

        return array_keys(array_filter($this->check_config));
    }

    /**
     * Check if a specific check is enabled.
     */
    public function hasCheckEnabled(string $check): bool
    {
        return in_array($check, $this->getEnabledChecks());
    }

    /**
     * Get the scan type label.
     */
    public function getScanTypeLabel(): string
    {
        return match ($this->scan_type) {
            'quick' => 'Quick Scan',
            'deep' => 'Deep Scan',
            default => ucfirst($this->scan_type),
        };
    }
}
