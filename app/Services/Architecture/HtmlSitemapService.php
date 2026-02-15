<?php

namespace App\Services\Architecture;

use App\Models\SiteArchitecture;

class HtmlSitemapService
{
    public function __construct(
        protected VisualSitemapService $visualService
    ) {}

    /**
     * Generate HTML sitemap content.
     */
    public function generateHtml(SiteArchitecture $architecture, array $options = []): string
    {
        $sections = $this->visualService->generateSections($architecture);
        $title = $options['title'] ?? 'Sitemap';
        $includeStyles = $options['include_styles'] ?? true;

        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
        $html .= "  <meta charset=\"UTF-8\">\n";
        $html .= "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $html .= "  <title>{$title}</title>\n";

        if ($includeStyles) {
            $html .= $this->getDefaultStyles();
        }

        $html .= "</head>\n<body>\n";
        $html .= "  <div class=\"sitemap-container\">\n";
        $html .= "    <h1>{$title}</h1>\n";

        foreach ($sections as $section) {
            $html .= $this->generateSectionHtml($section);
        }

        $html .= "  </div>\n</body>\n</html>";

        return $html;
    }

    /**
     * Generate HTML for a single section.
     */
    protected function generateSectionHtml(array $section): string
    {
        $html = "    <section class=\"sitemap-section\">\n";
        $html .= '      <h2>'.htmlspecialchars($section['name'])."</h2>\n";
        $html .= "      <ul>\n";

        foreach ($section['pages'] as $page) {
            $title = htmlspecialchars($page['title']);
            $url = htmlspecialchars($page['url']);
            $html .= "        <li><a href=\"{$url}\">{$title}</a></li>\n";
        }

        $html .= "      </ul>\n";
        $html .= "    </section>\n";

        return $html;
    }

    /**
     * Generate hierarchical HTML sitemap.
     */
    public function generateHierarchicalHtml(SiteArchitecture $architecture, array $options = []): string
    {
        $hierarchy = $this->visualService->generateHierarchy($architecture);
        $title = $options['title'] ?? 'Sitemap';
        $includeStyles = $options['include_styles'] ?? true;

        $html = "<!DOCTYPE html>\n<html lang=\"en\">\n<head>\n";
        $html .= "  <meta charset=\"UTF-8\">\n";
        $html .= "  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $html .= "  <title>{$title}</title>\n";

        if ($includeStyles) {
            $html .= $this->getDefaultStyles();
        }

        $html .= "</head>\n<body>\n";
        $html .= "  <div class=\"sitemap-container\">\n";
        $html .= "    <h1>{$title}</h1>\n";
        $html .= $this->generateTreeHtml($hierarchy);
        $html .= "  </div>\n</body>\n</html>";

        return $html;
    }

    /**
     * Generate nested list HTML for tree structure.
     */
    protected function generateTreeHtml(array $nodes, int $level = 0): string
    {
        if (empty($nodes)) {
            return '';
        }

        $indent = str_repeat('  ', $level + 2);
        $html = "{$indent}<ul class=\"sitemap-tree level-{$level}\">\n";

        foreach ($nodes as $node) {
            $title = htmlspecialchars($node['title']);
            $url = htmlspecialchars($node['url']);

            $html .= "{$indent}  <li>\n";
            $html .= "{$indent}    <a href=\"{$url}\">{$title}</a>\n";

            if (! empty($node['children'])) {
                $html .= $this->generateTreeHtml($node['children'], $level + 1);
            }

            $html .= "{$indent}  </li>\n";
        }

        $html .= "{$indent}</ul>\n";

        return $html;
    }

    /**
     * Generate sitemap data for Blade template.
     */
    public function getSitemapData(SiteArchitecture $architecture): array
    {
        return [
            'sections' => $this->visualService->generateSections($architecture),
            'hierarchy' => $this->visualService->generateHierarchy($architecture),
            'stats' => $this->visualService->getStructureStats($architecture),
        ];
    }

    /**
     * Generate categorized sitemap (by content type).
     */
    public function generateCategorizedData(SiteArchitecture $architecture): array
    {
        $nodes = $architecture->nodes()
            ->where('http_status', '>=', 200)
            ->where('http_status', '<', 300)
            ->orderBy('path')
            ->get();

        $categories = [
            'main' => ['name' => 'Main Pages', 'pattern' => '/^\/?(about|contact|services?|products?)?\/?$/i', 'pages' => []],
            'blog' => ['name' => 'Blog & Articles', 'pattern' => '/\/(blog|news|articles?)\//i', 'pages' => []],
            'docs' => ['name' => 'Documentation', 'pattern' => '/\/(docs?|documentation|help|faq|support)\//i', 'pages' => []],
            'legal' => ['name' => 'Legal', 'pattern' => '/\/(privacy|terms|legal|policy|disclaimer)\//i', 'pages' => []],
            'other' => ['name' => 'Other Pages', 'pattern' => null, 'pages' => []],
        ];

        foreach ($nodes as $node) {
            $path = $node->path ?? '/';
            $categorized = false;

            foreach ($categories as $key => &$category) {
                if ($category['pattern'] && preg_match($category['pattern'], $path)) {
                    $category['pages'][] = $this->formatPageData($node);
                    $categorized = true;
                    break;
                }
            }

            if (! $categorized) {
                $categories['other']['pages'][] = $this->formatPageData($node);
            }
        }

        // Remove empty categories
        return array_filter($categories, fn ($c) => ! empty($c['pages']));
    }

    /**
     * Format page data for display.
     */
    protected function formatPageData($node): array
    {
        return [
            'id' => $node->id,
            'url' => $node->url,
            'path' => $node->path,
            'title' => $node->title ?? $this->getTitleFromUrl($node->url),
            'depth' => $node->depth,
            'last_modified' => $node->updated_at?->toDateString(),
        ];
    }

    /**
     * Get title from URL.
     */
    protected function getTitleFromUrl(string $url): string
    {
        $parsed = parse_url($url);
        $path = $parsed['path'] ?? '/';

        if ($path === '/' || $path === '') {
            return 'Home';
        }

        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);

        // Clean up segment
        $lastSegment = preg_replace('/\.(html?|php|aspx?)$/i', '', $lastSegment);
        $lastSegment = str_replace(['-', '_'], ' ', $lastSegment);

        return ucwords($lastSegment);
    }

    /**
     * Get default CSS styles.
     */
    protected function getDefaultStyles(): string
    {
        return <<<'CSS'
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; padding: 2rem; max-width: 1200px; margin: 0 auto; }
    h1 { margin-bottom: 2rem; font-size: 2rem; border-bottom: 2px solid #eee; padding-bottom: 0.5rem; }
    h2 { margin: 1.5rem 0 1rem; font-size: 1.25rem; color: #555; }
    .sitemap-section { margin-bottom: 2rem; }
    ul { list-style: none; }
    .sitemap-section > ul { columns: 2; column-gap: 2rem; }
    @media (max-width: 768px) { .sitemap-section > ul { columns: 1; } }
    li { margin-bottom: 0.5rem; break-inside: avoid; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .sitemap-tree { padding-left: 1.5rem; }
    .sitemap-tree.level-0 { padding-left: 0; }
    .sitemap-tree li { margin-bottom: 0.25rem; }
  </style>

CSS;
    }
}
