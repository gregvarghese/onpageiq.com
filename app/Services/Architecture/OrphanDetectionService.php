<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureIssue;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class OrphanDetectionService
{
    /**
     * Detect orphan pages in the site architecture.
     * An orphan page has no internal inbound links (except possibly from itself).
     *
     * @return Collection<int, ArchitectureNode>
     */
    public function detectOrphans(SiteArchitecture $architecture): Collection
    {
        $orphans = collect();
        $homepage = $architecture->getHomepageNode();

        $architecture->nodes()
            ->chunk(100, function ($nodes) use (&$orphans, $homepage) {
                foreach ($nodes as $node) {
                    if ($this->isOrphan($node, $homepage)) {
                        $orphans->push($node);
                    }
                }
            });

        return $orphans;
    }

    /**
     * Check if a specific node is an orphan.
     */
    public function isOrphan(ArchitectureNode $node, ?ArchitectureNode $homepage = null): bool
    {
        // Homepage is never an orphan
        if ($homepage && $node->id === $homepage->id) {
            return false;
        }

        // Check if the node has any internal inbound links from other pages
        $internalInboundCount = $node->inboundLinks()
            ->whereHas('sourceNode', function ($query) use ($node) {
                $query->where('id', '!=', $node->id); // Exclude self-links
            })
            ->count();

        return $internalInboundCount === 0;
    }

    /**
     * Mark orphan pages and create issues.
     *
     * @return int Number of orphans detected
     */
    public function markOrphansAndCreateIssues(SiteArchitecture $architecture): int
    {
        $orphans = $this->detectOrphans($architecture);

        foreach ($orphans as $orphan) {
            // Mark the node as orphan
            $orphan->markAsOrphan();

            // Create an issue if one doesn't exist
            $existingIssue = $architecture->issues()
                ->where('node_id', $orphan->id)
                ->where('issue_type', \App\Enums\ArchitectureIssueType::OrphanPage)
                ->where('is_resolved', false)
                ->first();

            if (! $existingIssue) {
                ArchitectureIssue::createOrphanIssue($orphan);
            }
        }

        // Update architecture stats
        $architecture->update([
            'orphan_count' => $orphans->count(),
        ]);

        return $orphans->count();
    }

    /**
     * Get orphan pages grouped by their URL path segments.
     *
     * @return Collection<string, Collection<int, ArchitectureNode>>
     */
    public function getOrphansGroupedByPath(SiteArchitecture $architecture): Collection
    {
        $orphans = $this->detectOrphans($architecture);

        return $orphans->groupBy(function (ArchitectureNode $node) {
            $path = parse_url($node->url, PHP_URL_PATH) ?? '/';
            $segments = explode('/', trim($path, '/'));

            // Return the first path segment as the group key
            return $segments[0] ?? 'root';
        });
    }

    /**
     * Get potential linking opportunities for orphan pages.
     * Suggests pages that could link to the orphan based on URL similarity.
     *
     * @return Collection<int, array{orphan: ArchitectureNode, suggestions: Collection}>
     */
    public function getLinkingSuggestions(SiteArchitecture $architecture, int $maxSuggestions = 5): Collection
    {
        $orphans = $this->detectOrphans($architecture);
        $suggestions = collect();

        foreach ($orphans as $orphan) {
            $orphanPath = parse_url($orphan->url, PHP_URL_PATH) ?? '/';
            $orphanSegments = explode('/', trim($orphanPath, '/'));

            // Find pages with similar paths
            $similarPages = $architecture->nodes()
                ->where('id', '!=', $orphan->id)
                ->get()
                ->map(function (ArchitectureNode $node) use ($orphanSegments) {
                    $nodePath = parse_url($node->url, PHP_URL_PATH) ?? '/';
                    $nodeSegments = explode('/', trim($nodePath, '/'));

                    // Calculate path similarity score
                    $commonSegments = array_intersect($orphanSegments, $nodeSegments);
                    $score = count($commonSegments) / max(count($orphanSegments), count($nodeSegments), 1);

                    return [
                        'node' => $node,
                        'score' => $score,
                    ];
                })
                ->filter(fn ($item) => $item['score'] > 0.2) // Minimum similarity threshold
                ->sortByDesc('score')
                ->take($maxSuggestions)
                ->pluck('node');

            $suggestions->push([
                'orphan' => $orphan,
                'suggestions' => $similarPages,
            ]);
        }

        return $suggestions;
    }

    /**
     * Calculate the orphan rate for the architecture.
     */
    public function calculateOrphanRate(SiteArchitecture $architecture): float
    {
        $totalInternalNodes = $architecture->nodes()
            ->count();

        if ($totalInternalNodes === 0) {
            return 0.0;
        }

        $orphanCount = $this->detectOrphans($architecture)->count();

        return round($orphanCount / $totalInternalNodes * 100, 2);
    }
}
