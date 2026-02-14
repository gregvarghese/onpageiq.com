<?php

use App\Enums\Role;
use App\Livewire\Scans\ScanCreate;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Role::organizationRoles() as $roleName) {
        SpatieRole::findOrCreate($roleName);
    }

    $this->organization = Organization::factory()->create([
        'subscription_tier' => 'pro',
        'credit_balance' => 100,
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->user->assignRole(Role::Member->value);

    $this->project = Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Test Project',
    ]);
});

it('renders scan create page for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->assertStatus(200);
});

it('can select a project', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->set('selectedProjectId', $this->project->id)
        ->assertSet('selectedProjectId', $this->project->id);
});

it('calculates estimated credits for quick scan', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->set('urls', "https://example.com\nhttps://example.com/about")
        ->set('scanType', 'quick')
        ->assertSet('estimatedCredits', 2);
});

it('calculates estimated credits for deep scan', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->set('urls', "https://example.com\nhttps://example.com/about")
        ->set('scanType', 'deep')
        ->assertSet('estimatedCredits', 6);
});

it('can create a new project inline', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->call('openNewProjectModal')
        ->set('newProjectName', 'New Inline Project')
        ->set('newProjectLanguage', 'en')
        ->call('createProject');

    $this->assertDatabaseHas('projects', [
        'organization_id' => $this->organization->id,
        'name' => 'New Inline Project',
    ]);
});

it('validates project is required to start scan', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->set('urls', 'https://example.com')
        ->call('startScan')
        ->assertHasErrors(['selectedProjectId']);
});

it('validates urls are required to start scan', function () {
    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->set('selectedProjectId', $this->project->id)
        ->set('urls', '')
        ->call('startScan')
        ->assertHasErrors(['urls']);
});

it('shows insufficient credits error', function () {
    $this->organization->update(['credit_balance' => 0]);

    Livewire::actingAs($this->user)
        ->test(ScanCreate::class)
        ->set('selectedProjectId', $this->project->id)
        ->set('urls', 'https://example.com')
        ->call('startScan')
        ->assertHasErrors(['urls']);
});

it('requires authentication', function () {
    $this->get(route('scans.create'))
        ->assertRedirect(route('login'));
});
