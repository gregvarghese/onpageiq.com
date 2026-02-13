<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WebhookEndpoint extends Model
{
    /** @use HasFactory<\Database\Factories\WebhookEndpointFactory> */
    use HasFactory;

    public const EVENT_SCAN_STARTED = 'scan.started';

    public const EVENT_SCAN_COMPLETED = 'scan.completed';

    public const EVENT_SCAN_FAILED = 'scan.failed';

    public const EVENT_CREDITS_LOW = 'credits.low';

    public const EVENT_CREDITS_DEPLETED = 'credits.depleted';

    public const ALL_EVENTS = [
        self::EVENT_SCAN_STARTED,
        self::EVENT_SCAN_COMPLETED,
        self::EVENT_SCAN_FAILED,
        self::EVENT_CREDITS_LOW,
        self::EVENT_CREDITS_DEPLETED,
    ];

    protected $fillable = [
        'organization_id',
        'url',
        'secret',
        'events',
        'is_active',
        'description',
        'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WebhookEndpoint $endpoint) {
            if (empty($endpoint->secret)) {
                $endpoint->secret = Str::random(64);
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }
}
