<?php

namespace App\Services\Architecture;

use App\Enums\ArchitectureIssueType;
use App\Enums\ImpactLevel;
use App\Models\ArchitectureIssue;
use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;

class ArchitectureSeoService
{
    public function __construct(
        protected OrphanDetectionService $orphanService,
        protected DepthAnalysisService $depthService,
        protected LinkEquityService $equityService
    ) {}

    /**
     * Run full SEO analysis on site architecture.
     */
    public function analyze(SiteArchitecture $architecture): array
    {
        return [
            'orphan_analysis' => $this->analyzeOrphans($architecture),
            'depth_analysis' => $this->analyzeDepth($architecture),
            'equity_analysis' => $this->analyzeEquity($architecture),
            'linking_opportunities' => $this->findLinkingOpportunities($architecture),
            'critical_issues' => $this->getCriticalIssues($architecture),
            'overall_score' => $this->calculateOverallScore($architecture),
        ];
    }

    /**
     * Analyze orphan pages.
     */
    public function analyzeOrphans(SiteArchitecture $architecture): array
    {
        $orphans = $this->orphanService->detectOrphans($architecture);
        $orphanRate = $this->orphanService->calculateOrphanRate($architecture);

        return [
            'count' => $orphans->count(),
            'rate' => $orphanRate,
            'pages' => $orphans->take(10)->map(fn ($node) => [
                'id' => $node->id,
                'url' => $node->url,
                'title' => $node->title,
                'path' => $node->path,
            ])->values()->toArray(),
            'severity' => $this->getOrphanSeverity($orphanRate),
        ];
    }

    /**
     * Analyze page depth.
     */
    public function analyzeDepth(SiteArchitecture $architecture): array
    {
        $analysis = $this->depthService->analyzeDepth($architecture);
        $deepPages = $this->depthService->detectDeepPages($architecture);
        $depthScore = $this->depthService->calculateDepthScore($architecture);

        return [
            'max_depth' => $analysis['max_depth'],
            'average_depth' => $analysis['average_depth'],
            'deep_pages_count' => $deepPages->count(),
            'deep_pages' => $deepPages->take(10)->map(fn ($node) => [
                'id' => $node->id,
                'url' => $node->url,
                'title' => $node->title,
                'depth' => $node->depth,
            ])->values()->toArray(),
            'score' => $depthScore['score'],
            'grade' => $depthScore['grade'],
            'distribution' => $analysis['depth_distribution'],
        ];
    }

    /**
     * Analyze link equity distribution.
     */
    public function analyzeEquity(SiteArchitecture $architecture): array
    {
        $distribution = $this->equityService->analyzeDistribution($architecture);

        // Find pages with very low equity that need more internal links
        $lowEquityPages = $architecture->nodes()
            ->where('link_equity_score', '<', 0.01)
            ->where('depth', '>', 0)
            ->orderBy('link_equity_score')
            ->limit(10)
            ->get();

        // Find pages with high equity that could distribute more
        $highEquityPages = $architecture->nodes()
            ->where('link_equity_score', '>', 0.1)
            ->orderByDesc('link_equity_score')
            ->limit(10)
            ->get();

        return [
            'distribution' => $distribution,
            'low_equity_pages' => $lowEquityPages->map(fn ($node) => [
                'id' => $node->id,
                'url' => $node->url,
                'title' => $node->title,
                'score' => $node->link_equity_score,
                'inbound_count' => $node->inbound_count,
            ])->values()->toArray(),
            'high_equity_pages' => $highEquityPages->map(fn ($node) => [
                'id' => $node->id,
                'url' => $node->url,
                'title' => $node->title,
                'score' => $node->link_equity_score,
                'outbound_count' => $node->outbound_count,
            ])->values()->toArray(),
            'equity_gap' => $this->calculateEquityGap($distribution),
        ];
    }

    /**
     * Find internal linking opportunities.
     */
    public function findLinkingOpportunities(SiteArchitecture $architecture): array
    {
        $opportunities = [];

        // Find pages that could benefit from more internal links
        $lowLinkPages = $architecture->nodes()
            ->where('inbound_count', '<', 3)
            ->where('depth', '>', 0)
            ->whereNotNull('depth')
            ->orderBy('inbound_count')
            ->limit(20)
            ->get();

        // Find potential source pages (high equity, low outbound)
        $potentialSources = $architecture->nodes()
            ->where('link_equity_score', '>', 0.05)
            ->where('outbound_count', '<', 10)
            ->orderByDesc('link_equity_score')
            ->limit(20)
            ->get();

        // Match opportunities
        foreach ($lowLinkPages as $target) {
            $targetPath = $target->path ?? '';
            $targetSegments = explode('/', trim($targetPath, '/'));

            foreach ($potentialSources as $source) {
                if ($source->id === $target->id) {
                    continue;
                }

                // Check if they're in related content areas
                $sourcePath = $source->path ?? '';
                $sourceSegments = explode('/', trim($sourcePath, '/'));

                $sharedSegments = array_intersect($sourceSegments, $targetSegments);

                if (count($sharedSegments) > 0 || count($opportunities) < 10) {
                    $opportunities[] = [
                        'source' => [
                            'id' => $source->id,
                            'url' => $source->url,
                            'title' => $source->title,
                            'equity' => $source->link_equity_score,
                        ],
                        'target' => [
                            'id' => $target->id,
                            'url' => $target->url,
                            'title' => $target->title,
                            'inbound_count' => $target->inbound_count,
                        ],
                        'reason' => count($sharedSegments) > 0 ? 'Related content area' : 'Low inbound links',
                        'impact' => $this->calculateLinkImpact($source, $target),
                    ];

                    if (count($opportunities) >= 10) {
                        break 2;
                    }
                }
            }
        }

        // Sort by impact
        usort($opportunities, fn ($a, $b) => $b['impact'] <=> $a['impact']);

        return array_slice($opportunities, 0, 10);
    }

    /**
     * Get critical SEO issues.
     */
    public function getCriticalIssues(SiteArchitecture $architecture): array
    {
        $issues = $architecture->issues()
            ->whereIn('severity', [ImpactLevel::Critical, ImpactLevel::Serious])
            ->where('is_resolved', false)
            ->with('node')
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'serious' THEN 2 ELSE 3 END")
            ->limit(20)
            ->get();

        return $issues->map(fn (ArchitectureIssue $issue) => [
            'id' => $issue->id,
            'type' => $issue->issue_type->value,
            'severity' => $issue->severity->value,
            'message' => $issue->message,
            'node' => $issue->node ? [
                'id' => $issue->node->id,
                'url' => $issue->node->url,
                'title' => $issue->node->title,
            ] : null,
            'recommendation' => $issue->recommendation,
        ])->toArray();
    }

    /**
     * Calculate overall SEO score.
     */
    public function calculateOverallScore(SiteArchitecture $architecture): array
    {
        $scores = [];

        // Orphan score (0-100, higher is better)
        $orphanRate = $this->orphanService->calculateOrphanRate($architecture);
        $scores['orphan'] = max(0, 100 - ($orphanRate * 500)); // 20% orphans = 0 score

        // Depth score (already calculated as percentage)
        $depthScore = $this->depthService->calculateDepthScore($architecture);
        $scores['depth'] = $depthScore['score'];

        // Equity distribution score
        $distribution = $this->equityService->analyzeDistribution($architecture);
        $equityGap = $this->calculateEquityGap($distribution);
        $scores['equity'] = max(0, 100 - ($equityGap * 100)); // Lower gap = higher score

        // Issue score (based on critical/serious issues)
        $totalNodes = $architecture->nodes()->count();
        $criticalIssues = $architecture->issues()
            ->where('severity', ImpactLevel::Critical)
            ->where('is_resolved', false)
            ->count();
        $seriousIssues = $architecture->issues()
            ->where('severity', ImpactLevel::Serious)
            ->where('is_resolved', false)
            ->count();
        $issueImpact = (($criticalIssues * 2) + $seriousIssues) / max($totalNodes, 1);
        $scores['issues'] = max(0, 100 - ($issueImpact * 200));

        // Calculate weighted average
        $weights = [
            'orphan' => 0.25,
            'depth' => 0.25,
            'equity' => 0.25,
            'issues' => 0.25,
        ];

        $overallScore = 0;
        foreach ($scores as $key => $score) {
            $overallScore += $score * $weights[$key];
        }

        return [
            'overall' => round($overallScore, 1),
            'grade' => $this->scoreToGrade($overallScore),
            'breakdown' => $scores,
        ];
    }

    /**
     * Create all SEO issues for architecture.
     */
    public function createIssues(SiteArchitecture $architecture): int
    {
        $issueCount = 0;

        // Orphan issues
        $issueCount += $this->orphanService->markOrphansAndCreateIssues($architecture);

        // Deep page issues
        $deepPages = $this->depthService->detectDeepPages($architecture);
        foreach ($deepPages as $node) {
            if (! $architecture->issues()->where('node_id', $node->id)->where('issue_type', ArchitectureIssueType::DeepPage)->exists()) {
                ArchitectureIssue::createDeepPageIssue($node, $node->depth);
                $issueCount++;
            }
        }

        // Low equity issues
        $lowEquityPages = $architecture->nodes()
            ->where('link_equity_score', '<', 0.005)
            ->where('depth', '>', 0)
            ->get();

        foreach ($lowEquityPages as $node) {
            if (! $architecture->issues()->where('node_id', $node->id)->where('issue_type', ArchitectureIssueType::LowLinkEquity)->exists()) {
                ArchitectureIssue::create([
                    'site_architecture_id' => $architecture->id,
                    'node_id' => $node->id,
                    'issue_type' => ArchitectureIssueType::LowLinkEquity,
                    'severity' => ImpactLevel::Moderate,
                    'message' => "Page has very low link equity ({$node->link_equity_score}). Consider adding more internal links to this page.",
                    'recommendation' => 'Add internal links from high-authority pages to improve this page\'s visibility.',
                ]);
                $issueCount++;
            }
        }

        return $issueCount;
    }

    /**
     * Calculate equity gap (difference between highest and lowest).
     */
    protected function calculateEquityGap(array $distribution): float
    {
        if (empty($distribution)) {
            return 1.0;
        }

        $max = $distribution['max'] ?? 1;
        $min = $distribution['min'] ?? 0;
        $mean = $distribution['mean'] ?? 0.5;

        if ($mean === 0) {
            return 1.0;
        }

        // Calculate coefficient of variation as a measure of inequality
        $stdDev = $distribution['std_dev'] ?? 0;

        return min(1.0, $stdDev / $mean);
    }

    /**
     * Calculate potential impact of adding a link.
     */
    protected function calculateLinkImpact(ArchitectureNode $source, ArchitectureNode $target): float
    {
        $sourceEquity = $source->link_equity_score ?? 0;
        $sourceOutbound = max(1, $source->outbound_count);
        $targetInbound = max(1, $target->inbound_count);

        // Higher source equity and lower target inbound = higher impact
        return ($sourceEquity / $sourceOutbound) * (1 / $targetInbound) * 1000;
    }

    /**
     * Get severity level for orphan rate.
     */
    protected function getOrphanSeverity(float $rate): string
    {
        return match (true) {
            $rate > 0.2 => 'critical',
            $rate > 0.1 => 'serious',
            $rate > 0.05 => 'moderate',
            default => 'minor',
        };
    }

    /**
     * Convert score to letter grade.
     */
    protected function scoreToGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }
}
