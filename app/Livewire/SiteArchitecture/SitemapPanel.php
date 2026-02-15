<?php

namespace App\Livewire\SiteArchitecture;

use App\Models\SiteArchitecture;
use App\Services\Architecture\HtmlSitemapService;
use App\Services\Architecture\SitemapGeneratorService;
use App\Services\Architecture\SitemapValidationService;
use App\Services\Architecture\VisualSitemapService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class SitemapPanel extends Component
{
    #[Reactive]
    public ?string $architectureId = null;

    public string $activeTab = 'generate';

    public string $sitemapFormat = 'xml';

    public string $htmlLayout = 'sections';

    public string $existingSitemapUrl = '';

    public string $existingSitemapContent = '';

    public ?array $validationResult = null;

    public bool $showPreview = false;

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->validationResult = null;
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
    public function stats(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $generatorService = app(SitemapGeneratorService::class);

        return $generatorService->getStats($this->architecture);
    }

    #[Computed]
    public function structureStats(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $visualService = app(VisualSitemapService::class);

        return $visualService->getStructureStats($this->architecture);
    }

    #[Computed]
    public function hierarchy(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $visualService = app(VisualSitemapService::class);

        return $visualService->generateHierarchy($this->architecture);
    }

    #[Computed]
    public function sections(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $visualService = app(VisualSitemapService::class);

        return $visualService->generateSections($this->architecture);
    }

    #[Computed]
    public function generatedXml(): string
    {
        if (! $this->architecture) {
            return '';
        }

        $generatorService = app(SitemapGeneratorService::class);

        return $generatorService->generateXml($this->architecture);
    }

    #[Computed]
    public function generatedHtml(): string
    {
        if (! $this->architecture) {
            return '';
        }

        $htmlService = app(HtmlSitemapService::class);

        if ($this->htmlLayout === 'hierarchy') {
            return $htmlService->generateHierarchicalHtml($this->architecture);
        }

        return $htmlService->generateHtml($this->architecture);
    }

    public function validateFromUrl(): void
    {
        if (! $this->architecture || empty($this->existingSitemapUrl)) {
            return;
        }

        $validationService = app(SitemapValidationService::class);
        $this->validationResult = $validationService->validateFromUrl($this->architecture, $this->existingSitemapUrl);
    }

    public function validateFromContent(): void
    {
        if (! $this->architecture || empty($this->existingSitemapContent)) {
            return;
        }

        $validationService = app(SitemapValidationService::class);
        $this->validationResult = $validationService->validate($this->architecture, $this->existingSitemapContent);
    }

    public function generateReport(): array
    {
        if (! $this->architecture || ! $this->validationResult) {
            return [];
        }

        $validationService = app(SitemapValidationService::class);

        return $validationService->generateReport(
            $this->architecture,
            $this->existingSitemapContent ?: ''
        );
    }

    public function downloadXml(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $xml = $this->generatedXml;

        return response()->streamDownload(function () use ($xml) {
            echo $xml;
        }, 'sitemap.xml', [
            'Content-Type' => 'application/xml',
        ]);
    }

    public function downloadHtml(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $html = $this->generatedHtml;

        return response()->streamDownload(function () use ($html) {
            echo $html;
        }, 'sitemap.html', [
            'Content-Type' => 'text/html',
        ]);
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function clearSitemapValidation(): void
    {
        $this->validationResult = null;
        $this->existingSitemapUrl = '';
        $this->existingSitemapContent = '';
    }

    public function render(): View
    {
        return view('livewire.site-architecture.sitemap-panel');
    }
}
