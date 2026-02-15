<?php

namespace App\Services\Architecture\Export;

use App\Models\SiteArchitecture;

abstract class ExportService
{
    protected SiteArchitecture $architecture;

    protected array $options = [];

    public function __construct(SiteArchitecture $architecture, array $options = [])
    {
        $this->architecture = $architecture;
        $this->options = array_merge($this->defaultOptions(), $options);
    }

    /**
     * Get default options for this export type.
     */
    abstract protected function defaultOptions(): array;

    /**
     * Generate the export content.
     */
    abstract public function generate(): string;

    /**
     * Get the file extension for this export type.
     */
    abstract public function getExtension(): string;

    /**
     * Get the MIME type for this export.
     */
    abstract public function getMimeType(): string;

    /**
     * Get the suggested filename.
     */
    public function getFilename(): string
    {
        $projectName = $this->architecture->project?->name ?? 'architecture';
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectName);
        $date = now()->format('Y-m-d');

        return "{$safeName}-{$date}.{$this->getExtension()}";
    }

    /**
     * Get nodes for export.
     */
    protected function getNodes(): array
    {
        $query = $this->architecture->nodes();

        if (! ($this->options['include_errors'] ?? false)) {
            $query->where('http_status', '>=', 200)->where('http_status', '<', 300);
        }

        return $query->get()->map(fn ($node) => [
            'id' => $node->id,
            'url' => $node->url,
            'path' => $node->path,
            'title' => $node->title ?? $this->getTitleFromPath($node->path),
            'depth' => $node->depth,
            'status' => $node->status?->value ?? 'ok',
            'http_status' => $node->http_status,
            'inbound_count' => $node->inbound_count,
            'outbound_count' => $node->outbound_count,
            'link_equity_score' => $node->link_equity_score,
            'is_orphan' => $node->is_orphan,
            'is_deep' => $node->is_deep,
        ])->toArray();
    }

    /**
     * Get links for export.
     */
    protected function getLinks(): array
    {
        $query = $this->architecture->links()->with(['sourceNode', 'targetNode']);

        if (! ($this->options['include_external'] ?? false)) {
            $query->where('is_external', false);
        }

        return $query->get()->map(fn ($link) => [
            'source' => $link->source_node_id,
            'target' => $link->target_node_id,
            'type' => $link->getEffectiveLinkType()->value,
            'anchor_text' => $link->anchor_text,
            'is_external' => $link->is_external,
        ])->toArray();
    }

    /**
     * Get metadata for export.
     */
    protected function getMetadata(): array
    {
        return [
            'project_name' => $this->architecture->project?->name ?? 'Unknown',
            'exported_at' => now()->toIso8601String(),
            'total_nodes' => $this->architecture->total_nodes,
            'total_links' => $this->architecture->total_links,
            'max_depth' => $this->architecture->max_depth,
            'orphan_count' => $this->architecture->orphan_count,
            'error_count' => $this->architecture->error_count,
            'last_crawled_at' => $this->architecture->last_crawled_at?->toIso8601String(),
        ];
    }

    /**
     * Get title from path.
     */
    protected function getTitleFromPath(?string $path): string
    {
        if (empty($path) || $path === '/') {
            return 'Home';
        }

        $segments = explode('/', trim($path, '/'));
        $lastSegment = end($segments);

        $lastSegment = preg_replace('/\.(html?|php|aspx?)$/i', '', $lastSegment);
        $lastSegment = str_replace(['-', '_'], ' ', $lastSegment);

        return ucwords($lastSegment);
    }
}
