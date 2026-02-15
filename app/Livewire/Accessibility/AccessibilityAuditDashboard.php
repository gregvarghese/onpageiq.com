<?php

namespace App\Livewire\Accessibility;

use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use App\Enums\WcagLevel;
use App\Jobs\RunAccessibilityAuditJob;
use App\Models\AccessibilityAudit;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class AccessibilityAuditDashboard extends Component
{
    public Project $project;

    public ?AccessibilityAudit $selectedAudit = null;

    public string $wcagLevelTarget = 'AA';

    public string $categoryFilter = '';

    public string $statusFilter = '';

    public bool $showRunModal = false;

    public function mount(Project $project): void
    {
        $this->authorize('view', $project);
        $this->project = $project;

        // Load the latest audit by default
        $this->selectedAudit = $project->accessibilityAudits()
            ->latest()
            ->first();
    }

    /**
     * Start a new accessibility audit.
     */
    public function runAudit(): void
    {
        $this->authorize('update', $this->project);

        $audit = AccessibilityAudit::create([
            'project_id' => $this->project->id,
            'status' => AuditStatus::Pending,
            'wcag_level_target' => WcagLevel::from($this->wcagLevelTarget),
            'triggered_by_user_id' => Auth::id(),
        ]);

        RunAccessibilityAuditJob::dispatch($audit);

        $this->selectedAudit = $audit;
        $this->showRunModal = false;

        $this->dispatch('audit-started', auditId: $audit->id);
    }

    /**
     * Select an audit to view.
     */
    public function selectAudit(string $auditId): void
    {
        $this->selectedAudit = AccessibilityAudit::findOrFail($auditId);
    }

    /**
     * Set the category filter.
     */
    public function setCategory(string $category): void
    {
        $this->categoryFilter = $this->categoryFilter === $category ? '' : $category;
    }

    /**
     * Set the status filter.
     */
    public function setStatus(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
    }

    /**
     * Handle audit progress update from broadcast.
     */
    #[On('echo-private:projects.{project.id},accessibility-audit.progress')]
    public function handleAuditProgress(array $data): void
    {
        if ($this->selectedAudit && $this->selectedAudit->id === $data['audit_id']) {
            $this->selectedAudit->refresh();
        }
    }

    /**
     * Handle audit completion from broadcast.
     */
    #[On('echo-private:projects.{project.id},accessibility-audit.completed')]
    public function handleAuditCompleted(array $data): void
    {
        if ($this->selectedAudit && $this->selectedAudit->id === $data['audit_id']) {
            $this->selectedAudit->refresh();
        }
    }

    /**
     * Get all audits for this project.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, AccessibilityAudit>
     */
    #[Computed]
    public function audits(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->accessibilityAudits()
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Get the filtered checks for the selected audit.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\AuditCheck>|null
     */
    #[Computed]
    public function filteredChecks(): ?\Illuminate\Database\Eloquent\Collection
    {
        if (! $this->selectedAudit) {
            return null;
        }

        return $this->selectedAudit->checks()
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw("CASE WHEN status = 'fail' THEN 0 WHEN status = 'warning' THEN 1 ELSE 2 END")
            ->get();
    }

    /**
     * Get category scores for the radar chart.
     *
     * @return array<string, float>
     */
    #[Computed]
    public function categoryScores(): array
    {
        if (! $this->selectedAudit) {
            return [];
        }

        $scores = [];
        foreach (AuditCategory::cases() as $category) {
            $scores[$category->value] = $this->selectedAudit->getCategoryScore($category);
        }

        return $scores;
    }

    /**
     * Get counts by status for the current audit.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function statusCounts(): array
    {
        if (! $this->selectedAudit) {
            return [];
        }

        return [
            'pass' => $this->selectedAudit->checks_passed ?? 0,
            'fail' => $this->selectedAudit->checks_failed ?? 0,
            'warning' => $this->selectedAudit->checks()->where('status', 'warning')->count(),
            'not_applicable' => $this->selectedAudit->checks_not_applicable ?? 0,
        ];
    }

    /**
     * Get counts by category for the current audit.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function categoryCounts(): array
    {
        if (! $this->selectedAudit) {
            return [];
        }

        return $this->selectedAudit->checks()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.accessibility.accessibility-audit-dashboard', [
            'wcagLevels' => WcagLevel::cases(),
            'categories' => AuditCategory::cases(),
        ]);
    }
}
