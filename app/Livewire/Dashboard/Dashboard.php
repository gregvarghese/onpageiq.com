<?php

namespace App\Livewire\Dashboard;

use App\Models\Project;
use App\Models\Scan;
use App\Services\Billing\CreditService;
use App\Services\Billing\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    protected CreditService $creditService;

    protected SubscriptionService $subscriptionService;

    public function boot(CreditService $creditService, SubscriptionService $subscriptionService): void
    {
        $this->creditService = $creditService;
        $this->subscriptionService = $subscriptionService;
    }

    public function render(): View
    {
        $user = Auth::user();
        $organization = $user->organization;

        // Get recent projects
        $recentProjects = Project::query()
            ->where('organization_id', $organization->id)
            ->withCount(['urls', 'urls as issues_count' => function ($query) {
                $query->whereHas('scans', function ($q) {
                    $q->where('status', 'completed');
                });
            }])
            ->latest()
            ->limit(4)
            ->get();

        // Get recent scans
        $recentScans = Scan::query()
            ->whereHas('url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->with(['url:id,url,project_id', 'url.project:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        // Get scan statistics
        $scanStats = $this->getScanStats($organization);

        // Get credit usage
        $creditUsage = $this->creditService->getUsageStats($organization);

        // Get subscription info
        $tierConfig = $this->subscriptionService->getOrganizationTier($organization);

        return view('livewire.dashboard.dashboard', [
            'organization' => $organization,
            'recentProjects' => $recentProjects,
            'recentScans' => $recentScans,
            'scanStats' => $scanStats,
            'creditUsage' => $creditUsage,
            'tierConfig' => $tierConfig,
        ]);
    }

    /**
     * Get scan statistics for the organization.
     *
     * @return array<string, mixed>
     */
    protected function getScanStats($organization): array
    {
        $projectIds = $organization->projects()->pluck('id');

        $thirtyDaysAgo = now()->subDays(30);

        $totalScans = Scan::whereHas('url', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $completedScans = Scan::whereHas('url', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->where('status', 'completed')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        $failedScans = Scan::whereHas('url', fn ($q) => $q->whereIn('project_id', $projectIds))
            ->where('status', 'failed')
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->count();

        // Weekly scan counts for chart
        $weeklyCounts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Scan::whereHas('url', fn ($q) => $q->whereIn('project_id', $projectIds))
                ->whereDate('created_at', $date)
                ->count();
            $weeklyCounts[] = [
                'date' => $date->format('M j'),
                'count' => $count,
            ];
        }

        return [
            'total' => $totalScans,
            'completed' => $completedScans,
            'failed' => $failedScans,
            'success_rate' => $totalScans > 0 ? round(($completedScans / $totalScans) * 100) : 0,
            'weekly' => $weeklyCounts,
        ];
    }
}
