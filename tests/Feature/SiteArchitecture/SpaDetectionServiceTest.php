<?php

use App\Services\Architecture\SpaDetectionService;

beforeEach(function () {
    $this->service = new SpaDetectionService;
});

describe('SpaDetectionService', function () {
    it('detects React applications', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/static/js/react.production.min.js"></script>
        </head>
        <body>
            <div id="root" data-reactroot></div>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('react');
        expect($result['requires_js_rendering'])->toBeTrue();
    });

    it('detects Vue applications', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/js/vue.min.js"></script>
        </head>
        <body>
            <div id="app" v-cloak></div>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('vue');
    });

    it('detects Angular applications', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/scripts/zone.js"></script>
        </head>
        <body>
            <app-root _nghost-abc></app-root>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('angular');
    });

    it('detects Next.js applications', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/_next/static/chunks/main.js"></script>
        </head>
        <body>
            <div id="__next">
                <h1>Hello World</h1>
                <p>This is a Next.js page with plenty of SSR content that should be visible.</p>
            </div>
            <script id="__NEXT_DATA__" type="application/json">{"page":"/","query":{}}</script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('next');
        // Next.js detected - may or may not require JS rendering depending on content length
    });

    it('detects Nuxt applications', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/_nuxt/app.js"></script>
        </head>
        <body>
            <div id="__nuxt" data-n-head></div>
            <script>window.__NUXT__={}</script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('nuxt');
    });

    it('detects Svelte applications', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/build/bundle.js"></script>
        </head>
        <body>
            <div class="svelte-1a2b3c4">Content</div>
            <script>window.__svelte={}</script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('svelte');
    });

    it('detects generic SPA indicators', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/bundle.js"></script>
            <script src="/vendor.js"></script>
            <script src="/main.js"></script>
            <script src="/polyfills.js"></script>
        </head>
        <body>
            <div id="root"></div>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
    });

    it('detects webpack bundler', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <div id="app"></div>
            <script>webpackJsonp=[];</script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
    });

    it('returns false for traditional HTML pages', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <title>Traditional Page</title>
        </head>
        <body>
            <header>
                <nav>
                    <a href="/">Home</a>
                    <a href="/about">About</a>
                </nav>
            </header>
            <main>
                <h1>Welcome</h1>
                <p>This is a traditional server-rendered page with lots of content.</p>
                <p>More content here to make it substantial.</p>
                <p>Even more content to prove it's a real page.</p>
            </main>
            <footer>
                <p>Copyright 2024</p>
            </footer>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeFalse();
        expect($result['frameworks'])->toBeEmpty();
        expect($result['requires_js_rendering'])->toBeFalse();
    });

    it('returns confidence score', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <div id="root" data-reactroot></div>
            <script src="/react.production.min.js"></script>
            <script>window.React={}</script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['confidence'])->toBeGreaterThan(0);
        expect($result['confidence'])->toBeLessThanOrEqual(1);
    });

    it('handles empty HTML', function () {
        $result = $this->service->detect('');

        expect($result['is_spa'])->toBeFalse();
        expect($result['frameworks'])->toBeEmpty();
    });

    it('handles malformed HTML gracefully', function () {
        $html = '<html><body><div id="app"><script>broken';

        $result = $this->service->detect($html);

        expect($result)->toHaveKeys(['is_spa', 'frameworks', 'confidence', 'requires_js_rendering']);
    });

    it('detects multiple frameworks', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <body>
            <div data-reactroot></div>
            <div v-cloak></div>
            <script src="/react.js"></script>
            <script src="/vue.js"></script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['frameworks'])->toContain('react');
        expect($result['frameworks'])->toContain('vue');
    });

    it('returns known frameworks list', function () {
        $frameworks = $this->service->getKnownFrameworks();

        expect($frameworks)->toContain('react');
        expect($frameworks)->toContain('vue');
        expect($frameworks)->toContain('angular');
        expect($frameworks)->toContain('svelte');
        expect($frameworks)->toContain('next');
        expect($frameworks)->toContain('nuxt');
    });

    it('SSR framework with content does not require JS rendering', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/_next/static/chunks/main.js"></script>
        </head>
        <body>
            <div id="__next">
                <header><nav>Navigation here</nav></header>
                <main>
                    <h1>Server Rendered Content</h1>
                    <p>This page has substantial content already rendered by the server.</p>
                    <p>More paragraphs of real content that users can read.</p>
                    <p>Additional content making this a fully rendered page.</p>
                    <article>
                        <h2>Article Title</h2>
                        <p>Article content with many words to read.</p>
                    </article>
                </main>
                <footer>Footer content</footer>
            </div>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['requires_js_rendering'])->toBeFalse();
    });

    it('CSR framework with empty root requires JS rendering', function () {
        $html = <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <script src="/bundle.js"></script>
        </head>
        <body>
            <div id="root" data-reactroot></div>
            <script src="/react.production.min.js"></script>
        </body>
        </html>
        HTML;

        $result = $this->service->detect($html);

        expect($result['is_spa'])->toBeTrue();
        expect($result['requires_js_rendering'])->toBeTrue();
    });
});
