<?php

namespace App\Services\Architecture;

use App\Models\ArchitectureNode;
use App\Models\SiteArchitecture;
use Illuminate\Support\Collection;

class VisualSitemapService
{
    /**
     * Generate hierarchical sitemap structure.
     */
    public function generateHierarchy(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->orderBy('depth')
            ->orderBy('path')
            ->get();

        if ($nodes->isEmpty()) {
            return [];
        }

        return $this->buildTree($nodes);
    }

    /**
     * Build tree structure from flat nodes.
     */
    protected function buildTree(Collection $nodes): array
    {
        $tree = [];
        $pathIndex = [];

        foreach ($nodes as $node) {
            $path = trim($node->path ?? '/', '/');
            $segments = $path === '' ? [] : explode('/', $path);

            $entry = [
                'id' => $node->id,
                'url' => $node->url,
                'path' => '/'.$path,
                'title' => $node->title ?? $this->getTitleFromPath($path),
                'depth' => $node->depth,
                'status' => $node->status?->value ?? 'ok',
                'priority' => $this->calculateVisualPriority($node),
                'children' => [],
            ];

            if (empty($segments)) {
                // Root node
                $tree[] = $entry;
                $pathIndex[''] = &$tree[count($tree) - 1];
            } else {
                // Find parent path
                $parentPath = implode('/', array_slice($segments, 0, -1));

                if (isset($pathIndex[$parentPath])) {
                    $pathIndex[$parentPath]['children'][] = $entry;
                    $pathIndex[$path] = &$pathIndex[$parentPath]['children'][count($pathIndex[$parentPath]['children']) - 1];
                } else {
                    // Parent doesn't exist, add to root
                    $tree[] = $entry;
                    $pathIndex[$path] = &$tree[count($tree) - 1];
                }
            }
        }

        return $tree;
    }

    /**
     * Generate flat structure grouped by sections.
     */
    public function generateSections(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->orderBy('path')
            ->get();

        $sections = [];

        foreach ($nodes as $node) {
            $path = trim($node->path ?? '/', '/');
            $segments = explode('/', $path);
            $section = $segments[0] ?? 'root';

            if (! isset($sections[$section])) {
                $sections[$section] = [
                    'name' => $this->formatSectionName($section),
                    'path' => '/'.$section,
                    'pages' => [],
                    'count' => 0,
                ];
            }

            $sections[$section]['pages'][] = [
                'id' => $node->id,
                'url' => $node->url,
                'path' => '/'.$path,
                'title' => $node->title ?? $this->getTitleFromPath($path),
                'depth' => $node->depth,
            ];
            $sections[$section]['count']++;
        }

        // Sort sections by count (largest first), but keep root first
        uasort($sections, function ($a, $b) {
            if ($a['path'] === '/root') {
                return -1;
            }
            if ($b['path'] === '/root') {
                return 1;
            }

            return $b['count'] <=> $a['count'];
        });

        return array_values($sections);
    }

    /**
     * Generate breadcrumb paths for all pages.
     */
    public function generateBreadcrumbs(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->orderBy('depth')
            ->get();

        $breadcrumbs = [];

        foreach ($nodes as $node) {
            $path = trim($node->path ?? '/', '/');
            $segments = $path === '' ? [] : explode('/', $path);

            $crumbs = [
                ['title' => 'Home', 'path' => '/', 'url' => $this->findUrlForPath($nodes, '/')],
            ];

            $currentPath = '';
            foreach ($segments as $segment) {
                $currentPath .= '/'.$segment;
                $crumbs[] = [
                    'title' => $this->formatSectionName($segment),
                    'path' => $currentPath,
                    'url' => $this->findUrlForPath($nodes, $currentPath),
                ];
            }

            $breadcrumbs[$node->id] = $crumbs;
        }

        return $breadcrumbs;
    }

    /**
     * Generate sitemap for D3.js visualization.
     */
    public function generateD3Data(SiteArchitecture $architecture): array
    {
        $hierarchy = $this->generateHierarchy($architecture);

        if (empty($hierarchy)) {
            return ['name' => 'root', 'children' => []];
        }

        // D3 expects a single root node
        $root = [
            'name' => 'Site',
            'data' => ['path' => '/', 'depth' => -1],
            'children' => $this->convertToD3Format($hierarchy),
        ];

        return $root;
    }

    /**
     * Convert hierarchy to D3 format.
     */
    protected function convertToD3Format(array $nodes): array
    {
        return array_map(function ($node) {
            $d3Node = [
                'name' => $node['title'],
                'data' => [
                    'id' => $node['id'],
                    'url' => $node['url'],
                    'path' => $node['path'],
                    'depth' => $node['depth'],
                    'status' => $node['status'],
                    'priority' => $node['priority'],
                ],
            ];

            if (! empty($node['children'])) {
                $d3Node['children'] = $this->convertToD3Format($node['children']);
            }

            return $d3Node;
        }, $nodes);
    }

    /**
     * Calculate visual priority (for sizing/coloring nodes).
     */
    protected function calculateVisualPriority(ArchitectureNode $node): string
    {
        if ($node->depth === 0) {
            return 'high';
        }

        if ($node->depth <= 2 && $node->inbound_count > 5) {
            return 'high';
        }

        if ($node->depth <= 3) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get title from path segment.
     */
    protected function getTitleFromPath(string $path): string
    {
        if (empty($path) || $path === '/') {
            return 'Home';
        }

        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);

        return $this->formatSectionName($lastSegment);
    }

    /**
     * Format section name for display.
     */
    protected function formatSectionName(string $segment): string
    {
        if ($segment === '' || $segment === 'root') {
            return 'Home';
        }

        // Remove file extensions
        $segment = preg_replace('/\.(html?|php|aspx?)$/i', '', $segment);

        // Replace separators with spaces
        $segment = str_replace(['-', '_'], ' ', $segment);

        // Title case
        return ucwords($segment);
    }

    /**
     * Find URL for a given path.
     */
    protected function findUrlForPath(Collection $nodes, string $path): ?string
    {
        $normalizedPath = trim($path, '/');

        $node = $nodes->first(function ($n) use ($normalizedPath) {
            $nodePath = trim($n->path ?? '', '/');

            return $nodePath === $normalizedPath;
        });

        return $node?->url;
    }

    /**
     * Get statistics about the sitemap structure.
     */
    public function getStructureStats(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->get();

        $sections = $this->generateSections($architecture);
        $hierarchy = $this->generateHierarchy($architecture);

        return [
            'total_pages' => $nodes->count(),
            'total_sections' => count($sections),
            'max_depth' => $nodes->max('depth'),
            'avg_depth' => round($nodes->avg('depth'), 2),
            'pages_by_depth' => $nodes->groupBy('depth')->map->count()->toArray(),
            'largest_sections' => collect($sections)->take(5)->map(fn ($s) => [
                'name' => $s['name'],
                'count' => $s['count'],
            ])->toArray(),
            'orphan_pages' => $nodes->where('is_orphan', true)->count(),
            'deep_pages' => $nodes->where('is_deep', true)->count(),
        ];
    }
}
