<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DictionaryWord extends Model
{
    /** @use HasFactory<\Database\Factories\DictionaryWordFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'added_by_user_id',
        'word',
        'source',
        'notes',
    ];

    /**
     * Get the organization that owns this dictionary word.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the project this word belongs to (if project-specific).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who added this word.
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }

    /**
     * Scope to get organization-level words (not project-specific).
     */
    public function scopeOrganizationLevel(Builder $query): Builder
    {
        return $query->whereNull('project_id');
    }

    /**
     * Scope to get words for a specific project.
     */
    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope to get all words applicable to a project (org + project words).
     */
    public function scopeApplicableToProject(Builder $query, Project $project): Builder
    {
        return $query->where('organization_id', $project->organization_id)
            ->where(function (Builder $q) use ($project) {
                $q->whereNull('project_id')
                    ->orWhere('project_id', $project->id);
            });
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
