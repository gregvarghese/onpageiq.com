<?php

use App\Livewire\SiteArchitecture\ComparisonView;
use App\Livewire\SiteArchitecture\VersionHistoryPanel;
use App\Models\ArchitectureSnapshot;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;
use App\Services\Architecture\ArchitectureComparisonService;
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

describe('ArchitectureComparisonService', function () {
    it('compares two snapshots', function () {
        $baseSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home'],
                    ['id' => 'node-2', 'url' => 'https://example.com/about', 'title' => 'About'],
                ],
                'links' => [
                    ['source' => 'node-1', 'target' => 'node-2'],
                ],
                'metadata' => ['total_nodes' => 2, 'total_links' => 1],
            ],
            'nodes_count' => 2,
            'links_count' => 1,
        ]);

        $targetSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home'],
                    ['id' => 'node-2', 'url' => 'https://example.com/about', 'title' => 'About Us'],
                    ['id' => 'node-3', 'url' => 'https://example.com/contact', 'title' => 'Contact'],
                ],
                'links' => [
                    ['source' => 'node-1', 'target' => 'node-2'],
                    ['source' => 'node-1', 'target' => 'node-3'],
                ],
                'metadata' => ['total_nodes' => 3, 'total_links' => 2],
            ],
            'nodes_count' => 3,
            'links_count' => 2,
        ]);

        $service = app(ArchitectureComparisonService::class);
        $comparison = $service->compare($baseSnapshot, $targetSnapshot);

        expect($comparison)->toHaveKeys(['nodes', 'links', 'metrics', 'summary']);
        expect($comparison['nodes']['added'])->toHaveCount(1);
        expect($comparison['nodes']['removed'])->toHaveCount(0);
        expect($comparison['links']['added'])->toHaveCount(1);
    });

    it('detects node changes', function () {
        $baseSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home', 'depth' => 0],
                ],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $targetSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Homepage', 'depth' => 0],
                ],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $service = app(ArchitectureComparisonService::class);
        $comparison = $service->compare($baseSnapshot, $targetSnapshot);

        expect($comparison['nodes']['changed'])->toHaveCount(1);
        expect($comparison['nodes']['changed'][0]['changes'])->toHaveKey('title');
    });

    it('categorizes change types', function () {
        $baseSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [['id' => 'node-1', 'url' => 'https://example.com/']],
                'links' => [],
                'metadata' => ['total_nodes' => 1],
            ],
        ]);

        $targetSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/'],
                    ['id' => 'node-2', 'url' => 'https://example.com/new'],
                ],
                'links' => [],
                'metadata' => ['total_nodes' => 2],
            ],
        ]);

        $service = app(ArchitectureComparisonService::class);
        $comparison = $service->compare($baseSnapshot, $targetSnapshot);

        expect($comparison['summary']['change_type'])->toBe('expansion');
    });

    it('generates timeline for architecture', function () {
        ArchitectureSnapshot::factory()->count(3)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $service = app(ArchitectureComparisonService::class);
        $timeline = $service->getTimeline($this->architecture);

        expect($timeline)->toHaveCount(3);
        expect($timeline[0])->toHaveKeys(['id', 'created_at', 'nodes_count', 'links_count', 'changes']);
    });

    it('gets changed pages between snapshots', function () {
        $baseSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home']],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $targetSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home'],
                    ['id' => 'node-2', 'url' => 'https://example.com/new', 'title' => 'New Page'],
                ],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $service = app(ArchitectureComparisonService::class);
        $changedPages = $service->getChangedPages($baseSnapshot, $targetSnapshot);

        expect($changedPages)->toHaveCount(1);
        expect($changedPages[0]['change_type'])->toBe('added');
        expect($changedPages[0]['url'])->toBe('https://example.com/new');
    });

    it('applies retention policy', function () {
        // Create 10 snapshots
        ArchitectureSnapshot::factory()->count(10)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        expect($this->architecture->snapshots()->count())->toBe(10);

        $service = app(ArchitectureComparisonService::class);
        $deleted = $service->applyRetentionPolicy($this->architecture, [
            'min_snapshots' => 3,
            'max_snapshots' => 5,
            'max_age_days' => 90,
        ]);

        expect($deleted)->toBe(5);
        expect($this->architecture->snapshots()->count())->toBe(5);
    });

    it('generates comparison report', function () {
        $baseSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home']],
                'links' => [],
                'metadata' => ['total_nodes' => 1, 'total_links' => 0],
            ],
            'nodes_count' => 1,
            'links_count' => 0,
        ]);

        $targetSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/', 'title' => 'Home'],
                    ['id' => 'node-2', 'url' => 'https://example.com/about', 'title' => 'About'],
                ],
                'links' => [['source' => 'node-1', 'target' => 'node-2']],
                'metadata' => ['total_nodes' => 2, 'total_links' => 1],
            ],
            'nodes_count' => 2,
            'links_count' => 1,
        ]);

        $service = app(ArchitectureComparisonService::class);
        $report = $service->generateReport($baseSnapshot, $targetSnapshot);

        expect($report)->toHaveKeys([
            'base_snapshot',
            'target_snapshot',
            'time_between',
            'summary',
            'metrics',
            'changed_pages',
            'highlights',
        ]);
    });

    it('compares metrics between snapshots', function () {
        $baseSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [],
                'links' => [],
                'metadata' => [
                    'total_nodes' => 10,
                    'total_links' => 15,
                    'max_depth' => 3,
                    'orphan_count' => 2,
                    'error_count' => 1,
                ],
            ],
        ]);

        $targetSnapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [],
                'links' => [],
                'metadata' => [
                    'total_nodes' => 15,
                    'total_links' => 20,
                    'max_depth' => 4,
                    'orphan_count' => 1,
                    'error_count' => 0,
                ],
            ],
        ]);

        $service = app(ArchitectureComparisonService::class);
        $comparison = $service->compare($baseSnapshot, $targetSnapshot);

        expect($comparison['metrics']['total_nodes']['diff'])->toBe(5);
        expect($comparison['metrics']['orphan_count']['diff'])->toBe(-1);
    });
});

describe('VersionHistoryPanel Component', function () {
    it('renders with no snapshots', function () {
        Livewire::test(VersionHistoryPanel::class)
            ->assertStatus(200)
            ->assertSee('No snapshots available');
    });

    it('renders with snapshots', function () {
        ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->assertStatus(200)
            ->assertDontSee('No snapshots available');
    });

    it('lists snapshots', function () {
        ArchitectureSnapshot::factory()->count(3)->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $component = Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id]);

        $snapshots = $component->get('snapshots');
        expect($snapshots)->toHaveCount(3);
    });

    it('selects a snapshot', function () {
        $snapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->call('selectSnapshot', $snapshot->id)
            ->assertSet('selectedSnapshotId', $snapshot->id)
            ->assertDispatched('snapshot-selected');
    });

    it('starts comparison mode', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->set('selectedSnapshotId', $snapshot1->id)
            ->call('startComparison', $snapshot2->id)
            ->assertSet('showComparison', true)
            ->assertSet('compareSnapshotId', $snapshot2->id)
            ->assertDispatched('comparison-started');
    });

    it('cancels comparison', function () {
        Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->set('showComparison', true)
            ->set('compareSnapshotId', 'some-id')
            ->call('cancelComparison')
            ->assertSet('showComparison', false)
            ->assertSet('compareSnapshotId', null)
            ->assertDispatched('comparison-cancelled');
    });

    it('deletes a snapshot', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->call('deleteSnapshot', $snapshot1->id);

        expect(ArchitectureSnapshot::find($snapshot1->id))->toBeNull();
        expect(ArchitectureSnapshot::find($snapshot2->id))->not->toBeNull();
    });

    it('does not delete last snapshot', function () {
        $snapshot = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->call('deleteSnapshot', $snapshot->id);

        expect(ArchitectureSnapshot::find($snapshot->id))->not->toBeNull();
    });

    it('computes comparison data', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [['id' => 'node-1', 'url' => 'https://example.com/']],
                'links' => [],
                'metadata' => ['total_nodes' => 1],
            ],
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/'],
                    ['id' => 'node-2', 'url' => 'https://example.com/new'],
                ],
                'links' => [],
                'metadata' => ['total_nodes' => 2],
            ],
        ]);

        $component = Livewire::test(VersionHistoryPanel::class, ['architectureId' => $this->architecture->id])
            ->set('selectedSnapshotId', $snapshot2->id)
            ->set('compareSnapshotId', $snapshot1->id)
            ->set('showComparison', true);

        $comparison = $component->get('comparison');
        expect($comparison)->not->toBeNull();
        expect($comparison)->toHaveKey('summary');
    });
});

describe('ComparisonView Component', function () {
    it('renders without snapshots selected', function () {
        Livewire::test(ComparisonView::class)
            ->assertStatus(200)
            ->assertSee('Select two snapshots to compare');
    });

    it('renders with snapshots selected', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => ['nodes' => [], 'links' => [], 'metadata' => []],
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => ['nodes' => [], 'links' => [], 'metadata' => []],
        ]);

        Livewire::test(ComparisonView::class, ['architectureId' => $this->architecture->id])
            ->set('baseSnapshotId', $snapshot1->id)
            ->set('targetSnapshotId', $snapshot2->id)
            ->assertStatus(200)
            ->assertDontSee('Select two snapshots to compare');
    });

    it('switches view modes', function () {
        Livewire::test(ComparisonView::class)
            ->assertSet('viewMode', 'side-by-side')
            ->call('setViewMode', 'overlay')
            ->assertSet('viewMode', 'overlay')
            ->call('setViewMode', 'timeline')
            ->assertSet('viewMode', 'timeline')
            ->call('setViewMode', 'side-by-side')
            ->assertSet('viewMode', 'side-by-side');
    });

    it('handles comparison started event', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(ComparisonView::class, ['architectureId' => $this->architecture->id])
            ->dispatch('comparison-started', [
                'baseId' => $snapshot1->id,
                'targetId' => $snapshot2->id,
            ])
            ->assertSet('baseSnapshotId', $snapshot1->id)
            ->assertSet('targetSnapshotId', $snapshot2->id);
    });

    it('handles comparison cancelled event', function () {
        Livewire::test(ComparisonView::class)
            ->set('baseSnapshotId', 'some-id')
            ->set('targetSnapshotId', 'other-id')
            ->dispatch('comparison-cancelled')
            ->assertSet('baseSnapshotId', null)
            ->assertSet('targetSnapshotId', null);
    });

    it('toggles node filters', function () {
        Livewire::test(ComparisonView::class)
            ->assertSet('showAddedNodes', true)
            ->call('toggleFilter', 'added')
            ->assertSet('showAddedNodes', false)
            ->call('toggleFilter', 'added')
            ->assertSet('showAddedNodes', true);
    });

    it('swaps snapshots', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(ComparisonView::class, ['architectureId' => $this->architecture->id])
            ->set('baseSnapshotId', $snapshot1->id)
            ->set('targetSnapshotId', $snapshot2->id)
            ->call('swapSnapshots')
            ->assertSet('baseSnapshotId', $snapshot2->id)
            ->assertSet('targetSnapshotId', $snapshot1->id);
    });

    it('computes graph data for overlay view', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [['id' => 'node-1', 'url' => 'https://example.com/']],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'snapshot_data' => [
                'nodes' => [
                    ['id' => 'node-1', 'url' => 'https://example.com/'],
                    ['id' => 'node-2', 'url' => 'https://example.com/new'],
                ],
                'links' => [],
                'metadata' => [],
            ],
        ]);

        $component = Livewire::test(ComparisonView::class, ['architectureId' => $this->architecture->id])
            ->set('baseSnapshotId', $snapshot1->id)
            ->set('targetSnapshotId', $snapshot2->id);

        $graphData = $component->get('graphData');
        expect($graphData)->toHaveKeys(['nodes', 'links']);
        expect($graphData['nodes'])->not->toBeEmpty();
    });

    it('sets timeline position', function () {
        Livewire::test(ComparisonView::class)
            ->assertSet('timelinePosition', 100)
            ->call('setTimelinePosition', 50)
            ->assertSet('timelinePosition', 50)
            ->call('setTimelinePosition', -10)
            ->assertSet('timelinePosition', 0)
            ->call('setTimelinePosition', 150)
            ->assertSet('timelinePosition', 100);
    });

    it('selects snapshots programmatically', function () {
        $snapshot1 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        $snapshot2 = ArchitectureSnapshot::factory()->create([
            'site_architecture_id' => $this->architecture->id,
        ]);

        Livewire::test(ComparisonView::class, ['architectureId' => $this->architecture->id])
            ->call('selectSnapshots', $snapshot1->id, $snapshot2->id)
            ->assertSet('baseSnapshotId', $snapshot1->id)
            ->assertSet('targetSnapshotId', $snapshot2->id);
    });
});
