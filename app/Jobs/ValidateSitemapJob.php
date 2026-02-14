<?php

namespace App\Jobs;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidateSitemapJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 120;

    /**
     * Common sitemap locations to check.
     *
     * @var array<string>
     */
    protected array $sitemapPaths = [
        '/sitemap.xml',
        '/sitemap_index.xml',
        '/sitemap-index.xml',
        '/sitemaps.xml',
        '/sitemap/',
    ];

    public function __construct(
        public Project $project,
        public ?string $baseUrl = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the base URL from the first project URL or provided URL
        $baseUrl = $this->baseUrl ?? $this->getProjectBaseUrl();

        if (! $baseUrl) {
            Log::warning('No base URL found for sitemap validation', [
                'project_id' => $this->project->id,
            ]);

            return;
        }

        $sitemapUrl = $this->findSitemap($baseUrl);

        if (! $sitemapUrl) {
            $this->recordValidation([
                'found' => false,
                'message' => 'No sitemap found at common locations',
                'checked_locations' => array_map(fn ($path) => $baseUrl.$path, $this->sitemapPaths),
            ]);

            return;
        }

        $this->validateSitemap($sitemapUrl);
    }

    /**
     * Get the base URL from project URLs.
     */
    protected function getProjectBaseUrl(): ?string
    {
        $firstUrl = $this->project->urls()->first();

        if (! $firstUrl) {
            return null;
        }

        $parsed = parse_url($firstUrl->url);

        return $parsed['scheme'].'://'.$parsed['host'];
    }

    /**
     * Find a valid sitemap URL.
     */
    protected function findSitemap(string $baseUrl): ?string
    {
        // First check robots.txt for sitemap declaration
        try {
            $robotsResponse = Http::timeout(10)->get($baseUrl.'/robots.txt');

            if ($robotsResponse->successful()) {
                preg_match_all('/Sitemap:\s*(.+)/i', $robotsResponse->body(), $matches);

                foreach ($matches[1] as $sitemapUrl) {
                    $sitemapUrl = trim($sitemapUrl);
                    if ($this->isSitemapAccessible($sitemapUrl)) {
                        return $sitemapUrl;
                    }
                }
            }
        } catch (\Exception $e) {
            // Continue to check common paths
        }

        // Check common sitemap locations
        foreach ($this->sitemapPaths as $path) {
            $url = $baseUrl.$path;
            if ($this->isSitemapAccessible($url)) {
                return $url;
            }
        }

        return null;
    }

    /**
     * Check if a sitemap URL is accessible.
     */
    protected function isSitemapAccessible(string $url): bool
    {
        try {
            $response = Http::timeout(10)->head($url);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate a sitemap.
     */
    protected function validateSitemap(string $sitemapUrl): void
    {
        try {
            $response = Http::timeout(30)->get($sitemapUrl);

            if (! $response->successful()) {
                $this->recordValidation([
                    'found' => true,
                    'url' => $sitemapUrl,
                    'valid' => false,
                    'errors' => ['HTTP status: '.$response->status()],
                ]);

                return;
            }

            $content = $response->body();
            $errors = [];
            $warnings = [];
            $urls = [];

            // Parse XML
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);

            if ($xml === false) {
                $xmlErrors = libxml_get_errors();
                foreach ($xmlErrors as $error) {
                    $errors[] = 'XML Error: '.$error->message;
                }
                libxml_clear_errors();

                $this->recordValidation([
                    'found' => true,
                    'url' => $sitemapUrl,
                    'valid' => false,
                    'errors' => $errors,
                ]);

                return;
            }

            // Check if it's a sitemap index
            $isSitemapIndex = $xml->getName() === 'sitemapindex' || isset($xml->sitemap);

            if ($isSitemapIndex) {
                // Process sitemap index
                $sitemaps = [];
                foreach ($xml->sitemap as $sitemap) {
                    $sitemaps[] = (string) $sitemap->loc;
                }

                $this->recordValidation([
                    'found' => true,
                    'url' => $sitemapUrl,
                    'valid' => empty($errors),
                    'type' => 'index',
                    'sitemap_count' => count($sitemaps),
                    'sitemaps' => $sitemaps,
                    'errors' => $errors ?: null,
                    'warnings' => $warnings ?: null,
                ]);
            } else {
                // Process regular sitemap
                foreach ($xml->url as $urlEntry) {
                    $loc = (string) $urlEntry->loc;

                    if (empty($loc)) {
                        $errors[] = 'Empty <loc> element found';

                        continue;
                    }

                    if (! filter_var($loc, FILTER_VALIDATE_URL)) {
                        $errors[] = 'Invalid URL: '.$loc;

                        continue;
                    }

                    $urls[] = [
                        'loc' => $loc,
                        'lastmod' => isset($urlEntry->lastmod) ? (string) $urlEntry->lastmod : null,
                        'changefreq' => isset($urlEntry->changefreq) ? (string) $urlEntry->changefreq : null,
                        'priority' => isset($urlEntry->priority) ? (string) $urlEntry->priority : null,
                    ];
                }

                // Validation checks
                if (count($urls) > 50000) {
                    $warnings[] = 'Sitemap exceeds 50,000 URLs limit';
                }

                if (strlen($content) > 52428800) { // 50MB
                    $warnings[] = 'Sitemap exceeds 50MB size limit';
                }

                // Check for URLs not in project
                $projectUrls = $this->project->urls()->pluck('url')->toArray();
                $missingFromProject = [];

                foreach ($urls as $urlData) {
                    if (! in_array($urlData['loc'], $projectUrls)) {
                        $missingFromProject[] = $urlData['loc'];
                    }
                }

                if (count($missingFromProject) > 0) {
                    $warnings[] = count($missingFromProject).' URLs in sitemap are not in project';
                }

                $this->recordValidation([
                    'found' => true,
                    'url' => $sitemapUrl,
                    'valid' => empty($errors),
                    'type' => 'urlset',
                    'url_count' => count($urls),
                    'missing_from_project' => array_slice($missingFromProject, 0, 10),
                    'errors' => $errors ?: null,
                    'warnings' => $warnings ?: null,
                ]);
            }
        } catch (\Exception $e) {
            $this->recordValidation([
                'found' => true,
                'url' => $sitemapUrl,
                'valid' => false,
                'errors' => ['Failed to validate: '.$e->getMessage()],
            ]);
        }
    }

    /**
     * Record the validation result.
     *
     * @param  array<string, mixed>  $data
     */
    protected function recordValidation(array $data): void
    {
        // Store in project metadata or a dedicated table
        $this->project->update([
            'sitemap_validation' => array_merge([
                'validated_at' => now()->toIso8601String(),
            ], $data),
        ]);

        Log::info('Sitemap validation completed', [
            'project_id' => $this->project->id,
            'found' => $data['found'] ?? false,
            'valid' => $data['valid'] ?? false,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Sitemap validation job failed', [
            'project_id' => $this->project->id,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<string>
     */
    public function tags(): array
    {
        return [
            'sitemap',
            'project:'.$this->project->id,
        ];
    }
}
