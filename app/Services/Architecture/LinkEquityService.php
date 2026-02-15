<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class LinkEquityService
{
    /**
     * Default damping factor for PageRank algorithm.
     */
    public const DEFAULT_DAMPING_FACTOR = 0.85;

    /**
     * Default number of iterations for convergence.
     */
    public const DEFAULT_ITERATIONS = 50;

    /**
     * Convergence threshold.
     */
    public const CONVERGENCE_THRESHOLD = 0.0001;

    /**
     * Calculate link equity scores for all nodes using PageRank algorithm.
     *
     * @return array<string, float> Node ID => score
     */
    public function calculateLinkEquity(
        SiteArchitecture $architecture,
        float $dampingFactor = self::DEFAULT_DAMPING_FACTOR,
        int $maxIterations = self::DEFAULT_ITERATIONS
    ): array {
        // Get all internal nodes
        $nodes = $architecture->nodes()
            ->get()
            ->keyBy('id');

        $nodeCount = $nodes->count();
        if ($nodeCount === 0) {
            return [];
        }

        // Initialize scores equally
        $initialScore = 1.0 / $nodeCount;
        $scores = [];
        foreach ($nodes as $node) {
            $scores[$node->id] = $initialScore;
        }

        // Build adjacency list for outbound links
        $outboundLinks = [];
        $inboundLinks = [];

        foreach ($nodes as $node) {
            $outboundLinks[$node->id] = $node->outboundLinks()
                ->whereHas('targetNode', function ($query) {
                    $query->where('is_external', false);
                })
                ->where('is_nofollow', false) // Respect nofollow
                ->pluck('target_node_id')
                ->toArray();

            // Build inbound links map
            foreach ($outboundLinks[$node->id] as $targetId) {
                if (! isset($inboundLinks[$targetId])) {
                    $inboundLinks[$targetId] = [];
                }
                $inboundLinks[$targetId][] = $node->id;
            }
        }

        // Iterative PageRank calculation
        $randomJump = (1 - $dampingFactor) / $nodeCount;

        for ($i = 0; $i < $maxIterations; $i++) {
            $newScores = [];
            $maxDelta = 0;

            foreach ($nodes as $node) {
                $nodeId = $node->id;
                $inbound = $inboundLinks[$nodeId] ?? [];

                $linkScore = 0;
                foreach ($inbound as $sourceId) {
                    $outboundCount = count($outboundLinks[$sourceId] ?? []);
                    if ($outboundCount > 0) {
                        $linkScore += $scores[$sourceId] / $outboundCount;
                    }
                }

                $newScores[$nodeId] = $randomJump + ($dampingFactor * $linkScore);
                $maxDelta = max($maxDelta, abs($newScores[$nodeId] - $scores[$nodeId]));
            }

            $scores = $newScores;

            // Check for convergence
            if ($maxDelta < self::CONVERGENCE_THRESHOLD) {
                break;
            }
        }

        // Normalize scores to 0-100 range
        $maxScore = max($scores) ?: 1;
        $normalizedScores = [];
        foreach ($scores as $nodeId => $score) {
            $normalizedScores[$nodeId] = round(($score / $maxScore) * 100, 2);
        }

        return $normalizedScores;
    }

    /**
     * Calculate and persist link equity scores to nodes.
     */
    public function calculateAndPersist(
        SiteArchitecture $architecture,
        float $dampingFactor = self::DEFAULT_DAMPING_FACTOR
    ): void {
        $scores = $this->calculateLinkEquity($architecture, $dampingFactor);

        foreach ($scores as $nodeId => $score) {
            ArchitectureNode::where('id', $nodeId)->update([
                'link_equity_score' => $score,
            ]);
        }
    }

    /**
     * Get nodes with low link equity that need attention.
     *
     * @return Collection<int, ArchitectureNode>
     */
    public function getLowEquityNodes(SiteArchitecture $architecture, float $threshold = 10.0): Collection
    {
        return $architecture->nodes()
            ->where('link_equity_score', '<', $threshold)
            ->whereNotNull('link_equity_score')
            ->orderBy('link_equity_score')
            ->get();
    }

    /**
     * Get nodes with high link equity (important pages).
     *
     * @return Collection<int, ArchitectureNode>
     */
    public function getHighEquityNodes(SiteArchitecture $architecture, int $limit = 10): Collection
    {
        return $architecture->nodes()
            ->whereNotNull('link_equity_score')
            ->orderByDesc('link_equity_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Analyze link equity distribution.
     */
    public function analyzeDistribution(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->whereNotNull('link_equity_score')
            ->get();

        if ($nodes->isEmpty()) {
            return [
                'min' => 0,
                'max' => 0,
                'average' => 0,
                'median' => 0,
                'distribution' => [],
            ];
        }

        $scores = $nodes->pluck('link_equity_score')->sort()->values();

        // Create distribution buckets (0-10, 10-20, ..., 90-100)
        $distribution = [];
        for ($i = 0; $i < 10; $i++) {
            $min = $i * 10;
            $max = ($i + 1) * 10;
            $label = "{$min}-{$max}";
            $distribution[$label] = $scores->filter(
                fn ($s) => $s >= $min && $s < $max
            )->count();
        }

        // Count 100 separately
        $distribution['90-100'] += $scores->filter(fn ($s) => $s == 100)->count();

        return [
            'min' => round($scores->min(), 2),
            'max' => round($scores->max(), 2),
            'average' => round($scores->avg(), 2),
            'median' => round($scores->median(), 2),
            'distribution' => $distribution,
            'total_nodes' => $nodes->count(),
        ];
    }

    /**
     * Get link equity flow analysis for a specific node.
     */
    public function getNodeEquityFlow(ArchitectureNode $node): array
    {
        // Get equity received from inbound links
        $inboundLinks = $node->inboundLinks()
            ->with('sourceNode')
            ->get();

        $equityReceived = [];
        foreach ($inboundLinks as $link) {
            if ($link->sourceNode && ! $link->is_nofollow) {
                $sourceEquity = $link->sourceNode->link_equity_score ?? 0;
                $sourceOutboundCount = $link->sourceNode->outbound_count ?: 1;
                $contributed = round($sourceEquity / $sourceOutboundCount, 2);

                $equityReceived[] = [
                    'source_url' => $link->sourceNode->url,
                    'source_equity' => $sourceEquity,
                    'contributed' => $contributed,
                ];
            }
        }

        // Get equity distributed to outbound links
        $outboundLinks = $node->outboundLinks()
            ->with('targetNode')
            ->where('is_nofollow', false)
            ->get();

        $nodeEquity = $node->link_equity_score ?? 0;
        $outboundCount = $outboundLinks->count() ?: 1;
        $equityPerLink = round($nodeEquity / $outboundCount, 2);

        $equityDistributed = [];
        foreach ($outboundLinks as $link) {
            if ($link->targetNode) {
                $equityDistributed[] = [
                    'target_url' => $link->targetNode->url,
                    'equity_passed' => $equityPerLink,
                ];
            }
        }

        return [
            'node_equity' => $nodeEquity,
            'total_inbound' => $inboundLinks->count(),
            'total_outbound' => $outboundLinks->count(),
            'equity_received' => $equityReceived,
            'equity_distributed' => $equityDistributed,
            'total_received' => round(array_sum(array_column($equityReceived, 'contributed')), 2),
            'total_distributed' => round($equityPerLink * $outboundLinks->count(), 2),
        ];
    }

    /**
     * Identify potential link equity improvements.
     */
    public function suggestImprovements(SiteArchitecture $architecture): array
    {
        $suggestions = [];

        // Find high-equity pages with few outbound links
        $highEquityLowOutbound = $architecture->nodes()
            ->where('link_equity_score', '>', 50)
            ->where('outbound_count', '<', 3)
            ->get();

        foreach ($highEquityLowOutbound as $node) {
            $suggestions[] = [
                'type' => 'add_outbound_links',
                'node' => $node,
                'message' => "High-equity page '{$node->getDisplayName()}' has few outbound links. Consider adding internal links to distribute equity.",
            ];
        }

        // Find low-equity important pages
        $lowEquityPages = $architecture->nodes()
            ->where('link_equity_score', '<', 10)
            ->whereNotNull('link_equity_score')
            ->where('depth', '<=', 2) // Should be important based on depth
            ->get();

        foreach ($lowEquityPages as $node) {
            $suggestions[] = [
                'type' => 'increase_inbound_links',
                'node' => $node,
                'message' => "Page '{$node->getDisplayName()}' is shallow but has low link equity. Add more internal links pointing to it.",
            ];
        }

        return $suggestions;
    }
}
