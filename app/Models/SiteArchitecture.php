<?php

namespace App\Models;

use App\Enums\ArchitectureStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class SiteArchitecture extends Model
{
    use HasFactory, HasUuids;

    protected static function booted(): void
    {
        static::updated(function (SiteArchitecture $architecture) {
            // Clear graph data cache when architecture is updated
            Cache::forget("architecture_graph_data_{$architecture->id}");
            Cache::forget("architecture_graph_data_externals_{$architecture->id}");
        });
    }

    protected $fillable = [
        'project_id',
        'status',
        'total_nodes',
        'total_links',
        'max_depth',
        'orphan_count',
        'error_count',
        'crawl_config',
        'last_crawled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArchitectureStatus::class,
            'total_nodes' => 'integer',
            'total_links' => 'integer',
            'max_depth' => 'integer',
            'orphan_count' => 'integer',
            'error_count' => 'integer',
            'crawl_config' => 'array',
            'last_crawled_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(ArchitectureNode::class);
    }

    public function links(): HasMany
    {
        return $this->hasMany(ArchitectureLink::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ArchitectureSnapshot::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(ArchitectureIssue::class);
    }

    public function latestSnapshot(): ?ArchitectureSnapshot
    {
        return $this->snapshots()->latest('created_at')->first();
    }

    public function isProcessing(): bool
    {
        return $this->status->isProcessing();
    }

    public function isReady(): bool
    {
        return $this->status === ArchitectureStatus::Ready;
    }

    public function markAsCrawling(): void
    {
        $this->update(['status' => ArchitectureStatus::Crawling]);
    }

    public function markAsAnalyzing(): void
    {
        $this->update(['status' => ArchitectureStatus::Analyzing]);
    }

    public function markAsReady(): void
    {
        $this->update([
            'status' => ArchitectureStatus::Ready,
            'last_crawled_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => ArchitectureStatus::Failed]);
    }

    public function updateStats(): void
    {
        $this->update([
            'total_nodes' => $this->nodes()->count(),
            'total_links' => $this->links()->count(),
            'max_depth' => $this->nodes()->max('depth') ?? 0,
            'orphan_count' => $this->nodes()->where('is_orphan', true)->count(),
            'error_count' => $this->nodes()->whereIn('status', ['client_error', 'server_error', 'timeout'])->count(),
        ]);
    }

    public function getHomepageNode(): ?ArchitectureNode
    {
        return $this->nodes()->where('depth', 0)->first();
    }

    public function getOrphanNodes(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->nodes()->where('is_orphan', true)->get();
    }

    public function getDeepNodes(int $threshold = 4): \Illuminate\Database\Eloquent\Collection
    {
        return $this->nodes()->where('depth', '>', $threshold)->get();
    }

    public function getErrorNodes(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->nodes()->whereIn('status', ['client_error', 'server_error', 'timeout'])->get();
    }
}
