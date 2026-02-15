<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureSnapshot;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class ArchitectureComparisonService
{
    /**
     * Compare two snapshots and return detailed diff.
     */
    public function compare(ArchitectureSnapshot $base, ArchitectureSnapshot $target): array
    {
        $baseNodes = collect($base->getNodes())->keyBy('id');
        $targetNodes = collect($target->getNodes())->keyBy('id');

        $baseLinks = collect($base->getLinks())->keyBy(fn ($l) => $l['source'].'->'.$l['target']);
        $targetLinks = collect($target->getLinks())->keyBy(fn ($l) => $l['source'].'->'.$l['target']);

        return [
            'nodes' => $this->compareNodes($baseNodes, $targetNodes),
            'links' => $this->compareLinks($baseLinks, $targetLinks),
            'metrics' => $this->compareMetrics($base, $target),
            'summary' => $this->generateSummary($baseNodes, $targetNodes, $baseLinks, $targetLinks),
        ];
    }

    /**
     * Compare nodes between two snapshots.
     */
    protected function compareNodes(Collection $base, Collection $target): array
    {
        $baseIds = $base->keys()->toArray();
        $targetIds = $target->keys()->toArray();

        $addedIds = array_diff($targetIds, $baseIds);
        $removedIds = array_diff($baseIds, $targetIds);
        $commonIds = array_intersect($baseIds, $targetIds);

        $changed = [];
        foreach ($commonIds as $id) {
            $baseNode = $base[$id];
            $targetNode = $target[$id];
            $changes = $this->detectNodeChanges($baseNode, $targetNode);

            if (! empty($changes)) {
                $changed[] = [
                    'id' => $id,
                    'url' => $targetNode['url'] ?? $baseNode['url'],
                    'changes' => $changes,
                    'base' => $baseNode,
                    'target' => $targetNode,
                ];
            }
        }

        return [
            'added' => collect($addedIds)->map(fn ($id) => $target[$id])->values()->toArray(),
            'removed' => collect($removedIds)->map(fn ($id) => $base[$id])->values()->toArray(),
            'changed' => $changed,
            'unchanged_count' => count($commonIds) - count($changed),
        ];
    }

    /**
     * Detect changes between two node versions.
     */
    protected function detectNodeChanges(array $base, array $target): array
    {
        $changes = [];
        $trackFields = ['title', 'status', 'http_status', 'depth', 'is_orphan', 'is_deep', 'link_equity_score'];

        foreach ($trackFields as $field) {
            $baseValue = $base[$field] ?? null;
            $targetValue = $target[$field] ?? null;

            if ($baseValue !== $targetValue) {
                $changes[$field] = [
                    'from' => $baseValue,
                    'to' => $targetValue,
                ];
            }
        }

        return $changes;
    }

    /**
     * Compare links between two snapshots.
     */
    protected function compareLinks(Collection $base, Collection $target): array
    {
        $baseKeys = $base->keys()->toArray();
        $targetKeys = $target->keys()->toArray();

        $addedKeys = array_diff($targetKeys, $baseKeys);
        $removedKeys = array_diff($baseKeys, $targetKeys);

        return [
            'added' => collect($addedKeys)->map(fn ($key) => $target[$key])->values()->toArray(),
            'removed' => collect($removedKeys)->map(fn ($key) => $base[$key])->values()->toArray(),
            'added_count' => count($addedKeys),
            'removed_count' => count($removedKeys),
        ];
    }

    /**
     * Compare metadata/metrics between snapshots.
     */
    protected function compareMetrics(ArchitectureSnapshot $base, ArchitectureSnapshot $target): array
    {
        $baseMetadata = $base->getMetadata();
        $targetMetadata = $target->getMetadata();

        $metrics = [];
        $trackMetrics = ['total_nodes', 'total_links', 'max_depth', 'orphan_count', 'error_count'];

        foreach ($trackMetrics as $metric) {
            $baseValue = $baseMetadata[$metric] ?? 0;
            $targetValue = $targetMetadata[$metric] ?? 0;
            $diff = $targetValue - $baseValue;

            $metrics[$metric] = [
                'base' => $baseValue,
                'target' => $targetValue,
                'diff' => $diff,
                'percent_change' => $baseValue > 0 ? round(($diff / $baseValue) * 100, 1) : null,
            ];
        }

        return $metrics;
    }

    /**
     * Generate summary of changes.
     */
    protected function generateSummary(Collection $baseNodes, Collection $targetNodes, Collection $baseLinks, Collection $targetLinks): array
    {
        $addedNodes = count(array_diff($targetNodes->keys()->toArray(), $baseNodes->keys()->toArray()));
        $removedNodes = count(array_diff($baseNodes->keys()->toArray(), $targetNodes->keys()->toArray()));
        $addedLinks = count(array_diff($targetLinks->keys()->toArray(), $baseLinks->keys()->toArray()));
        $removedLinks = count(array_diff($baseLinks->keys()->toArray(), $targetLinks->keys()->toArray()));

        $totalChanges = $addedNodes + $removedNodes + $addedLinks + $removedLinks;

        return [
            'total_changes' => $totalChanges,
            'nodes_added' => $addedNodes,
            'nodes_removed' => $removedNodes,
            'links_added' => $addedLinks,
            'links_removed' => $removedLinks,
            'has_significant_changes' => $totalChanges > 10 || ($addedNodes + $removedNodes) > 5,
            'change_type' => $this->categorizeChangeType($addedNodes, $removedNodes, $addedLinks, $removedLinks),
        ];
    }

    /**
     * Categorize the type of change.
     */
    protected function categorizeChangeType(int $addedNodes, int $removedNodes, int $addedLinks, int $removedLinks): string
    {
        if ($addedNodes === 0 && $removedNodes === 0 && $addedLinks === 0 && $removedLinks === 0) {
            return 'no_change';
        }

        if ($addedNodes > 0 && $removedNodes === 0) {
            return 'expansion';
        }

        if ($removedNodes > 0 && $addedNodes === 0) {
            return 'contraction';
        }

        if ($addedNodes > $removedNodes) {
            return 'net_growth';
        }

        if ($removedNodes > $addedNodes) {
            return 'net_shrinkage';
        }

        return 'restructuring';
    }

    /**
     * Get comparison timeline for an architecture.
     */
    public function getTimeline(SiteArchitecture $architecture): array
    {
        $snapshots = $architecture->snapshots()
            ->orderBy('created_at', 'desc')
            ->get();

        if ($snapshots->isEmpty()) {
            return [];
        }

        $timeline = [];
        $previousSnapshot = null;

        foreach ($snapshots->reverse() as $snapshot) {
            $entry = [
                'id' => $snapshot->id,
                'created_at' => $snapshot->created_at->toIso8601String(),
                'nodes_count' => $snapshot->nodes_count,
                'links_count' => $snapshot->links_count,
                'changes' => null,
            ];

            if ($previousSnapshot) {
                $comparison = $this->compare($previousSnapshot, $snapshot);
                $entry['changes'] = $comparison['summary'];
            }

            $timeline[] = $entry;
            $previousSnapshot = $snapshot;
        }

        return array_reverse($timeline);
    }

    /**
     * Find snapshots that match specific criteria.
     */
    public function findSnapshotsWithChanges(SiteArchitecture $architecture, string $changeType): Collection
    {
        $snapshots = $architecture->snapshots()
            ->orderBy('created_at', 'desc')
            ->get();

        $matching = collect();
        $previousSnapshot = null;

        foreach ($snapshots->reverse() as $snapshot) {
            if ($previousSnapshot) {
                $comparison = $this->compare($previousSnapshot, $snapshot);
                if ($comparison['summary']['change_type'] === $changeType) {
                    $matching->push([
                        'snapshot' => $snapshot,
                        'comparison' => $comparison,
                    ]);
                }
            }
            $previousSnapshot = $snapshot;
        }

        return $matching;
    }

    /**
     * Get pages that have changed between snapshots.
     */
    public function getChangedPages(ArchitectureSnapshot $base, ArchitectureSnapshot $target): array
    {
        $comparison = $this->compare($base, $target);
        $pages = [];

        // Added pages
        foreach ($comparison['nodes']['added'] as $node) {
            $pages[] = [
                'url' => $node['url'] ?? '',
                'title' => $node['title'] ?? '',
                'change_type' => 'added',
                'details' => 'New page discovered',
            ];
        }

        // Removed pages
        foreach ($comparison['nodes']['removed'] as $node) {
            $pages[] = [
                'url' => $node['url'] ?? '',
                'title' => $node['title'] ?? '',
                'change_type' => 'removed',
                'details' => 'Page no longer found',
            ];
        }

        // Changed pages
        foreach ($comparison['nodes']['changed'] as $change) {
            $details = [];
            foreach ($change['changes'] as $field => $values) {
                $details[] = ucfirst(str_replace('_', ' ', $field)).": {$values['from']} → {$values['to']}";
            }

            $pages[] = [
                'url' => $change['url'],
                'title' => $change['target']['title'] ?? '',
                'change_type' => 'modified',
                'details' => implode(', ', $details),
            ];
        }

        return $pages;
    }

    /**
     * Apply snapshot retention policy.
     */
    public function applyRetentionPolicy(SiteArchitecture $architecture, array $policy): int
    {
        $deletedCount = 0;

        // Keep at least the specified minimum
        $minSnapshots = $policy['min_snapshots'] ?? 5;
        $maxAge = $policy['max_age_days'] ?? 90;
        $maxSnapshots = $policy['max_snapshots'] ?? 100;

        $snapshots = $architecture->snapshots()
            ->orderBy('created_at', 'desc')
            ->get();

        $total = $snapshots->count();

        if ($total <= $minSnapshots) {
            return 0;
        }

        // Delete snapshots exceeding max count (keep newest)
        if ($total > $maxSnapshots) {
            $toDelete = $snapshots->slice($maxSnapshots);
            foreach ($toDelete as $snapshot) {
                $snapshot->delete();
                $deletedCount++;
            }
            $snapshots = $snapshots->take($maxSnapshots);
        }

        // Delete snapshots older than max age (but keep minimum)
        $cutoffDate = now()->subDays($maxAge);
        $remaining = $snapshots->count();

        foreach ($snapshots as $snapshot) {
            if ($remaining <= $minSnapshots) {
                break;
            }

            if ($snapshot->created_at < $cutoffDate) {
                $snapshot->delete();
                $deletedCount++;
                $remaining--;
            }
        }

        return $deletedCount;
    }

    /**
     * Create a comparison report.
     */
    public function generateReport(ArchitectureSnapshot $base, ArchitectureSnapshot $target): array
    {
        $comparison = $this->compare($base, $target);
        $changedPages = $this->getChangedPages($base, $target);

        return [
            'base_snapshot' => [
                'id' => $base->id,
                'created_at' => $base->created_at->toIso8601String(),
                'nodes_count' => $base->nodes_count,
                'links_count' => $base->links_count,
            ],
            'target_snapshot' => [
                'id' => $target->id,
                'created_at' => $target->created_at->toIso8601String(),
                'nodes_count' => $target->nodes_count,
                'links_count' => $target->links_count,
            ],
            'time_between' => $base->created_at->diffForHumans($target->created_at, true),
            'summary' => $comparison['summary'],
            'metrics' => $comparison['metrics'],
            'changed_pages' => $changedPages,
            'highlights' => $this->generateHighlights($comparison, $changedPages),
        ];
    }

    /**
     * Generate highlights from comparison.
     */
    protected function generateHighlights(array $comparison, array $changedPages): array
    {
        $highlights = [];

        // Check for significant node changes
        $nodesAdded = $comparison['summary']['nodes_added'];
        $nodesRemoved = $comparison['summary']['nodes_removed'];

        if ($nodesAdded > 10) {
            $highlights[] = [
                'type' => 'info',
                'message' => "Significant expansion: {$nodesAdded} new pages discovered",
            ];
        }

        if ($nodesRemoved > 5) {
            $highlights[] = [
                'type' => 'warning',
                'message' => "Content reduction: {$nodesRemoved} pages no longer accessible",
            ];
        }

        // Check for orphan changes
        $orphanChanges = collect($changedPages)
            ->filter(fn ($p) => str_contains($p['details'], 'is_orphan'))
            ->count();

        if ($orphanChanges > 0) {
            $highlights[] = [
                'type' => 'attention',
                'message' => "{$orphanChanges} pages changed orphan status",
            ];
        }

        // Check for depth changes
        $depthChanges = collect($changedPages)
            ->filter(fn ($p) => str_contains($p['details'], 'depth'))
            ->count();

        if ($depthChanges > 3) {
            $highlights[] = [
                'type' => 'info',
                'message' => "Site structure changed: {$depthChanges} pages at different depths",
            ];
        }

        return $highlights;
    }
}
