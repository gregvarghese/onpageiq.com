<?php

use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;
use App\Services\Architecture\ClusteringService;
use App\Services\Architecture\DepthAnalysisService;
use App\Services\Architecture\GraphLayoutService;
use App\Services\Architecture\LinkEquityService;
use App\Services\Architecture\OrphanDetectionService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create([
        'organization_id' => $this->user->organization_id,
    ]);
    $this->architecture = SiteArchitecture::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'ready',
    ]);
});

describe('Large Graph Performance', function () {
    it('handles 1000 nodes efficiently', function () {
        // Create 1000 nodes
        $nodes = ArchitectureNode::factory()->count(1000)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        // Create links between nodes (average 3 links per node)
        $nodeIds = $nodes->pluck('id')->toArray();
        $links = [];
        foreach ($nodes->take(500) as $index => $node) {
            // Create 2-4 outbound links per node
            $numLinks = rand(2, 4);
            $targetIndices = array_rand($nodeIds, min($numLinks, count($nodeIds)));
            $targetIndices = is_array($targetIndices) ? $targetIndices : [$targetIndices];

            foreach ($targetIndices as $targetIndex) {
                if ($nodeIds[$targetIndex] !== $node->id) {
                    $links[] = [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'site_architecture_id' => $this->architecture->id,
                        'source_node_id' => $node->id,
                        'target_node_id' => $nodeIds[$targetIndex],
                        'target_url' => null,
                        'link_type' => 'navigation',
                        'is_external' => false,
                        'is_nofollow' => false,
                        'created_at' => now(),
                    ];
                }
            }
        }

        // Bulk insert links
        foreach (array_chunk($links, 100) as $chunk) {
            ArchitectureLink::insert($chunk);
        }

        // Measure graph data generation time
        $service = new GraphLayoutService;

        $startTime = microtime(true);
        $graphData = $service->getD3GraphData($this->architecture, false);
        $duration = microtime(true) - $startTime;

        expect($graphData['nodes'])->toHaveCount(1000);
        expect($duration)->toBeLessThan(5.0); // Should complete within 5 seconds
    });

    it('clustering service handles large node sets', function () {
        // Create 500 nodes with varied paths
        $paths = ['/blog', '/products', '/about', '/services', '/team'];
        foreach ($paths as $basePath) {
            ArchitectureNode::factory()->count(100)->create([
                'site_architecture_id' => $this->architecture->id,
                'path' => fn () => $basePath.'/'.fake()->slug(2),
            ]);
        }

        $service = new ClusteringService;

        $startTime = microtime(true);
        $clusters = $service->clusterByPath($this->architecture);
        $duration = microtime(true) - $startTime;

        // Should have at least 5 clusters (one per path prefix)
        expect(count($clusters))->toBeGreaterThanOrEqual(5);
        expect($duration)->toBeLessThan(2.0);
    });

    it('depth analysis scales with node count', function () {
        // Create homepage
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        // Create nodes at various depths
        for ($depth = 1; $depth <= 5; $depth++) {
            ArchitectureNode::factory()->count(100)->create([
                'site_architecture_id' => $this->architecture->id,
                'depth' => $depth,
            ]);
        }

        $service = new DepthAnalysisService;

        $startTime = microtime(true);
        $analysis = $service->analyzeDepth($this->architecture);
        $duration = microtime(true) - $startTime;

        // Analysis should return depth statistics
        expect($analysis)->toBeArray();
        expect($analysis)->toHaveKey('max_depth');
        expect($analysis)->toHaveKey('average_depth');
        expect($analysis)->toHaveKey('depth_distribution');
        expect($duration)->toBeLessThan(2.0);
    });

    it('link equity calculation converges for large graphs', function () {
        // Create interconnected graph
        $nodes = ArchitectureNode::factory()->count(200)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $nodeIds = $nodes->pluck('id')->toArray();

        // Create random links
        $links = [];
        for ($i = 0; $i < 600; $i++) {
            $sourceIndex = array_rand($nodeIds);
            $targetIndex = array_rand($nodeIds);

            if ($sourceIndex !== $targetIndex) {
                $links[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'site_architecture_id' => $this->architecture->id,
                    'source_node_id' => $nodeIds[$sourceIndex],
                    'target_node_id' => $nodeIds[$targetIndex],
                    'target_url' => null,
                    'link_type' => 'content',
                    'is_external' => false,
                    'is_nofollow' => false,
                    'created_at' => now(),
                ];
            }
        }

        foreach (array_chunk($links, 100) as $chunk) {
            ArchitectureLink::insert($chunk);
        }

        $service = new LinkEquityService;

        $startTime = microtime(true);
        $scores = $service->calculateLinkEquity($this->architecture);
        $duration = microtime(true) - $startTime;

        expect($scores)->toHaveCount(200);
        expect($duration)->toBeLessThan(5.0);
        // All scores should be positive
        expect(min($scores))->toBeGreaterThan(0);
    });

    it('orphan detection scales linearly', function () {
        // Create homepage (depth 0, not orphan)
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 0,
        ]);

        // Create linked nodes (have inbound links from homepage)
        $linkedNodes = ArchitectureNode::factory()->count(300)->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => false,
            'depth' => fn () => rand(1, 3),
        ]);

        // Create actual inbound links for linked nodes (from homepage)
        $links = [];
        foreach ($linkedNodes as $node) {
            $links[] = [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'site_architecture_id' => $this->architecture->id,
                'source_node_id' => $homepage->id,
                'target_node_id' => $node->id,
                'target_url' => null,
                'link_type' => 'navigation',
                'is_external' => false,
                'is_nofollow' => false,
                'created_at' => now(),
            ];
        }
        foreach (array_chunk($links, 100) as $chunk) {
            ArchitectureLink::insert($chunk);
        }

        // Create orphan nodes (no inbound links)
        ArchitectureNode::factory()->count(100)->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
            'depth' => fn () => rand(1, 5),
        ]);

        $service = new OrphanDetectionService;

        $startTime = microtime(true);
        $orphans = $service->detectOrphans($this->architecture);
        $duration = microtime(true) - $startTime;

        // Should find the 100 orphan nodes (nodes without inbound links)
        expect($orphans)->toHaveCount(100);
        expect($duration)->toBeLessThan(2.0);
    });

    it('graph data with externals handles many external domains', function () {
        // Create internal nodes
        $nodes = ArchitectureNode::factory()->count(100)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        // Create external links to 50 different domains
        $nodeIds = $nodes->pluck('id')->toArray();
        $links = [];

        for ($i = 0; $i < 50; $i++) {
            $domain = "external-domain-{$i}.com";
            // Each domain has 5-10 links
            for ($j = 0; $j < rand(5, 10); $j++) {
                $links[] = [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'site_architecture_id' => $this->architecture->id,
                    'source_node_id' => $nodeIds[array_rand($nodeIds)],
                    'target_node_id' => null,
                    'target_url' => "https://{$domain}/page-{$j}",
                    'link_type' => 'external',
                    'is_external' => true,
                    'external_domain' => $domain,
                    'is_nofollow' => false,
                    'created_at' => now(),
                ];
            }
        }

        foreach (array_chunk($links, 100) as $chunk) {
            ArchitectureLink::insert($chunk);
        }

        $service = new GraphLayoutService;

        $startTime = microtime(true);
        $graphData = $service->getD3GraphDataWithExternals($this->architecture, false);
        $duration = microtime(true) - $startTime;

        // Should have 100 internal nodes + 50 external domain nodes
        expect($graphData['nodes'])->toHaveCount(150);
        expect($duration)->toBeLessThan(3.0);
    });
});

describe('Memory Usage', function () {
    it('graph data generation stays within memory limits', function () {
        // Create 500 nodes
        ArchitectureNode::factory()->count(500)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $memoryBefore = memory_get_usage(true);

        $service = new GraphLayoutService;
        $graphData = $service->getD3GraphData($this->architecture, false);

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024 / 1024; // MB

        expect($graphData['nodes'])->toHaveCount(500);
        expect($memoryUsed)->toBeLessThan(50); // Should use less than 50MB additional memory
    });
});

describe('Cache Performance', function () {
    it('cached graph data retrieval is fast', function () {
        // Create nodes
        ArchitectureNode::factory()->count(200)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $service = new GraphLayoutService;

        // First call - uncached
        $startTime = microtime(true);
        $service->getD3GraphData($this->architecture);
        $uncachedDuration = microtime(true) - $startTime;

        // Second call - cached
        $startTime = microtime(true);
        $service->getD3GraphData($this->architecture);
        $cachedDuration = microtime(true) - $startTime;

        // Cached retrieval should be at least 5x faster
        expect($cachedDuration)->toBeLessThan($uncachedDuration / 5);
    });
});
