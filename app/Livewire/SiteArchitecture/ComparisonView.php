<?php

namespace App\Livewire\SiteArchitecture;

use App\Models\ArchitectureSnapshot;
use App\Models\SiteArchitecture;
use App\Services\Architecture\ArchitectureComparisonService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class ComparisonView extends Component
{
    #[Reactive]
    public ?string $architectureId = null;

    public ?string $baseSnapshotId = null;

    public ?string $targetSnapshotId = null;

    public string $viewMode = 'side-by-side'; // side-by-side, overlay, timeline

    public int $timelinePosition = 100; // 0-100 slider position

    public bool $showAddedNodes = true;

    public bool $showRemovedNodes = true;

    public bool $showChangedNodes = true;

    public bool $showUnchangedNodes = false;

    #[On('comparison-started')]
    public function handleComparisonStarted(array $data): void
    {
        $this->baseSnapshotId = $data['baseId'] ?? null;
        $this->targetSnapshotId = $data['targetId'] ?? null;
    }

    #[On('comparison-cancelled')]
    public function handleComparisonCancelled(): void
    {
        $this->baseSnapshotId = null;
        $this->targetSnapshotId = null;
    }

    #[Computed]
    public function architecture(): ?SiteArchitecture
    {
        if (! $this->architectureId) {
            return null;
        }

        return SiteArchitecture::find($this->architectureId);
    }

    #[Computed]
    public function baseSnapshot(): ?ArchitectureSnapshot
    {
        if (! $this->baseSnapshotId) {
            return null;
        }

        return ArchitectureSnapshot::find($this->baseSnapshotId);
    }

    #[Computed]
    public function targetSnapshot(): ?ArchitectureSnapshot
    {
        if (! $this->targetSnapshotId) {
            return null;
        }

        return ArchitectureSnapshot::find($this->targetSnapshotId);
    }

    #[Computed]
    public function comparison(): ?array
    {
        if (! $this->baseSnapshot || ! $this->targetSnapshot) {
            return null;
        }

        $service = app(ArchitectureComparisonService::class);

        return $service->compare($this->baseSnapshot, $this->targetSnapshot);
    }

    #[Computed]
    public function graphData(): array
    {
        if (! $this->comparison) {
            return ['nodes' => [], 'links' => []];
        }

        $nodes = [];
        $nodeIds = [];

        // Add nodes based on filter settings
        if ($this->showAddedNodes) {
            foreach ($this->comparison['nodes']['added'] as $node) {
                $node['_changeType'] = 'added';
                $nodes[] = $node;
                $nodeIds[$node['id']] = true;
            }
        }

        if ($this->showRemovedNodes) {
            foreach ($this->comparison['nodes']['removed'] as $node) {
                $node['_changeType'] = 'removed';
                $nodes[] = $node;
                $nodeIds[$node['id']] = true;
            }
        }

        if ($this->showChangedNodes) {
            foreach ($this->comparison['nodes']['changed'] as $change) {
                $node = $change['target'];
                $node['_changeType'] = 'changed';
                $node['_changes'] = $change['changes'];
                $nodes[] = $node;
                $nodeIds[$node['id']] = true;
            }
        }

        if ($this->showUnchangedNodes) {
            // Get unchanged nodes from target snapshot
            $targetNodes = collect($this->targetSnapshot->getNodes())->keyBy('id');
            $changedIds = collect($this->comparison['nodes']['changed'])->pluck('id')->toArray();
            $addedIds = collect($this->comparison['nodes']['added'])->pluck('id')->toArray();

            foreach ($targetNodes as $id => $node) {
                if (! isset($nodeIds[$id]) && ! in_array($id, $changedIds) && ! in_array($id, $addedIds)) {
                    $node['_changeType'] = 'unchanged';
                    $nodes[] = $node;
                    $nodeIds[$node['id']] = true;
                }
            }
        }

        // Get links that connect visible nodes
        $links = [];
        $targetLinks = $this->targetSnapshot?->getLinks() ?? [];

        foreach ($targetLinks as $link) {
            $sourceId = $link['source'] ?? null;
            $targetId = $link['target'] ?? null;

            if (isset($nodeIds[$sourceId]) && isset($nodeIds[$targetId])) {
                $links[] = $link;
            }
        }

        return [
            'nodes' => $nodes,
            'links' => $links,
        ];
    }

    #[Computed]
    public function sideBySideData(): array
    {
        if (! $this->baseSnapshot || ! $this->targetSnapshot) {
            return ['base' => [], 'target' => []];
        }

        return [
            'base' => [
                'nodes' => $this->baseSnapshot->getNodes(),
                'links' => $this->baseSnapshot->getLinks(),
                'metadata' => $this->baseSnapshot->getMetadata(),
            ],
            'target' => [
                'nodes' => $this->targetSnapshot->getNodes(),
                'links' => $this->targetSnapshot->getLinks(),
                'metadata' => $this->targetSnapshot->getMetadata(),
            ],
        ];
    }

    #[Computed]
    public function timelineData(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $service = app(ArchitectureComparisonService::class);

        return $service->getTimeline($this->architecture);
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['side-by-side', 'overlay', 'timeline'])) {
            $this->viewMode = $mode;
        }
    }

    public function setTimelinePosition(int $position): void
    {
        $this->timelinePosition = max(0, min(100, $position));
    }

    public function toggleFilter(string $filter): void
    {
        match ($filter) {
            'added' => $this->showAddedNodes = ! $this->showAddedNodes,
            'removed' => $this->showRemovedNodes = ! $this->showRemovedNodes,
            'changed' => $this->showChangedNodes = ! $this->showChangedNodes,
            'unchanged' => $this->showUnchangedNodes = ! $this->showUnchangedNodes,
            default => null,
        };

        unset($this->graphData);
    }

    public function selectSnapshots(string $baseId, string $targetId): void
    {
        $this->baseSnapshotId = $baseId;
        $this->targetSnapshotId = $targetId;

        unset($this->baseSnapshot, $this->targetSnapshot, $this->comparison, $this->graphData);
    }

    public function swapSnapshots(): void
    {
        $temp = $this->baseSnapshotId;
        $this->baseSnapshotId = $this->targetSnapshotId;
        $this->targetSnapshotId = $temp;

        unset($this->baseSnapshot, $this->targetSnapshot, $this->comparison, $this->graphData);
    }

    public function render(): View
    {
        return view('livewire.site-architecture.comparison-view');
    }
}
