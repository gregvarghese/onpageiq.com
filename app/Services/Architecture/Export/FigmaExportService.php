<?php

namespace App\Services\Architecture\Export;

class FigmaExportService extends ExportService
{
    protected function defaultOptions(): array
    {
        return [
            'include_errors' => false,
            'include_external' => false,
            'canvas_width' => 4000,
            'canvas_height' => 3000,
            'node_width' => 200,
            'node_height' => 80,
            'horizontal_spacing' => 100,
            'vertical_spacing' => 150,
            'include_connections' => true,
            'color_scheme' => 'status', // status, depth, section
        ];
    }

    public function generate(): string
    {
        $nodes = $this->getNodes();
        $links = $this->getLinks();
        $metadata = $this->getMetadata();

        $figmaData = [
            'name' => $metadata['project_name'].' - Site Architecture',
            'lastModified' => $metadata['exported_at'],
            'version' => '1.0',
            'document' => [
                'id' => '0:0',
                'name' => 'Document',
                'type' => 'DOCUMENT',
                'children' => [
                    $this->generatePage($nodes, $links, $metadata),
                ],
            ],
            'components' => $this->generateComponentDefinitions(),
            'styles' => $this->generateStyles(),
        ];

        return json_encode($figmaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function getExtension(): string
    {
        return 'fig.json';
    }

    public function getMimeType(): string
    {
        return 'application/json';
    }

    protected function generatePage(array $nodes, array $links, array $metadata): array
    {
        $positions = $this->calculatePositions($nodes);

        $children = [];

        // Add title frame
        $children[] = $this->generateTitleFrame($metadata);

        // Add legend
        $children[] = $this->generateLegend();

        // Add connection lines first (so they appear behind nodes)
        if ($this->options['include_connections']) {
            foreach ($links as $link) {
                $line = $this->generateConnection($link, $positions);
                if ($line) {
                    $children[] = $line;
                }
            }
        }

        // Add node frames
        foreach ($nodes as $node) {
            $position = $positions[$node['id']] ?? ['x' => 0, 'y' => 0];
            $children[] = $this->generateNodeFrame($node, $position);
        }

        return [
            'id' => '0:1',
            'name' => 'Site Architecture',
            'type' => 'CANVAS',
            'children' => $children,
            'backgroundColor' => [
                'r' => 0.97,
                'g' => 0.97,
                'b' => 0.97,
                'a' => 1,
            ],
        ];
    }

    protected function generateNodeFrame(array $node, array $position): array
    {
        $width = $this->options['node_width'];
        $height = $this->options['node_height'];
        $color = $this->getNodeColor($node);

        return [
            'id' => 'node_'.$node['id'],
            'name' => $node['title'] ?? $node['path'],
            'type' => 'FRAME',
            'x' => $position['x'],
            'y' => $position['y'],
            'width' => $width,
            'height' => $height,
            'cornerRadius' => 8,
            'fills' => [
                [
                    'type' => 'SOLID',
                    'color' => $color,
                ],
            ],
            'strokes' => [
                [
                    'type' => 'SOLID',
                    'color' => $this->darkenColor($color, 0.2),
                ],
            ],
            'strokeWeight' => 2,
            'effects' => [
                [
                    'type' => 'DROP_SHADOW',
                    'color' => ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0.1],
                    'offset' => ['x' => 0, 'y' => 2],
                    'radius' => 4,
                ],
            ],
            'children' => [
                $this->generateNodeTitle($node, $width),
                $this->generateNodeUrl($node, $width, $height),
                $this->generateNodeBadge($node),
            ],
        ];
    }

    protected function generateNodeTitle(array $node, float $width): array
    {
        return [
            'id' => 'title_'.$node['id'],
            'name' => 'Title',
            'type' => 'TEXT',
            'x' => 12,
            'y' => 12,
            'width' => $width - 24,
            'characters' => $node['title'] ?? $this->getTitleFromPath($node['path'] ?? '/'),
            'style' => [
                'fontFamily' => 'Inter',
                'fontWeight' => 600,
                'fontSize' => 14,
                'lineHeight' => 20,
            ],
            'fills' => [
                [
                    'type' => 'SOLID',
                    'color' => ['r' => 1, 'g' => 1, 'b' => 1, 'a' => 1],
                ],
            ],
        ];
    }

    protected function generateNodeUrl(array $node, float $width, float $height): array
    {
        return [
            'id' => 'url_'.$node['id'],
            'name' => 'URL',
            'type' => 'TEXT',
            'x' => 12,
            'y' => $height - 28,
            'width' => $width - 24,
            'characters' => $node['path'] ?? '/',
            'style' => [
                'fontFamily' => 'Inter',
                'fontWeight' => 400,
                'fontSize' => 11,
                'lineHeight' => 16,
            ],
            'fills' => [
                [
                    'type' => 'SOLID',
                    'color' => ['r' => 1, 'g' => 1, 'b' => 1, 'a' => 0.7],
                ],
            ],
        ];
    }

    protected function generateNodeBadge(array $node): array
    {
        $depth = $node['depth'] ?? 0;

        return [
            'id' => 'badge_'.$node['id'],
            'name' => 'Depth Badge',
            'type' => 'FRAME',
            'x' => $this->options['node_width'] - 36,
            'y' => 8,
            'width' => 28,
            'height' => 20,
            'cornerRadius' => 10,
            'fills' => [
                [
                    'type' => 'SOLID',
                    'color' => ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0.2],
                ],
            ],
            'children' => [
                [
                    'id' => 'badge_text_'.$node['id'],
                    'name' => 'Depth',
                    'type' => 'TEXT',
                    'x' => 8,
                    'y' => 3,
                    'characters' => (string) $depth,
                    'style' => [
                        'fontFamily' => 'Inter',
                        'fontWeight' => 500,
                        'fontSize' => 11,
                    ],
                    'fills' => [
                        [
                            'type' => 'SOLID',
                            'color' => ['r' => 1, 'g' => 1, 'b' => 1, 'a' => 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function generateConnection(array $link, array $positions): ?array
    {
        $sourcePos = $positions[$link['source']] ?? null;
        $targetPos = $positions[$link['target']] ?? null;

        if (! $sourcePos || ! $targetPos) {
            return null;
        }

        $width = $this->options['node_width'];
        $height = $this->options['node_height'];

        // Calculate connection points (center bottom of source to center top of target)
        $startX = $sourcePos['x'] + $width / 2;
        $startY = $sourcePos['y'] + $height;
        $endX = $targetPos['x'] + $width / 2;
        $endY = $targetPos['y'];

        $color = $this->getLinkColor($link);

        return [
            'id' => 'link_'.$link['source'].'_'.$link['target'],
            'name' => 'Connection',
            'type' => 'VECTOR',
            'strokeWeight' => 2,
            'strokes' => [
                [
                    'type' => 'SOLID',
                    'color' => $color,
                ],
            ],
            'strokeCap' => 'ROUND',
            'vectorPaths' => [
                [
                    'windingRule' => 'NONZERO',
                    'data' => $this->generateBezierPath($startX, $startY, $endX, $endY),
                ],
            ],
        ];
    }

    protected function generateBezierPath(float $x1, float $y1, float $x2, float $y2): string
    {
        $midY = ($y1 + $y2) / 2;

        return sprintf(
            'M %.2f %.2f C %.2f %.2f %.2f %.2f %.2f %.2f',
            $x1,
            $y1,
            $x1,
            $midY,
            $x2,
            $midY,
            $x2,
            $y2
        );
    }

    protected function generateTitleFrame(array $metadata): array
    {
        return [
            'id' => 'title_frame',
            'name' => 'Title',
            'type' => 'FRAME',
            'x' => 50,
            'y' => 50,
            'width' => 400,
            'height' => 100,
            'fills' => [],
            'children' => [
                [
                    'id' => 'title_text',
                    'name' => 'Project Title',
                    'type' => 'TEXT',
                    'x' => 0,
                    'y' => 0,
                    'characters' => $metadata['project_name'],
                    'style' => [
                        'fontFamily' => 'Inter',
                        'fontWeight' => 700,
                        'fontSize' => 32,
                    ],
                    'fills' => [
                        [
                            'type' => 'SOLID',
                            'color' => ['r' => 0.1, 'g' => 0.1, 'b' => 0.1, 'a' => 1],
                        ],
                    ],
                ],
                [
                    'id' => 'subtitle_text',
                    'name' => 'Subtitle',
                    'type' => 'TEXT',
                    'x' => 0,
                    'y' => 45,
                    'characters' => sprintf(
                        'Site Architecture • %d pages • %d links',
                        $metadata['total_nodes'],
                        $metadata['total_links']
                    ),
                    'style' => [
                        'fontFamily' => 'Inter',
                        'fontWeight' => 400,
                        'fontSize' => 14,
                    ],
                    'fills' => [
                        [
                            'type' => 'SOLID',
                            'color' => ['r' => 0.4, 'g' => 0.4, 'b' => 0.4, 'a' => 1],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function generateLegend(): array
    {
        $legendItems = [
            ['label' => 'OK (2xx)', 'color' => $this->hexToRgb('#10B981')],
            ['label' => 'Redirect (3xx)', 'color' => $this->hexToRgb('#F59E0B')],
            ['label' => 'Error (4xx/5xx)', 'color' => $this->hexToRgb('#EF4444')],
            ['label' => 'Orphan', 'color' => $this->hexToRgb('#8B5CF6')],
        ];

        $children = [];
        $y = 0;

        foreach ($legendItems as $index => $item) {
            $children[] = [
                'id' => 'legend_dot_'.$index,
                'name' => 'Dot',
                'type' => 'ELLIPSE',
                'x' => 0,
                'y' => $y + 4,
                'width' => 12,
                'height' => 12,
                'fills' => [
                    [
                        'type' => 'SOLID',
                        'color' => $item['color'],
                    ],
                ],
            ];

            $children[] = [
                'id' => 'legend_label_'.$index,
                'name' => 'Label',
                'type' => 'TEXT',
                'x' => 20,
                'y' => $y,
                'characters' => $item['label'],
                'style' => [
                    'fontFamily' => 'Inter',
                    'fontWeight' => 400,
                    'fontSize' => 12,
                ],
                'fills' => [
                    [
                        'type' => 'SOLID',
                        'color' => ['r' => 0.3, 'g' => 0.3, 'b' => 0.3, 'a' => 1],
                    ],
                ],
            ];

            $y += 24;
        }

        return [
            'id' => 'legend_frame',
            'name' => 'Legend',
            'type' => 'FRAME',
            'x' => 50,
            'y' => 180,
            'width' => 150,
            'height' => count($legendItems) * 24,
            'fills' => [],
            'children' => $children,
        ];
    }

    protected function generateComponentDefinitions(): array
    {
        return [
            'node_component' => [
                'key' => 'node_component',
                'name' => 'Page Node',
                'description' => 'Represents a page in the site architecture',
            ],
            'connection_component' => [
                'key' => 'connection_component',
                'name' => 'Page Connection',
                'description' => 'Represents a link between pages',
            ],
        ];
    }

    protected function generateStyles(): array
    {
        return [
            'fills' => [
                'ok' => [
                    'name' => 'Status/OK',
                    'type' => 'SOLID',
                    'color' => $this->hexToRgb('#10B981'),
                ],
                'redirect' => [
                    'name' => 'Status/Redirect',
                    'type' => 'SOLID',
                    'color' => $this->hexToRgb('#F59E0B'),
                ],
                'error' => [
                    'name' => 'Status/Error',
                    'type' => 'SOLID',
                    'color' => $this->hexToRgb('#EF4444'),
                ],
                'orphan' => [
                    'name' => 'Status/Orphan',
                    'type' => 'SOLID',
                    'color' => $this->hexToRgb('#8B5CF6'),
                ],
            ],
        ];
    }

    protected function calculatePositions(array $nodes): array
    {
        $positions = [];
        $width = $this->options['node_width'];
        $height = $this->options['node_height'];
        $hSpacing = $this->options['horizontal_spacing'];
        $vSpacing = $this->options['vertical_spacing'];

        // Group by depth for hierarchical layout
        $byDepth = collect($nodes)->groupBy('depth')->sortKeys();

        $startX = 500; // Leave room for title and legend
        $startY = 350;

        foreach ($byDepth as $depth => $levelNodes) {
            $levelNodes = $levelNodes->values();
            $count = $levelNodes->count();

            // Center nodes horizontally at this level
            $levelWidth = $count * $width + ($count - 1) * $hSpacing;
            $offsetX = $startX - $levelWidth / 2 + $this->options['canvas_width'] / 2;

            foreach ($levelNodes as $index => $node) {
                $positions[$node['id']] = [
                    'x' => $offsetX + $index * ($width + $hSpacing),
                    'y' => $startY + $depth * ($height + $vSpacing),
                ];
            }
        }

        return $positions;
    }

    protected function getNodeColor(array $node): array
    {
        if ($node['is_orphan'] ?? false) {
            return $this->hexToRgb('#8B5CF6');
        }

        $httpStatus = $node['http_status'] ?? 200;

        if ($httpStatus >= 400) {
            return $this->hexToRgb('#EF4444');
        }

        if ($httpStatus >= 300) {
            return $this->hexToRgb('#F59E0B');
        }

        return $this->hexToRgb('#10B981');
    }

    protected function getLinkColor(array $link): array
    {
        if ($link['is_external'] ?? false) {
            return $this->hexToRgb('#6B7280');
        }

        return match ($link['type'] ?? 'content') {
            'navigation' => $this->hexToRgb('#3B82F6'),
            'footer' => $this->hexToRgb('#6B7280'),
            'sidebar' => $this->hexToRgb('#8B5CF6'),
            default => $this->hexToRgb('#10B981'),
        };
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            'r' => hexdec(substr($hex, 0, 2)) / 255,
            'g' => hexdec(substr($hex, 2, 2)) / 255,
            'b' => hexdec(substr($hex, 4, 2)) / 255,
            'a' => 1,
        ];
    }

    protected function darkenColor(array $color, float $amount): array
    {
        return [
            'r' => max(0, $color['r'] - $amount),
            'g' => max(0, $color['g'] - $amount),
            'b' => max(0, $color['b'] - $amount),
            'a' => $color['a'],
        ];
    }
}
