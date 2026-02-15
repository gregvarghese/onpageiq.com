<?php

namespace App\Services\Architecture\Export;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfExportService extends ExportService
{
    protected function defaultOptions(): array
    {
        return [
            'include_errors' => true,
            'include_external' => false,
            'include_statistics' => true,
            'include_node_list' => true,
            'include_link_analysis' => true,
            'include_recommendations' => true,
            'page_size' => 'A4',
            'orientation' => 'portrait',
            'include_cover' => true,
            'include_toc' => true,
            'brand_color' => '#3B82F6',
        ];
    }

    public function generate(): string
    {
        $nodes = $this->getNodes();
        $links = $this->getLinks();
        $metadata = $this->getMetadata();

        $data = [
            'metadata' => $metadata,
            'nodes' => $nodes,
            'links' => $links,
            'statistics' => $this->calculateStatistics($nodes, $links),
            'nodesByDepth' => $this->groupNodesByDepth($nodes),
            'nodesByStatus' => $this->groupNodesByStatus($nodes),
            'topPages' => $this->getTopPages($nodes),
            'orphanPages' => $this->getOrphanPages($nodes),
            'errorPages' => $this->getErrorPages($nodes),
            'recommendations' => $this->generateRecommendations($nodes, $links),
            'options' => $this->options,
            'generatedAt' => now()->format('F j, Y \a\t g:i A'),
        ];

        $pdf = Pdf::loadView('exports.architecture-report', $data);

        $pdf->setPaper($this->options['page_size'], $this->options['orientation']);

        return $pdf->output();
    }

    public function getExtension(): string
    {
        return 'pdf';
    }

    public function getMimeType(): string
    {
        return 'application/pdf';
    }

    protected function calculateStatistics(array $nodes, array $links): array
    {
        $totalNodes = count($nodes);
        $totalLinks = count($links);

        $statusCounts = collect($nodes)->groupBy(function ($node) {
            $status = $node['http_status'] ?? 200;
            if ($status >= 400) {
                return 'error';
            }
            if ($status >= 300) {
                return 'redirect';
            }

            return 'ok';
        })->map->count();

        $depthDistribution = collect($nodes)->groupBy('depth')->map->count()->sortKeys();

        $avgInboundLinks = $totalNodes > 0
            ? collect($nodes)->avg('inbound_count') ?? 0
            : 0;

        $avgOutboundLinks = $totalNodes > 0
            ? collect($nodes)->avg('outbound_count') ?? 0
            : 0;

        $orphanCount = collect($nodes)->where('is_orphan', true)->count();
        $deepPages = collect($nodes)->where('is_deep', true)->count();

        $maxDepth = collect($nodes)->max('depth') ?? 0;

        return [
            'total_pages' => $totalNodes,
            'total_links' => $totalLinks,
            'ok_pages' => $statusCounts['ok'] ?? 0,
            'redirect_pages' => $statusCounts['redirect'] ?? 0,
            'error_pages' => $statusCounts['error'] ?? 0,
            'orphan_pages' => $orphanCount,
            'deep_pages' => $deepPages,
            'max_depth' => $maxDepth,
            'avg_inbound_links' => round($avgInboundLinks, 1),
            'avg_outbound_links' => round($avgOutboundLinks, 1),
            'depth_distribution' => $depthDistribution->toArray(),
            'health_score' => $this->calculateHealthScore($nodes, $links),
        ];
    }

    protected function calculateHealthScore(array $nodes, array $links): int
    {
        $score = 100;

        $totalNodes = count($nodes);
        if ($totalNodes === 0) {
            return 0;
        }

        // Deduct for errors
        $errorCount = collect($nodes)->filter(fn ($n) => ($n['http_status'] ?? 200) >= 400)->count();
        $score -= min(30, ($errorCount / $totalNodes) * 100);

        // Deduct for orphans
        $orphanCount = collect($nodes)->where('is_orphan', true)->count();
        $score -= min(20, ($orphanCount / $totalNodes) * 100);

        // Deduct for deep pages
        $deepCount = collect($nodes)->where('is_deep', true)->count();
        $score -= min(15, ($deepCount / $totalNodes) * 50);

        // Deduct for pages with no outbound links
        $deadEndCount = collect($nodes)->filter(fn ($n) => ($n['outbound_count'] ?? 0) === 0)->count();
        $score -= min(10, ($deadEndCount / $totalNodes) * 50);

        // Bonus for good internal linking
        $avgLinks = collect($nodes)->avg('inbound_count') ?? 0;
        if ($avgLinks >= 3) {
            $score += 5;
        }

        return max(0, min(100, (int) round($score)));
    }

    protected function groupNodesByDepth(array $nodes): array
    {
        return collect($nodes)
            ->groupBy('depth')
            ->sortKeys()
            ->map(fn ($group) => $group->values()->toArray())
            ->toArray();
    }

    protected function groupNodesByStatus(array $nodes): array
    {
        return collect($nodes)
            ->groupBy(function ($node) {
                $status = $node['http_status'] ?? 200;
                if ($status >= 400) {
                    return 'Error';
                }
                if ($status >= 300) {
                    return 'Redirect';
                }

                return 'OK';
            })
            ->map(fn ($group) => $group->values()->toArray())
            ->toArray();
    }

    protected function getTopPages(array $nodes, int $limit = 10): array
    {
        return collect($nodes)
            ->sortByDesc('inbound_count')
            ->take($limit)
            ->values()
            ->toArray();
    }

    protected function getOrphanPages(array $nodes): array
    {
        return collect($nodes)
            ->where('is_orphan', true)
            ->values()
            ->toArray();
    }

    protected function getErrorPages(array $nodes): array
    {
        return collect($nodes)
            ->filter(fn ($n) => ($n['http_status'] ?? 200) >= 400)
            ->values()
            ->toArray();
    }

    protected function generateRecommendations(array $nodes, array $links): array
    {
        $recommendations = [];

        // Check for orphan pages
        $orphans = collect($nodes)->where('is_orphan', true);
        if ($orphans->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'Internal Linking',
                'title' => 'Fix Orphan Pages',
                'description' => sprintf(
                    '%d page(s) have no inbound links. Add internal links to improve discoverability.',
                    $orphans->count()
                ),
                'affected_count' => $orphans->count(),
            ];
        }

        // Check for error pages
        $errors = collect($nodes)->filter(fn ($n) => ($n['http_status'] ?? 200) >= 400);
        if ($errors->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'critical',
                'category' => 'Technical SEO',
                'title' => 'Fix Broken Pages',
                'description' => sprintf(
                    '%d page(s) return error status codes. Fix or remove these pages.',
                    $errors->count()
                ),
                'affected_count' => $errors->count(),
            ];
        }

        // Check for deep pages
        $deep = collect($nodes)->where('is_deep', true);
        if ($deep->isNotEmpty()) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'Site Structure',
                'title' => 'Reduce Click Depth',
                'description' => sprintf(
                    '%d page(s) are more than 3 clicks from homepage. Consider restructuring navigation.',
                    $deep->count()
                ),
                'affected_count' => $deep->count(),
            ];
        }

        // Check for pages with no outbound links
        $deadEnds = collect($nodes)->filter(fn ($n) => ($n['outbound_count'] ?? 0) === 0);
        if ($deadEnds->count() > 3) {
            $recommendations[] = [
                'priority' => 'low',
                'category' => 'User Experience',
                'title' => 'Add Outbound Links to Dead-End Pages',
                'description' => sprintf(
                    '%d page(s) have no outbound links. Add related content links to improve navigation.',
                    $deadEnds->count()
                ),
                'affected_count' => $deadEnds->count(),
            ];
        }

        // Check internal link distribution
        $avgInbound = collect($nodes)->avg('inbound_count') ?? 0;
        if ($avgInbound < 2) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'Internal Linking',
                'title' => 'Improve Internal Link Distribution',
                'description' => 'Pages have an average of less than 2 inbound links. Increase internal linking for better SEO.',
                'affected_count' => null,
            ];
        }

        // Sort by priority
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($recommendations, fn ($a, $b) => ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4));

        return $recommendations;
    }

    /**
     * Generate HTML content for PDF (alternative to Blade view)
     */
    public function generateHtml(): string
    {
        $nodes = $this->getNodes();
        $links = $this->getLinks();
        $metadata = $this->getMetadata();
        $statistics = $this->calculateStatistics($nodes, $links);
        $recommendations = $this->generateRecommendations($nodes, $links);

        $brandColor = $this->options['brand_color'];

        $html = $this->renderCoverPage($metadata, $statistics, $brandColor);
        $html .= $this->renderStatisticsSection($statistics, $brandColor);

        if ($this->options['include_node_list']) {
            $html .= $this->renderNodeListSection($nodes, $brandColor);
        }

        if ($this->options['include_recommendations']) {
            $html .= $this->renderRecommendationsSection($recommendations, $brandColor);
        }

        return $html;
    }

    protected function renderCoverPage(array $metadata, array $statistics, string $brandColor): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; margin: 0; padding: 40px; }
        .cover { text-align: center; padding-top: 200px; }
        .cover h1 { font-size: 36px; color: {$brandColor}; margin-bottom: 10px; }
        .cover .subtitle { font-size: 18px; color: #666; }
        .cover .meta { margin-top: 40px; color: #999; font-size: 12px; }
        .score-badge { display: inline-block; padding: 20px 40px; background: {$brandColor}; color: white; border-radius: 50%; font-size: 48px; font-weight: bold; margin-top: 40px; }
    </style>
</head>
<body>
<div class="cover">
    <h1>{$metadata['project_name']}</h1>
    <p class="subtitle">Site Architecture Report</p>
    <div class="score-badge">{$statistics['health_score']}</div>
    <p style="margin-top: 20px; color: #666;">Health Score</p>
    <p class="meta">Generated on {$metadata['exported_at']}<br>{$statistics['total_pages']} pages • {$statistics['total_links']} links</p>
</div>
<div style="page-break-after: always;"></div>
HTML;
    }

    protected function renderStatisticsSection(array $statistics, string $brandColor): string
    {
        return <<<HTML
<h2 style="color: {$brandColor}; border-bottom: 2px solid {$brandColor}; padding-bottom: 10px;">Overview Statistics</h2>
<table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <tr>
        <td style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; width: 25%;">
            <div style="font-size: 32px; font-weight: bold; color: {$brandColor};">{$statistics['total_pages']}</div>
            <div style="color: #666; font-size: 12px;">Total Pages</div>
        </td>
        <td style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; width: 25%;">
            <div style="font-size: 32px; font-weight: bold; color: #10B981;">{$statistics['ok_pages']}</div>
            <div style="color: #666; font-size: 12px;">OK Pages</div>
        </td>
        <td style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; width: 25%;">
            <div style="font-size: 32px; font-weight: bold; color: #EF4444;">{$statistics['error_pages']}</div>
            <div style="color: #666; font-size: 12px;">Error Pages</div>
        </td>
        <td style="padding: 15px; background: #f8f9fa; border-radius: 8px; text-align: center; width: 25%;">
            <div style="font-size: 32px; font-weight: bold; color: #8B5CF6;">{$statistics['orphan_pages']}</div>
            <div style="color: #666; font-size: 12px;">Orphan Pages</div>
        </td>
    </tr>
</table>
<div style="page-break-after: always;"></div>
HTML;
    }

    protected function renderNodeListSection(array $nodes, string $brandColor): string
    {
        $rows = '';
        foreach (array_slice($nodes, 0, 50) as $node) {
            $statusColor = match (true) {
                ($node['http_status'] ?? 200) >= 400 => '#EF4444',
                ($node['http_status'] ?? 200) >= 300 => '#F59E0B',
                default => '#10B981',
            };

            $title = htmlspecialchars($node['title'] ?? $node['path']);
            $path = htmlspecialchars($node['path'] ?? '/');

            $rows .= <<<HTML
<tr>
    <td style="padding: 8px; border-bottom: 1px solid #eee;">{$title}</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee; font-size: 11px; color: #666;">{$path}</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">
        <span style="display: inline-block; width: 12px; height: 12px; background: {$statusColor}; border-radius: 50%;"></span>
    </td>
    <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">{$node['depth']}</td>
    <td style="padding: 8px; border-bottom: 1px solid #eee; text-align: center;">{$node['inbound_count']}</td>
</tr>
HTML;
        }

        return <<<HTML
<h2 style="color: {$brandColor}; border-bottom: 2px solid {$brandColor}; padding-bottom: 10px;">Page Inventory</h2>
<table style="width: 100%; border-collapse: collapse; font-size: 12px;">
    <thead>
        <tr style="background: #f8f9fa;">
            <th style="padding: 10px; text-align: left;">Title</th>
            <th style="padding: 10px; text-align: left;">Path</th>
            <th style="padding: 10px; text-align: center;">Status</th>
            <th style="padding: 10px; text-align: center;">Depth</th>
            <th style="padding: 10px; text-align: center;">Inbound</th>
        </tr>
    </thead>
    <tbody>
        {$rows}
    </tbody>
</table>
HTML;
    }

    protected function renderRecommendationsSection(array $recommendations, string $brandColor): string
    {
        if (empty($recommendations)) {
            return '';
        }

        $items = '';
        foreach ($recommendations as $rec) {
            $priorityColor = match ($rec['priority']) {
                'critical' => '#DC2626',
                'high' => '#EF4444',
                'medium' => '#F59E0B',
                default => '#6B7280',
            };

            $title = htmlspecialchars($rec['title']);
            $description = htmlspecialchars($rec['description']);
            $category = htmlspecialchars($rec['category']);
            $priority = ucfirst($rec['priority']);

            $items .= <<<HTML
<div style="margin-bottom: 15px; padding: 15px; border-left: 4px solid {$priorityColor}; background: #f8f9fa;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <strong>{$title}</strong>
        <span style="font-size: 10px; padding: 2px 8px; background: {$priorityColor}; color: white; border-radius: 10px;">{$priority}</span>
    </div>
    <p style="margin: 8px 0 0; color: #666; font-size: 12px;">{$description}</p>
    <span style="font-size: 10px; color: #999;">{$category}</span>
</div>
HTML;
        }

        return <<<HTML
<div style="page-break-before: always;"></div>
<h2 style="color: {$brandColor}; border-bottom: 2px solid {$brandColor}; padding-bottom: 10px;">Recommendations</h2>
{$items}
HTML;
    }
}
