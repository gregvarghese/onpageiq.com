<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchitectureSnapshot extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'site_architecture_id',
        'snapshot_data',
        'nodes_count',
        'links_count',
        'changes_summary',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_data' => 'array',
            'nodes_count' => 'integer',
            'links_count' => 'integer',
            'changes_summary' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function siteArchitecture(): BelongsTo
    {
        return $this->belongsTo(SiteArchitecture::class);
    }

    public function getNodes(): array
    {
        return $this->snapshot_data['nodes'] ?? [];
    }

    public function getLinks(): array
    {
        return $this->snapshot_data['links'] ?? [];
    }

    public function getMetadata(): array
    {
        return $this->snapshot_data['metadata'] ?? [];
    }

    public function hasChangesSummary(): bool
    {
        return ! empty($this->changes_summary);
    }

    public function getAddedCount(): int
    {
        return $this->changes_summary['added'] ?? 0;
    }

    public function getRemovedCount(): int
    {
        return $this->changes_summary['removed'] ?? 0;
    }

    public function getChangedCount(): int
    {
        return $this->changes_summary['changed'] ?? 0;
    }

    public static function createFromArchitecture(SiteArchitecture $architecture, ?self $previousSnapshot = null): self
    {
        $nodes = $architecture->nodes->map->toGraphNode()->toArray();
        $links = $architecture->links->map->toGraphEdge()->toArray();

        $changesSummary = null;
        if ($previousSnapshot) {
            $changesSummary = self::calculateChanges($previousSnapshot, $nodes, $links);
        }

        return self::create([
            'site_architecture_id' => $architecture->id,
            'snapshot_data' => [
                'nodes' => $nodes,
                'links' => $links,
                'metadata' => [
                    'total_nodes' => $architecture->total_nodes,
                    'total_links' => $architecture->total_links,
                    'max_depth' => $architecture->max_depth,
                    'orphan_count' => $architecture->orphan_count,
                    'error_count' => $architecture->error_count,
                ],
            ],
            'nodes_count' => count($nodes),
            'links_count' => count($links),
            'changes_summary' => $changesSummary,
            'created_at' => now(),
        ]);
    }

    protected static function calculateChanges(self $previous, array $currentNodes, array $currentLinks): array
    {
        $previousNodeIds = collect($previous->getNodes())->pluck('id')->toArray();
        $currentNodeIds = collect($currentNodes)->pluck('id')->toArray();

        $added = count(array_diff($currentNodeIds, $previousNodeIds));
        $removed = count(array_diff($previousNodeIds, $currentNodeIds));

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => 0, // Could implement detailed change detection
            'previous_nodes' => count($previousNodeIds),
            'current_nodes' => count($currentNodeIds),
        ];
    }

    public function compareWith(self $other): array
    {
        $thisNodes = collect($this->getNodes())->keyBy('id');
        $otherNodes = collect($other->getNodes())->keyBy('id');

        $thisIds = $thisNodes->keys()->toArray();
        $otherIds = $otherNodes->keys()->toArray();

        $added = array_diff($thisIds, $otherIds);
        $removed = array_diff($otherIds, $thisIds);
        $common = array_intersect($thisIds, $otherIds);

        return [
            'added' => collect($added)->map(fn ($id) => $thisNodes[$id])->values()->toArray(),
            'removed' => collect($removed)->map(fn ($id) => $otherNodes[$id])->values()->toArray(),
            'unchanged' => count($common),
            'summary' => [
                'added_count' => count($added),
                'removed_count' => count($removed),
                'common_count' => count($common),
            ],
        ];
    }
}
