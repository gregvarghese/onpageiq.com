<?php

namespace App\Livewire\Team;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TeamDepartments extends Component
{
    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteModal = false;

    public bool $showMembersModal = false;

    public ?int $departmentToEdit = null;

    public ?int $departmentToDelete = null;

    public ?int $departmentToManage = null;

    public string $name = '';

    public ?int $creditBudget = null;

    /**
     * @var array<int>
     */
    public array $selectedMembers = [];

    public function openCreateModal(): void
    {
        $this->reset(['name', 'creditBudget']);
        $this->showCreateModal = true;
    }

    public function createDepartment(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'creditBudget' => ['nullable', 'integer', 'min:0'],
        ]);

        Department::create([
            'organization_id' => $organization->id,
            'name' => $this->name,
            'credit_budget' => $this->creditBudget ?? 0,
            'credit_used' => 0,
        ]);

        $this->showCreateModal = false;
        $this->reset(['name', 'creditBudget']);

        session()->flash('success', 'Department created successfully.');
    }

    public function openEditModal(int $departmentId): void
    {
        $user = Auth::user();

        $department = Department::where('id', $departmentId)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $department) {
            return;
        }

        $this->departmentToEdit = $departmentId;
        $this->name = $department->name;
        $this->creditBudget = $department->credit_budget;
        $this->showEditModal = true;
    }

    public function updateDepartment(): void
    {
        $user = Auth::user();

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'creditBudget' => ['nullable', 'integer', 'min:0'],
        ]);

        $department = Department::where('id', $this->departmentToEdit)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $department) {
            return;
        }

        $department->update([
            'name' => $this->name,
            'credit_budget' => $this->creditBudget ?? 0,
        ]);

        $this->showEditModal = false;
        $this->reset(['departmentToEdit', 'name', 'creditBudget']);

        session()->flash('success', 'Department updated successfully.');
    }

    public function confirmDelete(int $departmentId): void
    {
        $this->departmentToDelete = $departmentId;
        $this->showDeleteModal = true;
    }

    public function deleteDepartment(): void
    {
        $user = Auth::user();

        $department = Department::where('id', $this->departmentToDelete)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $department) {
            $this->showDeleteModal = false;

            return;
        }

        // Remove department assignment from users
        User::where('department_id', $department->id)->update(['department_id' => null]);

        $department->delete();

        $this->showDeleteModal = false;
        $this->departmentToDelete = null;

        session()->flash('success', 'Department deleted successfully.');
    }

    public function openMembersModal(int $departmentId): void
    {
        $user = Auth::user();

        $department = Department::where('id', $departmentId)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $department) {
            return;
        }

        $this->departmentToManage = $departmentId;
        $this->selectedMembers = $department->users()->pluck('id')->toArray();
        $this->showMembersModal = true;
    }

    public function updateMembers(): void
    {
        $user = Auth::user();

        $department = Department::where('id', $this->departmentToManage)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (! $department) {
            return;
        }

        // Remove all users from this department
        User::where('department_id', $department->id)->update(['department_id' => null]);

        // Add selected users to this department
        User::whereIn('id', $this->selectedMembers)
            ->where('organization_id', $user->organization_id)
            ->update(['department_id' => $department->id]);

        $this->showMembersModal = false;
        $this->reset(['departmentToManage', 'selectedMembers']);

        session()->flash('success', 'Department members updated successfully.');
    }

    public function render(): View
    {
        $user = Auth::user();
        $organization = $user->organization;

        $departments = Department::query()
            ->where('organization_id', $organization->id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $allMembers = User::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        return view('livewire.team.team-departments', [
            'departments' => $departments,
            'organization' => $organization,
            'allMembers' => $allMembers,
        ]);
    }
}
