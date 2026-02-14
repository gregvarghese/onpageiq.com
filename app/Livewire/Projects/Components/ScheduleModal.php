<?php

namespace App\Livewire\Projects\Components;

use App\Models\Project;
use App\Models\ScanSchedule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ScheduleModal extends Component
{
    public Project $project;

    public bool $showModal = false;

    public ?ScanSchedule $editingSchedule = null;

    #[Validate('required|in:hourly,daily,weekly,monthly')]
    public string $frequency = 'daily';

    #[Validate('required|in:quick,deep')]
    public string $scanType = 'quick';

    #[Validate('nullable|date_format:H:i')]
    public ?string $preferredTime = '09:00';

    #[Validate('nullable|integer|min:0|max:6')]
    public ?int $dayOfWeek = 1;

    #[Validate('nullable|integer|min:1|max:28')]
    public ?int $dayOfMonth = 1;

    public ?int $urlGroupId = null;

    public bool $isActive = true;

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Open modal to create new schedule.
     */
    public function openCreateModal(): void
    {
        $this->authorize('update', $this->project);

        if (! $this->canCreateSchedule()) {
            $this->dispatch('notify', type: 'error', message: 'You have reached the scheduled scans limit for your plan.');

            return;
        }

        $this->reset(['frequency', 'scanType', 'preferredTime', 'dayOfWeek', 'dayOfMonth', 'urlGroupId', 'isActive', 'editingSchedule']);
        $this->frequency = 'daily';
        $this->scanType = 'quick';
        $this->preferredTime = '09:00';
        $this->dayOfWeek = 1;
        $this->dayOfMonth = 1;
        $this->isActive = true;
        $this->showModal = true;
    }

    /**
     * Open modal to edit existing schedule.
     */
    public function openEditModal(int $scheduleId): void
    {
        $this->authorize('update', $this->project);

        $this->editingSchedule = $this->project->scanSchedules()->findOrFail($scheduleId);
        $this->frequency = $this->editingSchedule->frequency;
        $this->scanType = $this->editingSchedule->scan_type;
        $this->preferredTime = $this->editingSchedule->preferred_time?->format('H:i');
        $this->dayOfWeek = $this->editingSchedule->day_of_week;
        $this->dayOfMonth = $this->editingSchedule->day_of_month;
        $this->urlGroupId = $this->editingSchedule->url_group_id;
        $this->isActive = $this->editingSchedule->is_active;
        $this->showModal = true;
    }

    /**
     * Save schedule.
     */
    public function save(): void
    {
        $this->authorize('update', $this->project);
        $this->validate();

        $data = [
            'frequency' => $this->frequency,
            'scan_type' => $this->scanType,
            'preferred_time' => $this->preferredTime,
            'day_of_week' => $this->frequency === 'weekly' ? $this->dayOfWeek : null,
            'day_of_month' => $this->frequency === 'monthly' ? $this->dayOfMonth : null,
            'url_group_id' => $this->urlGroupId ?: null,
            'is_active' => $this->isActive,
        ];

        if ($this->editingSchedule) {
            $this->editingSchedule->update($data);
            $this->editingSchedule->update(['next_run_at' => $this->editingSchedule->calculateNextRunAt()]);
            $this->dispatch('notify', type: 'success', message: 'Schedule updated successfully.');
        } else {
            $schedule = $this->project->scanSchedules()->create($data);
            $schedule->update(['next_run_at' => $schedule->calculateNextRunAt()]);
            $this->dispatch('notify', type: 'success', message: 'Schedule created successfully.');
        }

        $this->closeModal();
        $this->dispatch('schedule-updated');
    }

    /**
     * Delete schedule.
     */
    public function deleteSchedule(int $scheduleId): void
    {
        $this->authorize('update', $this->project);

        $schedule = $this->project->scanSchedules()->findOrFail($scheduleId);
        $schedule->delete();

        $this->dispatch('notify', type: 'success', message: 'Schedule deleted.');
        $this->dispatch('schedule-updated');
    }

    /**
     * Toggle schedule active state.
     */
    public function toggleActive(int $scheduleId): void
    {
        $this->authorize('update', $this->project);

        $schedule = $this->project->scanSchedules()->findOrFail($scheduleId);
        $schedule->update(['is_active' => ! $schedule->is_active]);

        $this->dispatch('schedule-updated');
    }

    /**
     * Close modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['editingSchedule']);
    }

    /**
     * Check if user can create more schedules.
     */
    public function canCreateSchedule(): bool
    {
        $organization = $this->project->organization;

        if (! $organization->canCreateScheduledScans()) {
            return false;
        }

        $limit = $organization->getScheduledScansLimit();

        if ($limit === null) {
            return true;
        }

        // Count schedules across all projects in the organization
        $currentCount = ScanSchedule::whereIn('project_id', $organization->projects()->pluck('id'))->count();

        return $currentCount < $limit;
    }

    /**
     * Get URL groups for dropdown.
     */
    #[Computed]
    public function urlGroups(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->urlGroups;
    }

    /**
     * Get schedules for this project.
     */
    #[Computed]
    public function schedules(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->project->scanSchedules()->with('urlGroup')->get();
    }

    public function render(): View
    {
        return view('livewire.projects.components.schedule-modal', [
            'canCreate' => $this->canCreateSchedule(),
        ]);
    }
}
