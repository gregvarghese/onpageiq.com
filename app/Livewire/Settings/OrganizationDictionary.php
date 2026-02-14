<?php

namespace App\Livewire\Settings;

use App\Models\DictionaryWord;
use App\Services\DictionaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class OrganizationDictionary extends Component
{
    use WithPagination;

    #[Validate('required|string|max:100')]
    public string $newWord = '';

    public string $bulkWords = '';

    public string $search = '';

    public bool $showAddModal = false;

    public bool $showBulkModal = false;

    public bool $showDeleteModal = false;

    public ?int $wordToDelete = null;

    public function mount(): void
    {
        $organization = Auth::user()->organization;

        if (! $organization->canUseOrganizationDictionary()) {
            session()->flash('error', 'Organization dictionary is not available on your current plan.');
            $this->redirect(route('dashboard'));
        }
    }

    public function addWord(): void
    {
        $this->validate();

        $organization = Auth::user()->organization;

        if (! $organization->canAddOrganizationDictionaryWord()) {
            session()->flash('error', 'Word limit reached. Please upgrade your plan or remove existing words.');

            return;
        }

        $dictionaryService = app(DictionaryService::class);
        $dictionaryService->addOrganizationWord(
            organization: $organization,
            word: $this->newWord,
            addedBy: Auth::user()
        );

        $this->newWord = '';
        $this->showAddModal = false;

        session()->flash('success', 'Word added to dictionary.');
    }

    public function bulkImport(): void
    {
        $organization = Auth::user()->organization;

        if (! $organization->canBulkImportDictionary()) {
            session()->flash('error', 'Bulk import is not available on your current plan.');

            return;
        }

        $words = array_filter(
            array_map('trim', preg_split('/[\n,]+/', $this->bulkWords))
        );

        if (empty($words)) {
            session()->flash('error', 'No valid words provided.');

            return;
        }

        $dictionaryService = app(DictionaryService::class);
        $result = $dictionaryService->bulkImportOrganizationWords(
            organization: $organization,
            words: $words,
            addedBy: Auth::user()
        );

        $this->bulkWords = '';
        $this->showBulkModal = false;

        $message = "Imported {$result['imported']} words.";
        if ($result['skipped'] > 0) {
            $message .= " Skipped {$result['skipped']} duplicates.";
        }
        if (! empty($result['errors'])) {
            $message .= ' Errors: '.implode(', ', array_slice($result['errors'], 0, 3));
        }

        session()->flash('success', $message);
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
                ->where('organization_id', Auth::user()->organization_id)
                ->whereNull('project_id')
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $organization = Auth::user()->organization;

        $query = $organization->organizationDictionaryWords()
            ->with('addedBy')
            ->orderBy('word');

        if ($this->search) {
            $query->where('word', 'like', '%'.strtolower($this->search).'%');
        }

        $words = $query->paginate(25);

        $limit = $organization->getOrganizationDictionaryWordLimit();
        $currentCount = $organization->organizationDictionaryWords()->count();
        $remaining = $limit === null ? null : max(0, $limit - $currentCount);

        return view('livewire.settings.organization-dictionary', [
            'words' => $words,
            'limit' => $limit,
            'currentCount' => $currentCount,
            'remaining' => $remaining,
            'canBulkImport' => $organization->canBulkImportDictionary(),
        ]);
    }
}
