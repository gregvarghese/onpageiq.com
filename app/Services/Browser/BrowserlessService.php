<?php

namespace App\Services\Browser;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BrowserlessService implements BrowserServiceInterface
{
    protected string $baseUrl;

    protected ?string $token;

    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('onpageiq.browser.browserless.url', 'https://chrome.browserless.io');
        $this->token = config('onpageiq.browser.browserless.token');
        $this->timeout = config('onpageiq.browser.browserless.timeout', 30000);
    }

    /**
     * Render a page and extract its content.
     *
     * @return array{content: string, title: string, meta: array, html: string}
     */
    public function renderPage(string $url): array
    {
        $response = $this->makeRequest('/content', [
            'url' => $url,
            'waitUntil' => 'networkidle0',
            'gotoOptions' => [
                'timeout' => $this->timeout,
            ],
        ]);

        $html = $response;

        // Parse HTML to extract content
        return $this->parseHtml($html);
    }

    /**
     * Take a screenshot of the page or specific element.
     */
    public function screenshot(string $url, ?string $selector = null): string
    {
        $options = [
            'url' => $url,
            'waitUntil' => 'networkidle0',
            'gotoOptions' => [
                'timeout' => $this->timeout,
            ],
            'options' => [
                'type' => 'png',
                'quality' => config('onpageiq.scanning.screenshot_quality', 80),
            ],
        ];

        if ($selector) {
            $options['selector'] = $selector;
        }

        $imageData = $this->makeRequest('/screenshot', $options, true);

        $filename = 'screenshots/'.uniqid('screenshot_').'.png';
        $path = storage_path('app/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $imageData);

        return $filename;
    }

    /**
     * Take a full-page screenshot.
     */
    public function fullPageScreenshot(string $url): string
    {
        $options = [
            'url' => $url,
            'waitUntil' => 'networkidle0',
            'gotoOptions' => [
                'timeout' => $this->timeout,
            ],
            'options' => [
                'type' => 'png',
                'fullPage' => true,
                'quality' => config('onpageiq.scanning.screenshot_quality', 80),
            ],
        ];

        $imageData = $this->makeRequest('/screenshot', $options, true);

        $filename = 'screenshots/'.uniqid('fullpage_').'.png';
        $path = storage_path('app/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $imageData);

        return $filename;
    }

    /**
     * Check if a URL is reachable and returns HTML content.
     */
    public function isReachable(string $url): bool
    {
        try {
            $response = Http::timeout(10)->head($url);

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
        return $this->makeRequest('/content', [
            'url' => $url,
            'waitUntil' => 'networkidle0',
            'gotoOptions' => [
                'timeout' => $this->timeout,
            ],
        ]);
    }

    /**
     * Take a screenshot with a highlighted element.
     *
     * @param  array{top: int, left: int, width: int, height: int}|null  $boundingBox
     */
    public function screenshotWithHighlight(string $url, string $selector, ?array $boundingBox = null): string
    {
        // Browserless uses a function endpoint for custom scripts
        $script = $this->getHighlightScript($selector);

        $options = [
            'url' => $url,
            'waitUntil' => 'networkidle0',
            'gotoOptions' => [
                'timeout' => $this->timeout,
            ],
            'addScriptTag' => [
                [
                    'content' => $script,
                ],
            ],
            'options' => [
                'type' => 'png',
            ],
        ];

        if ($selector) {
            $options['selector'] = $selector;
        }

        $imageData = $this->makeRequest('/screenshot', $options, true);

        $filename = 'screenshots/'.uniqid('highlight_').'.png';
        $path = storage_path('app/'.$filename);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $imageData);

        return $filename;
    }

    /**
     * Get the highlight script for Browserless.
     */
    protected function getHighlightScript(string $selector): string
    {
        $escapedSelector = addslashes($selector);

        return <<<JS
(function() {
    const element = document.querySelector('{$escapedSelector}');
    if (element) {
        element.style.outline = '3px solid #ef4444';
        element.style.outlineOffset = '2px';
        element.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
        element.scrollIntoView({ behavior: 'instant', block: 'center' });
    }
})();
JS;
    }

    /**
     * Make a request to the Browserless API.
     */
    protected function makeRequest(string $endpoint, array $data, bool $binary = false): string
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($endpoint, '/');

        if ($this->token) {
            $url .= '?token='.$this->token;
        }

        $response = Http::timeout($this->timeout / 1000)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->post($url, $data);

        if (! $response->successful()) {
            throw new RuntimeException('Browserless request failed: '.$response->body());
        }

        return $binary ? $response->body() : $response->body();
    }

    /**
     * Parse HTML to extract content.
     *
     * @return array{content: string, title: string, meta: array, html: string}
     */
    protected function parseHtml(string $html): array
    {
        $doc = new \DOMDocument;
        @$doc->loadHTML($html, LIBXML_NOERROR);

        // Extract title
        $title = '';
        $titleElements = $doc->getElementsByTagName('title');
        if ($titleElements->length > 0) {
            $title = $titleElements->item(0)->textContent;
        }

        // Extract meta tags
        $meta = [];
        $metaElements = $doc->getElementsByTagName('meta');
        foreach ($metaElements as $element) {
            $name = $element->getAttribute('name') ?: $element->getAttribute('property');
            $content = $element->getAttribute('content');
            if ($name && $content) {
                $meta[$name] = $content;
            }
        }

        // Extract text content
        $body = $doc->getElementsByTagName('body');
        $content = '';
        if ($body->length > 0) {
            // Remove script and style elements
            $xpath = new \DOMXPath($doc);
            $scripts = $xpath->query('//script|//style|//noscript');
            foreach ($scripts as $script) {
                $script->parentNode->removeChild($script);
            }

            $content = $body->item(0)->textContent;
            $content = preg_replace('/\s+/', ' ', $content);
            $content = trim($content);
        }

        return [
            'content' => $content,
            'title' => $title,
            'meta' => $meta,
            'html' => $html,
        ];
    }
}
