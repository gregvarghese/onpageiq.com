<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProjectList extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $user = Auth::user();

        $projects = Project::query()
            ->where('organization_id', $user->organization_id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->withCount('urls')
            ->latest()
            ->paginate(12);

        return view('livewire.projects.project-list', [
            'projects' => $projects,
        ]);
    }
}
