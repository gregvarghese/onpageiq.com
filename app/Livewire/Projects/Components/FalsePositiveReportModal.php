<?php

namespace App\Livewire\Projects\Components;

use App\Models\FalsePositiveReport;
use App\Models\Issue;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class FalsePositiveReportModal extends Component
{
    public bool $showModal = false;

    public ?Issue $issue = null;

    #[Validate('required|in:valid_word,industry_term,brand_name,context_specific,other')]
    public string $category = 'valid_word';

    #[Validate('nullable|string|max:1000')]
    public string $context = '';

    public bool $addToDictionary = false;

    public bool $submitted = false;

    /**
     * Open the modal for a specific issue.
     */
    #[On('report-false-positive')]
    public function openModal(int $issueId): void
    {
        $this->issue = Issue::with(['result.scan.url.project'])->findOrFail($issueId);
        $this->reset(['category', 'context', 'addToDictionary', 'submitted']);
        $this->category = 'valid_word';
        $this->showModal = true;
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->issue = null;
        $this->reset(['category', 'context', 'addToDictionary', 'submitted']);
    }

    /**
     * Submit the false positive report.
     */
    public function submit(): void
    {
        $this->validate();

        if (! $this->issue) {
            return;
        }

        // Create the false positive report
        FalsePositiveReport::create([
            'issue_id' => $this->issue->id,
            'reported_by_user_id' => Auth::id(),
            'category' => $this->category,
            'context' => $this->context ?: null,
            'status' => 'pending',
        ]);

        // Optionally add to dictionary
        if ($this->addToDictionary && $this->issue->text_excerpt) {
            $project = $this->issue->result?->scan?->url?->project;
            if ($project) {
                $project->organization->dictionaryWords()->firstOrCreate(
                    ['word' => strtolower($this->issue->text_excerpt)],
                    [
                        'added_by_user_id' => Auth::id(),
                        'source' => 'false_positive_report',
                    ]
                );
            }
        }

        $this->submitted = true;

        $this->dispatch('false-positive-reported', issueId: $this->issue->id);
    }

    /**
     * Get the category options.
     *
     * @return array<string, array{label: string, description: string}>
     */
    public function getCategoryOptions(): array
    {
        return [
            'valid_word' => [
                'label' => 'Valid Word',
                'description' => 'This is a correctly spelled word not in our dictionary',
            ],
            'industry_term' => [
                'label' => 'Industry Term',
                'description' => 'This is a technical or industry-specific term',
            ],
            'brand_name' => [
                'label' => 'Brand Name',
                'description' => 'This is a brand, product, or company name',
            ],
            'context_specific' => [
                'label' => 'Context Specific',
                'description' => 'The flagged text is correct in this specific context',
            ],
            'other' => [
                'label' => 'Other',
                'description' => 'Another reason (please explain below)',
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.projects.components.false-positive-report-modal', [
            'categoryOptions' => $this->getCategoryOptions(),
        ]);
    }
}
