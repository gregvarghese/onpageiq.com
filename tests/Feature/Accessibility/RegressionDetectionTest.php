<?php

use App\Enums\AlertType;
use App\Enums\CheckStatus;
use App\Enums\DeadlineType;
use App\Enums\ImpactLevel;
use App\Jobs\Accessibility\RegressionDetectionJob;
use App\Models\AccessibilityAlert;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\ComplianceDeadline;
use App\Models\Project;
use App\Services\Accessibility\RegressionService;

describe('ComplianceDeadline Model', function () {
    describe('creation', function () {
        it('can be created with factory', function () {
            $deadline = ComplianceDeadline::factory()->create();

            expect($deadline)->toBeInstanceOf(ComplianceDeadline::class)
                ->and($deadline->id)->not->toBeNull()
                ->and($deadline->title)->not->toBeNull();
        });

        it('belongs to a project', function () {
            $project = Project::factory()->create();
            $deadline = ComplianceDeadline::factory()->create([
                'project_id' => $project->id,
            ]);

            expect($deadline->project->id)->toBe($project->id);
        });

        it('has correct deadline type', function () {
            $deadline = ComplianceDeadline::factory()->wcagCompliance()->create();

            expect($deadline->type)->toBe(DeadlineType::WcagCompliance);
        });
    });

    describe('deadline status', function () {
        it('calculates days until deadline', function () {
            $deadline = ComplianceDeadline::factory()->create([
                'deadline_date' => now()->addDays(10),
            ]);

            expect($deadline->getDaysUntilDeadline())->toBe(10);
        });

        it('detects overdue deadline', function () {
            $deadline = ComplianceDeadline::factory()->overdue()->create();

            expect($deadline->isOverdue())->toBeTrue();
        });

        it('detects approaching deadline', function () {
            $deadline = ComplianceDeadline::factory()->create([
                'deadline_date' => now()->addDays(7),
                'reminder_days' => [14, 7, 3, 1],
            ]);

            expect($deadline->isApproaching())->toBeTrue();
        });

        it('can be marked as met', function () {
            $deadline = ComplianceDeadline::factory()->create();

            $deadline->markAsMet();

            expect($deadline->is_met)->toBeTrue()
                ->and($deadline->met_at)->not->toBeNull();
        });
    });

    describe('reminders', function () {
        it('should send reminder when due', function () {
            $deadline = ComplianceDeadline::factory()->create([
                'deadline_date' => now()->addDays(7),
                'reminder_days' => [14, 7, 3, 1],
                'notified_days' => [],
            ]);

            expect($deadline->shouldSendReminder())->toBeTrue();
        });

        it('should not send reminder if already notified', function () {
            $deadline = ComplianceDeadline::factory()->create([
                'deadline_date' => now()->addDays(7),
                'reminder_days' => [14, 7, 3, 1],
                'notified_days' => [7],
            ]);

            expect($deadline->shouldSendReminder())->toBeFalse();
        });

        it('can mark reminder as sent', function () {
            $deadline = ComplianceDeadline::factory()->create([
                'notified_days' => [],
            ]);

            $deadline->markReminderSent(7);

            expect($deadline->notified_days)->toContain(7);
        });
    });

    describe('scopes', function () {
        it('filters active deadlines', function () {
            ComplianceDeadline::factory()->create(['is_active' => true]);
            ComplianceDeadline::factory()->inactive()->create();

            $active = ComplianceDeadline::active()->get();

            expect($active)->toHaveCount(1);
        });

        it('filters upcoming deadlines', function () {
            ComplianceDeadline::factory()->create([
                'deadline_date' => now()->addDays(10),
                'is_met' => false,
            ]);
            ComplianceDeadline::factory()->overdue()->create();

            $upcoming = ComplianceDeadline::upcoming()->get();

            expect($upcoming)->toHaveCount(1);
        });

        it('filters overdue deadlines', function () {
            ComplianceDeadline::factory()->create([
                'deadline_date' => now()->addDays(10),
            ]);
            ComplianceDeadline::factory()->overdue()->create();

            $overdue = ComplianceDeadline::overdue()->get();

            expect($overdue)->toHaveCount(1);
        });
    });
});

describe('AccessibilityAlert Model', function () {
    describe('creation', function () {
        it('can be created with factory', function () {
            $alert = AccessibilityAlert::factory()->create();

            expect($alert)->toBeInstanceOf(AccessibilityAlert::class)
                ->and($alert->id)->not->toBeNull();
        });

        it('has correct alert type', function () {
            $alert = AccessibilityAlert::factory()->scoreThreshold()->create();

            expect($alert->type)->toBe(AlertType::ScoreThreshold);
        });
    });

    describe('alert actions', function () {
        it('can be marked as read', function () {
            $alert = AccessibilityAlert::factory()->create();

            $alert->markAsRead();

            expect($alert->is_read)->toBeTrue()
                ->and($alert->read_at)->not->toBeNull();
        });

        it('can be dismissed', function () {
            $alert = AccessibilityAlert::factory()->create();

            $alert->dismiss();

            expect($alert->is_dismissed)->toBeTrue()
                ->and($alert->dismissed_at)->not->toBeNull();
        });

        it('can mark email as sent', function () {
            $alert = AccessibilityAlert::factory()->create();

            $alert->markEmailSent();

            expect($alert->email_sent)->toBeTrue()
                ->and($alert->email_sent_at)->not->toBeNull();
        });
    });

    describe('alert properties', function () {
        it('returns severity from type', function () {
            $alert = AccessibilityAlert::factory()->criticalIssue()->create();

            expect($alert->getSeverity())->toBe('critical');
        });

        it('returns color from type', function () {
            $alert = AccessibilityAlert::factory()->regression()->create();

            expect($alert->getColor())->toBe('orange');
        });

        it('determines if email should be sent', function () {
            $regressionAlert = AccessibilityAlert::factory()->regression()->create();
            $auditCompleteAlert = AccessibilityAlert::factory()->create([
                'type' => AlertType::AuditComplete,
            ]);

            expect($regressionAlert->shouldSendEmail())->toBeTrue()
                ->and($auditCompleteAlert->shouldSendEmail())->toBeFalse();
        });
    });

    describe('scopes', function () {
        it('filters unread alerts', function () {
            AccessibilityAlert::factory()->create(['is_read' => false]);
            AccessibilityAlert::factory()->read()->create();

            $unread = AccessibilityAlert::unread()->get();

            expect($unread)->toHaveCount(1);
        });

        it('filters alerts needing email', function () {
            AccessibilityAlert::factory()->regression()->create(['email_sent' => false]);
            AccessibilityAlert::factory()->regression()->emailSent()->create();
            AccessibilityAlert::factory()->create([
                'type' => AlertType::AuditComplete,
                'email_sent' => false,
            ]);

            $needingEmail = AccessibilityAlert::needingEmail()->get();

            expect($needingEmail)->toHaveCount(1);
        });
    });

    describe('static creators', function () {
        it('creates score threshold alert', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            $alert = AccessibilityAlert::createScoreThresholdAlert($audit, 65.0, 80.0);

            expect($alert->type)->toBe(AlertType::ScoreThreshold)
                ->and($alert->data['score'])->toEqual(65.0)
                ->and($alert->data['threshold'])->toEqual(80.0);
        });

        it('creates regression alert', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            $alert = AccessibilityAlert::createRegressionAlert($audit, [
                'new_issues_count' => 5,
                'previous_score' => 85.0,
                'current_score' => 72.0,
            ]);

            expect($alert->type)->toBe(AlertType::Regression)
                ->and($alert->message)->toContain('5 new issues');
        });

        it('creates deadline reminder alert', function () {
            $deadline = ComplianceDeadline::factory()->create();

            $alert = AccessibilityAlert::createDeadlineReminder($deadline, 7);

            expect($alert->type)->toBe(AlertType::DeadlineReminder)
                ->and($alert->data['days_until'])->toBe(7);
        });
    });
});

describe('RegressionDetectionJob', function () {
    it('detects regression between audits', function () {
        $project = Project::factory()->create();

        // Create previous audit with good score
        $previousAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $project->id,
            'overall_score' => 90,
        ]);

        // Add some passing checks to previous audit
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $previousAudit->id,
            'status' => CheckStatus::Pass,
            'criterion_id' => '1.1.1',
            'criterion_name' => 'Non-text Content',
        ]);

        // Create current audit with worse score
        $currentAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $project->id,
            'url_id' => $previousAudit->url_id,
            'overall_score' => 70,
        ]);

        // Add failing checks to current audit
        AuditCheck::factory()->count(5)->create([
            'accessibility_audit_id' => $currentAudit->id,
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Serious,
            'criterion_id' => '1.4.3',
            'criterion_name' => 'Contrast (Minimum)',
        ]);

        // Run the job
        $job = new RegressionDetectionJob($currentAudit);
        $job->handle();

        // Refresh and check metadata
        $currentAudit->refresh();

        expect($currentAudit->metadata)->toHaveKey('regression')
            ->and($currentAudit->metadata['regression']['has_regression'])->toBeTrue()
            ->and($currentAudit->metadata['regression']['score_diff'])->toEqual(-20);
    });

    it('marks recurring issues', function () {
        $project = Project::factory()->create();

        // Create previous audit with a failing check
        $previousAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $project->id,
        ]);

        $fingerprint = hash('sha256', '1.1.1|img.hero|fail|Missing alt text');

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $previousAudit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
            'criterion_name' => 'Non-text Content',
            'fingerprint' => $fingerprint,
        ]);

        // Create current audit with same issue
        $currentAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $project->id,
            'url_id' => $previousAudit->url_id,
        ]);

        $currentCheck = AuditCheck::factory()->create([
            'accessibility_audit_id' => $currentAudit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
            'criterion_name' => 'Non-text Content',
            'fingerprint' => $fingerprint,
            'is_recurring' => false,
        ]);

        // Run the job
        $job = new RegressionDetectionJob($currentAudit);
        $job->handle();

        // Refresh and check
        $currentCheck->refresh();
        expect($currentCheck->is_recurring)->toBeTrue();
    });

    it('creates alerts for critical issues', function () {
        $project = Project::factory()->create();

        // Create previous audit
        $previousAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $project->id,
            'overall_score' => 90,
        ]);

        // Create current audit with critical issue
        $currentAudit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $project->id,
            'url_id' => $previousAudit->url_id,
            'overall_score' => 60,
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $currentAudit->id,
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Critical,
            'criterion_id' => '2.1.1',
            'criterion_name' => 'Keyboard',
            'is_recurring' => false,
        ]);

        // Run the job
        $job = new RegressionDetectionJob($currentAudit);
        $job->handle();

        // Check alerts were created
        $alerts = AccessibilityAlert::where('accessibility_audit_id', $currentAudit->id)->get();

        expect($alerts)->not->toBeEmpty()
            ->and($alerts->where('type', AlertType::Regression)->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('RegressionService', function () {
    beforeEach(function () {
        $this->service = new RegressionService;
    });

    describe('trends', function () {
        it('returns empty data for project without audits', function () {
            $project = Project::factory()->create();

            $trends = $this->service->getTrends($project);

            expect($trends['has_data'])->toBeFalse();
        });

        it('calculates trends for multiple audits', function () {
            $project = Project::factory()->create();

            // Create 3 audits with different scores
            AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 70,
                'completed_at' => now()->subDays(3),
            ]);
            AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 80,
                'completed_at' => now()->subDays(2),
            ]);
            AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 85,
                'completed_at' => now()->subDay(),
            ]);

            $trends = $this->service->getTrends($project);

            expect($trends['has_data'])->toBeTrue()
                ->and($trends['scores'])->toHaveCount(3)
                ->and($trends['summary']['score_trend'])->toBe('improving');
        });
    });

    describe('comparison', function () {
        it('compares two audits', function () {
            $project = Project::factory()->create();

            $previous = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 85,
            ]);

            $current = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 90,
            ]);

            // Add checks
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $previous->id,
                'status' => CheckStatus::Fail,
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'fingerprint' => 'fp-1',
            ]);

            AuditCheck::factory()->create([
                'accessibility_audit_id' => $current->id,
                'status' => CheckStatus::Pass,
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
            ]);

            $comparison = $this->service->compareAudits($current, $previous);

            expect($comparison['score_change'])->toBe(5.0)
                ->and($comparison['fixed_issues']['count'])->toBe(1)
                ->and($comparison['has_regression'])->toBeFalse();
        });

        it('detects regression in comparison', function () {
            $project = Project::factory()->create();

            $previous = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 90,
            ]);

            $current = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'overall_score' => 70,
            ]);

            // Add new critical issue
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $current->id,
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Critical,
                'criterion_id' => '2.1.1',
                'criterion_name' => 'Keyboard',
                'fingerprint' => 'new-fp',
            ]);

            $comparison = $this->service->compareAudits($current, $previous);

            expect($comparison['has_regression'])->toBeTrue()
                ->and($comparison['new_issues']['count'])->toBe(1);
        });
    });

    describe('resolution rate', function () {
        it('calculates resolution rate', function () {
            $project = Project::factory()->create();

            // Create audits showing issues being fixed
            $audit1 = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'completed_at' => now()->subDays(2),
            ]);
            $audit2 = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $project->id,
                'completed_at' => now()->subDay(),
            ]);

            // Audit 1 has 2 issues
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit1->id,
                'status' => CheckStatus::Fail,
                'fingerprint' => 'issue-1',
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
            ]);
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit1->id,
                'status' => CheckStatus::Fail,
                'fingerprint' => 'issue-2',
                'criterion_id' => '1.4.3',
                'criterion_name' => 'Contrast',
            ]);

            // Audit 2 has 1 issue (1 fixed, 0 new)
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit2->id,
                'status' => CheckStatus::Fail,
                'fingerprint' => 'issue-1',
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
            ]);

            $rate = $this->service->getResolutionRate($project);

            expect($rate['has_data'])->toBeTrue()
                ->and($rate['total_fixed'])->toBe(1)
                ->and($rate['total_new'])->toBe(0);
        });
    });

    describe('persistent issues', function () {
        it('identifies persistent issues', function () {
            $project = Project::factory()->create();

            // Create 3 audits with same recurring issue
            $fingerprint = 'persistent-issue';

            for ($i = 0; $i < 3; $i++) {
                $audit = AccessibilityAudit::factory()->completed()->create([
                    'project_id' => $project->id,
                    'completed_at' => now()->subDays(3 - $i),
                ]);

                AuditCheck::factory()->create([
                    'accessibility_audit_id' => $audit->id,
                    'status' => CheckStatus::Fail,
                    'fingerprint' => $fingerprint,
                    'criterion_id' => '1.1.1',
                    'criterion_name' => 'Non-text Content',
                    'message' => 'Missing alt text on hero image',
                    'impact' => ImpactLevel::Serious,
                ]);
            }

            $persistent = $this->service->getPersistentIssues($project, 3);

            expect($persistent)->toHaveCount(1)
                ->and($persistent->first()['occurrences'])->toBe(3);
        });
    });
});

describe('Enums', function () {
    describe('AlertType', function () {
        it('has all required types', function () {
            expect(AlertType::cases())->toHaveCount(7);
        });

        it('has labels', function () {
            expect(AlertType::ScoreThreshold->label())->toBe('Score Threshold Breach')
                ->and(AlertType::Regression->label())->toBe('Regression Detected');
        });

        it('has severities', function () {
            expect(AlertType::NewCriticalIssue->severity())->toBe('critical')
                ->and(AlertType::Regression->severity())->toBe('serious')
                ->and(AlertType::IssueFixed->severity())->toBe('info');
        });

        it('determines email sending', function () {
            expect(AlertType::Regression->shouldEmail())->toBeTrue()
                ->and(AlertType::IssueFixed->shouldEmail())->toBeFalse();
        });
    });

    describe('DeadlineType', function () {
        it('has all required types', function () {
            expect(DeadlineType::cases())->toHaveCount(7);
        });

        it('has labels', function () {
            expect(DeadlineType::WcagCompliance->label())->toBe('WCAG Compliance')
                ->and(DeadlineType::Section508->label())->toBe('Section 508 Compliance');
        });

        it('has default reminder days', function () {
            $legal = DeadlineType::LegalRequirement->defaultReminderDays();
            $custom = DeadlineType::Custom->defaultReminderDays();

            expect($legal)->toContain(30)
                ->and($custom)->not->toContain(30);
        });
    });
});
