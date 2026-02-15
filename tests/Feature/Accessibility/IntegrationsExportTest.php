<?php

use App\Enums\CheckStatus;
use App\Enums\FixComplexity;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;
use App\Enums\WebhookEvent;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Services\Accessibility\AccessibilityExportService;
use App\Services\Accessibility\GitHubIntegrationService;
use App\Services\Accessibility\WebhookService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->audit = AccessibilityAudit::factory()->completed()->create([
        'overall_score' => 75.0,
    ]);
    $this->project = $this->audit->project;
});

// ============================================
// WebhookEvent Enum Tests
// ============================================

describe('WebhookEvent Enum', function () {
    test('has all expected events', function () {
        $events = WebhookEvent::cases();

        expect($events)->toHaveCount(8);
        expect(WebhookEvent::AuditStarted->value)->toBe('audit.started');
        expect(WebhookEvent::AuditCompleted->value)->toBe('audit.completed');
        expect(WebhookEvent::AuditFailed->value)->toBe('audit.failed');
        expect(WebhookEvent::CriticalIssueFound->value)->toBe('issue.critical');
        expect(WebhookEvent::RegressionDetected->value)->toBe('regression.detected');
        expect(WebhookEvent::ScoreThresholdBreach->value)->toBe('score.threshold_breach');
        expect(WebhookEvent::DeadlineApproaching->value)->toBe('deadline.approaching');
        expect(WebhookEvent::DeadlinePassed->value)->toBe('deadline.passed');
    });

    test('provides human-readable labels', function () {
        expect(WebhookEvent::AuditStarted->label())->toBe('Audit Started');
        expect(WebhookEvent::CriticalIssueFound->label())->toBe('Critical Issue Found');
        expect(WebhookEvent::RegressionDetected->label())->toBe('Regression Detected');
    });

    test('provides descriptions', function () {
        expect(WebhookEvent::AuditCompleted->description())
            ->toBe('Fired when an accessibility audit completes successfully');
        expect(WebhookEvent::ScoreThresholdBreach->description())
            ->toBe('Fired when score drops below configured threshold');
    });

    test('returns default enabled events', function () {
        $defaults = WebhookEvent::defaultEnabled();

        expect($defaults)->toContain(WebhookEvent::AuditCompleted);
        expect($defaults)->toContain(WebhookEvent::CriticalIssueFound);
        expect($defaults)->toContain(WebhookEvent::RegressionDetected);
        expect($defaults)->toContain(WebhookEvent::DeadlinePassed);
        expect($defaults)->not->toContain(WebhookEvent::AuditStarted);
    });
});

// ============================================
// FixComplexity Enum Tests
// ============================================

describe('FixComplexity Enum', function () {
    test('has all complexity levels', function () {
        expect(FixComplexity::Quick->value)->toBe('quick');
        expect(FixComplexity::Easy->value)->toBe('easy');
        expect(FixComplexity::Medium->value)->toBe('medium');
        expect(FixComplexity::Complex->value)->toBe('complex');
        expect(FixComplexity::Architectural->value)->toBe('architectural');
    });

    test('provides effort estimates in minutes', function () {
        expect(FixComplexity::Quick->effortMinutes())->toBe(5);
        expect(FixComplexity::Easy->effortMinutes())->toBe(15);
        expect(FixComplexity::Medium->effortMinutes())->toBe(60);
        expect(FixComplexity::Complex->effortMinutes())->toBe(240);
        expect(FixComplexity::Architectural->effortMinutes())->toBe(480);
    });

    test('determines complexity from WCAG criterion', function () {
        // Easy fixes
        expect(FixComplexity::fromCriterion('1.4.3'))->toBe(FixComplexity::Easy);

        // Medium fixes
        expect(FixComplexity::fromCriterion('2.1.1'))->toBe(FixComplexity::Medium);
        expect(FixComplexity::fromCriterion('2.4.7'))->toBe(FixComplexity::Medium);

        // Complex fixes
        expect(FixComplexity::fromCriterion('1.2.2'))->toBe(FixComplexity::Complex);
        expect(FixComplexity::fromCriterion('2.4.1'))->toBe(FixComplexity::Complex);
    });

    test('determines quick fix when issue type matches', function () {
        expect(FixComplexity::fromCriterion('1.1.1', 'missing-alt'))->toBe(FixComplexity::Quick);
        expect(FixComplexity::fromCriterion('3.1.1', 'missing-lang'))->toBe(FixComplexity::Quick);
    });

    test('provides priority weights', function () {
        expect(FixComplexity::Quick->priorityWeight())->toBe(1);
        expect(FixComplexity::Architectural->priorityWeight())->toBe(5);
    });

    test('provides colors for UI', function () {
        expect(FixComplexity::Quick->color())->toBe('green');
        expect(FixComplexity::Architectural->color())->toBe('red');
    });
});

// ============================================
// GitHubIntegrationService Tests
// ============================================

describe('GitHubIntegrationService', function () {
    test('can be configured manually', function () {
        $service = new GitHubIntegrationService;
        $service->configure('test-token', 'owner', 'repo');

        expect($service->isConfigured())->toBeTrue();
    });

    test('can be configured from project settings', function () {
        $this->project->update([
            'settings' => [
                'github' => [
                    'token' => 'test-token',
                    'owner' => 'test-owner',
                    'repo' => 'test-repo',
                ],
            ],
        ]);

        $service = new GitHubIntegrationService;
        $service->configureFromProject($this->project);

        expect($service->isConfigured())->toBeTrue();
    });

    test('reports not configured when missing settings', function () {
        $service = new GitHubIntegrationService;
        $service->configureFromProject($this->project);

        expect($service->isConfigured())->toBeFalse();
    });

    test('generates appropriate issue title', function () {
        $check = AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Serious,
            'message' => 'Text has insufficient color contrast ratio',
        ]);

        $service = new GitHubIntegrationService;

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateIssueTitle');
        $method->setAccessible(true);

        $title = $method->invoke($service, $check);

        expect($title)->toContain('[A11y]');
        expect($title)->toContain('[SERIOUS]');
        expect($title)->toContain('1.4.3');
    });

    test('generates issue body with all relevant information', function () {
        $check = AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Serious,
            'wcag_level' => WcagLevel::AA,
            'message' => 'Text has insufficient color contrast ratio',
            'element_selector' => '.header-text',
            'element_html' => '<p class="header-text">Hello</p>',
            'suggestion' => 'Increase the contrast ratio to at least 4.5:1',
        ]);

        $service = new GitHubIntegrationService;

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateIssueBody');
        $method->setAccessible(true);

        $body = $method->invoke($service, $check);

        expect($body)->toContain('## Accessibility Issue');
        expect($body)->toContain('**WCAG Criterion:** 1.4.3 - Contrast (Minimum)');
        expect($body)->toContain('**Level:** AA');
        expect($body)->toContain('**Impact:** Serious');
        expect($body)->toContain('### Issue Description');
        expect($body)->toContain('### Element');
        expect($body)->toContain('`.header-text`');
        expect($body)->toContain('### Suggested Fix');
    });

    test('generates appropriate labels', function () {
        $check = AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Critical,
            'wcag_level' => WcagLevel::AA,
            'category' => 'vision',
        ]);

        $service = new GitHubIntegrationService;

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateLabels');
        $method->setAccessible(true);

        $labels = $method->invoke($service, $check);

        expect($labels)->toContain('accessibility');
        expect($labels)->toContain('a11y');
        expect($labels)->toContain('impact:critical');
        expect($labels)->toContain('wcag:AA');
        expect($labels)->toContain('category:vision');
    });

    test('creates issue via GitHub API', function () {
        Http::fake([
            'api.github.com/*' => Http::response([
                'html_url' => 'https://github.com/owner/repo/issues/123',
                'number' => 123,
            ], 201),
        ]);

        $check = AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Critical,
            'message' => 'Contrast issue',
        ]);

        $service = new GitHubIntegrationService;
        $service->configure('test-token', 'owner', 'repo');

        $result = $service->createIssue($check);

        expect($result)->not->toBeNull();
        expect($result['html_url'])->toBe('https://github.com/owner/repo/issues/123');
        expect($result['number'])->toBe(123);

        // Check that metadata was updated
        $check->refresh();
        expect($check->metadata['github_issue_url'])->toBe('https://github.com/owner/repo/issues/123');
    });

    test('returns null when not configured', function () {
        $check = AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
            'status' => CheckStatus::Fail,
        ]);

        $service = new GitHubIntegrationService;

        $result = $service->createIssue($check);

        expect($result)->toBeNull();
    });
});

// ============================================
// WebhookService Tests
// ============================================

describe('WebhookService', function () {
    test('sends webhook for enabled events', function () {
        Http::fake([
            'https://example.com/webhook' => Http::response(['success' => true], 200),
        ]);

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
                'webhook_events' => [WebhookEvent::AuditCompleted->value],
            ],
        ]);

        $service = new WebhookService;
        $result = $service->send($this->project, WebhookEvent::AuditCompleted, ['test' => 'data']);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook'
                && $request['event'] === 'audit.completed'
                && $request['data']['test'] === 'data';
        });
    });

    test('does not send webhook for disabled events', function () {
        Http::fake();

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
                'webhook_events' => [WebhookEvent::AuditCompleted->value],
            ],
        ]);

        $service = new WebhookService;
        $result = $service->send($this->project, WebhookEvent::AuditStarted, []);

        expect($result)->toBeFalse();
        Http::assertNothingSent();
    });

    test('returns false when no webhook URL configured', function () {
        $service = new WebhookService;
        $result = $service->send($this->project, WebhookEvent::AuditCompleted, []);

        expect($result)->toBeFalse();
    });

    test('sends audit completed webhook', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
                'webhook_events' => [WebhookEvent::AuditCompleted->value],
            ],
        ]);

        $this->audit->update([
            'checks_total' => 50,
            'checks_passed' => 45,
            'checks_failed' => 5,
        ]);

        // Refresh to get updated relationships
        $this->audit->refresh();
        $this->audit->load('project');

        $service = new WebhookService;
        $result = $service->sendAuditCompleted($this->audit);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request['event'] === 'audit.completed'
                && $request['data']['audit_id'] === $this->audit->id;
        });
    });

    test('sends critical issue webhook', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
                'webhook_events' => [WebhookEvent::CriticalIssueFound->value],
            ],
        ]);

        $check = AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.1.1',
            'criterion_name' => 'Non-text Content',
            'impact' => ImpactLevel::Critical,
            'message' => 'Image missing alt text',
        ]);

        $service = new WebhookService;
        $result = $service->sendCriticalIssueFound($check);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) use ($check) {
            return $request['event'] === 'issue.critical'
                && $request['data']['check_id'] === $check->id
                && $request['data']['criterion_id'] === '1.1.1';
        });
    });

    test('includes webhook secret header when configured', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
                'webhook_secret' => 'my-secret-key',
                'webhook_events' => [WebhookEvent::AuditCompleted->value],
            ],
        ]);

        $service = new WebhookService;
        $service->send($this->project, WebhookEvent::AuditCompleted, []);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Webhook-Secret', 'my-secret-key');
        });
    });

    test('can test webhook configuration', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
            ],
        ]);

        $service = new WebhookService;
        $result = $service->test($this->project);

        expect($result)->toBeTrue();

        Http::assertSent(function ($request) {
            return $request['event'] === 'test'
                && $request['data']['message'] === 'This is a test webhook from OnPageIQ Accessibility.';
        });
    });

    test('uses default enabled events when not configured', function () {
        Http::fake([
            '*' => Http::response(['success' => true], 200),
        ]);

        $this->project->update([
            'settings' => [
                'webhook_url' => 'https://example.com/webhook',
                // No webhook_events set - should use defaults
            ],
        ]);

        $service = new WebhookService;

        // AuditCompleted is in defaultEnabled
        $result = $service->send($this->project, WebhookEvent::AuditCompleted, []);
        expect($result)->toBeTrue();
    });
});

// ============================================
// AccessibilityExportService Tests
// ============================================

describe('AccessibilityExportService', function () {
    beforeEach(function () {
        // Create some checks for the audit
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.1.1',
            'criterion_name' => 'Non-text Content',
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Critical,
            'wcag_level' => WcagLevel::A,
            'category' => 'vision',
            'message' => 'Image missing alt text',
            'element_selector' => 'img.hero',
            'suggestion' => 'Add alt attribute',
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Serious,
            'wcag_level' => WcagLevel::AA,
            'category' => 'vision',
            'message' => 'Insufficient contrast',
            'element_selector' => '.text-light',
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '2.4.7',
            'criterion_name' => 'Focus Visible',
            'status' => CheckStatus::Pass,
            'impact' => null,
            'wcag_level' => WcagLevel::AA,
            'category' => 'motor',
            'message' => 'Focus indicators are visible',
        ]);
    });

    test('exports audit to CSV', function () {
        $service = new AccessibilityExportService;
        $path = $service->exportToCsv($this->audit);

        expect($path)->toContain('accessibility-audit-');
        expect($path)->toEndWith('.csv');
        expect(file_exists($path))->toBeTrue();

        $content = file_get_contents($path);
        expect($content)->toContain('Criterion ID');
        expect($content)->toContain('1.1.1');
        expect($content)->toContain('Non-text Content');
        expect($content)->toContain('Image missing alt text');

        // Cleanup
        unlink($path);
    });

    test('CSV includes all expected columns', function () {
        $service = new AccessibilityExportService;
        $path = $service->exportToCsv($this->audit);

        $content = file_get_contents($path);
        $lines = explode("\n", $content);
        $headers = str_getcsv($lines[0]);

        expect($headers)->toContain('Criterion ID');
        expect($headers)->toContain('Criterion Name');
        expect($headers)->toContain('WCAG Level');
        expect($headers)->toContain('Status');
        expect($headers)->toContain('Impact');
        expect($headers)->toContain('Category');
        expect($headers)->toContain('Message');
        expect($headers)->toContain('Complexity');
        expect($headers)->toContain('Effort (min)');

        unlink($path);
    });

    test('exports audit to JSON', function () {
        $service = new AccessibilityExportService;
        $path = $service->exportToJson($this->audit);

        expect($path)->toContain('accessibility-audit-');
        expect($path)->toEndWith('.json');
        expect(file_exists($path))->toBeTrue();

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        expect($data)->toHaveKey('meta');
        expect($data)->toHaveKey('audit');
        expect($data)->toHaveKey('summary');
        expect($data)->toHaveKey('checks');

        expect($data['meta']['generator'])->toBe('OnPageIQ Accessibility');
        expect($data['audit']['id'])->toBe($this->audit->id);

        unlink($path);
    });

    test('JSON includes summary statistics', function () {
        $service = new AccessibilityExportService;
        $path = $service->exportToJson($this->audit);

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        expect($data['summary']['total_checks'])->toBe(3);
        expect($data['summary']['passed'])->toBe(1);
        expect($data['summary']['failed'])->toBe(2);

        unlink($path);
    });

    test('JSON includes complexity for each check', function () {
        $service = new AccessibilityExportService;
        $path = $service->exportToJson($this->audit);

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        foreach ($data['checks'] as $check) {
            expect($check)->toHaveKey('complexity');
        }

        unlink($path);
    });

    test('organizes issues by multiple dimensions', function () {
        $service = new AccessibilityExportService;
        $organized = $service->organizeIssues($this->audit);

        expect($organized)->toHaveKey('by_wcag');
        expect($organized)->toHaveKey('by_impact');
        expect($organized)->toHaveKey('by_category');
        expect($organized)->toHaveKey('by_complexity');
        expect($organized)->toHaveKey('by_element');
    });

    test('groups issues by WCAG criterion', function () {
        $service = new AccessibilityExportService;
        $organized = $service->organizeIssues($this->audit);

        expect($organized['by_wcag'])->toHaveKey('1.1.1');
        expect($organized['by_wcag']['1.1.1']['criterion_name'])->toBe('Non-text Content');
        expect($organized['by_wcag']['1.1.1']['count'])->toBe(1);
    });

    test('groups issues by impact level', function () {
        $service = new AccessibilityExportService;
        $organized = $service->organizeIssues($this->audit);

        expect($organized['by_impact'])->toHaveKey('critical');
        expect($organized['by_impact'])->toHaveKey('serious');
        expect($organized['by_impact']['critical']['count'])->toBe(1);
    });

    test('groups issues by category', function () {
        $service = new AccessibilityExportService;
        $organized = $service->organizeIssues($this->audit);

        // Both failing checks are vision category
        expect($organized['by_category'])->toHaveKey('vision');
        expect($organized['by_category']['vision']['count'])->toBe(2);
    });

    test('groups issues by complexity', function () {
        $service = new AccessibilityExportService;
        $organized = $service->organizeIssues($this->audit);

        expect($organized['by_complexity'])->not->toBeEmpty();
        foreach ($organized['by_complexity'] as $group) {
            expect($group)->toHaveKey('complexity');
            expect($group)->toHaveKey('complexity_label');
            expect($group)->toHaveKey('total_effort');
            expect($group)->toHaveKey('count');
        }
    });

    test('groups issues by element selector', function () {
        $service = new AccessibilityExportService;
        $organized = $service->organizeIssues($this->audit);

        // Should have elements with selectors
        expect($organized['by_element'])->not->toBeEmpty();
    });

    test('exports audit to PDF when DomPDF is available', function () {
        $service = new AccessibilityExportService;

        // Skip if DomPDF is not installed
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            expect(fn () => $service->exportToPdf($this->audit))
                ->toThrow(\RuntimeException::class, 'PDF export requires the barryvdh/laravel-dompdf package');
        } else {
            $path = $service->exportToPdf($this->audit);

            expect($path)->toContain('accessibility-report-');
            expect($path)->toEndWith('.pdf');
            expect(file_exists($path))->toBeTrue();

            // Cleanup
            unlink($path);
        }
    });

    test('PDF export throws exception when DomPDF not installed', function () {
        // This test verifies the error handling when DomPDF is missing
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('DomPDF is installed, cannot test missing package scenario');
        }

        $service = new AccessibilityExportService;

        expect(fn () => $service->exportToPdf($this->audit))
            ->toThrow(\RuntimeException::class);
    });
});
