<?php

namespace App\Livewire\SiteArchitecture;

use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Services\Architecture\ClusteringService;
use App\Services\Architecture\DepthAnalysisService;
use App\Services\Architecture\GraphLayoutService;
use App\Services\Architecture\LinkEquityService;
use App\Services\Architecture\OrphanDetectionService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SiteArchitecturePage extends Component
{
    public Project $project;

    public ?SiteArchitecture $architecture = null;

    public string $viewMode = 'force'; // force, tree, directory

    public string $clusterStrategy = 'path';

    public bool $showExternalLinks = false;

    public bool $showClusters = false;

    public ?string $selectedNodeId = null;

    public array $filters = [
        'minDepth' => null,
        'maxDepth' => null,
        'linkType' => null,
        'status' => null,
    ];

    // Crawl progress tracking
    public bool $isCrawling = false;

    public int $crawledPages = 0;

    public int $totalDiscovered = 0;

    public string $currentCrawlUrl = '';

    public int $crawlProgressPercent = 0;

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->architecture = $project->latestSiteArchitecture;

        // Check if a crawl is currently in progress
        if ($this->architecture && $this->architecture->status->value === 'crawling') {
            $this->isCrawling = true;
        }
    }

    /**
     * Get the listeners for Reverb broadcasts.
     *
     * @return array<string, string>
     */
    public function getListeners(): array
    {
        return [
            "echo-private:project.{$this->project->id},ArchitectureCrawlProgress" => 'handleCrawlProgress',
            "echo-private:project.{$this->project->id},ArchitectureCrawlCompleted" => 'handleCrawlCompleted',
            "echo-private:project.{$this->project->id},ArchitectureCrawlFailed" => 'handleCrawlFailed',
            'node-selected' => 'selectNode',
        ];
    }

    /**
     * Handle crawl progress event.
     */
    public function handleCrawlProgress(array $data): void
    {
        $this->isCrawling = true;
        $this->crawledPages = $data['crawled_pages'] ?? 0;
        $this->totalDiscovered = $data['total_discovered'] ?? 0;
        $this->currentCrawlUrl = $data['current_url'] ?? '';
        $this->crawlProgressPercent = $data['progress_percent'] ?? 0;
    }

    /**
     * Handle crawl completed event.
     */
    public function handleCrawlCompleted(array $data): void
    {
        $this->isCrawling = false;
        $this->crawledPages = 0;
        $this->totalDiscovered = 0;
        $this->currentCrawlUrl = '';
        $this->crawlProgressPercent = 100;

        // Refresh the architecture data
        $this->architecture = $this->project->fresh()->latestSiteArchitecture;

        $this->dispatch('architecture-updated');
    }

    /**
     * Handle crawl failed event.
     */
    public function handleCrawlFailed(array $data): void
    {
        $this->isCrawling = false;
        $this->crawledPages = 0;
        $this->totalDiscovered = 0;
        $this->currentCrawlUrl = '';
        $this->crawlProgressPercent = 0;

        // Refresh the architecture data
        $this->architecture = $this->project->fresh()->latestSiteArchitecture;

        session()->flash('error', 'Architecture crawl failed: '.($data['message'] ?? 'Unknown error'));
    }

    #[Computed]
    public function graphData(): array
    {
        if (! $this->architecture) {
            return ['nodes' => [], 'links' => []];
        }

        $layoutService = app(GraphLayoutService::class);

        if ($this->showExternalLinks) {
            return $layoutService->getD3GraphDataWithExternals($this->architecture);
        }

        return $layoutService->getD3GraphData($this->architecture);
    }

    #[Computed]
    public function clusterData(): array
    {
        if (! $this->architecture || ! $this->showClusters) {
            return ['clusters' => [], 'links' => []];
        }

        $clusterService = app(ClusteringService::class);

        return $clusterService->getD3ClusterData($this->architecture, $this->clusterStrategy);
    }

    #[Computed]
    public function statistics(): array
    {
        if (! $this->architecture) {
            return [];
        }

        $depthService = app(DepthAnalysisService::class);
        $orphanService = app(OrphanDetectionService::class);
        $equityService = app(LinkEquityService::class);

        return [
            'totalNodes' => $this->architecture->total_nodes,
            'totalLinks' => $this->architecture->total_links,
            'maxDepth' => $this->architecture->max_depth,
            'orphanCount' => $this->architecture->orphan_count,
            'errorCount' => $this->architecture->error_count,
            'depthScore' => $depthService->calculateDepthScore($this->architecture),
            'orphanRate' => $orphanService->calculateOrphanRate($this->architecture),
            'equityDistribution' => $equityService->analyzeDistribution($this->architecture),
        ];
    }

    #[Computed]
    public function selectedNode(): ?array
    {
        if (! $this->selectedNodeId || ! $this->architecture) {
            return null;
        }

        $node = $this->architecture->nodes()->find($this->selectedNodeId);
        if (! $node) {
            return null;
        }

        $equityService = app(LinkEquityService::class);

        return [
            'node' => $node,
            'inboundLinks' => $node->inboundLinks()->with('sourceNode')->limit(20)->get(),
            'outboundLinks' => $node->outboundLinks()->with('targetNode')->limit(20)->get(),
            'issues' => $node->issues()->unresolved()->get(),
            'equityFlow' => $equityService->getNodeEquityFlow($node),
        ];
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
    }

    public function setClusterStrategy(string $strategy): void
    {
        $this->clusterStrategy = $strategy;
    }

    public function toggleExternalLinks(): void
    {
        $this->showExternalLinks = ! $this->showExternalLinks;
    }

    public function toggleClusters(): void
    {
        $this->showClusters = ! $this->showClusters;
    }

    public function selectNode(?string $nodeId): void
    {
        $this->selectedNodeId = $nodeId;
    }

    public function startCrawl(): void
    {
        $this->dispatch('open-crawl-config-modal');
    }

    public function refreshArchitecture(): void
    {
        $this->architecture = $this->project->latestSiteArchitecture;
    }

    public function render(): View
    {
        return view('livewire.site-architecture.site-architecture-page');
    }
}
