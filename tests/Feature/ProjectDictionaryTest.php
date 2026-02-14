<?php

use App\Livewire\Projects\ProjectDictionary;
use App\Models\DictionaryWord;
use App\Models\IndustryDictionary;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'pro']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->project = Project::factory()->create(['organization_id' => $this->organization->id]);
    $this->actingAs($this->user);
});

it('renders for pro tier users', function () {
    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->assertStatus(200)
        ->assertSee('Project Dictionary');
});

it('redirects free tier users', function () {
    $this->organization->update(['subscription_tier' => 'free']);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->assertRedirect(route('projects.show', $this->project));
});

it('displays existing project words', function () {
    DictionaryWord::factory()->forProject($this->project)->create([
        'organization_id' => $this->organization->id,
        'word' => 'projectword',
    ]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->assertSee('projectword');
});

it('can add a word', function () {
    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->set('showAddModal', true)
        ->set('newWord', 'NewProjectWord')
        ->call('addWord')
        ->assertSet('showAddModal', false);

    expect(DictionaryWord::where('word', 'newprojectword')
        ->where('project_id', $this->project->id)
        ->exists())->toBeTrue();
});

it('validates word is required', function () {
    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->set('newWord', '')
        ->call('addWord')
        ->assertHasErrors(['newWord' => 'required']);
});

it('can delete a word', function () {
    $word = DictionaryWord::factory()->forProject($this->project)->create([
        'organization_id' => $this->organization->id,
        'word' => 'todelete',
    ]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->call('confirmDelete', $word->id)
        ->call('deleteWord');

    expect(DictionaryWord::find($word->id))->toBeNull();
});

it('can search words', function () {
    DictionaryWord::factory()->forProject($this->project)->create([
        'organization_id' => $this->organization->id,
        'word' => 'searchable',
    ]);
    DictionaryWord::factory()->forProject($this->project)->create([
        'organization_id' => $this->organization->id,
        'word' => 'findme',
    ]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->set('search', 'search')
        ->assertSee('searchable')
        ->assertDontSee('findme');
});

it('shows industry dictionaries section', function () {
    IndustryDictionary::factory()->create([
        'name' => 'Test Industry',
        'is_active' => true,
    ]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->assertSee('Industry Dictionaries')
        ->assertSee('Test Industry');
});

it('can toggle industry dictionary', function () {
    $dictionary = IndustryDictionary::factory()->create([
        'is_active' => true,
    ]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->call('toggleIndustryDictionary', $dictionary->id);

    expect($this->project->industryDictionaries()->where('industry_dictionary_id', $dictionary->id)->exists())->toBeTrue();
});

it('respects industry dictionary limit for pro tier', function () {
    $dict1 = IndustryDictionary::factory()->create(['is_active' => true]);
    $dict2 = IndustryDictionary::factory()->create(['is_active' => true]);

    $this->project->industryDictionaries()->attach($dict1->id);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->call('toggleIndustryDictionary', $dict2->id);

    // Pro tier limit is 1, so second should not be attached
    expect($this->project->industryDictionaries()->count())->toBe(1);
});

it('shows word count and limit', function () {
    DictionaryWord::factory()->forProject($this->project)->count(5)->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->assertSee('5')
        ->assertSee('100'); // Pro tier project limit
});

it('prevents access to other organization projects', function () {
    $otherOrg = Organization::factory()->create();
    $otherProject = Project::factory()->create(['organization_id' => $otherOrg->id]);

    Livewire::test(ProjectDictionary::class, ['project' => $otherProject])
        ->assertStatus(403);
});

it('shows breadcrumb navigation', function () {
    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->assertSee('Projects')
        ->assertSee($this->project->name)
        ->assertSee('Dictionary');
});

it('enterprise tier has unlimited industry dictionaries', function () {
    $this->organization->update(['subscription_tier' => 'enterprise']);

    $dict1 = IndustryDictionary::factory()->create(['is_active' => true]);
    $dict2 = IndustryDictionary::factory()->create(['is_active' => true]);
    $dict3 = IndustryDictionary::factory()->create(['is_active' => true]);

    $this->project->industryDictionaries()->attach([$dict1->id, $dict2->id]);

    Livewire::test(ProjectDictionary::class, ['project' => $this->project])
        ->call('toggleIndustryDictionary', $dict3->id);

    expect($this->project->industryDictionaries()->count())->toBe(3);
});
