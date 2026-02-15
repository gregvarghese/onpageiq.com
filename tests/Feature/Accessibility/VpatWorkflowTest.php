<?php

use App\Enums\VpatConformanceLevel;
use App\Enums\VpatStatus;
use App\Livewire\Accessibility\VpatWorkflow;
use App\Models\AccessibilityAudit;
use App\Models\User;
use App\Models\VpatEvaluation;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->audit = AccessibilityAudit::factory()->completed()->create();
});

describe('VpatWorkflow Component', function () {
    test('renders without vpat', function () {
        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->assertStatus(200)
            ->assertSee('No VPAT Evaluation')
            ->assertSee('Create VPAT Evaluation');
    });

    test('renders with existing vpat', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'product_name' => 'Test Product',
            'vendor_name' => 'Test Vendor',
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->assertStatus(200)
            ->assertSee('Test Product')
            ->assertSee('Test Vendor')
            ->assertSee('Evaluation Progress');
    });

    test('can open create modal', function () {
        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->set('showCreateModal', true)
            ->assertSee('Create VPAT 2.4 Evaluation');
    });

    test('can create vpat evaluation', function () {
        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->set('productName', 'My Product')
            ->set('productVersion', '1.0.0')
            ->set('vendorName', 'My Company')
            ->set('reportTypes', ['wcag21', 'section508'])
            ->call('createVpat')
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('vpat_evaluations', [
            'accessibility_audit_id' => $this->audit->id,
            'product_name' => 'My Product',
            'product_version' => '1.0.0',
            'vendor_name' => 'My Company',
        ]);
    });

    test('validates required fields when creating vpat', function () {
        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->set('productName', '')
            ->set('vendorName', '')
            ->call('createVpat')
            ->assertHasErrors(['productName', 'vendorName']);
    });

    test('can switch between principles', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->assertSet('activePrinciple', 'Perceivable')
            ->set('activePrinciple', 'Operable')
            ->assertSet('activePrinciple', 'Operable');
    });

    test('can edit criterion evaluation', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Draft,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('editCriterion', '1.1.1')
            ->assertSet('currentCriterion', '1.1.1');
    });

    test('can save criterion evaluation', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Draft,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('editCriterion', '1.1.1')
            ->set('conformanceLevel', VpatConformanceLevel::Supports->value)
            ->set('remarks', 'All images have alt text')
            ->call('saveEvaluation')
            ->assertSet('currentCriterion', '');

        $vpat->refresh();
        $evaluation = $vpat->getWcagEvaluation('1.1.1');

        expect($evaluation['level'])->toBe(VpatConformanceLevel::Supports->value);
        expect($evaluation['remarks'])->toBe('All images have alt text');
    });

    test('can cancel editing', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Draft,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('editCriterion', '1.1.1')
            ->assertSet('currentCriterion', '1.1.1')
            ->call('cancelEdit')
            ->assertSet('currentCriterion', '');
    });

    test('cannot edit non-editable vpat', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Published,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('editCriterion', '1.1.1')
            ->assertSet('currentCriterion', '');
    });

    test('can submit vpat for review', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Draft,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('submitForReview')
            ->assertDispatched('vpat-submitted');

        expect($vpat->fresh()->status)->toBe(VpatStatus::InReview);
    });

    test('can approve vpat', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::InReview,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('approve')
            ->assertDispatched('vpat-approved');

        $vpat->refresh();
        expect($vpat->status)->toBe(VpatStatus::Approved);
        expect($vpat->approved_by_user_id)->toBe($this->user->id);
    });

    test('can publish vpat', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Approved,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('publish')
            ->assertDispatched('vpat-published');

        expect($vpat->fresh()->status)->toBe(VpatStatus::Published);
    });

    test('can populate from audit', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'status' => VpatStatus::Draft,
        ]);

        // Create some audit checks
        \App\Models\AuditCheck::factory()->count(3)->create([
            'accessibility_audit_id' => $this->audit->id,
            'criterion_id' => '1.1.1',
            'status' => \App\Enums\CheckStatus::Pass,
        ]);

        Livewire::test(VpatWorkflow::class, ['audit' => $this->audit])
            ->call('populateFromAudit')
            ->assertDispatched('vpat-populated');

        $vpat->refresh();
        $evaluation = $vpat->getWcagEvaluation('1.1.1');

        expect($evaluation)->not->toBeNull();
        expect($evaluation['level'])->toBe(VpatConformanceLevel::Supports->value);
    });

    test('computes completion percentage', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'wcag_evaluations' => [
                '1.1.1' => ['level' => VpatConformanceLevel::Supports->value, 'remarks' => ''],
                '1.2.1' => ['level' => VpatConformanceLevel::NotApplicable->value, 'remarks' => ''],
            ],
        ]);

        $component = Livewire::test(VpatWorkflow::class, ['audit' => $this->audit]);

        expect($component->get('completionPercentage'))->toBeGreaterThan(0);
    });

    test('computes conformance summary', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
            'wcag_evaluations' => [
                '1.1.1' => ['level' => VpatConformanceLevel::Supports->value, 'remarks' => ''],
                '1.2.1' => ['level' => VpatConformanceLevel::DoesNotSupport->value, 'remarks' => ''],
                '1.3.1' => ['level' => VpatConformanceLevel::PartiallySupports->value, 'remarks' => ''],
            ],
        ]);

        $component = Livewire::test(VpatWorkflow::class, ['audit' => $this->audit]);
        $summary = $component->get('conformanceSummary');

        expect($summary[VpatConformanceLevel::Supports->value])->toBe(1);
        expect($summary[VpatConformanceLevel::DoesNotSupport->value])->toBe(1);
        expect($summary[VpatConformanceLevel::PartiallySupports->value])->toBe(1);
    });

    test('gets active criteria for principle', function () {
        $vpat = VpatEvaluation::factory()->create([
            'accessibility_audit_id' => $this->audit->id,
            'created_by_user_id' => $this->user->id,
        ]);

        $component = Livewire::test(VpatWorkflow::class, ['audit' => $this->audit]);
        $criteria = $component->get('activeCriteria');

        expect($criteria)->toHaveKey('1.1.1');
        expect($criteria['1.1.1']['name'])->toBe('Non-text Content');
    });

    test('pre-fills product name from project', function () {
        $this->audit->project->update(['name' => 'My Project Name']);

        $component = Livewire::test(VpatWorkflow::class, ['audit' => $this->audit]);

        expect($component->get('productName'))->toBe('My Project Name');
    });
});
