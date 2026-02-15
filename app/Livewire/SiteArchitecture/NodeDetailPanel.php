<?php

namespace App\Livewire\SiteArchitecture;

use App\Models\ArchitectureNode;
use App\Services\Architecture\LinkEquityService;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class NodeDetailPanel extends Component
{
    #[Reactive]
    public ?string $nodeId = null;

    public function close(): void
    {
        $this->dispatch('node-deselected');
    }

    public function viewPage(): void
    {
        if ($this->node) {
            $this->dispatch('open-url', url: $this->node->url);
        }
    }

    public function runScan(): void
    {
        if ($this->node) {
            $this->dispatch('run-scan-for-url', url: $this->node->url);
        }
    }

    #[Computed]
    public function node(): ?ArchitectureNode
    {
        if (! $this->nodeId) {
            return null;
        }

        return ArchitectureNode::with(['siteArchitecture', 'issues'])->find($this->nodeId);
    }

    #[Computed]
    public function inboundLinks(): array
    {
        if (! $this->node) {
            return [];
        }

        return $this->node->inboundLinks()
            ->with('sourceNode')
            ->limit(20)
            ->get()
            ->toArray();
    }

    #[Computed]
    public function outboundLinks(): array
    {
        if (! $this->node) {
            return [];
        }

        return $this->node->outboundLinks()
            ->with('targetNode')
            ->limit(20)
            ->get()
            ->toArray();
    }

    #[Computed]
    public function issues(): array
    {
        if (! $this->node) {
            return [];
        }

        return $this->node->issues()
            ->unresolved()
            ->get()
            ->toArray();
    }

    #[Computed]
    public function equityFlow(): array
    {
        if (! $this->node) {
            return [];
        }

        $equityService = app(LinkEquityService::class);

        return $equityService->getNodeEquityFlow($this->node);
    }

    public function render(): View
    {
        return view('livewire.site-architecture.node-detail-panel');
    }
}
