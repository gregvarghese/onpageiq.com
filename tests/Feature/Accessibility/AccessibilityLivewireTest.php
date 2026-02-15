<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Accessibility\AccessibilityAuditDashboard;
use App\Livewire\Accessibility\AuditCheckDetail;
use App\Livewire\Accessibility\AuditResultsList;
use App\Livewire\Accessibility\RadarChart;
use App\Models\AccessibilityAudit;
use App\Models\AuditCheck;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    // Create permissions
    foreach (Permission::values() as $permission) {
        SpatiePermission::findOrCreate($permission, 'web');
    }

    // Create Owner role with permissions
    $ownerRole = SpatieRole::findOrCreate(Role::Owner->value, 'web');
    $ownerRole->syncPermissions(Permission::projectPermissions());

    $this->organization = Organization::factory()->create(['subscription_tier' => 'enterprise']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->user->assignRole(Role::Owner->value);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->actingAs($this->user);
});

describe('AccessibilityAuditDashboard', function () {
    it('renders successfully', function () {
        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->assertStatus(200)
            ->assertSee('Accessibility Audit');
    });

    it('shows empty state when no audits exist', function () {
        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->assertSee('No audits yet')
            ->assertSee('Run Accessibility Audit');
    });

    it('displays latest audit when audits exist', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 85.5,
        ]);

        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->assertSee('85.5%')
            ->assertSee('Overall Score');
    });

    it('can open run audit modal', function () {
        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->set('showRunModal', true)
            ->assertSee('Run Accessibility Audit')
            ->assertSee('WCAG Level');
    });

    it('can start a new audit', function () {
        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->set('wcagLevelTarget', 'AA')
            ->call('runAudit')
            ->assertSet('showRunModal', false);

        expect(AccessibilityAudit::where('project_id', $this->project->id)->count())->toBe(1);
    });

    it('can select a different audit', function () {
        $audit1 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 75,
        ]);

        $audit2 = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'overall_score' => 90,
        ]);

        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->call('selectAudit', $audit1->id)
            ->assertSet('selectedAudit.id', $audit1->id);
    });

    it('shows running status for in-progress audits', function () {
        $audit = AccessibilityAudit::factory()->running()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->assertSee('In Progress');
    });

    it('shows error message for failed audits', function () {
        $audit = AccessibilityAudit::factory()->failed()->create([
            'project_id' => $this->project->id,
            'error_message' => 'Connection timeout',
        ]);

        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->assertSee('Audit Failed')
            ->assertSee('Connection timeout');
    });

    it('computes category scores correctly', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
            'scores_by_category' => [
                'vision' => 85.0,
                'motor' => 90.0,
            ],
        ]);

        $component = Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project]);

        $categoryScores = $component->get('categoryScores');
        expect($categoryScores)->toBeArray();
    });

    it('can filter checks by category', function () {
        $audit = AccessibilityAudit::factory()->completed()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(AccessibilityAuditDashboard::class, ['project' => $this->project])
            ->call('setCategory', 'vision')
            ->assertSet('categoryFilter', 'vision')
            ->call('setCategory', 'vision')
            ->assertSet('categoryFilter', '');
    });
});

describe('AuditResultsList', function () {
    it('renders successfully', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(AuditResultsList::class, ['audit' => $audit])
            ->assertStatus(200);
    });

    it('displays checks with pagination', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(25)->forAudit($audit)->create();

        Livewire::test(AuditResultsList::class, ['audit' => $audit])
            ->assertSee('total checks');
    });

    it('can filter by status', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->count(3)->forAudit($audit)->passed()->create();
        AuditCheck::factory()->count(2)->forAudit($audit)->failed()->create();

        Livewire::test(AuditResultsList::class, ['audit' => $audit])
            ->set('statusFilter', 'fail')
            ->assertSet('statusFilter', 'fail');
    });

    it('can search checks', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        AuditCheck::factory()->forAudit($audit)->create([
            'criterion_id' => '1.4.3',
            'message' => 'Contrast ratio is insufficient',
        ]);

        Livewire::test(AuditResultsList::class, ['audit' => $audit])
            ->set('search', '1.4.3')
            ->assertSet('search', '1.4.3');
    });

    it('can clear filters', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(AuditResultsList::class, ['audit' => $audit])
            ->set('search', 'test')
            ->set('statusFilter', 'fail')
            ->set('wcagLevelFilter', 'AA')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('statusFilter', '')
            ->assertSet('wcagLevelFilter', '');
    });

    it('can sort by different columns', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        Livewire::test(AuditResultsList::class, ['audit' => $audit])
            ->call('sort', 'impact')
            ->assertSet('sortBy', 'impact')
            ->assertSet('sortDirection', 'asc')
            ->call('sort', 'impact')
            ->assertSet('sortDirection', 'desc');
    });
});

describe('AuditCheckDetail', function () {
    it('renders successfully', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $check = AuditCheck::factory()->forAudit($audit)->create();

        Livewire::test(AuditCheckDetail::class, ['check' => $check])
            ->assertStatus(200)
            ->assertSee('WCAG');
    });

    it('displays check details', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $check = AuditCheck::factory()->forAudit($audit)->create([
            'criterion_id' => '1.4.3',
            'message' => 'Contrast ratio is too low',
            'suggestion' => 'Increase the contrast ratio to at least 4.5:1',
        ]);

        Livewire::test(AuditCheckDetail::class, ['check' => $check])
            ->assertSee('1.4.3')
            ->assertSee('Contrast ratio is too low')
            ->assertSee('Increase the contrast ratio');
    });

    it('can add a note', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $check = AuditCheck::factory()->forAudit($audit)->create();

        Livewire::test(AuditCheckDetail::class, ['check' => $check])
            ->set('noteContent', 'This is a test note')
            ->call('addNote')
            ->assertSet('noteContent', '')
            ->assertSet('showAddNoteModal', false);

        expect($check->evidence()->count())->toBe(1);
    });

    it('validates note content', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $check = AuditCheck::factory()->forAudit($audit)->create();

        Livewire::test(AuditCheckDetail::class, ['check' => $check])
            ->set('noteContent', '')
            ->call('addNote')
            ->assertHasErrors(['noteContent' => 'required']);
    });

    it('shows related checks', function () {
        $audit = AccessibilityAudit::factory()->create([
            'project_id' => $this->project->id,
        ]);

        $check1 = AuditCheck::factory()->forAudit($audit)->create(['criterion_id' => '1.4.3']);
        $check2 = AuditCheck::factory()->forAudit($audit)->create(['criterion_id' => '1.4.3']);

        $component = Livewire::test(AuditCheckDetail::class, ['check' => $check1]);
        $relatedChecks = $component->get('relatedChecks');

        expect($relatedChecks)->toHaveCount(1);
    });
});

describe('RadarChart', function () {
    it('renders successfully', function () {
        Livewire::test(RadarChart::class, [
            'scores' => [
                'vision' => 85.0,
                'motor' => 90.0,
                'cognitive' => 75.0,
            ],
        ])
            ->assertStatus(200);
    });

    it('generates correct polygon points', function () {
        $component = new RadarChart;
        $component->mount([
            'vision' => 100,
            'motor' => 100,
            'cognitive' => 100,
            'general' => 100,
        ], 300);

        $points = $component->getPolygonPoints();
        expect($points)->not->toBeEmpty();
    });

    it('generates axis lines', function () {
        $component = new RadarChart;
        $component->mount([
            'vision' => 50,
            'motor' => 50,
        ], 300);

        $lines = $component->getAxisLines();
        expect($lines)->toBeArray();
    });

    it('generates label positions', function () {
        $component = new RadarChart;
        $component->mount([
            'vision' => 80,
            'motor' => 90,
        ], 300);

        $positions = $component->getLabelPositions();
        expect($positions)->toBeArray();
    });

    it('handles empty scores gracefully', function () {
        $component = new RadarChart;
        $component->mount([], 300);

        // Even with empty scores, the component iterates over AuditCategory::cases()
        // and generates default zero-value points at the center
        $points = $component->getPolygonPoints();
        $lines = $component->getAxisLines();

        expect($points)->toBeString();
        expect($lines)->toBeArray();
    });
});
