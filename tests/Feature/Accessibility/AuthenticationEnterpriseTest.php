<?php

use App\Enums\CredentialType;
use App\Models\ProjectCredential;
use App\Services\Accessibility\AuthenticatedScanService;
use App\Services\Accessibility\ColorSystemAnalysisService;
use Illuminate\Support\Facades\Http;

// ============================================
// CredentialType Enum Tests
// ============================================

describe('CredentialType Enum', function () {
    test('has all credential types', function () {
        expect(CredentialType::Form->value)->toBe('form');
        expect(CredentialType::OAuth->value)->toBe('oauth');
        expect(CredentialType::ApiKey->value)->toBe('api_key');
        expect(CredentialType::Cookie->value)->toBe('cookie');
        expect(CredentialType::Session->value)->toBe('session');
        expect(CredentialType::BasicAuth->value)->toBe('basic_auth');
    });

    test('provides labels', function () {
        expect(CredentialType::Form->label())->toBe('Form Login');
        expect(CredentialType::OAuth->label())->toBe('OAuth 2.0');
        expect(CredentialType::BasicAuth->label())->toBe('Basic Authentication');
    });

    test('provides required fields', function () {
        expect(CredentialType::Form->requiredFields())->toContain('username');
        expect(CredentialType::Form->requiredFields())->toContain('password');
        expect(CredentialType::ApiKey->requiredFields())->toContain('api_key');
        expect(CredentialType::OAuth->requiredFields())->toContain('client_id');
    });

    test('identifies types requiring login URL', function () {
        expect(CredentialType::Form->requiresLoginUrl())->toBeTrue();
        expect(CredentialType::OAuth->requiresLoginUrl())->toBeTrue();
        expect(CredentialType::ApiKey->requiresLoginUrl())->toBeFalse();
    });
});

// ============================================
// ProjectCredential Model Tests
// ============================================

describe('ProjectCredential Model', function () {
    test('can be created with factory', function () {
        $credential = ProjectCredential::factory()->create();

        expect($credential)->toBeInstanceOf(ProjectCredential::class);
        expect($credential->id)->not->toBeNull();
        expect($credential->type)->toBe(CredentialType::Form);
    });

    test('encrypts credentials at rest', function () {
        $credential = ProjectCredential::factory()->create([
            'credentials' => [
                'username' => 'testuser',
                'password' => 'secret123',
            ],
        ]);

        // Raw database value should be encrypted
        $rawValue = \DB::table('project_credentials')
            ->where('id', $credential->id)
            ->value('credentials');

        expect($rawValue)->not->toContain('testuser');
        expect($rawValue)->not->toContain('secret123');

        // But model attribute should decrypt
        $credential->refresh();
        expect($credential->credentials['username'])->toBe('testuser');
        expect($credential->credentials['password'])->toBe('secret123');
    });

    test('can create API key credential', function () {
        $credential = ProjectCredential::factory()->apiKey()->create();

        expect($credential->type)->toBe(CredentialType::ApiKey);
        expect($credential->credentials)->toHaveKey('api_key');
        expect($credential->credentials)->toHaveKey('header_name');
    });

    test('can create OAuth credential', function () {
        $credential = ProjectCredential::factory()->oauth()->create();

        expect($credential->type)->toBe(CredentialType::OAuth);
        expect($credential->credentials)->toHaveKey('client_id');
        expect($credential->credentials)->toHaveKey('client_secret');
    });

    test('can create basic auth credential', function () {
        $credential = ProjectCredential::factory()->basicAuth()->create();

        expect($credential->type)->toBe(CredentialType::BasicAuth);
    });

    test('generates auth headers for API key', function () {
        $credential = ProjectCredential::factory()->apiKey()->create([
            'credentials' => [
                'api_key' => 'my-api-key-123',
                'header_name' => 'X-API-Key',
            ],
        ]);

        $headers = $credential->getAuthHeaders();

        expect($headers)->toHaveKey('X-API-Key');
        expect($headers['X-API-Key'])->toBe('my-api-key-123');
    });

    test('generates auth headers for basic auth', function () {
        $credential = ProjectCredential::factory()->basicAuth()->create([
            'credentials' => [
                'username' => 'user',
                'password' => 'pass',
            ],
        ]);

        $headers = $credential->getAuthHeaders();

        expect($headers)->toHaveKey('Authorization');
        expect($headers['Authorization'])->toBe('Basic '.base64_encode('user:pass'));
    });

    test('tracks usage', function () {
        $credential = ProjectCredential::factory()->create();

        expect($credential->last_used_at)->toBeNull();

        $credential->markAsUsed();

        expect($credential->fresh()->last_used_at)->not->toBeNull();
    });

    test('tracks validation status', function () {
        $credential = ProjectCredential::factory()->create();

        $credential->markAsValidated(true);
        expect($credential->fresh()->is_valid)->toBeTrue();
        expect($credential->fresh()->last_validated_at)->not->toBeNull();

        $credential->markAsInvalid('Invalid credentials');
        expect($credential->fresh()->is_valid)->toBeFalse();
        expect($credential->fresh()->validation_error)->toBe('Invalid credentials');
    });

    test('supports credential rotation', function () {
        $credential = ProjectCredential::factory()->apiKey()->create([
            'credentials' => ['api_key' => 'old-key', 'header_name' => 'X-API-Key'],
        ]);

        $credential->rotate(['api_key' => 'new-key', 'header_name' => 'X-API-Key']);

        $credential->refresh();
        expect($credential->credentials['api_key'])->toBe('new-key');
        expect($credential->rotated_at)->not->toBeNull();
    });

    test('determines if validation is needed', function () {
        $credential = ProjectCredential::factory()->create([
            'last_validated_at' => null,
        ]);

        expect($credential->needsValidation())->toBeTrue();

        $credential->update(['last_validated_at' => now()]);
        expect($credential->needsValidation())->toBeFalse();

        $credential->update(['last_validated_at' => now()->subHours(25)]);
        expect($credential->needsValidation(24))->toBeTrue();
    });

    test('determines if credential is usable', function () {
        $credential = ProjectCredential::factory()->create([
            'is_active' => true,
            'is_valid' => true,
        ]);

        expect($credential->isUsable())->toBeTrue();

        $credential->update(['is_active' => false]);
        expect($credential->isUsable())->toBeFalse();

        $credential->update(['is_active' => true, 'is_valid' => false]);
        expect($credential->isUsable())->toBeFalse();
    });

    test('scopes filter correctly', function () {
        ProjectCredential::factory()->create(['is_active' => true, 'is_valid' => true]);
        ProjectCredential::factory()->create(['is_active' => false, 'is_valid' => true]);
        ProjectCredential::factory()->create(['is_active' => true, 'is_valid' => false]);

        expect(ProjectCredential::active()->count())->toBe(2);
        expect(ProjectCredential::valid()->count())->toBe(2);
        expect(ProjectCredential::usable()->count())->toBe(1);
    });
});

// ============================================
// ColorSystemAnalysisService Tests
// ============================================

describe('ColorSystemAnalysisService', function () {
    beforeEach(function () {
        $this->service = new ColorSystemAnalysisService;
    });

    test('extracts CSS custom properties', function () {
        $css = '
            :root {
                --primary-color: #3b82f6;
                --secondary-color: #10b981;
                --text-color: rgb(51, 51, 51);
            }
        ';

        $variables = $this->service->extractCssVariables($css);

        expect($variables)->toHaveKey('--primary-color');
        expect($variables)->toHaveKey('--secondary-color');
        expect($variables['--primary-color'])->toBe('#3b82f6');
    });

    test('converts hex to RGB', function () {
        $rgb = $this->service->hexToRgb('#ff0000');

        expect($rgb)->toBe(['r' => 255, 'g' => 0, 'b' => 0]);
    });

    test('handles 3-digit hex colors', function () {
        $rgb = $this->service->hexToRgb('#f00');

        expect($rgb)->toBe(['r' => 255, 'g' => 0, 'b' => 0]);
    });

    test('converts RGB to hex', function () {
        $hex = $this->service->rgbToHex(255, 0, 0);

        expect($hex)->toBe('#ff0000');
    });

    test('converts hex to HSL', function () {
        $hsl = $this->service->hexToHsl('#ff0000');

        expect($hsl['h'])->toBe(0);
        expect($hsl['s'])->toBe(100);
        expect($hsl['l'])->toBe(50);
    });

    test('calculates relative luminance', function () {
        $whiteLuminance = $this->service->calculateRelativeLuminance('#ffffff');
        $blackLuminance = $this->service->calculateRelativeLuminance('#000000');

        expect($whiteLuminance)->toBeGreaterThan(0.9);
        expect($blackLuminance)->toBeLessThan(0.1);
    });

    test('calculates contrast ratio', function () {
        // White on black should be 21:1
        $ratio = $this->service->calculateContrastRatio('#ffffff', '#000000');
        expect($ratio)->toBeGreaterThan(20);

        // Same colors should be 1:1
        $sameRatio = $this->service->calculateContrastRatio('#ffffff', '#ffffff');
        expect($sameRatio)->toEqual(1.0);
    });

    test('identifies WCAG compliant contrast', function () {
        // Good contrast (dark text on white)
        $goodRatio = $this->service->calculateContrastRatio('#333333', '#ffffff');
        expect($goodRatio)->toBeGreaterThan(4.5);

        // Poor contrast (light gray on white)
        $poorRatio = $this->service->calculateContrastRatio('#cccccc', '#ffffff');
        expect($poorRatio)->toBeLessThan(4.5);
    });

    test('builds color palette from CSS', function () {
        $css = '
            :root {
                --brand-blue: #3b82f6;
                --brand-green: #10b981;
            }
        ';

        $palette = $this->service->buildColorPalette($css);

        expect($palette)->toHaveKey('--brand-blue');
        expect($palette['--brand-blue']['hex'])->toBe('#3b82f6');
        expect($palette['--brand-blue'])->toHaveKey('rgb');
        expect($palette['--brand-blue'])->toHaveKey('hsl');
        expect($palette['--brand-blue'])->toHaveKey('luminance');
    });

    test('extracts all colors from CSS', function () {
        $css = '
            .header { background: #3b82f6; }
            .text { color: rgb(51, 51, 51); }
            .accent { border-color: #10b981; }
            .header { color: #3b82f6; } /* duplicate */
        ';

        $colors = $this->service->extractAllColors($css);

        expect(count($colors))->toBeGreaterThanOrEqual(2);
        expect($colors['#3b82f6']['occurrences'])->toBe(2);
    });

    test('generates contrast matrix', function () {
        $colors = [
            '#ffffff' => ['hex' => '#ffffff'],
            '#000000' => ['hex' => '#000000'],
            '#3b82f6' => ['hex' => '#3b82f6'],
        ];

        $matrix = $this->service->generateContrastMatrix($colors);

        expect($matrix)->toHaveKey('#ffffff');
        expect($matrix['#ffffff'])->toHaveKey('#000000');
        expect($matrix['#ffffff']['#000000']['passes_aa_normal'])->toBeTrue();
    });

    test('analyzes brand colors', function () {
        $brandColors = [
            'primary' => '#3b82f6',
            'secondary' => '#10b981',
            'background' => '#ffffff',
            'text' => '#1f2937',
        ];

        $analysis = $this->service->analyzeBrandColors($brandColors);

        expect($analysis)->toHaveKey('colors');
        expect($analysis)->toHaveKey('combinations');
        expect($analysis)->toHaveKey('recommendations');
        expect($analysis)->toHaveKey('overall_score');

        expect($analysis['colors'])->toHaveKey('primary');
        expect($analysis['colors']['primary']['contrast_with_white'])->toBeGreaterThan(0);
    });

    test('suggests accessible alternatives', function () {
        // Light color that fails contrast with white
        $alternatives = $this->service->suggestAccessibleAlternatives('#aaaaaa', '#ffffff');

        expect($alternatives)->toHaveKey('lighter');
        expect($alternatives)->toHaveKey('darker');

        // The darker alternative should have better contrast
        $originalRatio = $this->service->calculateContrastRatio('#aaaaaa', '#ffffff');
        $darkerRatio = $this->service->calculateContrastRatio($alternatives['darker'], '#ffffff');

        expect($darkerRatio)->toBeGreaterThan($originalRatio);
    });

    test('normalizes hex colors', function () {
        expect($this->service->normalizeHex('#FFF'))->toBe('#ffffff');
        expect($this->service->normalizeHex('ABC'))->toBe('#aabbcc');
        expect($this->service->normalizeHex('#12345678'))->toBe('#123456'); // Strip alpha
        expect($this->service->normalizeHex('invalid'))->toBeNull();
    });
});

// ============================================
// AuthenticatedScanService Tests
// ============================================

describe('AuthenticatedScanService', function () {
    test('authenticates with API key', function () {
        $credential = ProjectCredential::factory()->apiKey()->create([
            'credentials' => [
                'api_key' => 'test-api-key',
                'header_name' => 'X-API-Key',
            ],
        ]);

        $service = new AuthenticatedScanService;
        $result = $service->withCredential($credential)->authenticate();

        expect($result['success'])->toBeTrue();
        expect($result['headers'])->toHaveKey('X-API-Key');
        expect($result['headers']['X-API-Key'])->toBe('test-api-key');
    });

    test('authenticates with basic auth', function () {
        $credential = ProjectCredential::factory()->basicAuth()->create([
            'credentials' => [
                'username' => 'testuser',
                'password' => 'testpass',
            ],
        ]);

        $service = new AuthenticatedScanService;
        $result = $service->withCredential($credential)->authenticate();

        expect($result['success'])->toBeTrue();
        expect($result['headers'])->toHaveKey('Authorization');
        expect($result['headers']['Authorization'])->toContain('Basic');
    });

    test('authenticates with cookies', function () {
        $credential = ProjectCredential::factory()->cookieBased()->create([
            'credentials' => [
                'cookies' => [
                    'session_id' => 'abc123',
                    'auth_token' => 'xyz789',
                ],
            ],
        ]);

        $service = new AuthenticatedScanService;
        $result = $service->withCredential($credential)->authenticate();

        expect($result['success'])->toBeTrue();
        expect($result['cookies'])->toHaveKey('session_id');
        expect($result['cookies']['session_id'])->toBe('abc123');
    });

    test('fails when no credential configured', function () {
        $service = new AuthenticatedScanService;
        $result = $service->authenticate();

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('No credential configured');
    });

    test('fails when credential is not usable', function () {
        $credential = ProjectCredential::factory()->invalid()->create();

        $service = new AuthenticatedScanService;
        $result = $service->withCredential($credential)->authenticate();

        expect($result['success'])->toBeFalse();
        expect($result['error'])->toBe('Credential is not usable');
    });

    test('fetches authenticated page', function () {
        Http::fake([
            'https://example.com/protected' => Http::response('<html>Protected Content</html>', 200),
        ]);

        $credential = ProjectCredential::factory()->apiKey()->create([
            'credentials' => [
                'api_key' => 'test-key',
                'header_name' => 'X-API-Key',
            ],
        ]);

        $service = new AuthenticatedScanService;
        $service->withCredential($credential);

        $result = $service->fetchPage('https://example.com/protected');

        expect($result['success'])->toBeTrue();
        expect($result['content'])->toContain('Protected Content');
    });

    test('generates Playwright context options', function () {
        $credential = ProjectCredential::factory()->cookieBased()->create([
            'credentials' => [
                'cookies' => [
                    'session_id' => 'abc123',
                ],
            ],
        ]);

        $service = new AuthenticatedScanService;
        $service->withCredential($credential)->authenticate();

        $options = $service->getPlaywrightContextOptions('https://example.com');

        expect($options)->toHaveKey('cookies');
        expect($options['cookies'][0]['name'])->toBe('session_id');
        expect($options['cookies'][0]['value'])->toBe('abc123');
    });

    test('generates Playwright login steps', function () {
        $credential = ProjectCredential::factory()->formAuth()->create();

        $service = new AuthenticatedScanService;
        $service->withCredential($credential);

        $steps = $service->getPlaywrightLoginSteps();

        expect($steps)->not->toBeEmpty();
        expect(collect($steps)->pluck('action'))->toContain('fill');
        expect(collect($steps)->pluck('action'))->toContain('click');
    });
});
