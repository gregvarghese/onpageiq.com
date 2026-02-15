<?php

namespace App\Services\Architecture;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RobotsTxtService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * User agent to use for crawling.
     */
    protected string $userAgent = 'OnPageIQBot';

    /**
     * Parsed rules cache per domain.
     *
     * @var array<string, array{allow: array<string>, disallow: array<string>}>
     */
    protected array $rulesCache = [];

    /**
     * Check if a URL is allowed to be crawled according to robots.txt.
     */
    public function isAllowed(string $url): bool
    {
        $parsed = parse_url($url);

        if (! isset($parsed['host'])) {
            return true;
        }

        $baseUrl = ($parsed['scheme'] ?? 'https').'://'.$parsed['host'];
        $path = $parsed['path'] ?? '/';

        $rules = $this->getRulesForDomain($baseUrl);

        return $this->checkPathAgainstRules($path, $rules);
    }

    /**
     * Get parsed rules for a domain.
     *
     * @return array{allow: array<string>, disallow: array<string>, crawl_delay: int|null, sitemaps: array<string>}
     */
    public function getRulesForDomain(string $baseUrl): array
    {
        $cacheKey = 'robots_txt_'.md5($baseUrl);

        // Check memory cache first
        if (isset($this->rulesCache[$baseUrl])) {
            return $this->rulesCache[$baseUrl];
        }

        // Check persistent cache
        $rules = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($baseUrl) {
            return $this->fetchAndParseRobotsTxt($baseUrl);
        });

        $this->rulesCache[$baseUrl] = $rules;

        return $rules;
    }

    /**
     * Fetch and parse robots.txt for a domain.
     *
     * @return array{allow: array<string>, disallow: array<string>, crawl_delay: int|null, sitemaps: array<string>}
     */
    protected function fetchAndParseRobotsTxt(string $baseUrl): array
    {
        $robotsUrl = rtrim($baseUrl, '/').'/robots.txt';

        $defaultRules = [
            'allow' => [],
            'disallow' => [],
            'crawl_delay' => null,
            'sitemaps' => [],
        ];

        try {
            $response = Http::timeout(10)
                ->withUserAgent($this->userAgent)
                ->get($robotsUrl);

            if (! $response->successful()) {
                // If robots.txt doesn't exist or is inaccessible, allow everything
                Log::debug('robots.txt not found or inaccessible', [
                    'url' => $robotsUrl,
                    'status' => $response->status(),
                ]);

                return $defaultRules;
            }

            return $this->parseRobotsTxt($response->body());

        } catch (\Throwable $e) {
            Log::warning('Failed to fetch robots.txt', [
                'url' => $robotsUrl,
                'error' => $e->getMessage(),
            ]);

            return $defaultRules;
        }
    }

    /**
     * Parse robots.txt content.
     *
     * @return array{allow: array<string>, disallow: array<string>, crawl_delay: int|null, sitemaps: array<string>}
     */
    public function parseRobotsTxt(string $content): array
    {
        $rules = [
            'allow' => [],
            'disallow' => [],
            'crawl_delay' => null,
            'sitemaps' => [],
        ];

        $lines = explode("\n", $content);
        $currentUserAgent = null;
        $isRelevantSection = false;
        $wildcardSection = false;

        foreach ($lines as $line) {
            // Remove comments
            $line = preg_replace('/#.*$/', '', $line);
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Parse directive
            if (preg_match('/^(\w[\w-]*)\s*:\s*(.*)$/i', $line, $matches)) {
                $directive = strtolower($matches[1]);
                $value = trim($matches[2]);

                switch ($directive) {
                    case 'user-agent':
                        $currentUserAgent = strtolower($value);
                        // Check if this section applies to us
                        if ($currentUserAgent === '*') {
                            $wildcardSection = true;
                            $isRelevantSection = true;
                        } elseif (stripos($this->userAgent, $value) !== false || stripos($value, 'bot') !== false) {
                            $isRelevantSection = true;
                            $wildcardSection = false;
                        } else {
                            $isRelevantSection = false;
                        }
                        break;

                    case 'disallow':
                        if ($isRelevantSection && ! empty($value)) {
                            $rules['disallow'][] = $value;
                        }
                        break;

                    case 'allow':
                        if ($isRelevantSection && ! empty($value)) {
                            $rules['allow'][] = $value;
                        }
                        break;

                    case 'crawl-delay':
                        if ($isRelevantSection && is_numeric($value)) {
                            $rules['crawl_delay'] = (int) $value;
                        }
                        break;

                    case 'sitemap':
                        // Sitemaps are global, not per user-agent
                        if (! empty($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                            $rules['sitemaps'][] = $value;
                        }
                        break;
                }
            }
        }

        // Remove duplicates
        $rules['allow'] = array_unique($rules['allow']);
        $rules['disallow'] = array_unique($rules['disallow']);
        $rules['sitemaps'] = array_unique($rules['sitemaps']);

        return $rules;
    }

    /**
     * Check if a path is allowed according to the rules.
     *
     * @param  array{allow: array<string>, disallow: array<string>}  $rules
     */
    protected function checkPathAgainstRules(string $path, array $rules): bool
    {
        // If no rules, allow everything
        if (empty($rules['disallow']) && empty($rules['allow'])) {
            return true;
        }

        // Find the most specific matching rule
        $matchedAllow = null;
        $matchedDisallow = null;
        $allowLength = 0;
        $disallowLength = 0;

        // Check allow rules
        foreach ($rules['allow'] as $pattern) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                $patternLength = strlen($pattern);
                if ($patternLength > $allowLength) {
                    $matchedAllow = $pattern;
                    $allowLength = $patternLength;
                }
            }
        }

        // Check disallow rules
        foreach ($rules['disallow'] as $pattern) {
            if ($this->pathMatchesPattern($path, $pattern)) {
                $patternLength = strlen($pattern);
                if ($patternLength > $disallowLength) {
                    $matchedDisallow = $pattern;
                    $disallowLength = $patternLength;
                }
            }
        }

        // If no matches, allow
        if ($matchedAllow === null && $matchedDisallow === null) {
            return true;
        }

        // More specific rule wins
        if ($allowLength > $disallowLength) {
            return true;
        }

        if ($disallowLength > $allowLength) {
            return false;
        }

        // Equal length - allow wins (per Google's interpretation)
        if ($matchedAllow !== null) {
            return true;
        }

        return false;
    }

    /**
     * Check if a path matches a robots.txt pattern.
     */
    protected function pathMatchesPattern(string $path, string $pattern): bool
    {
        // Empty pattern matches nothing
        if (empty($pattern)) {
            return false;
        }

        // Simple prefix match for patterns without wildcards
        if (strpos($pattern, '*') === false && strpos($pattern, '$') === false) {
            return strpos($path, $pattern) === 0;
        }

        // Convert robots.txt pattern to regex
        $regex = $this->patternToRegex($pattern);

        return (bool) preg_match($regex, $path);
    }

    /**
     * Convert a robots.txt pattern to a regex.
     */
    protected function patternToRegex(string $pattern): string
    {
        // Escape special regex characters except * and $
        $pattern = preg_quote($pattern, '/');

        // Restore * and $ (we escaped them above)
        $pattern = str_replace('\*', '.*', $pattern);
        $pattern = str_replace('\$', '$', $pattern);

        // $ at the end means exact match
        if (substr($pattern, -1) !== '$') {
            $pattern .= '.*';
        }

        return '/^'.$pattern.'/';
    }

    /**
     * Get the crawl delay for a domain.
     */
    public function getCrawlDelay(string $baseUrl): ?int
    {
        $rules = $this->getRulesForDomain($baseUrl);

        return $rules['crawl_delay'];
    }

    /**
     * Get sitemaps declared in robots.txt.
     *
     * @return array<string>
     */
    public function getSitemaps(string $baseUrl): array
    {
        $rules = $this->getRulesForDomain($baseUrl);

        return $rules['sitemaps'];
    }

    /**
     * Clear the cache for a domain.
     */
    public function clearCache(string $baseUrl): void
    {
        $cacheKey = 'robots_txt_'.md5($baseUrl);
        Cache::forget($cacheKey);
        unset($this->rulesCache[$baseUrl]);
    }

    /**
     * Set the user agent.
     */
    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }
}
