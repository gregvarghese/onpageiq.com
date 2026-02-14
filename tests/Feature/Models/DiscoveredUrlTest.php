<?php

use App\Models\DiscoveredUrl;
use App\Models\Project;
use App\Models\User;

test('discovered url belongs to project', function () {
    $discovered = DiscoveredUrl::factory()->create();

    expect($discovered->project)->toBeInstanceOf(Project::class);
});

test('discovered url has pending status by default', function () {
    $discovered = DiscoveredUrl::factory()->create();

    expect($discovered->status)->toBe('pending');
    expect($discovered->isPending())->toBeTrue();
});

test('discovered url can be approved', function () {
    $user = User::factory()->create();
    $discovered = DiscoveredUrl::factory()->create();

    $discovered->approve($user);

    expect($discovered->fresh()->status)->toBe('approved');
    expect($discovered->fresh()->isApproved())->toBeTrue();
    expect($discovered->fresh()->approved_by_user_id)->toBe($user->id);
    expect($discovered->fresh()->approved_at)->not->toBeNull();
});

test('discovered url can be rejected', function () {
    $user = User::factory()->create();
    $discovered = DiscoveredUrl::factory()->create();

    $discovered->reject($user, 'Not relevant');

    expect($discovered->fresh()->status)->toBe('rejected');
    expect($discovered->fresh()->isRejected())->toBeTrue();
    expect($discovered->fresh()->rejection_reason)->toBe('Not relevant');
});

test('approving url adds it to project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();
    $discovered = DiscoveredUrl::factory()->create([
        'project_id' => $project->id,
        'url' => 'https://example.com/new-page',
    ]);

    $discovered->approve($user);

    expect($project->urls()->where('url', 'https://example.com/new-page')->exists())->toBeTrue();
});

test('discovered url tracks source information', function () {
    $discovered = DiscoveredUrl::factory()->create([
        'source_url' => 'https://example.com/page',
        'link_text' => 'Click here',
    ]);

    expect($discovered->source_url)->toBe('https://example.com/page');
    expect($discovered->link_text)->toBe('Click here');
});
