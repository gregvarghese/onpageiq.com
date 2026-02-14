<?php

use App\Models\DictionaryWord;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;

it('normalizes words to lowercase', function () {
    expect(DictionaryWord::normalizeWord('WarrCloud'))->toBe('warrcloud');
    expect(DictionaryWord::normalizeWord('  UPPERCASE  '))->toBe('uppercase');
    expect(DictionaryWord::normalizeWord('MixedCase'))->toBe('mixedcase');
});

it('auto-normalizes word on creation', function () {
    $organization = Organization::factory()->create();
    $word = DictionaryWord::factory()->create([
        'organization_id' => $organization->id,
        'word' => 'TestWord',
    ]);

    expect($word->word)->toBe('testword');
});

it('belongs to an organization', function () {
    $organization = Organization::factory()->create();
    $word = DictionaryWord::factory()->create([
        'organization_id' => $organization->id,
    ]);

    expect($word->organization)->toBeInstanceOf(Organization::class);
    expect($word->organization->id)->toBe($organization->id);
});

it('can belong to a project', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);
    $word = DictionaryWord::factory()->forProject($project)->create([
        'organization_id' => $organization->id,
    ]);

    expect($word->project)->toBeInstanceOf(Project::class);
    expect($word->project->id)->toBe($project->id);
});

it('can be organization level (no project)', function () {
    $word = DictionaryWord::factory()->organizationLevel()->create();

    expect($word->project)->toBeNull();
    expect($word->project_id)->toBeNull();
});

it('tracks who added the word', function () {
    $user = User::factory()->create();
    $word = DictionaryWord::factory()->create([
        'added_by_user_id' => $user->id,
    ]);

    expect($word->addedBy)->toBeInstanceOf(User::class);
    expect($word->addedBy->id)->toBe($user->id);
});

it('scopes to organization level words', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    $orgWord = DictionaryWord::factory()->organizationLevel()->create([
        'organization_id' => $organization->id,
    ]);
    $projectWord = DictionaryWord::factory()->forProject($project)->create([
        'organization_id' => $organization->id,
    ]);

    $orgLevelWords = DictionaryWord::organizationLevel()->get();

    expect($orgLevelWords)->toHaveCount(1);
    expect($orgLevelWords->first()->id)->toBe($orgWord->id);
});

it('scopes to words for a specific project', function () {
    $organization = Organization::factory()->create();
    $project1 = Project::factory()->create(['organization_id' => $organization->id]);
    $project2 = Project::factory()->create(['organization_id' => $organization->id]);

    $word1 = DictionaryWord::factory()->forProject($project1)->create([
        'organization_id' => $organization->id,
    ]);
    $word2 = DictionaryWord::factory()->forProject($project2)->create([
        'organization_id' => $organization->id,
    ]);

    $project1Words = DictionaryWord::forProject($project1->id)->get();

    expect($project1Words)->toHaveCount(1);
    expect($project1Words->first()->id)->toBe($word1->id);
});

it('scopes to words applicable to a project', function () {
    $organization = Organization::factory()->create();
    $project = Project::factory()->create(['organization_id' => $organization->id]);

    // Organization-level word
    $orgWord = DictionaryWord::factory()->organizationLevel()->create([
        'organization_id' => $organization->id,
        'word' => 'orgword',
    ]);

    // Project-specific word
    $projectWord = DictionaryWord::factory()->forProject($project)->create([
        'organization_id' => $organization->id,
        'word' => 'projectword',
    ]);

    // Different organization word (should not be included)
    $otherOrgWord = DictionaryWord::factory()->organizationLevel()->create([
        'word' => 'otherword',
    ]);

    $applicableWords = DictionaryWord::applicableToProject($project)->get();

    expect($applicableWords)->toHaveCount(2);
    expect($applicableWords->pluck('word')->toArray())->toContain('orgword', 'projectword');
    expect($applicableWords->pluck('word')->toArray())->not->toContain('otherword');
});
