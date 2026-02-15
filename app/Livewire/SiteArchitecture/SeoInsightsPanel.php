<?php

namespace App\Livewire\SiteArchitecture;

use App\Models\SiteArchitecture;
use App\Services\Architecture\ArchitectureRecommendationService;
use App\Services\Architecture\ArchitectureSeoService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class SeoInsightsPanel extends Component
{
    #[Reactive]
    public ?string $architectureId = null;

    public string $activeTab = 'overview';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    #[Computed]
    public function architecture(): ?SiteArchitecture
    {
        if (! $this->architectureId) {
            return null;
        }

        return SiteArchitecture::find($this->architectureId);
    }

    #[Computed]
    public function seoAnalysis(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $seoService = app(ArchitectureSeoService::class);

        return $seoService->analyze($this->architecture);
    }

    #[Computed]
    public function recommendations(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $recommendationService = app(ArchitectureRecommendationService::class);

        return $recommendationService->generateRecommendations($this->architecture);
    }

    #[Computed]
    public function roadmap(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $recommendationService = app(ArchitectureRecommendationService::class);

        return $recommendationService->getFixRoadmap($this->architecture);
    }

    public function render(): View
    {
        return view('livewire.site-architecture.seo-insights-panel');
    }
}
