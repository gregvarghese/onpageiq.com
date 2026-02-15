<?php

namespace App\Services\Accessibility;

class ColorSystemAnalysisService
{
    /**
     * Minimum contrast ratios per WCAG level.
     */
    protected const CONTRAST_RATIOS = [
        'AA_normal' => 4.5,
        'AA_large' => 3.0,
        'AAA_normal' => 7.0,
        'AAA_large' => 4.5,
        'non_text' => 3.0,
    ];

    /**
     * Extract CSS custom properties (variables) from stylesheet content.
     *
     * @return array<string, string>
     */
    public function extractCssVariables(string $css): array
    {
        $variables = [];

        // Match CSS custom properties in :root or other selectors
        preg_match_all('/--([a-zA-Z0-9_-]+)\s*:\s*([^;]+);/', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $name = '--'.$match[1];
            $value = trim($match[2]);
            $variables[$name] = $value;
        }

        return $variables;
    }

    /**
     * Build a color palette from CSS content.
     *
     * @return array<string, array<string, mixed>>
     */
    public function buildColorPalette(string $css): array
    {
        $variables = $this->extractCssVariables($css);
        $palette = [];

        foreach ($variables as $name => $value) {
            $hex = $this->resolveToHex($value, $variables);

            if ($hex) {
                $palette[$name] = [
                    'name' => $name,
                    'original' => $value,
                    'hex' => $hex,
                    'rgb' => $this->hexToRgb($hex),
                    'hsl' => $this->hexToHsl($hex),
                    'luminance' => $this->calculateRelativeLuminance($hex),
                ];
            }
        }

        return $palette;
    }

    /**
     * Extract all colors from CSS (not just variables).
     *
     * @return array<string, array<string, mixed>>
     */
    public function extractAllColors(string $css): array
    {
        $colors = [];

        // Match hex colors
        preg_match_all('/#([0-9a-fA-F]{3,8})\b/', $css, $hexMatches);
        foreach ($hexMatches[0] as $hex) {
            $normalized = $this->normalizeHex($hex);
            if ($normalized && ! isset($colors[$normalized])) {
                $colors[$normalized] = [
                    'hex' => $normalized,
                    'rgb' => $this->hexToRgb($normalized),
                    'luminance' => $this->calculateRelativeLuminance($normalized),
                    'occurrences' => 1,
                ];
            } elseif ($normalized) {
                $colors[$normalized]['occurrences']++;
            }
        }

        // Match rgb/rgba colors
        preg_match_all('/rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*[\d.]+)?\s*\)/', $css, $rgbMatches, PREG_SET_ORDER);
        foreach ($rgbMatches as $match) {
            $hex = $this->rgbToHex((int) $match[1], (int) $match[2], (int) $match[3]);
            if (! isset($colors[$hex])) {
                $colors[$hex] = [
                    'hex' => $hex,
                    'rgb' => ['r' => (int) $match[1], 'g' => (int) $match[2], 'b' => (int) $match[3]],
                    'luminance' => $this->calculateRelativeLuminance($hex),
                    'occurrences' => 1,
                ];
            } else {
                $colors[$hex]['occurrences']++;
            }
        }

        return $colors;
    }

    /**
     * Generate a contrast matrix for all color combinations.
     *
     * @param  array<string, array<string, mixed>>  $colors
     * @return array<string, array<string, array<string, mixed>>>
     */
    public function generateContrastMatrix(array $colors): array
    {
        $matrix = [];
        $colorKeys = array_keys($colors);

        foreach ($colorKeys as $foreground) {
            $matrix[$foreground] = [];

            foreach ($colorKeys as $background) {
                if ($foreground === $background) {
                    continue;
                }

                $ratio = $this->calculateContrastRatio(
                    $colors[$foreground]['hex'],
                    $colors[$background]['hex']
                );

                $matrix[$foreground][$background] = [
                    'ratio' => round($ratio, 2),
                    'passes_aa_normal' => $ratio >= self::CONTRAST_RATIOS['AA_normal'],
                    'passes_aa_large' => $ratio >= self::CONTRAST_RATIOS['AA_large'],
                    'passes_aaa_normal' => $ratio >= self::CONTRAST_RATIOS['AAA_normal'],
                    'passes_aaa_large' => $ratio >= self::CONTRAST_RATIOS['AAA_large'],
                    'passes_non_text' => $ratio >= self::CONTRAST_RATIOS['non_text'],
                ];
            }
        }

        return $matrix;
    }

    /**
     * Analyze brand colors for accessibility compliance.
     *
     * @param  array<string>  $brandColors  Hex colors
     * @return array<string, mixed>
     */
    public function analyzeBrandColors(array $brandColors): array
    {
        $analysis = [
            'colors' => [],
            'combinations' => [],
            'recommendations' => [],
            'overall_score' => 0,
        ];

        // Analyze individual colors
        foreach ($brandColors as $name => $hex) {
            $hex = $this->normalizeHex($hex);
            $analysis['colors'][$name] = [
                'hex' => $hex,
                'rgb' => $this->hexToRgb($hex),
                'hsl' => $this->hexToHsl($hex),
                'luminance' => $this->calculateRelativeLuminance($hex),
                'contrast_with_white' => $this->calculateContrastRatio($hex, '#ffffff'),
                'contrast_with_black' => $this->calculateContrastRatio($hex, '#000000'),
            ];
        }

        // Test all combinations
        $colorNames = array_keys($brandColors);
        $passCount = 0;
        $totalCount = 0;

        for ($i = 0; $i < count($colorNames); $i++) {
            for ($j = $i + 1; $j < count($colorNames); $j++) {
                $name1 = $colorNames[$i];
                $name2 = $colorNames[$j];
                $hex1 = $this->normalizeHex($brandColors[$name1]);
                $hex2 = $this->normalizeHex($brandColors[$name2]);

                $ratio = $this->calculateContrastRatio($hex1, $hex2);
                $passesAA = $ratio >= self::CONTRAST_RATIOS['AA_normal'];

                $analysis['combinations'][] = [
                    'color1' => ['name' => $name1, 'hex' => $hex1],
                    'color2' => ['name' => $name2, 'hex' => $hex2],
                    'ratio' => round($ratio, 2),
                    'passes_aa_normal' => $passesAA,
                    'passes_aa_large' => $ratio >= self::CONTRAST_RATIOS['AA_large'],
                    'passes_aaa_normal' => $ratio >= self::CONTRAST_RATIOS['AAA_normal'],
                    'wcag_level' => $this->getWcagLevel($ratio),
                ];

                if ($passesAA) {
                    $passCount++;
                }
                $totalCount++;
            }
        }

        // Calculate overall score
        $analysis['overall_score'] = $totalCount > 0 ? round(($passCount / $totalCount) * 100, 1) : 100;

        // Generate recommendations
        $analysis['recommendations'] = $this->generateColorRecommendations($analysis);

        return $analysis;
    }

    /**
     * Calculate contrast ratio between two colors.
     */
    public function calculateContrastRatio(string $color1, string $color2): float
    {
        $l1 = $this->calculateRelativeLuminance($color1);
        $l2 = $this->calculateRelativeLuminance($color2);

        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * Calculate relative luminance of a color.
     */
    public function calculateRelativeLuminance(string $hex): float
    {
        $rgb = $this->hexToRgb($hex);

        $r = $this->linearize($rgb['r'] / 255);
        $g = $this->linearize($rgb['g'] / 255);
        $b = $this->linearize($rgb['b'] / 255);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Linearize a color channel value.
     */
    protected function linearize(float $value): float
    {
        if ($value <= 0.03928) {
            return $value / 12.92;
        }

        return pow(($value + 0.055) / 1.055, 2.4);
    }

    /**
     * Convert hex to RGB.
     *
     * @return array{r: int, g: int, b: int}
     */
    public function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            'r' => hexdec(substr($hex, 0, 2)),
            'g' => hexdec(substr($hex, 2, 2)),
            'b' => hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Convert RGB to hex.
     */
    public function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Convert hex to HSL.
     *
     * @return array{h: int, s: int, l: int}
     */
    public function hexToHsl(string $hex): array
    {
        $rgb = $this->hexToRgb($hex);

        $r = $rgb['r'] / 255;
        $g = $rgb['g'] / 255;
        $b = $rgb['b'] / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }

        return [
            'h' => (int) round($h * 360),
            's' => (int) round($s * 100),
            'l' => (int) round($l * 100),
        ];
    }

    /**
     * Normalize a hex color to #rrggbb format.
     */
    public function normalizeHex(string $hex): ?string
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) === 8) {
            // RGBA - strip alpha
            $hex = substr($hex, 0, 6);
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return null;
        }

        return '#'.strtolower($hex);
    }

    /**
     * Resolve a CSS value to hex (resolving var() references).
     *
     * @param  array<string, string>  $variables
     */
    protected function resolveToHex(string $value, array $variables): ?string
    {
        // Resolve var() references
        if (preg_match('/var\(\s*(--[a-zA-Z0-9_-]+)/', $value, $match)) {
            $varName = $match[1];
            if (isset($variables[$varName])) {
                return $this->resolveToHex($variables[$varName], $variables);
            }

            return null;
        }

        // Try to parse as hex
        if (str_starts_with($value, '#')) {
            return $this->normalizeHex($value);
        }

        // Try to parse as rgb
        if (preg_match('/rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $value, $match)) {
            return $this->rgbToHex((int) $match[1], (int) $match[2], (int) $match[3]);
        }

        // Try to parse as hsl
        if (preg_match('/hsla?\s*\(\s*(\d+)\s*,\s*(\d+)%?\s*,\s*(\d+)%?/', $value, $match)) {
            return $this->hslToHex((int) $match[1], (int) $match[2], (int) $match[3]);
        }

        return null;
    }

    /**
     * Convert HSL to hex.
     */
    public function hslToHex(int $h, int $s, int $l): string
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        if ($s === 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hueToRgb($p, $q, $h + 1 / 3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1 / 3);
        }

        return $this->rgbToHex(
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255)
        );
    }

    /**
     * Helper for HSL to RGB conversion.
     */
    protected function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /**
     * Get WCAG compliance level for a contrast ratio.
     */
    protected function getWcagLevel(float $ratio): string
    {
        if ($ratio >= self::CONTRAST_RATIOS['AAA_normal']) {
            return 'AAA';
        }
        if ($ratio >= self::CONTRAST_RATIOS['AA_normal']) {
            return 'AA';
        }
        if ($ratio >= self::CONTRAST_RATIOS['AA_large']) {
            return 'AA Large';
        }

        return 'Fail';
    }

    /**
     * Generate recommendations for improving color accessibility.
     *
     * @param  array<string, mixed>  $analysis
     * @return array<string>
     */
    protected function generateColorRecommendations(array $analysis): array
    {
        $recommendations = [];

        // Check for low-contrast combinations
        $failingCombinations = collect($analysis['combinations'])
            ->filter(fn ($c) => ! $c['passes_aa_normal'])
            ->values();

        if ($failingCombinations->isNotEmpty()) {
            $recommendations[] = sprintf(
                '%d color combination(s) fail WCAG AA contrast requirements. Consider adjusting lightness values.',
                $failingCombinations->count()
            );
        }

        // Check for colors too similar to each other
        foreach ($analysis['colors'] as $name => $color) {
            if ($color['contrast_with_white'] < 3 && $color['contrast_with_black'] < 3) {
                $recommendations[] = sprintf(
                    'Color "%s" (%s) has poor contrast with both white and black. Consider using a more saturated or darker/lighter shade.',
                    $name,
                    $color['hex']
                );
            }
        }

        // Suggest accessible pairings
        $goodPairings = collect($analysis['combinations'])
            ->filter(fn ($c) => $c['passes_aaa_normal'])
            ->values();

        if ($goodPairings->isNotEmpty()) {
            $recommendations[] = sprintf(
                '%d color combination(s) meet AAA standards and are excellent for text readability.',
                $goodPairings->count()
            );
        }

        return $recommendations;
    }

    /**
     * Suggest accessible alternative for a color.
     *
     * @return array{lighter: string, darker: string}
     */
    public function suggestAccessibleAlternatives(string $hex, string $background = '#ffffff'): array
    {
        $currentRatio = $this->calculateContrastRatio($hex, $background);
        $targetRatio = self::CONTRAST_RATIOS['AA_normal'];

        $hsl = $this->hexToHsl($hex);

        // Calculate lighter and darker alternatives
        $lighterHsl = $hsl;
        $darkerHsl = $hsl;

        // Adjust lightness to meet contrast
        $bgLuminance = $this->calculateRelativeLuminance($background);

        // If background is light, we need darker text
        if ($bgLuminance > 0.5) {
            while ($darkerHsl['l'] > 0) {
                $darkerHsl['l'] -= 5;
                $darkerHex = $this->hslToHex($darkerHsl['h'], $darkerHsl['s'], max(0, $darkerHsl['l']));
                if ($this->calculateContrastRatio($darkerHex, $background) >= $targetRatio) {
                    break;
                }
            }
        } else {
            // Background is dark, we need lighter text
            while ($lighterHsl['l'] < 100) {
                $lighterHsl['l'] += 5;
                $lighterHex = $this->hslToHex($lighterHsl['h'], $lighterHsl['s'], min(100, $lighterHsl['l']));
                if ($this->calculateContrastRatio($lighterHex, $background) >= $targetRatio) {
                    break;
                }
            }
        }

        return [
            'lighter' => $this->hslToHex($lighterHsl['h'], $lighterHsl['s'], min(100, $lighterHsl['l'])),
            'darker' => $this->hslToHex($darkerHsl['h'], $darkerHsl['s'], max(0, $darkerHsl['l'])),
        ];
    }
}
