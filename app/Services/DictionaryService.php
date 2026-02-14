<?php

namespace App\Services;

use App\Models\DictionaryWord;
use App\Models\IndustryDictionary;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DictionaryService
{
    /**
     * Cache TTL in seconds (6 hours).
     */
    protected const CACHE_TTL = 21600;

    /**
     * Get all words applicable to a project (merged from all sources).
     *
     * Hierarchy (merge order):
     * 1. Industry Dictionaries (if enabled for project)
     * 2. Organization Dictionary (global to org)
     * 3. Project Dictionary (project-specific)
     *
     * @return array<string>
     */
    public function getWordsForProject(Project $project): array
    {
        $cacheKey = $this->getProjectCacheKey($project);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($project) {
            $words = [];

            // 1. Get industry dictionary words
            $industryWords = $this->getIndustryDictionaryWords($project);
            $words = array_merge($words, $industryWords);

            // 2. Get organization-level words
            $orgWords = $this->getOrganizationWords($project->organization);
            $words = array_merge($words, $orgWords);

            // 3. Get project-specific words
            $projectWords = $this->getProjectWords($project);
            $words = array_merge($words, $projectWords);

            // Return unique, normalized words
            return array_values(array_unique(array_map('strtolower', $words)));
        });
    }

    /**
     * Get words from enabled industry dictionaries for a project.
     *
     * @return array<string>
     */
    protected function getIndustryDictionaryWords(Project $project): array
    {
        if (! $project->organization->canUseIndustryDictionaries()) {
            return [];
        }

        return $project->industryDictionaries()
            ->where('is_active', true)
            ->with('words')
            ->get()
            ->flatMap(fn (IndustryDictionary $dict) => $dict->words->pluck('word'))
            ->toArray();
    }

    /**
     * Get organization-level dictionary words.
     *
     * @return array<string>
     */
    protected function getOrganizationWords(Organization $organization): array
    {
        if (! $organization->canUseOrganizationDictionary()) {
            return [];
        }

        return $organization->organizationDictionaryWords()
            ->pluck('word')
            ->toArray();
    }

    /**
     * Get project-specific dictionary words.
     *
     * @return array<string>
     */
    protected function getProjectWords(Project $project): array
    {
        if (! $project->organization->canUseProjectDictionary()) {
            return [];
        }

        return $project->dictionaryWords()
            ->pluck('word')
            ->toArray();
    }

    /**
     * Add a word to the organization dictionary.
     */
    public function addOrganizationWord(
        Organization $organization,
        string $word,
        ?User $addedBy = null,
        string $source = 'custom',
        ?string $notes = null
    ): DictionaryWord {
        $word = DictionaryWord::normalizeWord($word);

        $dictionaryWord = DictionaryWord::firstOrCreate(
            [
                'organization_id' => $organization->id,
                'project_id' => null,
                'word' => $word,
            ],
            [
                'added_by_user_id' => $addedBy?->id,
                'source' => $source,
                'notes' => $notes,
            ]
        );

        $this->clearOrganizationCache($organization);

        return $dictionaryWord;
    }

    /**
     * Add a word to a project dictionary.
     */
    public function addProjectWord(
        Project $project,
        string $word,
        ?User $addedBy = null,
        string $source = 'custom',
        ?string $notes = null
    ): DictionaryWord {
        $word = DictionaryWord::normalizeWord($word);

        $dictionaryWord = DictionaryWord::firstOrCreate(
            [
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'word' => $word,
            ],
            [
                'added_by_user_id' => $addedBy?->id,
                'source' => $source,
                'notes' => $notes,
            ]
        );

        $this->clearProjectCache($project);

        return $dictionaryWord;
    }

    /**
     * Bulk import words to organization dictionary.
     *
     * @param  array<string>  $words
     * @return array{imported: int, skipped: int, errors: array<string>}
     */
    public function bulkImportOrganizationWords(
        Organization $organization,
        array $words,
        ?User $addedBy = null
    ): array {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $limit = $organization->getOrganizationDictionaryWordLimit();
        $currentCount = $organization->organizationDictionaryWords()->count();

        foreach ($words as $word) {
            $word = trim($word);

            if (empty($word)) {
                continue;
            }

            if (mb_strlen($word) > 100) {
                $errors[] = "Word too long: {$word}";

                continue;
            }

            // Check limit
            if ($limit !== null && ($currentCount + $imported) >= $limit) {
                $errors[] = 'Word limit reached';
                break;
            }

            $normalizedWord = DictionaryWord::normalizeWord($word);

            // Check for duplicate
            $exists = DictionaryWord::where('organization_id', $organization->id)
                ->whereNull('project_id')
                ->where('word', $normalizedWord)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DictionaryWord::create([
                'organization_id' => $organization->id,
                'project_id' => null,
                'added_by_user_id' => $addedBy?->id,
                'word' => $normalizedWord,
                'source' => 'imported',
            ]);

            $imported++;
        }

        if ($imported > 0) {
            $this->clearOrganizationCache($organization);
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Bulk import words to project dictionary.
     *
     * @param  array<string>  $words
     * @return array{imported: int, skipped: int, errors: array<string>}
     */
    public function bulkImportProjectWords(
        Project $project,
        array $words,
        ?User $addedBy = null
    ): array {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $limit = $project->organization->getProjectDictionaryWordLimit();
        $currentCount = $project->dictionaryWords()->count();

        foreach ($words as $word) {
            $word = trim($word);

            if (empty($word)) {
                continue;
            }

            if (mb_strlen($word) > 100) {
                $errors[] = "Word too long: {$word}";

                continue;
            }

            // Check limit
            if ($limit !== null && ($currentCount + $imported) >= $limit) {
                $errors[] = 'Word limit reached';
                break;
            }

            $normalizedWord = DictionaryWord::normalizeWord($word);

            // Check for duplicate
            $exists = DictionaryWord::where('organization_id', $project->organization_id)
                ->where('project_id', $project->id)
                ->where('word', $normalizedWord)
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            DictionaryWord::create([
                'organization_id' => $project->organization_id,
                'project_id' => $project->id,
                'added_by_user_id' => $addedBy?->id,
                'word' => $normalizedWord,
                'source' => 'imported',
            ]);

            $imported++;
        }

        if ($imported > 0) {
            $this->clearProjectCache($project);
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Remove a word from the dictionary.
     */
    public function removeWord(DictionaryWord $word): void
    {
        $project = $word->project;
        $organization = $word->organization;

        $word->delete();

        if ($project) {
            $this->clearProjectCache($project);
        } else {
            $this->clearOrganizationCache($organization);
        }
    }

    /**
     * Toggle an industry dictionary for a project.
     */
    public function toggleIndustryDictionary(Project $project, IndustryDictionary $dictionary): bool
    {
        $isEnabled = $project->industryDictionaries()->where('industry_dictionary_id', $dictionary->id)->exists();

        if ($isEnabled) {
            $project->industryDictionaries()->detach($dictionary->id);
            $this->clearProjectCache($project);

            return false;
        }

        // Check if can enable more
        if (! $project->canEnableMoreIndustryDictionaries()) {
            return false;
        }

        $project->industryDictionaries()->attach($dictionary->id);
        $this->clearProjectCache($project);

        return true;
    }

    /**
     * Enable an industry dictionary for a project.
     */
    public function enableIndustryDictionary(Project $project, IndustryDictionary $dictionary): bool
    {
        if (! $project->canEnableMoreIndustryDictionaries()) {
            return false;
        }

        if (! $project->industryDictionaries()->where('industry_dictionary_id', $dictionary->id)->exists()) {
            $project->industryDictionaries()->attach($dictionary->id);
            $this->clearProjectCache($project);
        }

        return true;
    }

    /**
     * Disable an industry dictionary for a project.
     */
    public function disableIndustryDictionary(Project $project, IndustryDictionary $dictionary): void
    {
        $project->industryDictionaries()->detach($dictionary->id);
        $this->clearProjectCache($project);
    }

    /**
     * Get all available industry dictionaries with enabled status for a project.
     *
     * @return Collection<int, array{dictionary: IndustryDictionary, enabled: bool}>
     */
    public function getIndustryDictionariesForProject(Project $project): Collection
    {
        $enabledIds = $project->industryDictionaries()->pluck('industry_dictionaries.id')->toArray();

        return IndustryDictionary::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (IndustryDictionary $dict) => [
                'dictionary' => $dict,
                'enabled' => in_array($dict->id, $enabledIds),
            ]);
    }

    /**
     * Clear cache for a project.
     */
    public function clearProjectCache(Project $project): void
    {
        Cache::forget($this->getProjectCacheKey($project));
    }

    /**
     * Clear cache for all projects in an organization.
     */
    public function clearOrganizationCache(Organization $organization): void
    {
        // Clear cache for all projects in the organization
        $organization->projects->each(function (Project $project) {
            $this->clearProjectCache($project);
        });
    }

    /**
     * Get the cache key for a project's dictionary words.
     */
    protected function getProjectCacheKey(Project $project): string
    {
        return "dictionary_words:project:{$project->id}";
    }

    /**
     * Check if a word exists in the project's dictionary (including org + industry).
     */
    public function wordExistsForProject(Project $project, string $word): bool
    {
        $words = $this->getWordsForProject($project);

        return in_array(strtolower(trim($word)), $words, true);
    }
}
