<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // We handle retries manually

    public int $timeout = 30;

    public function __construct(
        public WebhookDelivery $delivery,
    ) {}

    public function handle(): void
    {
        $endpoint = $this->delivery->endpoint;

        if (! $endpoint || ! $endpoint->is_active) {
            $this->delivery->update([
                'status' => WebhookDelivery::STATUS_FAILED,
                'response_body' => 'Endpoint disabled or deleted',
            ]);

            return;
        }

        $payload = $this->delivery->payload;
        $signature = $this->generateSignature($payload, $endpoint->secret);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->delivery->event,
                    'X-Webhook-Delivery-Id' => (string) $this->delivery->id,
                ])
                ->post($endpoint->url, $payload);

            $this->delivery->increment('attempts');
            $this->delivery->update([
                'response_status' => $response->status(),
                'response_body' => substr($response->body(), 0, 5000),
            ]);

            if ($response->successful()) {
                $this->delivery->update([
                    'status' => WebhookDelivery::STATUS_SUCCESS,
                    'delivered_at' => now(),
                ]);
            } else {
                $this->handleFailure("HTTP {$response->status()}");
            }
        } catch (\Exception $e) {
            $this->delivery->increment('attempts');
            $this->delivery->update([
                'response_body' => substr($e->getMessage(), 0, 5000),
            ]);
            $this->handleFailure($e->getMessage());
        }
    }

    protected function handleFailure(string $reason): void
    {
        if ($this->delivery->canRetry()) {
            $delay = $this->delivery->getNextRetryDelay();
            $this->delivery->update([
                'next_retry_at' => now()->addSeconds($delay),
            ]);

            // Re-dispatch with delay
            self::dispatch($this->delivery)->delay(now()->addSeconds($delay));
        } else {
            $this->delivery->update([
                'status' => WebhookDelivery::STATUS_FAILED,
            ]);
        }
    }

    /**
     * Generate HMAC signature for the payload.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
}
