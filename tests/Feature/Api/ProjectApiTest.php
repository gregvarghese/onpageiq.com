<?php

use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
});

it('requires authentication for api access', function () {
    $this->getJson('/api/v1/projects')
        ->assertStatus(401);
});

it('lists projects for authenticated user', function () {
    Sanctum::actingAs($this->user, ['*']);

    Project::factory()->count(3)->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->getJson('/api/v1/projects')
        ->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('creates a new project', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->postJson('/api/v1/projects', [
        'name' => 'New API Project',
        'description' => 'Created via API',
        'language' => 'en',
    ])
        ->assertStatus(201)
        ->assertJsonPath('data.name', 'New API Project');

    $this->assertDatabaseHas('projects', [
        'name' => 'New API Project',
        'organization_id' => $this->organization->id,
    ]);
});

it('shows a single project', function () {
    Sanctum::actingAs($this->user, ['*']);

    $project = Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'My Test Project',
    ]);

    $this->getJson("/api/v1/projects/{$project->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'My Test Project');
});

it('updates an existing project', function () {
    Sanctum::actingAs($this->user, ['*']);

    $project = Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Original Name',
    ]);

    $this->putJson("/api/v1/projects/{$project->id}", [
        'name' => 'Updated Name',
    ])
        ->assertStatus(200)
        ->assertJsonPath('data.name', 'Updated Name');
});

it('deletes a project', function () {
    Sanctum::actingAs($this->user, ['*']);

    $project = Project::factory()->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->deleteJson("/api/v1/projects/{$project->id}")
        ->assertStatus(200)
        ->assertJsonPath('message', 'Project deleted successfully.');

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

it('cannot access projects from another organization', function () {
    Sanctum::actingAs($this->user, ['*']);

    $otherOrg = Organization::factory()->create();
    $otherProject = Project::factory()->create([
        'organization_id' => $otherOrg->id,
    ]);

    $this->getJson("/api/v1/projects/{$otherProject->id}")
        ->assertStatus(403);
});

it('validates required fields when creating project', function () {
    Sanctum::actingAs($this->user, ['*']);

    $this->postJson('/api/v1/projects', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('paginates project listing', function () {
    Sanctum::actingAs($this->user, ['*']);

    Project::factory()->count(25)->create([
        'organization_id' => $this->organization->id,
    ]);

    $this->getJson('/api/v1/projects')
        ->assertStatus(200)
        ->assertJsonPath('total', 25);
});

it('only shows projects from user organization', function () {
    Sanctum::actingAs($this->user, ['*']);

    // Create projects for user's organization
    Project::factory()->count(2)->create([
        'organization_id' => $this->organization->id,
    ]);

    // Create projects for another organization
    $otherOrg = Organization::factory()->create();
    Project::factory()->count(3)->create([
        'organization_id' => $otherOrg->id,
    ]);

    $this->getJson('/api/v1/projects')
        ->assertStatus(200)
        ->assertJsonCount(2, 'data');
});
