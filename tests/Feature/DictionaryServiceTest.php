<?php

use App\Models\DictionaryWord;
use App\Models\IndustryDictionary;
use App\Models\IndustryDictionaryWord;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\DictionaryService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->service = app(DictionaryService::class);
});

describe('getWordsForProject', function () {
    it('returns empty array for free tier organization', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'free']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $words = $this->service->getWordsForProject($project);

        expect($words)->toBe([]);
    });

    it('returns project words for pro tier', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        DictionaryWord::factory()->forProject($project)->create([
            'organization_id' => $organization->id,
            'word' => 'testword',
        ]);

        Cache::flush();
        $words = $this->service->getWordsForProject($project);

        expect($words)->toContain('testword');
    });

    it('returns org and project words for team tier', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        DictionaryWord::factory()->organizationLevel()->create([
            'organization_id' => $organization->id,
            'word' => 'orgword',
        ]);

        DictionaryWord::factory()->forProject($project)->create([
            'organization_id' => $organization->id,
            'word' => 'projectword',
        ]);

        Cache::flush();
        $words = $this->service->getWordsForProject($project);

        expect($words)->toContain('orgword', 'projectword');
    });

    it('includes industry dictionary words when enabled', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $industryDict = IndustryDictionary::factory()->create(['is_active' => true]);
        IndustryDictionaryWord::create([
            'industry_dictionary_id' => $industryDict->id,
            'word' => 'industryterm',
        ]);

        $project->industryDictionaries()->attach($industryDict->id);

        Cache::flush();
        $words = $this->service->getWordsForProject($project);

        expect($words)->toContain('industryterm');
    });

    it('caches results', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        DictionaryWord::factory()->forProject($project)->create([
            'organization_id' => $organization->id,
            'word' => 'cachedword',
        ]);

        Cache::flush();
        $words1 = $this->service->getWordsForProject($project);

        // Add another word (should not appear due to cache)
        DictionaryWord::factory()->forProject($project)->create([
            'organization_id' => $organization->id,
            'word' => 'newword',
        ]);

        $words2 = $this->service->getWordsForProject($project);

        expect($words1)->toBe($words2);
        expect($words2)->not->toContain('newword');
    });
});

describe('addOrganizationWord', function () {
    it('adds a word to organization dictionary', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $word = $this->service->addOrganizationWord($organization, 'NewWord', $user);

        expect($word)->toBeInstanceOf(DictionaryWord::class);
        expect($word->word)->toBe('newword');
        expect($word->organization_id)->toBe($organization->id);
        expect($word->project_id)->toBeNull();
        expect($word->added_by_user_id)->toBe($user->id);
    });

    it('does not create duplicate words', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);

        $this->service->addOrganizationWord($organization, 'duplicate');
        $this->service->addOrganizationWord($organization, 'duplicate');

        $count = DictionaryWord::where('organization_id', $organization->id)
            ->whereNull('project_id')
            ->where('word', 'duplicate')
            ->count();

        expect($count)->toBe(1);
    });

    it('clears cache after adding word', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        Cache::flush();
        $this->service->getWordsForProject($project);

        $this->service->addOrganizationWord($organization, 'neworgword');

        // Cache should be cleared, so new word should appear
        $words = $this->service->getWordsForProject($project);

        expect($words)->toContain('neworgword');
    });
});

describe('addProjectWord', function () {
    it('adds a word to project dictionary', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $user = User::factory()->create(['organization_id' => $organization->id]);

        $word = $this->service->addProjectWord($project, 'ProjectTerm', $user);

        expect($word->word)->toBe('projectterm');
        expect($word->project_id)->toBe($project->id);
    });
});

describe('bulkImportOrganizationWords', function () {
    it('imports multiple words', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);

        $result = $this->service->bulkImportOrganizationWords(
            $organization,
            ['word1', 'word2', 'word3']
        );

        expect($result['imported'])->toBe(3);
        expect($result['skipped'])->toBe(0);
        expect($result['errors'])->toBe([]);
    });

    it('skips duplicates', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);

        DictionaryWord::factory()->organizationLevel()->create([
            'organization_id' => $organization->id,
            'word' => 'existing',
        ]);

        $result = $this->service->bulkImportOrganizationWords(
            $organization,
            ['existing', 'newword']
        );

        expect($result['imported'])->toBe(1);
        expect($result['skipped'])->toBe(1);
    });

    it('respects word limit', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);

        // Team tier has 1000 word limit - insert directly to avoid faker issues
        $words = [];
        for ($i = 0; $i < 999; $i++) {
            $words[] = [
                'organization_id' => $organization->id,
                'project_id' => null,
                'word' => "word{$i}",
                'source' => 'custom',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DictionaryWord::insert($words);

        $result = $this->service->bulkImportOrganizationWords(
            $organization,
            ['newword1', 'newword2', 'newword3']
        );

        expect($result['imported'])->toBe(1);
        expect($result['errors'])->toContain('Word limit reached');
    });

    it('skips words longer than 100 characters', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);

        $longWord = str_repeat('a', 101);

        $result = $this->service->bulkImportOrganizationWords(
            $organization,
            [$longWord, 'shortword']
        );

        expect($result['imported'])->toBe(1);
        expect($result['errors'])->toHaveCount(1);
    });
});

describe('toggleIndustryDictionary', function () {
    it('enables an industry dictionary', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $dictionary = IndustryDictionary::factory()->create(['is_active' => true]);

        $result = $this->service->toggleIndustryDictionary($project, $dictionary);

        expect($result)->toBeTrue();
        expect($project->industryDictionaries()->where('industry_dictionary_id', $dictionary->id)->exists())->toBeTrue();
    });

    it('disables an enabled industry dictionary', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);
        $dictionary = IndustryDictionary::factory()->create(['is_active' => true]);

        $project->industryDictionaries()->attach($dictionary->id);

        $result = $this->service->toggleIndustryDictionary($project, $dictionary);

        expect($result)->toBeFalse();
        expect($project->industryDictionaries()->where('industry_dictionary_id', $dictionary->id)->exists())->toBeFalse();
    });

    it('respects industry dictionary limit', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        // Pro tier has limit of 1
        $dict1 = IndustryDictionary::factory()->create(['is_active' => true]);
        $dict2 = IndustryDictionary::factory()->create(['is_active' => true]);

        $project->industryDictionaries()->attach($dict1->id);

        $result = $this->service->toggleIndustryDictionary($project, $dict2);

        expect($result)->toBeFalse();
        expect($project->industryDictionaries()->count())->toBe(1);
    });
});

describe('removeWord', function () {
    it('removes a word and clears cache', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'team']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        $word = DictionaryWord::factory()->forProject($project)->create([
            'organization_id' => $organization->id,
        ]);

        $wordId = $word->id;

        $this->service->removeWord($word);

        expect(DictionaryWord::find($wordId))->toBeNull();
    });
});

describe('wordExistsForProject', function () {
    it('returns true if word exists', function () {
        $organization = Organization::factory()->create(['subscription_tier' => 'pro']);
        $project = Project::factory()->create(['organization_id' => $organization->id]);

        DictionaryWord::factory()->forProject($project)->create([
            'organization_id' => $organization->id,
            'word' => 'existingword',
        ]);

        Cache::flush();

        expect($this->service->wordExistsForProject($project, 'existingword'))->toBeTrue();
        expect($this->service->wordExistsForProject($project, 'ExistingWord'))->toBeTrue();
        expect($this->service->wordExistsForProject($project, 'nonexistent'))->toBeFalse();
    });
});
