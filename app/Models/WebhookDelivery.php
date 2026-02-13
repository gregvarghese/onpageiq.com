<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookDeliveryFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'webhook_endpoint_id',
        'event',
        'payload',
        'response_status',
        'response_body',
        'attempts',
        'next_retry_at',
        'delivered_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(WebhookEndpoint::class, 'webhook_endpoint_id');
    }

    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canRetry(): bool
    {
        return $this->status !== self::STATUS_SUCCESS && $this->attempts < self::MAX_ATTEMPTS;
    }

    /**
     * Calculate the next retry delay using exponential backoff.
     */
    public function getNextRetryDelay(): int
    {
        // Exponential backoff: 1min, 5min, 15min, 30min, 60min
        $delays = [60, 300, 900, 1800, 3600];

        return $delays[$this->attempts] ?? 3600;
    }
}
