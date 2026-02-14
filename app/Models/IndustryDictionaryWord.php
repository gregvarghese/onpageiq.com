<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryDictionaryWord extends Model
{
    protected $fillable = [
        'industry_dictionary_id',
        'word',
    ];

    /**
     * Get the industry dictionary this word belongs to.
     */
    public function industryDictionary(): BelongsTo
    {
        return $this->belongsTo(IndustryDictionary::class);
    }

    /**
     * Normalize a word for storage (lowercase, trimmed).
     */
    public static function normalizeWord(string $word): string
    {
        return mb_strtolower(trim($word));
    }

    /**
     * Set the word attribute (auto-normalize).
     */
    protected function setWordAttribute(string $value): void
    {
        $this->attributes['word'] = self::normalizeWord($value);
    }
}
