<?php

use App\Enums\Role;
use App\Livewire\Reports\ReportIndex;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Scan;
use App\Models\ScanResult;
use App\Models\Url;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    foreach (Role::organizationRoles() as $roleName) {
        SpatieRole::findOrCreate($roleName);
    }

    $this->organization = Organization::factory()->create([
        'subscription_tier' => 'pro',
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

it('renders reports page for authenticated user', function () {
    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->assertStatus(200);
});

it('displays scan statistics correctly', function () {
    $url = Url::factory()->create(['project_id' => $this->project->id]);

    Scan::factory()->count(3)->create([
        'url_id' => $url->id,
        'status' => 'completed',
    ]);

    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->assertSet('statistics.totalScans', 3)
        ->assertSet('statistics.completedScans', 3);
});

it('filters scans by date range', function () {
    $url = Url::factory()->create(['project_id' => $this->project->id]);

    Scan::factory()->create([
        'url_id' => $url->id,
        'status' => 'completed',
        'created_at' => now()->subDays(5),
    ]);

    Scan::factory()->create([
        'url_id' => $url->id,
        'status' => 'completed',
        'created_at' => now()->subDays(40),
    ]);

    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->set('dateRange', '30')
        ->assertSet('statistics.totalScans', 1);
});

it('displays issues by category breakdown', function () {
    $url = Url::factory()->create(['project_id' => $this->project->id]);
    $scan = Scan::factory()->create([
        'url_id' => $url->id,
        'status' => 'completed',
    ]);
    $result = ScanResult::factory()->create(['scan_id' => $scan->id]);

    Issue::factory()->count(5)->create([
        'scan_result_id' => $result->id,
        'category' => 'spelling',
    ]);
    Issue::factory()->count(3)->create([
        'scan_result_id' => $result->id,
        'category' => 'grammar',
    ]);

    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->assertSet('issuesByCategory.spelling', 5)
        ->assertSet('issuesByCategory.grammar', 3);
});

it('displays issues by severity breakdown', function () {
    $url = Url::factory()->create(['project_id' => $this->project->id]);
    $scan = Scan::factory()->create([
        'url_id' => $url->id,
        'status' => 'completed',
    ]);
    $result = ScanResult::factory()->create(['scan_id' => $scan->id]);

    Issue::factory()->count(4)->create([
        'scan_result_id' => $result->id,
        'severity' => 'error',
    ]);
    Issue::factory()->count(2)->create([
        'scan_result_id' => $result->id,
        'severity' => 'warning',
    ]);

    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->assertSet('issuesBySeverity.error', 4)
        ->assertSet('issuesBySeverity.warning', 2);
});

it('can filter by severity', function () {
    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->set('severityFilter', 'error')
        ->assertStatus(200);
});

it('can filter by category', function () {
    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->set('categoryFilter', 'spelling')
        ->assertStatus(200);
});

it('can filter by project', function () {
    Livewire::actingAs($this->user)
        ->test(ReportIndex::class)
        ->set('projectFilter', $this->project->id)
        ->assertStatus(200);
});

it('requires authentication', function () {
    $this->get(route('reports.index'))
        ->assertRedirect(route('login'));
});
