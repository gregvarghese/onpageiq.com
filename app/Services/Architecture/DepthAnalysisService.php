<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureIssue;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class DepthAnalysisService
{
    /**
     * Default threshold for considering a page "deep".
     */
    public const DEFAULT_DEEP_THRESHOLD = 4;

    /**
     * Analyze crawl depth for all nodes in the architecture.
     * Uses BFS from homepage to calculate minimum click depth.
     */
    public function analyzeDepth(SiteArchitecture $architecture): array
    {
        $homepage = $architecture->getHomepageNode();

        if (! $homepage) {
            return [
                'max_depth' => 0,
                'average_depth' => 0,
                'depth_distribution' => [],
            ];
        }

        // Initialize depths
        $depths = [];
        $visited = [];
        $queue = [['node' => $homepage, 'depth' => 0]];

        // BFS to calculate minimum depth from homepage
        while (! empty($queue)) {
            $current = array_shift($queue);
            $node = $current['node'];
            $depth = $current['depth'];

            if (isset($visited[$node->id])) {
                continue;
            }

            $visited[$node->id] = true;
            $depths[$node->id] = $depth;

            // Update node depth in database
            $node->update(['depth' => $depth]);

            // Add outbound links to queue
            $outboundNodes = $node->outboundLinks()
                ->whereHas('targetNode', function ($query) {
                    $query->where('is_external', false);
                })
                ->with('targetNode')
                ->get()
                ->pluck('targetNode')
                ->filter(fn ($n) => $n && ! isset($visited[$n->id]));

            foreach ($outboundNodes as $targetNode) {
                $queue[] = ['node' => $targetNode, 'depth' => $depth + 1];
            }
        }

        // Calculate statistics
        $depthValues = array_values($depths);
        $maxDepth = ! empty($depthValues) ? max($depthValues) : 0;
        $averageDepth = ! empty($depthValues) ? array_sum($depthValues) / count($depthValues) : 0;

        // Calculate depth distribution
        $distribution = array_count_values($depthValues);
        ksort($distribution);

        // Update architecture stats
        $architecture->update(['max_depth' => $maxDepth]);

        return [
            'max_depth' => $maxDepth,
            'average_depth' => round($averageDepth, 2),
            'depth_distribution' => $distribution,
            'total_analyzed' => count($depths),
        ];
    }

    /**
     * Detect pages that exceed the depth threshold.
     *
     * @return Collection<int, ArchitectureNode>
     */
    public function detectDeepPages(SiteArchitecture $architecture, int $threshold = self::DEFAULT_DEEP_THRESHOLD): Collection
    {
        return $architecture->nodes()
            ->where('depth', '>', $threshold)
            ->orderByDesc('depth')
            ->get();
    }

    /**
     * Mark deep pages and create issues.
     *
     * @return int Number of deep pages detected
     */
    public function markDeepPagesAndCreateIssues(
        SiteArchitecture $architecture,
        int $threshold = self::DEFAULT_DEEP_THRESHOLD
    ): int {
        $deepPages = $this->detectDeepPages($architecture, $threshold);

        foreach ($deepPages as $deepPage) {
            // Mark the node as deep
            $deepPage->markAsDeep($threshold);

            // Create an issue if one doesn't exist
            $existingIssue = $architecture->issues()
                ->where('node_id', $deepPage->id)
                ->where('issue_type', \App\Enums\ArchitectureIssueType::DeepPage)
                ->where('is_resolved', false)
                ->first();

            if (! $existingIssue) {
                ArchitectureIssue::createDeepPageIssue($architecture, $deepPage, $threshold);
            }
        }

        return $deepPages->count();
    }

    /**
     * Get depth statistics for each path segment.
     *
     * @return Collection<string, array{avg_depth: float, max_depth: int, count: int}>
     */
    public function getDepthByPathSegment(SiteArchitecture $architecture): Collection
    {
        $nodes = $architecture->nodes()
            ->whereNotNull('depth')
            ->get();

        return $nodes->groupBy(function (ArchitectureNode $node) {
            $path = parse_url($node->url, PHP_URL_PATH) ?? '/';
            $segments = explode('/', trim($path, '/'));

            return $segments[0] ?? 'root';
        })->map(function (Collection $groupNodes) {
            $depths = $groupNodes->pluck('depth')->filter()->values();

            return [
                'avg_depth' => $depths->isNotEmpty() ? round($depths->avg(), 2) : 0,
                'max_depth' => $depths->isNotEmpty() ? $depths->max() : 0,
                'min_depth' => $depths->isNotEmpty() ? $depths->min() : 0,
                'count' => $groupNodes->count(),
            ];
        })->sortByDesc('avg_depth');
    }

    /**
     * Get the shortest path from homepage to a specific node.
     *
     * @return array<int, ArchitectureNode>|null
     */
    public function getShortestPath(SiteArchitecture $architecture, ArchitectureNode $targetNode): ?array
    {
        $homepage = $architecture->getHomepageNode();

        if (! $homepage) {
            return null;
        }

        if ($homepage->id === $targetNode->id) {
            return [$homepage];
        }

        // BFS with path tracking
        $visited = [];
        $queue = [['node' => $homepage, 'path' => [$homepage]]];

        while (! empty($queue)) {
            $current = array_shift($queue);
            $node = $current['node'];
            $path = $current['path'];

            if (isset($visited[$node->id])) {
                continue;
            }

            $visited[$node->id] = true;

            // Check outbound links
            $outboundLinks = $node->outboundLinks()
                ->with('targetNode')
                ->get();

            foreach ($outboundLinks as $link) {
                $nextNode = $link->targetNode;

                if (! $nextNode || isset($visited[$nextNode->id])) {
                    continue;
                }

                $newPath = array_merge($path, [$nextNode]);

                if ($nextNode->id === $targetNode->id) {
                    return $newPath;
                }

                $queue[] = ['node' => $nextNode, 'path' => $newPath];
            }
        }

        return null; // No path found (orphan page)
    }

    /**
     * Calculate a depth score for SEO recommendations.
     * Lower scores are better (closer to homepage).
     */
    public function calculateDepthScore(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->whereNotNull('depth')
            ->get();

        $totalNodes = $nodes->count();
        if ($totalNodes === 0) {
            return [
                'score' => 100,
                'grade' => 'A',
                'message' => 'No pages to analyze',
            ];
        }

        // Calculate weighted score based on depth distribution
        $depthWeights = [
            0 => 100, // Homepage - perfect
            1 => 100, // 1 click - excellent
            2 => 90,  // 2 clicks - very good
            3 => 70,  // 3 clicks - good
            4 => 50,  // 4 clicks - acceptable
            5 => 30,  // 5 clicks - poor
        ];

        $totalScore = 0;
        foreach ($nodes as $node) {
            $depth = $node->depth ?? 0;
            $weight = $depthWeights[$depth] ?? max(0, 30 - (($depth - 5) * 10));
            $totalScore += $weight;
        }

        $averageScore = $totalScore / $totalNodes;

        // Determine grade
        $grade = match (true) {
            $averageScore >= 90 => 'A',
            $averageScore >= 80 => 'B',
            $averageScore >= 70 => 'C',
            $averageScore >= 50 => 'D',
            default => 'F',
        };

        return [
            'score' => round($averageScore, 1),
            'grade' => $grade,
            'message' => $this->getScoreMessage($grade),
        ];
    }

    /**
     * Get a message for the depth score grade.
     */
    protected function getScoreMessage(string $grade): string
    {
        return match ($grade) {
            'A' => 'Excellent site structure. Most pages are within 3 clicks of homepage.',
            'B' => 'Good site structure. Consider improving internal linking for deeper pages.',
            'C' => 'Average site structure. Some pages are too deep in the site hierarchy.',
            'D' => 'Poor site structure. Many pages require too many clicks to reach.',
            'F' => 'Critical issues with site structure. Major restructuring recommended.',
        };
    }
}
