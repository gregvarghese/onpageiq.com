<?php

use App\Enums\CheckStatus;
use App\Enums\FixComplexity;
use App\Enums\ImpactLevel;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Services\Accessibility\RemediationService;

describe('FixComplexity Enum', function () {
    it('has all complexity levels', function () {
        expect(FixComplexity::cases())->toHaveCount(5);
    });

    it('has labels', function () {
        expect(FixComplexity::Quick->label())->toBe('Quick Fix')
            ->and(FixComplexity::Easy->label())->toBe('Easy')
            ->and(FixComplexity::Medium->label())->toBe('Medium')
            ->and(FixComplexity::Complex->label())->toBe('Complex')
            ->and(FixComplexity::Architectural->label())->toBe('Architectural Change');
    });

    it('has effort estimates', function () {
        expect(FixComplexity::Quick->effortMinutes())->toBe(5)
            ->and(FixComplexity::Easy->effortMinutes())->toBe(15)
            ->and(FixComplexity::Medium->effortMinutes())->toBe(60)
            ->and(FixComplexity::Complex->effortMinutes())->toBe(240)
            ->and(FixComplexity::Architectural->effortMinutes())->toBe(480);
    });

    it('has colors', function () {
        expect(FixComplexity::Quick->color())->toBe('green')
            ->and(FixComplexity::Architectural->color())->toBe('red');
    });

    it('has priority weights', function () {
        expect(FixComplexity::Quick->priorityWeight())->toBe(1)
            ->and(FixComplexity::Architectural->priorityWeight())->toBe(5);
    });

    describe('fromCriterion', function () {
        it('determines quick fix for alt text', function () {
            $complexity = FixComplexity::fromCriterion('1.1.1', 'missing-alt');
            expect($complexity)->toBe(FixComplexity::Quick);
        });

        it('determines easy fix for contrast', function () {
            $complexity = FixComplexity::fromCriterion('1.4.3');
            expect($complexity)->toBe(FixComplexity::Easy);
        });

        it('determines medium fix for keyboard', function () {
            $complexity = FixComplexity::fromCriterion('2.1.1');
            expect($complexity)->toBe(FixComplexity::Medium);
        });

        it('determines complex fix for captions', function () {
            $complexity = FixComplexity::fromCriterion('1.2.2');
            expect($complexity)->toBe(FixComplexity::Complex);
        });

        it('defaults based on principle', function () {
            // Perceivable (1.x) defaults to Easy
            $complexity = FixComplexity::fromCriterion('1.9.9');
            expect($complexity)->toBe(FixComplexity::Easy);

            // Operable (2.x) defaults to Medium
            $complexity = FixComplexity::fromCriterion('2.9.9');
            expect($complexity)->toBe(FixComplexity::Medium);
        });
    });
});

describe('RemediationService', function () {
    beforeEach(function () {
        $this->service = new RemediationService;
    });

    describe('fix templates', function () {
        it('has templates for common criteria', function () {
            $template = $this->service->getFixTemplate('1.1.1');

            expect($template)->not->toBeNull()
                ->and($template['title'])->toBe('Add Alternative Text')
                ->and($template['techniques'])->toContain('H37');
        });

        it('returns null for unknown criteria', function () {
            $template = $this->service->getFixTemplate('9.9.9');

            expect($template)->toBeNull();
        });

        it('can get all templates', function () {
            $templates = $this->service->getAllFixTemplates();

            expect($templates)->toBeArray()
                ->and($templates)->toHaveKey('1.1.1')
                ->and($templates)->toHaveKey('2.4.7');
        });
    });

    describe('generateFixSuggestion', function () {
        it('generates suggestion for audit check', function () {
            $check = AuditCheck::factory()->create([
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'status' => CheckStatus::Fail,
                'message' => 'Image missing alt text',
            ]);

            $suggestion = $this->service->generateFixSuggestion($check);

            expect($suggestion)
                ->toHaveKey('criterion_id')
                ->toHaveKey('complexity')
                ->toHaveKey('title')
                ->toHaveKey('description')
                ->toHaveKey('effort_minutes')
                ->and($suggestion['criterion_id'])->toBe('1.1.1')
                ->and($suggestion['title'])->toBe('Add Alternative Text');
        });

        it('includes code snippet from template', function () {
            $check = AuditCheck::factory()->create([
                'criterion_id' => '2.4.7',
                'criterion_name' => 'Focus Visible',
                'status' => CheckStatus::Fail,
            ]);

            $suggestion = $this->service->generateFixSuggestion($check);

            expect($suggestion['code_snippet'])->toContain(':focus');
        });

        it('calculates complexity and effort', function () {
            $check = AuditCheck::factory()->create([
                'criterion_id' => '1.4.3',
                'criterion_name' => 'Contrast (Minimum)',
                'status' => CheckStatus::Fail,
            ]);

            $suggestion = $this->service->generateFixSuggestion($check);

            expect($suggestion['complexity'])->toBe('easy')
                ->and($suggestion['effort_minutes'])->toBe(15);
        });
    });

    describe('generateFixRoadmap', function () {
        it('generates roadmap for audit', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            // Create various issues
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Critical,
            ]);
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.4.3',
                'criterion_name' => 'Contrast',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Serious,
            ]);
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '2.1.1',
                'criterion_name' => 'Keyboard',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Critical,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap)
                ->toHaveKey('total_issues')
                ->toHaveKey('total_effort_minutes')
                ->toHaveKey('by_complexity')
                ->toHaveKey('prioritized')
                ->toHaveKey('quick_wins')
                ->toHaveKey('high_impact')
                ->toHaveKey('phases')
                ->and($roadmap['total_issues'])->toBe(3);
        });

        it('groups issues by complexity', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.4.3', // Easy
                'criterion_name' => 'Contrast',
                'status' => CheckStatus::Fail,
            ]);
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '2.1.1', // Medium
                'criterion_name' => 'Keyboard',
                'status' => CheckStatus::Fail,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap['by_complexity'])->toHaveKey('easy')
                ->and($roadmap['by_complexity'])->toHaveKey('medium')
                ->and($roadmap['by_complexity']['easy'])->toHaveCount(1)
                ->and($roadmap['by_complexity']['medium'])->toHaveCount(1);
        });

        it('prioritizes by impact and complexity', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.4.3',
                'criterion_name' => 'Contrast',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Minor,
            ]);
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Critical,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            // Critical issue should be prioritized first
            expect($roadmap['prioritized'][0]['criterion_id'])->toBe('1.1.1');
        });

        it('identifies quick wins', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            // Quick fix + high impact = quick win
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '3.1.1', // Easy fix
                'criterion_name' => 'Language of Page',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Serious,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap['quick_wins'])->not->toBeEmpty();
        });

        it('identifies high impact fixes', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '2.1.1',
                'criterion_name' => 'Keyboard',
                'status' => CheckStatus::Fail,
                'impact' => ImpactLevel::Critical,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap['high_impact'])->not->toBeEmpty()
                ->and($roadmap['high_impact'][0]['criterion_id'])->toBe('2.1.1');
        });

        it('generates implementation phases', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            // Create issues of different complexities
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.4.3', // Easy
                'criterion_name' => 'Contrast',
                'status' => CheckStatus::Fail,
            ]);
            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '2.1.1', // Medium
                'criterion_name' => 'Keyboard',
                'status' => CheckStatus::Fail,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap['phases'])->not->toBeEmpty()
                ->and($roadmap['phases'][0]['name'])->toBe('Quick Wins');
        });

        it('calculates total effort', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            // 2 easy fixes (15 min each) = 30 minutes
            AuditCheck::factory()->count(2)->create([
                'accessibility_audit_id' => $audit->id,
                'criterion_id' => '1.4.3',
                'criterion_name' => 'Contrast',
                'status' => CheckStatus::Fail,
            ]);

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap['total_effort_minutes'])->toBe(30)
                ->and($roadmap['total_effort_hours'])->toBe(0.5);
        });
    });

    describe('batchGenerateSuggestions', function () {
        it('generates suggestions for multiple checks', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            $checks = AuditCheck::factory()->count(3)->create([
                'accessibility_audit_id' => $audit->id,
                'status' => CheckStatus::Fail,
            ]);

            $suggestions = $this->service->batchGenerateSuggestions($checks);

            expect($suggestions)->toHaveCount(3)
                ->and($suggestions[0])->toHaveKey('criterion_id')
                ->and($suggestions[0])->toHaveKey('complexity');
        });

        it('excludes AI suggestions when disabled', function () {
            $check = AuditCheck::factory()->create([
                'criterion_id' => '1.1.1',
                'criterion_name' => 'Non-text Content',
                'status' => CheckStatus::Fail,
                'element_html' => '<img src="test.jpg">',
            ]);

            $suggestions = $this->service->batchGenerateSuggestions(collect([$check]), false);

            expect($suggestions[0])->not->toHaveKey('ai_suggestion');
        });
    });

    describe('empty audit', function () {
        it('handles audit with no issues', function () {
            $audit = AccessibilityAudit::factory()->completed()->create();

            $roadmap = $this->service->generateFixRoadmap($audit);

            expect($roadmap['total_issues'])->toBe(0)
                ->and($roadmap['total_effort_minutes'])->toBe(0)
                ->and($roadmap['quick_wins'])->toBeEmpty()
                ->and($roadmap['high_impact'])->toBeEmpty();
        });
    });
});
