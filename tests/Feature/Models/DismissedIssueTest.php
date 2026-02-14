<?php

use App\Models\DismissedIssue;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Url;
use App\Models\User;

test('dismissed issue belongs to organization', function () {
    $organization = Organization::factory()->create();
    $dismissed = DismissedIssue::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($dismissed->organization)->toBeInstanceOf(Organization::class);
    expect($dismissed->organization->id)->toBe($organization->id);
});

test('dismissed issue belongs to project when project-scoped', function () {
    $project = Project::factory()->create();
    $dismissed = DismissedIssue::factory()->forProject($project)->create();

    expect($dismissed->project)->toBeInstanceOf(Project::class);
    expect($dismissed->project->id)->toBe($project->id);
});

test('dismissed issue belongs to url when url-scoped', function () {
    $url = Url::factory()->create();
    $dismissed = DismissedIssue::factory()->forUrl($url)->create();

    expect($dismissed->url)->toBeInstanceOf(Url::class);
    expect($dismissed->url->id)->toBe($url->id);
});

test('dismissed issue belongs to dismissing user', function () {
    $user = User::factory()->create();
    $dismissed = DismissedIssue::factory()->create([
        'dismissed_by_user_id' => $user->id,
    ]);

    expect($dismissed->dismissedBy)->toBeInstanceOf(User::class);
    expect($dismissed->dismissedBy->id)->toBe($user->id);
});

test('dismissed issue can identify url scope', function () {
    $dismissed = DismissedIssue::factory()->create([
        'scope' => 'url',
    ]);

    expect($dismissed->isUrlScoped())->toBeTrue();
    expect($dismissed->isProjectScoped())->toBeFalse();
    expect($dismissed->isPatternBased())->toBeFalse();
});

test('dismissed issue can identify project scope', function () {
    $dismissed = DismissedIssue::factory()->create([
        'scope' => 'project',
    ]);

    expect($dismissed->isProjectScoped())->toBeTrue();
    expect($dismissed->isUrlScoped())->toBeFalse();
    expect($dismissed->isPatternBased())->toBeFalse();
});

test('dismissed issue can identify pattern scope', function () {
    $dismissed = DismissedIssue::factory()->pattern()->create();

    expect($dismissed->isPatternBased())->toBeTrue();
    expect($dismissed->isUrlScoped())->toBeFalse();
    expect($dismissed->isProjectScoped())->toBeFalse();
});

test('dismissed issue matches exact text case-insensitively', function () {
    $dismissed = DismissedIssue::factory()->create([
        'scope' => 'url',
        'text_pattern' => 'lorem',
    ]);

    expect($dismissed->matchesText('lorem'))->toBeTrue();
    expect($dismissed->matchesText('Lorem'))->toBeTrue();
    expect($dismissed->matchesText('LOREM'))->toBeTrue();
    expect($dismissed->matchesText('ipsum'))->toBeFalse();
});

test('pattern-based dismissal matches partial text', function () {
    $dismissed = DismissedIssue::factory()->pattern()->create([
        'text_pattern' => 'test',
    ]);

    expect($dismissed->matchesText('testing'))->toBeTrue();
    expect($dismissed->matchesText('a test case'))->toBeTrue();
    expect($dismissed->matchesText('TEST'))->toBeTrue();
    expect($dismissed->matchesText('unrelated'))->toBeFalse();
});

test('forUrl scope finds url-specific dismissals', function () {
    $url = Url::factory()->create();
    $dismissed = DismissedIssue::factory()->forUrl($url)->create();

    // Create unrelated dismissals
    DismissedIssue::factory()->count(3)->create();

    $found = DismissedIssue::forUrl($url)->get();

    expect($found)->toHaveCount(1);
    expect($found->first()->id)->toBe($dismissed->id);
});

test('forUrl scope finds project-level dismissals', function () {
    $project = Project::factory()->create();
    $url = Url::factory()->create(['project_id' => $project->id]);
    $dismissed = DismissedIssue::factory()->forProject($project)->create();

    $found = DismissedIssue::forUrl($url)->get();

    expect($found)->toHaveCount(1);
    expect($found->first()->id)->toBe($dismissed->id);
});

test('forUrl scope finds organization-level dismissals', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $url = Url::factory()->create(['project_id' => $project->id]);

    $dismissed = DismissedIssue::factory()->create([
        'organization_id' => $organization->id,
        'project_id' => null,
        'url_id' => null,
        'scope' => 'url',
    ]);

    $found = DismissedIssue::forUrl($url)->get();

    expect($found)->toHaveCount(1);
    expect($found->first()->id)->toBe($dismissed->id);
});

test('forCategory scope filters by category', function () {
    DismissedIssue::factory()->create(['category' => 'spelling']);
    DismissedIssue::factory()->create(['category' => 'grammar']);
    DismissedIssue::factory()->create(['category' => 'spelling']);

    $spellingDismissals = DismissedIssue::forCategory('spelling')->get();
    $grammarDismissals = DismissedIssue::forCategory('grammar')->get();

    expect($spellingDismissals)->toHaveCount(2);
    expect($grammarDismissals)->toHaveCount(1);
});

test('dismissed issue stores optional reason', function () {
    $dismissed = DismissedIssue::factory()->create([
        'reason' => 'This is a valid brand name',
    ]);

    expect($dismissed->reason)->toBe('This is a valid brand name');
});

test('dismissed issue can have null reason', function () {
    $dismissed = DismissedIssue::factory()->create([
        'reason' => null,
    ]);

    expect($dismissed->reason)->toBeNull();
});
