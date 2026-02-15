<?php

namespace App\Livewire\SiteArchitecture;

use App\Models\ArchitectureSnapshot;
use App\Models\SiteArchitecture;
use App\Services\Architecture\ArchitectureComparisonService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class VersionHistoryPanel extends Component
{
    #[Reactive]
    public ?string $architectureId = null;

    public ?string $selectedSnapshotId = null;

    public ?string $compareSnapshotId = null;

    public bool $showComparison = false;

    public function mount(): void
    {
        // Select the latest snapshot by default
        if ($this->architecture && $this->snapshots) {
            $latest = collect($this->snapshots)->first();
            if ($latest) {
                $this->selectedSnapshotId = $latest['id'];
            }
        }
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
    public function snapshots(): array
    {
        if (! $this->architecture) {
            return [];
        }

        return $this->architecture->snapshots()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (ArchitectureSnapshot $snapshot) => [
                'id' => $snapshot->id,
                'created_at' => $snapshot->created_at->toIso8601String(),
                'created_at_human' => $snapshot->created_at->diffForHumans(),
                'nodes_count' => $snapshot->nodes_count,
                'links_count' => $snapshot->links_count,
                'has_changes' => $snapshot->hasChangesSummary(),
                'added' => $snapshot->getAddedCount(),
                'removed' => $snapshot->getRemovedCount(),
            ])
            ->toArray();
    }

    #[Computed]
    public function timeline(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $service = app(ArchitectureComparisonService::class);

        return $service->getTimeline($this->architecture);
    }

    #[Computed]
    public function comparison(): ?array
    {
        if (! $this->showComparison || ! $this->selectedSnapshotId || ! $this->compareSnapshotId) {
            return null;
        }

        $base = ArchitectureSnapshot::find($this->compareSnapshotId);
        $target = ArchitectureSnapshot::find($this->selectedSnapshotId);

        if (! $base || ! $target) {
            return null;
        }

        $service = app(ArchitectureComparisonService::class);

        return $service->generateReport($base, $target);
    }

    public function selectSnapshot(string $snapshotId): void
    {
        $this->selectedSnapshotId = $snapshotId;
        $this->showComparison = false;
        $this->compareSnapshotId = null;

        $this->dispatch('snapshot-selected', snapshotId: $snapshotId);
    }

    public function startComparison(string $snapshotId): void
    {
        if ($this->selectedSnapshotId === $snapshotId) {
            return;
        }

        $this->compareSnapshotId = $snapshotId;
        $this->showComparison = true;

        $this->dispatch('comparison-started', [
            'baseId' => $this->compareSnapshotId,
            'targetId' => $this->selectedSnapshotId,
        ]);
    }

    public function cancelComparison(): void
    {
        $this->showComparison = false;
        $this->compareSnapshotId = null;

        $this->dispatch('comparison-cancelled');
    }

    public function restoreSnapshot(string $snapshotId): void
    {
        $snapshot = ArchitectureSnapshot::find($snapshotId);

        if (! $snapshot || ! $this->architecture) {
            return;
        }

        $this->dispatch('snapshot-restore-requested', snapshotId: $snapshotId);
    }

    public function deleteSnapshot(string $snapshotId): void
    {
        $snapshot = ArchitectureSnapshot::find($snapshotId);

        if (! $snapshot) {
            return;
        }

        // Don't delete if it's the only snapshot
        if ($this->architecture && $this->architecture->snapshots()->count() <= 1) {
            return;
        }

        $snapshot->delete();

        // Reset selection if deleted snapshot was selected
        if ($this->selectedSnapshotId === $snapshotId) {
            $this->selectedSnapshotId = null;
        }

        if ($this->compareSnapshotId === $snapshotId) {
            $this->cancelComparison();
        }

        unset($this->snapshots);
    }

    public function render(): View
    {
        return view('livewire.site-architecture.version-history-panel');
    }
}
