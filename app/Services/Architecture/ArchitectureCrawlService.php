<?php

namespace App\Services\Architecture;

use App\Enums\ArchitectureStatus;
use App\Enums\LinkType;
use App\Enums\NodeStatus;
use App\Events\ArchitectureCrawlProgress;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\ArchitectureSnapshot;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\Url;
use App\Services\Browser\BrowserServiceManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ArchitectureCrawlService
{
    /**
     * @var array<string, bool>
     */
    protected array $visitedUrls = [];

    /**
     * @var array<string, ArchitectureNode>
     */
    protected array $nodesByUrl = [];

    protected SiteArchitecture $architecture;

    protected array $crawlConfig;

    protected Project $project;

    /**
     * @var array<string, Url>
     */
    protected array $projectUrlsByUrl = [];

    protected int $crawledCount = 0;

    protected int $lastProgressBroadcast = 0;

    protected ?string $baseHost = null;

    public function __construct(
        protected BrowserServiceManager $browserManager,
        protected LinkClassificationService $linkClassifier,
        protected SpaDetectionService $spaDetector,
        protected RobotsTxtService $robotsTxtService
    ) {}

    /**
     * Start or resume a crawl for a project.
     */
    public function crawl(Project $project, array $config = []): SiteArchitecture
    {
        $this->crawlConfig = array_merge($this->getDefaultConfig(), $config);
        $this->project = $project;

        // Preload existing URLs with scan data for reuse
        if ($this->crawlConfig['use_existing_scans']) {
            $this->preloadExistingScans($project);
        }

        // Get or create site architecture
        $this->architecture = $project->siteArchitectures()
            ->whereIn('status', [ArchitectureStatus::Pending, ArchitectureStatus::Failed])
            ->first() ?? $this->createArchitecture($project);

        $this->architecture->markAsCrawling();

        try {
            // Get starting URL
            $startUrl = $this->getStartUrl($project);

            if (! $startUrl) {
                throw new \RuntimeException('No starting URL available for crawl');
            }

            // Store base host for robots.txt checks
            $this->baseHost = parse_url($startUrl, PHP_URL_HOST);

            // Detect SPA if auto-detect enabled
            if ($this->crawlConfig['auto_detect_spa']) {
                $this->detectAndConfigureSpa($startUrl);
            }

            // Pre-fetch robots.txt if we're respecting it
            if ($this->crawlConfig['respect_robots_txt']) {
                $this->robotsTxtService->getRulesForDomain($startUrl);
            }

            // Crawl starting from homepage
            $this->crawlUrl($startUrl, 0);

            // Process the queue
            $this->processQueue();

            // Analyze and create issues
            $this->architecture->markAsAnalyzing();
            $this->analyzeArchitecture();

            // Create snapshot
            $previousSnapshot = $this->architecture->latestSnapshot();
            ArchitectureSnapshot::createFromArchitecture($this->architecture, $previousSnapshot);

            // Update stats and mark ready
            $this->architecture->updateStats();
            $this->architecture->markAsReady();

        } catch (\Throwable $e) {
            Log::error('Architecture crawl failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            $this->architecture->markAsFailed();
            throw $e;
        }

        return $this->architecture->fresh();
    }

    /**
     * Crawl a single URL.
     */
    protected function crawlUrl(string $url, int $depth): ?ArchitectureNode
    {
        // Normalize URL
        $normalizedUrl = $this->normalizeUrl($url);

        // Skip if already visited or exceeds max depth
        if (isset($this->visitedUrls[$normalizedUrl])) {
            return $this->nodesByUrl[$normalizedUrl] ?? null;
        }

        if ($depth > $this->crawlConfig['max_depth']) {
            return null;
        }

        // Check URL patterns
        if (! $this->shouldCrawl($normalizedUrl)) {
            return null;
        }

        $this->visitedUrls[$normalizedUrl] = true;

        // Check max pages limit
        if (count($this->visitedUrls) > $this->crawlConfig['max_pages']) {
            return null;
        }

        try {
            // Fetch page content
            $response = $this->fetchPage($normalizedUrl);

            // Create node
            $node = $this->createNode($normalizedUrl, $response, $depth);
            $this->nodesByUrl[$normalizedUrl] = $node;

            // Track progress
            $this->crawledCount++;

            // Broadcast progress (throttled to every 5 pages or 2 seconds)
            $this->broadcastProgress($normalizedUrl);

            // If page is OK, extract and process links
            if ($response['status'] === 200 && ! empty($response['html'])) {
                $this->processLinks($node, $response['html'], $normalizedUrl, $depth);
            }

            return $node;

        } catch (\Throwable $e) {
            Log::warning('Failed to crawl URL', [
                'url' => $normalizedUrl,
                'error' => $e->getMessage(),
            ]);

            // Create error node
            return $this->createErrorNode($normalizedUrl, $depth, $e->getMessage());
        }
    }

    /**
     * Process crawl queue (breadth-first).
     */
    protected function processQueue(): void
    {
        // Queue is implicit in visitedUrls - we process links as we find them
        // This is already handled in crawlUrl via processLinks
    }

    /**
     * Fetch a page's content.
     *
     * @return array{status: int, html: string|null, title: string|null, redirect_url: string|null, from_cache: bool}
     */
    protected function fetchPage(string $url): array
    {
        // Try to get from existing scan data first
        if ($this->crawlConfig['use_existing_scans']) {
            $existingData = $this->getExistingScanData($url);

            if ($existingData) {
                Log::debug('Using existing scan data for URL', ['url' => $url]);

                return $existingData;
            }
        }

        $browserService = $this->browserManager->getService();
        $useJsRendering = $this->crawlConfig['javascript_rendering'];

        try {
            $content = $browserService->getPageContent(
                $url,
                $useJsRendering ? ['javascript' => true] : []
            );

            return [
                'status' => $content->statusCode ?? 200,
                'html' => $content->html,
                'title' => $this->extractTitle($content->html),
                'redirect_url' => null,
                'from_cache' => false,
            ];

        } catch (\Throwable $e) {
            // Try to get status code from exception message or default to timeout
            return [
                'status' => $this->parseStatusFromError($e),
                'html' => null,
                'title' => null,
                'redirect_url' => null,
                'from_cache' => false,
            ];
        }
    }

    /**
     * Preload existing URLs with scan data for reuse.
     */
    protected function preloadExistingScans(Project $project): void
    {
        $maxAge = Carbon::now()->subHours($this->crawlConfig['scan_data_max_age_hours']);

        $urls = $project->urls()
            ->with(['latestScan.result'])
            ->whereHas('latestScan', function ($query) use ($maxAge) {
                $query->where('status', 'completed')
                    ->where('completed_at', '>=', $maxAge);
            })
            ->get();

        foreach ($urls as $url) {
            $normalizedUrl = $this->normalizeUrl($url->url);
            $this->projectUrlsByUrl[$normalizedUrl] = $url;
        }

        Log::info('Preloaded existing scan data', [
            'project_id' => $project->id,
            'urls_count' => count($this->projectUrlsByUrl),
        ]);
    }

    /**
     * Get existing scan data for a URL if available and recent.
     *
     * @return array{status: int, html: string|null, title: string|null, redirect_url: string|null, from_cache: bool}|null
     */
    protected function getExistingScanData(string $url): ?array
    {
        $normalizedUrl = $this->normalizeUrl($url);

        if (! isset($this->projectUrlsByUrl[$normalizedUrl])) {
            return null;
        }

        $projectUrl = $this->projectUrlsByUrl[$normalizedUrl];
        $scan = $projectUrl->latestScan;

        if (! $scan || ! $scan->result) {
            return null;
        }

        $result = $scan->result;

        // Get HTML from content_snapshot
        $html = $result->content_snapshot;

        if (! $html) {
            return null;
        }

        // Get title from metadata or extract from HTML
        $title = $result->metadata['title'] ?? $this->extractTitle($html);

        return [
            'status' => $scan->http_status ?? 200,
            'html' => $html,
            'title' => $title,
            'redirect_url' => null,
            'from_cache' => true,
        ];
    }

    /**
     * Create an architecture node from response.
     */
    protected function createNode(string $url, array $response, int $depth): ArchitectureNode
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';

        $status = NodeStatus::fromHttpStatus($response['status']);

        return ArchitectureNode::create([
            'site_architecture_id' => $this->architecture->id,
            'url' => $url,
            'path' => $path,
            'title' => $response['title'],
            'status' => $status,
            'http_status' => $response['status'],
            'depth' => $depth,
            'inbound_count' => 0,
            'outbound_count' => 0,
            'link_equity_score' => $depth === 0 ? 1.0 : 0.0,
            'word_count' => $response['html'] ? $this->countWords($response['html']) : 0,
            'issues_count' => 0,
            'is_orphan' => $depth > 0,
            'is_deep' => $depth > $this->crawlConfig['deep_page_threshold'],
            'metadata' => [
                'crawled_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Create an error node.
     */
    protected function createErrorNode(string $url, int $depth, string $error): ArchitectureNode
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';

        return ArchitectureNode::create([
            'site_architecture_id' => $this->architecture->id,
            'url' => $url,
            'path' => $path,
            'title' => null,
            'status' => NodeStatus::Timeout,
            'http_status' => null,
            'depth' => $depth,
            'inbound_count' => 0,
            'outbound_count' => 0,
            'link_equity_score' => 0.0,
            'word_count' => 0,
            'issues_count' => 1,
            'is_orphan' => true,
            'is_deep' => false,
            'metadata' => [
                'error' => $error,
                'crawled_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Process links found on a page.
     */
    protected function processLinks(ArchitectureNode $sourceNode, string $html, string $baseUrl, int $currentDepth): void
    {
        $links = $this->linkClassifier->classifyLinksInHtml($html, $baseUrl);
        $outboundCount = 0;

        foreach ($links as $linkData) {
            $targetUrl = $linkData['href'];
            $isExternal = $this->isExternalUrl($targetUrl, $baseUrl);

            if ($isExternal) {
                // Create external link record without crawling
                $this->createExternalLink($sourceNode, $linkData);
                $outboundCount++;

                continue;
            }

            // Check if target node exists
            $normalizedTarget = $this->normalizeUrl($targetUrl);
            $targetNode = $this->nodesByUrl[$normalizedTarget] ?? null;

            if (! $targetNode && ! isset($this->visitedUrls[$normalizedTarget])) {
                // Crawl the target URL
                $targetNode = $this->crawlUrl($targetUrl, $currentDepth + 1);
            } elseif (! $targetNode && isset($this->visitedUrls[$normalizedTarget])) {
                $targetNode = $this->nodesByUrl[$normalizedTarget] ?? null;
            }

            if ($targetNode) {
                // Create internal link
                $this->createInternalLink($sourceNode, $targetNode, $linkData);

                // Update inbound count and orphan status
                $targetNode->increment('inbound_count');
                if ($targetNode->is_orphan) {
                    $targetNode->update(['is_orphan' => false]);
                }

                $outboundCount++;
            }
        }

        // Update source node outbound count
        $sourceNode->update(['outbound_count' => $outboundCount]);
    }

    /**
     * Create an internal link record.
     */
    protected function createInternalLink(ArchitectureNode $source, ArchitectureNode $target, array $linkData): ArchitectureLink
    {
        return ArchitectureLink::create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $source->id,
            'target_node_id' => $target->id,
            'target_url' => $target->url,
            'link_type' => $linkData['type'],
            'anchor_text' => Str::limit($linkData['anchor_text'], 255),
            'is_external' => false,
            'external_domain' => null,
            'is_nofollow' => $linkData['is_nofollow'],
            'position_in_page' => $linkData['position'],
            'created_at' => now(),
        ]);
    }

    /**
     * Create an external link record.
     */
    protected function createExternalLink(ArchitectureNode $source, array $linkData): ArchitectureLink
    {
        $parsed = parse_url($linkData['href']);
        $domain = $parsed['host'] ?? null;

        return ArchitectureLink::create([
            'site_architecture_id' => $this->architecture->id,
            'source_node_id' => $source->id,
            'target_node_id' => null,
            'target_url' => $linkData['href'],
            'link_type' => LinkType::External,
            'anchor_text' => Str::limit($linkData['anchor_text'], 255),
            'is_external' => true,
            'external_domain' => $domain,
            'is_nofollow' => $linkData['is_nofollow'],
            'position_in_page' => $linkData['position'],
            'created_at' => now(),
        ]);
    }

    /**
     * Analyze architecture and create issues.
     */
    protected function analyzeArchitecture(): void
    {
        // Find orphan pages
        $orphanNodes = $this->architecture->nodes()
            ->where('is_orphan', true)
            ->where('depth', '>', 0)
            ->get();

        foreach ($orphanNodes as $node) {
            ArchitectureIssue::createOrphanIssue($node);
            $node->increment('issues_count');
        }

        // Find deep pages
        $deepNodes = $this->architecture->nodes()
            ->where('depth', '>', $this->crawlConfig['deep_page_threshold'])
            ->get();

        foreach ($deepNodes as $node) {
            ArchitectureIssue::createDeepPageIssue($node);
            $node->increment('issues_count');
        }

        // Find broken links
        $brokenLinks = $this->architecture->links()
            ->where('is_external', false)
            ->whereNull('target_node_id')
            ->get();

        foreach ($brokenLinks as $link) {
            $sourceNode = $link->sourceNode;
            if ($sourceNode) {
                ArchitectureIssue::createBrokenLinkIssue($sourceNode, $link->target_url);
                $sourceNode->increment('issues_count');
            }
        }
    }

    /**
     * Detect SPA and configure rendering.
     */
    protected function detectAndConfigureSpa(string $url): void
    {
        try {
            $response = $this->fetchPage($url);

            if ($response['html']) {
                $detection = $this->spaDetector->detect($response['html']);

                if ($detection['requires_js_rendering']) {
                    $this->crawlConfig['javascript_rendering'] = true;

                    Log::info('SPA detected, enabling JavaScript rendering', [
                        'url' => $url,
                        'frameworks' => $detection['frameworks'],
                        'confidence' => $detection['confidence'],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('SPA detection failed', ['url' => $url, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Create a new site architecture record.
     */
    protected function createArchitecture(Project $project): SiteArchitecture
    {
        return SiteArchitecture::create([
            'project_id' => $project->id,
            'status' => ArchitectureStatus::Pending,
            'total_nodes' => 0,
            'total_links' => 0,
            'max_depth' => 0,
            'orphan_count' => 0,
            'error_count' => 0,
            'crawl_config' => $this->crawlConfig,
        ]);
    }

    /**
     * Get the starting URL for crawl.
     */
    protected function getStartUrl(Project $project): ?string
    {
        // Try to get from project URLs
        $url = $project->urls()->first();

        if ($url) {
            $parsed = parse_url($url->url);

            return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? '');
        }

        return null;
    }

    /**
     * Get default crawl configuration.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'max_pages' => 500,
            'max_depth' => 10,
            'deep_page_threshold' => 4,
            'respect_robots_txt' => true,
            'javascript_rendering' => false,
            'auto_detect_spa' => true,
            'use_existing_scans' => true,
            'scan_data_max_age_hours' => 24,
            'include_patterns' => [],
            'exclude_patterns' => [
                '*logout*',
                '*login*',
                '*register*',
                '*wp-admin*',
                '*wp-json*',
                '*.pdf',
                '*.jpg',
                '*.png',
                '*.gif',
                '*.css',
                '*.js',
            ],
            'request_timeout' => 30,
            'delay_between_requests' => 100, // ms
        ];
    }

    /**
     * Check if URL should be crawled.
     */
    protected function shouldCrawl(string $url): bool
    {
        // Check exclude patterns
        foreach ($this->crawlConfig['exclude_patterns'] as $pattern) {
            if (fnmatch($pattern, $url)) {
                return false;
            }
        }

        // Check include patterns (if any)
        if (! empty($this->crawlConfig['include_patterns'])) {
            $matchesInclude = false;
            foreach ($this->crawlConfig['include_patterns'] as $pattern) {
                if (fnmatch($pattern, $url)) {
                    $matchesInclude = true;
                    break;
                }
            }

            if (! $matchesInclude) {
                return false;
            }
        }

        // Check robots.txt if enabled
        if ($this->crawlConfig['respect_robots_txt']) {
            if (! $this->robotsTxtService->isAllowed($url)) {
                Log::debug('URL disallowed by robots.txt', ['url' => $url]);

                return false;
            }
        }

        return true;
    }

    /**
     * Normalize URL for comparison.
     */
    protected function normalizeUrl(string $url): string
    {
        // Remove fragment
        $url = preg_replace('/#.*$/', '', $url);

        // Remove trailing slash
        $url = rtrim($url, '/');

        // Lowercase
        return strtolower($url);
    }

    /**
     * Check if URL is external.
     */
    protected function isExternalUrl(string $url, string $baseUrl): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);

        if (! $urlHost || ! $baseHost) {
            return false;
        }

        // Remove www prefix for comparison
        $urlHost = preg_replace('/^www\./', '', strtolower($urlHost));
        $baseHost = preg_replace('/^www\./', '', strtolower($baseHost));

        return $urlHost !== $baseHost;
    }

    /**
     * Extract title from HTML.
     */
    protected function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES, 'UTF-8');
        }

        return null;
    }

    /**
     * Count words in HTML content.
     */
    protected function countWords(string $html): int
    {
        // Remove scripts and styles
        $html = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $html);

        // Strip tags and count words
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        return str_word_count($text);
    }

    /**
     * Broadcast crawl progress (throttled).
     */
    protected function broadcastProgress(string $currentUrl): void
    {
        // Throttle broadcasts to every 5 pages
        if ($this->crawledCount - $this->lastProgressBroadcast < 5) {
            return;
        }

        $this->lastProgressBroadcast = $this->crawledCount;

        try {
            ArchitectureCrawlProgress::dispatch(
                $this->architecture,
                $this->crawledCount,
                count($this->visitedUrls),
                $currentUrl
            );
        } catch (\Throwable $e) {
            // Don't let broadcast failures stop the crawl
            Log::warning('Failed to broadcast crawl progress', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse HTTP status from error.
     */
    protected function parseStatusFromError(\Throwable $e): int
    {
        $message = $e->getMessage();

        if (preg_match('/(\d{3})/', $message, $matches)) {
            $code = (int) $matches[1];
            if ($code >= 400 && $code < 600) {
                return $code;
            }
        }

        return 0; // Unknown/timeout
    }
}
