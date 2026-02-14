<?php

use App\Enums\Role;
use App\Livewire\Team\TeamDepartments;
use App\Models\Department;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Role::organizationRoles() as $roleName) {
        SpatieRole::findOrCreate($roleName);
    }

    $this->organization = Organization::factory()->create([
        'subscription_tier' => 'team',
    ]);
    $this->owner = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->owner->assignRole(Role::Owner->value);
});

it('renders departments page for authenticated user', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->assertStatus(200);
});

it('displays existing departments', function () {
    Department::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Marketing',
        'credit_budget' => 1000,
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->assertSee('Marketing')
        ->assertSee('1,000');
});

it('shows upgrade message for non-team tiers', function () {
    $organization = Organization::factory()->create([
        'subscription_tier' => 'free',
    ]);
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($user)
        ->test(TeamDepartments::class)
        ->assertSee('Upgrade to the Team or Enterprise plan');
});

it('can create a new department', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->call('openCreateModal')
        ->set('name', 'Engineering')
        ->set('creditBudget', 500)
        ->call('createDepartment')
        ->assertSet('showCreateModal', false);

    $this->assertDatabaseHas('departments', [
        'organization_id' => $this->organization->id,
        'name' => 'Engineering',
        'credit_budget' => 500,
    ]);
});

it('validates department name is required', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->call('openCreateModal')
        ->set('name', '')
        ->call('createDepartment')
        ->assertHasErrors(['name' => 'required']);
});

it('can update a department', function () {
    $department = Department::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Old Name',
        'credit_budget' => 100,
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->call('openEditModal', $department->id)
        ->set('name', 'New Name')
        ->set('creditBudget', 200)
        ->call('updateDepartment');

    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'name' => 'New Name',
        'credit_budget' => 200,
    ]);
});

it('can delete a department', function () {
    $department = Department::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'To Delete',
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->call('confirmDelete', $department->id)
        ->call('deleteDepartment');

    $this->assertDatabaseMissing('departments', ['id' => $department->id]);
});

it('unassigns users when department is deleted', function () {
    $department = Department::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'department_id' => $department->id,
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->call('confirmDelete', $department->id)
        ->call('deleteDepartment');

    expect($member->fresh()->department_id)->toBeNull();
});

it('can assign members to department', function () {
    $department = Department::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'department_id' => null,
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->call('openMembersModal', $department->id)
        ->set('selectedMembers', [$member->id])
        ->call('updateMembers');

    expect($member->fresh()->department_id)->toBe($department->id);
});

it('can search departments', function () {
    Department::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Marketing',
    ]);
    Department::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Engineering',
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->set('search', 'Mark')
        ->assertSee('Marketing')
        ->assertDontSee('Engineering');
});

it('displays member count', function () {
    $department = Department::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Sales',
    ]);

    User::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
        'department_id' => $department->id,
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamDepartments::class)
        ->assertSee('3 members');
});

it('requires authentication', function () {
    $this->get(route('team.departments'))
        ->assertRedirect(route('login'));
});
