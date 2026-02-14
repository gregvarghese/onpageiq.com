<?php

namespace App\Livewire\Projects\Components;

use App\Models\DictionaryWord;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class DictionaryPanel extends Component
{
    public Project $project;

    public bool $showPanel = false;

    #[Validate('required|string|min:1|max:100')]
    public string $newWord = '';

    public string $wordScope = 'project';

    public string $searchQuery = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Open the dictionary panel.
     */
    #[On('open-dictionary-panel')]
    public function openPanel(): void
    {
        $this->showPanel = true;
    }

    /**
     * Close the dictionary panel.
     */
    public function closePanel(): void
    {
        $this->showPanel = false;
        $this->reset(['newWord', 'searchQuery']);
    }

    /**
     * Add a new word to the dictionary.
     */
    public function addWord(): void
    {
        $this->authorize('update', $this->project);
        $this->validate();

        $word = DictionaryWord::normalizeWord($this->newWord);

        // Check if already exists
        $existsQuery = DictionaryWord::where('organization_id', $this->project->organization_id)
            ->where('word', $word);

        if ($this->wordScope === 'project') {
            $existsQuery->where('project_id', $this->project->id);
        } else {
            $existsQuery->whereNull('project_id');
        }

        if ($existsQuery->exists()) {
            $this->addError('newWord', 'This word is already in the dictionary.');

            return;
        }

        // Check limit
        if (! $this->project->canAddDictionaryWord()) {
            $this->dispatch('notify', type: 'error', message: 'Dictionary word limit reached for your plan.');

            return;
        }

        DictionaryWord::create([
            'organization_id' => $this->project->organization_id,
            'project_id' => $this->wordScope === 'project' ? $this->project->id : null,
            'word' => $word,
            'added_by_user_id' => Auth::id(),
            'source' => 'manual',
        ]);

        $this->reset('newWord');
        $this->dispatch('notify', type: 'success', message: "'{$word}' added to dictionary.");
        $this->dispatch('dictionary-updated');
    }

    /**
     * Delete a word from the dictionary.
     */
    public function deleteWord(int $wordId): void
    {
        $this->authorize('update', $this->project);

        $word = DictionaryWord::where('organization_id', $this->project->organization_id)
            ->where(function ($q) {
                $q->whereNull('project_id')
                    ->orWhere('project_id', $this->project->id);
            })
            ->findOrFail($wordId);

        $deletedWord = $word->word;
        $word->delete();

        $this->dispatch('notify', type: 'success', message: "'{$deletedWord}' removed from dictionary.");
        $this->dispatch('dictionary-updated');
    }

    /**
     * Add a word directly from an issue (quick-add).
     */
    #[On('add-word-from-issue')]
    public function addWordFromIssue(string $word): void
    {
        $this->authorize('update', $this->project);

        $normalizedWord = DictionaryWord::normalizeWord($word);

        // Check if already exists at project level
        $exists = DictionaryWord::where('organization_id', $this->project->organization_id)
            ->where('project_id', $this->project->id)
            ->where('word', $normalizedWord)
            ->exists();

        if ($exists) {
            $this->dispatch('notify', type: 'info', message: 'Word already in dictionary.');

            return;
        }

        if (! $this->project->canAddDictionaryWord()) {
            $this->dispatch('notify', type: 'error', message: 'Dictionary word limit reached.');

            return;
        }

        DictionaryWord::create([
            'organization_id' => $this->project->organization_id,
            'project_id' => $this->project->id,
            'word' => $normalizedWord,
            'added_by_user_id' => Auth::id(),
            'source' => 'issue',
        ]);

        $this->dispatch('notify', type: 'success', message: "'{$normalizedWord}' added to dictionary.");
        $this->dispatch('dictionary-updated');
    }

    /**
     * Get organization-level dictionary words.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DictionaryWord>
     */
    #[Computed]
    public function organizationWords(): \Illuminate\Database\Eloquent\Collection
    {
        $query = DictionaryWord::where('organization_id', $this->project->organization_id)
            ->whereNull('project_id')
            ->orderBy('word');

        if ($this->searchQuery) {
            $query->where('word', 'like', '%'.$this->searchQuery.'%');
        }

        return $query->get();
    }

    /**
     * Get project-level dictionary words.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DictionaryWord>
     */
    #[Computed]
    public function projectWords(): \Illuminate\Database\Eloquent\Collection
    {
        $query = DictionaryWord::where('organization_id', $this->project->organization_id)
            ->where('project_id', $this->project->id)
            ->orderBy('word');

        if ($this->searchQuery) {
            $query->where('word', 'like', '%'.$this->searchQuery.'%');
        }

        return $query->get();
    }

    /**
     * Get total word count vs limit.
     *
     * @return array{current: int, limit: int|null}
     */
    #[Computed]
    public function wordStats(): array
    {
        $current = DictionaryWord::where('organization_id', $this->project->organization_id)
            ->where(function ($q) {
                $q->whereNull('project_id')
                    ->orWhere('project_id', $this->project->id);
            })
            ->count();

        return [
            'current' => $current,
            'limit' => $this->project->organization->getDictionaryWordsLimit(),
        ];
    }

    public function render(): View
    {
        return view('livewire.projects.components.dictionary-panel');
    }
}
