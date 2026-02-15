<?php

namespace App\Livewire\Accessibility;

use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AuditResultsList extends Component
{
    use WithPagination;

    public AccessibilityAudit $audit;

    public string $search = '';

    public string $statusFilter = '';

    public string $wcagLevelFilter = '';

    public string $impactFilter = '';

    public string $categoryFilter = '';

    public string $sortBy = 'impact';

    public string $sortDirection = 'desc';

    public function mount(AccessibilityAudit $audit): void
    {
        $this->audit = $audit;
    }

    /**
     * Update search and reset pagination.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle sort direction or change sort column.
     */
    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->wcagLevelFilter = '';
        $this->impactFilter = '';
        $this->categoryFilter = '';
        $this->resetPage();
    }

    /**
     * Get paginated checks with filters applied.
     */
    #[Computed]
    public function checks(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->audit->checks()
            ->when($this->search, function ($q) {
                $q->where(function ($q) {
                    $q->where('criterion_id', 'like', "%{$this->search}%")
                        ->orWhere('message', 'like', "%{$this->search}%")
                        ->orWhere('element_selector', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->wcagLevelFilter, fn ($q) => $q->where('wcag_level', $this->wcagLevelFilter))
            ->when($this->impactFilter, fn ($q) => $q->where('impact', $this->impactFilter))
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter));

        // Apply sorting
        $sortColumn = match ($this->sortBy) {
            'impact' => "CASE impact WHEN 'critical' THEN 0 WHEN 'serious' THEN 1 WHEN 'moderate' THEN 2 ELSE 3 END",
            'status' => "CASE status WHEN 'fail' THEN 0 WHEN 'warning' THEN 1 WHEN 'pass' THEN 2 ELSE 3 END",
            'wcag_level' => "CASE wcag_level WHEN 'A' THEN 0 WHEN 'AA' THEN 1 ELSE 2 END",
            default => $this->sortBy,
        };

        if (in_array($this->sortBy, ['impact', 'status', 'wcag_level'])) {
            $query->orderByRaw("{$sortColumn} {$this->sortDirection}");
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate(20);
    }

    /**
     * Get summary statistics.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function summary(): array
    {
        return [
            'total' => $this->audit->checks_total ?? 0,
            'passed' => $this->audit->checks_passed ?? 0,
            'failed' => $this->audit->checks_failed ?? 0,
            'not_applicable' => $this->audit->checks_not_applicable ?? 0,
            'critical' => $this->audit->checks()->where('impact', ImpactLevel::Critical)->count(),
            'serious' => $this->audit->checks()->where('impact', ImpactLevel::Serious)->count(),
        ];
    }

    public function render(): View
    {
        return view('livewire.accessibility.audit-results-list', [
            'statuses' => CheckStatus::cases(),
            'wcagLevels' => WcagLevel::cases(),
            'impactLevels' => ImpactLevel::cases(),
        ]);
    }
}
