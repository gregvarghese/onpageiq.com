<?php

namespace Database\Factories;

use App\Enums\CredentialType;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectCredential>
 */
class ProjectCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->words(2, true).' Credentials',
            'type' => CredentialType::Form,
            'credentials' => [
                'username' => fake()->userName(),
                'password' => fake()->password(),
            ],
            'login_url' => fake()->url().'/login',
            'login_steps' => null,
            'headers' => null,
            'cookies' => null,
            'is_active' => true,
            'is_valid' => true,
            'last_used_at' => null,
            'last_validated_at' => null,
            'validation_error' => null,
            'rotated_at' => null,
            'created_by_user_id' => User::factory(),
        ];
    }

    /**
     * Create a form-based credential.
     */
    public function formAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CredentialType::Form,
            'credentials' => [
                'username' => fake()->userName(),
                'password' => fake()->password(),
                'username_field' => 'email',
                'password_field' => 'password',
                'submit_button' => 'button[type="submit"]',
            ],
            'login_url' => fake()->url().'/login',
            'login_steps' => [
                ['action' => 'fill', 'selector' => '#email', 'value' => '{{username}}'],
                ['action' => 'fill', 'selector' => '#password', 'value' => '{{password}}'],
                ['action' => 'click', 'selector' => 'button[type="submit"]'],
                ['action' => 'wait', 'url' => '/dashboard'],
            ],
        ]);
    }

    /**
     * Create an API key credential.
     */
    public function apiKey(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CredentialType::ApiKey,
            'credentials' => [
                'api_key' => fake()->sha256(),
                'header_name' => 'X-API-Key',
            ],
            'login_url' => null,
        ]);
    }

    /**
     * Create an OAuth credential.
     */
    public function oauth(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CredentialType::OAuth,
            'credentials' => [
                'client_id' => fake()->uuid(),
                'client_secret' => fake()->sha256(),
                'token_url' => fake()->url().'/oauth/token',
                'scope' => 'read write',
                'grant_type' => 'client_credentials',
            ],
            'login_url' => fake()->url().'/oauth/authorize',
        ]);
    }

    /**
     * Create a basic auth credential.
     */
    public function basicAuth(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CredentialType::BasicAuth,
            'credentials' => [
                'username' => fake()->userName(),
                'password' => fake()->password(),
            ],
            'login_url' => null,
        ]);
    }

    /**
     * Create a session token credential.
     */
    public function sessionToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CredentialType::Session,
            'credentials' => [
                'session_token' => fake()->sha256(),
                'header_name' => 'Authorization',
            ],
            'login_url' => null,
        ]);
    }

    /**
     * Create a cookie-based credential.
     */
    public function cookieBased(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CredentialType::Cookie,
            'credentials' => [
                'cookies' => [
                    'session_id' => fake()->sha256(),
                    'auth_token' => fake()->sha256(),
                ],
            ],
            'login_url' => null,
        ]);
    }

    /**
     * Create an inactive credential.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create an invalid credential.
     */
    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => false,
            'validation_error' => 'Authentication failed: Invalid credentials',
            'last_validated_at' => now(),
        ]);
    }

    /**
     * Create a recently validated credential.
     */
    public function validated(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_valid' => true,
            'last_validated_at' => now(),
            'validation_error' => null,
        ]);
    }

    /**
     * Create a credential that needs validation.
     */
    public function needsValidation(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_validated_at' => now()->subDays(2),
        ]);
    }

    /**
     * Create a recently rotated credential.
     */
    public function rotated(): static
    {
        return $this->state(fn (array $attributes) => [
            'rotated_at' => now(),
        ]);
    }
}
