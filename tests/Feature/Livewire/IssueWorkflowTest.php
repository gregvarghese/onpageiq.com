<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Projects\Components\IssueWorkflow;
use App\Models\DismissedIssue;
use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanResult;
use App\Models\Url;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Permission::values() as $permission) {
        SpatiePermission::findOrCreate($permission, 'web');
    }

    // Create Owner role with permissions
    $ownerRole = SpatieRole::findOrCreate(Role::Owner->value, 'web');
    $ownerRole->syncPermissions(Permission::projectPermissions());

    $this->organization = Organization::factory()->create([
        'subscription_tier' => 'team',
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->user->assignRole(Role::Owner->value);

    $this->project = Project::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->url = Url::factory()->create(['project_id' => $this->project->id]);
    $this->scan = Scan::factory()->completed()->create(['url_id' => $this->url->id]);
    $this->result = ScanResult::factory()->create(['scan_id' => $this->scan->id]);
});

test('it renders issue workflow component', function () {
    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->assertOk();
});

test('it displays issues for the project', function () {
    Issue::factory()->create([
        'scan_result_id' => $this->result->id,
        'category' => 'spelling',
        'text_excerpt' => 'misspeled word',
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->assertSee('misspeled word');
});

test('it can filter by category', function () {
    Issue::factory()->create([
        'scan_result_id' => $this->result->id,
        'category' => 'spelling',
        'text_excerpt' => 'spelling issue',
    ]);
    Issue::factory()->create([
        'scan_result_id' => $this->result->id,
        'category' => 'grammar',
        'text_excerpt' => 'grammar issue',
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->set('categoryFilter', 'spelling')
        ->assertSee('spelling issue')
        ->assertDontSee('grammar issue');
});

test('it can filter by severity', function () {
    Issue::factory()->create([
        'scan_result_id' => $this->result->id,
        'severity' => 'error',
        'text_excerpt' => 'error issue',
    ]);
    Issue::factory()->create([
        'scan_result_id' => $this->result->id,
        'severity' => 'warning',
        'text_excerpt' => 'warning issue',
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->set('severityFilter', 'error')
        ->assertSee('error issue')
        ->assertDontSee('warning issue');
});

test('it can open assign modal', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('openAssignModal', $issue->id)
        ->assertSet('showAssignModal', true)
        ->assertSet('selectedIssue.id', $issue->id);
});

test('it can close assign modal', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('openAssignModal', $issue->id)
        ->assertSet('showAssignModal', true)
        ->call('closeAssignModal')
        ->assertSet('showAssignModal', false);
});

test('it can assign issue to team member', function () {
    $assignee = User::factory()->create(['organization_id' => $this->organization->id]);
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('openAssignModal', $issue->id)
        ->set('assignToUserId', $assignee->id)
        ->call('saveAssignment')
        ->assertSet('showAssignModal', false);

    expect(IssueAssignment::where('issue_id', $issue->id)->exists())->toBeTrue();
});

test('it can open dismiss modal', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('openDismissModal', $issue->id)
        ->assertSet('showDismissModal', true)
        ->assertSet('selectedIssue.id', $issue->id);
});

test('it can close dismiss modal', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('openDismissModal', $issue->id)
        ->assertSet('showDismissModal', true)
        ->call('closeDismissModal')
        ->assertSet('showDismissModal', false);
});

test('it can dismiss issue', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
        'category' => 'spelling',
        'text_excerpt' => 'customword',
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('openDismissModal', $issue->id)
        ->set('dismissScope', 'url')
        ->set('dismissReason', 'Brand name')
        ->call('dismissIssue')
        ->assertSet('showDismissModal', false);

    expect(DismissedIssue::count())->toBe(1);
});

test('it can update assignment status', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);
    $assignment = IssueAssignment::factory()->create([
        'issue_id' => $issue->id,
        'status' => 'open',
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('updateStatus', $issue->id, 'in_progress');

    expect($assignment->fresh()->status)->toBe('in_progress');
});

test('it sets resolved_at when marking as resolved', function () {
    $issue = Issue::factory()->create([
        'scan_result_id' => $this->result->id,
    ]);
    $assignment = IssueAssignment::factory()->create([
        'issue_id' => $issue->id,
        'status' => 'in_progress',
        'resolved_at' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(IssueWorkflow::class, ['project' => $this->project])
        ->call('updateStatus', $issue->id, 'resolved');

    $assignment->refresh();
    expect($assignment->status)->toBe('resolved');
    expect($assignment->resolved_at)->not->toBeNull();
});
