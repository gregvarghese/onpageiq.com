<?php

use App\Enums\ArchitectureStatus;
use App\Livewire\SiteArchitecture\SiteArchitecturePage;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'organization_id' => $this->user->organization_id,
    ]);
});

describe('SiteArchitecturePage Component', function () {
    it('renders the component', function () {
        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee('Site Architecture');
    });

    it('shows empty state when no architecture exists', function () {
        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSee('No site architecture');
    });

    it('loads architecture data when available', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
            'total_nodes' => 10,
            'total_links' => 15,
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSet('architecture.id', $architecture->id);
    });

    it('changes view mode', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSet('viewMode', 'force')
            ->call('setViewMode', 'tree')
            ->assertSet('viewMode', 'tree')
            ->call('setViewMode', 'directory')
            ->assertSet('viewMode', 'directory');
    });

    it('changes cluster strategy', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSet('clusterStrategy', 'path')
            ->call('setClusterStrategy', 'depth')
            ->assertSet('clusterStrategy', 'depth')
            ->call('setClusterStrategy', 'content_type')
            ->assertSet('clusterStrategy', 'content_type');
    });

    it('toggles external links display', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSet('showExternalLinks', false)
            ->call('toggleExternalLinks')
            ->assertSet('showExternalLinks', true)
            ->call('toggleExternalLinks')
            ->assertSet('showExternalLinks', false);
    });

    it('toggles cluster display', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSet('showClusters', false)
            ->call('toggleClusters')
            ->assertSet('showClusters', true)
            ->call('toggleClusters')
            ->assertSet('showClusters', false);
    });

    it('selects a node', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'url' => 'https://example.com/page1',
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->assertSet('selectedNodeId', null)
            ->call('selectNode', $node->id)
            ->assertSet('selectedNodeId', $node->id);
    });

    it('deselects node when null is passed', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->call('selectNode', $node->id)
            ->assertSet('selectedNodeId', $node->id)
            ->call('selectNode', null)
            ->assertSet('selectedNodeId', null);
    });

    it('dispatches crawl config modal event', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->call('startCrawl')
            ->assertDispatched('open-crawl-config-modal');
    });

    it('refreshes architecture data', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'crawling',
            'total_nodes' => 5,
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        // Update architecture in database
        $architecture->update(['status' => ArchitectureStatus::Ready, 'total_nodes' => 20]);

        $component->call('refreshArchitecture')
            ->assertSet('architecture.status', ArchitectureStatus::Ready)
            ->assertSet('architecture.total_nodes', 20);
    });
});

describe('SiteArchitecturePage Computed Properties', function () {
    it('returns empty graph data when no architecture', function () {
        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        expect($component->get('graphData'))->toBe(['nodes' => [], 'links' => []]);
    });

    it('returns graph data with nodes and links', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $node1 = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $architecture->id,
            'url' => 'https://example.com/',
        ]);

        $node2 = ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'url' => 'https://example.com/about',
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node1->id,
            'target_node_id' => $node2->id,
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        $graphData = $component->get('graphData');

        expect($graphData['nodes'])->toHaveCount(2);
        expect($graphData['links'])->toHaveCount(1);
    });

    it('returns statistics when architecture exists', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
            'total_nodes' => 25,
            'total_links' => 40,
            'max_depth' => 3,
            'orphan_count' => 2,
            'error_count' => 1,
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        $statistics = $component->get('statistics');

        expect($statistics['totalNodes'])->toBe(25);
        expect($statistics['totalLinks'])->toBe(40);
        expect($statistics['maxDepth'])->toBe(3);
        expect($statistics['orphanCount'])->toBe(2);
        expect($statistics['errorCount'])->toBe(1);
    });

    it('returns empty statistics when no architecture', function () {
        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        expect($component->get('statistics'))->toBe([]);
    });

    it('returns selected node details', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'url' => 'https://example.com/page1',
            'title' => 'Test Page',
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->call('selectNode', $node->id);

        $selectedNode = $component->get('selectedNode');

        expect($selectedNode)->not->toBeNull();
        expect($selectedNode['node']->id)->toBe($node->id);
        expect($selectedNode['node']->title)->toBe('Test Page');
    });

    it('returns null for selected node when none selected', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        expect($component->get('selectedNode'))->toBeNull();
    });

    it('returns cluster data when clusters enabled', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        // Create nodes in different path clusters
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'url' => 'https://example.com/blog/post1',
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'url' => 'https://example.com/products/item1',
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project])
            ->call('toggleClusters');

        $clusterData = $component->get('clusterData');

        expect($clusterData)->toHaveKey('clusters');
        expect($clusterData)->toHaveKey('links');
        expect($clusterData)->toHaveKey('strategy');
    });

    it('returns empty cluster data when clusters disabled', function () {
        $architecture = SiteArchitecture::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'ready',
        ]);

        $component = Livewire::test(SiteArchitecturePage::class, ['project' => $this->project]);

        expect($component->get('clusterData'))->toBe(['clusters' => [], 'links' => []]);
    });
});

describe('SiteArchitecturePage Route', function () {
    it('is accessible via route', function () {
        $this->get(route('projects.architecture', $this->project))
            ->assertStatus(200);
    });

    it('requires authentication', function () {
        auth()->logout();

        $this->get(route('projects.architecture', $this->project))
            ->assertRedirect(route('login'));
    });
});
