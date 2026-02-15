<?php

namespace App\Livewire\Accessibility;

use App\Models\AccessibilityAudit;
use App\Models\Project;
use App\Services\Accessibility\RegressionService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegressionTrends extends Component
{
    public Project $project;

    public ?AccessibilityAudit $selectedAudit = null;

    public ?AccessibilityAudit $compareAudit = null;

    public string $activeTab = 'overview';

    public int $trendLimit = 10;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Set the active tab.
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    /**
     * Select an audit for comparison.
     */
    public function selectAudit(string $auditId): void
    {
        $this->selectedAudit = AccessibilityAudit::findOrFail($auditId);
        $this->compareAudit = null;
    }

    /**
     * Select the compare audit.
     */
    public function selectCompareAudit(string $auditId): void
    {
        $this->compareAudit = AccessibilityAudit::findOrFail($auditId);
    }

    /**
     * Clear selection.
     */
    public function clearSelection(): void
    {
        $this->selectedAudit = null;
        $this->compareAudit = null;
    }

    /**
     * Get trend data.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function trends(): array
    {
        $service = app(RegressionService::class);

        return $service->getTrends($this->project, $this->trendLimit);
    }

    /**
     * Get resolution rate.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function resolutionRate(): array
    {
        $service = app(RegressionService::class);

        return $service->getResolutionRate($this->project);
    }

    /**
     * Get persistent issues.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    #[Computed]
    public function persistentIssues(): \Illuminate\Support\Collection
    {
        $service = app(RegressionService::class);

        return $service->getPersistentIssues($this->project);
    }

    /**
     * Get comparison data.
     *
     * @return array<string, mixed>|null
     */
    #[Computed]
    public function comparison(): ?array
    {
        if (! $this->selectedAudit || ! $this->compareAudit) {
            return null;
        }

        $service = app(RegressionService::class);

        return $service->compareAudits($this->selectedAudit, $this->compareAudit);
    }

    /**
     * Get available audits for comparison.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AccessibilityAudit>
     */
    #[Computed]
    public function availableAudits(): \Illuminate\Database\Eloquent\Collection
    {
        return AccessibilityAudit::query()
            ->where('project_id', $this->project->id)
            ->where('status', 'completed')
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get();
    }

    /**
     * Get the summary statistics.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function summary(): array
    {
        $trends = $this->trends;

        if (! $trends['has_data']) {
            return [
                'has_data' => false,
            ];
        }

        return array_merge(['has_data' => true], $trends['summary'] ?? []);
    }

    /**
     * Get score trend class.
     */
    public function getScoreTrendClass(): string
    {
        $trend = $this->summary['score_trend'] ?? 'stable';

        return match ($trend) {
            'improving' => 'text-green-600 dark:text-green-400',
            'declining' => 'text-red-600 dark:text-red-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Get issue trend class.
     */
    public function getIssueTrendClass(): string
    {
        $trend = $this->summary['issue_trend'] ?? 'stable';

        return match ($trend) {
            'improving' => 'text-green-600 dark:text-green-400',
            'declining' => 'text-red-600 dark:text-red-400',
            default => 'text-gray-600 dark:text-gray-400',
        };
    }

    /**
     * Get trend icon.
     */
    public function getTrendIcon(string $trend): string
    {
        return match ($trend) {
            'improving' => 'arrow-trending-up',
            'declining' => 'arrow-trending-down',
            default => 'minus',
        };
    }

    public function render(): View
    {
        return view('livewire.accessibility.regression-trends');
    }
}
