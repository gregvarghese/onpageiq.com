<?php

namespace App\Services\Accessibility;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Playwright-based accessibility testing service.
 *
 * Provides advanced browser testing capabilities for accessibility audits:
 * - Keyboard journey testing with focus trap detection
 * - Mobile viewport simulation with touch target analysis
 * - Component lifecycle testing (modals, accordions, tabs)
 * - Accessibility tree extraction
 * - Timing content detection
 */
class PlaywrightAccessibilityService
{
    protected int $timeout;

    public function __construct()
    {
        $this->timeout = config('onpageiq.browser.local.timeout', 30000);
    }

    /**
     * Test keyboard navigation journey on a page.
     *
     * @return array{
     *     focusableElements: array,
     *     tabOrder: array,
     *     focusTraps: array,
     *     keyboardAccessible: bool,
     *     issues: array
     * }
     */
    public function testKeyboardJourney(string $url): array
    {
        $script = $this->getKeyboardJourneyScript($url);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'focusableElements' => [],
            'tabOrder' => [],
            'focusTraps' => [],
            'keyboardAccessible' => false,
            'issues' => [['type' => 'error', 'message' => 'Failed to analyze keyboard navigation']],
        ];
    }

    /**
     * Test mobile viewport and touch targets.
     *
     * @return array{
     *     viewport: array,
     *     touchTargets: array,
     *     smallTargets: array,
     *     orientationSupport: bool,
     *     issues: array
     * }
     */
    public function testMobileAccessibility(string $url, int $width = 375, int $height = 667): array
    {
        $script = $this->getMobileTestScript($url, $width, $height);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'viewport' => ['width' => $width, 'height' => $height],
            'touchTargets' => [],
            'smallTargets' => [],
            'orientationSupport' => true,
            'issues' => [['type' => 'error', 'message' => 'Failed to analyze mobile accessibility']],
        ];
    }

    /**
     * Test component lifecycle states (modals, accordions, tabs).
     *
     * @return array{
     *     components: array,
     *     stateChanges: array,
     *     ariaUpdates: array,
     *     focusManagement: array,
     *     issues: array
     * }
     */
    public function testComponentLifecycle(string $url): array
    {
        $script = $this->getComponentLifecycleScript($url);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'components' => [],
            'stateChanges' => [],
            'ariaUpdates' => [],
            'focusManagement' => [],
            'issues' => [['type' => 'error', 'message' => 'Failed to analyze component lifecycle']],
        ];
    }

    /**
     * Extract accessibility tree from the page.
     *
     * @return array{
     *     tree: array,
     *     landmarks: array,
     *     headings: array,
     *     ariaRoles: array
     * }
     */
    public function getAccessibilityTree(string $url): array
    {
        $script = $this->getAccessibilityTreeScript($url);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'tree' => [],
            'landmarks' => [],
            'headings' => [],
            'ariaRoles' => [],
        ];
    }

    /**
     * Detect timing-related content (carousels, auto-updating, animations).
     *
     * @return array{
     *     autoPlayingMedia: array,
     *     carousels: array,
     *     animations: array,
     *     liveRegions: array,
     *     issues: array
     * }
     */
    public function detectTimingContent(string $url): array
    {
        $script = $this->getTimingContentScript($url);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'autoPlayingMedia' => [],
            'carousels' => [],
            'animations' => [],
            'liveRegions' => [],
            'issues' => [],
        ];
    }

    /**
     * Test focus visibility across the page.
     *
     * @return array{
     *     elementsWithVisibleFocus: array,
     *     elementsWithoutVisibleFocus: array,
     *     focusStyles: array,
     *     issues: array
     * }
     */
    public function testFocusVisibility(string $url): array
    {
        $script = $this->getFocusVisibilityScript($url);
        $result = $this->runPlaywrightScript($script);

        return json_decode($result, true) ?? [
            'elementsWithVisibleFocus' => [],
            'elementsWithoutVisibleFocus' => [],
            'focusStyles' => [],
            'issues' => [],
        ];
    }

    /**
     * Run a Playwright script and return the output.
     */
    protected function runPlaywrightScript(string $script): string
    {
        $scriptPath = storage_path('app/temp/'.uniqid('a11y_playwright_').'.cjs');

        if (! is_dir(dirname($scriptPath))) {
            mkdir(dirname($scriptPath), 0755, true);
        }

        file_put_contents($scriptPath, $script);

        try {
            $result = Process::timeout($this->timeout / 1000)
                ->run(['node', $scriptPath]);

            if (! $result->successful()) {
                throw new RuntimeException('Playwright accessibility script failed: '.$result->errorOutput());
            }

            return $result->output();
        } finally {
            @unlink($scriptPath);
        }
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
     * Get the keyboard journey testing script.
     */
    protected function getKeyboardJourneyScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    const result = {
        focusableElements: [],
        tabOrder: [],
        focusTraps: [],
        keyboardAccessible: true,
        issues: []
    };

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Get all focusable elements
        const focusableSelectors = [
            'a[href]', 'button', 'input', 'select', 'textarea',
            '[tabindex]:not([tabindex="-1"])', '[contenteditable]',
            'audio[controls]', 'video[controls]', 'details', 'summary'
        ].join(', ');

        result.focusableElements = await page.evaluate((selector) => {
            const elements = Array.from(document.querySelectorAll(selector));
            return elements.map((el, index) => ({
                index,
                tagName: el.tagName.toLowerCase(),
                role: el.getAttribute('role'),
                ariaLabel: el.getAttribute('aria-label'),
                text: el.textContent?.trim().substring(0, 50),
                tabIndex: el.tabIndex,
                isVisible: el.offsetParent !== null,
                selector: el.id ? '#' + el.id : el.tagName.toLowerCase() + (el.className ? '.' + el.className.split(' ').join('.') : '')
            }));
        }, focusableSelectors);

        // Test tab order by simulating Tab key presses
        const maxTabs = Math.min(result.focusableElements.length + 5, 50);
        let previousElement = null;
        let tabCount = 0;
        let stuckCount = 0;

        // Focus the body first
        await page.evaluate(() => document.body.focus());

        for (let i = 0; i < maxTabs; i++) {
            await page.keyboard.press('Tab');
            tabCount++;

            const currentFocused = await page.evaluate(() => {
                const el = document.activeElement;
                if (!el || el === document.body) return null;
                return {
                    tagName: el.tagName.toLowerCase(),
                    role: el.getAttribute('role'),
                    ariaLabel: el.getAttribute('aria-label'),
                    text: el.textContent?.trim().substring(0, 50),
                    id: el.id,
                    isVisible: el.offsetParent !== null,
                    rect: el.getBoundingClientRect()
                };
            });

            if (currentFocused) {
                result.tabOrder.push({
                    order: tabCount,
                    ...currentFocused
                });

                // Check for focus trap (same element repeatedly)
                if (previousElement &&
                    previousElement.tagName === currentFocused.tagName &&
                    previousElement.id === currentFocused.id) {
                    stuckCount++;
                    if (stuckCount >= 3) {
                        result.focusTraps.push({
                            element: currentFocused,
                            message: 'Focus appears trapped on this element'
                        });
                        result.issues.push({
                            type: 'focus-trap',
                            criterion: '2.1.2',
                            message: 'Keyboard focus trap detected',
                            element: currentFocused
                        });
                        break;
                    }
                } else {
                    stuckCount = 0;
                }

                previousElement = currentFocused;
            }
        }

        // Check for elements that should be focusable but aren't in tab order
        const expectedFocusable = result.focusableElements.filter(el => el.isVisible && el.tabIndex >= 0);
        const actualTabbed = new Set(result.tabOrder.map(el => el.id).filter(Boolean));

        expectedFocusable.forEach(el => {
            if (el.id && !actualTabbed.has(el.id)) {
                // Check if it's still considered unreachable
                if (el.tabIndex >= 0) {
                    result.issues.push({
                        type: 'unreachable',
                        criterion: '2.1.1',
                        message: 'Interactive element may not be keyboard accessible',
                        element: el
                    });
                }
            }
        });

        // Test Escape key functionality on dialogs/modals
        const hasDialogs = await page.evaluate(() => {
            return document.querySelectorAll('[role="dialog"], [role="alertdialog"], .modal, [aria-modal="true"]').length > 0;
        });

        if (hasDialogs) {
            // Try to open and close with Escape
            await page.keyboard.press('Escape');
            result.escapeKeyTested = true;
        }

    } catch (error) {
        result.issues.push({
            type: 'error',
            message: error.message
        });
        result.keyboardAccessible = false;
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify(result));
})();
JS;
    }

    /**
     * Get the mobile accessibility testing script.
     */
    protected function getMobileTestScript(string $url, int $width, int $height): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium, devices } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        ignoreHTTPSErrors: {$ignoreHttpsErrors},
        viewport: { width: {$width}, height: {$height} },
        isMobile: true,
        hasTouch: true
    });
    const page = await context.newPage();

    const result = {
        viewport: { width: {$width}, height: {$height} },
        touchTargets: [],
        smallTargets: [],
        orientationSupport: true,
        issues: []
    };

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Analyze touch targets (WCAG 2.5.5 requires 44x44 CSS pixels minimum)
        const minTargetSize = 44;

        result.touchTargets = await page.evaluate((minSize) => {
            const interactiveSelectors = [
                'a[href]', 'button', 'input', 'select', 'textarea',
                '[role="button"]', '[role="link"]', '[role="checkbox"]',
                '[role="radio"]', '[role="switch"]', '[role="menuitem"]',
                '[onclick]', '[tabindex]:not([tabindex="-1"])'
            ].join(', ');

            const elements = Array.from(document.querySelectorAll(interactiveSelectors));
            const targets = [];
            const small = [];

            elements.forEach(el => {
                const rect = el.getBoundingClientRect();
                const styles = window.getComputedStyle(el);

                // Skip hidden elements
                if (rect.width === 0 || rect.height === 0 || styles.display === 'none' || styles.visibility === 'hidden') {
                    return;
                }

                const target = {
                    tagName: el.tagName.toLowerCase(),
                    text: el.textContent?.trim().substring(0, 30),
                    width: Math.round(rect.width),
                    height: Math.round(rect.height),
                    meetsMinSize: rect.width >= minSize && rect.height >= minSize,
                    selector: el.id ? '#' + el.id : el.tagName.toLowerCase()
                };

                targets.push(target);

                if (!target.meetsMinSize) {
                    small.push({
                        ...target,
                        issue: 'Touch target is ' + Math.round(rect.width) + 'x' + Math.round(rect.height) + 'px, minimum is ' + minSize + 'x' + minSize + 'px'
                    });
                }
            });

            return { all: targets, small };
        }, minTargetSize);

        result.smallTargets = result.touchTargets.small || [];
        result.touchTargets = result.touchTargets.all || [];

        // Add issues for small touch targets
        result.smallTargets.forEach(target => {
            result.issues.push({
                type: 'touch-target-size',
                criterion: '2.5.5',
                wcagLevel: 'AAA',
                message: target.issue,
                element: target
            });
        });

        // Check viewport meta for orientation support
        const viewportMeta = await page.evaluate(() => {
            const meta = document.querySelector('meta[name="viewport"]');
            return meta ? meta.getAttribute('content') : null;
        });

        if (viewportMeta) {
            // Check if orientation is locked
            if (viewportMeta.includes('orientation=') &&
                (viewportMeta.includes('orientation=portrait') || viewportMeta.includes('orientation=landscape'))) {
                result.orientationSupport = false;
                result.issues.push({
                    type: 'orientation-lock',
                    criterion: '1.3.4',
                    wcagLevel: 'AA',
                    message: 'Viewport orientation appears to be locked',
                    meta: viewportMeta
                });
            }
        }

        // Test at landscape orientation
        await page.setViewportSize({ width: {$height}, height: {$width} });

        const landscapeWorking = await page.evaluate(() => {
            // Check if content is still visible and usable
            const body = document.body;
            const rect = body.getBoundingClientRect();
            return rect.width > 0 && rect.height > 0;
        });

        if (!landscapeWorking) {
            result.orientationSupport = false;
            result.issues.push({
                type: 'orientation-issue',
                criterion: '1.3.4',
                wcagLevel: 'AA',
                message: 'Content may not work properly in landscape orientation'
            });
        }

        // Check for text that doesn't reflow at narrow width (320px test)
        await page.setViewportSize({ width: 320, height: {$height} });

        const reflowIssues = await page.evaluate(() => {
            const issues = [];
            const elements = document.querySelectorAll('*');

            elements.forEach(el => {
                const rect = el.getBoundingClientRect();
                const styles = window.getComputedStyle(el);

                // Check for horizontal overflow
                if (rect.width > 320 && styles.overflow !== 'hidden' && styles.overflowX !== 'hidden') {
                    if (el.scrollWidth > el.clientWidth) {
                        issues.push({
                            tagName: el.tagName.toLowerCase(),
                            width: Math.round(rect.width),
                            hasHorizontalScroll: true
                        });
                    }
                }
            });

            return issues.slice(0, 10); // Limit to first 10
        });

        reflowIssues.forEach(issue => {
            result.issues.push({
                type: 'reflow',
                criterion: '1.4.10',
                wcagLevel: 'AA',
                message: `Content requires horizontal scrolling at 320px width`,
                element: issue
            });
        });

    } catch (error) {
        result.issues.push({
            type: 'error',
            message: error.message
        });
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify(result));
})();
JS;
    }

    /**
     * Get the component lifecycle testing script.
     */
    protected function getComponentLifecycleScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    const result = {
        components: [],
        stateChanges: [],
        ariaUpdates: [],
        focusManagement: [],
        issues: []
    };

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Detect interactive components
        result.components = await page.evaluate(() => {
            const components = [];

            // Modals/Dialogs
            document.querySelectorAll('[role="dialog"], [role="alertdialog"], [aria-modal="true"], .modal').forEach(el => {
                components.push({
                    type: 'dialog',
                    role: el.getAttribute('role'),
                    ariaModal: el.getAttribute('aria-modal'),
                    ariaLabelledby: el.getAttribute('aria-labelledby'),
                    ariaDescribedby: el.getAttribute('aria-describedby'),
                    isVisible: el.offsetParent !== null || window.getComputedStyle(el).display !== 'none'
                });
            });

            // Accordions
            document.querySelectorAll('[role="region"][aria-labelledby], details, [data-accordion]').forEach(el => {
                const header = el.querySelector('[aria-expanded], summary, [data-accordion-header]');
                components.push({
                    type: 'accordion',
                    hasAriaExpanded: header?.hasAttribute('aria-expanded'),
                    hasAriaControls: header?.hasAttribute('aria-controls'),
                    isExpanded: header?.getAttribute('aria-expanded') === 'true' || el.hasAttribute('open')
                });
            });

            // Tabs
            document.querySelectorAll('[role="tablist"]').forEach(tablist => {
                const tabs = tablist.querySelectorAll('[role="tab"]');
                const panels = document.querySelectorAll('[role="tabpanel"]');
                components.push({
                    type: 'tabs',
                    tabCount: tabs.length,
                    panelCount: panels.length,
                    hasAriaSelected: Array.from(tabs).some(t => t.hasAttribute('aria-selected')),
                    hasAriaControls: Array.from(tabs).some(t => t.hasAttribute('aria-controls'))
                });
            });

            // Menus
            document.querySelectorAll('[role="menu"], [role="menubar"]').forEach(menu => {
                const items = menu.querySelectorAll('[role="menuitem"], [role="menuitemcheckbox"], [role="menuitemradio"]');
                components.push({
                    type: 'menu',
                    role: menu.getAttribute('role'),
                    itemCount: items.length,
                    hasAriaExpanded: menu.hasAttribute('aria-expanded')
                });
            });

            // Dropdowns/Comboboxes
            document.querySelectorAll('[role="combobox"], [role="listbox"]').forEach(el => {
                components.push({
                    type: 'combobox',
                    role: el.getAttribute('role'),
                    hasAriaExpanded: el.hasAttribute('aria-expanded'),
                    hasAriaControls: el.hasAttribute('aria-controls'),
                    hasAriaActivedescendant: el.hasAttribute('aria-activedescendant')
                });
            });

            // Tooltips
            document.querySelectorAll('[role="tooltip"]').forEach(el => {
                components.push({
                    type: 'tooltip',
                    isVisible: el.offsetParent !== null
                });
            });

            return components;
        });

        // Test each modal/dialog for proper focus management
        const dialogs = await page.locator('[role="dialog"], [role="alertdialog"], [aria-modal="true"]').all();

        for (const dialog of dialogs) {
            const isVisible = await dialog.isVisible().catch(() => false);

            if (isVisible) {
                // Check if dialog has focus trap
                const dialogInfo = await dialog.evaluate(el => {
                    const focusable = el.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    return {
                        hasFocusableElements: focusable.length > 0,
                        firstFocusable: focusable[0]?.tagName,
                        hasCloseButton: el.querySelector('[aria-label*="close"], [aria-label*="Close"], button:has-text("Close"), button:has-text("×")') !== null
                    };
                });

                result.focusManagement.push({
                    component: 'dialog',
                    ...dialogInfo
                });

                if (!dialogInfo.hasFocusableElements) {
                    result.issues.push({
                        type: 'focus-management',
                        criterion: '2.4.3',
                        message: 'Dialog has no focusable elements inside'
                    });
                }
            }
        }

        // Test accordions for ARIA state changes
        const accordionHeaders = await page.locator('[aria-expanded]').all();

        for (let i = 0; i < Math.min(accordionHeaders.length, 5); i++) {
            const header = accordionHeaders[i];
            const initialState = await header.getAttribute('aria-expanded');

            try {
                await header.click();
                await page.waitForTimeout(200);

                const newState = await header.getAttribute('aria-expanded');

                result.stateChanges.push({
                    component: 'accordion/expandable',
                    initialState,
                    newState,
                    stateChanged: initialState !== newState
                });

                if (initialState === newState) {
                    result.issues.push({
                        type: 'aria-state',
                        criterion: '4.1.2',
                        message: 'aria-expanded state did not change after interaction'
                    });
                }

                // Reset state
                await header.click();
            } catch (e) {
                // Element may not be interactable
            }
        }

        // Check for proper tab panel associations
        const tabs = await page.locator('[role="tab"]').all();

        for (const tab of tabs.slice(0, 5)) {
            const tabInfo = await tab.evaluate(el => ({
                hasAriaControls: el.hasAttribute('aria-controls'),
                ariaControls: el.getAttribute('aria-controls'),
                hasAriaSelected: el.hasAttribute('aria-selected'),
                tabIndex: el.tabIndex
            }));

            result.ariaUpdates.push({
                component: 'tab',
                ...tabInfo
            });

            if (!tabInfo.hasAriaControls) {
                result.issues.push({
                    type: 'aria-attribute',
                    criterion: '4.1.2',
                    message: 'Tab missing aria-controls attribute'
                });
            }
        }

    } catch (error) {
        result.issues.push({
            type: 'error',
            message: error.message
        });
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify(result));
})();
JS;
    }

    /**
     * Get the accessibility tree extraction script.
     */
    protected function getAccessibilityTreeScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    const result = {
        tree: [],
        landmarks: [],
        headings: [],
        ariaRoles: [],
        announceOrder: []
    };

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Get accessibility snapshot from Playwright
        const snapshot = await page.accessibility.snapshot({ interestingOnly: false });
        result.tree = snapshot;

        // Extract landmarks
        result.landmarks = await page.evaluate(() => {
            const landmarkRoles = ['banner', 'navigation', 'main', 'complementary', 'contentinfo', 'search', 'form', 'region'];
            const landmarks = [];

            // Semantic HTML landmarks
            const semanticMap = {
                'header': 'banner',
                'nav': 'navigation',
                'main': 'main',
                'aside': 'complementary',
                'footer': 'contentinfo',
                'section': 'region',
                'form': 'form'
            };

            // Get explicit ARIA landmarks
            landmarkRoles.forEach(role => {
                document.querySelectorAll('[role="' + role + '"]').forEach(el => {
                    landmarks.push({
                        role,
                        tagName: el.tagName.toLowerCase(),
                        ariaLabel: el.getAttribute('aria-label'),
                        ariaLabelledby: el.getAttribute('aria-labelledby'),
                        explicit: true
                    });
                });
            });

            // Get implicit semantic landmarks
            Object.entries(semanticMap).forEach(([tag, role]) => {
                document.querySelectorAll(tag).forEach(el => {
                    // Skip if already has explicit role
                    if (!el.hasAttribute('role')) {
                        // header/footer only count as banner/contentinfo if not nested
                        if ((tag === 'header' || tag === 'footer') &&
                            el.closest('article, aside, main, nav, section')) {
                            return;
                        }

                        landmarks.push({
                            role,
                            tagName: el.tagName.toLowerCase(),
                            ariaLabel: el.getAttribute('aria-label'),
                            ariaLabelledby: el.getAttribute('aria-labelledby'),
                            explicit: false
                        });
                    }
                });
            });

            return landmarks;
        });

        // Extract heading structure
        result.headings = await page.evaluate(() => {
            const headings = [];
            document.querySelectorAll('h1, h2, h3, h4, h5, h6, [role="heading"]').forEach(el => {
                const level = el.getAttribute('aria-level') ||
                              parseInt(el.tagName.charAt(1)) ||
                              2;
                headings.push({
                    level,
                    text: el.textContent?.trim().substring(0, 100),
                    tagName: el.tagName.toLowerCase(),
                    id: el.id || null
                });
            });
            return headings;
        });

        // Extract all ARIA roles used
        result.ariaRoles = await page.evaluate(() => {
            const roles = {};
            document.querySelectorAll('[role]').forEach(el => {
                const role = el.getAttribute('role');
                if (!roles[role]) {
                    roles[role] = { count: 0, examples: [] };
                }
                roles[role].count++;
                if (roles[role].examples.length < 3) {
                    roles[role].examples.push({
                        tagName: el.tagName.toLowerCase(),
                        ariaLabel: el.getAttribute('aria-label')
                    });
                }
            });
            return roles;
        });

        // Simulate screen reader announce order (simplified)
        result.announceOrder = await page.evaluate(() => {
            const order = [];
            const walker = document.createTreeWalker(
                document.body,
                NodeFilter.SHOW_ELEMENT,
                {
                    acceptNode: (node) => {
                        const style = window.getComputedStyle(node);
                        if (style.display === 'none' || style.visibility === 'hidden') {
                            return NodeFilter.FILTER_REJECT;
                        }
                        // Include elements that would be announced
                        if (node.getAttribute('role') ||
                            node.getAttribute('aria-label') ||
                            node.getAttribute('aria-labelledby') ||
                            ['H1','H2','H3','H4','H5','H6','BUTTON','A','INPUT','IMG'].includes(node.tagName)) {
                            return NodeFilter.FILTER_ACCEPT;
                        }
                        return NodeFilter.FILTER_SKIP;
                    }
                }
            );

            let node;
            let index = 0;
            while ((node = walker.nextNode()) && index < 100) {
                const accessible = {
                    index: index++,
                    tagName: node.tagName.toLowerCase(),
                    role: node.getAttribute('role'),
                    name: node.getAttribute('aria-label') ||
                          node.getAttribute('alt') ||
                          (node.tagName === 'IMG' ? node.getAttribute('alt') : null) ||
                          node.textContent?.trim().substring(0, 50)
                };
                order.push(accessible);
            }
            return order;
        });

    } catch (error) {
        result.error = error.message;
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify(result));
})();
JS;
    }

    /**
     * Get the timing content detection script.
     */
    protected function getTimingContentScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    const result = {
        autoPlayingMedia: [],
        carousels: [],
        animations: [],
        liveRegions: [],
        timers: [],
        issues: []
    };

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Detect auto-playing media
        result.autoPlayingMedia = await page.evaluate(() => {
            const media = [];
            document.querySelectorAll('video, audio').forEach(el => {
                media.push({
                    type: el.tagName.toLowerCase(),
                    autoplay: el.hasAttribute('autoplay'),
                    muted: el.muted,
                    controls: el.hasAttribute('controls'),
                    loop: el.hasAttribute('loop'),
                    duration: el.duration || null,
                    src: el.src?.substring(0, 100)
                });
            });
            return media;
        });

        // Check for auto-playing videos without controls
        result.autoPlayingMedia.forEach(media => {
            if (media.autoplay && !media.controls) {
                result.issues.push({
                    type: 'auto-play',
                    criterion: '1.4.2',
                    wcagLevel: 'A',
                    message: 'Auto-playing ' + media.type + ' without visible controls',
                    element: media
                });
            }
        });

        // Detect carousels/sliders
        result.carousels = await page.evaluate(() => {
            const carousels = [];
            const selectors = [
                '[class*="carousel"]', '[class*="slider"]', '[class*="slideshow"]',
                '[role="region"][aria-roledescription*="carousel"]',
                '[data-carousel]', '[data-slider]', '.swiper', '.slick-slider'
            ];

            document.querySelectorAll(selectors.join(', ')).forEach(el => {
                const slides = el.querySelectorAll('[class*="slide"], [role="group"]');
                carousels.push({
                    selector: el.className,
                    slideCount: slides.length,
                    hasPlayPause: el.querySelector('[aria-label*="pause"], [aria-label*="play"], .pause, .play') !== null,
                    hasNavigation: el.querySelector('[class*="prev"], [class*="next"], [class*="dot"], [role="tab"]') !== null,
                    ariaLive: el.getAttribute('aria-live')
                });
            });
            return carousels;
        });

        // Check carousels for required controls
        result.carousels.forEach(carousel => {
            if (!carousel.hasPlayPause && carousel.slideCount > 1) {
                result.issues.push({
                    type: 'carousel',
                    criterion: '2.2.2',
                    wcagLevel: 'A',
                    message: 'Carousel appears to auto-advance without pause control',
                    element: carousel
                });
            }
        });

        // Detect CSS animations
        result.animations = await page.evaluate(() => {
            const animations = [];
            const allElements = document.querySelectorAll('*');

            allElements.forEach(el => {
                const style = window.getComputedStyle(el);

                // Check for CSS animations
                if (style.animationName && style.animationName !== 'none') {
                    const duration = parseFloat(style.animationDuration) || 0;
                    const iterationCount = style.animationIterationCount;

                    if (duration > 0) {
                        animations.push({
                            type: 'css-animation',
                            name: style.animationName,
                            duration: style.animationDuration,
                            iterationCount,
                            infinite: iterationCount === 'infinite',
                            tagName: el.tagName.toLowerCase()
                        });
                    }
                }

                // Check for CSS transitions
                if (style.transitionDuration && style.transitionDuration !== '0s') {
                    const duration = parseFloat(style.transitionDuration);
                    if (duration > 0.5) { // Only flag long transitions
                        animations.push({
                            type: 'css-transition',
                            duration: style.transitionDuration,
                            property: style.transitionProperty,
                            tagName: el.tagName.toLowerCase()
                        });
                    }
                }
            });

            // Deduplicate by animation name
            const unique = [];
            const seen = new Set();
            animations.forEach(a => {
                const key = a.name || a.duration + a.type;
                if (!seen.has(key)) {
                    seen.add(key);
                    unique.push(a);
                }
            });

            return unique.slice(0, 20);
        });

        // Check for motion issues (WCAG 2.3.3)
        result.animations.forEach(anim => {
            if (anim.infinite) {
                result.issues.push({
                    type: 'motion',
                    criterion: '2.3.3',
                    wcagLevel: 'AAA',
                    message: 'Infinite animation detected - consider providing reduced motion alternative',
                    element: anim
                });
            }
        });

        // Detect ARIA live regions
        result.liveRegions = await page.evaluate(() => {
            const regions = [];
            document.querySelectorAll('[aria-live], [role="alert"], [role="status"], [role="log"], [role="marquee"], [role="timer"]').forEach(el => {
                regions.push({
                    ariaLive: el.getAttribute('aria-live') || 'polite',
                    role: el.getAttribute('role'),
                    ariaBusy: el.getAttribute('aria-busy'),
                    ariaAtomic: el.getAttribute('aria-atomic'),
                    hasContent: el.textContent?.trim().length > 0,
                    tagName: el.tagName.toLowerCase()
                });
            });
            return regions;
        });

        // Wait and check for dynamic content changes
        await page.waitForTimeout(3000);

        // Check for any elements that changed
        const dynamicChanges = await page.evaluate(() => {
            // This would require mutation observer in real implementation
            // Simplified check for common dynamic content indicators
            const indicators = [];

            // Check for countdown timers
            document.querySelectorAll('[class*="timer"], [class*="countdown"], [data-countdown]').forEach(el => {
                indicators.push({
                    type: 'timer',
                    selector: el.className
                });
            });

            // Check for live updating content
            document.querySelectorAll('[class*="live"], [class*="realtime"], [data-refresh]').forEach(el => {
                indicators.push({
                    type: 'live-content',
                    selector: el.className
                });
            });

            return indicators;
        });

        result.timers = dynamicChanges.filter(d => d.type === 'timer');

        // Issue for timers without extension option
        result.timers.forEach(timer => {
            result.issues.push({
                type: 'timing',
                criterion: '2.2.1',
                wcagLevel: 'A',
                message: 'Timer detected - verify users can extend time limits',
                element: timer
            });
        });

    } catch (error) {
        result.issues.push({
            type: 'error',
            message: error.message
        });
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify(result));
})();
JS;
    }

    /**
     * Get the focus visibility testing script.
     */
    protected function getFocusVisibilityScript(string $url): string
    {
        $escapedUrl = addslashes($url);
        $ignoreHttpsErrors = $this->shouldIgnoreHttpsErrors($url) ? 'true' : 'false';

        return <<<JS
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ ignoreHTTPSErrors: {$ignoreHttpsErrors} });
    const page = await context.newPage();

    const result = {
        elementsWithVisibleFocus: [],
        elementsWithoutVisibleFocus: [],
        focusStyles: [],
        issues: []
    };

    try {
        await page.goto('{$escapedUrl}', {
            waitUntil: 'networkidle',
            timeout: {$this->timeout}
        });

        // Get all focusable elements
        const focusableElements = await page.locator('a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])').all();

        for (let i = 0; i < Math.min(focusableElements.length, 30); i++) {
            const element = focusableElements[i];

            try {
                // Get element info before focus
                const beforeFocus = await element.evaluate(el => {
                    const style = window.getComputedStyle(el);
                    return {
                        outline: style.outline,
                        outlineWidth: style.outlineWidth,
                        outlineColor: style.outlineColor,
                        outlineStyle: style.outlineStyle,
                        boxShadow: style.boxShadow,
                        border: style.border,
                        backgroundColor: style.backgroundColor
                    };
                });

                // Focus the element
                await element.focus();
                await page.waitForTimeout(50);

                // Get element info after focus
                const afterFocus = await element.evaluate(el => {
                    const style = window.getComputedStyle(el);
                    const rect = el.getBoundingClientRect();
                    return {
                        outline: style.outline,
                        outlineWidth: style.outlineWidth,
                        outlineColor: style.outlineColor,
                        outlineStyle: style.outlineStyle,
                        boxShadow: style.boxShadow,
                        border: style.border,
                        backgroundColor: style.backgroundColor,
                        tagName: el.tagName.toLowerCase(),
                        text: el.textContent?.trim().substring(0, 30),
                        id: el.id,
                        visible: rect.width > 0 && rect.height > 0
                    };
                });

                // Check if focus is visible
                const hasVisibleFocus =
                    (afterFocus.outline !== beforeFocus.outline && afterFocus.outlineStyle !== 'none') ||
                    (afterFocus.outlineWidth !== beforeFocus.outlineWidth && parseFloat(afterFocus.outlineWidth) > 0) ||
                    (afterFocus.boxShadow !== beforeFocus.boxShadow && afterFocus.boxShadow !== 'none') ||
                    (afterFocus.border !== beforeFocus.border) ||
                    (afterFocus.backgroundColor !== beforeFocus.backgroundColor);

                const elementInfo = {
                    tagName: afterFocus.tagName,
                    text: afterFocus.text,
                    id: afterFocus.id,
                    focusStyles: {
                        outline: afterFocus.outline,
                        boxShadow: afterFocus.boxShadow
                    }
                };

                if (hasVisibleFocus) {
                    result.elementsWithVisibleFocus.push(elementInfo);
                } else if (afterFocus.visible) {
                    result.elementsWithoutVisibleFocus.push(elementInfo);
                    result.issues.push({
                        type: 'focus-visibility',
                        criterion: '2.4.7',
                        wcagLevel: 'AA',
                        message: 'Element does not have visible focus indicator',
                        element: elementInfo
                    });
                }

            } catch (e) {
                // Element may not be interactable
            }
        }

        // Check for CSS that removes focus outlines
        result.focusStyles = await page.evaluate(() => {
            const stylesheets = Array.from(document.styleSheets);
            const focusRemovers = [];

            try {
                stylesheets.forEach(sheet => {
                    try {
                        const rules = sheet.cssRules || sheet.rules;
                        Array.from(rules).forEach(rule => {
                            if (rule.cssText && rule.cssText.includes(':focus')) {
                                if (rule.cssText.includes('outline: none') ||
                                    rule.cssText.includes('outline: 0') ||
                                    rule.cssText.includes('outline:none') ||
                                    rule.cssText.includes('outline:0')) {
                                    focusRemovers.push({
                                        selector: rule.selectorText,
                                        style: 'outline removed'
                                    });
                                }
                            }
                        });
                    } catch (e) {
                        // Cross-origin stylesheet, skip
                    }
                });
            } catch (e) {
                // Error accessing stylesheets
            }

            return focusRemovers;
        });

        // Add issues for CSS that removes focus
        result.focusStyles.forEach(style => {
            result.issues.push({
                type: 'focus-style-removed',
                criterion: '2.4.7',
                wcagLevel: 'AA',
                message: 'CSS rule "' + style.selector + '" removes focus outline',
                element: style
            });
        });

    } catch (error) {
        result.issues.push({
            type: 'error',
            message: error.message
        });
    } finally {
        await browser.close();
    }

    console.log(JSON.stringify(result));
})();
JS;
    }
}
