<?php

namespace App\Livewire\Scans;

use App\Models\Project;
use App\Models\ScanResult;
use App\Services\DictionaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;

class IssueList extends Component
{
    public ScanResult $scanResult;

    public string $category = '';

    public string $severity = '';

    public bool $showAddToDictionaryModal = false;

    public string $wordToAdd = '';

    public string $addToScope = 'project';

    public function mount(ScanResult $scanResult): void
    {
        $this->scanResult = $scanResult;
    }

    public function filterByCategory(string $category): void
    {
        $this->category = $this->category === $category ? '' : $category;
    }

    public function filterBySeverity(string $severity): void
    {
        $this->severity = $this->severity === $severity ? '' : $severity;
    }

    public function clearFilters(): void
    {
        $this->category = '';
        $this->severity = '';
    }

    public function openAddToDictionaryModal(string $word): void
    {
        $this->wordToAdd = $word;
        $this->addToScope = 'project';
        $this->showAddToDictionaryModal = true;
    }

    public function addToDictionary(): void
    {
        $project = $this->getProject();
        $organization = $project->organization;

        if (empty($this->wordToAdd)) {
            return;
        }

        $dictionaryService = app(DictionaryService::class);

        if ($this->addToScope === 'organization') {
            if (! $organization->canUseOrganizationDictionary()) {
                session()->flash('error', 'Organization dictionary is not available on your current plan.');
                $this->showAddToDictionaryModal = false;

                return;
            }

            if (! $organization->canAddOrganizationDictionaryWord()) {
                session()->flash('error', 'Organization dictionary word limit reached.');
                $this->showAddToDictionaryModal = false;

                return;
            }

            $dictionaryService->addOrganizationWord(
                organization: $organization,
                word: $this->wordToAdd,
                addedBy: Auth::user(),
                source: 'scan_suggestion'
            );

            session()->flash('success', "\"{$this->wordToAdd}\" added to organization dictionary.");
        } else {
            if (! $organization->canUseProjectDictionary()) {
                session()->flash('error', 'Project dictionary is not available on your current plan.');
                $this->showAddToDictionaryModal = false;

                return;
            }

            if (! $project->canAddDictionaryWord()) {
                session()->flash('error', 'Project dictionary word limit reached.');
                $this->showAddToDictionaryModal = false;

                return;
            }

            $dictionaryService->addProjectWord(
                project: $project,
                word: $this->wordToAdd,
                addedBy: Auth::user(),
                source: 'scan_suggestion'
            );

            session()->flash('success', "\"{$this->wordToAdd}\" added to project dictionary.");
        }

        $this->wordToAdd = '';
        $this->showAddToDictionaryModal = false;
    }

    protected function getProject(): Project
    {
        return $this->scanResult->scan->url->project;
    }

    public function render(): View
    {
        $issues = $this->scanResult->issues()
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->severity, fn ($q) => $q->where('severity', $this->severity))
            ->orderByRaw("CASE severity WHEN 'error' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->get();

        $project = $this->getProject();
        $organization = $project->organization;

        return view('livewire.scans.issue-list', [
            'issues' => $issues,
            'canAddToProjectDictionary' => $organization->canUseProjectDictionary() && $project->canAddDictionaryWord(),
            'canAddToOrganizationDictionary' => $organization->canUseOrganizationDictionary() && $organization->canAddOrganizationDictionaryWord(),
        ]);
    }
}
