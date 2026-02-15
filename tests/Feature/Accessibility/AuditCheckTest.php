<?php

use App\Enums\AuditCategory;
use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\AuditEvidence;
use App\Models\Organization;
use App\Models\Project;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'enterprise']);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->audit = AccessibilityAudit::factory()->create(['project_id' => $this->project->id]);
});

describe('AuditCheck Model', function () {
    it('can be created with factory', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create();

        expect($check)->toBeInstanceOf(AuditCheck::class)
            ->and($check->accessibility_audit_id)->toBe($this->audit->id);
    });

    it('belongs to an audit', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create();

        expect($check->audit)->toBeInstanceOf(AccessibilityAudit::class)
            ->and($check->audit->id)->toBe($this->audit->id);
    });

    it('has many evidence', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create();
        AuditEvidence::factory()->count(3)->forCheck($check)->create();

        expect($check->evidence)->toHaveCount(3);
    });

    it('casts status to CheckStatus enum', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->failed()->create();

        expect($check->status)->toBe(CheckStatus::Fail);
    });

    it('casts wcag_level to WcagLevel enum', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'wcag_level' => 'AA',
        ]);

        expect($check->wcag_level)->toBe(WcagLevel::AA);
    });

    it('casts category to AuditCategory enum', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'category' => 'vision',
        ]);

        expect($check->category)->toBe(AuditCategory::Vision);
    });

    it('casts impact to ImpactLevel enum', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->critical()->create();

        expect($check->impact)->toBe(ImpactLevel::Critical);
    });

    it('casts metadata to array', function () {
        $metadata = ['contrast_ratio' => 3.5, 'required' => 4.5];
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'metadata' => $metadata,
        ]);

        expect($check->metadata)->toBeArray()
            ->and($check->metadata['contrast_ratio'])->toBe(3.5);
    });
});

describe('AuditCheck Status Methods', function () {
    it('can check if passed', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->passed()->create();

        expect($check->isPassed())->toBeTrue()
            ->and($check->isFailed())->toBeFalse();
    });

    it('can check if failed', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->failed()->create();

        expect($check->isFailed())->toBeTrue()
            ->and($check->isPassed())->toBeFalse();
    });

    it('can check if warning', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->warning()->create();

        expect($check->isWarning())->toBeTrue();
    });

    it('can check if needs manual review', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->manualReview()->create();

        expect($check->needsManualReview())->toBeTrue();
    });

    it('can check if requires attention', function () {
        $failed = AuditCheck::factory()->forAudit($this->audit)->failed()->create();
        $warning = AuditCheck::factory()->forAudit($this->audit)->warning()->create();
        $manual = AuditCheck::factory()->forAudit($this->audit)->manualReview()->create();
        $passed = AuditCheck::factory()->forAudit($this->audit)->passed()->create();

        expect($failed->requiresAttention())->toBeTrue()
            ->and($warning->requiresAttention())->toBeTrue()
            ->and($manual->requiresAttention())->toBeTrue()
            ->and($passed->requiresAttention())->toBeFalse();
    });
});

describe('AuditCheck Fingerprinting', function () {
    it('generates fingerprint on creation for failed checks', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->failed()->create([
            'criterion_id' => '1.4.3',
            'element_selector' => 'div.test',
            'message' => 'Low contrast',
        ]);

        expect($check->fingerprint)->not->toBeNull()
            ->and($check->fingerprint)->toHaveLength(64); // SHA256
    });

    it('generates consistent fingerprint for same issue', function () {
        $check1 = AuditCheck::factory()->forAudit($this->audit)->create([
            'criterion_id' => '1.4.3',
            'element_selector' => 'div.test',
            'status' => CheckStatus::Fail,
            'message' => 'Low contrast',
        ]);

        $check2 = AuditCheck::factory()->forAudit($this->audit)->create([
            'criterion_id' => '1.4.3',
            'element_selector' => 'div.test',
            'status' => CheckStatus::Fail,
            'message' => 'Low contrast',
        ]);

        expect($check1->fingerprint)->toBe($check2->fingerprint);
    });

    it('generates different fingerprint for different issues', function () {
        $check1 = AuditCheck::factory()->forAudit($this->audit)->create([
            'criterion_id' => '1.4.3',
            'element_selector' => 'div.test1',
            'status' => CheckStatus::Fail,
            'message' => 'Low contrast',
        ]);

        $check2 = AuditCheck::factory()->forAudit($this->audit)->create([
            'criterion_id' => '1.4.3',
            'element_selector' => 'div.test2',
            'status' => CheckStatus::Fail,
            'message' => 'Low contrast',
        ]);

        expect($check1->fingerprint)->not->toBe($check2->fingerprint);
    });

    it('can be marked as recurring', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->failed()->create([
            'is_recurring' => false,
        ]);

        $check->markAsRecurring();

        expect($check->fresh()->is_recurring)->toBeTrue();
    });
});

describe('AuditCheck Criterion Factories', function () {
    it('creates contrast failure check', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->contrastFailure()->create();

        expect($check->criterion_id)->toBe('1.4.3')
            ->and($check->criterion_name)->toBe('Contrast (Minimum)')
            ->and($check->status)->toBe(CheckStatus::Fail)
            ->and($check->impact)->toBe(ImpactLevel::Serious)
            ->and($check->metadata)->toHaveKey('contrast_ratio');
    });

    it('creates missing alt text check', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->missingAltText()->create();

        expect($check->criterion_id)->toBe('1.1.1')
            ->and($check->criterion_name)->toBe('Non-text Content')
            ->and($check->status)->toBe(CheckStatus::Fail)
            ->and($check->impact)->toBe(ImpactLevel::Critical);
    });

    it('creates keyboard trap check', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->keyboardTrap()->create();

        expect($check->criterion_id)->toBe('2.1.2')
            ->and($check->status)->toBe(CheckStatus::Fail)
            ->and($check->impact)->toBe(ImpactLevel::Critical);
    });

    it('creates focus not visible check', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->focusNotVisible()->create();

        expect($check->criterion_id)->toBe('2.4.7')
            ->and($check->status)->toBe(CheckStatus::Fail)
            ->and($check->code_snippet)->toContain(':focus');
    });
});

describe('AuditCheck Helper Methods', function () {
    it('returns WCAG documentation URL', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'criterion_id' => '1.4.3',
            'documentation_url' => null,
        ]);

        expect($check->getWcagUrl())->toContain('1.4.3');
    });

    it('uses custom documentation URL when set', function () {
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'documentation_url' => 'https://custom-docs.com/1.4.3',
        ]);

        expect($check->getWcagUrl())->toBe('https://custom-docs.com/1.4.3');
    });

    it('gets severity color from impact', function () {
        $critical = AuditCheck::factory()->forAudit($this->audit)->critical()->create();
        $serious = AuditCheck::factory()->forAudit($this->audit)->serious()->create();

        expect($critical->getSeverityColor())->toBe('red')
            ->and($serious->getSeverityColor())->toBe('orange');
    });

    it('gets severity icon from status', function () {
        $passed = AuditCheck::factory()->forAudit($this->audit)->passed()->create();
        $failed = AuditCheck::factory()->forAudit($this->audit)->failed()->create();

        expect($passed->getSeverityIcon())->toBe('check-circle')
            ->and($failed->getSeverityIcon())->toBe('x-circle');
    });

    it('truncates long HTML snippets', function () {
        $longHtml = str_repeat('<div class="test">', 50);
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'element_html' => $longHtml,
        ]);

        $truncated = $check->getTruncatedHtml(100);

        expect(strlen($truncated))->toBeLessThanOrEqual(103) // 100 + '...'
            ->and($truncated)->toEndWith('...');
    });

    it('returns full HTML when short', function () {
        $shortHtml = '<div class="test">Content</div>';
        $check = AuditCheck::factory()->forAudit($this->audit)->create([
            'element_html' => $shortHtml,
        ]);

        expect($check->getTruncatedHtml())->toBe($shortHtml);
    });
});
