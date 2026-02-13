<?php

namespace App\Services\Webhook;

use App\Jobs\SendWebhookJob;
use App\Models\Organization;
use App\Models\WebhookDelivery;
use App\Models\WebhookEndpoint;

class WebhookDispatcher
{
    /**
     * Dispatch a webhook event to all subscribed endpoints.
     *
     * @param  array<string, mixed>  $payload
     */
    public function dispatch(Organization $organization, string $event, array $payload): void
    {
        $endpoints = WebhookEndpoint::query()
            ->where('organization_id', $organization->id)
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            if ($endpoint->subscribesTo($event)) {
                $this->createDelivery($endpoint, $event, $payload);
            }
        }
    }

    /**
     * Create a webhook delivery and queue for sending.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function createDelivery(WebhookEndpoint $endpoint, string $event, array $payload): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'webhook_endpoint_id' => $endpoint->id,
            'event' => $event,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
        ]);

        SendWebhookJob::dispatch($delivery);

        $endpoint->update(['last_triggered_at' => now()]);

        return $delivery;
    }

    /**
     * Dispatch scan started event.
     *
     * @param  array<string, mixed>  $scanData
     */
    public function dispatchScanStarted(Organization $organization, array $scanData): void
    {
        $this->dispatch($organization, WebhookEndpoint::EVENT_SCAN_STARTED, [
            'event' => WebhookEndpoint::EVENT_SCAN_STARTED,
            'timestamp' => now()->toIso8601String(),
            'data' => $scanData,
        ]);
    }

    /**
     * Dispatch scan completed event.
     *
     * @param  array<string, mixed>  $scanData
     */
    public function dispatchScanCompleted(Organization $organization, array $scanData): void
    {
        $this->dispatch($organization, WebhookEndpoint::EVENT_SCAN_COMPLETED, [
            'event' => WebhookEndpoint::EVENT_SCAN_COMPLETED,
            'timestamp' => now()->toIso8601String(),
            'data' => $scanData,
        ]);
    }

    /**
     * Dispatch scan failed event.
     *
     * @param  array<string, mixed>  $scanData
     */
    public function dispatchScanFailed(Organization $organization, array $scanData): void
    {
        $this->dispatch($organization, WebhookEndpoint::EVENT_SCAN_FAILED, [
            'event' => WebhookEndpoint::EVENT_SCAN_FAILED,
            'timestamp' => now()->toIso8601String(),
            'data' => $scanData,
        ]);
    }

    /**
     * Dispatch credits low event.
     */
    public function dispatchCreditsLow(Organization $organization, int $currentBalance): void
    {
        $this->dispatch($organization, WebhookEndpoint::EVENT_CREDITS_LOW, [
            'event' => WebhookEndpoint::EVENT_CREDITS_LOW,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'organization_id' => $organization->id,
                'current_balance' => $currentBalance,
            ],
        ]);
    }

    /**
     * Dispatch credits depleted event.
     */
    public function dispatchCreditsDepleted(Organization $organization): void
    {
        $this->dispatch($organization, WebhookEndpoint::EVENT_CREDITS_DEPLETED, [
            'event' => WebhookEndpoint::EVENT_CREDITS_DEPLETED,
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'organization_id' => $organization->id,
                'current_balance' => 0,
            ],
        ]);
    }
}
