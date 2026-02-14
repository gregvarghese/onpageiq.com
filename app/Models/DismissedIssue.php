<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DismissedIssue extends Model
{
    /** @use HasFactory<\Database\Factories\DismissedIssueFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'url_id',
        'dismissed_by_user_id',
        'scope',
        'category',
        'text_pattern',
        'reason',
    ];

    /**
     * Get the organization this dismissed issue belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the project this dismissed issue belongs to (if project-scoped).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the URL this dismissed issue belongs to (if URL-scoped).
     */
    public function url(): BelongsTo
    {
        return $this->belongsTo(Url::class);
    }

    /**
     * Get the user who dismissed this issue.
     */
    public function dismissedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dismissed_by_user_id');
    }

    /**
     * Scope to find dismissals that apply to a given URL.
     */
    public function scopeForUrl(Builder $query, Url $url): Builder
    {
        return $query->where(function ($q) use ($url) {
            $q->where('url_id', $url->id)
                ->orWhere(function ($q2) use ($url) {
                    $q2->where('project_id', $url->project_id)
                        ->whereNull('url_id');
                })
                ->orWhere(function ($q3) use ($url) {
                    $q3->where('organization_id', $url->project->organization_id)
                        ->whereNull('project_id')
                        ->whereNull('url_id');
                });
        });
    }

    /**
     * Scope to find dismissals by category.
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Check if this dismissal matches a given issue text.
     */
    public function matchesText(string $text): bool
    {
        if ($this->scope === 'pattern') {
            return (bool) preg_match('/'.preg_quote($this->text_pattern, '/').'/i', $text);
        }

        return strtolower($this->text_pattern) === strtolower($text);
    }

    /**
     * Check if this is a URL-scoped dismissal.
     */
    public function isUrlScoped(): bool
    {
        return $this->scope === 'url';
    }

    /**
     * Check if this is a project-scoped dismissal.
     */
    public function isProjectScoped(): bool
    {
        return $this->scope === 'project';
    }

    /**
     * Check if this is a pattern-based dismissal.
     */
    public function isPatternBased(): bool
    {
        return $this->scope === 'pattern';
    }
}
