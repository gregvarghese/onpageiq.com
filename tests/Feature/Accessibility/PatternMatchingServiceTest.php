<?php

use App\Models\AriaPattern;
use App\Services\Accessibility\PatternMatchingService;
use Database\Seeders\AriaPatternSeeder;

beforeEach(function () {
    $this->service = new PatternMatchingService;
    // Ensure patterns are seeded
    $this->seed(AriaPatternSeeder::class);
});

describe('PatternMatchingService', function () {
    describe('analyze', function () {
        it('returns expected structure', function () {
            $html = '<html><body><button>Click me</button></body></html>';

            $result = $this->service->analyze($html);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['patterns', 'deviations', 'summary']);
        });

        it('detects button pattern', function () {
            $html = '<html><body><button>Submit</button></body></html>';

            $result = $this->service->analyze($html);

            $buttonPatterns = array_filter($result['patterns'], fn ($p) => $p['pattern']->slug === 'button');
            expect(count($buttonPatterns))->toBeGreaterThan(0);
        });

        it('detects dialog pattern', function () {
            $html = '<html><body><div role="dialog" aria-modal="true" aria-label="Confirm">Content</div></body></html>';

            $result = $this->service->analyze($html);

            $dialogPatterns = array_filter($result['patterns'], fn ($p) => $p['pattern']->slug === 'dialog-modal');
            expect(count($dialogPatterns))->toBeGreaterThan(0);
        });

        it('detects tabs pattern', function () {
            $html = '
                <html><body>
                    <div role="tablist" aria-label="Options">
                        <button role="tab" aria-selected="true" aria-controls="panel1">Tab 1</button>
                        <button role="tab" aria-selected="false" aria-controls="panel2">Tab 2</button>
                    </div>
                    <div role="tabpanel" id="panel1" aria-labelledby="tab1">Content 1</div>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            $tabPatterns = array_filter($result['patterns'], fn ($p) => $p['pattern']->slug === 'tabs');
            expect(count($tabPatterns))->toBeGreaterThan(0);
        });
    });

    describe('pattern detection', function () {
        it('detects native HTML button', function () {
            $html = '<html><body><button>Click</button></body></html>';

            $result = $this->service->analyze($html);

            $buttonDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'button');
            expect($buttonDetected)->toBeTrue();
        });

        it('detects ARIA role button', function () {
            $html = '<html><body><div role="button" tabindex="0">Click</div></body></html>';

            $result = $this->service->analyze($html);

            $buttonDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'button');
            expect($buttonDetected)->toBeTrue();
        });

        it('detects checkbox pattern', function () {
            $html = '<html><body><div role="checkbox" aria-checked="false">Accept</div></body></html>';

            $result = $this->service->analyze($html);

            $checkboxDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'checkbox');
            expect($checkboxDetected)->toBeTrue();
        });

        it('detects native checkbox', function () {
            $html = '<html><body><input type="checkbox" id="accept"><label for="accept">Accept</label></body></html>';

            $result = $this->service->analyze($html);

            $checkboxDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'checkbox');
            expect($checkboxDetected)->toBeTrue();
        });

        it('detects combobox pattern', function () {
            $html = '
                <html><body>
                    <div role="combobox" aria-expanded="false" aria-controls="listbox1">
                        Select option
                    </div>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            $comboboxDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'combobox');
            expect($comboboxDetected)->toBeTrue();
        });

        it('detects native select as combobox', function () {
            $html = '
                <html><body>
                    <select>
                        <option>Option 1</option>
                        <option>Option 2</option>
                    </select>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            $comboboxDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'combobox');
            expect($comboboxDetected)->toBeTrue();
        });

        it('detects slider pattern', function () {
            $html = '
                <html><body>
                    <div role="slider" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                        Volume
                    </div>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            $sliderDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'slider');
            expect($sliderDetected)->toBeTrue();
        });

        it('detects native range input as slider', function () {
            $html = '<html><body><input type="range" min="0" max="100" value="50"></body></html>';

            $result = $this->service->analyze($html);

            $sliderDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'slider');
            expect($sliderDetected)->toBeTrue();
        });

        it('detects menu pattern', function () {
            $html = '
                <html><body>
                    <ul role="menu" aria-label="File">
                        <li role="menuitem">New</li>
                        <li role="menuitem">Open</li>
                        <li role="menuitem">Save</li>
                    </ul>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            $menuDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'menu');
            expect($menuDetected)->toBeTrue();
        });

        it('detects alert pattern', function () {
            $html = '<html><body><div role="alert">Error: Something went wrong</div></body></html>';

            $result = $this->service->analyze($html);

            $alertDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'alert');
            expect($alertDetected)->toBeTrue();
        });

        it('detects tooltip pattern', function () {
            $html = '<html><body><div role="tooltip" id="tip1">Helpful information</div></body></html>';

            $result = $this->service->analyze($html);

            $tooltipDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'tooltip');
            expect($tooltipDetected)->toBeTrue();
        });

        it('detects switch pattern', function () {
            $html = '<html><body><div role="switch" aria-checked="false">Dark mode</div></body></html>';

            $result = $this->service->analyze($html);

            $switchDetected = collect($result['patterns'])
                ->contains(fn ($p) => $p['pattern']->slug === 'switch');
            expect($switchDetected)->toBeTrue();
        });
    });

    describe('deviation detection', function () {
        it('detects missing aria-checked on checkbox', function () {
            $html = '<html><body><div role="checkbox">Accept terms</div></body></html>';

            $result = $this->service->analyze($html);

            $checkboxDeviations = array_filter(
                $result['deviations'],
                fn ($d) => $d['pattern_slug'] === 'checkbox'
            );

            expect(count($checkboxDeviations))->toBeGreaterThan(0);

            $hasAriaCheckedIssue = collect($checkboxDeviations)
                ->flatMap(fn ($d) => $d['issues'])
                ->contains(fn ($i) => $i['type'] === 'missing_attribute' && $i['attribute'] === 'aria-checked');

            expect($hasAriaCheckedIssue)->toBeTrue();
        });

        it('detects missing aria-expanded on combobox', function () {
            $html = '<html><body><div role="combobox" aria-controls="list">Select</div></body></html>';

            $result = $this->service->analyze($html);

            $comboboxDeviations = array_filter(
                $result['deviations'],
                fn ($d) => $d['pattern_slug'] === 'combobox'
            );

            expect(count($comboboxDeviations))->toBeGreaterThan(0);
        });

        it('detects missing required attributes on slider', function () {
            $html = '<html><body><div role="slider">Volume</div></body></html>';

            $result = $this->service->analyze($html);

            $sliderDeviations = array_filter(
                $result['deviations'],
                fn ($d) => $d['pattern_slug'] === 'slider'
            );

            expect(count($sliderDeviations))->toBeGreaterThan(0);
        });

        it('does not report deviations for valid patterns', function () {
            $html = '
                <html><body>
                    <div role="checkbox" aria-checked="true" aria-label="Accept">Accept</div>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            $checkboxDeviations = array_filter(
                $result['deviations'],
                fn ($d) => $d['pattern_slug'] === 'checkbox'
            );

            // Should have no missing aria-checked issues
            $missingChecked = collect($checkboxDeviations)
                ->flatMap(fn ($d) => $d['issues'])
                ->contains(fn ($i) => $i['type'] === 'missing_attribute' && $i['attribute'] === 'aria-checked');

            expect($missingChecked)->toBeFalse();
        });
    });

    describe('summary generation', function () {
        it('generates correct pattern counts', function () {
            $html = '
                <html><body>
                    <button>Button 1</button>
                    <button>Button 2</button>
                    <a href="/page">Link</a>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            expect($result['summary']['total_patterns_detected'])->toBeGreaterThan(0);
            expect($result['summary']['pattern_counts'])->toBeArray();
        });

        it('calculates compliance score', function () {
            $html = '<html><body><button>Valid button</button></body></html>';

            $result = $this->service->analyze($html);

            expect($result['summary']['compliance_score'])->toBeGreaterThanOrEqual(0)
                ->and($result['summary']['compliance_score'])->toBeLessThanOrEqual(100);
        });

        it('counts deviations by type', function () {
            $html = '
                <html><body>
                    <div role="checkbox">Missing checked</div>
                    <div role="slider">Missing values</div>
                </body></html>
            ';

            $result = $this->service->analyze($html);

            expect($result['summary']['deviations_by_type'])->toBeArray();
        });
    });

    describe('detectElementPattern', function () {
        it('returns pattern for matching element', function () {
            $elementData = [
                'tag' => 'div',
                'role' => 'button',
                'attributes' => ['role' => 'button', 'tabindex' => '0'],
            ];

            $pattern = $this->service->detectElementPattern($elementData);

            expect($pattern)->not->toBeNull()
                ->and($pattern->slug)->toBe('button');
        });

        it('returns null for non-matching element', function () {
            $elementData = [
                'tag' => 'div',
                'role' => null,
                'attributes' => [],
            ];

            $pattern = $this->service->detectElementPattern($elementData);

            expect($pattern)->toBeNull();
        });
    });
});

describe('AriaPattern', function () {
    describe('model', function () {
        it('casts JSON fields correctly', function () {
            $pattern = AriaPattern::where('slug', 'button')->first();

            expect($pattern->required_roles)->toBeArray()
                ->and($pattern->keyboard_interactions)->toBeArray()
                ->and($pattern->detection_rules)->toBeArray();
        });

        it('has documentation URL', function () {
            $pattern = AriaPattern::where('slug', 'dialog-modal')->first();

            expect($pattern->documentation_url)->toContain('w3.org/WAI/ARIA/apg');
        });
    });

    describe('scopes', function () {
        it('filters built-in patterns', function () {
            $builtIn = AriaPattern::builtIn()->get();

            expect($builtIn->count())->toBeGreaterThan(0);
            $builtIn->each(fn ($p) => expect($p->is_custom)->toBeFalse());
        });

        it('filters by category', function () {
            $widgets = AriaPattern::category(AriaPattern::CATEGORY_WIDGET)->get();

            expect($widgets->count())->toBeGreaterThan(0);
            $widgets->each(fn ($p) => expect($p->category)->toBe(AriaPattern::CATEGORY_WIDGET));
        });
    });

    describe('matchesElement', function () {
        it('matches element by role', function () {
            $pattern = AriaPattern::where('slug', 'button')->first();

            $result = $pattern->matchesElement([
                'tag' => 'div',
                'role' => 'button',
                'attributes' => ['role' => 'button'],
            ]);

            expect($result)->toBeTrue();
        });

        it('does not match wrong role', function () {
            $pattern = AriaPattern::where('slug', 'button')->first();

            $result = $pattern->matchesElement([
                'tag' => 'div',
                'role' => 'checkbox',
                'attributes' => ['role' => 'checkbox'],
            ]);

            expect($result)->toBeFalse();
        });
    });

    describe('getDeviations', function () {
        it('returns missing attributes', function () {
            $pattern = AriaPattern::where('slug', 'checkbox')->first();

            $deviations = $pattern->getDeviations([
                'tag' => 'div',
                'role' => 'checkbox',
                'attributes' => ['role' => 'checkbox'],
            ]);

            $missingAttr = collect($deviations)
                ->contains(fn ($d) => $d['type'] === 'missing_attribute');

            expect($missingAttr)->toBeTrue();
        });

        it('returns empty for valid pattern', function () {
            $pattern = AriaPattern::where('slug', 'checkbox')->first();

            $deviations = $pattern->getDeviations([
                'tag' => 'div',
                'role' => 'checkbox',
                'attributes' => ['role' => 'checkbox', 'aria-checked' => 'false'],
            ]);

            $missingChecked = collect($deviations)
                ->contains(fn ($d) => $d['type'] === 'missing_attribute' && $d['attribute'] === 'aria-checked');

            expect($missingChecked)->toBeFalse();
        });
    });
});
