<?php

use App\Services\Accessibility\PlaywrightAccessibilityService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->service = new PlaywrightAccessibilityService;
});

describe('PlaywrightAccessibilityService', function () {
    describe('testKeyboardJourney', function () {
        it('returns expected structure', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'focusableElements' => [
                        ['index' => 0, 'tagName' => 'a', 'text' => 'Home', 'tabIndex' => 0],
                        ['index' => 1, 'tagName' => 'button', 'text' => 'Submit', 'tabIndex' => 0],
                    ],
                    'tabOrder' => [
                        ['order' => 1, 'tagName' => 'a', 'text' => 'Home'],
                        ['order' => 2, 'tagName' => 'button', 'text' => 'Submit'],
                    ],
                    'focusTraps' => [],
                    'keyboardAccessible' => true,
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testKeyboardJourney('https://example.com');

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['focusableElements', 'tabOrder', 'focusTraps', 'keyboardAccessible', 'issues'])
                ->and($result['focusableElements'])->toHaveCount(2)
                ->and($result['tabOrder'])->toHaveCount(2)
                ->and($result['keyboardAccessible'])->toBeTrue()
                ->and($result['issues'])->toBeEmpty();
        });

        it('detects focus traps', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'focusableElements' => [],
                    'tabOrder' => [],
                    'focusTraps' => [
                        ['element' => ['tagName' => 'div'], 'message' => 'Focus appears trapped on this element'],
                    ],
                    'keyboardAccessible' => true,
                    'issues' => [
                        ['type' => 'focus-trap', 'criterion' => '2.1.2', 'message' => 'Keyboard focus trap detected'],
                    ],
                ])),
            ]);

            $result = $this->service->testKeyboardJourney('https://example.com');

            expect($result['focusTraps'])->toHaveCount(1)
                ->and($result['issues'])->toHaveCount(1)
                ->and($result['issues'][0]['criterion'])->toBe('2.1.2');
        });

        it('handles script errors gracefully', function () {
            Process::fake([
                '*' => Process::result('invalid json'),
            ]);

            $result = $this->service->testKeyboardJourney('https://example.com');

            expect($result)->toBeArray()
                ->and($result['keyboardAccessible'])->toBeFalse()
                ->and($result['issues'])->not->toBeEmpty();
        });
    });

    describe('testMobileAccessibility', function () {
        it('returns expected structure with viewport info', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'viewport' => ['width' => 375, 'height' => 667],
                    'touchTargets' => [
                        ['tagName' => 'button', 'width' => 48, 'height' => 48, 'meetsMinSize' => true],
                    ],
                    'smallTargets' => [],
                    'orientationSupport' => true,
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testMobileAccessibility('https://example.com');

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['viewport', 'touchTargets', 'smallTargets', 'orientationSupport', 'issues'])
                ->and($result['viewport']['width'])->toBe(375)
                ->and($result['viewport']['height'])->toBe(667)
                ->and($result['touchTargets'])->toHaveCount(1)
                ->and($result['orientationSupport'])->toBeTrue();
        });

        it('detects small touch targets', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'viewport' => ['width' => 375, 'height' => 667],
                    'touchTargets' => [
                        ['tagName' => 'a', 'width' => 20, 'height' => 20, 'meetsMinSize' => false],
                    ],
                    'smallTargets' => [
                        ['tagName' => 'a', 'width' => 20, 'height' => 20, 'issue' => 'Touch target is 20x20px, minimum is 44x44px'],
                    ],
                    'orientationSupport' => true,
                    'issues' => [
                        ['type' => 'touch-target-size', 'criterion' => '2.5.5', 'wcagLevel' => 'AAA'],
                    ],
                ])),
            ]);

            $result = $this->service->testMobileAccessibility('https://example.com');

            expect($result['smallTargets'])->toHaveCount(1)
                ->and($result['issues'])->toHaveCount(1)
                ->and($result['issues'][0]['criterion'])->toBe('2.5.5');
        });

        it('detects orientation lock issues', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'viewport' => ['width' => 375, 'height' => 667],
                    'touchTargets' => [],
                    'smallTargets' => [],
                    'orientationSupport' => false,
                    'issues' => [
                        ['type' => 'orientation-lock', 'criterion' => '1.3.4', 'wcagLevel' => 'AA'],
                    ],
                ])),
            ]);

            $result = $this->service->testMobileAccessibility('https://example.com');

            expect($result['orientationSupport'])->toBeFalse()
                ->and($result['issues'][0]['criterion'])->toBe('1.3.4');
        });

        it('accepts custom viewport dimensions', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'viewport' => ['width' => 768, 'height' => 1024],
                    'touchTargets' => [],
                    'smallTargets' => [],
                    'orientationSupport' => true,
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testMobileAccessibility('https://example.com', 768, 1024);

            expect($result['viewport']['width'])->toBe(768)
                ->and($result['viewport']['height'])->toBe(1024);
        });
    });

    describe('testComponentLifecycle', function () {
        it('returns expected structure', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'components' => [
                        ['type' => 'dialog', 'role' => 'dialog', 'ariaModal' => 'true'],
                        ['type' => 'tabs', 'tabCount' => 3, 'panelCount' => 3],
                    ],
                    'stateChanges' => [],
                    'ariaUpdates' => [],
                    'focusManagement' => [],
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testComponentLifecycle('https://example.com');

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['components', 'stateChanges', 'ariaUpdates', 'focusManagement', 'issues'])
                ->and($result['components'])->toHaveCount(2);
        });

        it('detects dialogs with focus management issues', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'components' => [
                        ['type' => 'dialog', 'role' => 'dialog'],
                    ],
                    'stateChanges' => [],
                    'ariaUpdates' => [],
                    'focusManagement' => [
                        ['component' => 'dialog', 'hasFocusableElements' => false],
                    ],
                    'issues' => [
                        ['type' => 'focus-management', 'criterion' => '2.4.3', 'message' => 'Dialog has no focusable elements inside'],
                    ],
                ])),
            ]);

            $result = $this->service->testComponentLifecycle('https://example.com');

            expect($result['focusManagement'])->toHaveCount(1)
                ->and($result['issues'])->toHaveCount(1)
                ->and($result['issues'][0]['criterion'])->toBe('2.4.3');
        });

        it('detects aria-expanded state change issues', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'components' => [
                        ['type' => 'accordion', 'hasAriaExpanded' => true],
                    ],
                    'stateChanges' => [
                        ['component' => 'accordion/expandable', 'initialState' => 'false', 'newState' => 'false', 'stateChanged' => false],
                    ],
                    'ariaUpdates' => [],
                    'focusManagement' => [],
                    'issues' => [
                        ['type' => 'aria-state', 'criterion' => '4.1.2', 'message' => 'aria-expanded state did not change after interaction'],
                    ],
                ])),
            ]);

            $result = $this->service->testComponentLifecycle('https://example.com');

            expect($result['stateChanges'][0]['stateChanged'])->toBeFalse()
                ->and($result['issues'][0]['criterion'])->toBe('4.1.2');
        });
    });

    describe('getAccessibilityTree', function () {
        it('returns expected structure', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'tree' => ['role' => 'WebArea', 'name' => 'Test Page'],
                    'landmarks' => [
                        ['role' => 'banner', 'tagName' => 'header', 'explicit' => false],
                        ['role' => 'main', 'tagName' => 'main', 'explicit' => false],
                        ['role' => 'navigation', 'tagName' => 'nav', 'explicit' => false],
                    ],
                    'headings' => [
                        ['level' => 1, 'text' => 'Welcome', 'tagName' => 'h1'],
                        ['level' => 2, 'text' => 'Features', 'tagName' => 'h2'],
                    ],
                    'ariaRoles' => [
                        'button' => ['count' => 5, 'examples' => []],
                    ],
                ])),
            ]);

            $result = $this->service->getAccessibilityTree('https://example.com');

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['tree', 'landmarks', 'headings', 'ariaRoles'])
                ->and($result['landmarks'])->toHaveCount(3)
                ->and($result['headings'])->toHaveCount(2);
        });

        it('extracts ARIA roles with counts', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'tree' => [],
                    'landmarks' => [],
                    'headings' => [],
                    'ariaRoles' => [
                        'button' => ['count' => 5, 'examples' => [['tagName' => 'div']]],
                        'dialog' => ['count' => 1, 'examples' => [['tagName' => 'div']]],
                    ],
                ])),
            ]);

            $result = $this->service->getAccessibilityTree('https://example.com');

            expect($result['ariaRoles'])->toHaveKey('button')
                ->and($result['ariaRoles']['button']['count'])->toBe(5)
                ->and($result['ariaRoles'])->toHaveKey('dialog');
        });
    });

    describe('detectTimingContent', function () {
        it('returns expected structure', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'autoPlayingMedia' => [],
                    'carousels' => [],
                    'animations' => [],
                    'liveRegions' => [],
                    'timers' => [],
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->detectTimingContent('https://example.com');

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['autoPlayingMedia', 'carousels', 'animations', 'liveRegions', 'issues']);
        });

        it('detects auto-playing media without controls', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'autoPlayingMedia' => [
                        ['type' => 'video', 'autoplay' => true, 'controls' => false, 'muted' => false],
                    ],
                    'carousels' => [],
                    'animations' => [],
                    'liveRegions' => [],
                    'issues' => [
                        ['type' => 'auto-play', 'criterion' => '1.4.2', 'wcagLevel' => 'A'],
                    ],
                ])),
            ]);

            $result = $this->service->detectTimingContent('https://example.com');

            expect($result['autoPlayingMedia'])->toHaveCount(1)
                ->and($result['autoPlayingMedia'][0]['autoplay'])->toBeTrue()
                ->and($result['issues'][0]['criterion'])->toBe('1.4.2');
        });

        it('detects carousels without pause control', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'autoPlayingMedia' => [],
                    'carousels' => [
                        ['selector' => 'carousel', 'slideCount' => 5, 'hasPlayPause' => false, 'hasNavigation' => true],
                    ],
                    'animations' => [],
                    'liveRegions' => [],
                    'issues' => [
                        ['type' => 'carousel', 'criterion' => '2.2.2', 'wcagLevel' => 'A'],
                    ],
                ])),
            ]);

            $result = $this->service->detectTimingContent('https://example.com');

            expect($result['carousels'])->toHaveCount(1)
                ->and($result['carousels'][0]['hasPlayPause'])->toBeFalse()
                ->and($result['issues'][0]['criterion'])->toBe('2.2.2');
        });

        it('detects infinite animations', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'autoPlayingMedia' => [],
                    'carousels' => [],
                    'animations' => [
                        ['type' => 'css-animation', 'name' => 'spin', 'infinite' => true],
                    ],
                    'liveRegions' => [],
                    'issues' => [
                        ['type' => 'motion', 'criterion' => '2.3.3', 'wcagLevel' => 'AAA'],
                    ],
                ])),
            ]);

            $result = $this->service->detectTimingContent('https://example.com');

            expect($result['animations'])->toHaveCount(1)
                ->and($result['animations'][0]['infinite'])->toBeTrue()
                ->and($result['issues'][0]['criterion'])->toBe('2.3.3');
        });

        it('detects ARIA live regions', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'autoPlayingMedia' => [],
                    'carousels' => [],
                    'animations' => [],
                    'liveRegions' => [
                        ['ariaLive' => 'polite', 'role' => 'status', 'hasContent' => true],
                        ['ariaLive' => 'assertive', 'role' => 'alert', 'hasContent' => false],
                    ],
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->detectTimingContent('https://example.com');

            expect($result['liveRegions'])->toHaveCount(2)
                ->and($result['liveRegions'][0]['ariaLive'])->toBe('polite')
                ->and($result['liveRegions'][1]['ariaLive'])->toBe('assertive');
        });
    });

    describe('testFocusVisibility', function () {
        it('returns expected structure', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'elementsWithVisibleFocus' => [
                        ['tagName' => 'button', 'text' => 'Submit'],
                    ],
                    'elementsWithoutVisibleFocus' => [],
                    'focusStyles' => [],
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testFocusVisibility('https://example.com');

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['elementsWithVisibleFocus', 'elementsWithoutVisibleFocus', 'focusStyles', 'issues'])
                ->and($result['elementsWithVisibleFocus'])->toHaveCount(1);
        });

        it('detects elements without visible focus', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'elementsWithVisibleFocus' => [],
                    'elementsWithoutVisibleFocus' => [
                        ['tagName' => 'a', 'text' => 'Link without focus'],
                    ],
                    'focusStyles' => [],
                    'issues' => [
                        ['type' => 'focus-visibility', 'criterion' => '2.4.7', 'wcagLevel' => 'AA'],
                    ],
                ])),
            ]);

            $result = $this->service->testFocusVisibility('https://example.com');

            expect($result['elementsWithoutVisibleFocus'])->toHaveCount(1)
                ->and($result['issues'][0]['criterion'])->toBe('2.4.7');
        });

        it('detects CSS that removes focus outline', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'elementsWithVisibleFocus' => [],
                    'elementsWithoutVisibleFocus' => [],
                    'focusStyles' => [
                        ['selector' => '*:focus', 'style' => 'outline removed'],
                        ['selector' => 'a:focus', 'style' => 'outline removed'],
                    ],
                    'issues' => [
                        ['type' => 'focus-style-removed', 'criterion' => '2.4.7'],
                        ['type' => 'focus-style-removed', 'criterion' => '2.4.7'],
                    ],
                ])),
            ]);

            $result = $this->service->testFocusVisibility('https://example.com');

            expect($result['focusStyles'])->toHaveCount(2)
                ->and($result['issues'])->toHaveCount(2);
        });
    });

    describe('HTTPS error handling', function () {
        it('ignores HTTPS errors for .test domains', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'focusableElements' => [],
                    'tabOrder' => [],
                    'focusTraps' => [],
                    'keyboardAccessible' => true,
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testKeyboardJourney('https://mysite.test/page');

            expect($result['keyboardAccessible'])->toBeTrue();

            // Verify the script was created with ignoreHTTPSErrors: true
            Process::assertRan(function ($process) {
                return $process->command[0] === 'node';
            });
        });

        it('ignores HTTPS errors for localhost', function () {
            Process::fake([
                '*' => Process::result(json_encode([
                    'viewport' => ['width' => 375, 'height' => 667],
                    'touchTargets' => [],
                    'smallTargets' => [],
                    'orientationSupport' => true,
                    'issues' => [],
                ])),
            ]);

            $result = $this->service->testMobileAccessibility('https://localhost:8000/page');

            expect($result['viewport'])->toBeArray();
        });
    });
});
