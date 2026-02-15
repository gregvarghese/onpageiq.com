<?php

namespace App\Services\Architecture;

use Symfony\Component\DomCrawler\Crawler;

class SpaDetectionService
{
    /**
     * Known SPA framework signatures.
     *
     * @var array<string, array{scripts: array<string>, attributes: array<string>, globals: array<string>}>
     */
    protected array $frameworks = [
        'react' => [
            'scripts' => ['react', 'react-dom', 'react.production', 'react.development'],
            'attributes' => ['data-reactroot', 'data-reactid'],
            'globals' => ['React', 'ReactDOM', '__REACT_DEVTOOLS_GLOBAL_HOOK__'],
        ],
        'vue' => [
            'scripts' => ['vue.js', 'vue.min.js', 'vue.runtime', 'vue.esm'],
            'attributes' => ['data-v-', 'v-cloak', 'v-if', 'v-for', 'v-bind', 'v-on'],
            'globals' => ['Vue', '__VUE__', '__VUE_DEVTOOLS_GLOBAL_HOOK__'],
        ],
        'angular' => [
            'scripts' => ['angular', 'zone.js', 'polyfills', '@angular'],
            'attributes' => ['ng-app', 'ng-controller', 'ng-model', '_ngcontent', '_nghost'],
            'globals' => ['ng', 'angular', 'getAllAngularRootElements'],
        ],
        'svelte' => [
            'scripts' => ['svelte', 'svelte-kit'],
            'attributes' => ['class*="svelte-"'],
            'globals' => ['__svelte'],
        ],
        'next' => [
            'scripts' => ['_next/static', 'next/dist'],
            'attributes' => ['__next', 'data-nscript'],
            'globals' => ['__NEXT_DATA__', '__NEXT_LOADED_PAGES__'],
        ],
        'nuxt' => [
            'scripts' => ['_nuxt', 'nuxt'],
            'attributes' => ['__nuxt', 'data-n-head', 'data-nuxt'],
            'globals' => ['__NUXT__', '$nuxt'],
        ],
        'gatsby' => [
            'scripts' => ['gatsby'],
            'attributes' => ['___gatsby'],
            'globals' => ['___loader', '___webpackCompilationHash'],
        ],
        'ember' => [
            'scripts' => ['ember', 'ember.js'],
            'attributes' => ['data-ember-action', 'id*="ember"'],
            'globals' => ['Ember', 'Em'],
        ],
    ];

    /**
     * Detect SPA frameworks from HTML content.
     *
     * @return array{is_spa: bool, frameworks: array<string>, confidence: float, requires_js_rendering: bool}
     */
    public function detect(string $html): array
    {
        $detectedFrameworks = [];
        $totalScore = 0;

        $crawler = new Crawler($html);

        foreach ($this->frameworks as $framework => $signatures) {
            $score = $this->detectFramework($crawler, $html, $signatures);

            if ($score > 0) {
                $detectedFrameworks[$framework] = $score;
                $totalScore += $score;
            }
        }

        // Check for generic SPA indicators
        $genericScore = $this->detectGenericSpaIndicators($crawler, $html);
        $totalScore += $genericScore;

        // Sort by score descending
        arsort($detectedFrameworks);

        $isSpa = ! empty($detectedFrameworks) || $genericScore > 50;
        $confidence = min(100, $totalScore) / 100;

        return [
            'is_spa' => $isSpa,
            'frameworks' => array_keys($detectedFrameworks),
            'confidence' => $confidence,
            'requires_js_rendering' => $this->shouldUseJsRendering($detectedFrameworks, $genericScore, $html),
        ];
    }

    /**
     * Detect a specific framework.
     */
    protected function detectFramework(Crawler $crawler, string $html, array $signatures): int
    {
        $score = 0;

        // Check scripts
        $scriptSources = $crawler->filter('script[src]')->each(fn (Crawler $node) => $node->attr('src') ?? '');

        foreach ($signatures['scripts'] as $pattern) {
            foreach ($scriptSources as $src) {
                if (str_contains(strtolower($src), strtolower($pattern))) {
                    $score += 30;
                    break;
                }
            }
        }

        // Check inline scripts for globals
        $inlineScripts = $crawler->filter('script:not([src])')->each(fn (Crawler $node) => $node->text());
        $allInlineScript = implode(' ', $inlineScripts);

        foreach ($signatures['globals'] as $global) {
            if (str_contains($html, $global)) {
                $score += 20;
                break;
            }
        }

        // Check attributes
        foreach ($signatures['attributes'] as $attr) {
            if (str_contains($attr, '*=')) {
                // Wildcard attribute check
                $attrPrefix = str_replace('*=', '', $attr);
                $attrPrefix = trim($attrPrefix, '"\'');
                if (str_contains($html, $attrPrefix)) {
                    $score += 25;
                    break;
                }
            } else {
                // Exact attribute check
                try {
                    $found = $crawler->filter("[$attr]")->count() > 0;
                    if ($found) {
                        $score += 25;
                        break;
                    }
                } catch (\Exception) {
                    // Invalid selector, try string search
                    if (str_contains($html, $attr)) {
                        $score += 25;
                        break;
                    }
                }
            }
        }

        return $score;
    }

    /**
     * Detect generic SPA indicators.
     */
    protected function detectGenericSpaIndicators(Crawler $crawler, string $html): int
    {
        $score = 0;

        // Check for minimal body content (common in SPAs that render client-side)
        $body = $crawler->filter('body');
        if ($body->count() > 0) {
            $bodyText = trim($body->text());

            // Very little text but lots of scripts
            if (strlen($bodyText) < 100 && substr_count(strtolower($html), '<script') > 3) {
                $score += 40;
            }

            // Root div with no or minimal content (SPA mount point)
            // Use DOM-based check for more reliable detection
            $rootSelectors = ['#root', '#app', '#main', '#__next', '#__nuxt'];
            foreach ($rootSelectors as $selector) {
                try {
                    $rootDiv = $crawler->filter($selector);
                    if ($rootDiv->count() > 0) {
                        $rootText = trim($rootDiv->text());
                        // Empty or minimal content suggests SPA mount point
                        if (strlen($rootText) < 50) {
                            $score += 30;
                            break;
                        }
                    }
                } catch (\Exception) {
                    // Invalid selector, skip
                }
            }
        }

        // Check for bundler artifacts
        $bundlerPatterns = [
            'webpack',
            'webpackJsonp',
            'parcelRequire',
            '__vite__',
            'rollup',
        ];

        foreach ($bundlerPatterns as $pattern) {
            if (str_contains($html, $pattern)) {
                $score += 25;
                break;
            }
        }

        // Check for history API usage hints
        if (str_contains($html, 'history.pushState') || str_contains($html, 'history.replaceState')) {
            $score += 10;
        }

        return $score;
    }

    /**
     * Determine if JavaScript rendering should be used.
     */
    protected function shouldUseJsRendering(array $detectedFrameworks, int $genericScore, string $html): bool
    {
        // Always use JS rendering for known CSR-heavy frameworks
        $csrFrameworks = ['react', 'vue', 'angular', 'svelte', 'ember'];

        foreach ($csrFrameworks as $framework) {
            if (isset($detectedFrameworks[$framework]) && $detectedFrameworks[$framework] >= 50) {
                return true;
            }
        }

        // SSR frameworks might not need JS rendering if content is already there
        $ssrFrameworks = ['next', 'nuxt', 'gatsby'];
        foreach ($ssrFrameworks as $framework) {
            if (isset($detectedFrameworks[$framework])) {
                // Check if page has substantial content
                $crawler = new Crawler($html);
                $textLength = strlen(trim($crawler->filter('body')->text()));

                // If there's substantial content (250+ chars), SSR is likely working
                if ($textLength > 250) {
                    return false;
                }

                return true;
            }
        }

        // High generic score suggests client-side rendering
        if ($genericScore >= 50) {
            return true;
        }

        return false;
    }

    /**
     * Get all known framework names.
     *
     * @return array<string>
     */
    public function getKnownFrameworks(): array
    {
        return array_keys($this->frameworks);
    }
}
