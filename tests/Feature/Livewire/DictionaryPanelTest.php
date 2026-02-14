<?php

use App\Enums\Permission;
use App\Enums\Role;
use App\Livewire\Projects\Components\DictionaryPanel;
use App\Models\DictionaryWord;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;

beforeEach(function () {
    // Create permissions
    foreach (Permission::values() as $permission) {
        SpatiePermission::findOrCreate($permission, 'web');
    }

    // Create Owner role with permissions
    $ownerRole = SpatieRole::findOrCreate(Role::Owner->value, 'web');
    $ownerRole->syncPermissions(Permission::projectPermissions());

    $this->organization = Organization::factory()->create([
        'subscription_tier' => 'pro',
        'credit_balance' => 100,
    ]);
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->user->assignRole(Role::Owner->value);

    $this->project = Project::factory()->create([
        'organization_id' => $this->organization->id,
        'name' => 'Test Project',
    ]);
});

it('renders dictionary panel component', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->assertStatus(200);
});

it('can open the panel', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('showPanel', true)
        ->assertSet('showPanel', true);
});

it('can close the panel', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('showPanel', true)
        ->call('closePanel')
        ->assertSet('showPanel', false);
});

it('can add a project-level word', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('newWord', 'brandname')
        ->set('wordScope', 'project')
        ->call('addWord');

    $this->assertDatabaseHas('dictionary_words', [
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'brandname',
        'added_by_user_id' => $this->user->id,
    ]);
});

it('can add an organization-level word', function () {
    $this->organization->update(['subscription_tier' => 'team']);

    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('newWord', 'companyterm')
        ->set('wordScope', 'organization')
        ->call('addWord');

    $this->assertDatabaseHas('dictionary_words', [
        'organization_id' => $this->organization->id,
        'project_id' => null,
        'word' => 'companyterm',
    ]);
});

it('normalizes words to lowercase', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('newWord', 'MyBrandName')
        ->set('wordScope', 'project')
        ->call('addWord');

    $this->assertDatabaseHas('dictionary_words', [
        'word' => 'mybrandname',
    ]);
});

it('prevents duplicate words at project level', function () {
    DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'existingword',
    ]);

    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('newWord', 'existingword')
        ->set('wordScope', 'project')
        ->call('addWord')
        ->assertHasErrors(['newWord']);
});

it('can delete a project word', function () {
    $word = DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'deleteMe',
    ]);

    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->call('deleteWord', $word->id);

    $this->assertDatabaseMissing('dictionary_words', [
        'id' => $word->id,
    ]);
});

it('can delete an organization word', function () {
    $word = DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => null,
        'word' => 'orgword',
    ]);

    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->call('deleteWord', $word->id);

    $this->assertDatabaseMissing('dictionary_words', [
        'id' => $word->id,
    ]);
});

it('can search words', function () {
    DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'findme',
    ]);
    DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'another',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('searchQuery', 'find');

    expect($component->instance()->projectWords)->toHaveCount(1);
    expect($component->instance()->projectWords->first()->word)->toBe('findme');
});

it('displays project words separately from organization words', function () {
    DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'projectword',
    ]);
    DictionaryWord::factory()->create([
        'organization_id' => $this->organization->id,
        'project_id' => null,
        'word' => 'orgword',
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project]);

    expect($component->instance()->projectWords)->toHaveCount(1);
    expect($component->instance()->organizationWords)->toHaveCount(1);
});

it('can add word from issue event', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->dispatch('add-word-from-issue', word: 'typoword');

    $this->assertDatabaseHas('dictionary_words', [
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
        'word' => 'typoword',
        'source' => 'issue',
    ]);
});

it('validates word is required', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('newWord', '')
        ->call('addWord')
        ->assertHasErrors(['newWord']);
});

it('validates word max length', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->set('newWord', str_repeat('a', 101))
        ->call('addWord')
        ->assertHasErrors(['newWord']);
});

it('shows word count and limit', function () {
    DictionaryWord::factory()->count(5)->create([
        'organization_id' => $this->organization->id,
        'project_id' => $this->project->id,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project]);

    $stats = $component->instance()->wordStats;
    expect($stats['current'])->toBe(5);
    expect($stats['limit'])->toBe(100); // Pro tier limit
});

it('handles open-dictionary-panel event', function () {
    Livewire::actingAs($this->user)
        ->test(DictionaryPanel::class, ['project' => $this->project])
        ->assertSet('showPanel', false)
        ->dispatch('open-dictionary-panel')
        ->assertSet('showPanel', true);
});
