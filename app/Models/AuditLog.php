<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_VIEWED = 'viewed';

    public const ACTION_EXPORTED = 'exported';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_LOGIN_FAILED = 'login_failed';

    public const ACTION_PASSWORD_RESET = 'password_reset';

    public const ACTION_API_ACCESS = 'api_access';

    protected $fillable = [
        'organization_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
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

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get a human-readable description of the action.
     */
    public function getDescription(): string
    {
        $subject = $this->auditable_type
            ? class_basename($this->auditable_type).' #'.$this->auditable_id
            : 'system';

        return match ($this->action) {
            self::ACTION_CREATED => "Created {$subject}",
            self::ACTION_UPDATED => "Updated {$subject}",
            self::ACTION_DELETED => "Deleted {$subject}",
            self::ACTION_VIEWED => "Viewed {$subject}",
            self::ACTION_EXPORTED => "Exported {$subject}",
            self::ACTION_LOGIN => 'Logged in',
            self::ACTION_LOGOUT => 'Logged out',
            self::ACTION_LOGIN_FAILED => 'Failed login attempt',
            self::ACTION_PASSWORD_RESET => 'Reset password',
            self::ACTION_API_ACCESS => 'API access',
            default => ucfirst($this->action),
        };
    }
}
