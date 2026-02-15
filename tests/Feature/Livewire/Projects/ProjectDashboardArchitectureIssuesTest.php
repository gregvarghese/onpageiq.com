<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Projects\ProjectDashboard;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
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

    $this->user = User::factory()->create();
    $this->user->assignRole(Role::Owner->value);
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'organization_id' => $this->user->organization_id,
    ]);

    $this->architecture = SiteArchitecture::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'ready',
    ]);

    $this->node = ArchitectureNode::factory()->create([
        'site_architecture_id' => $this->architecture->id,
        'url' => 'https://example.com/page',
    ]);

    $this->architectureIssue = ArchitectureIssue::factory()->create([
        'site_architecture_id' => $this->architecture->id,
        'node_id' => $this->node->id,
        'is_resolved' => false,
    ]);

    // Refresh the project to ensure relationships are loaded
    $this->project->refresh();
});

describe('Architecture Issues in Findings', function () {
    it('shows architecture issues in findings table', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->assertSee($this->architectureIssue->message);
    });

    it('filters findings by architecture category', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('setFindingsFilter', 'architecture')
            ->assertSet('findingsFilter', 'architecture');
    });

    it('counts architecture issues in findingsCounts computed property', function () {
        $component = Livewire::test(ProjectDashboard::class, ['project' => $this->project]);

        // Access computed property via instance
        $instance = $component->instance();
        $counts = $instance->findingsCounts();
        expect($counts['architecture'])->toBe(1);
    });
});

describe('Resolve Architecture Issue', function () {
    it('resolves an architecture issue', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('resolveArchitectureIssue', $this->architectureIssue->id)
            ->assertDispatched('issues-updated');

        expect($this->architectureIssue->fresh()->is_resolved)->toBeTrue();
    });

    it('shows success message after resolving', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('resolveArchitectureIssue', $this->architectureIssue->id)
            ->assertHasNoErrors();

        // Verify the issue was actually resolved
        expect($this->architectureIssue->fresh()->is_resolved)->toBeTrue();
    });

    it('rejects resolving issue from different project', function () {
        $otherProject = Project::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);
        $otherArchitecture = SiteArchitecture::factory()->create([
            'project_id' => $otherProject->id,
        ]);
        $otherIssue = ArchitectureIssue::factory()->create([
            'site_architecture_id' => $otherArchitecture->id,
            'is_resolved' => false,
        ]);

        // Should not dispatch issues-updated event when issue is from different project
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('resolveArchitectureIssue', $otherIssue->id)
            ->assertNotDispatched('issues-updated');

        expect($otherIssue->fresh()->is_resolved)->toBeFalse();
    });
});

describe('Ignore Architecture Issue', function () {
    it('ignores an architecture issue', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('ignoreArchitectureIssue', $this->architectureIssue->id)
            ->assertDispatched('issues-updated');

        expect($this->architectureIssue->fresh()->is_resolved)->toBeTrue();
    });

    it('shows success message after ignoring', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('ignoreArchitectureIssue', $this->architectureIssue->id)
            ->assertHasNoErrors();

        // Verify the issue was actually dismissed
        expect($this->architectureIssue->fresh()->is_resolved)->toBeTrue();
    });

    it('rejects ignoring issue from different project', function () {
        $otherProject = Project::factory()->create([
            'organization_id' => $this->user->organization_id,
        ]);
        $otherArchitecture = SiteArchitecture::factory()->create([
            'project_id' => $otherProject->id,
        ]);
        $otherIssue = ArchitectureIssue::factory()->create([
            'site_architecture_id' => $otherArchitecture->id,
            'is_resolved' => false,
        ]);

        // Should not dispatch issues-updated event when issue is from different project
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->call('ignoreArchitectureIssue', $otherIssue->id)
            ->assertNotDispatched('issues-updated');

        expect($otherIssue->fresh()->is_resolved)->toBeFalse();
    });
});

describe('Architecture Issues Display', function () {
    it('does not include resolved architecture issues in findings', function () {
        $this->architectureIssue->update(['is_resolved' => true]);
        $this->project->refresh();

        $component = Livewire::test(ProjectDashboard::class, ['project' => $this->project]);

        $instance = $component->instance();
        $counts = $instance->findingsCounts();
        expect($counts['architecture'])->toBe(0);
    });

    it('shows architecture issue with node link', function () {
        Livewire::test(ProjectDashboard::class, ['project' => $this->project])
            ->assertSee($this->node->url);
    });
});
