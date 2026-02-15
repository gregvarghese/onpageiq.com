<?php

use App\Enums\ArchitectureIssueType;
use App\Enums\ArchitectureStatus;
use App\Enums\ImpactLevel;
use App\Enums\LinkType;
use App\Enums\NodeStatus;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\ArchitectureSnapshot;
use App\Models\Project;
use App\Models\SiteArchitecture;

describe('SiteArchitecture Model', function () {
    it('belongs to a project', function () {
        $project = Project::factory()->create();
        $architecture = SiteArchitecture::factory()->create(['project_id' => $project->id]);

        expect($architecture->project)->toBeInstanceOf(Project::class)
            ->and($architecture->project->id)->toBe($project->id);
    });

    it('has many nodes', function () {
        $architecture = SiteArchitecture::factory()->create();
        $nodes = ArchitectureNode::factory()->count(3)->create([
            'site_architecture_id' => $architecture->id,
        ]);

        expect($architecture->nodes)->toHaveCount(3);
    });

    it('has many links', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node1 = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        $node2 = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureLink::factory()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node1->id,
            'target_node_id' => $node2->id,
        ]);

        expect($architecture->links)->toHaveCount(2);
    });

    it('has many snapshots', function () {
        $architecture = SiteArchitecture::factory()->create();
        ArchitectureSnapshot::factory()->count(2)->create([
            'site_architecture_id' => $architecture->id,
        ]);

        expect($architecture->snapshots)->toHaveCount(2);
    });

    it('has many issues', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        ArchitectureIssue::factory()->count(3)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect($architecture->issues)->toHaveCount(3);
    });

    it('returns latest snapshot', function () {
        $architecture = SiteArchitecture::factory()->create();
        ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $architecture->id,
            'created_at' => now()->subDay(),
        ]);
        $latest = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $architecture->id,
            'created_at' => now(),
        ]);

        expect($architecture->latestSnapshot()->id)->toBe($latest->id);
    });

    it('checks processing status', function () {
        $crawling = SiteArchitecture::factory()->crawling()->create();
        $analyzing = SiteArchitecture::factory()->analyzing()->create();
        $ready = SiteArchitecture::factory()->ready()->create();

        expect($crawling->isProcessing())->toBeTrue()
            ->and($analyzing->isProcessing())->toBeTrue()
            ->and($ready->isProcessing())->toBeFalse();
    });

    it('checks ready status', function () {
        $ready = SiteArchitecture::factory()->ready()->create();
        $pending = SiteArchitecture::factory()->pending()->create();

        expect($ready->isReady())->toBeTrue()
            ->and($pending->isReady())->toBeFalse();
    });

    it('marks as crawling', function () {
        $architecture = SiteArchitecture::factory()->pending()->create();
        $architecture->markAsCrawling();

        expect($architecture->fresh()->status)->toBe(ArchitectureStatus::Crawling);
    });

    it('marks as analyzing', function () {
        $architecture = SiteArchitecture::factory()->crawling()->create();
        $architecture->markAsAnalyzing();

        expect($architecture->fresh()->status)->toBe(ArchitectureStatus::Analyzing);
    });

    it('marks as ready', function () {
        $architecture = SiteArchitecture::factory()->analyzing()->create();
        $architecture->markAsReady();

        $fresh = $architecture->fresh();
        expect($fresh->status)->toBe(ArchitectureStatus::Ready)
            ->and($fresh->last_crawled_at)->not->toBeNull();
    });

    it('marks as failed', function () {
        $architecture = SiteArchitecture::factory()->crawling()->create();
        $architecture->markAsFailed();

        expect($architecture->fresh()->status)->toBe(ArchitectureStatus::Failed);
    });

    it('updates stats from nodes', function () {
        $architecture = SiteArchitecture::factory()->empty()->create();

        ArchitectureNode::factory()->count(5)->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->orphan()->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->clientError()->create(['site_architecture_id' => $architecture->id]);

        $node1 = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id, 'depth' => 3]);
        $node2 = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id, 'depth' => 5]);

        ArchitectureLink::factory()->count(10)->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node1->id,
            'target_node_id' => $node2->id,
        ]);

        $architecture->updateStats();
        $architecture->refresh();

        expect($architecture->total_nodes)->toBe(9)
            ->and($architecture->total_links)->toBe(10)
            ->and($architecture->max_depth)->toBe(5)
            ->and($architecture->orphan_count)->toBe(1)
            ->and($architecture->error_count)->toBe(1);
    });

    it('gets homepage node', function () {
        $architecture = SiteArchitecture::factory()->create();
        $homepage = ArchitectureNode::factory()->homepage()->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id, 'depth' => 1]);

        expect($architecture->getHomepageNode()->id)->toBe($homepage->id);
    });

    it('gets orphan nodes', function () {
        $architecture = SiteArchitecture::factory()->create();
        ArchitectureNode::factory()->count(3)->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->orphan()->count(2)->create(['site_architecture_id' => $architecture->id]);

        expect($architecture->getOrphanNodes())->toHaveCount(2);
    });

    it('gets deep nodes', function () {
        $architecture = SiteArchitecture::factory()->create();
        ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id, 'depth' => 2]);
        ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id, 'depth' => 5]);
        ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id, 'depth' => 6]);

        expect($architecture->getDeepNodes(4))->toHaveCount(2);
    });

    it('gets error nodes', function () {
        $architecture = SiteArchitecture::factory()->create();
        ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->clientError()->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->serverError()->create(['site_architecture_id' => $architecture->id]);
        ArchitectureNode::factory()->timeout()->create(['site_architecture_id' => $architecture->id]);

        expect($architecture->getErrorNodes())->toHaveCount(3);
    });
});

describe('ArchitectureNode Model', function () {
    it('belongs to a site architecture', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        expect($node->siteArchitecture->id)->toBe($architecture->id);
    });

    it('has outbound links', function () {
        $architecture = SiteArchitecture::factory()->create();
        $sourceNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        $targetNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureLink::factory()->count(3)->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

        expect($sourceNode->outboundLinks)->toHaveCount(3);
    });

    it('has inbound links', function () {
        $architecture = SiteArchitecture::factory()->create();
        $sourceNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        $targetNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureLink::factory()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

        expect($targetNode->inboundLinks)->toHaveCount(2);
    });

    it('has issues', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureIssue::factory()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect($node->issues)->toHaveCount(2);
    });

    it('checks error status', function () {
        $errorNode = ArchitectureNode::factory()->clientError()->create();
        $okNode = ArchitectureNode::factory()->create();

        expect($errorNode->isError())->toBeTrue()
            ->and($okNode->isError())->toBeFalse();
    });

    it('checks warning status', function () {
        $redirectNode = ArchitectureNode::factory()->redirect()->create();
        $okNode = ArchitectureNode::factory()->create();

        expect($redirectNode->isWarning())->toBeTrue()
            ->and($okNode->isWarning())->toBeFalse();
    });

    it('checks healthy status', function () {
        $healthyNode = ArchitectureNode::factory()->create([
            'status' => NodeStatus::Ok,
            'is_orphan' => false,
            'is_deep' => false,
        ]);
        $orphanNode = ArchitectureNode::factory()->orphan()->create();

        expect($healthyNode->isHealthy())->toBeTrue()
            ->and($orphanNode->isHealthy())->toBeFalse();
    });

    it('updates link counts', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'inbound_count' => 0,
            'outbound_count' => 0,
        ]);
        $otherNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureLink::factory()->count(3)->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node->id,
            'target_node_id' => $otherNode->id,
        ]);
        ArchitectureLink::factory()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $otherNode->id,
            'target_node_id' => $node->id,
        ]);

        $node->updateLinkCounts();
        $node->refresh();

        expect($node->outbound_count)->toBe(3)
            ->and($node->inbound_count)->toBe(2);
    });

    it('marks as orphan', function () {
        $node = ArchitectureNode::factory()->create(['is_orphan' => false]);
        $node->markAsOrphan();

        expect($node->fresh()->is_orphan)->toBeTrue();
    });

    it('marks as deep when exceeding threshold', function () {
        $deepNode = ArchitectureNode::factory()->create(['depth' => 6, 'is_deep' => false]);
        $shallowNode = ArchitectureNode::factory()->create(['depth' => 3, 'is_deep' => false]);

        $deepNode->markAsDeep(4);
        $shallowNode->markAsDeep(4);

        expect($deepNode->fresh()->is_deep)->toBeTrue()
            ->and($shallowNode->fresh()->is_deep)->toBeFalse();
    });

    it('sets position', function () {
        $node = ArchitectureNode::factory()->create();
        $node->setPosition(100.5, -200.75);

        $fresh = $node->fresh();
        expect((float) $fresh->position_x)->toBe(100.5)
            ->and((float) $fresh->position_y)->toBe(-200.75);
    });

    it('gets display name', function () {
        $nodeWithTitle = ArchitectureNode::factory()->create(['title' => 'My Page', 'path' => '/my-page']);
        $nodeWithoutTitle = ArchitectureNode::factory()->create(['title' => null, 'path' => '/fallback-path']);

        expect($nodeWithTitle->getDisplayName())->toBe('My Page')
            ->and($nodeWithoutTitle->getDisplayName())->toBe('/fallback-path');
    });

    it('gets short path', function () {
        $shortNode = ArchitectureNode::factory()->create(['path' => '/short']);
        $longNode = ArchitectureNode::factory()->create([
            'path' => '/this-is-a-very-long-path-that-should-be-truncated-for-display',
        ]);

        expect($shortNode->getShortPath())->toBe('/short')
            ->and($longNode->getShortPath())->toContain('...');
    });

    it('converts to graph node', function () {
        $node = ArchitectureNode::factory()->create([
            'url' => 'https://example.com/page',
            'path' => '/page',
            'title' => 'Test Page',
            'status' => NodeStatus::Ok,
            'depth' => 2,
        ]);

        $graph = $node->toGraphNode();

        expect($graph)->toHaveKeys(['id', 'url', 'path', 'title', 'status', 'depth', 'inbound_count', 'outbound_count', 'link_equity_score', 'is_orphan', 'is_deep', 'x', 'y'])
            ->and($graph['url'])->toBe('https://example.com/page')
            ->and($graph['status'])->toBe('ok');
    });
});

describe('ArchitectureLink Model', function () {
    it('belongs to source and target nodes', function () {
        $architecture = SiteArchitecture::factory()->create();
        $sourceNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        $targetNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        $link = ArchitectureLink::factory()->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
        ]);

        expect($link->sourceNode->id)->toBe($sourceNode->id)
            ->and($link->targetNode->id)->toBe($targetNode->id);
    });

    it('gets effective link type without override', function () {
        $link = ArchitectureLink::factory()->navigation()->create();

        expect($link->getEffectiveLinkType())->toBe(LinkType::Navigation);
    });

    it('gets effective link type with override', function () {
        $link = ArchitectureLink::factory()->navigation()->withOverride(LinkType::Content)->create();

        expect($link->getEffectiveLinkType())->toBe(LinkType::Content);
    });

    it('checks internal status', function () {
        $internal = ArchitectureLink::factory()->content()->create();
        $external = ArchitectureLink::factory()->external()->create();

        expect($internal->isInternal())->toBeTrue()
            ->and($external->isInternal())->toBeFalse();
    });

    it('checks broken status', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        $brokenLink = ArchitectureLink::factory()->broken()->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node->id,
        ]);
        $validLink = ArchitectureLink::factory()->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node->id,
            'target_node_id' => $node->id,
        ]);
        $externalLink = ArchitectureLink::factory()->external()->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $node->id,
        ]);

        expect($brokenLink->isBroken())->toBeTrue()
            ->and($validLink->isBroken())->toBeFalse()
            ->and($externalLink->isBroken())->toBeFalse();
    });

    it('overrides link type', function () {
        $link = ArchitectureLink::factory()->navigation()->create();
        $link->overrideLinkType(LinkType::Sidebar);

        expect($link->fresh()->link_type_override)->toBe(LinkType::Sidebar);
    });

    it('clears override', function () {
        $link = ArchitectureLink::factory()->withOverride(LinkType::Content)->create();
        $link->clearOverride();

        expect($link->fresh()->link_type_override)->toBeNull();
    });

    it('gets color from effective type', function () {
        $link = ArchitectureLink::factory()->navigation()->create();

        expect($link->getColor())->toBe(LinkType::Navigation->color());
    });

    it('converts to graph edge', function () {
        $architecture = SiteArchitecture::factory()->create();
        $sourceNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        $targetNode = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        $link = ArchitectureLink::factory()->content()->create([
            'site_architecture_id' => $architecture->id,
            'source_node_id' => $sourceNode->id,
            'target_node_id' => $targetNode->id,
            'anchor_text' => 'Read More',
        ]);

        $edge = $link->toGraphEdge();

        expect($edge)->toHaveKeys(['id', 'source', 'target', 'type', 'color', 'anchor_text', 'is_external', 'is_nofollow', 'position'])
            ->and($edge['source'])->toBe($sourceNode->id)
            ->and($edge['target'])->toBe($targetNode->id)
            ->and($edge['type'])->toBe('content');
    });
});

describe('ArchitectureSnapshot Model', function () {
    it('belongs to site architecture', function () {
        $architecture = SiteArchitecture::factory()->create();
        $snapshot = ArchitectureSnapshot::factory()->create(['site_architecture_id' => $architecture->id]);

        expect($snapshot->siteArchitecture->id)->toBe($architecture->id);
    });

    it('gets nodes from snapshot data', function () {
        $snapshot = ArchitectureSnapshot::factory()->create();

        expect($snapshot->getNodes())->toBeArray();
    });

    it('gets links from snapshot data', function () {
        $snapshot = ArchitectureSnapshot::factory()->create();

        expect($snapshot->getLinks())->toBeArray();
    });

    it('gets metadata from snapshot data', function () {
        $snapshot = ArchitectureSnapshot::factory()->create();

        expect($snapshot->getMetadata())->toBeArray()
            ->and($snapshot->getMetadata())->toHaveKeys(['total_nodes', 'total_links']);
    });

    it('checks for changes', function () {
        $withChanges = ArchitectureSnapshot::factory()->withChanges()->create();
        $noChanges = ArchitectureSnapshot::factory()->create(['changes_summary' => null]);

        expect($withChanges->hasChangesSummary())->toBeTrue()
            ->and($noChanges->hasChangesSummary())->toBeFalse();
    });

    it('gets change counts', function () {
        $snapshot = ArchitectureSnapshot::factory()->create([
            'changes_summary' => [
                'added' => 5,
                'removed' => 2,
                'changed' => 3,
            ],
        ]);

        expect($snapshot->getAddedCount())->toBe(5)
            ->and($snapshot->getRemovedCount())->toBe(2)
            ->and($snapshot->getChangedCount())->toBe(3);
    });

    it('compares with another snapshot', function () {
        $architecture = SiteArchitecture::factory()->create();

        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'a', 'url' => '/a'],
                    ['id' => 'b', 'url' => '/b'],
                ],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'b', 'url' => '/b'],
                    ['id' => 'c', 'url' => '/c'],
                ],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $comparison = $snapshot2->compareWith($snapshot1);

        expect($comparison['summary']['added_count'])->toBe(1)
            ->and($comparison['summary']['removed_count'])->toBe(1)
            ->and($comparison['summary']['common_count'])->toBe(1);
    });
});

describe('ArchitectureIssue Model', function () {
    it('belongs to site architecture and node', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);
        $issue = ArchitectureIssue::factory()->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect($issue->siteArchitecture->id)->toBe($architecture->id)
            ->and($issue->node->id)->toBe($node->id);
    });

    it('resolves issue', function () {
        $issue = ArchitectureIssue::factory()->unresolved()->create();
        $issue->resolve();

        expect($issue->fresh()->is_resolved)->toBeTrue();
    });

    it('reopens issue', function () {
        $issue = ArchitectureIssue::factory()->resolved()->create();
        $issue->reopen();

        expect($issue->fresh()->is_resolved)->toBeFalse();
    });

    it('checks critical severity', function () {
        $critical = ArchitectureIssue::factory()->critical()->create();
        $moderate = ArchitectureIssue::factory()->moderate()->create();

        expect($critical->isCritical())->toBeTrue()
            ->and($moderate->isCritical())->toBeFalse();
    });

    it('checks serious severity', function () {
        $serious = ArchitectureIssue::factory()->serious()->create();
        $minor = ArchitectureIssue::factory()->minor()->create();

        expect($serious->isSerious())->toBeTrue()
            ->and($minor->isSerious())->toBeFalse();
    });

    it('gets category from issue type', function () {
        $issue = ArchitectureIssue::factory()->orphanPage()->create();

        expect($issue->getCategory())->toBe(ArchitectureIssueType::OrphanPage->category());
    });

    it('creates issue for node', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        $issue = ArchitectureIssue::createForNode($node, ArchitectureIssueType::OrphanPage);

        expect($issue->node_id)->toBe($node->id)
            ->and($issue->issue_type)->toBe(ArchitectureIssueType::OrphanPage)
            ->and($issue->severity)->toBe(ArchitectureIssueType::OrphanPage->severity());
    });

    it('creates orphan issue', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        $issue = ArchitectureIssue::createOrphanIssue($node);

        expect($issue->issue_type)->toBe(ArchitectureIssueType::OrphanPage)
            ->and($issue->recommendation)->toContain('internal links');
    });

    it('creates deep page issue', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create([
            'site_architecture_id' => $architecture->id,
            'depth' => 7,
        ]);

        $issue = ArchitectureIssue::createDeepPageIssue($node);

        expect($issue->issue_type)->toBe(ArchitectureIssueType::DeepPage)
            ->and($issue->recommendation)->toContain('7 clicks');
    });

    it('creates broken link issue', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        $issue = ArchitectureIssue::createBrokenLinkIssue($node, 'https://broken.url/page');

        expect($issue->issue_type)->toBe(ArchitectureIssueType::BrokenLink)
            ->and($issue->severity)->toBe(ImpactLevel::Critical)
            ->and($issue->message)->toContain('https://broken.url/page');
    });

    it('scopes unresolved issues', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureIssue::factory()->unresolved()->count(3)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);
        ArchitectureIssue::factory()->resolved()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect(ArchitectureIssue::unresolved()->count())->toBe(3);
    });

    it('scopes by type', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureIssue::factory()->orphanPage()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);
        ArchitectureIssue::factory()->brokenLink()->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect(ArchitectureIssue::ofType(ArchitectureIssueType::OrphanPage)->count())->toBe(2);
    });

    it('scopes critical issues', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureIssue::factory()->critical()->count(2)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);
        ArchitectureIssue::factory()->minor()->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect(ArchitectureIssue::critical()->count())->toBe(2);
    });

    it('scopes serious issues', function () {
        $architecture = SiteArchitecture::factory()->create();
        $node = ArchitectureNode::factory()->create(['site_architecture_id' => $architecture->id]);

        ArchitectureIssue::factory()->serious()->count(3)->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);
        ArchitectureIssue::factory()->moderate()->create([
            'site_architecture_id' => $architecture->id,
            'node_id' => $node->id,
        ]);

        expect(ArchitectureIssue::serious()->count())->toBe(3);
    });
});

describe('Project SiteArchitecture Relationships', function () {
    it('has site architectures', function () {
        $project = Project::factory()->create();
        SiteArchitecture::factory()->count(2)->create(['project_id' => $project->id]);

        expect($project->siteArchitectures)->toHaveCount(2);
    });

    it('gets latest site architecture', function () {
        $project = Project::factory()->create();
        SiteArchitecture::factory()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDay(),
        ]);
        $latest = SiteArchitecture::factory()->create([
            'project_id' => $project->id,
            'created_at' => now(),
        ]);

        expect($project->latestSiteArchitecture->id)->toBe($latest->id);
    });
});
