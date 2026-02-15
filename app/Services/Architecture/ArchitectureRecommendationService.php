<?php

namespace App\Services\Architecture;

use App\Models\SiteArchitecture;

class ArchitectureRecommendationService
{
    /**
     * Priority weights for different recommendation types.
     */
    protected const PRIORITY_WEIGHTS = [
        'orphan' => 100,
        'deep_page' => 80,
        'broken_link' => 90,
        'low_equity' => 60,
        'linking_opportunity' => 50,
        'structure' => 40,
    ];

    public function __construct(
        protected ArchitectureSeoService $seoService
    ) {}

    /**
     * Generate prioritized recommendations for site architecture.
     */
    public function generateRecommendations(SiteArchitecture $architecture): array
    {
        $recommendations = [];

        // Collect recommendations from various analyses
        $recommendations = array_merge(
            $recommendations,
            $this->getOrphanRecommendations($architecture),
            $this->getDepthRecommendations($architecture),
            $this->getEquityRecommendations($architecture),
            $this->getStructureRecommendations($architecture),
            $this->getLinkingRecommendations($architecture)
        );

        // Sort by priority
        usort($recommendations, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        // Add implementation effort estimates
        foreach ($recommendations as &$rec) {
            $rec['effort'] = $this->estimateEffort($rec);
            $rec['impact_score'] = $this->calculateImpactScore($rec);
        }

        return array_slice($recommendations, 0, 20);
    }

    /**
     * Get recommendations for orphan pages.
     */
    protected function getOrphanRecommendations(SiteArchitecture $architecture): array
    {
        $recommendations = [];

        $orphans = $architecture->nodes()
            ->where('is_orphan', true)
            ->orderBy('link_equity_score', 'desc')
            ->limit(10)
            ->get();

        if ($orphans->count() > 5) {
            $recommendations[] = [
                'type' => 'orphan',
                'priority' => self::PRIORITY_WEIGHTS['orphan'] + 20,
                'severity' => 'critical',
                'title' => 'High number of orphan pages detected',
                'description' => "Found {$orphans->count()} pages with no internal links pointing to them. These pages are difficult for users and search engines to discover.",
                'action' => 'Add internal links from related content pages to orphan pages.',
                'affected_pages' => $orphans->take(5)->pluck('url')->toArray(),
                'category' => 'internal_linking',
            ];
        }

        foreach ($orphans->take(5) as $orphan) {
            $recommendations[] = [
                'type' => 'orphan',
                'priority' => self::PRIORITY_WEIGHTS['orphan'],
                'severity' => 'serious',
                'title' => "Link to orphan page: {$orphan->getShortPath()}",
                'description' => 'This page has no internal links pointing to it. Add links from related pages to improve discoverability.',
                'action' => "Add at least 2-3 internal links to {$orphan->url}",
                'affected_pages' => [$orphan->url],
                'node_id' => $orphan->id,
                'category' => 'internal_linking',
            ];
        }

        return $recommendations;
    }

    /**
     * Get recommendations for deep pages.
     */
    protected function getDepthRecommendations(SiteArchitecture $architecture): array
    {
        $recommendations = [];

        $deepPages = $architecture->nodes()
            ->where('is_deep', true)
            ->orderByDesc('depth')
            ->limit(10)
            ->get();

        if ($deepPages->count() > 0) {
            $avgDepth = $deepPages->avg('depth');

            $recommendations[] = [
                'type' => 'deep_page',
                'priority' => self::PRIORITY_WEIGHTS['deep_page'] + 10,
                'severity' => 'serious',
                'title' => 'Deep pages require restructuring',
                'description' => "Found {$deepPages->count()} pages more than 4 clicks from homepage (avg: {$avgDepth} clicks). Deep pages rank lower and are harder for users to find.",
                'action' => 'Restructure navigation or add links from higher-level pages.',
                'affected_pages' => $deepPages->take(5)->pluck('url')->toArray(),
                'category' => 'site_structure',
            ];
        }

        foreach ($deepPages->take(3) as $page) {
            $recommendations[] = [
                'type' => 'deep_page',
                'priority' => self::PRIORITY_WEIGHTS['deep_page'],
                'severity' => 'moderate',
                'title' => "Reduce depth of: {$page->getShortPath()}",
                'description' => "This page is {$page->depth} clicks from the homepage. Consider adding links from pages closer to the homepage.",
                'action' => "Add links from 1-2 click depth pages to {$page->url}",
                'affected_pages' => [$page->url],
                'node_id' => $page->id,
                'category' => 'site_structure',
            ];
        }

        return $recommendations;
    }

    /**
     * Get recommendations for link equity issues.
     */
    protected function getEquityRecommendations(SiteArchitecture $architecture): array
    {
        $recommendations = [];

        // Find pages with very low equity
        $lowEquityPages = $architecture->nodes()
            ->where('link_equity_score', '<', 0.01)
            ->where('depth', '>', 0)
            ->whereNotNull('depth')
            ->orderBy('link_equity_score')
            ->limit(10)
            ->get();

        // Find pages hoarding equity (high score, few outbound links)
        $equityHoarders = $architecture->nodes()
            ->where('link_equity_score', '>', 0.1)
            ->where('outbound_count', '<', 5)
            ->orderByDesc('link_equity_score')
            ->limit(5)
            ->get();

        if ($equityHoarders->count() > 0) {
            $recommendations[] = [
                'type' => 'low_equity',
                'priority' => self::PRIORITY_WEIGHTS['low_equity'] + 15,
                'severity' => 'moderate',
                'title' => 'Distribute link equity more evenly',
                'description' => "Found {$equityHoarders->count()} pages with high authority but few outbound links. Adding internal links from these pages will boost other pages.",
                'action' => 'Add relevant internal links from high-authority pages.',
                'affected_pages' => $equityHoarders->pluck('url')->toArray(),
                'category' => 'link_equity',
            ];
        }

        foreach ($lowEquityPages->take(5) as $page) {
            $recommendations[] = [
                'type' => 'low_equity',
                'priority' => self::PRIORITY_WEIGHTS['low_equity'],
                'severity' => 'minor',
                'title' => "Boost authority of: {$page->getShortPath()}",
                'description' => "This page has very low link equity ({$page->link_equity_score}). It needs more internal links from authoritative pages.",
                'action' => "Add links to {$page->url} from high-traffic pages",
                'affected_pages' => [$page->url],
                'node_id' => $page->id,
                'category' => 'link_equity',
            ];
        }

        return $recommendations;
    }

    /**
     * Get recommendations for site structure.
     */
    protected function getStructureRecommendations(SiteArchitecture $architecture): array
    {
        $recommendations = [];

        $totalNodes = $architecture->nodes()->count();
        $maxDepth = $architecture->max_depth ?? 0;

        // Check if site is too flat or too deep
        if ($maxDepth > 6 && $totalNodes > 50) {
            $recommendations[] = [
                'type' => 'structure',
                'priority' => self::PRIORITY_WEIGHTS['structure'] + 20,
                'severity' => 'moderate',
                'title' => 'Site structure is too deep',
                'description' => "Maximum depth is {$maxDepth} clicks. Ideally, all important pages should be within 3-4 clicks of the homepage.",
                'action' => 'Flatten site structure by adding hub pages or improving navigation.',
                'category' => 'site_structure',
            ];
        }

        // Check for missing hub pages (paths with many children but no index)
        $pathStats = [];
        foreach ($architecture->nodes()->get() as $node) {
            $path = $node->path ?? '/';
            $segments = array_filter(explode('/', trim($path, '/')));

            if (count($segments) > 1) {
                $parentPath = '/'.implode('/', array_slice($segments, 0, -1));
                $pathStats[$parentPath] = ($pathStats[$parentPath] ?? 0) + 1;
            }
        }

        foreach ($pathStats as $path => $count) {
            if ($count >= 5) {
                $hasIndex = $architecture->nodes()
                    ->where('path', $path)
                    ->orWhere('path', $path.'/')
                    ->exists();

                if (! $hasIndex) {
                    $recommendations[] = [
                        'type' => 'structure',
                        'priority' => self::PRIORITY_WEIGHTS['structure'],
                        'severity' => 'minor',
                        'title' => "Consider adding hub page for: {$path}",
                        'description' => "This URL path has {$count} child pages but no index/hub page. A hub page can improve navigation and SEO.",
                        'action' => "Create an index page at {$path} that links to all child pages.",
                        'category' => 'site_structure',
                    ];
                }
            }
        }

        return array_slice($recommendations, 0, 5);
    }

    /**
     * Get recommendations for internal linking opportunities.
     */
    protected function getLinkingRecommendations(SiteArchitecture $architecture): array
    {
        $recommendations = [];
        $opportunities = $this->seoService->findLinkingOpportunities($architecture);

        foreach (array_slice($opportunities, 0, 5) as $opp) {
            $recommendations[] = [
                'type' => 'linking_opportunity',
                'priority' => self::PRIORITY_WEIGHTS['linking_opportunity'] + ($opp['impact'] / 10),
                'severity' => 'info',
                'title' => "Link opportunity: {$opp['source']['title']} → {$opp['target']['title']}",
                'description' => "Adding a link from a high-authority page to an under-linked page. Reason: {$opp['reason']}",
                'action' => "Add internal link from {$opp['source']['url']} to {$opp['target']['url']}",
                'affected_pages' => [$opp['source']['url'], $opp['target']['url']],
                'category' => 'internal_linking',
            ];
        }

        return $recommendations;
    }

    /**
     * Estimate implementation effort.
     */
    protected function estimateEffort(array $recommendation): string
    {
        return match ($recommendation['type']) {
            'orphan' => 'low',
            'deep_page' => 'medium',
            'structure' => 'high',
            'low_equity' => 'low',
            'linking_opportunity' => 'low',
            'broken_link' => 'low',
            default => 'medium',
        };
    }

    /**
     * Calculate impact score for prioritization.
     */
    protected function calculateImpactScore(array $recommendation): int
    {
        $baseScore = match ($recommendation['severity']) {
            'critical' => 100,
            'serious' => 75,
            'moderate' => 50,
            'minor' => 25,
            'info' => 10,
            default => 25,
        };

        $effortMultiplier = match ($recommendation['effort']) {
            'low' => 1.5,
            'medium' => 1.0,
            'high' => 0.6,
            default => 1.0,
        };

        return (int) round($baseScore * $effortMultiplier);
    }

    /**
     * Get fix roadmap grouped by priority and effort.
     */
    public function getFixRoadmap(SiteArchitecture $architecture): array
    {
        $recommendations = $this->generateRecommendations($architecture);

        $roadmap = [
            'quick_wins' => [], // High impact, low effort
            'major_projects' => [], // High impact, high effort
            'fill_ins' => [], // Low impact, low effort
            'deprioritize' => [], // Low impact, high effort
        ];

        foreach ($recommendations as $rec) {
            $isHighImpact = $rec['impact_score'] >= 50;
            $isLowEffort = $rec['effort'] === 'low';

            if ($isHighImpact && $isLowEffort) {
                $roadmap['quick_wins'][] = $rec;
            } elseif ($isHighImpact) {
                $roadmap['major_projects'][] = $rec;
            } elseif ($isLowEffort) {
                $roadmap['fill_ins'][] = $rec;
            } else {
                $roadmap['deprioritize'][] = $rec;
            }
        }

        return $roadmap;
    }

    /**
     * Get summary statistics for recommendations.
     */
    public function getSummary(SiteArchitecture $architecture): array
    {
        $recommendations = $this->generateRecommendations($architecture);

        $byCategory = [];
        $bySeverity = [];

        foreach ($recommendations as $rec) {
            $category = $rec['category'] ?? 'other';
            $severity = $rec['severity'] ?? 'info';

            $byCategory[$category] = ($byCategory[$category] ?? 0) + 1;
            $bySeverity[$severity] = ($bySeverity[$severity] ?? 0) + 1;
        }

        return [
            'total' => count($recommendations),
            'by_category' => $byCategory,
            'by_severity' => $bySeverity,
            'top_priority' => array_slice($recommendations, 0, 3),
        ];
    }
}
