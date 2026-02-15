<?php

namespace App\Services\Architecture\Export;

class SvgExportService extends ExportService
{
    protected function defaultOptions(): array
    {
        return [
            'width' => 1200,
            'height' => 800,
            'include_legend' => true,
            'include_metadata' => true,
            'include_errors' => false,
            'include_external' => false,
            'node_radius' => 8,
            'font_size' => 10,
            'show_labels' => true,
            'color_scheme' => 'status', // status, depth, equity
        ];
    }

    public function generate(): string
    {
        $nodes = $this->getNodes();
        $links = $this->getLinks();
        $metadata = $this->getMetadata();

        $width = $this->options['width'];
        $height = $this->options['height'];

        $svg = $this->generateSvgHeader($width, $height);
        $svg .= $this->generateStyles();
        $svg .= $this->generateDefs();

        // Main content group
        $svg .= '<g class="content">';
        $svg .= $this->generateLinks($links, $nodes);
        $svg .= $this->generateNodes($nodes);
        $svg .= '</g>';

        if ($this->options['include_legend']) {
            $svg .= $this->generateLegend();
        }

        if ($this->options['include_metadata']) {
            $svg .= $this->generateMetadataBox($metadata);
        }

        $svg .= '</svg>';

        return $svg;
    }

    public function getExtension(): string
    {
        return 'svg';
    }

    public function getMimeType(): string
    {
        return 'image/svg+xml';
    }

    protected function generateSvgHeader(int $width, int $height): string
    {
        return <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$width} {$height}" width="{$width}" height="{$height}">

SVG;
    }

    protected function generateStyles(): string
    {
        return <<<'SVG'
<style>
  .node { cursor: pointer; }
  .node circle { stroke: #fff; stroke-width: 1.5; }
  .node text { font-family: -apple-system, BlinkMacSystemFont, sans-serif; pointer-events: none; }
  .link { fill: none; stroke-opacity: 0.6; }
  .link-navigation { stroke: #3B82F6; }
  .link-content { stroke: #10B981; }
  .link-footer { stroke: #6B7280; }
  .link-sidebar { stroke: #8B5CF6; }
  .link-external { stroke: #F59E0B; stroke-dasharray: 4,2; }
  .status-ok { fill: #10B981; }
  .status-redirect { fill: #F59E0B; }
  .status-error { fill: #EF4444; }
  .status-orphan { fill: #8B5CF6; }
  .legend { font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 11px; }
  .metadata { font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 10px; fill: #6B7280; }
</style>

SVG;
    }

    protected function generateDefs(): string
    {
        return <<<'SVG'
<defs>
  <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="10" refY="3.5" orient="auto">
    <polygon points="0 0, 10 3.5, 0 7" fill="#999" />
  </marker>
</defs>

SVG;
    }

    protected function generateNodes(array $nodes): string
    {
        $svg = '<g class="nodes">';

        $nodePositions = $this->calculateNodePositions($nodes);
        $radius = $this->options['node_radius'];
        $fontSize = $this->options['font_size'];

        foreach ($nodes as $index => $node) {
            $pos = $nodePositions[$node['id']] ?? ['x' => 100, 'y' => 100];
            $statusClass = $this->getStatusClass($node);
            $title = htmlspecialchars($node['title'] ?? '', ENT_XML1);

            $svg .= "<g class=\"node\" data-id=\"{$node['id']}\" transform=\"translate({$pos['x']},{$pos['y']})\">";
            $svg .= "<circle r=\"{$radius}\" class=\"{$statusClass}\" />";

            if ($this->options['show_labels']) {
                $labelX = $radius + 3;
                $svg .= "<text x=\"{$labelX}\" dy=\".35em\" font-size=\"{$fontSize}\">{$title}</text>";
            }

            $svg .= "<title>{$title}\n{$node['url']}\nDepth: {$node['depth']}</title>";
            $svg .= '</g>';
        }

        $svg .= '</g>';

        return $svg;
    }

    protected function generateLinks(array $links, array $nodes): string
    {
        $svg = '<g class="links">';

        $nodePositions = $this->calculateNodePositions($nodes);
        $nodeMap = collect($nodes)->keyBy('id')->toArray();

        foreach ($links as $link) {
            $sourcePos = $nodePositions[$link['source']] ?? null;
            $targetPos = $nodePositions[$link['target']] ?? null;

            if (! $sourcePos || ! $targetPos) {
                continue;
            }

            $linkClass = 'link link-'.($link['type'] ?? 'content');

            $svg .= "<line class=\"{$linkClass}\" ";
            $svg .= "x1=\"{$sourcePos['x']}\" y1=\"{$sourcePos['y']}\" ";
            $svg .= "x2=\"{$targetPos['x']}\" y2=\"{$targetPos['y']}\" ";
            $svg .= 'stroke-width="1" />';
        }

        $svg .= '</g>';

        return $svg;
    }

    protected function generateLegend(): string
    {
        $x = 20;
        $y = 20;

        return <<<SVG
<g class="legend" transform="translate({$x},{$y})">
  <rect x="0" y="0" width="120" height="100" fill="white" stroke="#e5e7eb" rx="4" />
  <text x="10" y="20" font-weight="bold">Legend</text>
  <g transform="translate(10,35)">
    <circle cx="6" cy="0" r="5" class="status-ok" /><text x="16" dy=".35em">OK</text>
  </g>
  <g transform="translate(10,55)">
    <circle cx="6" cy="0" r="5" class="status-redirect" /><text x="16" dy=".35em">Redirect</text>
  </g>
  <g transform="translate(10,75)">
    <circle cx="6" cy="0" r="5" class="status-error" /><text x="16" dy=".35em">Error</text>
  </g>
</g>

SVG;
    }

    protected function generateMetadataBox(array $metadata): string
    {
        $x = $this->options['width'] - 180;
        $y = $this->options['height'] - 80;

        $projectName = htmlspecialchars($metadata['project_name'], ENT_XML1);
        $exportedAt = $metadata['exported_at'];

        return <<<SVG
<g class="metadata" transform="translate({$x},{$y})">
  <rect x="0" y="0" width="160" height="60" fill="white" stroke="#e5e7eb" rx="4" />
  <text x="10" y="18">{$projectName}</text>
  <text x="10" y="34">Nodes: {$metadata['total_nodes']} | Links: {$metadata['total_links']}</text>
  <text x="10" y="50">Exported: {$exportedAt}</text>
</g>

SVG;
    }

    protected function calculateNodePositions(array $nodes): array
    {
        $positions = [];
        $width = $this->options['width'];
        $height = $this->options['height'];
        $padding = 100;

        // Group nodes by depth for hierarchical layout
        $byDepth = collect($nodes)->groupBy('depth');
        $maxDepth = $byDepth->keys()->max() ?? 0;

        $levelHeight = ($height - 2 * $padding) / max(1, $maxDepth);

        foreach ($byDepth as $depth => $levelNodes) {
            $levelNodes = $levelNodes->values();
            $count = $levelNodes->count();
            $levelWidth = ($width - 2 * $padding) / max(1, $count);

            foreach ($levelNodes as $index => $node) {
                $positions[$node['id']] = [
                    'x' => $padding + ($index + 0.5) * $levelWidth,
                    'y' => $padding + $depth * $levelHeight,
                ];
            }
        }

        return $positions;
    }

    protected function getStatusClass(array $node): string
    {
        if ($node['is_orphan'] ?? false) {
            return 'status-orphan';
        }

        $httpStatus = $node['http_status'] ?? 200;

        if ($httpStatus >= 400) {
            return 'status-error';
        }

        if ($httpStatus >= 300) {
            return 'status-redirect';
        }

        return 'status-ok';
    }
}
