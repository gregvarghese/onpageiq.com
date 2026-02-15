<?php

namespace App\Services\Architecture\Export;

class MermaidExportService extends ExportService
{
    protected function defaultOptions(): array
    {
        return [
            'diagram_type' => 'flowchart', // flowchart, mindmap, graph
            'direction' => 'TB', // TB, BT, LR, RL
            'include_errors' => false,
            'include_external' => false,
            'show_status' => true,
            'show_depth' => false,
            'max_label_length' => 30,
            'group_by_depth' => false,
        ];
    }

    public function generate(): string
    {
        return match ($this->options['diagram_type']) {
            'mindmap' => $this->generateMindmap(),
            'graph' => $this->generateGraph(),
            default => $this->generateFlowchart(),
        };
    }

    public function getExtension(): string
    {
        return 'mmd';
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }

    protected function generateFlowchart(): string
    {
        $nodes = $this->getNodes();
        $links = $this->getLinks();
        $direction = $this->options['direction'];

        $mermaid = "flowchart {$direction}\n";
        $mermaid .= $this->generateFlowchartStyles();

        // Group nodes by depth if requested
        if ($this->options['group_by_depth']) {
            $mermaid .= $this->generateDepthSubgraphs($nodes);
        }

        // Generate node definitions
        foreach ($nodes as $node) {
            $mermaid .= $this->generateFlowchartNode($node);
        }

        // Generate links
        $nodeMap = collect($nodes)->keyBy('id')->toArray();
        foreach ($links as $link) {
            $mermaid .= $this->generateFlowchartLink($link, $nodeMap);
        }

        return $mermaid;
    }

    protected function generateFlowchartNode(array $node): string
    {
        $id = $this->sanitizeId($node['id']);
        $label = $this->truncateLabel($node['title'] ?? $node['path']);
        $shape = $this->getNodeShape($node);

        $line = "    {$id}{$shape['open']}\"{$label}\"{$shape['close']}";

        if ($this->options['show_status']) {
            $statusClass = $this->getStatusClass($node);
            $line .= ":::{$statusClass}";
        }

        return $line."\n";
    }

    protected function generateFlowchartLink(array $link, array $nodeMap): string
    {
        $sourceId = $this->sanitizeId($link['source']);
        $targetId = $this->sanitizeId($link['target']);

        $arrow = $this->getLinkArrow($link);
        $label = '';

        if (! empty($link['anchor_text']) && strlen($link['anchor_text']) <= 20) {
            $label = '|'.$this->escapeLabel($link['anchor_text']).'|';
        }

        return "    {$sourceId} {$arrow}{$label} {$targetId}\n";
    }

    protected function generateFlowchartStyles(): string
    {
        return <<<'MERMAID'
    classDef ok fill:#10B981,stroke:#059669,color:#fff
    classDef redirect fill:#F59E0B,stroke:#D97706,color:#fff
    classDef error fill:#EF4444,stroke:#DC2626,color:#fff
    classDef orphan fill:#8B5CF6,stroke:#7C3AED,color:#fff
    classDef external fill:#6B7280,stroke:#4B5563,color:#fff,stroke-dasharray: 5 5

MERMAID;
    }

    protected function generateDepthSubgraphs(array $nodes): string
    {
        $byDepth = collect($nodes)->groupBy('depth')->sortKeys();
        $mermaid = '';

        foreach ($byDepth as $depth => $levelNodes) {
            $mermaid .= "    subgraph depth{$depth}[\"Depth {$depth}\"]\n";
            foreach ($levelNodes as $node) {
                $id = $this->sanitizeId($node['id']);
                $mermaid .= "        {$id}\n";
            }
            $mermaid .= "    end\n";
        }

        return $mermaid;
    }

    protected function generateMindmap(): string
    {
        $nodes = $this->getNodes();

        $mermaid = "mindmap\n";
        $mermaid .= "  root((Site Architecture))\n";

        // Build hierarchy from nodes
        $hierarchy = $this->buildHierarchy($nodes);
        $mermaid .= $this->renderMindmapLevel($hierarchy, 2);

        return $mermaid;
    }

    protected function buildHierarchy(array $nodes): array
    {
        $byDepth = collect($nodes)->groupBy('depth')->sortKeys()->toArray();
        $hierarchy = [];

        // Start with depth 0 (root level)
        $depth0 = $byDepth[0] ?? [];
        foreach ($depth0 as $node) {
            $hierarchy[] = [
                'node' => $node,
                'children' => $this->findChildren($node, $byDepth),
            ];
        }

        return $hierarchy;
    }

    protected function findChildren(array $parent, array $byDepth): array
    {
        $links = $this->getLinks();
        $parentId = $parent['id'];
        $nextDepth = $parent['depth'] + 1;

        $childNodes = $byDepth[$nextDepth] ?? [];
        $children = [];

        // Find nodes linked from this parent
        $childIds = collect($links)
            ->where('source', $parentId)
            ->pluck('target')
            ->toArray();

        foreach ($childNodes as $node) {
            if (in_array($node['id'], $childIds)) {
                $children[] = [
                    'node' => $node,
                    'children' => $this->findChildren($node, $byDepth),
                ];
            }
        }

        return $children;
    }

    protected function renderMindmapLevel(array $items, int $indent): string
    {
        $mermaid = '';
        $spaces = str_repeat('  ', $indent);

        foreach ($items as $item) {
            $label = $this->truncateLabel($item['node']['title'] ?? $item['node']['path']);
            $shape = $this->getMindmapShape($item['node']);

            $mermaid .= "{$spaces}{$shape['open']}{$label}{$shape['close']}\n";

            if (! empty($item['children'])) {
                $mermaid .= $this->renderMindmapLevel($item['children'], $indent + 1);
            }
        }

        return $mermaid;
    }

    protected function generateGraph(): string
    {
        $nodes = $this->getNodes();
        $links = $this->getLinks();
        $direction = $this->options['direction'];

        $mermaid = "graph {$direction}\n";
        $mermaid .= $this->generateFlowchartStyles();

        foreach ($nodes as $node) {
            $id = $this->sanitizeId($node['id']);
            $label = $this->truncateLabel($node['title'] ?? $node['path']);
            $statusClass = $this->getStatusClass($node);

            $mermaid .= "    {$id}[\"{$label}\"]:::{$statusClass}\n";
        }

        foreach ($links as $link) {
            $sourceId = $this->sanitizeId($link['source']);
            $targetId = $this->sanitizeId($link['target']);
            $mermaid .= "    {$sourceId} --> {$targetId}\n";
        }

        return $mermaid;
    }

    protected function sanitizeId($id): string
    {
        // Mermaid IDs must be alphanumeric with underscores
        return 'node_'.preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
    }

    protected function truncateLabel(string $label): string
    {
        $maxLength = $this->options['max_label_length'];
        $label = $this->escapeLabel($label);

        if (strlen($label) > $maxLength) {
            return substr($label, 0, $maxLength - 3).'...';
        }

        return $label;
    }

    protected function escapeLabel(string $label): string
    {
        // Escape special Mermaid characters
        return str_replace(
            ['"', '#', '&', '<', '>', '[', ']', '(', ')', '{', '}'],
            ['\'', '', 'and', '', '', '', '', '', '', '', ''],
            $label
        );
    }

    protected function getNodeShape(array $node): array
    {
        if ($node['is_orphan'] ?? false) {
            return ['open' => '{{', 'close' => '}}'];
        }

        if (($node['http_status'] ?? 200) >= 400) {
            return ['open' => '((', 'close' => '))'];
        }

        if (($node['depth'] ?? 0) === 0) {
            return ['open' => '([', 'close' => '])'];
        }

        return ['open' => '[', 'close' => ']'];
    }

    protected function getMindmapShape(array $node): array
    {
        if ($node['is_orphan'] ?? false) {
            return ['open' => '{{', 'close' => '}}'];
        }

        if (($node['http_status'] ?? 200) >= 400) {
            return ['open' => ')', 'close' => '('];
        }

        return ['open' => '', 'close' => ''];
    }

    protected function getLinkArrow(array $link): string
    {
        if ($link['is_external'] ?? false) {
            return '-.->';
        }

        return match ($link['type'] ?? 'content') {
            'navigation' => '==>',
            'footer' => '-.->',
            'sidebar' => '--o',
            default => '-->',
        };
    }

    protected function getStatusClass(array $node): string
    {
        if ($node['is_orphan'] ?? false) {
            return 'orphan';
        }

        $httpStatus = $node['http_status'] ?? 200;

        if ($httpStatus >= 400) {
            return 'error';
        }

        if ($httpStatus >= 300) {
            return 'redirect';
        }

        return 'ok';
    }
}
