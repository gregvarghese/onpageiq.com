<?php

use App\Livewire\Settings\OrganizationDictionary;
use App\Models\DictionaryWord;
use App\Models\Organization;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->organization = Organization::factory()->create(['subscription_tier' => 'team']);
    $this->user = User::factory()->create(['organization_id' => $this->organization->id]);
    $this->actingAs($this->user);
});

it('renders for team tier users', function () {
    Livewire::test(OrganizationDictionary::class)
        ->assertStatus(200)
        ->assertSee('Organization Dictionary');
});

it('redirects free tier users', function () {
    $this->organization->update(['subscription_tier' => 'free']);

    Livewire::test(OrganizationDictionary::class)
        ->assertRedirect(route('dashboard'));
});

it('redirects pro tier users', function () {
    $this->organization->update(['subscription_tier' => 'pro']);

    Livewire::test(OrganizationDictionary::class)
        ->assertRedirect(route('dashboard'));
});

it('displays existing words', function () {
    DictionaryWord::factory()->organizationLevel()->create([
        'organization_id' => $this->organization->id,
        'word' => 'existingword',
    ]);

    Livewire::test(OrganizationDictionary::class)
        ->assertSee('existingword');
});

it('can add a word', function () {
    Livewire::test(OrganizationDictionary::class)
        ->set('showAddModal', true)
        ->set('newWord', 'NewTestWord')
        ->call('addWord')
        ->assertSet('showAddModal', false)
        ->assertSet('newWord', '');

    expect(DictionaryWord::where('word', 'newtestword')->exists())->toBeTrue();
});

it('validates word is required', function () {
    Livewire::test(OrganizationDictionary::class)
        ->set('newWord', '')
        ->call('addWord')
        ->assertHasErrors(['newWord' => 'required']);
});

it('validates word max length', function () {
    Livewire::test(OrganizationDictionary::class)
        ->set('newWord', str_repeat('a', 101))
        ->call('addWord')
        ->assertHasErrors(['newWord' => 'max']);
});

it('can delete a word', function () {
    $word = DictionaryWord::factory()->organizationLevel()->create([
        'organization_id' => $this->organization->id,
        'word' => 'todelete',
    ]);

    Livewire::test(OrganizationDictionary::class)
        ->call('confirmDelete', $word->id)
        ->assertSet('wordToDelete', $word->id)
        ->assertSet('showDeleteModal', true)
        ->call('deleteWord')
        ->assertSet('showDeleteModal', false);

    expect(DictionaryWord::find($word->id))->toBeNull();
});

it('can search words', function () {
    DictionaryWord::factory()->organizationLevel()->create([
        'organization_id' => $this->organization->id,
        'word' => 'searchable',
    ]);
    DictionaryWord::factory()->organizationLevel()->create([
        'organization_id' => $this->organization->id,
        'word' => 'findme',
    ]);

    Livewire::test(OrganizationDictionary::class)
        ->assertSee('searchable')
        ->assertSee('findme')
        ->set('search', 'search')
        ->assertSee('searchable')
        ->assertDontSee('findme');
});

it('shows bulk import button for team tier', function () {
    Livewire::test(OrganizationDictionary::class)
        ->assertSee('Bulk Import');
});

it('can bulk import words', function () {
    Livewire::test(OrganizationDictionary::class)
        ->set('showBulkModal', true)
        ->set('bulkWords', "word1\nword2\nword3")
        ->call('bulkImport')
        ->assertSet('showBulkModal', false);

    expect(DictionaryWord::where('organization_id', $this->organization->id)->count())->toBe(3);
});

it('shows word count and limit', function () {
    DictionaryWord::factory()->organizationLevel()->count(5)->create([
        'organization_id' => $this->organization->id,
    ]);

    Livewire::test(OrganizationDictionary::class)
        ->assertSee('5')
        ->assertSee('1000'); // Team tier limit
});

it('prevents adding when limit reached', function () {
    // Fill up to the limit (team = 1000) - insert directly to avoid faker issues
    $words = [];
    for ($i = 0; $i < 1000; $i++) {
        $words[] = [
            'organization_id' => $this->organization->id,
            'project_id' => null,
            'word' => "word{$i}",
            'source' => 'custom',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
    DictionaryWord::insert($words);

    $initialCount = DictionaryWord::where('organization_id', $this->organization->id)->count();

    Livewire::test(OrganizationDictionary::class)
        ->set('newWord', 'onemore')
        ->call('addWord');

    // Verify no new word was added
    $finalCount = DictionaryWord::where('organization_id', $this->organization->id)->count();
    expect($finalCount)->toBe($initialCount);
    expect(DictionaryWord::where('word', 'onemore')->exists())->toBeFalse();
});
