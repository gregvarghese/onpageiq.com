<?php

namespace App\Services\Browser;

interface BrowserServiceInterface
{
    /**
     * Render a page and extract its content.
     *
     * @return array{content: string, title: string, meta: array}
     */
    public function renderPage(string $url): array;

    /**
     * Take a screenshot of the page.
     */
    public function screenshot(string $url, ?string $selector = null): string;

    /**
     * Take a full-page screenshot.
     */
    public function fullPageScreenshot(string $url): string;

    /**
     * Check if a URL is reachable and returns HTML content.
     */
    public function isReachable(string $url): bool;

    /**
     * Get the page's HTML content.
     */
    public function getHtml(string $url): string;

    /**
     * Take a screenshot with a highlighted element.
     *
     * @param  array{top: int, left: int, width: int, height: int}|null  $boundingBox
     */
    public function screenshotWithHighlight(string $url, string $selector, ?array $boundingBox = null): string;
}
