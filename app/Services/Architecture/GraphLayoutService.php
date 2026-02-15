<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Facades\Cache;

class GraphLayoutService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    protected const CACHE_TTL = 300;

    /**
     * Layout algorithms.
     */
    public const LAYOUT_FORCE_DIRECTED = 'force';

    public const LAYOUT_HIERARCHICAL = 'hierarchical';

    public const LAYOUT_RADIAL = 'radial';

    public const LAYOUT_CIRCULAR = 'circular';

    /**
     * Default canvas dimensions.
     */
    protected int $width = 1200;

    protected int $height = 800;

    /**
     * Set canvas dimensions.
     */
    public function setDimensions(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Calculate and persist positions for all nodes.
     */
    public function calculateLayout(
        SiteArchitecture $architecture,
        string $algorithm = self::LAYOUT_FORCE_DIRECTED
    ): void {
        $positions = match ($algorithm) {
            self::LAYOUT_HIERARCHICAL => $this->calculateHierarchicalLayout($architecture),
            self::LAYOUT_RADIAL => $this->calculateRadialLayout($architecture),
            self::LAYOUT_CIRCULAR => $this->calculateCircularLayout($architecture),
            default => $this->calculateForceDirectedLayout($architecture),
        };

        // Persist positions to database
        foreach ($positions as $nodeId => $position) {
            ArchitectureNode::where('id', $nodeId)->update([
                'position_x' => $position['x'],
                'position_y' => $position['y'],
            ]);
        }
    }

    /**
     * Calculate initial positions for force-directed layout.
     * D3.js will handle the simulation client-side, but we provide initial positions.
     *
     * @return array<string, array{x: float, y: float}>
     */
    public function calculateForceDirectedLayout(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->get();

        $positions = [];
        $centerX = $this->width / 2;
        $centerY = $this->height / 2;
        $radius = min($this->width, $this->height) * 0.4;

        // Position homepage at center
        $homepage = $architecture->getHomepageNode();
        if ($homepage) {
            $positions[$homepage->id] = ['x' => $centerX, 'y' => $centerY];
        }

        // Group nodes by depth and position in concentric circles
        $nodesByDepth = $nodes->groupBy('depth');

        foreach ($nodesByDepth as $depth => $depthNodes) {
            if ($depth === 0) {
                continue; // Homepage already positioned
            }

            $count = $depthNodes->count();
            $depthRadius = $radius * ($depth / max($nodesByDepth->keys()->max(), 1));
            $angleStep = (2 * M_PI) / max($count, 1);

            foreach ($depthNodes->values() as $index => $node) {
                $angle = $index * $angleStep - (M_PI / 2); // Start from top
                $positions[$node->id] = [
                    'x' => $centerX + $depthRadius * cos($angle),
                    'y' => $centerY + $depthRadius * sin($angle),
                ];
            }
        }

        // Handle nodes without depth (orphans) - place around perimeter
        $orphans = $nodes->filter(fn ($n) => $n->depth === null);
        if ($orphans->isNotEmpty()) {
            $orphanRadius = $radius * 1.2;
            $orphanAngleStep = (2 * M_PI) / $orphans->count();

            foreach ($orphans->values() as $index => $node) {
                $angle = $index * $orphanAngleStep;
                $positions[$node->id] = [
                    'x' => $centerX + $orphanRadius * cos($angle),
                    'y' => $centerY + $orphanRadius * sin($angle),
                ];
            }
        }

        return $positions;
    }

    /**
     * Calculate hierarchical tree layout based on depth.
     *
     * @return array<string, array{x: float, y: float}>
     */
    public function calculateHierarchicalLayout(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->orderBy('depth')
            ->get();

        $positions = [];
        $levelHeight = $this->height / (($nodes->max('depth') ?? 0) + 2);
        $nodesByDepth = $nodes->groupBy('depth');

        foreach ($nodesByDepth as $depth => $levelNodes) {
            $count = $levelNodes->count();
            $levelWidth = $this->width / ($count + 1);
            $y = ($depth ?? 0) * $levelHeight + $levelHeight;

            foreach ($levelNodes->values() as $index => $node) {
                $x = ($index + 1) * $levelWidth;
                $positions[$node->id] = ['x' => $x, 'y' => $y];
            }
        }

        return $positions;
    }

    /**
     * Calculate radial layout with homepage at center.
     *
     * @return array<string, array{x: float, y: float}>
     */
    public function calculateRadialLayout(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->get();

        $positions = [];
        $centerX = $this->width / 2;
        $centerY = $this->height / 2;
        $maxRadius = min($this->width, $this->height) * 0.45;

        $homepage = $architecture->getHomepageNode();
        $maxDepth = $nodes->max('depth') ?? 1;

        if ($homepage) {
            $positions[$homepage->id] = ['x' => $centerX, 'y' => $centerY];
        }

        $nodesByDepth = $nodes->groupBy('depth');

        foreach ($nodesByDepth as $depth => $depthNodes) {
            if ($depth === 0 || $depth === null) {
                continue;
            }

            $radius = ($depth / $maxDepth) * $maxRadius;
            $count = $depthNodes->count();
            $angleStep = (2 * M_PI) / max($count, 1);

            foreach ($depthNodes->values() as $index => $node) {
                $angle = $index * $angleStep - (M_PI / 2);
                $positions[$node->id] = [
                    'x' => $centerX + $radius * cos($angle),
                    'y' => $centerY + $radius * sin($angle),
                ];
            }
        }

        return $positions;
    }

    /**
     * Calculate circular layout - all nodes in a circle.
     *
     * @return array<string, array{x: float, y: float}>
     */
    public function calculateCircularLayout(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->orderBy('depth')
            ->orderBy('url')
            ->get();

        $positions = [];
        $centerX = $this->width / 2;
        $centerY = $this->height / 2;
        $radius = min($this->width, $this->height) * 0.4;
        $count = $nodes->count();
        $angleStep = (2 * M_PI) / max($count, 1);

        foreach ($nodes->values() as $index => $node) {
            $angle = $index * $angleStep - (M_PI / 2);
            $positions[$node->id] = [
                'x' => $centerX + $radius * cos($angle),
                'y' => $centerY + $radius * sin($angle),
            ];
        }

        return $positions;
    }

    /**
     * Get graph data formatted for D3.js visualization.
     */
    public function getD3GraphData(SiteArchitecture $architecture, bool $useCache = true): array
    {
        $cacheKey = "architecture_graph_data_{$architecture->id}";

        if ($useCache) {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($architecture) {
                return $this->buildGraphData($architecture);
            });
        }

        return $this->buildGraphData($architecture);
    }

    /**
     * Build graph data from architecture.
     */
    protected function buildGraphData(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->get()
            ->map(fn (ArchitectureNode $node) => $node->toGraphNode())
            ->values()
            ->toArray();

        $links = $architecture->links()
            ->get()
            ->map(fn ($link) => $link->toGraphEdge())
            ->values()
            ->toArray();

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }

    /**
     * Get graph data with external nodes (grouped by domain).
     */
    public function getD3GraphDataWithExternals(SiteArchitecture $architecture, bool $useCache = true): array
    {
        $cacheKey = "architecture_graph_data_externals_{$architecture->id}";

        if ($useCache) {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($architecture) {
                return $this->buildGraphDataWithExternals($architecture);
            });
        }

        return $this->buildGraphDataWithExternals($architecture);
    }

    /**
     * Build graph data with external nodes.
     */
    protected function buildGraphDataWithExternals(SiteArchitecture $architecture): array
    {
        $baseData = $this->getD3GraphData($architecture, false);

        // Group external links by domain
        $externalDomains = $architecture->links()
            ->where('is_external', true)
            ->whereNotNull('external_domain')
            ->get()
            ->groupBy('external_domain');

        $externalNodes = [];
        foreach ($externalDomains as $domain => $links) {
            $externalNodes[] = [
                'id' => 'external_'.$domain,
                'url' => 'https://'.$domain,
                'label' => $domain,
                'type' => 'external_domain',
                'link_count' => $links->count(),
                'x' => null,
                'y' => null,
            ];
        }

        // Create links to external domain nodes
        $externalLinks = [];
        foreach ($architecture->links()->where('is_external', true)->get() as $link) {
            if ($link->external_domain) {
                $externalLinks[] = [
                    'source' => $link->source_node_id,
                    'target' => 'external_'.$link->external_domain,
                    'type' => 'external',
                    'color' => '#F59E0B',
                ];
            }
        }

        return [
            'nodes' => array_merge($baseData['nodes'], $externalNodes),
            'links' => array_merge($baseData['links'], $externalLinks),
        ];
    }

    /**
     * Calculate bounding box for all nodes.
     */
    public function getBoundingBox(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->whereNotNull('position_x')
            ->whereNotNull('position_y')
            ->get();

        if ($nodes->isEmpty()) {
            return [
                'minX' => 0,
                'minY' => 0,
                'maxX' => $this->width,
                'maxY' => $this->height,
                'width' => $this->width,
                'height' => $this->height,
            ];
        }

        $minX = $nodes->min('position_x');
        $minY = $nodes->min('position_y');
        $maxX = $nodes->max('position_x');
        $maxY = $nodes->max('position_y');

        return [
            'minX' => $minX,
            'minY' => $minY,
            'maxX' => $maxX,
            'maxY' => $maxY,
            'width' => $maxX - $minX,
            'height' => $maxY - $minY,
        ];
    }

    /**
     * Export layout as JSON for client-side D3.
     */
    public function exportLayoutJson(SiteArchitecture $architecture): string
    {
        $data = $this->getD3GraphData($architecture);

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Clear cached graph data for an architecture.
     */
    public function clearCache(SiteArchitecture $architecture): void
    {
        Cache::forget("architecture_graph_data_{$architecture->id}");
        Cache::forget("architecture_graph_data_externals_{$architecture->id}");
    }

    /**
     * Clear all cached graph data.
     */
    public function clearAllCache(): void
    {
        // This would require cache tags for efficient clearing
        // For now, individual caches are cleared via clearCache()
    }
}
