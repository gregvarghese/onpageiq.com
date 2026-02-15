<?php

use App\Enums\AuditCategory;
use App\Enums\AuditStatus;
use App\Enums\ComplianceFramework;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Url;
use App\Models\User;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'enterprise']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->url = Url::factory()->create(['project_id' => $this->project->id]);
});

describe('AccessibilityAudit Model', function () {
    it('can be created with factory', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit)->toBeInstanceOf(AccessibilityAudit::class)
            ->and($audit->project_id)->toBe($this->project->id)
            ->and($audit->status)->toBe(AuditStatus::Pending);
    });

    it('belongs to a project', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->project)->toBeInstanceOf(Project::class)
            ->and($audit->project->id)->toBe($this->project->id);
    });

    it('belongs to a url when specified', function () {
        $audit = AccessibilityAudit::factory()->forUrl($this->url)->create();

        expect($audit->url)->toBeInstanceOf(Url::class)
            ->and($audit->url->id)->toBe($this->url->id);
    });

    it('belongs to triggered by user', function () {
        $audit = AccessibilityAudit::factory()->triggeredBy($this->user)->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->triggeredBy)->toBeInstanceOf(User::class)
            ->and($audit->triggeredBy->id)->toBe($this->user->id);
    });

    it('has many checks', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(5)->forAudit($audit)->create();

        expect($audit->checks)->toHaveCount(5);
    });

    it('can filter failed checks', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(3)->forAudit($audit)->passed()->create();
        AuditCheck::factory()->count(2)->forAudit($audit)->failed()->create();

        expect($audit->failedChecks)->toHaveCount(2);
    });

    it('casts status to AuditStatus enum', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'status' => 'running',
        ]);

        expect($audit->status)->toBe(AuditStatus::Running);
    });

    it('casts wcag_level_target to WcagLevel enum', function () {
        $audit = AccessibilityAudit::factory()->levelAA()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->wcag_level_target)->toBe(WcagLevel::AA);
    });

    it('casts framework to ComplianceFramework enum', function () {
        $audit = AccessibilityAudit::factory()->section508()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->framework)->toBe(ComplianceFramework::Section508);
    });

    it('casts scores_by_category to array', function () {
        $scores = [
            'vision' => 85.5,
            'motor' => 90.0,
            'cognitive' => 78.25,
        ];

        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'scores_by_category' => $scores,
        ]);

        expect($audit->scores_by_category)->toBeArray()
            ->and($audit->scores_by_category['vision'])->toBe(85.5);
    });
});

describe('AccessibilityAudit Status Methods', function () {
    it('can check if pending', function () {
        $audit = AccessibilityAudit::factory()->pending()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->isPending())->toBeTrue()
            ->and($audit->isRunning())->toBeFalse()
            ->and($audit->isCompleted())->toBeFalse();
    });

    it('can check if running', function () {
        $audit = AccessibilityAudit::factory()->running()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->isRunning())->toBeTrue()
            ->and($audit->isPending())->toBeFalse();
    });

    it('can check if completed', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->isCompleted())->toBeTrue()
            ->and($audit->isTerminal())->toBeTrue();
    });

    it('can check if failed', function () {
        $audit = AccessibilityAudit::factory()->failed()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->isFailed())->toBeTrue()
            ->and($audit->isTerminal())->toBeTrue();
    });

    it('can mark as running', function () {
        $audit = AccessibilityAudit::factory()->pending()->create([
            'project_id' => $this->project->id,
        ]);

        $audit->markAsRunning();

        expect($audit->fresh()->status)->toBe(AuditStatus::Running)
            ->and($audit->fresh()->started_at)->not->toBeNull();
    });

    it('can mark as completed and recalculates scores', function () {
        $audit = AccessibilityAudit::factory()->running()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(8)->forAudit($audit)->passed()->create();
        AuditCheck::factory()->count(2)->forAudit($audit)->failed()->create();

        $audit->markAsCompleted();

        $audit->refresh();
        expect($audit->status)->toBe(AuditStatus::Completed)
            ->and($audit->completed_at)->not->toBeNull()
            ->and($audit->checks_total)->toBe(10)
            ->and($audit->checks_passed)->toBe(8)
            ->and($audit->checks_failed)->toBe(2)
            ->and((float) $audit->overall_score)->toBe(80.0);
    });

    it('can mark as failed with error message', function () {
        $audit = AccessibilityAudit::factory()->running()->create([
            'project_id' => $this->project->id,
        ]);

        $audit->markAsFailed('Connection timeout');

        expect($audit->fresh()->status)->toBe(AuditStatus::Failed)
            ->and($audit->fresh()->error_message)->toBe('Connection timeout')
            ->and($audit->fresh()->completed_at)->not->toBeNull();
    });

    it('can mark as cancelled', function () {
        $audit = AccessibilityAudit::factory()->running()->create([
            'project_id' => $this->project->id,
        ]);

        $audit->markAsCancelled();

        expect($audit->fresh()->status)->toBe(AuditStatus::Cancelled)
            ->and($audit->fresh()->isTerminal())->toBeTrue();
    });
});

describe('AccessibilityAudit Score Calculations', function () {
    it('calculates overall score excluding not applicable checks', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(7)->forAudit($audit)->passed()->create();
        AuditCheck::factory()->count(3)->forAudit($audit)->failed()->create();
        AuditCheck::factory()->count(5)->forAudit($audit)->notApplicable()->create();

        $audit->recalculateScores();

        expect((float) $audit->overall_score)->toBe(70.0) // 7 / (7+3) = 70%
            ->and($audit->checks_total)->toBe(15)
            ->and($audit->checks_not_applicable)->toBe(5);
    });

    it('calculates scores by category', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        // Vision: 2 pass, 1 fail = 66.67%
        AuditCheck::factory()->forAudit($audit)->passed()->create(['category' => AuditCategory::Vision]);
        AuditCheck::factory()->forAudit($audit)->passed()->create(['category' => AuditCategory::Vision]);
        AuditCheck::factory()->forAudit($audit)->failed()->create(['category' => AuditCategory::Vision]);

        // Motor: 3 pass, 0 fail = 100%
        AuditCheck::factory()->count(3)->forAudit($audit)->passed()->create(['category' => AuditCategory::Motor]);

        $audit->recalculateScores();

        expect($audit->getCategoryScore(AuditCategory::Vision))->toBe(66.67)
            ->and($audit->getCategoryScore(AuditCategory::Motor))->toBe(100.00);
    });

    it('returns 100% when all checks are not applicable', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(5)->forAudit($audit)->notApplicable()->create();

        $audit->recalculateScores();

        expect((float) $audit->overall_score)->toBe(100.0);
    });

    it('calculates compliance percentage for target level', function () {
        $audit = AccessibilityAudit::factory()->levelAA()->create([
            'project_id' => $this->project->id,
        ]);

        // Level A checks: 3 pass, 1 fail
        AuditCheck::factory()->count(3)->forAudit($audit)->passed()->create(['wcag_level' => WcagLevel::A]);
        AuditCheck::factory()->forAudit($audit)->failed()->create(['wcag_level' => WcagLevel::A]);

        // Level AA checks: 2 pass, 0 fail
        AuditCheck::factory()->count(2)->forAudit($audit)->passed()->create(['wcag_level' => WcagLevel::AA]);

        // Level AAA checks (should be excluded from AA compliance): 1 fail
        AuditCheck::factory()->forAudit($audit)->failed()->create(['wcag_level' => WcagLevel::AAA]);

        // AA compliance = 5 pass / 6 total (A + AA) = 83.33%
        expect($audit->getCompliancePercentage())->toBe(83.33);
    });
});

describe('AccessibilityAudit Duration', function () {
    it('calculates duration in seconds', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'started_at' => now()->subSeconds(120),
            'completed_at' => now(),
        ]);

        expect($audit->getDurationInSeconds())->toBe(120);
    });

    it('returns null when not completed', function () {
        $audit = AccessibilityAudit::factory()->running()->create([
            'project_id' => $this->project->id,
        ]);

        expect($audit->getDurationInSeconds())->toBeNull();
    });

    it('formats duration as human readable', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'started_at' => now()->subMinutes(2)->subSeconds(30),
            'completed_at' => now(),
        ]);

        expect($audit->getFormattedDuration())->toBe('2m 30s');
    });

    it('formats short duration in seconds', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'started_at' => now()->subSeconds(45),
            'completed_at' => now(),
        ]);

        expect($audit->getFormattedDuration())->toBe('45 seconds');
    });
});
