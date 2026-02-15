<?php

use App\Enums\ArchitectureIssueType;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use App\Services\Architecture\ClusteringService;
use App\Services\Architecture\DepthAnalysisService;
use App\Services\Architecture\GraphLayoutService;
use App\Services\Architecture\LinkEquityService;
use App\Services\Architecture\OrphanDetectionService;

beforeEach(function () {
    $this->architecture = SiteArchitecture::factory()->ready()->create();
});

describe('OrphanDetectionService', function () {
    beforeEach(function () {
        $this->service = new OrphanDetectionService;
    });

    it('detects orphan pages with no inbound links', function () {
        // Create homepage with outbound links
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        // Create linked page (not orphan)
        $linkedPage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/linked',
            'depth' => 1,
        ]);

        // Create link from homepage to linked page
        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $linkedPage->id,
        ]);

        // Create orphan page (no inbound links)
        $orphanPage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/orphan',
            'depth' => 99, // High depth to indicate unreachable
        ]);

        $orphans = $this->service->detectOrphans($this->architecture);

        expect($orphans)->toHaveCount(1);
        expect($orphans->first()->id)->toBe($orphanPage->id);
    });

    it('does not count homepage as orphan', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $orphans = $this->service->detectOrphans($this->architecture);

        expect($orphans)->toHaveCount(0);
    });

    it('calculates orphan rate', function () {
        // Create 3 internal nodes, 1 orphan
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $linked = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/linked',
            'depth' => 1,
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $linked->id,
        ]);

        // Create orphan (no inbound links)
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/orphan',
            'depth' => 99,
        ]);

        $rate = $this->service->calculateOrphanRate($this->architecture);

        // 1 orphan out of 3 nodes = 33.33%
        expect($rate)->toBeGreaterThan(30);
        expect($rate)->toBeLessThan(35);
    });

    it('marks orphans and creates issues', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $orphan = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/orphan',
            'depth' => 99,
        ]);

        $count = $this->service->markOrphansAndCreateIssues($this->architecture);

        expect($count)->toBe(1);
        expect($orphan->fresh()->is_orphan)->toBeTrue();
        expect($this->architecture->issues()->where('issue_type', ArchitectureIssueType::OrphanPage)->count())->toBe(1);
    });
});

describe('DepthAnalysisService', function () {
    beforeEach(function () {
        $this->service = new DepthAnalysisService;
    });

    it('analyzes depth from homepage using BFS', function () {
        // Create homepage
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        // Create depth 1 page
        $depth1 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page1',
            'depth' => 1,
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $depth1->id,
        ]);

        // Create depth 2 page
        $depth2 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page2',
            'depth' => 2,
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $depth1->id,
            'target_node_id' => $depth2->id,
        ]);

        $result = $this->service->analyzeDepth($this->architecture);

        expect($result['max_depth'])->toBe(2);
        expect($result['total_analyzed'])->toBe(3);
        expect($depth1->fresh()->depth)->toBe(1);
        expect($depth2->fresh()->depth)->toBe(2);
    });

    it('detects deep pages exceeding threshold', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        // Create a page at depth 5 (above default threshold of 4)
        $deepPage = ArchitectureNode::factory()->deep()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/deep-page',
            'depth' => 5,
        ]);

        $deepPages = $this->service->detectDeepPages($this->architecture, 4);

        expect($deepPages)->toHaveCount(1);
        expect($deepPages->first()->id)->toBe($deepPage->id);
    });

    it('calculates depth score', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page1',
            'depth' => 1,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page2',
            'depth' => 2,
        ]);

        $score = $this->service->calculateDepthScore($this->architecture);

        expect($score['score'])->toBeGreaterThan(90);
        expect($score['grade'])->toBe('A');
    });

    it('gets depth statistics by path segment', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'path' => '/',
            'depth' => 0,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/blog/post-1',
            'path' => '/blog/post-1',
            'depth' => 2,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/blog/post-2',
            'path' => '/blog/post-2',
            'depth' => 2,
        ]);

        $stats = $this->service->getDepthByPathSegment($this->architecture);

        expect($stats->has('blog'))->toBeTrue();
        expect($stats->get('blog')['count'])->toBe(2);
        expect($stats->get('blog')['avg_depth'])->toBe(2.0);
    });
});

describe('LinkEquityService', function () {
    beforeEach(function () {
        $this->service = new LinkEquityService;
    });

    it('calculates PageRank-like scores', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $page1 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page1',
            'depth' => 1,
        ]);

        $page2 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page2',
            'depth' => 1,
        ]);

        // Homepage links to both pages
        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $page1->id,
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $page2->id,
        ]);

        // Page1 also links to page2
        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $page1->id,
            'target_node_id' => $page2->id,
        ]);

        $scores = $this->service->calculateLinkEquity($this->architecture);

        expect($scores)->toHaveCount(3);
        // Page2 should have highest score (most inbound links)
        expect($scores[$page2->id])->toBeGreaterThanOrEqual($scores[$page1->id]);
    });

    it('persists link equity scores to nodes', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $page = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page',
            'depth' => 1,
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $page->id,
        ]);

        $this->service->calculateAndPersist($this->architecture);

        expect($homepage->fresh()->link_equity_score)->not->toBeNull();
        expect($page->fresh()->link_equity_score)->not->toBeNull();
    });

    it('analyzes score distribution', function () {
        ArchitectureNode::factory()->count(5)->create([
            'site_architecture_id' => $this->architecture->id,
            'link_equity_score' => fn () => fake()->randomFloat(2, 10, 100),
        ]);

        $distribution = $this->service->analyzeDistribution($this->architecture);

        expect($distribution)->toHaveKeys(['min', 'max', 'average', 'median', 'distribution']);
        expect($distribution['total_nodes'])->toBe(5);
    });

    it('respects nofollow links', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $page = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page',
            'depth' => 1,
        ]);

        // Create nofollow link
        ArchitectureLink::factory()->nofollow()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $page->id,
        ]);

        $scores = $this->service->calculateLinkEquity($this->architecture);

        // Both should have similar scores since nofollow doesn't pass equity
        expect(abs($scores[$homepage->id] - $scores[$page->id]))->toBeLessThan(20);
    });
});

describe('GraphLayoutService', function () {
    beforeEach(function () {
        $this->service = new GraphLayoutService;
    });

    it('calculates force-directed layout positions', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        $page1 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page1',
            'depth' => 1,
        ]);

        $positions = $this->service->calculateForceDirectedLayout($this->architecture);

        expect($positions)->toHaveCount(2);
        expect($positions[$homepage->id])->toHaveKeys(['x', 'y']);
        expect($positions[$page1->id])->toHaveKeys(['x', 'y']);
    });

    it('places homepage at center', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        $this->service->setDimensions(1000, 800);
        $positions = $this->service->calculateForceDirectedLayout($this->architecture);

        expect((float) $positions[$homepage->id]['x'])->toBe(500.0);
        expect((float) $positions[$homepage->id]['y'])->toBe(400.0);
    });

    it('calculates hierarchical layout', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        $page1 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page1',
            'depth' => 1,
        ]);

        $page2 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page2',
            'depth' => 1,
        ]);

        $positions = $this->service->calculateHierarchicalLayout($this->architecture);

        expect($positions)->toHaveCount(3);
        // Depth 0 should be above depth 1
        expect($positions[$homepage->id]['y'])->toBeLessThan($positions[$page1->id]['y']);
        expect($positions[$page1->id]['y'])->toBe($positions[$page2->id]['y']);
    });

    it('exports D3 graph data', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $page = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page',
            'depth' => 1,
        ]);

        ArchitectureLink::factory()->navigation()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $page->id,
        ]);

        $data = $this->service->getD3GraphData($this->architecture);

        expect($data)->toHaveKeys(['nodes', 'links']);
        expect($data['nodes'])->toHaveCount(2);
        expect($data['links'])->toHaveCount(1);
    });

    it('persists layout positions to database', function () {
        $page = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/page',
            'depth' => 0,
        ]);

        $this->service->calculateLayout($this->architecture, GraphLayoutService::LAYOUT_CIRCULAR);

        $page->refresh();
        expect($page->position_x)->not->toBeNull();
        expect($page->position_y)->not->toBeNull();
    });

    it('groups external links by domain', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        // Create external links to same domain
        ArchitectureLink::factory()->external()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => null,
            'target_url' => 'https://github.com/page1',
            'external_domain' => 'github.com',
        ]);

        ArchitectureLink::factory()->external()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => null,
            'target_url' => 'https://github.com/page2',
            'external_domain' => 'github.com',
        ]);

        // Create external link to different domain
        ArchitectureLink::factory()->external()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => null,
            'target_url' => 'https://twitter.com/user',
            'external_domain' => 'twitter.com',
        ]);

        $data = $this->service->getD3GraphDataWithExternals($this->architecture);

        // Should have 1 internal node + 2 external domain nodes
        expect($data['nodes'])->toHaveCount(3);

        // Find external domain nodes
        $externalNodes = collect($data['nodes'])->filter(
            fn ($node) => ($node['type'] ?? null) === 'external_domain'
        );

        expect($externalNodes)->toHaveCount(2);

        // GitHub node should have link_count of 2
        $githubNode = $externalNodes->firstWhere('label', 'github.com');
        expect($githubNode['link_count'])->toBe(2);
    });

    it('creates links to external domain nodes', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        ArchitectureLink::factory()->external()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => null,
            'target_url' => 'https://external.com/page',
            'external_domain' => 'external.com',
        ]);

        $data = $this->service->getD3GraphDataWithExternals($this->architecture);

        // Should have grouped links to external domain nodes (in addition to base links)
        $groupedExternalLinks = collect($data['links'])->filter(
            fn ($link) => ($link['target'] ?? null) === 'external_external.com'
        );

        expect($groupedExternalLinks)->toHaveCount(1);
        expect($groupedExternalLinks->first()['source'])->toBe($homepage->id);
        expect($groupedExternalLinks->first()['type'])->toBe('external');
        expect($groupedExternalLinks->first()['color'])->toBe('#F59E0B');
    });

    it('returns base data when no external links exist', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $data = $this->service->getD3GraphDataWithExternals($this->architecture);

        expect($data['nodes'])->toHaveCount(1);
        expect($data['links'])->toHaveCount(0);
    });

    it('caches graph data', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        // First call - should compute and cache
        $data1 = $this->service->getD3GraphData($this->architecture);

        // Add another node
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/new-page',
            'depth' => 1,
        ]);

        // Second call - should return cached data (still 1 node)
        $data2 = $this->service->getD3GraphData($this->architecture);

        expect($data2['nodes'])->toHaveCount(1);

        // With cache disabled - should return fresh data (2 nodes)
        $data3 = $this->service->getD3GraphData($this->architecture, false);

        expect($data3['nodes'])->toHaveCount(2);
    });

    it('clears cache when architecture is updated', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        // Cache the data
        $data1 = $this->service->getD3GraphData($this->architecture);
        expect($data1['nodes'])->toHaveCount(1);

        // Add another node
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/new-page',
            'depth' => 1,
        ]);

        // Update the architecture (triggers cache clear)
        $this->architecture->update(['total_nodes' => 2]);

        // Now should return fresh data
        $data2 = $this->service->getD3GraphData($this->architecture);
        expect($data2['nodes'])->toHaveCount(2);
    });

    it('clears cache via clearCache method', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        // Cache the data
        $this->service->getD3GraphData($this->architecture);

        // Add another node
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/new-page',
            'depth' => 1,
        ]);

        // Clear cache manually
        $this->service->clearCache($this->architecture);

        // Now should return fresh data
        $data = $this->service->getD3GraphData($this->architecture);
        expect($data['nodes'])->toHaveCount(2);
    });
});

describe('ClusteringService', function () {
    beforeEach(function () {
        $this->service = new ClusteringService;
    });

    it('determines if clustering is needed', function () {
        // With few nodes, no clustering needed
        ArchitectureNode::factory()->count(10)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        expect($this->service->shouldCluster($this->architecture))->toBeFalse();
    });

    it('clusters by URL path', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/blog/post-1',
            'path' => '/blog/post-1',
            'depth' => 1,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/blog/post-2',
            'path' => '/blog/post-2',
            'depth' => 1,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/products/item-1',
            'path' => '/products/item-1',
            'depth' => 1,
        ]);

        $clusters = $this->service->clusterByPath($this->architecture);

        expect($clusters->has('blog'))->toBeTrue();
        expect($clusters->has('products'))->toBeTrue();
        expect($clusters->get('blog')['node_count'])->toBe(2);
        expect($clusters->get('products')['node_count'])->toBe(1);
    });

    it('clusters by depth', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'depth' => 0,
        ]);

        ArchitectureNode::factory()->count(3)->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 2,
        ]);

        $clusters = $this->service->clusterByDepth($this->architecture);

        expect($clusters->has('depth_0-1'))->toBeTrue();
        expect($clusters->has('depth_2-3'))->toBeTrue();
    });

    it('clusters by content type', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/blog/my-article',
            'path' => '/blog/my-article',
            'depth' => 1,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/products/widget',
            'path' => '/products/widget',
            'depth' => 1,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/docs/getting-started',
            'path' => '/docs/getting-started',
            'depth' => 1,
        ]);

        $clusters = $this->service->clusterByContentType($this->architecture);

        expect($clusters->has('blog'))->toBeTrue();
        expect($clusters->has('products'))->toBeTrue();
        expect($clusters->has('documentation'))->toBeTrue();
    });

    it('exports D3 cluster data', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/blog/post',
            'path' => '/blog/post',
            'depth' => 1,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/products/item',
            'path' => '/products/item',
            'depth' => 1,
        ]);

        $data = $this->service->getD3ClusterData($this->architecture);

        expect($data)->toHaveKeys(['clusters', 'links', 'strategy', 'total_nodes', 'total_clusters']);
        expect($data['total_clusters'])->toBe(2);
    });

    it('recommends clustering strategy', function () {
        ArchitectureNode::factory()->count(10)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $strategy = $this->service->getRecommendedStrategy($this->architecture);

        expect($strategy)->toBeIn([
            ClusteringService::CLUSTER_BY_PATH,
            ClusteringService::CLUSTER_BY_DEPTH,
            ClusteringService::CLUSTER_BY_CONTENT_TYPE,
            ClusteringService::CLUSTER_BY_LINK_DENSITY,
        ]);
    });
});
