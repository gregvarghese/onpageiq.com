<?php

use App\Enums\ArchitectureIssueType;
use App\Enums\ImpactLevel;
use App\Enums\LinkType;
use App\Jobs\CrawlArchitectureJob;
use App\Livewire\SiteArchitecture\ArchitectureFilters;
use App\Livewire\SiteArchitecture\CrawlConfigModal;
use App\Livewire\SiteArchitecture\LinkClassificationModal;
use App\Livewire\SiteArchitecture\NodeDetailPanel;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
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

describe('NodeDetailPanel Component', function () {
    it('renders with no node selected', function () {
        Livewire::test(NodeDetailPanel::class)
            ->assertStatus(200)
            ->assertSet('nodeId', null);
    });

    it('displays node details when node is set', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/test-page',
            'title' => 'Test Page Title',
            'depth' => 2,
            'inbound_count' => 5,
            'outbound_count' => 3,
        ]);

        Livewire::test(NodeDetailPanel::class, ['nodeId' => $node->id])
            ->assertSee('Test Page Title')
            ->assertSee('https://example.com/test-page');
    });

    it('dispatches node-deselected event on close', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(NodeDetailPanel::class, ['nodeId' => $node->id])
            ->call('close')
            ->assertDispatched('node-deselected');
    });

    it('dispatches open-url event on view page', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/test',
        ]);

        Livewire::test(NodeDetailPanel::class, ['nodeId' => $node->id])
            ->call('viewPage')
            ->assertDispatched('open-url', url: 'https://example.com/test');
    });

    it('shows inbound links', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $sourceNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/source-page',
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $node->id,
        ]);

        $component = Livewire::test(NodeDetailPanel::class, ['nodeId' => $node->id]);

        expect($component->get('inboundLinks'))->toHaveCount(1);
    });

    it('shows outbound links', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $targetNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/target-page',
        ]);

        ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $node->id,
            'target_node_id' => $targetNode->id,
        ]);

        $component = Livewire::test(NodeDetailPanel::class, ['nodeId' => $node->id]);

        expect($component->get('outboundLinks'))->toHaveCount(1);
    });

    it('shows node issues', function () {
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        ArchitectureIssue::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'node_id' => $node->id,
            'issue_type' => ArchitectureIssueType::OrphanPage,
            'severity' => ImpactLevel::Serious,
            'is_resolved' => false,
        ]);

        $component = Livewire::test(NodeDetailPanel::class, ['nodeId' => $node->id]);

        expect($component->get('issues'))->toHaveCount(1);
    });
});

describe('CrawlConfigModal Component', function () {
    it('renders closed by default', function () {
        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->assertSet('isOpen', false);
    });

    it('opens on event', function () {
        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->dispatch('open-crawl-config-modal')
            ->assertSet('isOpen', true);
    });

    it('closes on close method', function () {
        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->dispatch('open-crawl-config-modal')
            ->assertSet('isOpen', true)
            ->call('close')
            ->assertSet('isOpen', false);
    });

    it('has default configuration values', function () {
        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->assertSet('maxDepth', 5)
            ->assertSet('maxPages', 1000)
            ->assertSet('timeout', 30000)
            ->assertSet('respectRobotsTxt', true)
            ->assertSet('enableJsRendering', false);
    });

    it('validates max depth range', function () {
        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->set('maxDepth', 15)
            ->call('startCrawl')
            ->assertHasErrors(['maxDepth']);
    });

    it('validates max pages range', function () {
        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->set('maxPages', 50000)
            ->call('startCrawl')
            ->assertHasErrors(['maxPages']);
    });

    it('dispatches crawl job on start', function () {
        Queue::fake();

        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->dispatch('open-crawl-config-modal')
            ->call('startCrawl')
            ->assertDispatched('crawl-started')
            ->assertSet('isOpen', false);

        Queue::assertPushed(CrawlArchitectureJob::class);
    });

    it('loads project architecture config defaults', function () {
        $this->project->update([
            'architecture_config' => [
                'max_depth' => 8,
                'max_pages' => 2000,
                'request_timeout' => 45,
                'exclude_patterns' => ['/admin/*', '/api/*'],
                'include_patterns' => ['/blog/*'],
                'respect_robots_txt' => false,
                'javascript_rendering' => true,
            ],
        ]);

        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->dispatch('open-crawl-config-modal')
            ->assertSet('maxDepth', 8)
            ->assertSet('maxPages', 2000)
            ->assertSet('timeout', 45000) // Converted to ms
            ->assertSet('excludePatterns', "/admin/*\n/api/*")
            ->assertSet('includePatterns', '/blog/*')
            ->assertSet('respectRobotsTxt', false)
            ->assertSet('enableJsRendering', true);
    });

    it('saves config as project defaults when checkbox is checked', function () {
        Queue::fake();

        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->dispatch('open-crawl-config-modal')
            ->set('maxDepth', 7)
            ->set('maxPages', 1500)
            ->set('timeout', 20000)
            ->set('respectRobotsTxt', false)
            ->set('saveAsDefaults', true)
            ->call('startCrawl');

        $this->project->refresh();
        expect($this->project->architecture_config)->toMatchArray([
            'max_depth' => 7,
            'max_pages' => 1500,
            'request_timeout' => 20, // Converted to seconds
            'respect_robots_txt' => false,
        ]);
    });

    it('does not save config as defaults when checkbox is unchecked', function () {
        Queue::fake();

        $this->project->update(['architecture_config' => null]);

        Livewire::test(CrawlConfigModal::class, ['project' => $this->project])
            ->dispatch('open-crawl-config-modal')
            ->set('maxDepth', 7)
            ->set('saveAsDefaults', false)
            ->call('startCrawl');

        $this->project->refresh();
        expect($this->project->architecture_config)->toBeNull();
    });
});

describe('ArchitectureFilters Component', function () {
    it('renders collapsed by default', function () {
        Livewire::test(ArchitectureFilters::class)
            ->assertSet('isExpanded', false);
    });

    it('toggles expansion', function () {
        Livewire::test(ArchitectureFilters::class)
            ->call('toggleExpanded')
            ->assertSet('isExpanded', true)
            ->call('toggleExpanded')
            ->assertSet('isExpanded', false);
    });

    it('has default filter values', function () {
        Livewire::test(ArchitectureFilters::class)
            ->assertSet('filters.minDepth', null)
            ->assertSet('filters.maxDepth', null)
            ->assertSet('filters.linkType', null)
            ->assertSet('filters.status', null)
            ->assertSet('filters.showOrphans', false);
    });

    it('resets filters to defaults', function () {
        Livewire::test(ArchitectureFilters::class)
            ->set('filters.minDepth', 2)
            ->set('filters.showOrphans', true)
            ->call('resetFilters')
            ->assertSet('filters.minDepth', null)
            ->assertSet('filters.showOrphans', false)
            ->assertDispatched('filters-updated');
    });

    it('dispatches filters-updated on apply', function () {
        Livewire::test(ArchitectureFilters::class)
            ->set('filters.minDepth', 1)
            ->set('filters.maxDepth', 5)
            ->call('applyFilters')
            ->assertDispatched('filters-updated');
    });

    it('detects active filters', function () {
        $component = Livewire::test(ArchitectureFilters::class);

        expect($component->invade()->hasActiveFilters())->toBeFalse();

        $component->set('filters.showOrphans', true);

        expect($component->invade()->hasActiveFilters())->toBeTrue();
    });

    it('provides link types for dropdown', function () {
        $component = Livewire::test(ArchitectureFilters::class);
        $linkTypes = $component->invade()->getLinkTypes();

        expect($linkTypes)->toHaveKey('navigation');
        expect($linkTypes)->toHaveKey('content');
        expect($linkTypes)->toHaveKey('footer');
    });

    it('provides node statuses for dropdown', function () {
        $component = Livewire::test(ArchitectureFilters::class);
        $statuses = $component->invade()->getNodeStatuses();

        expect($statuses)->toHaveKey('ok');
        expect($statuses)->toHaveKey('redirect');
        expect($statuses)->toHaveKey('orphan');
    });
});

describe('LinkClassificationModal Component', function () {
    it('renders closed by default', function () {
        Livewire::test(LinkClassificationModal::class)
            ->assertSet('isOpen', false)
            ->assertSet('linkId', null);
    });

    it('opens with link id', function () {
        $sourceNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $targetNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $link = ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'link_type' => LinkType::Content,
        ]);

        Livewire::test(LinkClassificationModal::class)
            ->dispatch('open-link-classification-modal', linkId: $link->id)
            ->assertSet('isOpen', true)
            ->assertSet('linkId', $link->id)
            ->assertSet('selectedLinkType', 'content');
    });

    it('closes and resets state', function () {
        $sourceNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $targetNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $link = ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

        Livewire::test(LinkClassificationModal::class)
            ->dispatch('open-link-classification-modal', linkId: $link->id)
            ->call('close')
            ->assertSet('isOpen', false)
            ->assertSet('linkId', null)
            ->assertSet('selectedLinkType', null);
    });

    it('saves link type override', function () {
        $sourceNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $targetNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $link = ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'link_type' => LinkType::Content,
            'link_type_override' => null,
        ]);

        Livewire::test(LinkClassificationModal::class)
            ->dispatch('open-link-classification-modal', linkId: $link->id)
            ->set('selectedLinkType', 'navigation')
            ->call('save')
            ->assertDispatched('link-classification-updated')
            ->assertSet('isOpen', false);

        $link->refresh();
        expect($link->link_type_override)->toBe(LinkType::Navigation);
    });

    it('clears override when selecting original type', function () {
        $sourceNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $targetNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $link = ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'link_type' => LinkType::Content,
            'link_type_override' => LinkType::Navigation,
        ]);

        Livewire::test(LinkClassificationModal::class)
            ->dispatch('open-link-classification-modal', linkId: $link->id)
            ->set('selectedLinkType', 'content')
            ->call('save');

        $link->refresh();
        expect($link->link_type_override)->toBeNull();
    });

    it('clears override on clear button', function () {
        $sourceNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $targetNode = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $link = ArchitectureLink::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'link_type' => LinkType::Content,
            'link_type_override' => LinkType::Navigation,
        ]);

        Livewire::test(LinkClassificationModal::class)
            ->dispatch('open-link-classification-modal', linkId: $link->id)
            ->call('clearOverride')
            ->assertDispatched('link-classification-updated')
            ->assertSet('isOpen', false);

        $link->refresh();
        expect($link->link_type_override)->toBeNull();
    });

    it('provides link types for selection', function () {
        $component = Livewire::test(LinkClassificationModal::class);
        $linkTypes = $component->invade()->getLinkTypes();

        expect($linkTypes)->toHaveKey('navigation');
        expect($linkTypes)->toHaveKey('content');
        expect($linkTypes)->toHaveKey('footer');
        expect($linkTypes)->toHaveKey('sidebar');
    });
});
