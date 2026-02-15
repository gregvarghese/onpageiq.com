<?php

use App\Jobs\Architecture\ExportArchitectureJob;
use App\Models\ArchitectureLink;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Services\Architecture\Export\FigmaExportService;
use App\Services\Architecture\Export\MermaidExportService;
use App\Services\Architecture\Export\PdfExportService;
use App\Services\Architecture\Export\SvgExportService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->project = Project::factory()->create(['name' => 'Test Project']);
    $this->architecture = SiteArchitecture::factory()->create([
        'project_id' => $this->project->id,
        'total_nodes' => 5,
        'total_links' => 4,
        'max_depth' => 2,
    ]);

    // Create test nodes using for() to properly associate with architecture
    $this->homeNode = ArchitectureNode::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'url' => 'https://example.com/',
            'path' => '/',
            'title' => 'Home',
            'depth' => 0,
            'http_status' => 200,
            'inbound_count' => 0,
            'outbound_count' => 3,
        ]);

    $this->aboutNode = ArchitectureNode::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'url' => 'https://example.com/about',
            'path' => '/about',
            'title' => 'About Us',
            'depth' => 1,
            'http_status' => 200,
            'inbound_count' => 2,
            'outbound_count' => 1,
        ]);

    $this->contactNode = ArchitectureNode::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'url' => 'https://example.com/contact',
            'path' => '/contact',
            'title' => 'Contact',
            'depth' => 1,
            'http_status' => 200,
            'inbound_count' => 1,
            'outbound_count' => 0,
        ]);

    $this->errorNode = ArchitectureNode::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'url' => 'https://example.com/broken',
            'path' => '/broken',
            'title' => 'Broken Page',
            'depth' => 2,
            'http_status' => 404,
            'inbound_count' => 1,
            'outbound_count' => 0,
        ]);

    $this->orphanNode = ArchitectureNode::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'url' => 'https://example.com/orphan',
            'path' => '/orphan',
            'title' => 'Orphan Page',
            'depth' => 1,
            'http_status' => 200,
            'is_orphan' => true,
            'inbound_count' => 0,
            'outbound_count' => 0,
        ]);

    // Create links
    ArchitectureLink::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'source_node_id' => $this->homeNode->id,
            'target_node_id' => $this->aboutNode->id,
            'link_type' => 'navigation',
        ]);

    ArchitectureLink::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'source_node_id' => $this->homeNode->id,
            'target_node_id' => $this->contactNode->id,
            'link_type' => 'navigation',
        ]);

    ArchitectureLink::factory()
        ->for($this->architecture, 'siteArchitecture')
        ->create([
            'source_node_id' => $this->aboutNode->id,
            'target_node_id' => $this->homeNode->id,
            'link_type' => 'content',
        ]);
});

// SVG Export Tests
describe('SVG Export Service', function () {
    it('generates valid SVG content', function () {
        $service = new SvgExportService($this->architecture);
        $content = $service->generate();

        expect($content)->toContain('<?xml version="1.0"')
            ->and($content)->toContain('<svg xmlns="http://www.w3.org/2000/svg"')
            ->and($content)->toContain('</svg>');
    });

    it('includes nodes in SVG', function () {
        $service = new SvgExportService($this->architecture);
        $content = $service->generate();

        expect($content)->toContain('class="nodes"')
            ->and($content)->toContain('Home')
            ->and($content)->toContain('About Us');
    });

    it('includes links in SVG', function () {
        $service = new SvgExportService($this->architecture);
        $content = $service->generate();

        expect($content)->toContain('class="links"')
            ->and($content)->toContain('<line class="link');
    });

    it('includes legend when enabled', function () {
        $service = new SvgExportService($this->architecture, ['include_legend' => true]);
        $content = $service->generate();

        expect($content)->toContain('class="legend"')
            ->and($content)->toContain('Legend');
    });

    it('excludes legend when disabled', function () {
        $service = new SvgExportService($this->architecture, ['include_legend' => false]);
        $content = $service->generate();

        expect($content)->not->toContain('class="legend"');
    });

    it('includes metadata box when enabled', function () {
        $service = new SvgExportService($this->architecture, ['include_metadata' => true]);
        $content = $service->generate();

        expect($content)->toContain('class="metadata"')
            ->and($content)->toContain('Test Project');
    });

    it('applies status-based colors', function () {
        $service = new SvgExportService($this->architecture, ['include_errors' => true]);
        $content = $service->generate();

        expect($content)->toContain('status-ok')
            ->and($content)->toContain('status-error');
    });

    it('returns correct extension', function () {
        $service = new SvgExportService($this->architecture);
        expect($service->getExtension())->toBe('svg');
    });

    it('returns correct MIME type', function () {
        $service = new SvgExportService($this->architecture);
        expect($service->getMimeType())->toBe('image/svg+xml');
    });

    it('generates filename with project name and date', function () {
        $service = new SvgExportService($this->architecture);
        $filename = $service->getFilename();

        expect($filename)->toContain('Test_Project')
            ->and($filename)->toContain(now()->format('Y-m-d'))
            ->and($filename)->toEndWith('.svg');
    });
});

// Mermaid Export Tests
describe('Mermaid Export Service', function () {
    it('generates flowchart diagram', function () {
        $service = new MermaidExportService($this->architecture, ['diagram_type' => 'flowchart']);
        $content = $service->generate();

        expect($content)->toStartWith('flowchart TB')
            ->and($content)->toContain('classDef ok')
            ->and($content)->toContain('classDef error');
    });

    it('generates mindmap diagram', function () {
        $service = new MermaidExportService($this->architecture, ['diagram_type' => 'mindmap']);
        $content = $service->generate();

        expect($content)->toStartWith('mindmap')
            ->and($content)->toContain('root((Site Architecture))');
    });

    it('generates graph diagram', function () {
        $service = new MermaidExportService($this->architecture, ['diagram_type' => 'graph']);
        $content = $service->generate();

        expect($content)->toStartWith('graph TB');
    });

    it('supports different directions', function () {
        $serviceLR = new MermaidExportService($this->architecture, ['direction' => 'LR']);
        $serviceBT = new MermaidExportService($this->architecture, ['direction' => 'BT']);

        expect($serviceLR->generate())->toContain('flowchart LR')
            ->and($serviceBT->generate())->toContain('flowchart BT');
    });

    it('includes node definitions', function () {
        $service = new MermaidExportService($this->architecture);
        $content = $service->generate();

        expect($content)->toContain('["Home"]')
            ->and($content)->toContain('["About Us"]');
    });

    it('includes link definitions', function () {
        $service = new MermaidExportService($this->architecture);
        $content = $service->generate();

        expect($content)->toContain('-->');
    });

    it('groups by depth when enabled', function () {
        $service = new MermaidExportService($this->architecture, ['group_by_depth' => true]);
        $content = $service->generate();

        expect($content)->toContain('subgraph depth0')
            ->and($content)->toContain('subgraph depth1');
    });

    it('truncates long labels', function () {
        // Create a node with a very long title
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'title' => 'This is a very long page title that should be truncated',
            'path' => '/long-title',
            'depth' => 1,
            'http_status' => 200,
        ]);

        $service = new MermaidExportService($this->architecture, ['max_label_length' => 20]);
        $content = $service->generate();

        expect($content)->toContain('...');
    });

    it('returns correct extension', function () {
        $service = new MermaidExportService($this->architecture);
        expect($service->getExtension())->toBe('mmd');
    });

    it('returns correct MIME type', function () {
        $service = new MermaidExportService($this->architecture);
        expect($service->getMimeType())->toBe('text/plain');
    });
});

// Figma Export Tests
describe('Figma Export Service', function () {
    it('generates valid JSON structure', function () {
        $service = new FigmaExportService($this->architecture);
        $content = $service->generate();
        $data = json_decode($content, true);

        expect($data)->toBeArray()
            ->and($data)->toHaveKey('name')
            ->and($data)->toHaveKey('document')
            ->and($data)->toHaveKey('components')
            ->and($data)->toHaveKey('styles');
    });

    it('includes project name in document', function () {
        $service = new FigmaExportService($this->architecture);
        $content = $service->generate();
        $data = json_decode($content, true);

        expect($data['name'])->toContain('Test Project');
    });

    it('includes canvas with nodes', function () {
        $service = new FigmaExportService($this->architecture);
        $content = $service->generate();
        $data = json_decode($content, true);

        $canvas = $data['document']['children'][0];
        expect($canvas['type'])->toBe('CANVAS')
            ->and($canvas['name'])->toBe('Site Architecture');
    });

    it('generates node frames with correct structure', function () {
        $service = new FigmaExportService($this->architecture);
        $content = $service->generate();
        $data = json_decode($content, true);

        $canvas = $data['document']['children'][0];
        $nodeFrames = collect($canvas['children'])->filter(fn ($c) => str_starts_with($c['id'] ?? '', 'node_'));

        expect($nodeFrames->count())->toBeGreaterThan(0);

        $firstNode = $nodeFrames->first();
        expect($firstNode)->toHaveKey('type')
            ->and($firstNode['type'])->toBe('FRAME')
            ->and($firstNode)->toHaveKey('fills')
            ->and($firstNode)->toHaveKey('strokes');
    });

    it('includes connections when enabled', function () {
        $service = new FigmaExportService($this->architecture, ['include_connections' => true]);
        $content = $service->generate();
        $data = json_decode($content, true);

        $canvas = $data['document']['children'][0];
        $connections = collect($canvas['children'])->filter(fn ($c) => str_starts_with($c['id'] ?? '', 'link_'));

        expect($connections->count())->toBeGreaterThan(0);
    });

    it('excludes connections when disabled', function () {
        $service = new FigmaExportService($this->architecture, ['include_connections' => false]);
        $content = $service->generate();
        $data = json_decode($content, true);

        $canvas = $data['document']['children'][0];
        $connections = collect($canvas['children'])->filter(fn ($c) => str_starts_with($c['id'] ?? '', 'link_'));

        expect($connections->count())->toBe(0);
    });

    it('includes legend', function () {
        $service = new FigmaExportService($this->architecture);
        $content = $service->generate();
        $data = json_decode($content, true);

        $canvas = $data['document']['children'][0];
        $legend = collect($canvas['children'])->firstWhere('id', 'legend_frame');

        expect($legend)->not->toBeNull()
            ->and($legend['name'])->toBe('Legend');
    });

    it('includes title frame', function () {
        $service = new FigmaExportService($this->architecture);
        $content = $service->generate();
        $data = json_decode($content, true);

        $canvas = $data['document']['children'][0];
        $title = collect($canvas['children'])->firstWhere('id', 'title_frame');

        expect($title)->not->toBeNull();
    });

    it('returns correct extension', function () {
        $service = new FigmaExportService($this->architecture);
        expect($service->getExtension())->toBe('fig.json');
    });

    it('returns correct MIME type', function () {
        $service = new FigmaExportService($this->architecture);
        expect($service->getMimeType())->toBe('application/json');
    });
});

// PDF Export Tests
describe('PDF Export Service', function () {
    it('generates HTML content', function () {
        $service = new PdfExportService($this->architecture);
        $html = $service->generateHtml();

        expect($html)->toContain('<!DOCTYPE html')
            ->and($html)->toContain('Test Project')
            ->and($html)->toContain('Health Score');
    });

    it('calculates statistics correctly', function () {
        $service = new PdfExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateStatistics');

        $nodes = [
            ['http_status' => 200, 'depth' => 0, 'is_orphan' => false, 'inbound_count' => 5, 'outbound_count' => 3],
            ['http_status' => 200, 'depth' => 1, 'is_orphan' => false, 'inbound_count' => 3, 'outbound_count' => 2],
            ['http_status' => 404, 'depth' => 2, 'is_orphan' => false, 'inbound_count' => 1, 'outbound_count' => 0],
            ['http_status' => 200, 'depth' => 1, 'is_orphan' => true, 'inbound_count' => 0, 'outbound_count' => 1],
        ];
        $links = [[], [], []];

        $stats = $method->invoke($service, $nodes, $links);

        expect($stats['total_pages'])->toBe(4)
            ->and($stats['ok_pages'])->toBe(3)
            ->and($stats['error_pages'])->toBe(1)
            ->and($stats['orphan_pages'])->toBe(1)
            ->and($stats['max_depth'])->toBe(2);
    });

    it('generates recommendations', function () {
        $service = new PdfExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('generateRecommendations');

        $nodes = [
            ['http_status' => 404, 'is_orphan' => false, 'outbound_count' => 0, 'is_deep' => false],
            ['http_status' => 200, 'is_orphan' => true, 'outbound_count' => 1, 'is_deep' => false],
        ];
        $links = [];

        $recommendations = $method->invoke($service, $nodes, $links);

        expect($recommendations)->toBeArray()
            ->and(count($recommendations))->toBeGreaterThan(0);

        $hasErrorRec = collect($recommendations)->contains(fn ($r) => str_contains($r['title'], 'Broken'));
        $hasOrphanRec = collect($recommendations)->contains(fn ($r) => str_contains($r['title'], 'Orphan'));

        expect($hasErrorRec)->toBeTrue()
            ->and($hasOrphanRec)->toBeTrue();
    });

    it('calculates health score', function () {
        $service = new PdfExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('calculateHealthScore');

        // All healthy nodes
        $healthyNodes = [
            ['http_status' => 200, 'is_orphan' => false, 'is_deep' => false, 'outbound_count' => 2, 'inbound_count' => 3],
            ['http_status' => 200, 'is_orphan' => false, 'is_deep' => false, 'outbound_count' => 1, 'inbound_count' => 3],
        ];
        $healthyScore = $method->invoke($service, $healthyNodes, []);

        // Nodes with issues
        $unhealthyNodes = [
            ['http_status' => 404, 'is_orphan' => false, 'is_deep' => false, 'outbound_count' => 0, 'inbound_count' => 1],
            ['http_status' => 200, 'is_orphan' => true, 'is_deep' => true, 'outbound_count' => 0, 'inbound_count' => 0],
        ];
        $unhealthyScore = $method->invoke($service, $unhealthyNodes, []);

        expect($healthyScore)->toBeGreaterThan($unhealthyScore)
            ->and($healthyScore)->toBeLessThanOrEqual(100)
            ->and($unhealthyScore)->toBeGreaterThanOrEqual(0);
    });

    it('returns correct extension', function () {
        $service = new PdfExportService($this->architecture);
        expect($service->getExtension())->toBe('pdf');
    });

    it('returns correct MIME type', function () {
        $service = new PdfExportService($this->architecture);
        expect($service->getMimeType())->toBe('application/pdf');
    });
});

// Export Job Tests
describe('Export Architecture Job', function () {
    it('dispatches job with correct parameters', function () {
        Queue::fake();

        ExportArchitectureJob::dispatch(
            $this->architecture,
            'svg',
            ['width' => 1200, 'height' => 800]
        );

        Queue::assertPushed(ExportArchitectureJob::class, function ($job) {
            return $job->architecture->id === $this->architecture->id
                && $job->format === 'svg'
                && $job->options['width'] === 1200;
        });
    });

    it('has correct job tags', function () {
        $job = new ExportArchitectureJob($this->architecture, 'svg');
        $tags = $job->tags();

        expect($tags)->toContain('export')
            ->and($tags)->toContain('architecture:'.$this->architecture->id)
            ->and($tags)->toContain('format:svg');
    });

    it('has unique job ID', function () {
        $job = new ExportArchitectureJob($this->architecture, 'svg');
        $uniqueId = $job->uniqueId();

        expect($uniqueId)->toBe('export-architecture-'.$this->architecture->id.'-svg');
    });

    it('stores export file on execution', function () {
        Storage::fake('local');

        $job = new ExportArchitectureJob($this->architecture, 'svg');
        $job->handle();

        Storage::disk('local')->assertExists('exports/architecture/'.$this->architecture->id);
    });
});

// Common Export Options Tests
describe('Export Common Options', function () {
    it('excludes error pages by default', function () {
        $service = new SvgExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getNodes');
        $nodes = $method->invoke($service);

        $errorNodes = collect($nodes)->filter(fn ($n) => ($n['http_status'] ?? 200) >= 400);
        expect($errorNodes->count())->toBe(0);
    });

    it('includes error pages when option enabled', function () {
        $service = new SvgExportService($this->architecture, ['include_errors' => true]);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getNodes');
        $nodes = $method->invoke($service);

        $errorNodes = collect($nodes)->filter(fn ($n) => ($n['http_status'] ?? 200) >= 400);
        expect($errorNodes->count())->toBeGreaterThan(0);
    });

    it('excludes external links by default', function () {
        // Create external link
        ArchitectureLink::factory()
            ->for($this->architecture, 'siteArchitecture')
            ->create([
                'source_node_id' => $this->homeNode->id,
                'target_node_id' => null,
                'target_url' => 'https://external.com/page',
                'is_external' => true,
                'external_domain' => 'external.com',
            ]);

        $service = new SvgExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getLinks');
        $links = $method->invoke($service);

        $externalLinks = collect($links)->filter(fn ($l) => $l['is_external'] ?? false);
        expect($externalLinks->count())->toBe(0);
    });

    it('gets metadata from architecture', function () {
        $service = new SvgExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getMetadata');
        $metadata = $method->invoke($service);

        expect($metadata)->toHaveKey('project_name')
            ->and($metadata)->toHaveKey('exported_at')
            ->and($metadata)->toHaveKey('total_nodes')
            ->and($metadata)->toHaveKey('total_links')
            ->and($metadata['project_name'])->toBe('Test Project');
    });

    it('generates title from path', function () {
        $service = new SvgExportService($this->architecture);

        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('getTitleFromPath');

        expect($method->invoke($service, '/'))->toBe('Home')
            ->and($method->invoke($service, '/about-us'))->toBe('About Us')
            ->and($method->invoke($service, '/blog/my-post.html'))->toBe('My Post')
            ->and($method->invoke($service, '/products/category/item'))->toBe('Item');
    });
});
