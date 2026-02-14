<?php

namespace App\Livewire\Projects\Components;

use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\IssueStateChange;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class IssueKanbanBoard extends Component
{
    public ?Project $project = null;

    public ?int $assigneeFilter = null;

    public ?int $projectFilter = null;

    /**
     * The Kanban columns and their statuses.
     *
     * @var array<string, string>
     */
    public array $columns = [
        'open' => 'Open',
        'in_progress' => 'In Progress',
        'review' => 'Review',
        'resolved' => 'Resolved',
    ];

    public function mount(?Project $project = null): void
    {
        $this->project = $project;
        $this->projectFilter = $project?->id;
    }

    /**
     * Move an issue to a new status column.
     */
    public function moveIssue(int $issueId, string $newStatus): void
    {
        $issue = Issue::findOrFail($issueId);

        // Verify user has permission (must be in same organization)
        if ($this->project) {
            $this->authorize('update', $this->project);
        }

        $assignment = $issue->assignment;
        $oldStatus = $assignment?->status ?? 'open';

        if ($oldStatus === $newStatus) {
            return;
        }

        // Create or update assignment
        if (! $assignment) {
            $assignment = IssueAssignment::create([
                'issue_id' => $issue->id,
                'status' => $newStatus,
            ]);
        } else {
            $assignment->update(['status' => $newStatus]);
        }

        // Record state change
        IssueStateChange::create([
            'issue_id' => $issue->id,
            'user_id' => Auth::id(),
            'from_state' => $oldStatus,
            'to_state' => $newStatus,
            'reason' => null,
        ]);

        $this->dispatch('issue-moved', issueId: $issueId, status: $newStatus);
    }

    /**
     * Assign an issue to a user.
     */
    public function assignIssue(int $issueId, ?int $userId): void
    {
        $issue = Issue::findOrFail($issueId);

        if ($this->project) {
            $this->authorize('update', $this->project);
        }

        $assignment = $issue->assignment;

        if ($assignment) {
            $assignment->update([
                'assigned_to_user_id' => $userId,
                'assigned_by_user_id' => Auth::id(),
                'assigned_at' => now(),
            ]);
        } else {
            IssueAssignment::create([
                'issue_id' => $issue->id,
                'assigned_to_user_id' => $userId,
                'assigned_by_user_id' => Auth::id(),
                'assigned_at' => now(),
                'status' => 'open',
            ]);
        }

        $this->dispatch('issue-assigned', issueId: $issueId, userId: $userId);
    }

    /**
     * Set the assignee filter.
     */
    public function setAssigneeFilter(?int $userId): void
    {
        $this->assigneeFilter = $userId;
    }

    /**
     * Set the project filter.
     */
    public function setProjectFilter(?int $projectId): void
    {
        $this->projectFilter = $projectId;
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->assigneeFilter = null;
        if (! $this->project) {
            $this->projectFilter = null;
        }
    }

    /**
     * Get issues grouped by status column.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    #[Computed]
    public function issuesByColumn(): array
    {
        $query = Issue::query()
            ->with(['assignment.assignedTo', 'result.scan.url.project']);

        // Filter by project
        if ($this->projectFilter) {
            $query->whereHas('result.scan.url', function ($q) {
                $q->where('project_id', $this->projectFilter);
            });
        } elseif ($this->project) {
            $query->whereHas('result.scan.url', function ($q) {
                $q->where('project_id', $this->project->id);
            });
        }

        // Filter by assignee
        if ($this->assigneeFilter) {
            $query->whereHas('assignment', function ($q) {
                $q->where('assigned_to_user_id', $this->assigneeFilter);
            });
        }

        $issues = $query->latest()->get();

        $grouped = [
            'open' => collect(),
            'in_progress' => collect(),
            'review' => collect(),
            'resolved' => collect(),
        ];

        foreach ($issues as $issue) {
            $status = $issue->assignment?->status ?? 'open';
            if (isset($grouped[$status])) {
                $grouped[$status]->push($issue);
            } else {
                $grouped['open']->push($issue);
            }
        }

        return $grouped;
    }

    /**
     * Get count of issues per column.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function columnCounts(): array
    {
        $issues = $this->issuesByColumn;

        return [
            'open' => $issues['open']->count(),
            'in_progress' => $issues['in_progress']->count(),
            'review' => $issues['review']->count(),
            'resolved' => $issues['resolved']->count(),
        ];
    }

    /**
     * Get team members for assignee dropdown.
     */
    #[Computed]
    public function teamMembers(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->project) {
            return $this->project->organization->users()->get();
        }

        return Auth::user()->currentOrganization?->users()->get() ?? collect();
    }

    /**
     * Get available projects for filter (when not scoped to single project).
     */
    #[Computed]
    public function availableProjects(): \Illuminate\Database\Eloquent\Collection
    {
        if ($this->project) {
            return collect([$this->project]);
        }

        return Auth::user()->currentOrganization?->projects()->get() ?? collect();
    }

    /**
     * Get count of issues assigned to current user (for dashboard widget).
     */
    #[Computed]
    public function myAssignedCount(): int
    {
        return Issue::query()
            ->whereHas('assignment', function ($q) {
                $q->where('assigned_to_user_id', Auth::id())
                    ->whereNotIn('status', ['resolved', 'dismissed']);
            })
            ->count();
    }

    public function render(): View
    {
        return view('livewire.projects.components.issue-kanban-board');
    }
}
