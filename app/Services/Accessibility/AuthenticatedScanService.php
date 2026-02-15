<?php

namespace App\Services\Accessibility;

use App\Enums\CredentialType;
use App\Models\ProjectCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthenticatedScanService
{
    protected ?ProjectCredential $credential = null;

    /**
     * @var array<string, string>
     */
    protected array $sessionCookies = [];

    /**
     * @var array<string, string>
     */
    protected array $sessionHeaders = [];

    /**
     * Set the credential to use for authentication.
     */
    public function withCredential(ProjectCredential $credential): self
    {
        $this->credential = $credential;

        return $this;
    }

    /**
     * Authenticate and get session information.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    public function authenticate(): array
    {
        if (! $this->credential) {
            return ['success' => false, 'cookies' => [], 'headers' => [], 'error' => 'No credential configured'];
        }

        if (! $this->credential->isUsable()) {
            return ['success' => false, 'cookies' => [], 'headers' => [], 'error' => 'Credential is not usable'];
        }

        try {
            $result = match ($this->credential->type) {
                CredentialType::Form => $this->authenticateForm(),
                CredentialType::OAuth => $this->authenticateOAuth(),
                CredentialType::ApiKey => $this->authenticateApiKey(),
                CredentialType::BasicAuth => $this->authenticateBasicAuth(),
                CredentialType::Cookie => $this->authenticateCookie(),
                CredentialType::Session => $this->authenticateSession(),
            };

            if ($result['success']) {
                $this->credential->markAsUsed();
                $this->credential->markAsValidated(true);
                $this->sessionCookies = $result['cookies'];
                $this->sessionHeaders = $result['headers'];
            } else {
                $this->credential->markAsInvalid($result['error'] ?? 'Authentication failed');
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Authentication failed', [
                'credential_id' => $this->credential->id,
                'type' => $this->credential->type->value,
                'error' => $e->getMessage(),
            ]);

            $this->credential->markAsInvalid($e->getMessage());

            return [
                'success' => false,
                'cookies' => [],
                'headers' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get authenticated HTTP client.
     */
    public function getHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::withHeaders($this->sessionHeaders);

        if (! empty($this->sessionCookies)) {
            $cookieString = collect($this->sessionCookies)
                ->map(fn ($value, $name) => "{$name}={$value}")
                ->implode('; ');
            $client->withHeaders(['Cookie' => $cookieString]);
        }

        return $client;
    }

    /**
     * Fetch an authenticated page.
     *
     * @return array{success: bool, content?: string, status?: int, error?: string}
     */
    public function fetchPage(string $url): array
    {
        if (empty($this->sessionCookies) && empty($this->sessionHeaders)) {
            $authResult = $this->authenticate();
            if (! $authResult['success']) {
                return ['success' => false, 'error' => $authResult['error']];
            }
        }

        try {
            $response = $this->getHttpClient()->get($url);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'content' => $response->body(),
                    'status' => $response->status(),
                ];
            }

            return [
                'success' => false,
                'status' => $response->status(),
                'error' => "HTTP {$response->status()}",
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate via form submission.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    protected function authenticateForm(): array
    {
        $loginUrl = $this->credential->login_url;
        $credentials = $this->credential->credentials;
        $loginSteps = $this->credential->login_steps;

        if (! $loginUrl) {
            return ['success' => false, 'cookies' => [], 'headers' => [], 'error' => 'Login URL not configured'];
        }

        // Simple form submission (for non-JS forms)
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $usernameField = $credentials['username_field'] ?? 'email';
        $passwordField = $credentials['password_field'] ?? 'password';

        try {
            // First, get the login page to capture any CSRF tokens
            $loginPage = Http::get($loginUrl);
            $cookies = $this->extractCookies($loginPage);

            // Extract CSRF token if present
            $csrfToken = $this->extractCsrfToken($loginPage->body());

            $formData = [
                $usernameField => $username,
                $passwordField => $password,
            ];

            if ($csrfToken) {
                $formData['_token'] = $csrfToken;
            }

            // Submit login form
            $response = Http::withCookies($cookies, parse_url($loginUrl, PHP_URL_HOST))
                ->asForm()
                ->post($loginUrl, $formData);

            // Check if login was successful (usually a redirect)
            if ($response->successful() || $response->redirect()) {
                $newCookies = array_merge($cookies, $this->extractCookies($response));

                return [
                    'success' => true,
                    'cookies' => $newCookies,
                    'headers' => [],
                ];
            }

            return [
                'success' => false,
                'cookies' => [],
                'headers' => [],
                'error' => 'Login failed - invalid credentials or blocked',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'cookies' => [],
                'headers' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate via OAuth 2.0.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    protected function authenticateOAuth(): array
    {
        $credentials = $this->credential->credentials;

        $clientId = $credentials['client_id'] ?? '';
        $clientSecret = $credentials['client_secret'] ?? '';
        $tokenUrl = $credentials['token_url'] ?? '';
        $scope = $credentials['scope'] ?? '';
        $grantType = $credentials['grant_type'] ?? 'client_credentials';

        if (! $tokenUrl) {
            return ['success' => false, 'cookies' => [], 'headers' => [], 'error' => 'Token URL not configured'];
        }

        try {
            $response = Http::asForm()->post($tokenUrl, [
                'grant_type' => $grantType,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $accessToken = $data['access_token'] ?? '';
                $tokenType = $data['token_type'] ?? 'Bearer';

                return [
                    'success' => true,
                    'cookies' => [],
                    'headers' => [
                        'Authorization' => "{$tokenType} {$accessToken}",
                    ],
                ];
            }

            return [
                'success' => false,
                'cookies' => [],
                'headers' => [],
                'error' => 'OAuth token request failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'cookies' => [],
                'headers' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Authenticate via API key.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    protected function authenticateApiKey(): array
    {
        return [
            'success' => true,
            'cookies' => [],
            'headers' => $this->credential->getAuthHeaders(),
        ];
    }

    /**
     * Authenticate via Basic Auth.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    protected function authenticateBasicAuth(): array
    {
        return [
            'success' => true,
            'cookies' => [],
            'headers' => $this->credential->getAuthHeaders(),
        ];
    }

    /**
     * Authenticate via pre-configured cookies.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    protected function authenticateCookie(): array
    {
        return [
            'success' => true,
            'cookies' => $this->credential->getAuthCookies(),
            'headers' => [],
        ];
    }

    /**
     * Authenticate via session token.
     *
     * @return array{success: bool, cookies: array<string, string>, headers: array<string, string>, error?: string}
     */
    protected function authenticateSession(): array
    {
        return [
            'success' => true,
            'cookies' => [],
            'headers' => $this->credential->getAuthHeaders(),
        ];
    }

    /**
     * Extract cookies from HTTP response.
     *
     * @return array<string, string>
     */
    protected function extractCookies($response): array
    {
        $cookies = [];

        foreach ($response->cookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return $cookies;
    }

    /**
     * Extract CSRF token from HTML.
     */
    protected function extractCsrfToken(string $html): ?string
    {
        // Look for Laravel CSRF token
        if (preg_match('/<meta name="csrf-token" content="([^"]+)"/', $html, $match)) {
            return $match[1];
        }

        // Look for hidden input
        if (preg_match('/<input[^>]+name="_token"[^>]+value="([^"]+)"/', $html, $match)) {
            return $match[1];
        }

        if (preg_match('/<input[^>]+value="([^"]+)"[^>]+name="_token"/', $html, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * Validate that credentials are still working.
     */
    public function validateCredentials(): bool
    {
        $result = $this->authenticate();

        return $result['success'];
    }

    /**
     * Get Playwright browser context options for authenticated sessions.
     *
     * @return array<string, mixed>
     */
    public function getPlaywrightContextOptions(string $baseUrl): array
    {
        $options = [];

        // Add cookies for browser context
        if (! empty($this->sessionCookies)) {
            $domain = parse_url($baseUrl, PHP_URL_HOST);
            $options['cookies'] = collect($this->sessionCookies)
                ->map(fn ($value, $name) => [
                    'name' => $name,
                    'value' => $value,
                    'domain' => $domain,
                    'path' => '/',
                ])
                ->values()
                ->toArray();
        }

        // Add extra HTTP headers
        if (! empty($this->sessionHeaders)) {
            $options['extraHTTPHeaders'] = $this->sessionHeaders;
        }

        return $options;
    }

    /**
     * Generate login steps for Playwright automation.
     *
     * @return array<array{action: string, selector?: string, value?: string, url?: string}>
     */
    public function getPlaywrightLoginSteps(): array
    {
        if (! $this->credential || $this->credential->type !== CredentialType::Form) {
            return [];
        }

        // Use custom login steps if defined
        if ($this->credential->login_steps) {
            $credentials = $this->credential->credentials;

            return collect($this->credential->login_steps)
                ->map(function ($step) use ($credentials) {
                    // Replace credential placeholders
                    if (isset($step['value'])) {
                        $step['value'] = str_replace(
                            ['{{username}}', '{{password}}'],
                            [$credentials['username'] ?? '', $credentials['password'] ?? ''],
                            $step['value']
                        );
                    }

                    return $step;
                })
                ->toArray();
        }

        // Generate default login steps
        $credentials = $this->credential->credentials;

        return [
            [
                'action' => 'navigate',
                'url' => $this->credential->login_url,
            ],
            [
                'action' => 'fill',
                'selector' => $credentials['username_selector'] ?? 'input[name="email"], input[name="username"], input[type="email"]',
                'value' => $credentials['username'] ?? '',
            ],
            [
                'action' => 'fill',
                'selector' => $credentials['password_selector'] ?? 'input[name="password"], input[type="password"]',
                'value' => $credentials['password'] ?? '',
            ],
            [
                'action' => 'click',
                'selector' => $credentials['submit_selector'] ?? 'button[type="submit"], input[type="submit"]',
            ],
            [
                'action' => 'wait',
                'selector' => 'body', // Wait for page to load
            ],
        ];
    }
}
