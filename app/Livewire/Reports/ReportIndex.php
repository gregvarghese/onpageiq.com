<?php

namespace App\Livewire\Reports;

use App\Models\Issue;
use App\Models\Project;
use App\Models\Scan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ReportIndex extends Component
{
    use WithPagination;

    public string $dateRange = '30';

    public ?int $projectFilter = null;

    public string $severityFilter = '';

    public string $categoryFilter = '';

    public function updatedDateRange(): void
    {
        $this->resetPage();
    }

    public function updatedProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSeverityFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, int>
     */
    public function getStatisticsProperty(): array
    {
        $user = Auth::user();
        $organization = $user->organization;

        $dateLimit = now()->subDays((int) $this->dateRange);

        $baseQuery = Scan::query()
            ->whereHas('url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->where('created_at', '>=', $dateLimit);

        $totalScans = (clone $baseQuery)->count();
        $completedScans = (clone $baseQuery)->where('status', 'completed')->count();

        $totalIssues = Issue::query()
            ->whereHas('scanResult.scan.url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->whereHas('scanResult.scan', function ($query) use ($dateLimit) {
                $query->where('created_at', '>=', $dateLimit);
            })
            ->count();

        $errorCount = Issue::query()
            ->whereHas('scanResult.scan.url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->whereHas('scanResult.scan', function ($query) use ($dateLimit) {
                $query->where('created_at', '>=', $dateLimit);
            })
            ->where('severity', 'error')
            ->count();

        return [
            'totalScans' => $totalScans,
            'completedScans' => $completedScans,
            'totalIssues' => $totalIssues,
            'errorCount' => $errorCount,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getIssuesByCategoryProperty(): array
    {
        $user = Auth::user();
        $organization = $user->organization;
        $dateLimit = now()->subDays((int) $this->dateRange);

        $results = Issue::query()
            ->select('category', DB::raw('count(*) as count'))
            ->whereHas('scanResult.scan.url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->whereHas('scanResult.scan', function ($query) use ($dateLimit) {
                $query->where('created_at', '>=', $dateLimit);
            })
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        return [
            'spelling' => $results['spelling'] ?? 0,
            'grammar' => $results['grammar'] ?? 0,
            'seo' => $results['seo'] ?? 0,
            'readability' => $results['readability'] ?? 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getIssuesBySeverityProperty(): array
    {
        $user = Auth::user();
        $organization = $user->organization;
        $dateLimit = now()->subDays((int) $this->dateRange);

        $results = Issue::query()
            ->select('severity', DB::raw('count(*) as count'))
            ->whereHas('scanResult.scan.url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->whereHas('scanResult.scan', function ($query) use ($dateLimit) {
                $query->where('created_at', '>=', $dateLimit);
            })
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'error' => $results['error'] ?? 0,
            'warning' => $results['warning'] ?? 0,
            'suggestion' => $results['suggestion'] ?? 0,
        ];
    }

    public function render(): View
    {
        $user = Auth::user();
        $organization = $user->organization;
        $dateLimit = now()->subDays((int) $this->dateRange);

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        // Get recent scans with issues
        $scans = Scan::query()
            ->whereHas('url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
                if ($this->projectFilter) {
                    $query->where('id', $this->projectFilter);
                }
            })
            ->where('status', 'completed')
            ->where('created_at', '>=', $dateLimit)
            ->with(['url.project', 'result.issues'])
            ->when($this->severityFilter || $this->categoryFilter, function ($query) {
                $query->whereHas('result.issues', function ($q) {
                    if ($this->severityFilter) {
                        $q->where('severity', $this->severityFilter);
                    }
                    if ($this->categoryFilter) {
                        $q->where('category', $this->categoryFilter);
                    }
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.reports.report-index', [
            'projects' => $projects,
            'scans' => $scans,
            'organization' => $organization,
        ]);
    }
}
