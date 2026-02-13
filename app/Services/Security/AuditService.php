<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log a model creation.
     */
    public function logCreated(Model $model, ?User $user = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_CREATED,
            auditable: $model,
            newValues: $model->toArray(),
            user: $user,
        );
    }

    /**
     * Log a model update.
     *
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function logUpdated(Model $model, array $oldValues, array $newValues, ?User $user = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_UPDATED,
            auditable: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            user: $user,
        );
    }

    /**
     * Log a model deletion.
     */
    public function logDeleted(Model $model, ?User $user = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_DELETED,
            auditable: $model,
            oldValues: $model->toArray(),
            user: $user,
        );
    }

    /**
     * Log a view action.
     */
    public function logViewed(Model $model, ?User $user = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_VIEWED,
            auditable: $model,
            user: $user,
        );
    }

    /**
     * Log an export action.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function logExported(Model $model, ?array $metadata = null, ?User $user = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_EXPORTED,
            auditable: $model,
            metadata: $metadata,
            user: $user,
        );
    }

    /**
     * Log a login action.
     */
    public function logLogin(User $user): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_LOGIN,
            user: $user,
        );
    }

    /**
     * Log a logout action.
     */
    public function logLogout(User $user): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_LOGOUT,
            user: $user,
        );
    }

    /**
     * Log a failed login attempt.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function logLoginFailed(string $email, ?array $metadata = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_LOGIN_FAILED,
            metadata: array_merge(['email' => $email], $metadata ?? []),
        );
    }

    /**
     * Log a password reset.
     */
    public function logPasswordReset(User $user): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_PASSWORD_RESET,
            user: $user,
        );
    }

    /**
     * Log API access.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function logApiAccess(string $endpoint, ?User $user = null, ?array $metadata = null): AuditLog
    {
        return $this->log(
            action: AuditLog::ACTION_API_ACCESS,
            metadata: array_merge(['endpoint' => $endpoint], $metadata ?? []),
            user: $user,
        );
    }

    /**
     * Log a custom action.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public function logCustom(string $action, ?Model $auditable = null, ?array $metadata = null, ?User $user = null): AuditLog
    {
        return $this->log(
            action: $action,
            auditable: $auditable,
            metadata: $metadata,
            user: $user,
        );
    }

    /**
     * Create an audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     * @param  array<string, mixed>|null  $metadata
     */
    protected function log(
        string $action,
        ?Model $auditable = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null,
        ?User $user = null,
    ): AuditLog {
        $user = $user ?? Auth::user();

        return AuditLog::create([
            'organization_id' => $user?->organization_id,
            'user_id' => $user?->id,
            'action' => $action,
            'auditable_type' => $auditable ? get_class($auditable) : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues ? $this->filterSensitive($oldValues) : null,
            'new_values' => $newValues ? $this->filterSensitive($newValues) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Filter sensitive fields from values.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function filterSensitive(array $values): array
    {
        $sensitive = [
            'password',
            'password_confirmation',
            'secret',
            'token',
            'api_key',
            'stripe_id',
            'pm_last_four',
            'fingerprint_hash',
        ];

        foreach ($sensitive as $field) {
            if (isset($values[$field])) {
                $values[$field] = '[REDACTED]';
            }
        }

        return $values;
    }
}
