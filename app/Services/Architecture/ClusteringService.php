<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class ClusteringService
{
    /**
     * Clustering strategies.
     */
    public const CLUSTER_BY_PATH = 'path';

    public const CLUSTER_BY_DEPTH = 'depth';

    public const CLUSTER_BY_CONTENT_TYPE = 'content_type';

    public const CLUSTER_BY_LINK_DENSITY = 'link_density';

    /**
     * Minimum nodes to trigger clustering.
     */
    public const CLUSTER_THRESHOLD = 100;

    /**
     * Maximum nodes per cluster.
     */
    public const MAX_CLUSTER_SIZE = 50;

    /**
     * Check if clustering is needed based on node count.
     */
    public function shouldCluster(SiteArchitecture $architecture): bool
    {
        return $architecture->nodes()
            ->count() > self::CLUSTER_THRESHOLD;
    }

    /**
     * Cluster nodes using the specified strategy.
     *
     * @return Collection<string, array{nodes: Collection, center: array, metadata: array}>
     */
    public function clusterNodes(
        SiteArchitecture $architecture,
        string $strategy = self::CLUSTER_BY_PATH
    ): Collection {
        return match ($strategy) {
            self::CLUSTER_BY_DEPTH => $this->clusterByDepth($architecture),
            self::CLUSTER_BY_CONTENT_TYPE => $this->clusterByContentType($architecture),
            self::CLUSTER_BY_LINK_DENSITY => $this->clusterByLinkDensity($architecture),
            default => $this->clusterByPath($architecture),
        };
    }

    /**
     * Cluster nodes by URL path segments.
     *
     * @return Collection<string, array{nodes: Collection, center: array, metadata: array}>
     */
    public function clusterByPath(SiteArchitecture $architecture): Collection
    {
        $nodes = $architecture->nodes()
            ->get();

        // Group by first path segment
        $clusters = $nodes->groupBy(function (ArchitectureNode $node) {
            $path = parse_url($node->url, PHP_URL_PATH) ?? '/';
            $segments = explode('/', trim($path, '/'));

            return $segments[0] ?: 'root';
        });

        return $this->formatClusters($clusters, 'path');
    }

    /**
     * Cluster nodes by crawl depth.
     *
     * @return Collection<string, array{nodes: Collection, center: array, metadata: array}>
     */
    public function clusterByDepth(SiteArchitecture $architecture): Collection
    {
        $nodes = $architecture->nodes()
            ->get();

        $clusters = $nodes->groupBy(function (ArchitectureNode $node) {
            $depth = $node->depth ?? 0;

            // Group into depth ranges for large sites
            if ($depth <= 1) {
                return 'depth_0-1';
            }
            if ($depth <= 3) {
                return 'depth_2-3';
            }
            if ($depth <= 5) {
                return 'depth_4-5';
            }

            return 'depth_6+';
        });

        return $this->formatClusters($clusters, 'depth');
    }

    /**
     * Cluster nodes by content type (inferred from path).
     *
     * @return Collection<string, array{nodes: Collection, center: array, metadata: array}>
     */
    public function clusterByContentType(SiteArchitecture $architecture): Collection
    {
        $nodes = $architecture->nodes()
            ->get();

        $clusters = $nodes->groupBy(function (ArchitectureNode $node) {
            $path = strtolower($node->path ?? '');

            // Detect content type from common URL patterns
            if (preg_match('/\/(blog|news|articles?|posts?)\//', $path)) {
                return 'blog';
            }
            if (preg_match('/\/(products?|shop|store|items?)\//', $path)) {
                return 'products';
            }
            if (preg_match('/\/(categor(y|ies)|collections?)\//', $path)) {
                return 'categories';
            }
            if (preg_match('/\/(pages?|about|contact|faq|help)/', $path)) {
                return 'pages';
            }
            if (preg_match('/\/(docs?|documentation|guides?|tutorials?)\//', $path)) {
                return 'documentation';
            }
            if (preg_match('/\/(users?|profiles?|accounts?)\//', $path)) {
                return 'users';
            }
            if (preg_match('/\/(tags?|labels?)\//', $path)) {
                return 'tags';
            }
            if (preg_match('/\/(search|results)/', $path)) {
                return 'search';
            }
            if ($path === '/' || $path === '') {
                return 'homepage';
            }

            return 'other';
        });

        return $this->formatClusters($clusters, 'content_type');
    }

    /**
     * Cluster nodes by link density (high/medium/low connectivity).
     *
     * @return Collection<string, array{nodes: Collection, center: array, metadata: array}>
     */
    public function clusterByLinkDensity(SiteArchitecture $architecture): Collection
    {
        $nodes = $architecture->nodes()
            ->get();

        // Calculate average link count for thresholds
        $avgInbound = $nodes->avg('inbound_count') ?? 5;
        $avgOutbound = $nodes->avg('outbound_count') ?? 5;

        $clusters = $nodes->groupBy(function (ArchitectureNode $node) use ($avgInbound, $avgOutbound) {
            $totalLinks = ($node->inbound_count ?? 0) + ($node->outbound_count ?? 0);
            $avgTotal = $avgInbound + $avgOutbound;

            if ($totalLinks > $avgTotal * 2) {
                return 'high_connectivity';
            }
            if ($totalLinks > $avgTotal * 0.5) {
                return 'medium_connectivity';
            }

            return 'low_connectivity';
        });

        return $this->formatClusters($clusters, 'link_density');
    }

    /**
     * Format clusters into a standardized structure.
     *
     * @param  Collection<string, Collection>  $clusters
     * @return Collection<string, array{nodes: Collection, center: array, metadata: array}>
     */
    protected function formatClusters(Collection $clusters, string $strategy): Collection
    {
        return $clusters->map(function (Collection $nodes, string $clusterId) use ($strategy) {
            // Calculate cluster center from average positions
            $avgX = $nodes->avg('position_x') ?? 600;
            $avgY = $nodes->avg('position_y') ?? 400;

            // Calculate cluster metrics
            $avgEquity = $nodes->avg('link_equity_score') ?? 0;
            $avgDepth = $nodes->avg('depth') ?? 0;
            $totalInbound = $nodes->sum('inbound_count');
            $totalOutbound = $nodes->sum('outbound_count');

            return [
                'id' => $clusterId,
                'nodes' => $nodes,
                'node_ids' => $nodes->pluck('id')->toArray(),
                'node_count' => $nodes->count(),
                'center' => [
                    'x' => round($avgX, 2),
                    'y' => round($avgY, 2),
                ],
                'metadata' => [
                    'strategy' => $strategy,
                    'avg_link_equity' => round($avgEquity, 2),
                    'avg_depth' => round($avgDepth, 2),
                    'total_inbound_links' => $totalInbound,
                    'total_outbound_links' => $totalOutbound,
                ],
            ];
        });
    }

    /**
     * Get cluster data formatted for D3.js visualization.
     */
    public function getD3ClusterData(
        SiteArchitecture $architecture,
        string $strategy = self::CLUSTER_BY_PATH
    ): array {
        $clusters = $this->clusterNodes($architecture, $strategy);

        $clusterNodes = [];
        $clusterLinks = [];

        foreach ($clusters as $clusterId => $cluster) {
            // Create cluster node
            $clusterNodes[] = [
                'id' => 'cluster_'.$clusterId,
                'label' => ucfirst(str_replace('_', ' ', $clusterId)),
                'type' => 'cluster',
                'node_count' => $cluster['node_count'],
                'x' => $cluster['center']['x'],
                'y' => $cluster['center']['y'],
                'radius' => min(50, 15 + ($cluster['node_count'] * 0.5)),
                'metadata' => $cluster['metadata'],
            ];
        }

        // Calculate inter-cluster links
        $clusterLinkCounts = [];
        foreach ($clusters as $sourceClusterId => $sourceCluster) {
            foreach ($sourceCluster['nodes'] as $node) {
                $outboundLinks = $node->outboundLinks()
                    ->with('targetNode')
                    ->get();

                foreach ($outboundLinks as $link) {
                    if (! $link->targetNode) {
                        continue;
                    }

                    // Find target cluster
                    foreach ($clusters as $targetClusterId => $targetCluster) {
                        if ($targetCluster['nodes']->contains('id', $link->targetNode->id)) {
                            if ($sourceClusterId !== $targetClusterId) {
                                $key = $sourceClusterId.'|'.$targetClusterId;
                                $clusterLinkCounts[$key] = ($clusterLinkCounts[$key] ?? 0) + 1;
                            }
                            break;
                        }
                    }
                }
            }
        }

        // Create cluster links
        foreach ($clusterLinkCounts as $key => $count) {
            [$source, $target] = explode('|', $key);
            $clusterLinks[] = [
                'source' => 'cluster_'.$source,
                'target' => 'cluster_'.$target,
                'weight' => $count,
                'type' => 'cluster_link',
            ];
        }

        return [
            'clusters' => $clusterNodes,
            'links' => $clusterLinks,
            'strategy' => $strategy,
            'total_nodes' => $clusters->sum('node_count'),
            'total_clusters' => $clusters->count(),
        ];
    }

    /**
     * Expand a cluster to show individual nodes.
     *
     * @return array{nodes: array, links: array}
     */
    public function expandCluster(SiteArchitecture $architecture, string $clusterId, string $strategy = self::CLUSTER_BY_PATH): array
    {
        $clusters = $this->clusterNodes($architecture, $strategy);

        if (! isset($clusters[$clusterId])) {
            return ['nodes' => [], 'links' => []];
        }

        $clusterNodes = $clusters[$clusterId]['nodes'];
        $nodeIds = $clusterNodes->pluck('id')->toArray();

        // Get nodes formatted for D3
        $nodes = $clusterNodes->map(fn (ArchitectureNode $node) => $node->toGraphNode())->values()->toArray();

        // Get links between cluster nodes
        $links = $architecture->links()
            ->whereIn('source_node_id', $nodeIds)
            ->whereIn('target_node_id', $nodeIds)
            ->get()
            ->map(fn ($link) => $link->toGraphEdge())
            ->values()
            ->toArray();

        return [
            'nodes' => $nodes,
            'links' => $links,
            'cluster_id' => $clusterId,
        ];
    }

    /**
     * Get recommended clustering strategy based on site structure.
     */
    public function getRecommendedStrategy(SiteArchitecture $architecture): string
    {
        $nodes = $architecture->nodes()
            ->get();

        // Check path diversity
        $pathSegments = $nodes->map(function (ArchitectureNode $node) {
            $path = parse_url($node->url, PHP_URL_PATH) ?? '/';
            $segments = explode('/', trim($path, '/'));

            return $segments[0] ?? 'root';
        })->unique()->count();

        // Check depth distribution
        $maxDepth = $nodes->max('depth') ?? 0;

        // If many unique path segments, cluster by path
        if ($pathSegments > 5 && $pathSegments < $nodes->count() * 0.3) {
            return self::CLUSTER_BY_PATH;
        }

        // If deep site, cluster by depth
        if ($maxDepth > 4) {
            return self::CLUSTER_BY_DEPTH;
        }

        // Default to path clustering
        return self::CLUSTER_BY_PATH;
    }
}
