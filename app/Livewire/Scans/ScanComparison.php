<?php

namespace App\Livewire\Scans;

use App\Models\Scan;
use App\Services\Diff\ScanComparison as ScanComparisonDto;
use App\Services\Diff\ScanDiffService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ScanComparison extends Component
{
    public Scan $currentScan;

    public ?int $baselineScanId = null;

    public ?ScanComparisonDto $comparison = null;

    public string $activeTab = 'summary';

    public function mount(Scan $scan, ?int $baseline = null): void
    {
        $this->authorize('view', $scan->url->project);
        $this->currentScan = $scan;
        $this->baselineScanId = $baseline;

        if ($baseline) {
            $this->compare();
        }
    }

    public function compare(): void
    {
        if (! $this->baselineScanId) {
            return;
        }

        $baselineScan = Scan::find($this->baselineScanId);

        if (! $baselineScan || $baselineScan->url_id !== $this->currentScan->url_id) {
            $this->addError('baseline', 'Invalid baseline scan selected.');

            return;
        }

        $diffService = app(ScanDiffService::class);
        $this->comparison = $diffService->compare(
            $baselineScan->result,
            $this->currentScan->result
        );
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(): View
    {
        $availableBaselines = $this->currentScan->url
            ->scans()
            ->where('id', '!=', $this->currentScan->id)
            ->where('status', 'completed')
            ->whereHas('result')
            ->orderByDesc('completed_at')
            ->limit(20)
            ->get();

        return view('livewire.scans.scan-comparison', [
            'availableBaselines' => $availableBaselines,
        ]);
    }
}
