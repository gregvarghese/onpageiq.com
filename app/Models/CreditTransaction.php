<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\CreditTransactionFactory> */
    use HasFactory;

    public const TYPE_SUBSCRIPTION_CREDIT = 'subscription_credit';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_USAGE = 'usage';

    public const TYPE_REFUND = 'refund';

    public const TYPE_BONUS = 'bonus';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'organization_id',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }
}
