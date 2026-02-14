<?php

namespace App\Livewire\Projects\Components;

use App\Models\Project;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class DictionaryConflictResolver extends Component
{
    public Project $project;

    public bool $showModal = false;

    /**
     * Words selected for bulk action.
     *
     * @var array<string>
     */
    public array $selectedWords = [];

    /**
     * Filter for conflict type.
     */
    public string $conflictFilter = 'all';

    /**
     * Search term.
     */
    public string $search = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Open the conflict resolver modal.
     */
    #[On('open-dictionary-conflicts')]
    public function openModal(): void
    {
        $this->showModal = true;
        $this->selectedWords = [];
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedWords = [];
        $this->search = '';
    }

    /**
     * Get conflicts between project and organization dictionaries.
     *
     * @return array<string, array<string, mixed>>
     */
    #[Computed]
    public function conflicts(): array
    {
        $projectDictionary = collect($this->project->dictionary ?? []);
        $orgDictionary = collect($this->project->organization->dictionary ?? []);

        $conflicts = [];

        // Find duplicates (words in both dictionaries)
        $duplicates = $projectDictionary->intersect($orgDictionary);
        foreach ($duplicates as $word) {
            $conflicts[$word] = [
                'word' => $word,
                'type' => 'duplicate',
                'in_project' => true,
                'in_organization' => true,
                'description' => 'Word exists in both project and organization dictionaries',
            ];
        }

        // Find case conflicts
        $projectLower = $projectDictionary->mapWithKeys(fn ($w) => [strtolower($w) => $w]);
        $orgLower = $orgDictionary->mapWithKeys(fn ($w) => [strtolower($w) => $w]);

        foreach ($projectLower as $lower => $projectWord) {
            if (isset($orgLower[$lower]) && $orgLower[$lower] !== $projectWord) {
                if (! isset($conflicts[$projectWord])) {
                    $conflicts[$projectWord] = [
                        'word' => $projectWord,
                        'type' => 'case_mismatch',
                        'in_project' => true,
                        'in_organization' => true,
                        'org_variant' => $orgLower[$lower],
                        'description' => "Case mismatch: project has '{$projectWord}', organization has '{$orgLower[$lower]}'",
                    ];
                }
            }
        }

        // Find words in project that could be promoted to organization
        $projectOnly = $projectDictionary->diff($orgDictionary);
        foreach ($projectOnly as $word) {
            if (! isset($conflicts[$word])) {
                $conflicts[$word] = [
                    'word' => $word,
                    'type' => 'project_only',
                    'in_project' => true,
                    'in_organization' => false,
                    'description' => 'Word is only in project dictionary',
                ];
            }
        }

        // Apply filters
        $conflicts = collect($conflicts);

        if ($this->conflictFilter !== 'all') {
            $conflicts = $conflicts->where('type', $this->conflictFilter);
        }

        if ($this->search) {
            $search = strtolower($this->search);
            $conflicts = $conflicts->filter(fn ($c) => str_contains(strtolower($c['word']), $search));
        }

        return $conflicts->toArray();
    }

    /**
     * Get summary counts.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function summary(): array
    {
        $projectDictionary = collect($this->project->dictionary ?? []);
        $orgDictionary = collect($this->project->organization->dictionary ?? []);

        $duplicates = $projectDictionary->intersect($orgDictionary)->count();

        // Count case mismatches
        $caseMismatches = 0;
        $projectLower = $projectDictionary->mapWithKeys(fn ($w) => [strtolower($w) => $w]);
        $orgLower = $orgDictionary->mapWithKeys(fn ($w) => [strtolower($w) => $w]);

        foreach ($projectLower as $lower => $projectWord) {
            if (isset($orgLower[$lower]) && $orgLower[$lower] !== $projectWord) {
                $caseMismatches++;
            }
        }

        $projectOnly = $projectDictionary->diff($orgDictionary)->count() - $caseMismatches;

        return [
            'total' => $duplicates + $caseMismatches + max(0, $projectOnly),
            'duplicates' => $duplicates,
            'case_mismatches' => $caseMismatches,
            'project_only' => max(0, $projectOnly),
        ];
    }

    /**
     * Toggle word selection.
     */
    public function toggleWord(string $word): void
    {
        if (in_array($word, $this->selectedWords)) {
            $this->selectedWords = array_values(array_diff($this->selectedWords, [$word]));
        } else {
            $this->selectedWords[] = $word;
        }
    }

    /**
     * Select all visible conflicts.
     */
    public function selectAll(): void
    {
        $this->selectedWords = array_keys($this->conflicts);
    }

    /**
     * Deselect all.
     */
    public function deselectAll(): void
    {
        $this->selectedWords = [];
    }

    /**
     * Remove selected duplicates from project dictionary.
     */
    public function removeDuplicatesFromProject(): void
    {
        $projectDictionary = collect($this->project->dictionary ?? []);
        $orgDictionary = collect($this->project->organization->dictionary ?? []);

        // Only remove words that are also in org dictionary
        $toRemove = collect($this->selectedWords)->filter(
            fn ($word) => $orgDictionary->contains($word) || $orgDictionary->map('strtolower')->contains(strtolower($word))
        );

        $newDictionary = $projectDictionary->reject(fn ($w) => $toRemove->contains($w))->values()->toArray();

        $this->project->update(['dictionary' => $newDictionary]);
        $this->selectedWords = [];

        $this->dispatch('dictionary-updated');
        session()->flash('message', $toRemove->count().' word(s) removed from project dictionary.');
    }

    /**
     * Promote selected words to organization dictionary.
     */
    public function promoteToOrganization(): void
    {
        $this->authorize('update', $this->project->organization);

        $orgDictionary = collect($this->project->organization->dictionary ?? []);
        $projectDictionary = collect($this->project->dictionary ?? []);

        $promoted = 0;
        foreach ($this->selectedWords as $word) {
            if (! $orgDictionary->map('strtolower')->contains(strtolower($word))) {
                $orgDictionary->push($word);
                $promoted++;
            }
        }

        $this->project->organization->update(['dictionary' => $orgDictionary->unique()->values()->toArray()]);

        // Remove promoted words from project dictionary
        $newProjectDictionary = $projectDictionary->reject(
            fn ($w) => in_array($w, $this->selectedWords)
        )->values()->toArray();

        $this->project->update(['dictionary' => $newProjectDictionary]);

        $this->selectedWords = [];
        $this->dispatch('dictionary-updated');
        session()->flash('message', "{$promoted} word(s) promoted to organization dictionary.");
    }

    /**
     * Standardize case for selected words.
     */
    public function standardizeCase(string $preferCase = 'organization'): void
    {
        $projectDictionary = collect($this->project->dictionary ?? []);
        $orgDictionary = collect($this->project->organization->dictionary ?? []);
        $orgLower = $orgDictionary->mapWithKeys(fn ($w) => [strtolower($w) => $w]);

        $newDictionary = $projectDictionary->map(function ($word) use ($orgLower, $preferCase) {
            $lower = strtolower($word);
            if (isset($orgLower[$lower]) && in_array($word, $this->selectedWords)) {
                return $preferCase === 'organization' ? $orgLower[$lower] : $word;
            }

            return $word;
        })->unique()->values()->toArray();

        $this->project->update(['dictionary' => $newDictionary]);
        $this->selectedWords = [];

        $this->dispatch('dictionary-updated');
        session()->flash('message', 'Case standardized for selected words.');
    }

    public function render(): View
    {
        return view('livewire.projects.components.dictionary-conflict-resolver');
    }
}
