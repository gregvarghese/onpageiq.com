<?php

namespace App\Models;

use App\Enums\NodeStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchitectureNode extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'site_architecture_id',
        'url',
        'path',
        'title',
        'status',
        'http_status',
        'depth',
        'inbound_count',
        'outbound_count',
        'link_equity_score',
        'word_count',
        'issues_count',
        'is_orphan',
        'is_deep',
        'metadata',
        'position_x',
        'position_y',
    ];

    protected function casts(): array
    {
        return [
            'status' => NodeStatus::class,
            'http_status' => 'integer',
            'depth' => 'integer',
            'inbound_count' => 'integer',
            'outbound_count' => 'integer',
            'link_equity_score' => 'decimal:6',
            'word_count' => 'integer',
            'issues_count' => 'integer',
            'is_orphan' => 'boolean',
            'is_deep' => 'boolean',
            'metadata' => 'array',
            'position_x' => 'decimal:4',
            'position_y' => 'decimal:4',
        ];
    }

    public function siteArchitecture(): BelongsTo
    {
        return $this->belongsTo(SiteArchitecture::class);
    }

    public function outboundLinks(): HasMany
    {
        return $this->hasMany(ArchitectureLink::class, 'source_node_id');
    }

    public function inboundLinks(): HasMany
    {
        return $this->hasMany(ArchitectureLink::class, 'target_node_id');
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ArchitectureIssue::class, 'node_id');
    }

    public function isError(): bool
    {
        return $this->status->isError();
    }

    public function isWarning(): bool
    {
        return $this->status->isWarning();
    }

    public function isHealthy(): bool
    {
        return $this->status === NodeStatus::Ok && ! $this->is_orphan && ! $this->is_deep;
    }

    public function updateLinkCounts(): void
    {
        $this->update([
            'inbound_count' => $this->inboundLinks()->count(),
            'outbound_count' => $this->outboundLinks()->count(),
        ]);
    }

    public function markAsOrphan(): void
    {
        $this->update(['is_orphan' => true]);
    }

    public function markAsDeep(int $threshold = 4): void
    {
        if ($this->depth > $threshold) {
            $this->update(['is_deep' => true]);
        }
    }

    public function setPosition(float $x, float $y): void
    {
        $this->update([
            'position_x' => $x,
            'position_y' => $y,
        ]);
    }

    public function getDisplayName(): string
    {
        return $this->title ?: $this->path;
    }

    public function getShortPath(): string
    {
        $path = $this->path;
        if (strlen($path) > 50) {
            return substr($path, 0, 25).'...'.substr($path, -22);
        }

        return $path;
    }

    public function toGraphNode(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'path' => $this->path,
            'title' => $this->title,
            'status' => $this->status->value,
            'depth' => $this->depth,
            'inbound_count' => $this->inbound_count,
            'outbound_count' => $this->outbound_count,
            'link_equity_score' => (float) $this->link_equity_score,
            'is_orphan' => $this->is_orphan,
            'is_deep' => $this->is_deep,
            'x' => $this->position_x ? (float) $this->position_x : null,
            'y' => $this->position_y ? (float) $this->position_y : null,
        ];
    }
}
