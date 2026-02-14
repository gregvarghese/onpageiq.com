<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryDictionary extends Model
{
    /** @use HasFactory<\Database\Factories\IndustryDictionaryFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'word_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'word_count' => 'integer',
        ];
    }

    /**
     * Get the words in this industry dictionary.
     */
    public function words(): HasMany
    {
        return $this->hasMany(IndustryDictionaryWord::class);
    }

    /**
     * Get the projects that have enabled this industry dictionary.
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_industry_dictionary')
            ->withTimestamps();
    }

    /**
     * Get all word strings for this dictionary.
     *
     * @return array<string>
     */
    public function getWordList(): array
    {
        return $this->words()->pluck('word')->toArray();
    }

    /**
     * Update the word count based on actual words.
     */
    public function updateWordCount(): void
    {
        $this->update(['word_count' => $this->words()->count()]);
    }
}
