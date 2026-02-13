<?php

namespace App\Livewire\Webhooks;

use App\Jobs\SendWebhookJob;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class WebhookDeliveries extends Component
{
    use WithPagination;

    public ?int $endpointId = null;

    public string $statusFilter = 'all';

    public ?array $selectedDelivery = null;

    public function mount(?int $endpoint = null): void
    {
        $this->endpointId = $endpoint;
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function retryDelivery(int $deliveryId): void
    {
        $delivery = $this->getDelivery($deliveryId);

        if (! $delivery || ! $delivery->canRetry()) {
            return;
        }

        $delivery->update([
            'status' => WebhookDelivery::STATUS_PENDING,
            'next_retry_at' => null,
        ]);

        SendWebhookJob::dispatch($delivery);
    }

    public function viewDetails(int $deliveryId): void
    {
        $delivery = $this->getDelivery($deliveryId);

        if (! $delivery) {
            return;
        }

        $this->selectedDelivery = [
            'id' => $delivery->id,
            'event' => $delivery->event,
            'status' => $delivery->status,
            'attempts' => $delivery->attempts,
            'response_status' => $delivery->response_status,
            'response_body' => $delivery->response_body,
            'payload' => $delivery->payload,
            'created_at' => $delivery->created_at->toIso8601String(),
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
        ];
    }

    public function closeDetails(): void
    {
        $this->selectedDelivery = null;
    }

    protected function getDelivery(int $id): ?WebhookDelivery
    {
        return WebhookDelivery::query()
            ->whereHas('endpoint', function ($query) {
                $query->where('organization_id', Auth::user()->organization_id);
            })
            ->where('id', $id)
            ->first();
    }

    public function render(): View
    {
        $query = WebhookDelivery::query()
            ->whereHas('endpoint', function ($query) {
                $query->where('organization_id', Auth::user()->organization_id);
                if ($this->endpointId) {
                    $query->where('id', $this->endpointId);
                }
            })
            ->with('endpoint:id,url')
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->latest();

        $deliveries = $query->paginate(20);

        $endpoint = $this->endpointId
            ? WebhookEndpoint::where('id', $this->endpointId)
                ->where('organization_id', Auth::user()->organization_id)
                ->first()
            : null;

        return view('livewire.webhooks.webhook-deliveries', [
            'deliveries' => $deliveries,
            'endpoint' => $endpoint,
        ]);
    }
}
