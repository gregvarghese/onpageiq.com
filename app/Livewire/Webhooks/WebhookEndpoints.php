<?php

namespace App\Livewire\Webhooks;

use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class WebhookEndpoints extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingEndpointId = null;

    public string $url = '';

    public string $description = '';

    /** @var array<string> */
    public array $events = [];

    public bool $isActive = true;

    public function createEndpoint(): void
    {
        $this->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        WebhookEndpoint::create([
            'organization_id' => Auth::user()->organization_id,
            'url' => $this->url,
            'events' => $this->events,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ]);

        $this->resetForm();
        $this->showCreateModal = false;
    }

    public function editEndpoint(int $id): void
    {
        $endpoint = $this->getEndpoint($id);
        if (! $endpoint) {
            return;
        }

        $this->editingEndpointId = $id;
        $this->url = $endpoint->url;
        $this->events = $endpoint->events;
        $this->description = $endpoint->description ?? '';
        $this->isActive = $endpoint->is_active;
        $this->showEditModal = true;
    }

    public function updateEndpoint(): void
    {
        $this->validate([
            'url' => ['required', 'url', 'max:2048'],
            'events' => ['required', 'array', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $endpoint = $this->getEndpoint($this->editingEndpointId);
        if (! $endpoint) {
            return;
        }

        $endpoint->update([
            'url' => $this->url,
            'events' => $this->events,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ]);

        $this->resetForm();
        $this->showEditModal = false;
    }

    public function confirmDelete(int $id): void
    {
        $this->editingEndpointId = $id;
        $this->showDeleteModal = true;
    }

    public function deleteEndpoint(): void
    {
        $endpoint = $this->getEndpoint($this->editingEndpointId);
        $endpoint?->delete();

        $this->editingEndpointId = null;
        $this->showDeleteModal = false;
    }

    public function toggleActive(int $id): void
    {
        $endpoint = $this->getEndpoint($id);
        $endpoint?->update(['is_active' => ! $endpoint->is_active]);
    }

    protected function getEndpoint(?int $id): ?WebhookEndpoint
    {
        if (! $id) {
            return null;
        }

        return WebhookEndpoint::query()
            ->where('id', $id)
            ->where('organization_id', Auth::user()->organization_id)
            ->first();
    }

    protected function resetForm(): void
    {
        $this->url = '';
        $this->events = [];
        $this->description = '';
        $this->isActive = true;
        $this->editingEndpointId = null;
    }

    public function render(): View
    {
        $endpoints = WebhookEndpoint::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->withCount('deliveries')
            ->latest()
            ->paginate(10);

        return view('livewire.webhooks.webhook-endpoints', [
            'endpoints' => $endpoints,
            'availableEvents' => WebhookEndpoint::ALL_EVENTS,
        ]);
    }
}
