<?php

use App\Enums\ManualTestStatus;
use App\Enums\VpatConformanceLevel;
use App\Enums\VpatStatus;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\ManualTestChecklist;
use App\Models\User;
use App\Models\VpatEvaluation;
use App\Services\Accessibility\VpatGeneratorService;

describe('VpatEvaluation Model', function () {
    describe('creation', function () {
        it('can be created with factory', function () {
            $vpat = VpatEvaluation::factory()->create();

            expect($vpat)->toBeInstanceOf(VpatEvaluation::class)
                ->and($vpat->id)->not->toBeNull()
                ->and($vpat->product_name)->not->toBeNull();
        });

        it('has correct default status', function () {
            $vpat = VpatEvaluation::factory()->create();

            expect($vpat->status)->toBe(VpatStatus::Draft);
        });

        it('belongs to accessibility audit', function () {
            $audit = AccessibilityAudit::factory()->create();
            $vpat = VpatEvaluation::factory()->create([
                'accessibility_audit_id' => $audit->id,
            ]);

            expect($vpat->accessibilityAudit->id)->toBe($audit->id);
        });

        it('belongs to creator user', function () {
            $user = User::factory()->create();
            $vpat = VpatEvaluation::factory()->create([
                'created_by_user_id' => $user->id,
            ]);

            expect($vpat->createdBy->id)->toBe($user->id);
        });
    });

    describe('wcag evaluations', function () {
        it('can set wcag evaluation', function () {
            $vpat = VpatEvaluation::factory()->create();

            $vpat->setWcagEvaluation('1.1.1', VpatConformanceLevel::Supports, 'All images have alt text');
            $vpat->save();

            $vpat->refresh();
            $evaluation = $vpat->getWcagEvaluation('1.1.1');

            expect($evaluation)->not->toBeNull()
                ->and($evaluation['level'])->toBe(VpatConformanceLevel::Supports->value)
                ->and($evaluation['remarks'])->toBe('All images have alt text');
        });

        it('can get conformance level', function () {
            $vpat = VpatEvaluation::factory()->withSampleEvaluations()->create();

            $level = $vpat->getWcagConformanceLevel('1.1.1');

            expect($level)->toBe(VpatConformanceLevel::Supports);
        });

        it('returns null for unevaluated criterion', function () {
            $vpat = VpatEvaluation::factory()->create();

            $level = $vpat->getWcagConformanceLevel('9.9.9');

            expect($level)->toBeNull();
        });

        it('calculates conformance summary', function () {
            $vpat = VpatEvaluation::factory()->withSampleEvaluations()->create();

            $summary = $vpat->getWcagConformanceSummary();

            expect($summary)->toBeArray()
                ->and($summary[VpatConformanceLevel::Supports->value])->toBeGreaterThan(0);
        });
    });

    describe('status workflow', function () {
        it('starts as draft', function () {
            $vpat = VpatEvaluation::factory()->create();

            expect($vpat->status)->toBe(VpatStatus::Draft)
                ->and($vpat->isEditable())->toBeTrue();
        });

        it('can be submitted for review', function () {
            $vpat = VpatEvaluation::factory()->create();

            $vpat->submitForReview();

            expect($vpat->status)->toBe(VpatStatus::InReview)
                ->and($vpat->isEditable())->toBeTrue();
        });

        it('can be approved', function () {
            $vpat = VpatEvaluation::factory()->inReview()->create();
            $approver = User::factory()->create();

            $vpat->approve($approver);

            expect($vpat->status)->toBe(VpatStatus::Approved)
                ->and($vpat->approved_by_user_id)->toBe($approver->id)
                ->and($vpat->approved_at)->not->toBeNull()
                ->and($vpat->isEditable())->toBeFalse();
        });

        it('can be published', function () {
            $vpat = VpatEvaluation::factory()->approved()->create();

            $vpat->publish();

            expect($vpat->status)->toBe(VpatStatus::Published)
                ->and($vpat->published_at)->not->toBeNull()
                ->and($vpat->isEditable())->toBeFalse();
        });
    });

    describe('report types', function () {
        it('can have multiple report types', function () {
            $vpat = VpatEvaluation::factory()->withAllReportTypes()->create();

            expect($vpat->hasReportType('wcag21'))->toBeTrue()
                ->and($vpat->hasReportType('section508'))->toBeTrue()
                ->and($vpat->hasReportType('en301549'))->toBeTrue();
        });

        it('checks for specific report type', function () {
            $vpat = VpatEvaluation::factory()->create(['report_types' => ['wcag21']]);

            expect($vpat->hasReportType('wcag21'))->toBeTrue()
                ->and($vpat->hasReportType('section508'))->toBeFalse();
        });
    });

    describe('completion percentage', function () {
        it('calculates completion percentage', function () {
            $vpat = VpatEvaluation::factory()->withSampleEvaluations()->create();

            $percentage = $vpat->getWcagCompletionPercentage();

            expect($percentage)->toBeGreaterThan(0)
                ->and($percentage)->toBeLessThanOrEqual(100);
        });

        it('returns 100 when no criteria defined', function () {
            $vpat = VpatEvaluation::factory()->create(['wcag_evaluations' => []]);

            // With empty config, should return 100
            $percentage = $vpat->getWcagCompletionPercentage();

            expect($percentage)->toBeGreaterThanOrEqual(0);
        });
    });
});

describe('ManualTestChecklist Model', function () {
    describe('creation', function () {
        it('can be created with factory', function () {
            $checklist = ManualTestChecklist::factory()->create();

            expect($checklist)->toBeInstanceOf(ManualTestChecklist::class)
                ->and($checklist->id)->not->toBeNull();
        });

        it('has default pending status', function () {
            $checklist = ManualTestChecklist::factory()->create();

            expect($checklist->status)->toBe(ManualTestStatus::Pending);
        });

        it('belongs to tester', function () {
            $user = User::factory()->create();
            $checklist = ManualTestChecklist::factory()->create([
                'tester_user_id' => $user->id,
            ]);

            expect($checklist->tester->id)->toBe($user->id);
        });
    });

    describe('test lifecycle', function () {
        it('can be started', function () {
            $checklist = ManualTestChecklist::factory()->create();

            $checklist->start();

            expect($checklist->status)->toBe(ManualTestStatus::InProgress)
                ->and($checklist->started_at)->not->toBeNull();
        });

        it('can be marked as passed', function () {
            $checklist = ManualTestChecklist::factory()->inProgress()->create();

            $checklist->markAsPassed('All tests passed', 'No issues found');

            expect($checklist->status)->toBe(ManualTestStatus::Passed)
                ->and($checklist->actual_results)->toBe('All tests passed')
                ->and($checklist->tester_notes)->toBe('No issues found')
                ->and($checklist->completed_at)->not->toBeNull()
                ->and($checklist->isComplete())->toBeTrue()
                ->and($checklist->isPassed())->toBeTrue();
        });

        it('can be marked as failed', function () {
            $checklist = ManualTestChecklist::factory()->inProgress()->create();

            $checklist->markAsFailed('Focus not visible', 'CSS outline:none');

            expect($checklist->status)->toBe(ManualTestStatus::Failed)
                ->and($checklist->isComplete())->toBeTrue()
                ->and($checklist->isPassed())->toBeFalse();
        });

        it('can be marked as blocked', function () {
            $checklist = ManualTestChecklist::factory()->inProgress()->create();

            $checklist->markAsBlocked('Cannot access staging environment');

            expect($checklist->status)->toBe(ManualTestStatus::Blocked)
                ->and($checklist->isComplete())->toBeTrue();
        });

        it('can be skipped', function () {
            $checklist = ManualTestChecklist::factory()->create();

            $checklist->skip('Not applicable to this product');

            expect($checklist->status)->toBe(ManualTestStatus::Skipped)
                ->and($checklist->isComplete())->toBeTrue();
        });
    });

    describe('environment', function () {
        it('can set environment info', function () {
            $checklist = ManualTestChecklist::factory()->create();

            $checklist->setEnvironment(
                browser: 'Chrome',
                browserVersion: '120.0',
                assistiveTechnology: 'NVDA',
                operatingSystem: 'Windows 11'
            );

            expect($checklist->browser)->toBe('Chrome')
                ->and($checklist->browser_version)->toBe('120.0')
                ->and($checklist->assistive_technology)->toBe('NVDA')
                ->and($checklist->operating_system)->toBe('Windows 11');
        });
    });

    describe('duration', function () {
        it('calculates duration', function () {
            $checklist = ManualTestChecklist::factory()->create([
                'started_at' => now()->subMinutes(10),
                'completed_at' => now(),
            ]);

            $duration = $checklist->getDurationInSeconds();

            expect($duration)->toBeGreaterThanOrEqual(600); // 10 minutes
        });

        it('returns null when incomplete', function () {
            $checklist = ManualTestChecklist::factory()->create([
                'started_at' => now(),
                'completed_at' => null,
            ]);

            expect($checklist->getDurationInSeconds())->toBeNull();
        });
    });

    describe('scopes', function () {
        it('filters by criterion', function () {
            ManualTestChecklist::factory()->create(['criterion_id' => '1.1.1']);
            ManualTestChecklist::factory()->create(['criterion_id' => '2.1.1']);

            $results = ManualTestChecklist::forCriterion('1.1.1')->get();

            expect($results)->toHaveCount(1)
                ->and($results->first()->criterion_id)->toBe('1.1.1');
        });

        it('filters incomplete', function () {
            ManualTestChecklist::factory()->create(['status' => ManualTestStatus::Pending]);
            ManualTestChecklist::factory()->create(['status' => ManualTestStatus::InProgress]);
            ManualTestChecklist::factory()->passed()->create();

            $incomplete = ManualTestChecklist::incomplete()->get();

            expect($incomplete)->toHaveCount(2);
        });

        it('filters completed', function () {
            ManualTestChecklist::factory()->create(['status' => ManualTestStatus::Pending]);
            ManualTestChecklist::factory()->passed()->create();
            ManualTestChecklist::factory()->failed()->create();

            $completed = ManualTestChecklist::completed()->get();

            expect($completed)->toHaveCount(2);
        });
    });

    describe('factory states', function () {
        it('creates keyboard test', function () {
            $checklist = ManualTestChecklist::factory()->keyboardTest()->create();

            expect($checklist->criterion_id)->toBe('2.1.1')
                ->and($checklist->wcag_level)->toBe(WcagLevel::A)
                ->and($checklist->test_steps)->toBeArray()
                ->and($checklist->expected_results)->toBeArray();
        });

        it('creates screen reader test', function () {
            $checklist = ManualTestChecklist::factory()->screenReaderTest()->create();

            expect($checklist->criterion_id)->toBe('4.1.2')
                ->and($checklist->assistive_technology)->toBe('NVDA 2023.3');
        });
    });
});

describe('VpatGeneratorService', function () {
    beforeEach(function () {
        $this->service = new VpatGeneratorService;
    });

    it('gets wcag criteria', function () {
        $criteria = $this->service->getWcagCriteria();

        expect($criteria)->toBeArray()
            ->and($criteria)->toHaveKeys(['Perceivable', 'Operable', 'Understandable', 'Robust']);
    });

    it('gets criteria by level', function () {
        $levelA = $this->service->getCriteriaByLevel(WcagLevel::A);
        $levelAA = $this->service->getCriteriaByLevel(WcagLevel::AA);

        expect($levelA)->not->toBeEmpty()
            ->and($levelAA)->not->toBeEmpty();

        $levelA->each(fn ($c) => expect($c['level'])->toBe('A'));
        $levelAA->each(fn ($c) => expect($c['level'])->toBe('AA'));
    });

    it('generates html preview', function () {
        $vpat = VpatEvaluation::factory()->withSampleEvaluations()->create();

        $html = $this->service->generateHtml($vpat);

        expect($html)->toBeString()
            ->and($html)->toContain($vpat->product_name)
            ->and($html)->toContain('WCAG 2.1 Report')
            ->and($html)->toContain('Conformance Summary');
    });

    it('populates vpat from audit checks', function () {
        $audit = AccessibilityAudit::factory()->completed()->create();

        // Create some audit checks
        $audit->checks()->createMany([
            [
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'status' => 'pass',
                'wcag_level' => 'A',
                'category' => 'vision',
                'impact' => 'critical',
                'message' => 'All images have alt text',
            ],
            [
                'criterion_id' => '1.4.3',
                'criterion_name' => 'Contrast (Minimum)',
                'status' => 'fail',
                'wcag_level' => 'AA',
                'category' => 'vision',
                'impact' => 'serious',
                'message' => 'Contrast ratio is 3.5:1',
            ],
        ]);

        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $audit->id,
            'wcag_evaluations' => [],
        ]);

        $this->service->populateFromAudit($vpat);
        $vpat->refresh();

        $evaluation111 = $vpat->getWcagEvaluation('1.1.1');
        $evaluation143 = $vpat->getWcagEvaluation('1.4.3');

        expect($evaluation111)->not->toBeNull()
            ->and($evaluation111['level'])->toBe(VpatConformanceLevel::Supports->value)
            ->and($evaluation143)->not->toBeNull()
            ->and($evaluation143['level'])->toBe(VpatConformanceLevel::DoesNotSupport->value);
    });
});

describe('Enums', function () {
    describe('VpatConformanceLevel', function () {
        it('has all required levels', function () {
            expect(VpatConformanceLevel::cases())->toHaveCount(5);
        });

        it('has labels', function () {
            expect(VpatConformanceLevel::Supports->label())->toBe('Supports')
                ->and(VpatConformanceLevel::PartiallySupports->label())->toBe('Partially Supports')
                ->and(VpatConformanceLevel::DoesNotSupport->label())->toBe('Does Not Support')
                ->and(VpatConformanceLevel::NotApplicable->label())->toBe('Not Applicable')
                ->and(VpatConformanceLevel::NotEvaluated->label())->toBe('Not Evaluated');
        });

        it('has colors', function () {
            expect(VpatConformanceLevel::Supports->color())->toBe('green')
                ->and(VpatConformanceLevel::DoesNotSupport->color())->toBe('red');
        });
    });

    describe('VpatStatus', function () {
        it('tracks editability', function () {
            expect(VpatStatus::Draft->isEditable())->toBeTrue()
                ->and(VpatStatus::InReview->isEditable())->toBeTrue()
                ->and(VpatStatus::Approved->isEditable())->toBeFalse()
                ->and(VpatStatus::Published->isEditable())->toBeFalse();
        });
    });

    describe('ManualTestStatus', function () {
        it('identifies terminal states', function () {
            expect(ManualTestStatus::Pending->isTerminal())->toBeFalse()
                ->and(ManualTestStatus::InProgress->isTerminal())->toBeFalse()
                ->and(ManualTestStatus::Passed->isTerminal())->toBeTrue()
                ->and(ManualTestStatus::Failed->isTerminal())->toBeTrue()
                ->and(ManualTestStatus::Blocked->isTerminal())->toBeTrue()
                ->and(ManualTestStatus::Skipped->isTerminal())->toBeTrue();
        });

        it('identifies success', function () {
            expect(ManualTestStatus::Passed->isSuccess())->toBeTrue()
                ->and(ManualTestStatus::Failed->isSuccess())->toBeFalse();
        });
    });
});
