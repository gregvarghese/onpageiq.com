<?php

namespace App\Livewire\Projects;

use App\Jobs\ScanUrlJob;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectDashboard extends Component
{
    public Project $project;

    public string $newUrl = '';

    public string $scanType = 'quick';

    /**
     * Track which sections are collapsed.
     *
     * @var array<string, bool>
     */
    public array $collapsedSections = [
        'dashboard' => false,
        'charts' => false,
        'urls' => false,
        'queue' => false,
        'issues' => false,
        'schedules' => false,
        'timeline' => false,
    ];

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;
    }

    /**
     * Toggle a section's collapsed state.
     */
    public function toggleSection(string $section): void
    {
        if (isset($this->collapsedSections[$section])) {
            $this->collapsedSections[$section] = ! $this->collapsedSections[$section];
        }
    }

    /**
     * Add a new URL to the project.
     */
    public function addUrl(): void
    {
        $this->validate([
            'newUrl' => ['required', 'url', 'max:2048'],
        ]);

        $this->project->urls()->create([
            'url' => $this->newUrl,
            'status' => 'pending',
        ]);

        $this->newUrl = '';
        $this->dispatch('url-added');
    }

    /**
     * Scan a specific URL.
     */
    public function scanUrl(int $urlId): void
    {
        $url = Url::findOrFail($urlId);
        $this->authorize('update', $url->project);

        $url->markAsScanning();

        $scan = Scan::create([
            'url_id' => $url->id,
            'triggered_by_user_id' => Auth::id(),
            'scan_type' => $this->scanType,
            'status' => 'pending',
        ]);

        ScanUrlJob::dispatch($scan);

        $this->dispatch('scan-started', scanId: $scan->id);
    }

    /**
     * Scan selected URLs.
     *
     * @param  array<int>  $urlIds
     */
    public function scanSelected(array $urlIds): void
    {
        $this->authorize('update', $this->project);

        $urls = $this->project->urls()
            ->whereIn('id', $urlIds)
            ->where('status', '!=', 'scanning')
            ->get();

        foreach ($urls as $url) {
            $url->markAsScanning();

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

    #[On('set-scan-type')]
    public function setScanType(string $type): void
    {
        $this->scanType = $type;
    }

    #[On('trigger-scan-all')]
    public function scanAllUrls(): void
    {
        $this->authorize('update', $this->project);

        $urls = $this->project->urls()->where('status', '!=', 'scanning')->get();

        foreach ($urls as $url) {
            $url->markAsScanning();

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

    /**
     * Delete a URL.
     */
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
        $this->project->refresh();
    }

    /**
     * Get dashboard statistics.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function stats(): array
    {
        $urls = $this->project->urls()->with(['latestScan.result.issues'])->get();
        $totalUrls = $urls->count();
        $completedUrls = $urls->where('status', 'completed')->count();
        $scanningUrls = $urls->whereIn('status', ['scanning', 'pending'])->count();

        $totalIssues = 0;
        $errorCount = 0;
        $warningCount = 0;
        $lastScanAt = null;

        foreach ($urls as $url) {
            if ($url->latestScan?->result) {
                $issues = $url->latestScan->result->issues;
                $totalIssues += $issues->count();
                $errorCount += $issues->where('severity', 'error')->count();
                $warningCount += $issues->where('severity', 'warning')->count();
            }

            if ($url->last_scanned_at && (! $lastScanAt || $url->last_scanned_at->gt($lastScanAt))) {
                $lastScanAt = $url->last_scanned_at;
            }
        }

        // Calculate score (100 - weighted issues percentage)
        $score = $totalUrls > 0
            ? max(0, 100 - (($errorCount * 5 + $warningCount * 2) / max(1, $totalUrls)))
            : 100;

        return [
            'score' => round($score),
            'totalUrls' => $totalUrls,
            'completedUrls' => $completedUrls,
            'scanningUrls' => $scanningUrls,
            'totalIssues' => $totalIssues,
            'errorCount' => $errorCount,
            'warningCount' => $warningCount,
            'lastScanAt' => $lastScanAt,
            'totalScans' => Scan::whereIn('url_id', $urls->pluck('id'))->count(),
        ];
    }

    /**
     * Get pending scans in queue.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Scan>
     */
    #[Computed]
    public function pendingScans(): \Illuminate\Database\Eloquent\Collection
    {
        return Scan::whereIn('url_id', $this->project->urls()->pluck('id'))
            ->whereIn('status', ['pending', 'processing'])
            ->with('url')
            ->latest()
            ->get();
    }

    public function render(): View
    {
        $urls = $this->project->urls()
            ->with(['latestScan.result.issues', 'group'])
            ->latest()
            ->get();

        return view('livewire.projects.project-dashboard', [
            'urls' => $urls,
            'urlGroups' => $this->project->urlGroups,
        ]);
    }
}
