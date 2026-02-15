<?php

namespace App\Services\Architecture;

use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class SitemapValidationService
{
    public function __construct(
        protected SitemapGeneratorService $generatorService
    ) {}

    /**
     * Validate sitemap against architecture.
     */
    public function validate(SiteArchitecture $architecture, string $sitemapXml): array
    {
        $parsed = $this->generatorService->parseSitemap($sitemapXml);

        if ($parsed['type'] === 'error') {
            return [
                'valid' => false,
                'error' => $parsed['error'],
                'issues' => [],
            ];
        }

        if ($parsed['type'] === 'index') {
            return [
                'valid' => true,
                'type' => 'index',
                'sitemaps_count' => count($parsed['sitemaps']),
                'issues' => [],
                'note' => 'Sitemap index detected. Individual sitemaps need separate validation.',
            ];
        }

        $sitemapUrls = collect($parsed['urls'])->pluck('loc')->map(fn ($url) => $this->normalizeUrl($url));

        $architectureUrls = $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->pluck('url')
            ->map(fn ($url) => $this->normalizeUrl($url));

        return $this->compareUrls($sitemapUrls, $architectureUrls, $parsed['urls']);
    }

    /**
     * Compare URLs between sitemap and architecture.
     */
    protected function compareUrls(Collection $sitemapUrls, Collection $architectureUrls, array $sitemapData): array
    {
        $sitemapSet = $sitemapUrls->flip();
        $architectureSet = $architectureUrls->flip();

        // URLs in sitemap but not in architecture (potentially stale)
        $extraInSitemap = $sitemapUrls->filter(fn ($url) => ! isset($architectureSet[$url]))->values();

        // URLs in architecture but not in sitemap (missing)
        $missingFromSitemap = $architectureUrls->filter(fn ($url) => ! isset($sitemapSet[$url]))->values();

        // URLs in both
        $matching = $sitemapUrls->filter(fn ($url) => isset($architectureSet[$url]))->values();

        $issues = [];

        // Generate issues
        foreach ($extraInSitemap as $url) {
            $issues[] = [
                'type' => 'stale_url',
                'severity' => 'warning',
                'url' => $url,
                'message' => 'URL in sitemap not found in site architecture',
                'recommendation' => 'Remove from sitemap or verify the page exists',
            ];
        }

        foreach ($missingFromSitemap as $url) {
            $issues[] = [
                'type' => 'missing_url',
                'severity' => 'info',
                'url' => $url,
                'message' => 'URL found in architecture but missing from sitemap',
                'recommendation' => 'Add to sitemap if page should be indexed',
            ];
        }

        // Check for priority issues
        $sitemapDataMap = collect($sitemapData)->keyBy(fn ($item) => $this->normalizeUrl($item['loc']));
        foreach ($matching as $url) {
            $data = $sitemapDataMap[$url] ?? null;
            if ($data) {
                $priorityIssue = $this->checkPriority($data);
                if ($priorityIssue) {
                    $issues[] = $priorityIssue;
                }

                $changefreqIssue = $this->checkChangefreq($data);
                if ($changefreqIssue) {
                    $issues[] = $changefreqIssue;
                }
            }
        }

        return [
            'valid' => empty($issues) || collect($issues)->where('severity', 'error')->isEmpty(),
            'type' => 'urlset',
            'summary' => [
                'total_in_sitemap' => $sitemapUrls->count(),
                'total_in_architecture' => $architectureUrls->count(),
                'matching' => $matching->count(),
                'extra_in_sitemap' => $extraInSitemap->count(),
                'missing_from_sitemap' => $missingFromSitemap->count(),
            ],
            'matching_urls' => $matching->toArray(),
            'extra_urls' => $extraInSitemap->toArray(),
            'missing_urls' => $missingFromSitemap->toArray(),
            'issues' => $issues,
        ];
    }

    /**
     * Check priority value validity.
     */
    protected function checkPriority(array $urlData): ?array
    {
        $priority = $urlData['priority'] ?? null;

        if ($priority === null || $priority === '') {
            return null;
        }

        $priorityFloat = (float) $priority;

        if ($priorityFloat < 0 || $priorityFloat > 1) {
            return [
                'type' => 'invalid_priority',
                'severity' => 'warning',
                'url' => $urlData['loc'],
                'message' => "Invalid priority value: {$priority} (must be between 0.0 and 1.0)",
                'recommendation' => 'Update priority to a value between 0.0 and 1.0',
            ];
        }

        return null;
    }

    /**
     * Check changefreq value validity.
     */
    protected function checkChangefreq(array $urlData): ?array
    {
        $changefreq = $urlData['changefreq'] ?? null;

        if ($changefreq === null || $changefreq === '') {
            return null;
        }

        $validValues = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

        if (! in_array(strtolower($changefreq), $validValues)) {
            return [
                'type' => 'invalid_changefreq',
                'severity' => 'warning',
                'url' => $urlData['loc'],
                'message' => "Invalid changefreq value: {$changefreq}",
                'recommendation' => 'Use one of: '.implode(', ', $validValues),
            ];
        }

        return null;
    }

    /**
     * Fetch and validate sitemap from URL.
     */
    public function validateFromUrl(SiteArchitecture $architecture, string $sitemapUrl): array
    {
        try {
            $response = Http::timeout(30)->get($sitemapUrl);

            if (! $response->successful()) {
                return [
                    'valid' => false,
                    'error' => "Failed to fetch sitemap: HTTP {$response->status()}",
                    'issues' => [],
                ];
            }

            $contentType = $response->header('Content-Type');
            if (! str_contains($contentType, 'xml')) {
                return [
                    'valid' => false,
                    'error' => "Invalid content type: {$contentType} (expected XML)",
                    'issues' => [],
                ];
            }

            return $this->validate($architecture, $response->body());

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Failed to fetch sitemap: '.$e->getMessage(),
                'issues' => [],
            ];
        }
    }

    /**
     * Generate validation report.
     */
    public function generateReport(SiteArchitecture $architecture, string $sitemapXml): array
    {
        $validation = $this->validate($architecture, $sitemapXml);

        if (! $validation['valid'] && isset($validation['error'])) {
            return $validation;
        }

        $report = [
            'generated_at' => now()->toIso8601String(),
            'architecture_id' => $architecture->id,
            'validation_result' => $validation['valid'] ? 'passed' : 'failed',
            'summary' => $validation['summary'] ?? null,
            'issues_by_type' => [],
            'issues_by_severity' => [],
            'recommendations' => [],
        ];

        // Group issues
        $issues = collect($validation['issues'] ?? []);

        $report['issues_by_type'] = $issues->groupBy('type')->map->count()->toArray();
        $report['issues_by_severity'] = $issues->groupBy('severity')->map->count()->toArray();

        // Generate recommendations
        if ($validation['summary']['extra_in_sitemap'] > 0) {
            $report['recommendations'][] = [
                'priority' => 'medium',
                'action' => 'Remove stale URLs',
                'description' => "Found {$validation['summary']['extra_in_sitemap']} URLs in sitemap that no longer exist on the site.",
            ];
        }

        if ($validation['summary']['missing_from_sitemap'] > 0) {
            $report['recommendations'][] = [
                'priority' => 'low',
                'action' => 'Add missing URLs',
                'description' => "Found {$validation['summary']['missing_from_sitemap']} pages not included in the sitemap.",
            ];
        }

        $matchRate = $validation['summary']['total_in_sitemap'] > 0
            ? ($validation['summary']['matching'] / $validation['summary']['total_in_sitemap']) * 100
            : 0;

        $report['match_rate'] = round($matchRate, 1);
        $report['health_score'] = $this->calculateHealthScore($validation);

        return $report;
    }

    /**
     * Calculate sitemap health score.
     */
    protected function calculateHealthScore(array $validation): int
    {
        $score = 100;

        $summary = $validation['summary'] ?? [];
        $issues = $validation['issues'] ?? [];

        // Deduct for extra URLs (stale)
        $extraRate = $summary['total_in_sitemap'] > 0
            ? ($summary['extra_in_sitemap'] / $summary['total_in_sitemap'])
            : 0;
        $score -= min(30, $extraRate * 100);

        // Deduct for missing URLs
        $missingRate = $summary['total_in_architecture'] > 0
            ? ($summary['missing_from_sitemap'] / $summary['total_in_architecture'])
            : 0;
        $score -= min(20, $missingRate * 50);

        // Deduct for validation issues
        $errorCount = collect($issues)->where('severity', 'error')->count();
        $warningCount = collect($issues)->where('severity', 'warning')->count();

        $score -= $errorCount * 5;
        $score -= $warningCount * 2;

        return max(0, min(100, (int) round($score)));
    }

    /**
     * Normalize URL for comparison.
     */
    protected function normalizeUrl(string $url): string
    {
        $url = strtolower(trim($url));

        // Remove trailing slash (except for root)
        $parsed = parse_url($url);
        if (isset($parsed['path']) && $parsed['path'] !== '/') {
            $url = rtrim($url, '/');
        }

        // Remove common tracking parameters
        $url = preg_replace('/[?&](utm_\w+|ref|source|campaign)=[^&]*/', '', $url);
        $url = rtrim($url, '?&');

        return $url;
    }
}
