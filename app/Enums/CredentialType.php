<?php

namespace App\Enums;

enum CredentialType: string
{
    case Form = 'form';
    case OAuth = 'oauth';
    case ApiKey = 'api_key';
    case Cookie = 'cookie';
    case Session = 'session';
    case BasicAuth = 'basic_auth';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Form => 'Form Login',
            self::OAuth => 'OAuth 2.0',
            self::ApiKey => 'API Key',
            self::Cookie => 'Cookie-based',
            self::Session => 'Session Token',
            self::BasicAuth => 'Basic Authentication',
        };
    }

    /**
     * Get the description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Form => 'Login via username/password form submission',
            self::OAuth => 'OAuth 2.0 authentication flow',
            self::ApiKey => 'API key passed in headers',
            self::Cookie => 'Pre-configured authentication cookies',
            self::Session => 'Session token for API access',
            self::BasicAuth => 'HTTP Basic Authentication',
        };
    }

    /**
     * Get required fields for this credential type.
     *
     * @return array<string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::Form => ['username', 'password', 'login_url'],
            self::OAuth => ['client_id', 'client_secret', 'token_url'],
            self::ApiKey => ['api_key', 'header_name'],
            self::Cookie => ['cookies'],
            self::Session => ['session_token', 'header_name'],
            self::BasicAuth => ['username', 'password'],
        };
    }

    /**
     * Check if this type requires a login URL.
     */
    public function requiresLoginUrl(): bool
    {
        return match ($this) {
            self::Form, self::OAuth => true,
            default => false,
        };
    }

    /**
     * Check if this type supports multi-step login.
     */
    public function supportsMultiStep(): bool
    {
        return match ($this) {
            self::Form => true,
            default => false,
        };
    }
}
