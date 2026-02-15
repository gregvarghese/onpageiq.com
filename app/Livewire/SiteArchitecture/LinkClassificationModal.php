<?php

namespace App\Livewire\SiteArchitecture;

use App\Enums\LinkType;
use App\Models\ArchitectureLink;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class LinkClassificationModal extends Component
{
    public bool $isOpen = false;

    public ?string $linkId = null;

    public ?string $selectedLinkType = null;

    #[On('open-link-classification-modal')]
    public function open(string $linkId): void
    {
        $this->linkId = $linkId;
        $this->selectedLinkType = $this->link?->link_type_override?->value ?? $this->link?->link_type->value;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->linkId = null;
        $this->selectedLinkType = null;
    }

    public function save(): void
    {
        if (! $this->link || ! $this->selectedLinkType) {
            return;
        }

        $linkType = LinkType::from($this->selectedLinkType);

        // If selected type matches original, clear the override
        if ($linkType === $this->link->link_type) {
            $this->link->clearOverride();
        } else {
            $this->link->overrideLinkType($linkType);
        }

        $this->dispatch('link-classification-updated', linkId: $this->linkId);
        $this->close();
    }

    public function clearOverride(): void
    {
        if (! $this->link) {
            return;
        }

        $this->link->clearOverride();
        $this->dispatch('link-classification-updated', linkId: $this->linkId);
        $this->close();
    }

    #[Computed]
    public function link(): ?ArchitectureLink
    {
        if (! $this->linkId) {
            return null;
        }

        return ArchitectureLink::with(['sourceNode', 'targetNode'])->find($this->linkId);
    }

    public function getLinkTypes(): array
    {
        return collect(LinkType::cases())
            ->mapWithKeys(fn (LinkType $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.site-architecture.link-classification-modal', [
            'linkTypes' => $this->getLinkTypes(),
        ]);
    }
}
