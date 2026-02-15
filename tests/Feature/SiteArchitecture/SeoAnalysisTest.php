<?php

use App\Enums\ArchitectureIssueType;
use App\Livewire\SiteArchitecture\SeoInsightsPanel;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;
use App\Services\Architecture\ArchitectureRecommendationService;
use App\Services\Architecture\ArchitectureSeoService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'organization_id' => $this->user->organization_id,
    ]);

    $this->architecture = SiteArchitecture::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'ready',
    ]);
});

describe('ArchitectureSeoService', function () {
    it('analyzes site architecture', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
        ]);

        $page = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/about',
            'depth' => 1,
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $homepage->id,
            'target_node_id' => $page->id,
        ]);

        $service = app(ArchitectureSeoService::class);
        $analysis = $service->analyze($this->architecture);

        expect($analysis)->toHaveKeys([
            'orphan_analysis',
            'depth_analysis',
            'equity_analysis',
            'linking_opportunities',
            'critical_issues',
            'overall_score',
        ]);
    });

    it('analyzes orphan pages', function () {
        // Create orphan page (no inbound links)
        $orphanNode = ArchitectureNode::factory()
            ->orphan()
            ->for($this->architecture, 'siteArchitecture')
            ->create([
                'url' => 'https://example.com/orphan',
            ]);

        // Verify node was created correctly
        expect($orphanNode->site_architecture_id)->toBe($this->architecture->id);
        expect($this->architecture->nodes()->count())->toBe(1);

        $service = app(ArchitectureSeoService::class);

        // Direct orphan check
        $isOrphan = app(\App\Services\Architecture\OrphanDetectionService::class)->isOrphan($orphanNode);
        expect($isOrphan)->toBeTrue();

        $analysis = $service->analyzeOrphans($this->architecture);

        expect($analysis['count'])->toBeGreaterThanOrEqual(1);
        expect($analysis['rate'])->toBeGreaterThan(0);
        expect($analysis)->toHaveKey('severity');
    });

    it('analyzes page depth', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 0,
        ]);

        ArchitectureNode::factory()->deep()->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 6,
        ]);

        $service = app(ArchitectureSeoService::class);
        $analysis = $service->analyzeDepth($this->architecture);

        expect($analysis)->toHaveKeys(['max_depth', 'average_depth', 'deep_pages_count', 'score', 'grade']);
        expect($analysis['deep_pages_count'])->toBeGreaterThanOrEqual(1);
    });

    it('analyzes link equity distribution', function () {
        ArchitectureNode::factory()->withHighEquity()->create([
            'site_architecture_id' => $this->architecture->id,
            'link_equity_score' => 0.5,
        ]);

        ArchitectureNode::factory()->withLowEquity()->create([
            'site_architecture_id' => $this->architecture->id,
            'link_equity_score' => 0.001,
            'depth' => 3,
        ]);

        $service = app(ArchitectureSeoService::class);
        $analysis = $service->analyzeEquity($this->architecture);

        expect($analysis)->toHaveKeys(['distribution', 'low_equity_pages', 'high_equity_pages', 'equity_gap']);
    });

    it('finds linking opportunities', function () {
        $highEquityPage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/popular',
            'link_equity_score' => 0.3,
            'outbound_count' => 2,
            'depth' => 1,
        ]);

        $lowLinkPage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/hidden',
            'inbound_count' => 1,
            'depth' => 3,
        ]);

        $service = app(ArchitectureSeoService::class);
        $opportunities = $service->findLinkingOpportunities($this->architecture);

        expect($opportunities)->toBeArray();
    });

    it('calculates overall SEO score', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 0,
        ]);

        ArchitectureNode::factory()->count(5)->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 2,
            'is_orphan' => false,
        ]);

        $service = app(ArchitectureSeoService::class);
        $score = $service->calculateOverallScore($this->architecture);

        expect($score)->toHaveKeys(['overall', 'grade', 'breakdown']);
        expect($score['overall'])->toBeGreaterThanOrEqual(0);
        expect($score['overall'])->toBeLessThanOrEqual(100);
        expect($score['grade'])->toBeIn(['A', 'B', 'C', 'D', 'F']);
    });

    it('creates SEO issues', function () {
        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
            'inbound_count' => 0,
        ]);

        ArchitectureNode::factory()->deep()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_deep' => true,
            'depth' => 6,
        ]);

        $service = app(ArchitectureSeoService::class);
        $issueCount = $service->createIssues($this->architecture);

        expect($issueCount)->toBeGreaterThan(0);
        expect($this->architecture->issues()->count())->toBeGreaterThan(0);
    });

    it('gets critical issues', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        ArchitectureIssue::factory()->critical()->create([
            'site_architecture_id' => $this->architecture->id,
            'node_id' => $node->id,
            'issue_type' => ArchitectureIssueType::OrphanPage,
        ]);

        $service = app(ArchitectureSeoService::class);
        $criticalIssues = $service->getCriticalIssues($this->architecture);

        expect($criticalIssues)->toHaveCount(1);
        expect($criticalIssues[0]['severity'])->toBe('critical');
    });
});

describe('ArchitectureRecommendationService', function () {
    it('generates prioritized recommendations', function () {
        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
        ]);

        $service = app(ArchitectureRecommendationService::class);
        $recommendations = $service->generateRecommendations($this->architecture);

        expect($recommendations)->toBeArray();
        expect(count($recommendations))->toBeGreaterThan(0);

        // Check recommendations are sorted by priority (descending)
        $priorities = array_column($recommendations, 'priority');
        for ($i = 1; $i < count($priorities); $i++) {
            expect($priorities[$i])->toBeLessThanOrEqual($priorities[$i - 1]);
        }
    });

    it('includes effort estimates', function () {
        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
        ]);

        $service = app(ArchitectureRecommendationService::class);
        $recommendations = $service->generateRecommendations($this->architecture);

        foreach ($recommendations as $rec) {
            expect($rec)->toHaveKeys(['effort', 'impact_score']);
            expect($rec['effort'])->toBeIn(['low', 'medium', 'high']);
        }
    });

    it('generates fix roadmap', function () {
        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
        ]);

        ArchitectureNode::factory()->deep()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_deep' => true,
            'depth' => 7,
        ]);

        $service = app(ArchitectureRecommendationService::class);
        $roadmap = $service->getFixRoadmap($this->architecture);

        expect($roadmap)->toHaveKeys(['quick_wins', 'major_projects', 'fill_ins', 'deprioritize']);
    });

    it('provides recommendation summary', function () {
        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
        ]);

        $service = app(ArchitectureRecommendationService::class);
        $summary = $service->getSummary($this->architecture);

        expect($summary)->toHaveKeys(['total', 'by_category', 'by_severity', 'top_priority']);
    });
});

describe('SeoInsightsPanel Component', function () {
    it('renders with no architecture', function () {
        Livewire::test(SeoInsightsPanel::class)
            ->assertStatus(200)
            ->assertSee('No architecture data available');
    });

    it('renders with architecture data', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(SeoInsightsPanel::class, ['architectureId' => $this->architecture->id])
            ->assertStatus(200)
            ->assertSee('Overview');
    });

    it('switches between tabs', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(SeoInsightsPanel::class, ['architectureId' => $this->architecture->id])
            ->assertSet('activeTab', 'overview')
            ->call('setTab', 'issues')
            ->assertSet('activeTab', 'issues')
            ->call('setTab', 'recommendations')
            ->assertSet('activeTab', 'recommendations')
            ->call('setTab', 'roadmap')
            ->assertSet('activeTab', 'roadmap');
    });

    it('computes SEO analysis', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $component = Livewire::test(SeoInsightsPanel::class, ['architectureId' => $this->architecture->id]);

        $analysis = $component->get('seoAnalysis');

        expect($analysis)->toBeArray();
        expect($analysis)->toHaveKey('overall_score');
    });

    it('computes recommendations', function () {
        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'is_orphan' => true,
        ]);

        $component = Livewire::test(SeoInsightsPanel::class, ['architectureId' => $this->architecture->id]);

        $recommendations = $component->get('recommendations');

        expect($recommendations)->toBeArray();
    });

    it('computes roadmap', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $component = Livewire::test(SeoInsightsPanel::class, ['architectureId' => $this->architecture->id]);

        $roadmap = $component->get('roadmap');

        expect($roadmap)->toBeArray();
        expect($roadmap)->toHaveKeys(['quick_wins', 'major_projects', 'fill_ins', 'deprioritize']);
    });
});
