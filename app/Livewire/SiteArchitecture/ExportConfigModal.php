<?php

namespace App\Livewire\SiteArchitecture;

use App\Jobs\Architecture\ExportArchitectureJob;
use App\Models\SiteArchitecture;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ExportConfigModal extends Component
{
    public ?string $architectureId = null;

    public bool $showModal = false;

    public string $exportFormat = 'svg';

    // SVG Options
    public int $svgWidth = 1200;

    public int $svgHeight = 800;

    public bool $svgIncludeLegend = true;

    public bool $svgIncludeMetadata = true;

    public bool $svgShowLabels = true;

    public string $svgColorScheme = 'status';

    // Mermaid Options
    public string $mermaidDiagramType = 'flowchart';

    public string $mermaidDirection = 'TB';

    public bool $mermaidGroupByDepth = false;

    public int $mermaidMaxLabelLength = 30;

    // Figma Options
    public int $figmaCanvasWidth = 4000;

    public int $figmaCanvasHeight = 3000;

    public bool $figmaIncludeConnections = true;

    // PDF Options
    public bool $pdfIncludeCover = true;

    public bool $pdfIncludeToc = true;

    public bool $pdfIncludeStatistics = true;

    public bool $pdfIncludeNodeList = true;

    public bool $pdfIncludeRecommendations = true;

    public string $pdfPageSize = 'A4';

    public string $pdfOrientation = 'portrait';

    public string $pdfBrandColor = '#3B82F6';

    // Common Options
    public bool $includeErrors = false;

    public bool $includeExternal = false;

    // Export state
    public bool $isExporting = false;

    public ?string $exportJobId = null;

    public ?string $downloadUrl = null;

    public ?string $exportError = null;

    #[Computed]
    public function architecture(): ?SiteArchitecture
    {
        if (! $this->architectureId) {
            return null;
        }

        return SiteArchitecture::find($this->architectureId);
    }

    #[Computed]
    public function formatOptions(): array
    {
        return [
            'svg' => [
                'label' => 'SVG',
                'description' => 'Scalable vector graphics for web or print',
                'icon' => 'photo',
            ],
            'mermaid' => [
                'label' => 'Mermaid',
                'description' => 'Diagram syntax for documentation',
                'icon' => 'code-bracket',
            ],
            'figma' => [
                'label' => 'Figma',
                'description' => 'Import into Figma for design work',
                'icon' => 'paint-brush',
            ],
            'pdf' => [
                'label' => 'PDF Report',
                'description' => 'Comprehensive architecture report',
                'icon' => 'document-text',
            ],
        ];
    }

    #[On('open-export-modal')]
    public function openModal(string $architectureId): void
    {
        $this->architectureId = $architectureId;
        $this->showModal = true;
        $this->resetExportState();
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetExportState();
    }

    public function selectFormat(string $format): void
    {
        $this->exportFormat = $format;
    }

    public function startExport(): void
    {
        if (! $this->architecture) {
            $this->exportError = 'Architecture not found.';

            return;
        }

        $this->isExporting = true;
        $this->exportError = null;
        $this->downloadUrl = null;

        $options = $this->buildExportOptions();

        // Dispatch job and get batch ID for tracking
        $job = new ExportArchitectureJob(
            $this->architecture,
            $this->exportFormat,
            $options
        );

        dispatch($job);

        // For simple exports, generate immediately
        $this->generateExport();
    }

    protected function generateExport(): void
    {
        try {
            $service = $this->getExportService();
            $content = $service->generate();
            $filename = $service->getFilename();
            $mimeType = $service->getMimeType();

            // Store temporarily and create download URL
            $path = 'exports/'.$filename;
            \Storage::disk('local')->put($path, $content);

            $this->downloadUrl = route('architecture.export.download', [
                'path' => $path,
                'mime' => $mimeType,
            ]);

            $this->isExporting = false;
        } catch (\Throwable $e) {
            $this->exportError = 'Export failed: '.$e->getMessage();
            $this->isExporting = false;
        }
    }

    protected function getExportService()
    {
        $options = $this->buildExportOptions();

        return match ($this->exportFormat) {
            'svg' => new \App\Services\Architecture\Export\SvgExportService($this->architecture, $options),
            'mermaid' => new \App\Services\Architecture\Export\MermaidExportService($this->architecture, $options),
            'figma' => new \App\Services\Architecture\Export\FigmaExportService($this->architecture, $options),
            'pdf' => new \App\Services\Architecture\Export\PdfExportService($this->architecture, $options),
            default => throw new \InvalidArgumentException("Unknown export format: {$this->exportFormat}"),
        };
    }

    protected function buildExportOptions(): array
    {
        $common = [
            'include_errors' => $this->includeErrors,
            'include_external' => $this->includeExternal,
        ];

        return match ($this->exportFormat) {
            'svg' => array_merge($common, [
                'width' => $this->svgWidth,
                'height' => $this->svgHeight,
                'include_legend' => $this->svgIncludeLegend,
                'include_metadata' => $this->svgIncludeMetadata,
                'show_labels' => $this->svgShowLabels,
                'color_scheme' => $this->svgColorScheme,
            ]),
            'mermaid' => array_merge($common, [
                'diagram_type' => $this->mermaidDiagramType,
                'direction' => $this->mermaidDirection,
                'group_by_depth' => $this->mermaidGroupByDepth,
                'max_label_length' => $this->mermaidMaxLabelLength,
            ]),
            'figma' => array_merge($common, [
                'canvas_width' => $this->figmaCanvasWidth,
                'canvas_height' => $this->figmaCanvasHeight,
                'include_connections' => $this->figmaIncludeConnections,
            ]),
            'pdf' => array_merge($common, [
                'include_cover' => $this->pdfIncludeCover,
                'include_toc' => $this->pdfIncludeToc,
                'include_statistics' => $this->pdfIncludeStatistics,
                'include_node_list' => $this->pdfIncludeNodeList,
                'include_recommendations' => $this->pdfIncludeRecommendations,
                'page_size' => $this->pdfPageSize,
                'orientation' => $this->pdfOrientation,
                'brand_color' => $this->pdfBrandColor,
            ]),
            default => $common,
        };
    }

    protected function resetExportState(): void
    {
        $this->isExporting = false;
        $this->exportJobId = null;
        $this->downloadUrl = null;
        $this->exportError = null;
    }

    public function render()
    {
        return view('livewire.site-architecture.export-config-modal');
    }
}
