<?php

namespace App\Jobs;

use App\Models\BrokenLink;
use App\Models\Url;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckBrokenLinksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 60;

    public function __construct(
        public Url $url,
        public ?int $scanId = null,
        public ?string $pageContent = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Clear previous broken links for this URL
        $this->url->brokenLinks()->delete();

        // Extract links from page content or fetch the page
        $links = $this->extractLinks();

        if (empty($links)) {
            Log::info('No links found to check', ['url_id' => $this->url->id]);

            return;
        }

        // Check each link
        foreach ($links as $link) {
            $this->checkLink($link);
        }

        Log::info('Broken link check completed', [
            'url_id' => $this->url->id,
            'links_checked' => count($links),
            'broken_found' => $this->url->brokenLinks()->count(),
        ]);
    }

    /**
     * Extract links from page content.
     *
     * @return array<array{url: string, type: string, anchor_text: ?string}>
     */
    protected function extractLinks(): array
    {
        $content = $this->pageContent;

        if (! $content) {
            try {
                $response = Http::timeout(30)->get($this->url->url);
                $content = $response->body();
            } catch (\Exception $e) {
                Log::warning('Failed to fetch page for link extraction', [
                    'url_id' => $this->url->id,
                    'error' => $e->getMessage(),
                ]);

                return [];
            }
        }

        $links = [];
        $baseUrl = $this->url->url;
        $baseParts = parse_url($baseUrl);
        $baseHost = $baseParts['scheme'].'://'.$baseParts['host'];

        // Extract anchor links
        preg_match_all('/<a[^>]+href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/si', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $href = $match[1];
            $anchorText = strip_tags($match[2]);

            // Skip javascript, mailto, tel, and anchor links
            if (preg_match('/^(javascript:|mailto:|tel:|#)/', $href)) {
                continue;
            }

            // Resolve relative URLs
            $absoluteUrl = $this->resolveUrl($href, $baseHost, $baseParts['path'] ?? '/');

            if ($absoluteUrl) {
                $links[] = [
                    'url' => $absoluteUrl,
                    'type' => 'anchor',
                    'anchor_text' => $anchorText ?: null,
                ];
            }
        }

        // Extract image sources
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $imgMatches);
        foreach ($imgMatches[1] as $src) {
            if (preg_match('/^data:/', $src)) {
                continue; // Skip data URIs
            }

            $absoluteUrl = $this->resolveUrl($src, $baseHost, $baseParts['path'] ?? '/');
            if ($absoluteUrl) {
                $links[] = [
                    'url' => $absoluteUrl,
                    'type' => 'image',
                    'anchor_text' => null,
                ];
            }
        }

        // Extract script sources
        preg_match_all('/<script[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $scriptMatches);
        foreach ($scriptMatches[1] as $src) {
            $absoluteUrl = $this->resolveUrl($src, $baseHost, $baseParts['path'] ?? '/');
            if ($absoluteUrl) {
                $links[] = [
                    'url' => $absoluteUrl,
                    'type' => 'script',
                    'anchor_text' => null,
                ];
            }
        }

        // Extract stylesheet links
        preg_match_all('/<link[^>]+href=["\']([^"\']+)["\'][^>]*>/i', $content, $cssMatches);
        foreach ($cssMatches[1] as $href) {
            $absoluteUrl = $this->resolveUrl($href, $baseHost, $baseParts['path'] ?? '/');
            if ($absoluteUrl) {
                $links[] = [
                    'url' => $absoluteUrl,
                    'type' => 'stylesheet',
                    'anchor_text' => null,
                ];
            }
        }

        // Remove duplicates
        return collect($links)
            ->unique('url')
            ->values()
            ->toArray();
    }

    /**
     * Resolve a URL to an absolute URL.
     */
    protected function resolveUrl(string $url, string $baseHost, string $basePath): ?string
    {
        // Already absolute
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        // Protocol-relative
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        // Root-relative
        if (str_starts_with($url, '/')) {
            return $baseHost.$url;
        }

        // Relative to current path
        $pathDir = dirname($basePath);

        return $baseHost.$pathDir.'/'.$url;
    }

    /**
     * Check if a link is broken.
     *
     * @param  array{url: string, type: string, anchor_text: ?string}  $link
     */
    protected function checkLink(array $link): void
    {
        try {
            $response = Http::timeout(15)
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 5,
                        'track_redirects' => true,
                    ],
                    'verify' => false, // Some sites have SSL issues
                ])
                ->head($link['url']);

            $statusCode = $response->status();

            // Consider 4xx and 5xx as broken (except 405 Method Not Allowed)
            if ($statusCode >= 400 && $statusCode !== 405) {
                $this->recordBrokenLink($link, $statusCode, 'broken');
            } elseif ($statusCode === 405) {
                // Try GET request if HEAD is not allowed
                $getResponse = Http::timeout(15)->get($link['url']);
                if ($getResponse->status() >= 400) {
                    $this->recordBrokenLink($link, $getResponse->status(), 'broken');
                }
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->recordBrokenLink($link, null, 'unreachable', $e->getMessage());
        } catch (\Exception $e) {
            $this->recordBrokenLink($link, null, 'error', $e->getMessage());
        }
    }

    /**
     * Record a broken link.
     *
     * @param  array{url: string, type: string, anchor_text: ?string}  $link
     */
    protected function recordBrokenLink(array $link, ?int $statusCode, string $status, ?string $errorMessage = null): void
    {
        BrokenLink::create([
            'url_id' => $this->url->id,
            'scan_id' => $this->scanId,
            'link_url' => $link['url'],
            'link_type' => $link['type'],
            'anchor_text' => $link['anchor_text'],
            'status_code' => $statusCode,
            'status' => $status,
            'error_message' => $errorMessage,
            'checked_at' => now(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Broken link check job failed', [
            'url_id' => $this->url->id,
            'url' => $this->url->url,
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
            'broken-links',
            'url:'.$this->url->id,
            'project:'.$this->url->project_id,
        ];
    }
}
