<?php

use App\Livewire\SiteArchitecture\SitemapPanel;
use App\Models\ArchitectureNode;
use App\Models\Project;
use App\Models\SiteArchitecture;
use App\Models\User;
use App\Services\Architecture\HtmlSitemapService;
use App\Services\Architecture\SitemapGeneratorService;
use App\Services\Architecture\SitemapValidationService;
use App\Services\Architecture\VisualSitemapService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->project = Project::factory()->create([
        'organization_id' => $this->user->organization_id,
    ]);

    $this->architecture = SiteArchitecture::factory()->create([
        'project_id' => $this->project->id,
        'status' => 'ready',
    ]);
});

describe('SitemapGeneratorService', function () {
    it('generates XML sitemap', function () {
        ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/about',
            'http_status' => 200,
            'depth' => 1,
        ]);

        $service = app(SitemapGeneratorService::class);
        $xml = $service->generateXml($this->architecture);

        expect($xml)->toContain('<?xml version="1.0" encoding="UTF-8"?>');
        expect($xml)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        expect($xml)->toContain('<loc>https://example.com/</loc>');
        expect($xml)->toContain('<loc>https://example.com/about</loc>');
        expect($xml)->toContain('<priority>');
        expect($xml)->toContain('<changefreq>');
    });

    it('calculates priority based on depth', function () {
        $homepage = ArchitectureNode::factory()->homepage()->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 0,
        ]);

        $deepPage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'depth' => 5,
        ]);

        $service = app(SitemapGeneratorService::class);

        expect($service->calculatePriority($homepage))->toBe('1.0');
        expect((float) $service->calculatePriority($deepPage))->toBeLessThan(1.0);
    });

    it('calculates change frequency based on content type', function () {
        $homepage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/',
            'depth' => 0,
        ]);

        $blogPost = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/blog/my-post',
            'depth' => 2,
        ]);

        $aboutPage = ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/about',
            'depth' => 1,
        ]);

        $service = app(SitemapGeneratorService::class);

        expect($service->calculateChangeFrequency($homepage, []))->toBe('daily');
        expect($service->calculateChangeFrequency($blogPost, []))->toBe('weekly');
        expect($service->calculateChangeFrequency($aboutPage, []))->toBe('yearly');
    });

    it('excludes non-200 pages from sitemap', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/good',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/redirect',
            'http_status' => 301,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/error',
            'http_status' => 404,
        ]);

        $service = app(SitemapGeneratorService::class);
        $xml = $service->generateXml($this->architecture);

        expect($xml)->toContain('https://example.com/good');
        expect($xml)->not->toContain('https://example.com/redirect');
        expect($xml)->not->toContain('https://example.com/error');
    });

    it('generates sitemap stats', function () {
        ArchitectureNode::factory()->count(5)->create([
            'site_architecture_id' => $this->architecture->id,
            'http_status' => 200,
        ]);

        $service = app(SitemapGeneratorService::class);
        $stats = $service->getStats($this->architecture);

        expect($stats)->toHaveKeys(['total_urls', 'requires_index', 'sitemap_count', 'by_priority', 'by_changefreq']);
        expect($stats['total_urls'])->toBe(5);
        expect($stats['requires_index'])->toBeFalse();
    });

    it('parses sitemap XML', function () {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://example.com/</loc>
    <lastmod>2026-01-01</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
</urlset>
XML;

        $service = app(SitemapGeneratorService::class);
        $parsed = $service->parseSitemap($xml);

        expect($parsed['type'])->toBe('urlset');
        expect($parsed['urls'])->toHaveCount(1);
        expect($parsed['urls'][0]['loc'])->toBe('https://example.com/');
    });
});

describe('VisualSitemapService', function () {
    it('generates hierarchy from nodes', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'path' => '/',
            'http_status' => 200,
            'depth' => 0,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/about',
            'path' => '/about',
            'http_status' => 200,
            'depth' => 1,
        ]);

        $service = app(VisualSitemapService::class);
        $hierarchy = $service->generateHierarchy($this->architecture);

        expect($hierarchy)->not->toBeEmpty();
        expect($hierarchy[0])->toHaveKeys(['id', 'url', 'path', 'title', 'depth', 'children']);
    });

    it('generates sections from nodes', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/blog/post-1',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/blog/post-2',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/about',
            'http_status' => 200,
        ]);

        $service = app(VisualSitemapService::class);
        $sections = $service->generateSections($this->architecture);

        expect($sections)->not->toBeEmpty();

        $blogSection = collect($sections)->firstWhere('name', 'Blog');
        expect($blogSection)->not->toBeNull();
        expect($blogSection['count'])->toBe(2);
    });

    it('generates D3 data format', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'path' => '/',
            'http_status' => 200,
        ]);

        $service = app(VisualSitemapService::class);
        $d3Data = $service->generateD3Data($this->architecture);

        expect($d3Data)->toHaveKeys(['name', 'children']);
        expect($d3Data['name'])->toBe('Site');
    });

    it('generates structure stats', function () {
        ArchitectureNode::factory()->count(3)->create([
            'site_architecture_id' => $this->architecture->id,
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->orphan()->create([
            'site_architecture_id' => $this->architecture->id,
            'http_status' => 200,
        ]);

        $service = app(VisualSitemapService::class);
        $stats = $service->getStructureStats($this->architecture);

        expect($stats)->toHaveKeys(['total_pages', 'total_sections', 'max_depth', 'orphan_pages']);
        expect($stats['total_pages'])->toBe(4);
    });
});

describe('HtmlSitemapService', function () {
    it('generates HTML sitemap', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'path' => '/',
            'title' => 'Home',
            'http_status' => 200,
        ]);

        $service = app(HtmlSitemapService::class);
        $html = $service->generateHtml($this->architecture);

        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('<title>Sitemap</title>');
        expect($html)->toContain('https://example.com/');
    });

    it('generates hierarchical HTML sitemap', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'path' => '/',
            'http_status' => 200,
        ]);

        $service = app(HtmlSitemapService::class);
        $html = $service->generateHierarchicalHtml($this->architecture);

        expect($html)->toContain('<!DOCTYPE html>');
        expect($html)->toContain('sitemap-tree');
    });

    it('generates categorized data', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/blog/post',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'path' => '/privacy',
            'http_status' => 200,
        ]);

        $service = app(HtmlSitemapService::class);
        $categories = $service->generateCategorizedData($this->architecture);

        expect($categories)->not->toBeEmpty();
    });
});

describe('SitemapValidationService', function () {
    it('validates sitemap against architecture', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/about',
            'http_status' => 200,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://example.com/</loc></url>
  <url><loc>https://example.com/about</loc></url>
</urlset>
XML;

        $service = app(SitemapValidationService::class);
        $result = $service->validate($this->architecture, $xml);

        expect($result['valid'])->toBeTrue();
        expect($result['summary']['matching'])->toBe(2);
        expect($result['summary']['extra_in_sitemap'])->toBe(0);
        expect($result['summary']['missing_from_sitemap'])->toBe(0);
    });

    it('detects stale URLs in sitemap', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://example.com/</loc></url>
  <url><loc>https://example.com/deleted-page</loc></url>
</urlset>
XML;

        $service = app(SitemapValidationService::class);
        $result = $service->validate($this->architecture, $xml);

        expect($result['summary']['extra_in_sitemap'])->toBe(1);
        expect($result['extra_urls'])->toContain('https://example.com/deleted-page');
    });

    it('detects missing URLs from sitemap', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/new-page',
            'http_status' => 200,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://example.com/</loc></url>
</urlset>
XML;

        $service = app(SitemapValidationService::class);
        $result = $service->validate($this->architecture, $xml);

        expect($result['summary']['missing_from_sitemap'])->toBe(1);
        expect($result['missing_urls'])->toContain('https://example.com/new-page');
    });

    it('generates validation report', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://example.com/</loc></url>
</urlset>
XML;

        $service = app(SitemapValidationService::class);
        $report = $service->generateReport($this->architecture, $xml);

        expect($report)->toHaveKeys(['generated_at', 'validation_result', 'health_score', 'recommendations']);
    });

    it('handles invalid XML gracefully', function () {
        $service = app(SitemapValidationService::class);
        $result = $service->validate($this->architecture, 'not valid xml');

        expect($result['valid'])->toBeFalse();
        expect($result)->toHaveKey('error');
    });
});

describe('SitemapPanel Component', function () {
    it('renders with no architecture', function () {
        Livewire::test(SitemapPanel::class)
            ->assertStatus(200)
            ->assertSee('No architecture data available');
    });

    it('renders with architecture data', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'http_status' => 200,
        ]);

        Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id])
            ->assertStatus(200)
            ->assertSee('Generate');
    });

    it('switches between tabs', function () {
        Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id])
            ->assertSet('activeTab', 'generate')
            ->call('setTab', 'validate')
            ->assertSet('activeTab', 'validate')
            ->call('setTab', 'visual')
            ->assertSet('activeTab', 'visual');
    });

    it('computes stats', function () {
        ArchitectureNode::factory()->count(3)->create([
            'site_architecture_id' => $this->architecture->id,
            'http_status' => 200,
        ]);

        $component = Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id]);

        $stats = $component->get('stats');
        expect($stats['total_urls'])->toBe(3);
    });

    it('generates XML preview', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        $component = Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id]);

        $xml = $component->get('generatedXml');
        expect($xml)->toContain('<?xml');
        expect($xml)->toContain('https://example.com/');
    });

    it('toggles preview visibility', function () {
        Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id])
            ->assertSet('showPreview', false)
            ->call('togglePreview')
            ->assertSet('showPreview', true)
            ->call('togglePreview')
            ->assertSet('showPreview', false);
    });

    it('changes sitemap format', function () {
        Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id])
            ->assertSet('sitemapFormat', 'xml')
            ->set('sitemapFormat', 'html')
            ->assertSet('sitemapFormat', 'html');
    });

    it('validates sitemap from content', function () {
        ArchitectureNode::factory()->create([
            'site_architecture_id' => $this->architecture->id,
            'url' => 'https://example.com/',
            'http_status' => 200,
        ]);

        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url><loc>https://example.com/</loc></url>
</urlset>
XML;

        Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id])
            ->set('existingSitemapContent', $xml)
            ->call('validateFromContent')
            ->assertSet('validationResult.valid', true);
    });

    it('clears sitemap validation results', function () {
        Livewire::test(SitemapPanel::class, ['architectureId' => $this->architecture->id])
            ->set('validationResult', ['valid' => true])
            ->set('existingSitemapUrl', 'https://example.com/sitemap.xml')
            ->call('clearSitemapValidation')
            ->assertSet('validationResult', null)
            ->assertSet('existingSitemapUrl', '');
    });
});
