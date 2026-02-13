<?php

namespace App\Livewire\Scans;

use App\Models\ScanResult;
use Illuminate\View\View;
use Livewire\Component;

class IssueList extends Component
{
    public ScanResult $scanResult;

    public string $category = '';

    public string $severity = '';

    public function mount(ScanResult $scanResult): void
    {
        $this->scanResult = $scanResult;
    }

    public function filterByCategory(string $category): void
    {
        $this->category = $this->category === $category ? '' : $category;
    }

    public function filterBySeverity(string $severity): void
    {
        $this->severity = $this->severity === $severity ? '' : $severity;
    }

    public function clearFilters(): void
    {
        $this->category = '';
        $this->severity = '';
    }

    public function render(): View
    {
        $issues = $this->scanResult->issues()
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->severity, fn ($q) => $q->where('severity', $this->severity))
            ->orderByRaw("CASE severity WHEN 'error' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->get();

        return view('livewire.scans.issue-list', [
            'issues' => $issues,
        ]);
    }
}
