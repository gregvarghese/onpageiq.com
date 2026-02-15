<?php

use App\Services\Accessibility\AriaValidationService;

beforeEach(function () {
    $this->service = new AriaValidationService;
});

describe('AriaValidationService', function () {
    describe('validate', function () {
        it('returns expected structure', function () {
            $html = '<html><body><button>Click</button></body></html>';

            $result = $this->service->validate($html);

            expect($result)->toBeArray()
                ->and($result)->toHaveKeys(['valid', 'issues', 'summary']);
        });

        it('returns valid true when no issues found', function () {
            $html = '
                <html><body>
                    <button>Valid button</button>
                    <a href="/link">Valid link</a>
                </body></html>
            ';

            $result = $this->service->validate($html);

            // May have info-level issues but no errors
            $errors = array_filter($result['issues'], fn ($i) => $i['severity'] === 'error');
            expect(count($errors))->toBe(0);
        });
    });

    describe('checkRequiredAttributes', function () {
        it('detects missing aria-checked on checkbox', function () {
            $html = '<html><body><div role="checkbox">Check me</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'missing-required-attribute');

            expect(count($issues))->toBeGreaterThan(0);
            expect($issues[array_key_first($issues)]['message'])->toContain('aria-checked');
        });

        it('passes when required attributes are present', function () {
            $html = '<html><body><div role="checkbox" aria-checked="false">Check me</div></body></html>';

            $result = $this->service->validate($html);
            $missingAttr = array_filter($result['issues'], fn ($i) => $i['type'] === 'missing-required-attribute' && str_contains($i['message'], 'checkbox')
            );

            expect(count($missingAttr))->toBe(0);
        });

        it('detects missing aria-expanded on combobox', function () {
            $html = '<html><body><div role="combobox">Select</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'missing-required-attribute' && str_contains($i['message'], 'combobox')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('detects missing aria-valuenow on slider', function () {
            $html = '<html><body><div role="slider">Volume</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => str_contains($i['message'], 'aria-valuenow')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });
    });

    describe('checkValidAttributeValues', function () {
        it('detects invalid aria-expanded value', function () {
            $html = '<html><body><button aria-expanded="yes">Toggle</button></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'invalid-value' && str_contains($i['message'], 'aria-expanded')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('accepts valid aria-expanded values', function () {
            $html = '
                <html><body>
                    <button aria-expanded="true">Open</button>
                    <button aria-expanded="false">Closed</button>
                </body></html>
            ';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'invalid-value' && str_contains($i['message'], 'aria-expanded')
            );

            expect(count($issues))->toBe(0);
        });

        it('detects invalid aria-live value', function () {
            $html = '<html><body><div aria-live="loud">Updates</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => str_contains($i['message'], 'aria-live')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('detects non-numeric aria-valuenow', function () {
            $html = '<html><body><div role="slider" aria-valuenow="high">Volume</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => str_contains($i['message'], 'aria-valuenow') && str_contains($i['message'], 'number')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });
    });

    describe('checkIdReferences', function () {
        it('detects broken aria-labelledby reference', function () {
            $html = '<html><body><input aria-labelledby="nonexistent"></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'broken-reference');

            expect(count($issues))->toBeGreaterThan(0);
            expect($issues[array_key_first($issues)]['message'])->toContain('nonexistent');
        });

        it('passes when ID reference exists', function () {
            $html = '
                <html><body>
                    <span id="label">Email</span>
                    <input aria-labelledby="label">
                </body></html>
            ';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'broken-reference');

            expect(count($issues))->toBe(0);
        });

        it('detects broken aria-describedby reference', function () {
            $html = '<html><body><input aria-describedby="missing-help"></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'broken-reference' && str_contains($i['message'], 'missing-help')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('handles multiple ID references', function () {
            $html = '
                <html><body>
                    <span id="label1">First</span>
                    <input aria-labelledby="label1 label2">
                </body></html>
            ';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'broken-reference' && str_contains($i['message'], 'label2')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });
    });

    describe('checkAbstractRoles', function () {
        it('detects use of abstract roles', function () {
            $html = '<html><body><div role="widget">Content</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'abstract-role');

            expect(count($issues))->toBeGreaterThan(0);
            expect($issues[array_key_first($issues)]['message'])->toContain('widget');
        });

        it('detects all abstract roles', function () {
            $html = '
                <html><body>
                    <div role="command">Command</div>
                    <div role="landmark">Landmark</div>
                    <div role="structure">Structure</div>
                </body></html>
            ';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'abstract-role');

            expect(count($issues))->toBe(3);
        });
    });

    describe('checkProhibitedAttributes', function () {
        it('detects aria-hidden on focusable elements', function () {
            $html = '<html><body><button aria-hidden="true">Hidden but focusable</button></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'prohibited-attribute' && str_contains($i['message'], 'aria-hidden')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('detects aria-hidden on links', function () {
            $html = '<html><body><a href="/page" aria-hidden="true">Link</a></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'prohibited-attribute'
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('allows aria-hidden on non-focusable elements', function () {
            $html = '<html><body><div aria-hidden="true">Decorative</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'prohibited-attribute' && str_contains($i['message'], 'aria-hidden')
            );

            expect(count($issues))->toBe(0);
        });
    });

    describe('checkDeprecatedAttributes', function () {
        it('detects aria-grabbed usage', function () {
            $html = '<html><body><div aria-grabbed="true" draggable="true">Draggable</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'deprecated-attribute' && str_contains($i['message'], 'aria-grabbed')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('detects aria-dropeffect usage', function () {
            $html = '<html><body><div aria-dropeffect="copy">Drop zone</div></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'deprecated-attribute' && str_contains($i['message'], 'aria-dropeffect')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });
    });

    describe('checkRedundantRoles', function () {
        it('detects redundant role on button', function () {
            $html = '<html><body><button role="button">Click</button></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'redundant-role' && str_contains($i['message'], 'button')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('detects redundant role on nav', function () {
            $html = '<html><body><nav role="navigation">Links</nav></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'redundant-role' && str_contains($i['message'], 'navigation')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('detects redundant role on main', function () {
            $html = '<html><body><main role="main">Content</main></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'redundant-role' && str_contains($i['message'], 'main')
            );

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('does not flag different roles on elements', function () {
            $html = '<html><body><nav role="menubar">Menu</nav></body></html>';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'redundant-role');

            expect(count($issues))->toBe(0);
        });
    });

    describe('checkParentChildRelationships', function () {
        it('detects invalid children in list', function () {
            $html = '
                <html><body>
                    <ul role="list">
                        <div role="button">Invalid child</div>
                    </ul>
                </body></html>
            ';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'invalid-child-role');

            expect(count($issues))->toBeGreaterThan(0);
        });

        it('accepts valid children in tablist', function () {
            $html = '
                <html><body>
                    <div role="tablist">
                        <div role="tab">Tab 1</div>
                        <div role="tab">Tab 2</div>
                    </div>
                </body></html>
            ';

            $result = $this->service->validate($html);
            $issues = array_filter($result['issues'], fn ($i) => $i['type'] === 'invalid-child-role' && str_contains($i['message'], 'tablist')
            );

            expect(count($issues))->toBe(0);
        });
    });

    describe('summary', function () {
        it('generates correct issue counts', function () {
            $html = '
                <html><body>
                    <div role="checkbox">Missing aria-checked</div>
                    <button aria-expanded="invalid">Invalid value</button>
                    <button role="button">Redundant</button>
                </body></html>
            ';

            $result = $this->service->validate($html);

            expect($result['summary']['total'])->toBeGreaterThan(0);
            expect($result['summary']['byType'])->toBeArray();
            expect($result['summary']['bySeverity'])->toHaveKeys(['error', 'warning', 'info']);
        });
    });
});
