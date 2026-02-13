<?php

use App\Livewire\Dashboard\Dashboard;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Url;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'credit_balance' => 500,
        'subscription_tier' => 'pro',
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('renders dashboard for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertStatus(200)
        ->assertSee('Dashboard')
        ->assertSee('Credit Balance');
});

it('displays organization credit balance', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('500');
});

it('displays subscription tier', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('Pro');
});

it('displays recent projects', function () {
    $project = Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Test Project Alpha',
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('Test Project Alpha');
});

it('displays recent scans', function () {
    $project = Project::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $url = Url::factory()->create([
        'project_id' => $project->id,
        'url' => 'https://example.com/test-page',
    ]);
    $scan = Scan::factory()->create([
        'url_id' => $url->id,
        'status' => 'completed',
    ]);

    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('example.com');
});

it('shows empty state when no projects exist', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('No projects yet');
});

it('shows empty state when no scans exist', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('No scans yet');
});

it('displays quick action links', function () {
    Livewire::actingAs($this->user)
        ->test(Dashboard::class)
        ->assertSee('New Project')
        ->assertSee('Buy Credits')
        ->assertSee('API Access');
});

it('requires authentication', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
