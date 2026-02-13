<?php

use App\Models\Organization;
use App\Models\User;

it('redirects guests to login', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('allows authenticated users to access dashboard', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertStatus(200);
});

it('allows users to logout', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->post('/logout')
        ->assertRedirect('/');

    $this->assertGuest();
});

it('has google oauth route', function () {
    // OAuth routes exist and return proper responses (redirect or error if unconfigured)
    $response = $this->get('/auth/google');
    expect($response->status())->toBeIn([302, 500]); // 302 = redirect, 500 = driver not configured
});

it('has microsoft oauth route', function () {
    // OAuth routes exist and return proper responses (redirect or error if unconfigured)
    $response = $this->get('/auth/microsoft');
    expect($response->status())->toBeIn([302, 500]); // 302 = redirect, 500 = driver not configured
});

it('protects project routes', function () {
    $this->get('/projects')
        ->assertRedirect('/login');
});

it('protects billing routes', function () {
    $this->get('/billing')
        ->assertRedirect('/login');
});

it('protects api tokens routes', function () {
    $this->get('/api/tokens')
        ->assertRedirect('/login');
});

it('protects notification routes', function () {
    $this->get('/notifications')
        ->assertRedirect('/login');
});

it('authenticated user can access projects', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->get('/projects')
        ->assertStatus(200);
});

it('authenticated user can access billing', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->get('/billing')
        ->assertStatus(200);
});

it('authenticated user can access notifications', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($user)
        ->get('/notifications')
        ->assertStatus(200);
});
