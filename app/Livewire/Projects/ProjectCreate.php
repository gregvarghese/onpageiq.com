<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class ProjectCreate extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public string $description = '';

    #[Validate('required|string|size:2')]
    public string $language = 'en';

    public bool $checkSpelling = true;

    public bool $checkGrammar = true;

    public bool $checkSeo = true;

    public bool $checkReadability = true;

    public function mount(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        // Set default checks based on subscription tier
        $defaultChecks = $organization->getDefaultChecks();
        $this->checkSpelling = in_array('spelling', $defaultChecks);
        $this->checkGrammar = in_array('grammar', $defaultChecks);
        $this->checkSeo = in_array('seo', $defaultChecks);
        $this->checkReadability = in_array('readability', $defaultChecks);
    }

    public function create(): void
    {
        $this->validate();

        $user = Auth::user();

        $project = Project::create([
            'organization_id' => $user->organization_id,
            'created_by_user_id' => $user->id,
            'name' => $this->name,
            'description' => $this->description ?: null,
            'language' => $this->language,
            'check_config' => [
                'spelling' => $this->checkSpelling,
                'grammar' => $this->checkGrammar,
                'seo' => $this->checkSeo,
                'readability' => $this->checkReadability,
            ],
        ]);

        session()->flash('success', 'Project created successfully!');

        $this->redirect(route('projects.show', $project), navigate: true);
    }

    public function render(): View
    {
        $user = Auth::user();
        $availableChecks = $user->organization->getAvailableChecks();

        return view('livewire.projects.project-create', [
            'availableChecks' => $availableChecks,
        ]);
    }
}
