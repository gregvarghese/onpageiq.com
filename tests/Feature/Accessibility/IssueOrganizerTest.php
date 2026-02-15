<?php

use App\Enums\AuditCategory;
use App\Enums\CheckStatus;
use App\Enums\ImpactLevel;
use App\Livewire\Accessibility\IssueOrganizer;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->audit = AccessibilityAudit::factory()->completed()->create();
});

describe('IssueOrganizer Component', function () {
    test('renders with no issues', function () {
        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->assertStatus(200)
            ->assertSee('Issue Organization')
            ->assertSee('No Issues Found');
    });

    test('renders with issues', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
            'criterion_name' => 'Non-text Content',
            'message' => 'Image missing alt text',
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->assertStatus(200)
            ->assertSee('1.1.1')
            ->assertSee('Non-text Content');
    });

    test('defaults to by_wcag view', function () {
        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->assertSet('activeView', 'by_wcag');
    });

    test('can switch to by_impact view', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'impact' => ImpactLevel::Critical,
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->call('setView', 'by_impact')
            ->assertSet('activeView', 'by_impact')
            ->assertSee('Critical');
    });

    test('can switch to by_category view', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'category' => AuditCategory::Vision,
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->call('setView', 'by_category')
            ->assertSet('activeView', 'by_category');
    });

    test('can switch to by_complexity view', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->call('setView', 'by_complexity')
            ->assertSet('activeView', 'by_complexity');
    });

    test('can switch to by_element view', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'element_selector' => 'img.hero-image',
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->call('setView', 'by_element')
            ->assertSet('activeView', 'by_element');
    });

    test('ignores invalid view', function () {
        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->call('setView', 'invalid_view')
            ->assertSet('activeView', 'by_wcag');
    });

    test('can toggle group expansion', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->assertSet('expandedGroup', '')
            ->call('toggleGroup', '1.1.1')
            ->assertSet('expandedGroup', '1.1.1')
            ->call('toggleGroup', '1.1.1')
            ->assertSet('expandedGroup', '');
    });

    test('clears expanded group when switching views', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
        ]);

        Livewire::test(IssueOrganizer::class, ['audit' => $this->audit])
            ->call('toggleGroup', '1.1.1')
            ->assertSet('expandedGroup', '1.1.1')
            ->call('setView', 'by_impact')
            ->assertSet('expandedGroup', '');
    });

    test('can search issues', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
            'message' => 'Image missing alt text',
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '2.4.1',
            'message' => 'Skip link not found',
        ]);

        $component = Livewire::test(IssueOrganizer::class, ['audit' => $this->audit]);

        // Initially shows all
        expect(count($component->get('activeIssues')))->toBe(2);

        // Filter by search
        $component->set('searchQuery', 'alt text');
        expect(count($component->get('activeIssues')))->toBe(1);
    });

    test('computes total issues', function () {
        AuditCheck::factory()->count(3)->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
        ]);

        AuditCheck::factory()->count(2)->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '2.4.1',
        ]);

        $component = Livewire::test(IssueOrganizer::class, ['audit' => $this->audit]);

        expect($component->get('totalIssues'))->toBe(5);
    });

    test('computes group count', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '2.4.1',
        ]);

        $component = Livewire::test(IssueOrganizer::class, ['audit' => $this->audit]);

        expect($component->get('groupCount'))->toBe(2);
    });

    test('only includes failed checks', function () {
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Fail,
            'criterion_id' => '1.1.1',
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'status' => CheckStatus::Pass,
            'criterion_id' => '2.4.1',
        ]);

        $component = Livewire::test(IssueOrganizer::class, ['audit' => $this->audit]);

        expect($component->get('totalIssues'))->toBe(1);
    });

    test('returns correct impact colors', function () {
        $component = new IssueOrganizer;

        expect($component->getImpactColor('critical'))->toBe('red');
        expect($component->getImpactColor('serious'))->toBe('orange');
        expect($component->getImpactColor('moderate'))->toBe('yellow');
        expect($component->getImpactColor('minor'))->toBe('blue');
        expect($component->getImpactColor('unknown'))->toBe('gray');
    });

    test('returns correct category colors', function () {
        $component = new IssueOrganizer;

        expect($component->getCategoryColor('vision'))->toBe('purple');
        expect($component->getCategoryColor('motor'))->toBe('blue');
        expect($component->getCategoryColor('cognitive'))->toBe('green');
        expect($component->getCategoryColor('unknown'))->toBe('gray');
    });

    test('returns correct complexity colors', function () {
        $component = new IssueOrganizer;

        expect($component->getComplexityColor('quick'))->toBe('green');
        expect($component->getComplexityColor('easy'))->toBe('blue');
        expect($component->getComplexityColor('medium'))->toBe('yellow');
        expect($component->getComplexityColor('complex'))->toBe('orange');
        expect($component->getComplexityColor('architectural'))->toBe('red');
    });

    test('provides view options', function () {
        $component = Livewire::test(IssueOrganizer::class, ['audit' => $this->audit]);
        $options = $component->get('viewOptions');

        expect($options)->toHaveKey('by_wcag');
        expect($options)->toHaveKey('by_impact');
        expect($options)->toHaveKey('by_category');
        expect($options)->toHaveKey('by_complexity');
        expect($options)->toHaveKey('by_element');
    });
});
