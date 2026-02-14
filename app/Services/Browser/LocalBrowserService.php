<?php

namespace App\Services\Browser;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use RuntimeException;

class LocalBrowserService implements BrowserServiceInterface
{
    protected int $timeout;

    protected ?string $executablePath;

    public function __construct()
    {
        $this->timeout = config('onpageiq.browser.local.timeout', 30000);
        $this->executablePath = config('onpageiq.browser.local.executable_path');
    }

    /**
     * Render a page and extract its content.
     *
     * @return array{content: string, title: string, meta: array, html: string}
     */
    public function renderPage(string $url): array
    {
        $script = $this->getExtractScript($url);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'content' => '',
            'title' => '',
            'meta' => [],
            'html' => '',
        ];
    }

    /**
     * Take a screenshot of the page or specific element.
     */
    public function screenshot(string $url, ?string $selector = null): string
    {
        $filename = 'public/screenshots/'.uniqid('screenshot_').'.png';
        $path = storage_path('app/'.$filename);

        $script = $this->getScreenshotScript($url, $path, $selector);
        $this->runPlaywrightScript($script);

        return $filename;
    }

    /**
     * Take a full-page screenshot.
     */
    public function fullPageScreenshot(string $url): string
    {
        $filename = 'public/screenshots/'.uniqid('fullpage_').'.png';
        $path = storage_path('app/'.$filename);

        $script = $this->getFullPageScreenshotScript($url, $path);
        $this->runPlaywrightScript($script);

        return $filename;
    }

    /**
     * Check if a URL is reachable and returns HTML content.
     */
    public function isReachable(string $url): bool
    {
        try {
            $request = Http::timeout(10);

            // Disable SSL verification for local development domains
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && (str_ends_with($host, '.test') || str_ends_with($host, '.local') || $host === 'localhost')) {
                $request = $request->withoutVerifying();
            }

            $response = $request->head($url);

            if (! $response->successful()) {
                return false;
            }

            $contentType = $response->header('Content-Type') ?? '';

            return str_contains($contentType, 'text/html');
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get the page's HTML content.
     */
    public function getHtml(string $url): string
    {
        $script = $this->getHtmlScript($url);

        return $this->runPlaywrightScript($script);
    }

    /**
     * Check if HTTPS errors should be ignored for this URL.
     */
    protected function shouldIgnoreHttpsErrors(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return $host && (
            str_ends_with($host, '.test') ||
            str_ends_with($host, '.local') ||
            $host === 'localhost'
        );
    }

    /**
     * Run a Playwright script and return the output.
     */
    protected function runPlaywrightScript(string $script): string
    {
        $scriptPath = storage_path('app/temp/'.uniqid('playwright_').'.cjs');

        if (! is_dir(dirname($scriptPath))) {
            mkdir(dirname($scriptPath), 0755, true);
        }

        file_put_contents($scriptPath, $script);

        try {
            $result = Process::timeout($this->timeout / 1000)
                ->run(['node', $scriptPath]);

            if (! $result->successful()) {
                throw new RuntimeException('Playwright script failed: '.$result->errorOutput());
            }

            return $result->output();
        } finally {
            @unlink($scriptPath);
        }
    }

    /**
     * Get the extraction script.
     */
    protected function getExtractScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        const result = await page.evaluate(() => {
            // Remove scripts, styles, and hidden elements
            const elementsToRemove = document.querySelectorAll('script, style, noscript, [hidden]');
            elementsToRemove.forEach(el => el.remove());

            const getMeta = () => {
                const metas = {};
                document.querySelectorAll('meta').forEach(meta => {
                    const name = meta.getAttribute('name') || meta.getAttribute('property');
                    const content = meta.getAttribute('content');
                    if (name && content) {
                        metas[name] = content;
                    }
                });
                return metas;
            };

            return {
                content: document.body.innerText,
                title: document.title,
                meta: getMeta(),
                html: document.documentElement.outerHTML
            };
        });

        console.log(JSON.stringify(result));
    } catch (error) {
        console.log(JSON.stringify({ error: error.message }));
    } finally {
        await browser.close();
    }
})();
JS;
    }

    /**
     * Get the screenshot script.
     */
    protected function getScreenshotScript(string $url, string $path, ?string $selector): string
    {
        $escapedUrl = addslashes($url);
        $escapedPath = addslashes($path);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';
        $selectorCode = $selector
            ? "await page.locator('".addslashes($selector)."').screenshot({ path: '{$escapedPath}' });"
            : "await page.screenshot({ path: '{$escapedPath}' });";

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });
        {$selectorCode}
        console.log('done');
    } catch (error) {
        console.log('error: ' + error.message);
    } finally {
        await browser.close();
    }
})();
JS;
    }

    /**
     * Get the full-page screenshot script.
     */
    protected function getFullPageScreenshotScript(string $url, string $path): string
    {
        $escapedUrl = addslashes($url);
        $escapedPath = addslashes($path);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });
        await page.screenshot({ path: '{$escapedPath}', fullPage: true });
        console.log('done');
    } catch (error) {
        console.log('error: ' + error.message);
    } finally {
        await browser.close();
    }
})();
JS;
    }

    /**
     * Take a screenshot with a highlighted element.
     *
     * @param  array{top: int, left: int, width: int, height: int}|null  $boundingBox
     */
    public function screenshotWithHighlight(string $url, string $selector, ?array $boundingBox = null): string
    {
        $filename = 'public/screenshots/'.uniqid('highlight_').'.png';
        $path = storage_path('app/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $script = $this->getHighlightScreenshotScript($url, $path, $selector, $boundingBox);
        $this->runPlaywrightScript($script);

        return $filename;
    }

    /**
     * Get the highlight screenshot script.
     *
     * @param  array{top: int, left: int, width: int, height: int}|null  $boundingBox
     */
    protected function getHighlightScreenshotScript(string $url, string $path, string $selector, ?array $boundingBox): string
    {
        $escapedUrl = addslashes($url);
        $escapedPath = addslashes($path);
        $escapedSelector = addslashes($selector);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        $highlightStyle = 'outline: 3px solid #ef4444; outline-offset: 2px; background-color: rgba(239, 68, 68, 0.1);';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Try to find and highlight the element
        const element = await page.locator('{$escapedSelector}').first();

        if (await element.count() > 0) {
            // Scroll element into view
            await element.scrollIntoViewIfNeeded();

            // Add highlight style
            await element.evaluate(el => {
                el.style.outline = '3px solid #ef4444';
                el.style.outlineOffset = '2px';
                el.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
            });

            // Wait for styles to apply
            await page.waitForTimeout(100);

            // Get bounding box and add padding for context
            const box = await element.boundingBox();
            if (box) {
                const padding = 50;
                const clip = {
                    x: Math.max(0, box.x - padding),
                    y: Math.max(0, box.y - padding),
                    width: box.width + (padding * 2),
                    height: box.height + (padding * 2)
                };

                await page.screenshot({
                    path: '{$escapedPath}',
                    clip: clip
                });
            } else {
                await element.screenshot({ path: '{$escapedPath}' });
            }
        } else {
            // Element not found, take viewport screenshot
            await page.screenshot({ path: '{$escapedPath}' });
        }

        console.log('done');
    } catch (error) {
        console.log('error: ' + error.message);
        // Take a fallback viewport screenshot
        try {
            await page.screenshot({ path: '{$escapedPath}' });
        } catch (e) {}
    } finally {
        await browser.close();
    }
})();
JS;
    }

    /**
     * Get the HTML extraction script.
     */
    protected function getHtmlScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });
        console.log(await page.content());
    } catch (error) {
        console.log('');
    } finally {
        await browser.close();
    }
})();
JS;
    }
}
