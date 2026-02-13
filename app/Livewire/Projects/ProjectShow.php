<?php

namespace App\Livewire\Projects;

use App\Jobs\ScanUrlJob;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectShow extends Component
{
    public Project $project;

    public string $newUrl = '';

    public string $scanType = 'quick';

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function addUrl(): void
    {
        $this->validate([
            'newUrl' => ['required', 'url', 'max:2048'],
        ]);

        $url = $this->project->urls()->create([
            'url' => $this->newUrl,
            'status' => 'pending',
        ]);

        $this->newUrl = '';
        $this->dispatch('url-added');
    }

    public function scanUrl(int $urlId): void
    {
        $url = Url::findOrFail($urlId);
        $this->authorize('update', $url->project);

        $scan = Scan::create([
            'url_id' => $url->id,
            'triggered_by_user_id' => Auth::id(),
            'scan_type' => $this->scanType,
            'status' => 'pending',
        ]);

        ScanUrlJob::dispatch($scan);

        $this->dispatch('scan-started', scanId: $scan->id);
    }

    public function scanAllUrls(): void
    {
        $this->authorize('update', $this->project);

        $urls = $this->project->urls()->where('status', '!=', 'scanning')->get();

        foreach ($urls as $url) {
            $scan = Scan::create([
                'url_id' => $url->id,
                'triggered_by_user_id' => Auth::id(),
                'scan_type' => $this->scanType,
                'status' => 'pending',
            ]);

            ScanUrlJob::dispatch($scan);
        }

        $this->dispatch('scans-started', count: $urls->count());
    }

    public function deleteUrl(int $urlId): void
    {
        $url = Url::findOrFail($urlId);
        $this->authorize('update', $url->project);

        $url->delete();
        $this->dispatch('url-deleted');
    }

    #[On('echo-private:organizations.{project.organization_id},scan.completed')]
    public function handleScanCompleted(array $data): void
    {
        // Refresh the component when a scan completes
        $this->project->refresh();
    }

    public function render(): View
    {
        $urls = $this->project->urls()
            ->with(['latestScan.result'])
            ->latest()
            ->get();

        return view('livewire.projects.project-show', [
            'urls' => $urls,
        ]);
    }
}
