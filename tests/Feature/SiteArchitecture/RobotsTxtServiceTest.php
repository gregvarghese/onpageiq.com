<?php

use App\Services\Architecture\RobotsTxtService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->service = new RobotsTxtService;
    Cache::flush();
});

describe('RobotsTxtService', function () {
    describe('parseRobotsTxt', function () {
        it('parses basic disallow rules', function () {
            $content = <<<'ROBOTS'
User-agent: *
Disallow: /admin/
Disallow: /private/
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            expect($rules['disallow'])->toContain('/admin/');
            expect($rules['disallow'])->toContain('/private/');
            expect($rules['allow'])->toBeEmpty();
        });

        it('parses allow rules', function () {
            $content = <<<'ROBOTS'
User-agent: *
Disallow: /private/
Allow: /private/public/
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            expect($rules['disallow'])->toContain('/private/');
            expect($rules['allow'])->toContain('/private/public/');
        });

        it('parses crawl-delay', function () {
            $content = <<<'ROBOTS'
User-agent: *
Crawl-delay: 10
Disallow: /admin/
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            expect($rules['crawl_delay'])->toBe(10);
        });

        it('parses sitemap directives', function () {
            $content = <<<'ROBOTS'
User-agent: *
Disallow: /admin/

Sitemap: https://example.com/sitemap.xml
Sitemap: https://example.com/sitemap-posts.xml
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            expect($rules['sitemaps'])->toContain('https://example.com/sitemap.xml');
            expect($rules['sitemaps'])->toContain('https://example.com/sitemap-posts.xml');
        });

        it('ignores comments', function () {
            $content = <<<'ROBOTS'
# This is a comment
User-agent: *
Disallow: /admin/ # inline comment
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            expect($rules['disallow'])->toContain('/admin/');
            expect($rules['disallow'])->not->toContain('# inline comment');
        });

        it('handles empty content', function () {
            $rules = $this->service->parseRobotsTxt('');

            expect($rules['disallow'])->toBeEmpty();
            expect($rules['allow'])->toBeEmpty();
            expect($rules['crawl_delay'])->toBeNull();
        });

        it('handles multiple user-agent sections', function () {
            $content = <<<'ROBOTS'
User-agent: Googlebot
Disallow: /google-only/

User-agent: *
Disallow: /private/
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            // The service should pick up wildcard rules
            expect($rules['disallow'])->toContain('/private/');
        });

        it('removes duplicate rules', function () {
            $content = <<<'ROBOTS'
User-agent: *
Disallow: /admin/
Disallow: /admin/
Disallow: /private/
ROBOTS;

            $rules = $this->service->parseRobotsTxt($content);

            expect($rules['disallow'])->toHaveCount(2);
        });
    });

    describe('isAllowed', function () {
        it('allows URLs when no rules exist', function () {
            Http::fake([
                '*/robots.txt' => Http::response('', 404),
            ]);

            expect($this->service->isAllowed('https://example.com/any-page'))->toBeTrue();
        });

        it('disallows URLs matching disallow patterns', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /admin/
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/admin/'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/admin/users'))->toBeFalse();
        });

        it('allows URLs not matching any disallow pattern', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /admin/
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/public'))->toBeTrue();
            expect($this->service->isAllowed('https://example.com/blog/post'))->toBeTrue();
        });

        it('allow overrides disallow for more specific paths', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /private/
Allow: /private/public/
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/private/'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/private/secret'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/private/public/'))->toBeTrue();
            expect($this->service->isAllowed('https://example.com/private/public/page'))->toBeTrue();
        });

        it('handles wildcard patterns', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /*.pdf
Disallow: /search*
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/document.pdf'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/files/doc.pdf'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/search'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/search?q=test'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/document.html'))->toBeTrue();
        });

        it('handles end-of-url pattern', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /exact-match$
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/exact-match'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/exact-match/more'))->toBeTrue();
        });

        it('disallows everything with disallow all', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/'))->toBeFalse();
            expect($this->service->isAllowed('https://example.com/any-page'))->toBeFalse();
        });

        it('allows everything when only allow rules exist', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Allow: /
ROBOTS
                ),
            ]);

            expect($this->service->isAllowed('https://example.com/'))->toBeTrue();
            expect($this->service->isAllowed('https://example.com/any-page'))->toBeTrue();
        });
    });

    describe('caching', function () {
        it('caches robots.txt response', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /admin/
ROBOTS
                ),
            ]);

            // First call
            $this->service->isAllowed('https://example.com/page1');

            // Second call should use cache
            $this->service->isAllowed('https://example.com/page2');

            // Should only have made one HTTP request
            Http::assertSentCount(1);
        });

        it('clears cache when requested', function () {
            Http::fake([
                '*/robots.txt' => Http::response('User-agent: *'),
            ]);

            $this->service->isAllowed('https://example.com/page');
            $this->service->clearCache('https://example.com');
            $this->service->isAllowed('https://example.com/page2');

            Http::assertSentCount(2);
        });
    });

    describe('getCrawlDelay', function () {
        it('returns crawl delay when set', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Crawl-delay: 5
ROBOTS
                ),
            ]);

            expect($this->service->getCrawlDelay('https://example.com'))->toBe(5);
        });

        it('returns null when no crawl delay', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /admin/
ROBOTS
                ),
            ]);

            expect($this->service->getCrawlDelay('https://example.com'))->toBeNull();
        });
    });

    describe('getSitemaps', function () {
        it('returns sitemaps from robots.txt', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /admin/

Sitemap: https://example.com/sitemap.xml
ROBOTS
                ),
            ]);

            $sitemaps = $this->service->getSitemaps('https://example.com');

            expect($sitemaps)->toContain('https://example.com/sitemap.xml');
        });

        it('returns empty array when no sitemaps', function () {
            Http::fake([
                '*/robots.txt' => Http::response(<<<'ROBOTS'
User-agent: *
Disallow: /admin/
ROBOTS
                ),
            ]);

            expect($this->service->getSitemaps('https://example.com'))->toBeEmpty();
        });
    });

    describe('error handling', function () {
        it('allows all when robots.txt returns 404', function () {
            Http::fake([
                '*/robots.txt' => Http::response('', 404),
            ]);

            expect($this->service->isAllowed('https://example.com/admin'))->toBeTrue();
        });

        it('allows all when robots.txt request fails', function () {
            Http::fake([
                '*/robots.txt' => Http::response('', 500),
            ]);

            expect($this->service->isAllowed('https://example.com/admin'))->toBeTrue();
        });

        it('handles timeout gracefully', function () {
            Http::fake([
                '*/robots.txt' => function () {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
                },
            ]);

            expect($this->service->isAllowed('https://example.com/admin'))->toBeTrue();
        });
    });
});
