<?php

use App\Enums\Role;
use App\Livewire\Settings\SettingsIndex;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Role::organizationRoles() as $roleName) {
        SpatieRole::findOrCreate($roleName);
    }

    $this->organization = Organization::factory()->create([
        'name' => 'Test Organization',
        'subscription_tier' => 'pro',
        'credit_balance' => 500,
    ]);
    $this->owner = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->owner->assignRole(Role::Owner->value);
});

it('renders settings page for authenticated user', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->assertStatus(200);
});

it('displays organization name', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->assertSet('organizationName', 'Test Organization');
});

it('can update organization settings', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->set('organizationName', 'Updated Organization')
        ->set('timezone', 'America/New_York')
        ->set('defaultLanguage', 'es')
        ->call('updateOrganization');

    $this->organization->refresh();
    expect($this->organization->name)->toBe('Updated Organization');
    expect($this->organization->settings['timezone'])->toBe('America/New_York');
    expect($this->organization->settings['default_language'])->toBe('es');
});

it('validates organization name is required', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->set('organizationName', '')
        ->call('updateOrganization')
        ->assertHasErrors(['organizationName' => 'required']);
});

it('shows delete organization confirmation modal', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->call('confirmDeleteOrganization')
        ->assertSet('showDeleteOrgModal', true);
});

it('requires organization name to delete', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->call('confirmDeleteOrganization')
        ->set('deleteConfirmation', 'Wrong Name')
        ->call('deleteOrganization')
        ->assertHasErrors(['deleteConfirmation']);

    $this->assertDatabaseHas('organizations', ['id' => $this->organization->id]);
});

it('loads timezone options', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->assertSet('timezones', function ($timezones) {
            return array_key_exists('UTC', $timezones)
                && array_key_exists('America/New_York', $timezones);
        });
});

it('loads language options', function () {
    Livewire::actingAs($this->owner)
        ->test(SettingsIndex::class)
        ->assertSet('languages', function ($languages) {
            return array_key_exists('en', $languages)
                && array_key_exists('es', $languages);
        });
});

it('requires authentication', function () {
    $this->get(route('settings.index'))
        ->assertRedirect(route('login'));
});
