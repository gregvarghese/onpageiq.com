<?php

namespace App\Livewire\Scans;

use App\Models\Scan;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ScanResults extends Component
{
    public Scan $scan;

    public string $categoryFilter = '';

    public string $severityFilter = '';

    public function mount(Scan $scan): void
    {
        $this->authorize('view', $scan->url->project);
        $this->scan = $scan;
    }

    public function render(): View
    {
        $result = $this->scan->result;

        $issues = $result?->issues()
            ->when($this->categoryFilter, fn ($q) => $q->where('category', $this->categoryFilter))
            ->when($this->severityFilter, fn ($q) => $q->where('severity', $this->severityFilter))
            ->get() ?? collect();

        $categoryCounts = $result?->issues()
            ->selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray() ?? [];

        $severityCounts = $result?->issues()
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray() ?? [];

        return view('livewire.scans.scan-results', [
            'result' => $result,
            'issues' => $issues,
            'categoryCounts' => $categoryCounts,
            'severityCounts' => $severityCounts,
            'scores' => $result?->scores ?? [],
        ]);
    }
}
