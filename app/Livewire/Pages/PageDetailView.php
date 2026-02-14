<?php

namespace App\Livewire\Pages;

use App\Models\Url;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PageDetailView extends Component
{
    public Url $url;

    public function mount(Url $url): void
    {
        $this->authorize('view', $url->project);
        $this->url = $url->load([
            'latestScan.result.issues',
            'project',
            'screenshots',
            'metrics',
            'brokenLinks',
            'schemaValidations',
        ]);
    }

    /**
     * Get the latest scan for this URL.
     */
    #[Computed]
    public function latestScan(): ?\App\Models\Scan
    {
        return $this->url->latestScan;
    }

    /**
     * Get issues grouped by category.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    #[Computed]
    public function issuesByCategory(): array
    {
        $issues = $this->latestScan?->result?->issues ?? collect();

        return [
            'spelling' => $issues->where('category', 'spelling'),
            'grammar' => $issues->where('category', 'grammar'),
            'seo' => $issues->where('category', 'seo'),
            'accessibility' => $issues->where('category', 'accessibility'),
            'readability' => $issues->where('category', 'readability'),
        ];
    }

    /**
     * Get the latest metrics for this URL.
     */
    #[Computed]
    public function metrics(): ?\App\Models\PageMetrics
    {
        return $this->url->metrics()->latest()->first();
    }

    /**
     * Get the latest screenshots.
     *
     * @return array<string, \App\Models\PageScreenshot|null>
     */
    #[Computed]
    public function screenshots(): array
    {
        $screenshots = $this->url->screenshots()->latest()->get();

        return [
            'desktop' => $screenshots->where('viewport', 'desktop')->first(),
            'mobile' => $screenshots->where('viewport', 'mobile')->first(),
        ];
    }

    /**
     * Get Core Web Vitals status.
     *
     * @return array<string, array{value: float|null, status: string}>
     */
    #[Computed]
    public function coreWebVitals(): array
    {
        $metrics = $this->metrics;

        return [
            'lcp' => [
                'value' => $metrics?->lcp_ms,
                'status' => $this->getLcpStatus($metrics?->lcp_ms),
            ],
            'fid' => [
                'value' => $metrics?->fid_ms,
                'status' => $this->getFidStatus($metrics?->fid_ms),
            ],
            'cls' => [
                'value' => $metrics?->cls_score,
                'status' => $this->getClsStatus($metrics?->cls_score),
            ],
        ];
    }

    /**
     * Get readability info.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function readability(): array
    {
        $metrics = $this->metrics;

        return [
            'grade' => $metrics?->readability_grade,
            'ease' => $metrics?->readability_ease,
            'wordCount' => $metrics?->word_count,
        ];
    }

    /**
     * Get broken links for this page.
     */
    #[Computed]
    public function brokenLinks(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->url->brokenLinks()->latest()->get();
    }

    /**
     * Get schema validations for this page.
     */
    #[Computed]
    public function schemaValidations(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->url->schemaValidations()->latest()->get();
    }

    private function getLcpStatus(?float $value): string
    {
        if ($value === null) {
            return 'unknown';
        }

        return $value <= 2500 ? 'good' : ($value <= 4000 ? 'needs-improvement' : 'poor');
    }

    private function getFidStatus(?float $value): string
    {
        if ($value === null) {
            return 'unknown';
        }

        return $value <= 100 ? 'good' : ($value <= 300 ? 'needs-improvement' : 'poor');
    }

    private function getClsStatus(?float $value): string
    {
        if ($value === null) {
            return 'unknown';
        }

        return $value <= 0.1 ? 'good' : ($value <= 0.25 ? 'needs-improvement' : 'poor');
    }

    public function render(): View
    {
        return view('livewire.pages.page-detail-view');
    }
}
