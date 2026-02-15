<?php

namespace App\Livewire\SiteArchitecture;

use App\Jobs\CrawlArchitectureJob;
use App\Models\Project;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CrawlConfigModal extends Component
{
    public Project $project;

    public bool $isOpen = false;

    #[Validate('required|integer|min:1|max:10')]
    public int $maxDepth = 5;

    #[Validate('required|integer|min:10|max:10000')]
    public int $maxPages = 1000;

    #[Validate('required|integer|min:100|max:60000')]
    public int $timeout = 30000;

    #[Validate('nullable|string|max:1000')]
    public string $excludePatterns = '';

    #[Validate('nullable|string|max:1000')]
    public string $includePatterns = '';

    public bool $respectRobotsTxt = true;

    public bool $enableJsRendering = false;

    public bool $followExternalLinks = false;

    public bool $saveAsDefaults = false;

    #[On('open-crawl-config-modal')]
    public function open(): void
    {
        $this->loadProjectDefaults();
        $this->isOpen = true;
    }

    /**
     * Load the project's saved architecture config defaults.
     */
    protected function loadProjectDefaults(): void
    {
        $config = $this->project->getArchitectureConfigWithDefaults();

        $this->maxDepth = $config['max_depth'] ?? 5;
        $this->maxPages = $config['max_pages'] ?? 1000;
        $this->timeout = ($config['request_timeout'] ?? 30) * 1000; // Convert to ms
        $this->excludePatterns = implode("\n", $config['exclude_patterns'] ?? []);
        $this->includePatterns = implode("\n", $config['include_patterns'] ?? []);
        $this->respectRobotsTxt = $config['respect_robots_txt'] ?? true;
        $this->enableJsRendering = $config['javascript_rendering'] ?? false;
        $this->followExternalLinks = false; // Not stored in config
        $this->saveAsDefaults = false;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetErrorBag();
    }

    public function startCrawl(): void
    {
        $this->validate();

        $config = $this->buildConfig();

        // Save as defaults if requested
        if ($this->saveAsDefaults) {
            $this->saveProjectDefaults($config);
        }

        CrawlArchitectureJob::dispatch($this->project, $config);

        $this->dispatch('crawl-started');
        $this->close();
    }

    /**
     * Build the crawl configuration array.
     *
     * @return array<string, mixed>
     */
    protected function buildConfig(): array
    {
        return [
            'max_depth' => $this->maxDepth,
            'max_pages' => $this->maxPages,
            'request_timeout' => (int) ($this->timeout / 1000), // Convert to seconds
            'exclude_patterns' => array_filter(array_map('trim', explode("\n", $this->excludePatterns))),
            'include_patterns' => array_filter(array_map('trim', explode("\n", $this->includePatterns))),
            'respect_robots_txt' => $this->respectRobotsTxt,
            'javascript_rendering' => $this->enableJsRendering,
            'follow_external_links' => $this->followExternalLinks,
        ];
    }

    /**
     * Save the current configuration as project defaults.
     *
     * @param  array<string, mixed>  $config
     */
    protected function saveProjectDefaults(array $config): void
    {
        $this->project->update([
            'architecture_config' => [
                'max_depth' => $config['max_depth'],
                'max_pages' => $config['max_pages'],
                'request_timeout' => $config['request_timeout'],
                'exclude_patterns' => $config['exclude_patterns'],
                'include_patterns' => $config['include_patterns'],
                'respect_robots_txt' => $config['respect_robots_txt'],
                'javascript_rendering' => $config['javascript_rendering'],
            ],
        ]);
    }

    public function render(): View
    {
        return view('livewire.site-architecture.crawl-config-modal');
    }
}
