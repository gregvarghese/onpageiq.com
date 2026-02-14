<?php

namespace App\Livewire\Projects;

use App\Models\DictionaryWord;
use App\Models\IndustryDictionary;
use App\Models\Project;
use App\Services\DictionaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProjectDictionary extends Component
{
    use WithPagination;

    public Project $project;

    #[Validate('required|string|max:100')]
    public string $newWord = '';

    public string $search = '';

    public bool $showAddModal = false;

    public bool $showDeleteModal = false;

    public ?int $wordToDelete = null;

    public function mount(Project $project): void
    {
        $this->project = $project;

        // Authorize access
        if ($project->organization_id !== Auth::user()->organization_id) {
            abort(403);
        }

        if (! $project->organization->canUseProjectDictionary()) {
            session()->flash('error', 'Project dictionary is not available on your current plan.');
            $this->redirect(route('projects.show', $project));
        }
    }

    public function addWord(): void
    {
        $this->validate();

        if (! $this->project->canAddDictionaryWord()) {
            session()->flash('error', 'Word limit reached. Please upgrade your plan or remove existing words.');

            return;
        }

        $dictionaryService = app(DictionaryService::class);
        $dictionaryService->addProjectWord(
            project: $this->project,
            word: $this->newWord,
            addedBy: Auth::user()
        );

        $this->newWord = '';
        $this->showAddModal = false;

        session()->flash('success', 'Word added to dictionary.');
    }

    public function confirmDelete(int $wordId): void
    {
        $this->wordToDelete = $wordId;
        $this->showDeleteModal = true;
    }

    public function deleteWord(): void
    {
        if ($this->wordToDelete) {
            $word = DictionaryWord::where('id', $this->wordToDelete)
                ->where('project_id', $this->project->id)
                ->first();

            if ($word) {
                $dictionaryService = app(DictionaryService::class);
                $dictionaryService->removeWord($word);
                session()->flash('success', 'Word removed from dictionary.');
            }
        }

        $this->wordToDelete = null;
        $this->showDeleteModal = false;
    }

    public function toggleIndustryDictionary(int $dictionaryId): void
    {
        $dictionary = IndustryDictionary::find($dictionaryId);

        if (! $dictionary) {
            return;
        }

        $dictionaryService = app(DictionaryService::class);
        $isNowEnabled = $dictionaryService->toggleIndustryDictionary($this->project, $dictionary);

        if ($isNowEnabled) {
            session()->flash('success', "Enabled {$dictionary->name} dictionary.");
        } else {
            session()->flash('success', "Disabled {$dictionary->name} dictionary.");
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $organization = $this->project->organization;

        $query = $this->project->dictionaryWords()
            ->with('addedBy')
            ->orderBy('word');

        if ($this->search) {
            $query->where('word', 'like', '%'.strtolower($this->search).'%');
        }

        $words = $query->paginate(25);

        $limit = $organization->getProjectDictionaryWordLimit();
        $currentCount = $this->project->dictionaryWords()->count();
        $remaining = $limit === null ? null : max(0, $limit - $currentCount);

        // Get industry dictionaries
        $dictionaryService = app(DictionaryService::class);
        $industryDictionaries = $dictionaryService->getIndustryDictionariesForProject($this->project);

        $industryLimit = $organization->getIndustryDictionaryLimit();
        $enabledIndustryCount = $this->project->getEnabledIndustryDictionaryCount();

        return view('livewire.projects.project-dictionary', [
            'words' => $words,
            'limit' => $limit,
            'currentCount' => $currentCount,
            'remaining' => $remaining,
            'industryDictionaries' => $industryDictionaries,
            'industryLimit' => $industryLimit,
            'enabledIndustryCount' => $enabledIndustryCount,
            'canUseIndustryDictionaries' => $organization->canUseIndustryDictionaries(),
        ]);
    }
}
