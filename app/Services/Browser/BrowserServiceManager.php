<?php

namespace App\Services\Browser;

use InvalidArgumentException;

class BrowserServiceManager
{
    /**
     * @var array<string, BrowserServiceInterface>
     */
    protected array $drivers = [];

    /**
     * Get the browser service instance for the configured driver.
     */
    public function driver(?string $driver = null): BrowserServiceInterface
    {
        $driver = $driver ?? config('onpageiq.browser.driver', 'local');

        if (! isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Create a new driver instance.
     */
    protected function createDriver(string $driver): BrowserServiceInterface
    {
        return match ($driver) {
            'local' => new LocalBrowserService,
            'browserless' => new BrowserlessService,
            default => throw new InvalidArgumentException("Unsupported browser driver: {$driver}"),
        };
    }

    /**
     * Render a page using the configured driver.
     *
     * @return array{content: string, title: string, meta: array, html: string}
     */
    public function renderPage(string $url): array
    {
        return $this->driver()->renderPage($url);
    }

    /**
     * Get page content as a PageContent object.
     */
    public function getPageContent(string $url): PageContent
    {
        $data = $this->renderPage($url);

        return PageContent::fromRenderedData($url, $data);
    }

    /**
     * Take a screenshot of the page.
     */
    public function screenshot(string $url, ?string $selector = null): string
    {
        return $this->driver()->screenshot($url, $selector);
    }

    /**
     * Take a full-page screenshot.
     */
    public function fullPageScreenshot(string $url): string
    {
        return $this->driver()->fullPageScreenshot($url);
    }

    /**
     * Check if a URL is reachable and returns HTML content.
     */
    public function isReachable(string $url): bool
    {
        return $this->driver()->isReachable($url);
    }

    /**
     * Get the page's HTML content.
     */
    public function getHtml(string $url): string
    {
        return $this->driver()->getHtml($url);
    }
}
