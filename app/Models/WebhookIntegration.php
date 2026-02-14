<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookIntegration extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookIntegrationFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'project_id',
        'type',
        'name',
        'webhook_url',
        'config',
        'events',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'events' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the organization this integration belongs to.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the project this integration is scoped to (if any).
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope to find active integrations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to find integrations for a specific event.
     */
    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->whereJsonContains('events', $event);
    }

    /**
     * Scope to find integrations for a specific project.
     */
    public function scopeForProject(Builder $query, Project $project): Builder
    {
        return $query->where(function ($q) use ($project) {
            $q->where('project_id', $project->id)
                ->orWhereNull('project_id');
        })->where('organization_id', $project->organization_id);
    }

    /**
     * Check if this integration handles a specific event.
     */
    public function handlesEvent(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    /**
     * Check if this is a Slack integration.
     */
    public function isSlack(): bool
    {
        return $this->type === 'slack';
    }

    /**
     * Check if this is a Jira integration.
     */
    public function isJira(): bool
    {
        return $this->type === 'jira';
    }

    /**
     * Check if this is a GitHub integration.
     */
    public function isGitHub(): bool
    {
        return $this->type === 'github';
    }

    /**
     * Check if this is a Linear integration.
     */
    public function isLinear(): bool
    {
        return $this->type === 'linear';
    }

    /**
     * Check if this is a generic webhook.
     */
    public function isGeneric(): bool
    {
        return $this->type === 'generic';
    }

    /**
     * Get the type label for display.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'slack' => 'Slack',
            'jira' => 'Jira',
            'github' => 'GitHub',
            'linear' => 'Linear',
            'generic' => 'Webhook',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get the type icon for display.
     */
    public function getTypeIcon(): string
    {
        return match ($this->type) {
            'slack' => 'chat-bubble-left-right',
            'jira' => 'ticket',
            'github' => 'code-bracket',
            'linear' => 'squares-2x2',
            'generic' => 'globe-alt',
            default => 'link',
        };
    }

    /**
     * Get available event types for webhooks.
     *
     * @return array<string, string>
     */
    public static function getAvailableEvents(): array
    {
        return [
            'scan.completed' => 'Scan Completed',
            'scan.failed' => 'Scan Failed',
            'issues.found' => 'Issues Found',
            'issues.resolved' => 'Issues Resolved',
            'schedule.run' => 'Scheduled Scan Run',
        ];
    }
}
