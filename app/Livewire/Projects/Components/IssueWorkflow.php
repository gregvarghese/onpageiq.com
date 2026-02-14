<?php

namespace App\Livewire\Projects\Components;

use App\Models\DismissedIssue;
use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class IssueWorkflow extends Component
{
    use WithPagination;

    public Project $project;

    public string $categoryFilter = '';

    public string $severityFilter = '';

    public string $assigneeFilter = '';

    public string $statusFilter = '';

    public bool $showAssignModal = false;

    public bool $showDismissModal = false;

    public ?Issue $selectedIssue = null;

    public ?int $assignToUserId = null;

    public ?string $dueDate = null;

    public string $dismissScope = 'url';

    public string $dismissReason = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Get filtered issues for this project.
     */
    #[Computed]
    public function issues(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $urlIds = $this->project->urls()->pluck('id');

        $query = Issue::query()
            ->select('issues.*')
            ->join('scan_results', 'issues.scan_result_id', '=', 'scan_results.id')
            ->join('scans', 'scan_results.scan_id', '=', 'scans.id')
            ->whereIn('scans.url_id', $urlIds)
            ->with(['scanResult.scan.url', 'assignment.assignedTo'])
            ->latest('issues.created_at');

        if ($this->categoryFilter) {
            $query->where('issues.category', $this->categoryFilter);
        }

        if ($this->severityFilter) {
            $query->where('issues.severity', $this->severityFilter);
        }

        if ($this->assigneeFilter) {
            if ($this->assigneeFilter === 'unassigned') {
                $query->whereDoesntHave('assignment');
            } else {
                $query->whereHas('assignment', function ($q) {
                    $q->where('assigned_to_user_id', $this->assigneeFilter);
                });
            }
        }

        if ($this->statusFilter) {
            if ($this->statusFilter === 'unassigned') {
                $query->whereDoesntHave('assignment');
            } else {
                $query->whereHas('assignment', function ($q) {
                    $q->where('status', $this->statusFilter);
                });
            }
        }

        return $query->paginate(20);
    }

    /**
     * Get team members for assignment dropdown.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    #[Computed]
    public function teamMembers(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->organization->users()
            ->orderBy('name')
            ->get();
    }

    /**
     * Open the assign modal.
     */
    public function openAssignModal(int $issueId): void
    {
        if (! $this->project->organization->canUseIssueAssignments()) {
            $this->dispatch('notify', type: 'error', message: 'Issue assignments require a Team or Enterprise plan.');

            return;
        }

        $this->selectedIssue = Issue::findOrFail($issueId);
        $this->assignToUserId = $this->selectedIssue->assignment?->assigned_to_user_id;
        $this->dueDate = $this->selectedIssue->assignment?->due_date?->format('Y-m-d');
        $this->showAssignModal = true;
    }

    /**
     * Save the assignment.
     */
    public function saveAssignment(): void
    {
        $this->authorize('update', $this->project);

        if (! $this->assignToUserId) {
            $this->addError('assignToUserId', 'Please select a team member.');

            return;
        }

        $existingAssignment = $this->selectedIssue->assignment;

        if ($existingAssignment) {
            $existingAssignment->update([
                'assigned_to_user_id' => $this->assignToUserId,
                'due_date' => $this->dueDate ?: null,
            ]);
        } else {
            IssueAssignment::create([
                'issue_id' => $this->selectedIssue->id,
                'assigned_by_user_id' => Auth::id(),
                'assigned_to_user_id' => $this->assignToUserId,
                'due_date' => $this->dueDate ?: null,
                'status' => 'open',
            ]);
        }

        $this->closeAssignModal();
        $this->dispatch('notify', type: 'success', message: 'Issue assigned successfully.');
    }

    /**
     * Update assignment status.
     */
    public function updateStatus(int $issueId, string $status): void
    {
        $this->authorize('update', $this->project);

        $issue = Issue::findOrFail($issueId);
        $assignment = $issue->assignment;

        if (! $assignment) {
            return;
        }

        $data = ['status' => $status];

        if (in_array($status, ['resolved', 'dismissed'])) {
            $data['resolved_at'] = now();
        }

        $assignment->update($data);

        $this->dispatch('notify', type: 'success', message: 'Status updated.');
    }

    /**
     * Close assign modal.
     */
    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->reset(['selectedIssue', 'assignToUserId', 'dueDate']);
    }

    /**
     * Open dismiss modal.
     */
    public function openDismissModal(int $issueId): void
    {
        $this->selectedIssue = Issue::with('scanResult.scan.url')->findOrFail($issueId);
        $this->dismissScope = 'url';
        $this->dismissReason = '';
        $this->showDismissModal = true;
    }

    /**
     * Dismiss the issue.
     */
    public function dismissIssue(): void
    {
        $this->authorize('update', $this->project);

        $url = $this->selectedIssue->scanResult->scan->url;

        DismissedIssue::create([
            'organization_id' => $this->project->organization_id,
            'project_id' => $this->dismissScope !== 'url' ? $this->project->id : null,
            'url_id' => $this->dismissScope === 'url' ? $url->id : null,
            'dismissed_by_user_id' => Auth::id(),
            'scope' => $this->dismissScope,
            'category' => $this->selectedIssue->category,
            'text_pattern' => $this->selectedIssue->text_excerpt,
            'reason' => $this->dismissReason ?: null,
        ]);

        // If there's an assignment, mark it as dismissed
        if ($this->selectedIssue->assignment) {
            $this->selectedIssue->assignment->markAsDismissed($this->dismissReason);
        }

        $this->closeDismissModal();
        $this->dispatch('notify', type: 'success', message: 'Issue dismissed.');
    }

    /**
     * Close dismiss modal.
     */
    public function closeDismissModal(): void
    {
        $this->showDismissModal = false;
        $this->reset(['selectedIssue', 'dismissScope', 'dismissReason']);
    }

    /**
     * Add word to dictionary.
     */
    #[On('add-to-dictionary')]
    public function addToDictionary(int $issueId): void
    {
        $this->authorize('update', $this->project);

        $issue = Issue::findOrFail($issueId);

        if ($issue->category !== 'spelling') {
            return;
        }

        $word = trim($issue->text_excerpt);

        // Check if already exists
        $exists = $this->project->dictionaryWords()
            ->where('word', $word)
            ->exists();

        if ($exists) {
            $this->dispatch('notify', type: 'info', message: 'Word already in dictionary.');

            return;
        }

        if (! $this->project->canAddDictionaryWord()) {
            $this->dispatch('notify', type: 'error', message: 'Dictionary word limit reached.');

            return;
        }

        $this->project->dictionaryWords()->create([
            'organization_id' => $this->project->organization_id,
            'word' => $word,
            'added_by_user_id' => Auth::id(),
        ]);

        $this->dispatch('notify', type: 'success', message: "'{$word}' added to dictionary.");
    }

    public function render(): View
    {
        return view('livewire.projects.components.issue-workflow');
    }
}
