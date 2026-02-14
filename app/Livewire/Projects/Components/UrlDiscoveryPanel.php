<?php

namespace App\Livewire\Projects\Components;

use App\Models\DiscoveredUrl;
use App\Models\Project;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class UrlDiscoveryPanel extends Component
{
    use WithPagination;

    public Project $project;

    public bool $showPanel = false;

    /**
     * Selected URL IDs for bulk actions.
     *
     * @var array<int>
     */
    public array $selectedUrls = [];

    /**
     * Filter by status.
     */
    public string $statusFilter = 'pending';

    /**
     * Search term.
     */
    public string $search = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Toggle the panel visibility.
     */
    public function togglePanel(): void
    {
        $this->showPanel = ! $this->showPanel;
        if (! $this->showPanel) {
            $this->selectedUrls = [];
        }
    }

    /**
     * Get discovered URLs with filters.
     */
    #[Computed]
    public function discoveredUrls()
    {
        $query = DiscoveredUrl::where('project_id', $this->project->id);

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->search) {
            $query->where('url', 'like', '%'.$this->search.'%');
        }

        return $query->orderBy('discovered_at', 'desc')->paginate(20);
    }

    /**
     * Get counts by status.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        return [
            'pending' => DiscoveredUrl::where('project_id', $this->project->id)->where('status', 'pending')->count(),
            'approved' => DiscoveredUrl::where('project_id', $this->project->id)->where('status', 'approved')->count(),
            'rejected' => DiscoveredUrl::where('project_id', $this->project->id)->where('status', 'rejected')->count(),
            'all' => DiscoveredUrl::where('project_id', $this->project->id)->count(),
        ];
    }

    /**
     * Toggle URL selection.
     */
    public function toggleSelection(int $id): void
    {
        if (in_array($id, $this->selectedUrls)) {
            $this->selectedUrls = array_values(array_diff($this->selectedUrls, [$id]));
        } else {
            $this->selectedUrls[] = $id;
        }
    }

    /**
     * Select all visible URLs.
     */
    public function selectAll(): void
    {
        $this->selectedUrls = $this->discoveredUrls->pluck('id')->toArray();
    }

    /**
     * Deselect all URLs.
     */
    public function deselectAll(): void
    {
        $this->selectedUrls = [];
    }

    /**
     * Approve a single URL.
     */
    public function approveUrl(int $id): void
    {
        $this->authorize('update', $this->project);

        $discovered = DiscoveredUrl::findOrFail($id);
        $discovered->approve(auth()->user());

        $this->dispatch('url-approved');
        session()->flash('message', 'URL approved and added to project.');
    }

    /**
     * Reject a single URL.
     */
    public function rejectUrl(int $id, ?string $reason = null): void
    {
        $this->authorize('update', $this->project);

        $discovered = DiscoveredUrl::findOrFail($id);
        $discovered->reject(auth()->user(), $reason);

        $this->dispatch('url-rejected');
        session()->flash('message', 'URL rejected.');
    }

    /**
     * Bulk approve selected URLs.
     */
    public function bulkApprove(): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedUrls)) {
            return;
        }

        $urls = DiscoveredUrl::whereIn('id', $this->selectedUrls)
            ->where('status', 'pending')
            ->get();

        foreach ($urls as $url) {
            $url->approve(auth()->user());
        }

        $count = $urls->count();
        $this->selectedUrls = [];

        $this->dispatch('urls-approved', count: $count);
        session()->flash('message', "{$count} URL(s) approved and added to project.");
    }

    /**
     * Bulk reject selected URLs.
     */
    public function bulkReject(): void
    {
        $this->authorize('update', $this->project);

        if (empty($this->selectedUrls)) {
            return;
        }

        $urls = DiscoveredUrl::whereIn('id', $this->selectedUrls)
            ->where('status', 'pending')
            ->get();

        foreach ($urls as $url) {
            $url->reject(auth()->user(), 'Bulk rejected');
        }

        $count = $urls->count();
        $this->selectedUrls = [];

        $this->dispatch('urls-rejected', count: $count);
        session()->flash('message', "{$count} URL(s) rejected.");
    }

    /**
     * Delete rejected URLs permanently.
     */
    public function deleteRejected(): void
    {
        $this->authorize('update', $this->project);

        $count = DiscoveredUrl::where('project_id', $this->project->id)
            ->where('status', 'rejected')
            ->delete();

        session()->flash('message', "{$count} rejected URL(s) deleted.");
    }

    /**
     * Re-queue a rejected URL for review.
     */
    public function requeueUrl(int $id): void
    {
        $this->authorize('update', $this->project);

        $discovered = DiscoveredUrl::findOrFail($id);
        $discovered->update([
            'status' => 'pending',
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
        ]);

        session()->flash('message', 'URL re-queued for review.');
    }

    /**
     * Refresh URL discovery (trigger crawl job).
     */
    #[On('refresh-discovery')]
    public function refreshDiscovery(): void
    {
        // This would dispatch a job to crawl for new URLs
        $this->dispatch('discovery-started');
        session()->flash('message', 'URL discovery started. New URLs will appear here.');
    }

    public function render(): View
    {
        return view('livewire.projects.components.url-discovery-panel');
    }
}
