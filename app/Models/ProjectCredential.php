<?php

namespace App\Models;

use App\Enums\CredentialType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ProjectCredential extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectCredentialFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'type',
        'credentials',
        'login_url',
        'login_steps',
        'headers',
        'cookies',
        'is_active',
        'last_used_at',
        'last_validated_at',
        'is_valid',
        'validation_error',
        'rotated_at',
        'created_by_user_id',
    ];

    protected $hidden = [
        'credentials',
    ];

    protected function casts(): array
    {
        return [
            'type' => CredentialType::class,
            'login_steps' => 'array',
            'headers' => 'array',
            'cookies' => 'array',
            'is_active' => 'boolean',
            'is_valid' => 'boolean',
            'last_used_at' => 'datetime',
            'last_validated_at' => 'datetime',
            'rotated_at' => 'datetime',
        ];
    }

    /**
     * Get the project this credential belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user who created this credential.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Set credentials with encryption.
     *
     * @param  array<string, mixed>  $value
     */
    public function setCredentialsAttribute(array $value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Get decrypted credentials.
     *
     * @return array<string, mixed>
     */
    public function getCredentialsAttribute(?string $value): array
    {
        if (! $value) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get a specific credential value.
     */
    public function getCredential(string $key, mixed $default = null): mixed
    {
        return $this->credentials[$key] ?? $default;
    }

    /**
     * Update a specific credential value.
     */
    public function updateCredential(string $key, mixed $value): void
    {
        $credentials = $this->credentials;
        $credentials[$key] = $value;
        $this->credentials = $credentials;
    }

    /**
     * Mark credential as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Mark credential as validated.
     */
    public function markAsValidated(bool $isValid = true, ?string $error = null): void
    {
        $this->update([
            'last_validated_at' => now(),
            'is_valid' => $isValid,
            'validation_error' => $error,
        ]);
    }

    /**
     * Mark credential as invalid.
     */
    public function markAsInvalid(string $error): void
    {
        $this->markAsValidated(false, $error);
    }

    /**
     * Rotate the credential (update with new values).
     *
     * @param  array<string, mixed>  $newCredentials
     */
    public function rotate(array $newCredentials): void
    {
        $this->update([
            'credentials' => $newCredentials,
            'rotated_at' => now(),
            'is_valid' => true,
            'validation_error' => null,
        ]);
    }

    /**
     * Check if credential needs validation.
     */
    public function needsValidation(int $maxAgeHours = 24): bool
    {
        if (! $this->last_validated_at) {
            return true;
        }

        return $this->last_validated_at->diffInHours(now()) >= $maxAgeHours;
    }

    /**
     * Check if credential is usable.
     */
    public function isUsable(): bool
    {
        return $this->is_active && $this->is_valid;
    }

    /**
     * Get the authentication headers for HTTP requests.
     *
     * @return array<string, string>
     */
    public function getAuthHeaders(): array
    {
        $headers = $this->headers ?? [];

        // Add type-specific headers
        switch ($this->type) {
            case CredentialType::ApiKey:
                $headerName = $this->getCredential('header_name', 'X-API-Key');
                $headers[$headerName] = $this->getCredential('api_key', '');
                break;

            case CredentialType::Session:
                $headerName = $this->getCredential('header_name', 'Authorization');
                $token = $this->getCredential('session_token', '');
                $headers[$headerName] = "Bearer {$token}";
                break;

            case CredentialType::BasicAuth:
                $username = $this->getCredential('username', '');
                $password = $this->getCredential('password', '');
                $headers['Authorization'] = 'Basic '.base64_encode("{$username}:{$password}");
                break;
        }

        return $headers;
    }

    /**
     * Get cookies for HTTP requests.
     *
     * @return array<string, string>
     */
    public function getAuthCookies(): array
    {
        if ($this->type === CredentialType::Cookie) {
            return $this->getCredential('cookies', []);
        }

        return $this->cookies ?? [];
    }

    /**
     * Scope to active credentials.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to valid credentials.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Scope to usable credentials (active and valid).
     */
    public function scopeUsable($query)
    {
        return $query->where('is_active', true)->where('is_valid', true);
    }

    /**
     * Scope to credentials of a specific type.
     */
    public function scopeOfType($query, CredentialType $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to credentials needing validation.
     */
    public function scopeNeedsValidation($query, int $maxAgeHours = 24)
    {
        return $query->where(function ($q) use ($maxAgeHours) {
            $q->whereNull('last_validated_at')
                ->orWhere('last_validated_at', '<', now()->subHours($maxAgeHours));
        });
    }
}
