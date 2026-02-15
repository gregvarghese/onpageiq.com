<?php

namespace App\Livewire\SiteArchitecture;

use App\Enums\LinkType;
use App\Enums\NodeStatus;
use Illuminate\View\View;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class ArchitectureFilters extends Component
{
    #[Modelable]
    public array $filters = [
        'minDepth' => null,
        'maxDepth' => null,
        'linkType' => null,
        'status' => null,
        'urlPattern' => '',
        'showOrphans' => false,
        'showDeep' => false,
        'showErrors' => false,
    ];

    public bool $isExpanded = false;

    public function toggleExpanded(): void
    {
        $this->isExpanded = ! $this->isExpanded;
    }

    public function resetFilters(): void
    {
        $this->filters = [
            'minDepth' => null,
            'maxDepth' => null,
            'linkType' => null,
            'status' => null,
            'urlPattern' => '',
            'showOrphans' => false,
            'showDeep' => false,
            'showErrors' => false,
        ];

        $this->dispatch('filters-updated', filters: $this->filters);
    }

    public function applyFilters(): void
    {
        $this->dispatch('filters-updated', filters: $this->filters);
    }

    public function getLinkTypes(): array
    {
        return collect(LinkType::cases())
            ->mapWithKeys(fn (LinkType $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function getNodeStatuses(): array
    {
        return collect(NodeStatus::cases())
            ->mapWithKeys(fn (NodeStatus $status) => [$status->value => $status->label()])
            ->toArray();
    }

    public function hasActiveFilters(): bool
    {
        return $this->filters['minDepth'] !== null
            || $this->filters['maxDepth'] !== null
            || $this->filters['linkType'] !== null
            || $this->filters['status'] !== null
            || $this->filters['urlPattern'] !== ''
            || $this->filters['showOrphans']
            || $this->filters['showDeep']
            || $this->filters['showErrors'];
    }

    public function render(): View
    {
        return view('livewire.site-architecture.architecture-filters', [
            'linkTypes' => $this->getLinkTypes(),
            'nodeStatuses' => $this->getNodeStatuses(),
            'hasActiveFilters' => $this->hasActiveFilters(),
        ]);
    }
}
