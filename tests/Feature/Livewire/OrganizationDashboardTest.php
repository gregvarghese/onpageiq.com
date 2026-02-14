<?php

use App\Enums\Role;
use App\Livewire\Dashboard\OrganizationDashboard;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ScanSchedule;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    // Create Owner role
    SpatieRole::findOrCreate(Role::Owner->value, 'web');

    $this->organization = Organization::factory()->create([
        'name' => 'Test Organization',
        'credit_balance' => 500,
        'subscription_tier' => 'team',
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->user->assignRole(Role::Owner->value);
});

test('it renders organization dashboard for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(OrganizationDashboard::class)
        ->assertOk();
});

test('it loads organization data', function () {
    $component = Livewire::actingAs($this->user)
        ->test(OrganizationDashboard::class);

    expect($component->get('organization.name'))->toBe('Test Organization');
});

test('it displays credit balance', function () {
    Livewire::actingAs($this->user)
        ->test(OrganizationDashboard::class)
        ->assertSee('500');
});

test('it displays projects from organization', function () {
    Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Alpha Project',
    ]);

    Livewire::actingAs($this->user)
        ->test(OrganizationDashboard::class)
        ->assertSee('Alpha Project');
});

test('it displays scheduled scans when they exist', function () {
    $project = Project::factory()->create(['organization_id' => $this->organization->id]);
    ScanSchedule::factory()->create([
        'project_id' => $project->id,
        'is_active' => true,
        'frequency' => 'daily',
    ]);

    Livewire::actingAs($this->user)
        ->test(OrganizationDashboard::class)
        ->assertSee('Daily');
});

test('it shows alert when credits are zero', function () {
    $organization = Organization::factory()->create([
        'credit_balance' => 0,
        'subscription_tier' => 'pro',
    ]);
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);
    $user->assignRole('Owner');

    Livewire::actingAs($user)
        ->test(OrganizationDashboard::class)
        ->assertSee('no credits remaining');
});

test('it requires authentication', function () {
    $this->get(route('organization.dashboard'))
        ->assertRedirect(route('login'));
});

test('it prevents access to other organizations', function () {
    $otherOrg = Organization::factory()->create();
    $otherUser = User::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    // User should only see their own organization
    Livewire::actingAs($this->user)
        ->test(OrganizationDashboard::class)
        ->assertDontSee($otherOrg->name);
});
