<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class SitemapGeneratorService
{
    protected const MAX_URLS_PER_SITEMAP = 50000;

    protected const DEFAULT_CHANGEFREQ = 'weekly';

    /**
     * Generate XML sitemap from architecture.
     */
    public function generateXml(SiteArchitecture $architecture, array $options = []): string
    {
        $nodes = $this->getIndexableNodes($architecture);

        if ($nodes->count() > self::MAX_URLS_PER_SITEMAP) {
            return $this->generateSitemapIndex($architecture, $nodes, $options);
        }

        return $this->generateSingleSitemap($nodes, $options);
    }

    /**
     * Generate a single sitemap XML.
     */
    protected function generateSingleSitemap(Collection $nodes, array $options = []): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        foreach ($nodes as $node) {
            $xml .= $this->generateUrlEntry($node, $options);
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Generate sitemap index for large sites.
     */
    protected function generateSitemapIndex(SiteArchitecture $architecture, Collection $nodes, array $options = []): string
    {
        $baseUrl = $options['base_url'] ?? $this->getBaseUrl($architecture);
        $chunks = $nodes->chunk(self::MAX_URLS_PER_SITEMAP);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";

        $index = 1;
        foreach ($chunks as $chunk) {
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>{$baseUrl}/sitemap-{$index}.xml</loc>\n";
            $xml .= '    <lastmod>'.now()->toW3cString()."</lastmod>\n";
            $xml .= "  </sitemap>\n";
            $index++;
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Generate individual sitemap files for large sites.
     */
    public function generateSitemapFiles(SiteArchitecture $architecture, array $options = []): array
    {
        $nodes = $this->getIndexableNodes($architecture);
        $chunks = $nodes->chunk(self::MAX_URLS_PER_SITEMAP);
        $files = [];

        $index = 1;
        foreach ($chunks as $chunk) {
            $files["sitemap-{$index}.xml"] = $this->generateSingleSitemap($chunk, $options);
            $index++;
        }

        if (count($files) > 1) {
            $files['sitemap.xml'] = $this->generateSitemapIndex($architecture, $nodes, $options);
        } elseif (count($files) === 1) {
            $files = ['sitemap.xml' => reset($files)];
        }

        return $files;
    }

    /**
     * Generate a URL entry for the sitemap.
     */
    protected function generateUrlEntry(ArchitectureNode $node, array $options = []): string
    {
        $priority = $this->calculatePriority($node);
        $changefreq = $this->calculateChangeFrequency($node, $options);
        $lastmod = $node->updated_at?->toW3cString() ?? now()->toW3cString();

        $xml = "  <url>\n";
        $xml .= '    <loc>'.htmlspecialchars($node->url, ENT_XML1)."</loc>\n";
        $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
        $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
        $xml .= "    <priority>{$priority}</priority>\n";
        $xml .= "  </url>\n";

        return $xml;
    }

    /**
     * Calculate priority based on node characteristics.
     */
    public function calculatePriority(ArchitectureNode $node): string
    {
        $priority = 0.5;

        // Homepage gets highest priority
        if ($node->depth === 0) {
            return '1.0';
        }

        // Adjust by depth (shallower = higher priority)
        $depthFactor = max(0, 1 - ($node->depth * 0.15));
        $priority = 0.3 + ($depthFactor * 0.5);

        // Boost for high link equity
        if ($node->link_equity_score > 0.1) {
            $priority += 0.1;
        }

        // Boost for high inbound links
        if ($node->inbound_count > 10) {
            $priority += 0.1;
        }

        // Penalty for orphan pages
        if ($node->is_orphan) {
            $priority -= 0.2;
        }

        // Penalty for deep pages
        if ($node->is_deep) {
            $priority -= 0.1;
        }

        // Clamp between 0.1 and 1.0
        $priority = max(0.1, min(1.0, $priority));

        return number_format($priority, 1);
    }

    /**
     * Calculate change frequency based on content type.
     */
    public function calculateChangeFrequency(ArchitectureNode $node, array $options = []): string
    {
        $defaultFreq = $options['default_changefreq'] ?? self::DEFAULT_CHANGEFREQ;

        // Homepage typically changes frequently
        if ($node->depth === 0) {
            return 'daily';
        }

        // Detect content type from path
        $path = $node->path ?? '';

        if (preg_match('/\/(blog|news|articles?)\//i', $path)) {
            return 'weekly';
        }

        if (preg_match('/\/(docs?|documentation|help|faq)\//i', $path)) {
            return 'monthly';
        }

        if (preg_match('/\/(about|contact|privacy|terms)\/?$/i', $path)) {
            return 'yearly';
        }

        return $defaultFreq;
    }

    /**
     * Get indexable nodes (exclude errors, external, etc.)
     */
    protected function getIndexableNodes(SiteArchitecture $architecture): Collection
    {
        return $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->whereNotNull('url')
            ->orderBy('depth')
            ->orderByDesc('link_equity_score')
            ->get();
    }

    /**
     * Get base URL from architecture.
     */
    protected function getBaseUrl(SiteArchitecture $architecture): string
    {
        $homepage = $architecture->nodes()
            ->where('depth', 0)
            ->first();

        if ($homepage && $homepage->url) {
            $parsed = parse_url($homepage->url);

            return ($parsed['scheme'] ?? 'https').'://'.($parsed['host'] ?? 'example.com');
        }

        return 'https://example.com';
    }

    /**
     * Get sitemap statistics.
     */
    public function getStats(SiteArchitecture $architecture): array
    {
        $nodes = $this->getIndexableNodes($architecture);

        $byPriority = $nodes->groupBy(fn ($node) => $this->calculatePriority($node));
        $byChangefreq = $nodes->groupBy(fn ($node) => $this->calculateChangeFrequency($node, []));

        return [
            'total_urls' => $nodes->count(),
            'requires_index' => $nodes->count() > self::MAX_URLS_PER_SITEMAP,
            'sitemap_count' => (int) ceil($nodes->count() / self::MAX_URLS_PER_SITEMAP),
            'by_priority' => $byPriority->map->count()->sortKeysDesc()->toArray(),
            'by_changefreq' => $byChangefreq->map->count()->toArray(),
            'max_depth' => $nodes->max('depth'),
            'avg_priority' => $nodes->avg(fn ($n) => (float) $this->calculatePriority($n)),
        ];
    }

    /**
     * Parse existing sitemap XML.
     */
    public function parseSitemap(string $xml): array
    {
        $urls = [];

        try {
            $doc = new \SimpleXMLElement($xml);
            $doc->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            // Check if it's a sitemap index
            $sitemaps = $doc->xpath('//sm:sitemap');
            if (! empty($sitemaps)) {
                return [
                    'type' => 'index',
                    'sitemaps' => collect($sitemaps)->map(fn ($s) => [
                        'loc' => (string) $s->loc,
                        'lastmod' => (string) ($s->lastmod ?? ''),
                    ])->toArray(),
                ];
            }

            // Parse regular sitemap
            $urlElements = $doc->xpath('//sm:url');
            foreach ($urlElements as $url) {
                $urls[] = [
                    'loc' => (string) $url->loc,
                    'lastmod' => (string) ($url->lastmod ?? ''),
                    'changefreq' => (string) ($url->changefreq ?? ''),
                    'priority' => (string) ($url->priority ?? ''),
                ];
            }
        } catch (\Exception $e) {
            return ['type' => 'error', 'error' => $e->getMessage()];
        }

        return [
            'type' => 'urlset',
            'urls' => $urls,
        ];
    }
}
