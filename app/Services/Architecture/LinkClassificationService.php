<?php

namespace App\Services\Architecture;

use App\Enums\LinkType;
use DOMElement;
use DOMXPath;
use Symfony\Component\DomCrawler\Crawler;

class LinkClassificationService
{
    /**
     * Classify a link based on its context in the page.
     */
    public function classifyLink(DOMElement $linkElement, DOMXPath $xpath): LinkType
    {
        $href = $linkElement->getAttribute('href');

        // Check if external first
        if ($this->isExternalLink($href)) {
            return LinkType::External;
        }

        // Check by semantic HTML5 elements
        $semanticType = $this->classifyBySemanticContext($linkElement, $xpath);
        if ($semanticType !== null) {
            return $semanticType;
        }

        // Check by common class/id patterns
        $patternType = $this->classifyByPatterns($linkElement, $xpath);
        if ($patternType !== null) {
            return $patternType;
        }

        // Check by ARIA roles
        $ariaType = $this->classifyByAriaRole($linkElement, $xpath);
        if ($ariaType !== null) {
            return $ariaType;
        }

        // Check by position in document
        $positionType = $this->classifyByPosition($linkElement, $xpath);
        if ($positionType !== null) {
            return $positionType;
        }

        // Default to content link
        return LinkType::Content;
    }

    /**
     * Classify multiple links from HTML content.
     *
     * @return array<int, array{href: string, type: LinkType, anchor_text: string, is_nofollow: bool, position: string}>
     */
    public function classifyLinksInHtml(string $html, string $baseUrl): array
    {
        $crawler = new Crawler($html);
        $results = [];

        $crawler->filter('a[href]')->each(function (Crawler $node) use (&$results, $baseUrl) {
            $element = $node->getNode(0);
            if (! $element instanceof DOMElement) {
                return;
            }

            $href = $element->getAttribute('href');
            $absoluteUrl = $this->resolveUrl($href, $baseUrl);

            if ($absoluteUrl === null) {
                return;
            }

            $doc = $element->ownerDocument;
            $xpath = new DOMXPath($doc);

            $linkType = $this->classifyLink($element, $xpath);
            $position = $this->determinePositionInPage($element, $xpath);

            $results[] = [
                'href' => $absoluteUrl,
                'type' => $linkType,
                'anchor_text' => trim($node->text()),
                'is_nofollow' => $this->hasNofollow($element),
                'position' => $position,
            ];
        });

        return $results;
    }

    /**
     * Check if a link is external.
     */
    protected function isExternalLink(string $href): bool
    {
        if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:')) {
            return false;
        }

        return str_starts_with($href, 'http://') || str_starts_with($href, 'https://');
    }

    /**
     * Classify by HTML5 semantic elements.
     */
    protected function classifyBySemanticContext(DOMElement $element, DOMXPath $xpath): ?LinkType
    {
        // Check parent elements
        $parent = $element->parentNode;
        while ($parent instanceof DOMElement) {
            $tagName = strtolower($parent->tagName);

            switch ($tagName) {
                case 'nav':
                    return LinkType::Navigation;
                case 'header':
                    return LinkType::Header;
                case 'footer':
                    return LinkType::Footer;
                case 'aside':
                    return LinkType::Sidebar;
                case 'main':
                case 'article':
                case 'section':
                    // Could be content, but continue checking
                    break;
            }

            $parent = $parent->parentNode;
        }

        return null;
    }

    /**
     * Classify by common CSS class/id patterns.
     */
    protected function classifyByPatterns(DOMElement $element, DOMXPath $xpath): ?LinkType
    {
        $patterns = [
            'navigation' => ['nav', 'navigation', 'menu', 'main-menu', 'primary-menu', 'site-nav'],
            'header' => ['header', 'top-bar', 'masthead', 'site-header'],
            'footer' => ['footer', 'site-footer', 'bottom', 'foot'],
            'sidebar' => ['sidebar', 'side-bar', 'widget', 'aside'],
            'breadcrumb' => ['breadcrumb', 'breadcrumbs', 'crumb'],
            'pagination' => ['pagination', 'pager', 'page-numbers', 'nav-links'],
        ];

        $parent = $element;
        $depth = 0;
        $maxDepth = 10;

        while ($parent instanceof DOMElement && $depth < $maxDepth) {
            $classes = strtolower($parent->getAttribute('class'));
            $id = strtolower($parent->getAttribute('id'));
            $combined = $classes.' '.$id;

            foreach ($patterns as $type => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($combined, $keyword)) {
                        return LinkType::from($type);
                    }
                }
            }

            $parent = $parent->parentNode;
            $depth++;
        }

        return null;
    }

    /**
     * Classify by ARIA roles.
     */
    protected function classifyByAriaRole(DOMElement $element, DOMXPath $xpath): ?LinkType
    {
        $roleMapping = [
            'navigation' => LinkType::Navigation,
            'banner' => LinkType::Header,
            'contentinfo' => LinkType::Footer,
            'complementary' => LinkType::Sidebar,
        ];

        $parent = $element;
        $depth = 0;

        while ($parent instanceof DOMElement && $depth < 10) {
            $role = strtolower($parent->getAttribute('role'));

            if (isset($roleMapping[$role])) {
                return $roleMapping[$role];
            }

            $parent = $parent->parentNode;
            $depth++;
        }

        return null;
    }

    /**
     * Classify by approximate position in document.
     */
    protected function classifyByPosition(DOMElement $element, DOMXPath $xpath): ?LinkType
    {
        // Get all elements in document order
        $allElements = $xpath->query('//*');
        $totalElements = $allElements->length;

        if ($totalElements === 0) {
            return null;
        }

        // Find position of this element
        $position = 0;
        foreach ($allElements as $index => $el) {
            if ($el === $element || $this->isDescendantOf($element, $el)) {
                $position = $index;
                break;
            }
        }

        $relativePosition = $position / $totalElements;

        // Very rough heuristic
        if ($relativePosition < 0.1) {
            return LinkType::Header;
        } elseif ($relativePosition > 0.9) {
            return LinkType::Footer;
        }

        return null;
    }

    /**
     * Determine the position in page for the link.
     */
    protected function determinePositionInPage(DOMElement $element, DOMXPath $xpath): string
    {
        $parent = $element->parentNode;

        while ($parent instanceof DOMElement) {
            $tagName = strtolower($parent->tagName);
            $role = strtolower($parent->getAttribute('role'));

            if ($tagName === 'nav' || $role === 'navigation') {
                return 'nav';
            }
            if ($tagName === 'header' || $role === 'banner') {
                return 'header';
            }
            if ($tagName === 'footer' || $role === 'contentinfo') {
                return 'footer';
            }
            if ($tagName === 'aside' || $role === 'complementary') {
                return 'sidebar';
            }
            if (in_array($tagName, ['main', 'article', 'section']) || $role === 'main') {
                return 'main';
            }

            $parent = $parent->parentNode;
        }

        return 'main';
    }

    /**
     * Check if element has nofollow attribute.
     */
    protected function hasNofollow(DOMElement $element): bool
    {
        $rel = strtolower($element->getAttribute('rel'));

        return str_contains($rel, 'nofollow');
    }

    /**
     * Resolve relative URL to absolute.
     */
    protected function resolveUrl(string $href, string $baseUrl): ?string
    {
        if (empty($href) || str_starts_with($href, '#') || str_starts_with($href, 'javascript:') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
            return null;
        }

        // Already absolute
        if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
            return $href;
        }

        // Protocol-relative
        if (str_starts_with($href, '//')) {
            $parsed = parse_url($baseUrl);

            return ($parsed['scheme'] ?? 'https').':'.$href;
        }

        // Build absolute URL
        $parsed = parse_url($baseUrl);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';

        if (str_starts_with($href, '/')) {
            return "{$scheme}://{$host}{$port}{$href}";
        }

        // Relative path
        $basePath = $parsed['path'] ?? '/';
        $baseDir = dirname($basePath);
        if ($baseDir === '.') {
            $baseDir = '/';
        }

        return "{$scheme}://{$host}{$port}".rtrim($baseDir, '/').'/'.$href;
    }

    /**
     * Check if element is descendant of another.
     */
    protected function isDescendantOf(DOMElement $element, $ancestor): bool
    {
        $parent = $element->parentNode;

        while ($parent !== null) {
            if ($parent === $ancestor) {
                return true;
            }
            $parent = $parent->parentNode;
        }

        return false;
    }
}
