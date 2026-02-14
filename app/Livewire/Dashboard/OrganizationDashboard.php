<?php

namespace App\Livewire\Dashboard;

use App\Models\Issue;
use App\Models\Organization;
use App\Models\Scan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrganizationDashboard extends Component
{
    public Organization $organization;

    public function mount(): void
    {
        $this->organization = Auth::user()->organization;
        $this->authorize('view', $this->organization);
    }

    /**
     * Get issue counts overview.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function issueCounts(): array
    {
        $projectIds = $this->organization->projects()->pluck('id');
        $urlIds = \App\Models\Url::whereIn('project_id', $projectIds)->pluck('id');

        $openIssues = Issue::query()
            ->whereHas('result.scan', function ($q) use ($urlIds) {
                $q->whereIn('url_id', $urlIds);
            })
            ->whereDoesntHave('assignment', function ($q) {
                $q->whereIn('status', ['resolved', 'dismissed']);
            })
            ->get();

        $byProject = [];
        foreach ($this->organization->projects as $project) {
            $projectUrlIds = $project->urls()->pluck('id');
            $count = Issue::query()
                ->whereHas('result.scan', function ($q) use ($projectUrlIds) {
                    $q->whereIn('url_id', $projectUrlIds);
                })
                ->whereDoesntHave('assignment', function ($q) {
                    $q->whereIn('status', ['resolved', 'dismissed']);
                })
                ->count();
            $byProject[$project->id] = [
                'name' => $project->name,
                'count' => $count,
            ];
        }

        return [
            'total' => $openIssues->count(),
            'errors' => $openIssues->where('severity', 'error')->count(),
            'warnings' => $openIssues->where('severity', 'warning')->count(),
            'suggestions' => $openIssues->where('severity', 'suggestion')->count(),
            'byProject' => $byProject,
        ];
    }

    /**
     * Get credit usage stats.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function creditUsage(): array
    {
        $balance = $this->organization->credit_balance ?? 0;
        $monthlyUsage = $this->organization->creditTransactions()
            ->where('created_at', '>=', now()->startOfMonth())
            ->where('amount', '<', 0)
            ->sum('amount');

        $lastMonthUsage = $this->organization->creditTransactions()
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->where('amount', '<', 0)
            ->sum('amount');

        // Calculate burn rate (average daily usage this month)
        $daysThisMonth = now()->day;
        $dailyBurnRate = $daysThisMonth > 0 ? abs($monthlyUsage) / $daysThisMonth : 0;

        // Estimate days until credits run out
        $daysRemaining = $dailyBurnRate > 0 ? floor($balance / $dailyBurnRate) : null;

        return [
            'balance' => $balance,
            'monthlyUsage' => abs($monthlyUsage),
            'lastMonthUsage' => abs($lastMonthUsage),
            'dailyBurnRate' => round($dailyBurnRate, 1),
            'daysRemaining' => $daysRemaining,
            'lowBalance' => $balance < ($dailyBurnRate * 7), // Less than 7 days worth
        ];
    }

    /**
     * Get recent team activity.
     */
    #[Computed]
    public function recentActivity(): \Illuminate\Support\Collection
    {
        $projectIds = $this->organization->projects()->pluck('id');
        $urlIds = \App\Models\Url::whereIn('project_id', $projectIds)->pluck('id');

        // Get recent scans
        $recentScans = Scan::whereIn('url_id', $urlIds)
            ->with(['url.project', 'triggeredBy'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($scan) => [
                'type' => 'scan',
                'message' => 'Scanned '.(parse_url($scan->url->url, PHP_URL_PATH) ?: '/'),
                'project' => $scan->url->project->name,
                'user' => $scan->triggeredBy?->name ?? 'System',
                'status' => $scan->status,
                'created_at' => $scan->created_at,
            ]);

        // Get recent issue state changes
        $recentStateChanges = \App\Models\IssueStateChange::query()
            ->whereHas('issue.result.scan', function ($q) use ($urlIds) {
                $q->whereIn('url_id', $urlIds);
            })
            ->with(['user', 'issue'])
            ->latest()
            ->take(5)
            ->get()
            ->map(fn ($change) => [
                'type' => 'state_change',
                'message' => 'Moved issue to '.ucfirst(str_replace('_', ' ', $change->to_state)),
                'project' => null,
                'user' => $change->user?->name ?? 'Unknown',
                'status' => $change->to_state,
                'created_at' => $change->created_at,
            ]);

        return $recentScans->concat($recentStateChanges)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
    }

    /**
     * Get scheduled scans for calendar.
     */
    #[Computed]
    public function scheduledScans(): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\ScanSchedule::query()
            ->whereHas('project', function ($q) {
                $q->where('organization_id', $this->organization->id);
            })
            ->where('is_active', true)
            ->with('project')
            ->get();
    }

    /**
     * Get active alerts.
     *
     * @return array<array{type: string, severity: string, message: string}>
     */
    #[Computed]
    public function alerts(): array
    {
        $alerts = [];

        // Check credit balance
        $creditUsage = $this->creditUsage;
        if ($creditUsage['balance'] <= 0) {
            $alerts[] = [
                'type' => 'credits',
                'severity' => 'error',
                'message' => 'You have no credits remaining. Purchase credits to continue scanning.',
            ];
        } elseif ($creditUsage['lowBalance']) {
            $alerts[] = [
                'type' => 'credits',
                'severity' => 'warning',
                'message' => 'Low credit balance. Approximately '.($creditUsage['daysRemaining'] ?? 0).' days remaining at current usage.',
            ];
        }

        // Check for failed scans
        $projectIds = $this->organization->projects()->pluck('id');
        $urlIds = \App\Models\Url::whereIn('project_id', $projectIds)->pluck('id');
        $failedScans = Scan::whereIn('url_id', $urlIds)
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subDay())
            ->count();

        if ($failedScans > 0) {
            $alerts[] = [
                'type' => 'failed_scans',
                'severity' => 'warning',
                'message' => $failedScans.' scan(s) failed in the last 24 hours.',
            ];
        }

        // Check for critical issues
        $criticalIssues = Issue::query()
            ->whereHas('result.scan', function ($q) use ($urlIds) {
                $q->whereIn('url_id', $urlIds);
            })
            ->where('severity', 'error')
            ->whereDoesntHave('assignment', function ($q) {
                $q->whereIn('status', ['resolved', 'dismissed']);
            })
            ->count();

        if ($criticalIssues >= 10) {
            $alerts[] = [
                'type' => 'critical_issues',
                'severity' => 'warning',
                'message' => $criticalIssues.' critical issues require attention.',
            ];
        }

        return $alerts;
    }

    /**
     * Get trend data for the last 30 days.
     *
     * @return array<string, array>
     */
    #[Computed]
    public function trendData(): array
    {
        $projectIds = $this->organization->projects()->pluck('id');
        $urlIds = \App\Models\Url::whereIn('project_id', $projectIds)->pluck('id');

        $issuesOverTime = [];
        $resolutionsOverTime = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            // Issues created that day
            $issuesOverTime[$date] = Issue::query()
                ->whereHas('result.scan', function ($q) use ($urlIds) {
                    $q->whereIn('url_id', $urlIds);
                })
                ->whereDate('created_at', $date)
                ->count();

            // Issues resolved that day
            $resolutionsOverTime[$date] = \App\Models\IssueStateChange::query()
                ->whereHas('issue.result.scan', function ($q) use ($urlIds) {
                    $q->whereIn('url_id', $urlIds);
                })
                ->where('to_state', 'resolved')
                ->whereDate('created_at', $date)
                ->count();
        }

        return [
            'issues' => $issuesOverTime,
            'resolutions' => $resolutionsOverTime,
        ];
    }

    public function render(): View
    {
        return view('livewire.dashboard.organization-dashboard');
    }
}
