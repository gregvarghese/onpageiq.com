<?php

namespace App\Livewire\Projects\Components;

use App\Models\Project;
use App\Models\UrlGroup;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class UrlGroupManager extends Component
{
    public Project $project;

    public bool $showModal = false;

    public ?UrlGroup $editingGroup = null;

    #[Validate('required|string|max:50')]
    public string $name = '';

    #[Validate('required|string|regex:/^#[0-9A-Fa-f]{6}$/')]
    public string $color = '#6B7280';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Open the modal to create a new group.
     */
    public function openCreateModal(): void
    {
        $this->authorize('update', $this->project);

        if (! $this->canCreateGroup()) {
            $this->dispatch('notify', type: 'error', message: 'You have reached the maximum number of URL groups for your plan.');

            return;
        }

        $this->reset(['name', 'color', 'editingGroup']);
        $this->color = '#6B7280';
        $this->showModal = true;
    }

    /**
     * Open the modal to edit an existing group.
     */
    public function openEditModal(int $groupId): void
    {
        $this->authorize('update', $this->project);

        $this->editingGroup = $this->project->urlGroups()->findOrFail($groupId);
        $this->name = $this->editingGroup->name;
        $this->color = $this->editingGroup->color;
        $this->showModal = true;
    }

    /**
     * Save the group (create or update).
     */
    public function save(): void
    {
        $this->authorize('update', $this->project);
        $this->validate();

        if ($this->editingGroup) {
            $this->editingGroup->update([
                'name' => $this->name,
                'color' => $this->color,
            ]);
            $this->dispatch('notify', type: 'success', message: 'Group updated successfully.');
        } else {
            $maxSortOrder = $this->project->urlGroups()->max('sort_order') ?? 0;

            $this->project->urlGroups()->create([
                'name' => $this->name,
                'color' => $this->color,
                'sort_order' => $maxSortOrder + 1,
            ]);
            $this->dispatch('notify', type: 'success', message: 'Group created successfully.');
        }

        $this->closeModal();
        $this->dispatch('group-updated');
    }

    /**
     * Delete a group.
     */
    public function deleteGroup(int $groupId): void
    {
        $this->authorize('update', $this->project);

        $group = $this->project->urlGroups()->findOrFail($groupId);

        // Unassign all URLs from this group
        $group->urls()->update(['url_group_id' => null]);

        $group->delete();

        $this->dispatch('notify', type: 'success', message: 'Group deleted successfully.');
        $this->dispatch('group-updated');
    }

    /**
     * Assign a URL to a group.
     */
    #[On('assign-url-to-group')]
    public function assignUrlToGroup(int $urlId, ?int $groupId): void
    {
        $this->authorize('update', $this->project);

        $url = $this->project->urls()->findOrFail($urlId);
        $url->update(['url_group_id' => $groupId]);

        $this->dispatch('url-group-changed');
    }

    /**
     * Reorder groups.
     *
     * @param  array<int, int>  $order  Map of group_id => sort_order
     */
    public function reorderGroups(array $order): void
    {
        $this->authorize('update', $this->project);

        foreach ($order as $groupId => $sortOrder) {
            $this->project->urlGroups()
                ->where('id', $groupId)
                ->update(['sort_order' => $sortOrder]);
        }

        $this->dispatch('group-updated');
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['name', 'color', 'editingGroup']);
    }

    /**
     * Check if user can create more groups.
     */
    public function canCreateGroup(): bool
    {
        $organization = $this->project->organization;

        if (! $organization->canCreateUrlGroups()) {
            return false;
        }

        $limit = $organization->getUrlGroupsLimit();

        if ($limit === null) {
            return true;
        }

        return $this->project->urlGroups()->count() < $limit;
    }

    /**
     * Get the remaining group slots.
     */
    public function getRemainingSlots(): ?int
    {
        $limit = $this->project->organization->getUrlGroupsLimit();

        if ($limit === null) {
            return null;
        }

        return max(0, $limit - $this->project->urlGroups()->count());
    }

    public function render(): View
    {
        return view('livewire.projects.components.url-group-manager', [
            'groups' => $this->project->urlGroups()->withCount('urls')->get(),
            'canCreate' => $this->canCreateGroup(),
            'remainingSlots' => $this->getRemainingSlots(),
        ]);
    }
}
