<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Projects\Components\ScheduleModal;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ScanSchedule;
use App\Models\UrlGroup;
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

    $this->organization = Organization::factory()->create([
        'subscription_tier' => 'pro',
        'credit_balance' => 100,
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->user->assignRole(Role::Owner->value);

    $this->project = Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Test Project',
    ]);
});

it('renders schedule modal component', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->assertStatus(200);
});

it('can open create modal', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->assertSet('showModal', true)
        ->assertSet('editingSchedule', null);
});

it('can create a daily schedule', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->set('frequency', 'daily')
        ->set('scanType', 'quick')
        ->set('preferredTime', '09:00')
        ->set('isActive', true)
        ->call('save');

    $this->assertDatabaseHas('scan_schedules', [
        'project_id' => $this->project->id,
        'frequency' => 'daily',
        'scan_type' => 'quick',
        'is_active' => true,
    ]);
});

it('can create a weekly schedule', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->set('frequency', 'weekly')
        ->set('scanType', 'deep')
        ->set('preferredTime', '14:00')
        ->set('dayOfWeek', 1) // Monday
        ->set('isActive', true)
        ->call('save');

    $this->assertDatabaseHas('scan_schedules', [
        'project_id' => $this->project->id,
        'frequency' => 'weekly',
        'scan_type' => 'deep',
        'day_of_week' => 1,
        'is_active' => true,
    ]);
});

it('can create a monthly schedule', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->set('frequency', 'monthly')
        ->set('scanType', 'quick')
        ->set('preferredTime', '10:00')
        ->set('dayOfMonth', 15)
        ->set('isActive', true)
        ->call('save');

    $this->assertDatabaseHas('scan_schedules', [
        'project_id' => $this->project->id,
        'frequency' => 'monthly',
        'day_of_month' => 15,
        'is_active' => true,
    ]);
});

it('can edit an existing schedule', function () {
    $schedule = ScanSchedule::factory()->create([
        'project_id' => $this->project->id,
        'frequency' => 'daily',
        'scan_type' => 'quick',
    ]);

    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openEditModal', $schedule->id)
        ->assertSet('showModal', true)
        ->assertSet('frequency', 'daily')
        ->set('frequency', 'weekly')
        ->set('dayOfWeek', 3)
        ->call('save');

    $schedule->refresh();
    expect($schedule->frequency)->toBe('weekly');
    expect($schedule->day_of_week)->toBe(3);
});

it('can delete a schedule', function () {
    $schedule = ScanSchedule::factory()->create([
        'project_id' => $this->project->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('deleteSchedule', $schedule->id);

    $this->assertDatabaseMissing('scan_schedules', [
        'id' => $schedule->id,
    ]);
});

it('can toggle schedule active state', function () {
    $schedule = ScanSchedule::factory()->create([
        'project_id' => $this->project->id,
        'is_active' => true,
    ]);

    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('toggleActive', $schedule->id);

    $schedule->refresh();
    expect($schedule->is_active)->toBeFalse();
});

it('can assign a url group to schedule', function () {
    $urlGroup = UrlGroup::factory()->create([
        'project_id' => $this->project->id,
        'name' => 'Blog Pages',
    ]);

    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->set('frequency', 'daily')
        ->set('scanType', 'quick')
        ->set('urlGroupId', $urlGroup->id)
        ->call('save');

    $this->assertDatabaseHas('scan_schedules', [
        'project_id' => $this->project->id,
        'url_group_id' => $urlGroup->id,
    ]);
});

it('validates frequency is required', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->set('frequency', '')
        ->call('save')
        ->assertHasErrors(['frequency']);
});

it('validates scan type is required', function () {
    Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project])
        ->call('openCreateModal')
        ->set('scanType', '')
        ->call('save')
        ->assertHasErrors(['scanType']);
});

it('enforces schedule limit for organization tier', function () {
    // Pro tier has limit of 1 scheduled scan
    ScanSchedule::factory()->create([
        'project_id' => $this->project->id,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project]);

    expect($component->instance()->canCreateSchedule())->toBeFalse();
});

it('allows unlimited schedules for enterprise tier', function () {
    $this->organization->update(['subscription_tier' => 'enterprise']);

    // Create multiple schedules
    ScanSchedule::factory()->count(10)->create([
        'project_id' => $this->project->id,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(ScheduleModal::class, ['project' => $this->project]);

    expect($component->instance()->canCreateSchedule())->toBeTrue();
});
