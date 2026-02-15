<?php

use App\Enums\AuditStatus;
use App\Enums\CheckStatus;
use App\Livewire\Accessibility\RegressionTrends;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create();
});

describe('RegressionTrends Component', function () {
    test('renders with no data', function () {
        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee('Regression', false)
            ->assertSee('Trends', false)
            ->assertSee('No Trend Data');
    });

    test('renders with audit data', function () {
        AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 85,
        ]);

        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee('Score History');
    });

    test('defaults to overview tab', function () {
        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->assertSet('activeTab', 'overview');
    });

    test('can switch to compare tab', function () {
        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('setTab', 'compare')
            ->assertSet('activeTab', 'compare')
            ->assertSee('Select Audits to Compare');
    });

    test('can switch to persistent tab', function () {
        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('setTab', 'persistent')
            ->assertSet('activeTab', 'persistent');
    });

    test('shows trend summary with multiple audits', function () {
        // Create two audits with different scores
        AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 70,
            'completed_at' => now()->subDays(2),
        ]);

        AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 85,
            'completed_at' => now()->subDay(),
        ]);

        $component = Livewire::test(RegressionTrends::class, ['project' => $this->project]);
        $summary = $component->get('summary');

        expect($summary['has_data'])->toBeTrue();
        expect($summary['score_change'])->toBe(15.0);
    });

    test('can select audit for comparison', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('selectAudit', $audit->id)
            ->assertSet('selectedAudit.id', $audit->id);
    });

    test('can select compare audit', function () {
        $audit1 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        $audit2 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('selectAudit', $audit1->id)
            ->call('selectCompareAudit', $audit2->id)
            ->assertSet('selectedAudit.id', $audit1->id)
            ->assertSet('compareAudit.id', $audit2->id);
    });

    test('can clear selection', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('selectAudit', $audit->id)
            ->assertSet('selectedAudit.id', $audit->id)
            ->call('clearSelection')
            ->assertSet('selectedAudit', null)
            ->assertSet('compareAudit', null);
    });

    test('computes comparison when both audits selected', function () {
        $audit1 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 70,
        ]);

        $audit2 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 85,
        ]);

        // Add checks
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $audit1->id,
            'status' => CheckStatus::Fail,
            'fingerprint' => 'fp-1',
        ]);

        AuditCheck::factory()->create([
            'accessibility_audit_id' => $audit2->id,
            'status' => CheckStatus::Fail,
            'fingerprint' => 'fp-2',
        ]);

        $component = Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('selectAudit', $audit2->id)
            ->call('selectCompareAudit', $audit1->id);

        $comparison = $component->get('comparison');

        expect($comparison)->not->toBeNull();
        expect($comparison['score_change'])->toBe(15.0);
    });

    test('gets available audits', function () {
        AccessibilityAudit::factory()->count(3)->completed()->create([
            'project_id' => $this->project->id,
        ]);

        // Pending audit should not be included
        AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
            'status' => AuditStatus::Pending,
        ]);

        $component = Livewire::test(RegressionTrends::class, ['project' => $this->project]);

        expect($component->get('availableAudits')->count())->toBe(3);
    });

    test('computes resolution rate', function () {
        $audit1 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'completed_at' => now()->subDays(2),
        ]);

        $audit2 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'completed_at' => now(),
        ]);

        // Add a fixed issue (was in audit1, not in audit2)
        AuditCheck::factory()->create([
            'accessibility_audit_id' => $audit1->id,
            'status' => CheckStatus::Fail,
            'fingerprint' => 'fp-fixed',
        ]);

        $component = Livewire::test(RegressionTrends::class, ['project' => $this->project]);
        $rate = $component->get('resolutionRate');

        expect($rate['has_data'])->toBeTrue();
    });

    test('computes persistent issues', function () {
        // Create 4 audits with same recurring issue
        $fingerprint = 'fp-persistent-'.uniqid();

        for ($i = 0; $i < 4; $i++) {
            $audit = AccessibilityAudit::factory()->completed()->create([
                'project_id' => $this->project->id,
                'completed_at' => now()->subDays($i),
            ]);

            AuditCheck::factory()->create([
                'accessibility_audit_id' => $audit->id,
                'status' => CheckStatus::Fail,
                'fingerprint' => $fingerprint,
                'criterion_id' => '1.1.1',
                'message' => 'Persistent issue',
            ]);
        }

        $component = Livewire::test(RegressionTrends::class, ['project' => $this->project]);
        $persistent = $component->get('persistentIssues');

        expect($persistent->count())->toBeGreaterThanOrEqual(1);
        expect($persistent->first()['occurrences'])->toBeGreaterThanOrEqual(3);
    });

    test('returns correct score trend class', function () {
        $component = new RegressionTrends;
        $component->project = $this->project;

        // We need to mock the summary - test the method directly
        expect($component->getScoreTrendClass())->toContain('text-');
    });

    test('returns correct issue trend class', function () {
        $component = new RegressionTrends;
        $component->project = $this->project;

        expect($component->getIssueTrendClass())->toContain('text-');
    });

    test('returns correct trend icon', function () {
        $component = new RegressionTrends;
        $component->project = $this->project;

        expect($component->getTrendIcon('improving'))->toBe('arrow-trending-up');
        expect($component->getTrendIcon('declining'))->toBe('arrow-trending-down');
        expect($component->getTrendIcon('stable'))->toBe('minus');
    });

    test('shows no persistent issues message when empty', function () {
        Livewire::test(RegressionTrends::class, ['project' => $this->project])
            ->call('setTab', 'persistent')
            ->assertSee('No Persistent Issues');
    });
});
