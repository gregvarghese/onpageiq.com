<?php

namespace App\Livewire\Scans;

use App\Jobs\ScanUrlJob;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Url;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ScanCreate extends Component
{
    public ?int $selectedProjectId = null;

    public string $urls = '';

    public string $scanType = 'quick';

    public bool $checkSpelling = true;

    public bool $checkGrammar = true;

    public bool $checkSeo = false;

    public bool $checkReadability = false;

    public bool $showNewProjectModal = false;

    public string $newProjectName = '';

    public string $newProjectLanguage = 'en';

    /**
     * @var array<string, string>
     */
    public array $languages = [
        'en' => 'English',
        'en-GB' => 'English (UK)',
        'en-AU' => 'English (Australia)',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'nl' => 'Dutch',
    ];

    public function mount(?int $project = null): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        if ($project) {
            $this->selectedProjectId = $project;
        }

        // Set available checks based on tier
        $availableChecks = $organization->getAvailableChecks();
        $this->checkSpelling = true;
        $this->checkGrammar = $availableChecks['grammar'] ?? false;
        $this->checkSeo = false;
        $this->checkReadability = false;
    }

    public function updatedSelectedProjectId(): void
    {
        // When project changes, update checks based on project config
        if ($this->selectedProjectId) {
            $project = Project::find($this->selectedProjectId);
            if ($project) {
                $enabledChecks = $project->getEnabledChecks();
                $this->checkSpelling = in_array('spelling', $enabledChecks);
                $this->checkGrammar = in_array('grammar', $enabledChecks);
                $this->checkSeo = in_array('seo', $enabledChecks);
                $this->checkReadability = in_array('readability', $enabledChecks);
            }
        }
    }

    public function openNewProjectModal(): void
    {
        $this->reset(['newProjectName', 'newProjectLanguage']);
        $this->newProjectLanguage = 'en';
        $this->showNewProjectModal = true;
    }

    public function createProject(): void
    {
        $user = Auth::user();

        $this->validate([
            'newProjectName' => ['required', 'string', 'max:255'],
            'newProjectLanguage' => ['required', 'string'],
        ]);

        $project = Project::create([
            'organization_id' => $user->organization_id,
            'created_by_user_id' => $user->id,
            'name' => $this->newProjectName,
            'language' => $this->newProjectLanguage,
        ]);

        $this->selectedProjectId = $project->id;
        $this->showNewProjectModal = false;
        $this->reset(['newProjectName', 'newProjectLanguage']);
    }

    public function getEstimatedCreditsProperty(): int
    {
        $urlCount = $this->getUrlCount();
        $baseCredits = $urlCount;

        // Deep scans cost 3x
        if ($this->scanType === 'deep') {
            $baseCredits *= 3;
        }

        return $baseCredits;
    }

    /**
     * @return array<string>
     */
    public function getSelectedChecksProperty(): array
    {
        $checks = [];
        if ($this->checkSpelling) {
            $checks[] = 'spelling';
        }
        if ($this->checkGrammar) {
            $checks[] = 'grammar';
        }
        if ($this->checkSeo) {
            $checks[] = 'seo';
        }
        if ($this->checkReadability) {
            $checks[] = 'readability';
        }

        return $checks;
    }

    public function startScan(): void
    {
        $user = Auth::user();
        $organization = $user->organization;

        $this->validate([
            'selectedProjectId' => ['required', 'exists:projects,id'],
            'urls' => ['required', 'string'],
            'scanType' => ['required', Rule::in(['quick', 'deep'])],
        ]);

        // Parse URLs
        $urlList = $this->parseUrls($this->urls);

        if (empty($urlList)) {
            $this->addError('urls', 'Please enter at least one valid URL.');

            return;
        }

        // Check credits
        $estimatedCredits = $this->estimatedCredits;
        if (! $organization->hasCredits($estimatedCredits)) {
            $this->addError('urls', 'Insufficient credits. You need '.$estimatedCredits.' credits but only have '.$organization->credit_balance.'.');

            return;
        }

        $project = Project::find($this->selectedProjectId);

        // Create URLs and scans
        $lastScan = null;
        foreach ($urlList as $urlString) {
            // Find or create the URL
            $url = Url::firstOrCreate(
                [
                    'project_id' => $project->id,
                    'url' => $urlString,
                ],
                [
                    'status' => 'pending',
                ]
            );

            // Create the scan
            $scan = Scan::create([
                'url_id' => $url->id,
                'triggered_by_user_id' => $user->id,
                'scan_type' => $this->scanType,
                'status' => 'pending',
                'credits_charged' => $this->scanType === 'deep' ? 3 : 1,
            ]);

            // Deduct credits
            $organization->deductCredits($scan->credits_charged);

            // Dispatch the job
            ScanUrlJob::dispatch($scan);

            $lastScan = $scan;
        }

        // Redirect to the last scan or project
        if ($lastScan && count($urlList) === 1) {
            $this->redirect(route('scans.show', $lastScan), navigate: true);
        } else {
            $this->redirect(route('projects.show', $project), navigate: true);
        }
    }

    /**
     * @return array<string>
     */
    protected function parseUrls(string $input): array
    {
        $lines = preg_split('/[\r\n]+/', $input);
        $urls = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Add https:// if no protocol
            if (! preg_match('/^https?:\/\//i', $line)) {
                $line = 'https://'.$line;
            }

            // Validate URL
            if (filter_var($line, FILTER_VALIDATE_URL)) {
                $urls[] = $line;
            }
        }

        return array_unique($urls);
    }

    protected function getUrlCount(): int
    {
        if (empty($this->urls)) {
            return 0;
        }

        return count($this->parseUrls($this->urls));
    }

    public function render(): View
    {
        $user = Auth::user();
        $organization = $user->organization;

        $projects = Project::query()
            ->where('organization_id', $organization->id)
            ->orderBy('name')
            ->get();

        $recentScans = Scan::query()
            ->whereHas('url.project', function ($query) use ($organization) {
                $query->where('organization_id', $organization->id);
            })
            ->with('url.project')
            ->where('status', 'completed')
            ->latest()
            ->limit(5)
            ->get();

        return view('livewire.scans.scan-create', [
            'projects' => $projects,
            'organization' => $organization,
            'recentScans' => $recentScans,
            'urlCount' => $this->getUrlCount(),
        ]);
    }
}
