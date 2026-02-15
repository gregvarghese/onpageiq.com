<?php

use App\Enums\LinkType;
use App\Services\Architecture\LinkClassificationService;

beforeEach(function () {
    $this->service = new LinkClassificationService;
});

describe('LinkClassificationService', function () {
    it('classifies navigation links in nav element', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <nav>
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
            </nav>
            <article>
                <a href="/article">Read More</a>
            </article>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toHaveCount(3);

        $navLinks = collect($links)->filter(fn ($l) => $l['type'] === LinkType::Navigation);
        expect($navLinks)->toHaveCount(2);

        // Link inside article without nav/header/footer/aside is classified as Content
        $nonNavLinks = collect($links)->filter(fn ($l) => $l['type'] !== LinkType::Navigation);
        expect($nonNavLinks)->toHaveCount(1);
    });

    it('classifies header links', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <header>
                <a href="/">Logo</a>
                <a href="/login">Login</a>
            </header>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toHaveCount(2);
        expect($links[0]['type'])->toBe(LinkType::Header);
        expect($links[1]['type'])->toBe(LinkType::Header);
    });

    it('classifies footer links', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <footer>
                <a href="/privacy">Privacy Policy</a>
                <a href="/terms">Terms</a>
            </footer>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toHaveCount(2);
        expect($links[0]['type'])->toBe(LinkType::Footer);
        expect($links[1]['type'])->toBe(LinkType::Footer);
    });

    it('classifies sidebar links', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <aside>
                <a href="/related1">Related Post 1</a>
                <a href="/related2">Related Post 2</a>
            </aside>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toHaveCount(2);
        expect($links[0]['type'])->toBe(LinkType::Sidebar);
    });

    it('classifies links by CSS class patterns', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <div class="breadcrumb">
                <a href="/">Home</a>
                <a href="/category">Category</a>
            </div>
            <div class="pagination">
                <a href="/page/2">Next</a>
            </div>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        $breadcrumbLinks = collect($links)->filter(fn ($l) => $l['type'] === LinkType::Breadcrumb);
        expect($breadcrumbLinks)->toHaveCount(2);

        $paginationLinks = collect($links)->filter(fn ($l) => $l['type'] === LinkType::Pagination);
        expect($paginationLinks)->toHaveCount(1);
    });

    it('classifies links by ARIA role', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <div role="navigation">
                <a href="/menu-item">Menu Item</a>
            </div>
            <div role="contentinfo">
                <a href="/footer-link">Footer Link</a>
            </div>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links[0]['type'])->toBe(LinkType::Navigation);
        expect($links[1]['type'])->toBe(LinkType::Footer);
    });

    it('identifies external links', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <a href="https://external.com/page">External</a>
            <a href="/internal">Internal</a>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toHaveCount(2);

        $externalLinks = collect($links)->filter(fn ($l) => $l['type'] === LinkType::External);
        expect($externalLinks)->toHaveCount(1);
    });

    it('detects nofollow attribute', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <a href="/sponsored" rel="nofollow">Sponsored</a>
            <a href="/normal">Normal</a>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links[0]['is_nofollow'])->toBeTrue();
        expect($links[1]['is_nofollow'])->toBeFalse();
    });

    it('extracts anchor text', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <a href="/page">Click here for more info</a>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links[0]['anchor_text'])->toBe('Click here for more info');
    });

    it('resolves relative URLs', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <a href="/absolute-path">Absolute</a>
            <a href="relative-path">Relative</a>
            <a href="../parent">Parent</a>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com/section/page');

        expect($links[0]['href'])->toBe('https://example.com/absolute-path');
        expect($links[1]['href'])->toBe('https://example.com/section/relative-path');
    });

    it('skips javascript and anchor links', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <a href="javascript:void(0)">JS Link</a>
            <a href="#section">Anchor</a>
            <a href="mailto:test@example.com">Email</a>
            <a href="/valid">Valid</a>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toHaveCount(1);
        expect($links[0]['href'])->toBe('https://example.com/valid');
    });

    it('determines position in page', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <header><a href="/header-link">Header</a></header>
            <nav><a href="/nav-link">Nav</a></nav>
            <main><a href="/main-link">Main</a></main>
            <aside><a href="/sidebar-link">Sidebar</a></aside>
            <footer><a href="/footer-link">Footer</a></footer>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        $linksByHref = collect($links)->keyBy('href');

        expect($linksByHref['https://example.com/header-link']['position'])->toBe('header');
        expect($linksByHref['https://example.com/nav-link']['position'])->toBe('nav');
        expect($linksByHref['https://example.com/main-link']['position'])->toBe('main');
        expect($linksByHref['https://example.com/sidebar-link']['position'])->toBe('sidebar');
        expect($linksByHref['https://example.com/footer-link']['position'])->toBe('footer');
    });

    it('handles complex nested structures', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <header>
                <div class="container">
                    <nav class="main-navigation">
                        <ul>
                            <li><a href="/home">Home</a></li>
                            <li><a href="/products">Products</a></li>
                        </ul>
                    </nav>
                </div>
            </header>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        // Should detect as navigation due to nav element
        expect($links)->toHaveCount(2);
        expect($links[0]['type'])->toBe(LinkType::Navigation);
        expect($links[1]['type'])->toBe(LinkType::Navigation);
    });

    it('handles empty HTML gracefully', function () {
        $links = $this->service->classifyLinksInHtml('', 'https://example.com');

        expect($links)->toBeArray()->toBeEmpty();
    });

    it('handles HTML without links', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <p>No links here</p>
        </body>
        </html>
        HTML;

        $links = $this->service->classifyLinksInHtml($html, 'https://example.com');

        expect($links)->toBeArray()->toBeEmpty();
    });
});
