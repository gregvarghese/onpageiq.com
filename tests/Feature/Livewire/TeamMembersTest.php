<?php

use App\Enums\Role;
use App\Livewire\Team\TeamMembers;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    // Ensure roles exist
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

it('renders team members page for authenticated user', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->assertStatus(200);
});

it('displays organization members', function () {
    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    $member->assignRole(Role::Member->value);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->assertSee('John Doe')
        ->assertSee('john@example.com');
});

it('shows upgrade message for non-team tiers', function () {
    $organization = Organization::factory()->create([
        'subscription_tier' => 'free',
    ]);
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    Livewire::actingAs($user)
        ->test(TeamMembers::class)
        ->assertSee('Upgrade to the Team or Enterprise plan');
});

it('can filter members by search', function () {
    $member1 = User::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Alice Smith',
    ]);
    $member2 = User::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Bob Johnson',
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->set('search', 'Alice')
        ->assertSee('Alice Smith')
        ->assertDontSee('Bob Johnson');
});

it('can filter members by role', function () {
    $admin = User::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Admin User',
    ]);
    $admin->assignRole(Role::Admin->value);

    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Regular Member',
    ]);
    $member->assignRole(Role::Member->value);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->set('roleFilter', Role::Admin->value)
        ->assertSee('Admin User')
        ->assertDontSee('Regular Member');
});

it('can open invite modal', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('openInviteModal')
        ->assertSet('showInviteModal', true);
});

it('can invite a new member', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('openInviteModal')
        ->set('inviteEmail', 'newmember@example.com')
        ->set('inviteRole', Role::Member->value)
        ->call('inviteMember')
        ->assertSet('showInviteModal', false);

    $this->assertDatabaseHas('users', [
        'email' => 'newmember@example.com',
        'organization_id' => $this->organization->id,
    ]);
});

it('validates invite email is required', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('openInviteModal')
        ->set('inviteEmail', '')
        ->call('inviteMember')
        ->assertHasErrors(['inviteEmail' => 'required']);
});

it('validates invite email is unique in organization', function () {
    User::factory()->create([
        'organization_id' => $this->organization->id,
        'email' => 'existing@example.com',
    ]);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('openInviteModal')
        ->set('inviteEmail', 'existing@example.com')
        ->call('inviteMember')
        ->assertHasErrors(['inviteEmail']);
});

it('can change member role', function () {
    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $member->assignRole(Role::Member->value);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('openRoleModal', $member->id)
        ->set('newRole', Role::Manager->value)
        ->call('updateRole');

    expect($member->fresh()->hasRole(Role::Manager->value))->toBeTrue();
});

it('cannot change own role', function () {
    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('openRoleModal', $this->owner->id)
        ->set('newRole', Role::Member->value)
        ->call('updateRole')
        ->assertSet('showRoleModal', false);

    // Owner should still have Owner role
    expect($this->owner->fresh()->hasRole(Role::Owner->value))->toBeTrue();
});

it('can remove a member', function () {
    $member = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $member->assignRole(Role::Member->value);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('confirmRemove', $member->id)
        ->call('removeMember');

    $this->assertDatabaseMissing('users', ['id' => $member->id]);
});

it('cannot remove the owner', function () {
    $anotherOwner = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $anotherOwner->assignRole(Role::Owner->value);

    Livewire::actingAs($this->owner)
        ->test(TeamMembers::class)
        ->call('confirmRemove', $anotherOwner->id)
        ->call('removeMember')
        ->assertSet('showRemoveModal', false);

    // Owner should still exist
    $this->assertDatabaseHas('users', ['id' => $anotherOwner->id]);
});

it('requires authentication', function () {
    $this->get(route('team.members'))
        ->assertRedirect(route('login'));
});
