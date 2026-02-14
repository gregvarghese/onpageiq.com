<?php

namespace App\Livewire\Projects\Components;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class BulkImportModal extends Component
{
    public Project $project;

    public bool $showModal = false;

    public string $importMethod = 'paste';

    #[Validate('nullable|string')]
    public string $urlsText = '';

    #[Validate('nullable|url')]
    public string $sitemapUrl = '';

    /**
     * URLs discovered from sitemap or pasted.
     *
     * @var array<int, array{url: string, selected: bool}>
     */
    public array $discoveredUrls = [];

    public bool $isLoading = false;

    public string $errorMessage = '';

    public function mount(Project $project): void
    {
        $this->project = $project;
    }

    /**
     * Open the import modal.
     */
    public function openModal(): void
    {
        $this->authorize('update', $this->project);
        $this->reset(['urlsText', 'sitemapUrl', 'discoveredUrls', 'errorMessage', 'importMethod']);
        $this->showModal = true;
    }

    /**
     * Close the modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['urlsText', 'sitemapUrl', 'discoveredUrls', 'errorMessage', 'isLoading']);
    }

    /**
     * Parse pasted URLs.
     */
    public function parseUrls(): void
    {
        $this->errorMessage = '';
        $this->discoveredUrls = [];

        $lines = preg_split('/[\r\n]+/', trim($this->urlsText));

        if (empty($lines) || (count($lines) === 1 && empty($lines[0]))) {
            $this->errorMessage = 'Please enter at least one URL.';

            return;
        }

        $existingUrls = $this->project->urls()->pluck('url')->toArray();

        foreach ($lines as $line) {
            $url = trim($line);

            if (empty($url)) {
                continue;
            }

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $isExisting = in_array($url, $existingUrls);

            $this->discoveredUrls[] = [
                'url' => $url,
                'selected' => ! $isExisting,
                'existing' => $isExisting,
            ];
        }

        if (empty($this->discoveredUrls)) {
            $this->errorMessage = 'No valid URLs found. Please check the format.';
        }
    }

    /**
     * Fetch and parse a sitemap.
     */
    public function fetchSitemap(): void
    {
        $this->validate(['sitemapUrl' => 'required|url']);

        $this->errorMessage = '';
        $this->discoveredUrls = [];
        $this->isLoading = true;

        try {
            $response = Http::timeout(30)->get($this->sitemapUrl);

            if (! $response->successful()) {
                $this->errorMessage = 'Failed to fetch sitemap. Status: '.$response->status();
                $this->isLoading = false;

                return;
            }

            $content = $response->body();
            $urls = $this->parseSitemapXml($content);

            if (empty($urls)) {
                $this->errorMessage = 'No URLs found in the sitemap.';
                $this->isLoading = false;

                return;
            }

            $existingUrls = $this->project->urls()->pluck('url')->toArray();

            foreach ($urls as $url) {
                $isExisting = in_array($url, $existingUrls);

                $this->discoveredUrls[] = [
                    'url' => $url,
                    'selected' => ! $isExisting,
                    'existing' => $isExisting,
                ];
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error fetching sitemap: '.$e->getMessage();
        }

        $this->isLoading = false;
    }

    /**
     * Parse sitemap XML and extract URLs.
     *
     * @return array<string>
     */
    protected function parseSitemapXml(string $content): array
    {
        $urls = [];

        try {
            $xml = simplexml_load_string($content);

            if ($xml === false) {
                return [];
            }

            // Handle sitemap index
            if ($xml->getName() === 'sitemapindex') {
                // For simplicity, just get the first sitemap
                // In production, you might want to recursively fetch all
                return [];
            }

            // Handle regular sitemap
            foreach ($xml->url as $urlElement) {
                $loc = (string) $urlElement->loc;
                if (! empty($loc) && filter_var($loc, FILTER_VALIDATE_URL)) {
                    $urls[] = $loc;
                }
            }
        } catch (\Exception $e) {
            // Silently fail on XML parsing errors
        }

        return array_unique($urls);
    }

    /**
     * Toggle selection of a URL.
     */
    public function toggleUrl(int $index): void
    {
        if (isset($this->discoveredUrls[$index]) && ! $this->discoveredUrls[$index]['existing']) {
            $this->discoveredUrls[$index]['selected'] = ! $this->discoveredUrls[$index]['selected'];
        }
    }

    /**
     * Select all URLs.
     */
    public function selectAll(): void
    {
        foreach ($this->discoveredUrls as $index => $url) {
            if (! $url['existing']) {
                $this->discoveredUrls[$index]['selected'] = true;
            }
        }
    }

    /**
     * Deselect all URLs.
     */
    public function deselectAll(): void
    {
        foreach ($this->discoveredUrls as $index => $url) {
            $this->discoveredUrls[$index]['selected'] = false;
        }
    }

    /**
     * Import selected URLs.
     */
    public function importSelected(): void
    {
        $this->authorize('update', $this->project);

        $selectedUrls = array_filter($this->discoveredUrls, fn ($url) => $url['selected'] && ! $url['existing']);

        if (empty($selectedUrls)) {
            $this->errorMessage = 'No URLs selected for import.';

            return;
        }

        $imported = 0;

        foreach ($selectedUrls as $urlData) {
            $this->project->urls()->create([
                'url' => $urlData['url'],
                'status' => 'pending',
            ]);
            $imported++;
        }

        $this->dispatch('notify', type: 'success', message: "{$imported} URLs imported successfully.");
        $this->dispatch('urls-imported');
        $this->closeModal();
    }

    /**
     * Get count of selected URLs.
     */
    public function getSelectedCount(): int
    {
        return count(array_filter($this->discoveredUrls, fn ($url) => $url['selected'] && ! $url['existing']));
    }

    public function render(): View
    {
        return view('livewire.projects.components.bulk-import-modal', [
            'selectedCount' => $this->getSelectedCount(),
        ]);
    }
}
