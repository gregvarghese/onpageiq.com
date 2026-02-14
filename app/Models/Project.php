<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'created_by_user_id',
        'name',
        'description',
        'language',
        'check_config',
    ];

    protected function casts(): array
    {
        return [
            'check_config' => 'array',
        ];
    }

    /**
     * Get the organization that owns the project.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the user who created the project.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get all URLs in this project.
     */
    public function urls(): HasMany
    {
        return $this->hasMany(Url::class);
    }

    /**
     * Get the dictionary words specific to this project.
     */
    public function dictionaryWords(): HasMany
    {
        return $this->hasMany(DictionaryWord::class);
    }

    /**
     * Get the industry dictionaries enabled for this project.
     */
    public function industryDictionaries(): BelongsToMany
    {
        return $this->belongsToMany(IndustryDictionary::class, 'project_industry_dictionary')
            ->withTimestamps();
    }

    /**
     * Get the enabled checks for this project.
     *
     * @return array<string>
     */
    public function getEnabledChecks(): array
    {
        if (! empty($this->check_config)) {
            return array_keys(array_filter($this->check_config));
        }

        return $this->organization->getDefaultChecks();
    }

    /**
     * Check if a specific check type is enabled.
     */
    public function hasCheckEnabled(string $check): bool
    {
        return in_array($check, $this->getEnabledChecks());
    }

    /**
     * Check if the project can add more dictionary words.
     */
    public function canAddDictionaryWord(): bool
    {
        if (! $this->organization->canUseProjectDictionary()) {
            return false;
        }

        $limit = $this->organization->getProjectDictionaryWordLimit();

        if ($limit === null) {
            return true; // Unlimited
        }

        return $this->dictionaryWords()->count() < $limit;
    }

    /**
     * Get the remaining dictionary word slots for this project.
     */
    public function getRemainingDictionarySlots(): ?int
    {
        $limit = $this->organization->getProjectDictionaryWordLimit();

        if ($limit === null) {
            return null; // Unlimited
        }

        return max(0, $limit - $this->dictionaryWords()->count());
    }

    /**
     * Check if the project can enable more industry dictionaries.
     */
    public function canEnableMoreIndustryDictionaries(): bool
    {
        if (! $this->organization->canUseIndustryDictionaries()) {
            return false;
        }

        $limit = $this->organization->getIndustryDictionaryLimit();

        if ($limit === null) {
            return true; // Unlimited
        }

        return $this->industryDictionaries()->count() < $limit;
    }

    /**
     * Get the count of enabled industry dictionaries.
     */
    public function getEnabledIndustryDictionaryCount(): int
    {
        return $this->industryDictionaries()->count();
    }
}
