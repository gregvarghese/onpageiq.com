<?php

use App\Enums\Role;
use App\Livewire\Profile\ProfileEdit;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Role::organizationRoles() as $roleName) {
        SpatieRole::findOrCreate($roleName);
    }

    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
    ]);
    $this->user->assignRole(Role::Member->value);
});

it('renders profile edit page for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->assertStatus(200);
});

it('displays current user information', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->assertSet('name', 'Test User')
        ->assertSet('email', 'test@example.com');
});

it('can update profile information', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('name', 'Updated Name')
        ->set('email', 'updated@example.com')
        ->call('updateProfile');

    $this->user->refresh();
    expect($this->user->name)->toBe('Updated Name');
    expect($this->user->email)->toBe('updated@example.com');
});

it('validates name is required', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('name', '')
        ->call('updateProfile')
        ->assertHasErrors(['name' => 'required']);
});

it('validates email is required', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('email', '')
        ->call('updateProfile')
        ->assertHasErrors(['email' => 'required']);
});

it('validates email is unique', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('email', 'taken@example.com')
        ->call('updateProfile')
        ->assertHasErrors(['email']);
});

it('can update password', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('currentPassword', 'password123')
        ->set('newPassword', 'newpassword456')
        ->set('newPasswordConfirmation', 'newpassword456')
        ->call('updatePassword');

    $this->user->refresh();
    expect(Hash::check('newpassword456', $this->user->password))->toBeTrue();
});

it('validates current password is correct', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('currentPassword', 'wrongpassword')
        ->set('newPassword', 'newpassword456')
        ->set('newPasswordConfirmation', 'newpassword456')
        ->call('updatePassword')
        ->assertHasErrors(['currentPassword']);
});

it('validates password confirmation matches', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('currentPassword', 'password123')
        ->set('newPassword', 'newpassword456')
        ->set('newPasswordConfirmation', 'differentpassword')
        ->call('updatePassword')
        ->assertHasErrors(['newPasswordConfirmation']);
});

it('can update notification preferences', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->set('notifyOnScanComplete', true)
        ->set('notifyOnIssuesFound', false)
        ->set('notifyOnWeeklyDigest', true)
        ->set('notifyOnBillingAlerts', true)
        ->call('updateNotifications');

    $this->user->refresh();
    expect($this->user->notification_preferences['scan_complete'])->toBeTrue();
    expect($this->user->notification_preferences['issues_found'])->toBeFalse();
    expect($this->user->notification_preferences['weekly_digest'])->toBeTrue();
});

it('shows delete account confirmation modal', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->call('confirmDelete')
        ->assertSet('showDeleteModal', true);
});

it('requires email confirmation to delete account', function () {
    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->call('confirmDelete')
        ->set('deleteConfirmation', 'wrong@email.com')
        ->call('deleteAccount')
        ->assertHasErrors(['deleteConfirmation']);
});

it('prevents owner from deleting account', function () {
    $this->user->syncRoles([Role::Owner->value]);

    Livewire::actingAs($this->user)
        ->test(ProfileEdit::class)
        ->call('confirmDelete')
        ->set('deleteConfirmation', $this->user->email)
        ->call('deleteAccount')
        ->assertHasErrors(['deleteConfirmation']);

    $this->assertDatabaseHas('users', ['id' => $this->user->id]);
});

it('requires authentication', function () {
    $this->get(route('profile.edit'))
        ->assertRedirect(route('login'));
});
