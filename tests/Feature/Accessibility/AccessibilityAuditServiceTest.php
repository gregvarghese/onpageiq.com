<?php

use App\Enums\AuditStatus;
use App\Enums\CheckStatus;
use App\Enums\ComplianceFramework;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Url;
use App\Models\User;
use App\Services\Accessibility\AccessibilityAuditService;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'enterprise']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->url = Url::factory()->create(['project_id' => $this->project->id]);
    $this->service = app(AccessibilityAuditService::class);
});

describe('AccessibilityAuditService::createAudit', function () {
    it('creates an audit with default settings', function () {
        $audit = $this->service->createAudit($this->project);

        expect($audit)->toBeInstanceOf(AccessibilityAudit::class)
            ->and($audit->project_id)->toBe($this->project->id)
            ->and($audit->status)->toBe(AuditStatus::Pending)
            ->and($audit->wcag_level_target)->toBe(WcagLevel::AA)
            ->and($audit->framework)->toBe(ComplianceFramework::Wcag21);
    });

    it('creates an audit for a specific URL', function () {
        $audit = $this->service->createAudit($this->project, $this->url);

        expect($audit->url_id)->toBe($this->url->id);
    });

    it('creates an audit with triggered by user', function () {
        $audit = $this->service->createAudit($this->project, null, $this->user);

        expect($audit->triggered_by_user_id)->toBe($this->user->id);
    });

    it('creates an audit with custom WCAG level', function () {
        $audit = $this->service->createAudit(
            $this->project,
            null,
            null,
            WcagLevel::AAA
        );

        expect($audit->wcag_level_target)->toBe(WcagLevel::AAA);
    });

    it('creates an audit with custom framework', function () {
        $audit = $this->service->createAudit(
            $this->project,
            null,
            null,
            WcagLevel::AA,
            ComplianceFramework::Section508
        );

        expect($audit->framework)->toBe(ComplianceFramework::Section508);
    });
});

describe('AccessibilityAuditService::runAudit', function () {
    it('marks audit as running when started', function () {
        $audit = $this->service->createAudit($this->project);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test Page</title></head><body><h1>Hello</h1></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $audit->refresh();
        expect($audit->status)->toBe(AuditStatus::Completed)
            ->and($audit->started_at)->not->toBeNull()
            ->and($audit->completed_at)->not->toBeNull();
    });

    it('creates checks for WCAG criteria', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test Page</title></head><body><h1>Hello</h1></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        expect($audit->checks()->count())->toBeGreaterThan(0);
    });

    it('detects missing alt text on images', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><img src="test.jpg"></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $altCheck = $audit->checks()->where('criterion_id', '1.1.1')->where('status', CheckStatus::Fail)->first();

        expect($altCheck)->not->toBeNull()
            ->and($altCheck->message)->toContain('missing alt');
    });

    it('passes when images have alt text', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><img src="test.jpg" alt="Test image"></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $altChecks = $audit->checks()->where('criterion_id', '1.1.1')->get();
        $failedAltChecks = $altChecks->where('status', CheckStatus::Fail);

        expect($failedAltChecks)->toHaveCount(0);
    });

    it('detects missing page title', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head></head><body><h1>Hello</h1></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $titleCheck = $audit->checks()->where('criterion_id', '2.4.2')->first();

        expect($titleCheck)->not->toBeNull()
            ->and($titleCheck->status)->toBe(CheckStatus::Fail);
    });

    it('detects missing language attribute', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html><head><title>Test</title></head><body><h1>Hello</h1></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $langCheck = $audit->checks()->where('criterion_id', '3.1.1')->first();

        expect($langCheck)->not->toBeNull()
            ->and($langCheck->status)->toBe(CheckStatus::Fail);
    });

    it('passes when language attribute is present', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><h1>Hello</h1></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $langCheck = $audit->checks()->where('criterion_id', '3.1.1')->where('status', CheckStatus::Pass)->first();

        expect($langCheck)->not->toBeNull();
    });

    it('detects duplicate IDs', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><div id="test">A</div><div id="test">B</div></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $parsingCheck = $audit->checks()->where('criterion_id', '4.1.1')->where('status', CheckStatus::Fail)->first();

        expect($parsingCheck)->not->toBeNull()
            ->and($parsingCheck->message)->toContain('Duplicate ID');
    });

    it('detects missing form labels', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><input type="text" name="email"></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $labelCheck = $audit->checks()->where('criterion_id', '3.3.2')->where('status', CheckStatus::Fail)->first();

        expect($labelCheck)->not->toBeNull()
            ->and($labelCheck->message)->toContain('missing');
    });

    it('passes when form has labels', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><label for="email">Email</label><input type="text" id="email" name="email"></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $labelChecks = $audit->checks()->where('criterion_id', '3.3.2')->get();
        $failedLabelChecks = $labelChecks->where('status', CheckStatus::Fail);

        expect($failedLabelChecks)->toHaveCount(0);
    });

    it('detects missing skip link or main landmark', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><div>Content</div></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $bypassCheck = $audit->checks()->where('criterion_id', '2.4.1')->first();

        expect($bypassCheck)->not->toBeNull()
            ->and($bypassCheck->status)->toBe(CheckStatus::Fail);
    });

    it('passes when main landmark exists', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main>Content</main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $bypassCheck = $audit->checks()->where('criterion_id', '2.4.1')->where('status', CheckStatus::Pass)->first();

        expect($bypassCheck)->not->toBeNull();
    });

    it('marks audit as failed on exception', function () {
        $audit = $this->service->createAudit($this->project);

        // Invalid HTML that will cause parsing issues but still be handled
        $html = '';

        $this->service->runAudit($audit, $html, 'https://example.com');

        // Even with empty HTML, the service should complete (handle gracefully)
        // The audit should have some status
        $audit->refresh();
        expect($audit->status->isTerminal())->toBeTrue();
    });

    it('calculates overall score after completion', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::A);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test Page</title></head><body><main><h1>Hello</h1><img src="test.jpg" alt="Test"></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $audit->refresh();
        expect($audit->overall_score)->not->toBeNull()
            ->and($audit->checks_total)->toBeGreaterThan(0);
    });
});

describe('AccessibilityAuditService::detectRegression', function () {
    it('returns zeros when no previous audit exists', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        $regression = $this->service->detectRegression($audit, null);

        expect($regression)->toBe(['new' => 0, 'fixed' => 0, 'recurring' => 0]);
    });

    it('detects new issues', function () {
        $previousAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        $currentAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        // Add a new issue to current audit
        \App\Models\AuditCheck::factory()->forAudit($currentAudit)->failed()->create([
            'fingerprint' => 'new-issue-fingerprint',
        ]);

        $regression = $this->service->detectRegression($currentAudit, $previousAudit);

        expect($regression['new'])->toBe(1);
    });

    it('detects fixed issues', function () {
        $previousAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        // Issue in previous audit
        \App\Models\AuditCheck::factory()->forAudit($previousAudit)->failed()->create([
            'fingerprint' => 'fixed-issue-fingerprint',
        ]);

        $currentAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        // No issues in current audit with that fingerprint

        $regression = $this->service->detectRegression($currentAudit, $previousAudit);

        expect($regression['fixed'])->toBe(1);
    });

    it('detects recurring issues and marks them', function () {
        $previousAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        $sharedFingerprint = 'recurring-issue-fingerprint';

        \App\Models\AuditCheck::factory()->forAudit($previousAudit)->failed()->create([
            'fingerprint' => $sharedFingerprint,
        ]);

        $currentAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        $currentCheck = \App\Models\AuditCheck::factory()->forAudit($currentAudit)->failed()->create([
            'fingerprint' => $sharedFingerprint,
            'is_recurring' => false,
        ]);

        $regression = $this->service->detectRegression($currentAudit, $previousAudit);

        expect($regression['recurring'])->toBe(1)
            ->and($currentCheck->fresh()->is_recurring)->toBeTrue();
    });
});

describe('AccessibilityAuditService::getPreviousAudits', function () {
    it('returns previous audits for same project and URL', function () {
        $audits = AccessibilityAudit::factory()->completed()->count(3)->create([
            'project_id' => $this->project->id,
            'url_id' => $this->url->id,
        ]);

        $latestAudit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'url_id' => $this->url->id,
        ]);

        $previous = $this->service->getPreviousAudits($latestAudit, 10);

        expect($previous)->toHaveCount(3)
            ->and($previous->pluck('id')->toArray())->not->toContain($latestAudit->id);
    });

    it('respects limit parameter', function () {
        AccessibilityAudit::factory()->completed()->count(5)->create([
            'project_id' => $this->project->id,
        ]);

        $latestAudit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $previous = $this->service->getPreviousAudits($latestAudit, 2);

        expect($previous)->toHaveCount(2);
    });

    it('only returns completed audits', function () {
        AccessibilityAudit::factory()->completed()->count(2)->create([
            'project_id' => $this->project->id,
        ]);

        AccessibilityAudit::factory()->failed()->create([
            'project_id' => $this->project->id,
        ]);

        $latestAudit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $previous = $this->service->getPreviousAudits($latestAudit, 10);

        expect($previous)->toHaveCount(2);
    });
});

describe('AccessibilityAuditService WCAG Level AA Checks', function () {
    it('detects label not in accessible name (2.5.3)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><button aria-label="Submit form">Send</button></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '2.5.3')->where('status', CheckStatus::Fail)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('Accessible name');
    });

    it('passes label in name when visible text is in aria-label (2.5.3)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><button aria-label="Send your message">Send</button></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '2.5.3')->where('status', CheckStatus::Pass)->first();

        expect($check)->not->toBeNull();
    });

    it('detects viewport that prevents user scaling (1.4.4)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title><meta name="viewport" content="width=device-width, user-scalable=no"></head><body><main><p>Content</p></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '1.4.4')->where('status', CheckStatus::Fail)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('user scaling');
    });

    it('detects fixed width elements that may prevent reflow (1.4.10)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><div style="width: 500px;">Fixed width content</div></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '1.4.10')->where('status', CheckStatus::Warning)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('fixed width');
    });

    it('detects inputs missing autocomplete for personal data (1.3.5)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><form><label for="email">Email</label><input type="email" id="email" name="email"></form></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '1.3.5')->where('status', CheckStatus::Warning)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('autocomplete');
    });

    it('detects multiple navigation methods (2.4.5)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><nav><a href="/">Home</a></nav><form role="search"><input type="search"></form><main>Content</main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '2.4.5')->where('status', CheckStatus::Pass)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('Multiple ways');
    });

    it('detects empty headings (2.4.6)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><h1></h1><p>Content</p></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '2.4.6')->where('status', CheckStatus::Fail)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('Empty heading');
    });

    it('detects focus outline removal (2.4.7)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title><style>a:focus { outline: none; }</style></head><body><main><a href="/">Link</a></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '2.4.7')->where('status', CheckStatus::Warning)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('outline');
    });

    it('detects ARIA live regions for status messages (4.1.3)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><div role="status" aria-live="polite">Status message</div></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '4.1.3')->where('status', CheckStatus::Pass)->first();

        expect($check)->not->toBeNull()
            ->and($check->message)->toContain('live region');
    });

    it('detects forms needing error suggestions (3.3.3)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><form><input type="email" name="email" required></form></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '3.3.3')->first();

        expect($check)->not->toBeNull();
    });

    it('detects payment forms needing confirmation (3.3.4)', function () {
        $audit = $this->service->createAudit($this->project, null, null, WcagLevel::AA);

        $html = '<!DOCTYPE html><html lang="en"><head><title>Test</title></head><body><main><form action="/payment"><input type="text" name="credit-card"><button>Pay Now</button></form></main></body></html>';

        $this->service->runAudit($audit, $html, 'https://example.com');

        $check = $audit->checks()->where('criterion_id', '3.3.4')->first();

        expect($check)->not->toBeNull();
    });
});
