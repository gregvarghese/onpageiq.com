<?php

namespace Database\Seeders;

use App\Models\AriaPattern;
use Illuminate\Database\Seeder;

class AriaPatternSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patterns = $this->getPatterns();

        foreach ($patterns as $pattern) {
            AriaPattern::updateOrCreate(
                ['slug' => $pattern['slug']],
                $pattern
            );
        }
    }

    /**
     * Get all WAI-ARIA APG patterns.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getPatterns(): array
    {
        return [
            // Widget Patterns
            $this->accordion(),
            $this->alert(),
            $this->alertDialog(),
            $this->button(),
            $this->checkbox(),
            $this->combobox(),
            $this->dialogModal(),
            $this->disclosure(),
            $this->link(),
            $this->listbox(),
            $this->menu(),
            $this->menuButton(),
            $this->meter(),
            $this->radioGroup(),
            $this->slider(),
            $this->sliderMultiThumb(),
            $this->spinButton(),
            $this->switchPattern(),
            $this->tooltip(),

            // Composite Patterns
            $this->breadcrumb(),
            $this->carousel(),
            $this->feed(),
            $this->grid(),
            $this->tabs(),
            $this->toolbar(),
            $this->treeView(),
            $this->table(),
        ];
    }

    protected function accordion(): array
    {
        return [
            'name' => 'Accordion',
            'slug' => 'accordion',
            'description' => 'An accordion is a vertically stacked set of interactive headings that each contain a title, content snippet, or thumbnail representing a section of content.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['button'],
            'optional_roles' => ['region'],
            'required_attributes' => [
                'button' => ['aria-expanded', 'aria-controls'],
            ],
            'optional_attributes' => [
                'region' => ['aria-labelledby'],
            ],
            'keyboard_interactions' => [
                'Enter' => 'Expands/collapses the accordion panel',
                'Space' => 'Expands/collapses the accordion panel',
                'Tab' => 'Moves focus to the next focusable element',
                'Shift+Tab' => 'Moves focus to the previous focusable element',
                'ArrowDown' => 'Moves focus to the next accordion header (optional)',
                'ArrowUp' => 'Moves focus to the previous accordion header (optional)',
                'Home' => 'Moves focus to the first accordion header (optional)',
                'End' => 'Moves focus to the last accordion header (optional)',
            ],
            'focus_management' => [],
            'html_selectors' => [
                '[data-accordion]',
                '.accordion',
                '[role="button"][aria-expanded][aria-controls]',
            ],
            'detection_rules' => [
                [
                    'type' => 'combined',
                    'conditions' => [
                        ['type' => 'attribute', 'attribute' => 'aria-expanded'],
                        ['type' => 'attribute', 'attribute' => 'aria-controls'],
                    ],
                ],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/accordion/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function alert(): array
    {
        return [
            'name' => 'Alert',
            'slug' => 'alert',
            'description' => 'An alert is an element that displays a brief, important message in a way that attracts the user\'s attention without interrupting the user\'s task.',
            'category' => AriaPattern::CATEGORY_LIVE_REGION,
            'required_roles' => ['alert'],
            'optional_roles' => [],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [],
            'keyboard_interactions' => [],
            'focus_management' => [
                'auto_focus' => false,
            ],
            'html_selectors' => ['[role="alert"]'],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'alert'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/alert/',
            'wcag_criteria' => '4.1.3',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function alertDialog(): array
    {
        return [
            'name' => 'Alert Dialog',
            'slug' => 'alertdialog',
            'description' => 'An alert dialog is a modal dialog that interrupts the user\'s workflow to communicate an important message and acquire a response.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['alertdialog'],
            'optional_roles' => [],
            'required_attributes' => [
                'alertdialog' => ['aria-modal', 'aria-labelledby', 'aria-describedby'],
            ],
            'optional_attributes' => [],
            'keyboard_interactions' => [
                'Tab' => 'Moves focus to next focusable element inside the dialog',
                'Shift+Tab' => 'Moves focus to previous focusable element inside the dialog',
                'Escape' => 'Closes the dialog (if cancellation is allowed)',
            ],
            'focus_management' => [
                'focus_on_open' => 'The element that requires user attention',
                'focus_trap' => true,
                'focus_on_close' => 'Element that triggered the dialog',
            ],
            'html_selectors' => ['[role="alertdialog"]'],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'alertdialog'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/alertdialog/',
            'wcag_criteria' => '2.1.2,2.4.3,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function button(): array
    {
        return [
            'name' => 'Button',
            'slug' => 'button',
            'description' => 'A button is a widget that enables users to trigger an action or event, such as submitting a form, opening a dialog, canceling an action, or performing a delete operation.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['button'],
            'optional_roles' => [],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [
                'button' => ['aria-pressed', 'aria-expanded', 'aria-haspopup'],
            ],
            'keyboard_interactions' => [
                'Enter' => 'Activates the button',
                'Space' => 'Activates the button',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'button',
                'input[type="button"]',
                'input[type="submit"]',
                'input[type="reset"]',
                '[role="button"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'button'],
                ['type' => 'selector', 'selector' => 'button'],
                ['type' => 'selector', 'selector' => 'input[type="button"]'],
                ['type' => 'selector', 'selector' => 'input[type="submit"]'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/button/',
            'wcag_criteria' => '4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function checkbox(): array
    {
        return [
            'name' => 'Checkbox',
            'slug' => 'checkbox',
            'description' => 'A checkbox is an input control that allows the user to select one or more options from a set.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['checkbox'],
            'optional_roles' => ['group'],
            'required_attributes' => [
                'checkbox' => ['aria-checked'],
            ],
            'optional_attributes' => [
                'checkbox' => ['aria-labelledby', 'aria-describedby'],
            ],
            'keyboard_interactions' => [
                'Space' => 'Toggles checkbox between checked and unchecked states',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'input[type="checkbox"]',
                '[role="checkbox"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'checkbox'],
                ['type' => 'selector', 'selector' => 'input[type="checkbox"]'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/checkbox/',
            'wcag_criteria' => '4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function combobox(): array
    {
        return [
            'name' => 'Combobox',
            'slug' => 'combobox',
            'description' => 'A combobox is an input widget with an associated popup that enables users to select a value from a collection of possible values.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['combobox'],
            'optional_roles' => ['listbox', 'tree', 'grid', 'dialog'],
            'required_attributes' => [
                'combobox' => ['aria-expanded', 'aria-controls'],
            ],
            'optional_attributes' => [
                'combobox' => ['aria-autocomplete', 'aria-activedescendant', 'aria-haspopup'],
            ],
            'keyboard_interactions' => [
                'ArrowDown' => 'Opens the listbox and moves focus to first option or next option',
                'ArrowUp' => 'Opens the listbox and moves focus to last option or previous option',
                'Enter' => 'Accepts the focused option, closes the listbox',
                'Escape' => 'Closes the listbox',
                'Home' => 'Moves focus to the first option',
                'End' => 'Moves focus to the last option',
            ],
            'focus_management' => [
                'aria_activedescendant' => true,
            ],
            'html_selectors' => [
                'select',
                '[role="combobox"]',
                'input[aria-expanded][aria-controls]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'combobox'],
                ['type' => 'selector', 'selector' => 'select'],
                [
                    'type' => 'combined',
                    'conditions' => [
                        ['type' => 'selector', 'selector' => 'input'],
                        ['type' => 'attribute', 'attribute' => 'aria-expanded'],
                        ['type' => 'attribute', 'attribute' => 'aria-controls'],
                    ],
                ],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/combobox/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function dialogModal(): array
    {
        return [
            'name' => 'Dialog (Modal)',
            'slug' => 'dialog-modal',
            'description' => 'A dialog is a window overlaid on either the primary window or another dialog window. Windows under a modal dialog are inert.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['dialog'],
            'optional_roles' => [],
            'required_attributes' => [
                'dialog' => ['aria-modal'],
            ],
            'optional_attributes' => [
                'dialog' => ['aria-label', 'aria-labelledby', 'aria-describedby'],
            ],
            'keyboard_interactions' => [
                'Tab' => 'Moves focus to next focusable element inside the dialog',
                'Shift+Tab' => 'Moves focus to previous focusable element inside the dialog',
                'Escape' => 'Closes the dialog',
            ],
            'focus_management' => [
                'focus_on_open' => 'First focusable element or dialog itself',
                'focus_trap' => true,
                'focus_on_close' => 'Element that triggered the dialog',
            ],
            'html_selectors' => [
                'dialog',
                '[role="dialog"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'dialog'],
                ['type' => 'selector', 'selector' => 'dialog'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/',
            'wcag_criteria' => '2.1.2,2.4.3,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function disclosure(): array
    {
        return [
            'name' => 'Disclosure',
            'slug' => 'disclosure',
            'description' => 'A disclosure is a button that controls visibility of a section of content. When hidden, it is often styled as a typical push button with an arrow or triangle pointing right; when visible, the arrow or triangle points down.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['button'],
            'optional_roles' => [],
            'required_attributes' => [
                'button' => ['aria-expanded', 'aria-controls'],
            ],
            'optional_attributes' => [],
            'keyboard_interactions' => [
                'Enter' => 'Activates the disclosure button',
                'Space' => 'Activates the disclosure button',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'details > summary',
                'button[aria-expanded][aria-controls]',
            ],
            'detection_rules' => [
                ['type' => 'selector', 'selector' => 'details'],
                [
                    'type' => 'combined',
                    'conditions' => [
                        ['type' => 'role', 'role' => 'button'],
                        ['type' => 'attribute', 'attribute' => 'aria-expanded'],
                        ['type' => 'attribute', 'attribute' => 'aria-controls'],
                    ],
                ],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/',
            'wcag_criteria' => '4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function link(): array
    {
        return [
            'name' => 'Link',
            'slug' => 'link',
            'description' => 'A link is a widget that provides an interactive reference to a resource.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['link'],
            'optional_roles' => [],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [],
            'keyboard_interactions' => [
                'Enter' => 'Activates the link',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'a[href]',
                '[role="link"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'link'],
                ['type' => 'selector', 'selector' => 'a[href]'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/link/',
            'wcag_criteria' => '2.4.4,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function listbox(): array
    {
        return [
            'name' => 'Listbox',
            'slug' => 'listbox',
            'description' => 'A listbox widget presents a list of options and allows a user to select one or more of them.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['listbox', 'option'],
            'optional_roles' => ['group'],
            'required_attributes' => [
                'listbox' => [],
                'option' => ['aria-selected'],
            ],
            'optional_attributes' => [
                'listbox' => ['aria-label', 'aria-labelledby', 'aria-multiselectable', 'aria-activedescendant'],
            ],
            'keyboard_interactions' => [
                'ArrowDown' => 'Moves focus to the next option',
                'ArrowUp' => 'Moves focus to the previous option',
                'Home' => 'Moves focus to the first option',
                'End' => 'Moves focus to the last option',
                'Space' => 'Toggles selection (in multi-select mode)',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
                'aria_activedescendant' => true,
            ],
            'html_selectors' => [
                '[role="listbox"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'listbox'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/listbox/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function menu(): array
    {
        return [
            'name' => 'Menu',
            'slug' => 'menu',
            'description' => 'A menu is a widget that offers a list of choices to the user, such as a set of actions or functions.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['menu', 'menuitem'],
            'optional_roles' => ['menubar', 'menuitemcheckbox', 'menuitemradio'],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [
                'menu' => ['aria-label', 'aria-labelledby'],
                'menuitem' => ['aria-haspopup', 'aria-expanded'],
            ],
            'keyboard_interactions' => [
                'Enter' => 'Activates the menu item',
                'Space' => 'Activates the menu item',
                'ArrowDown' => 'Moves focus to the next menu item',
                'ArrowUp' => 'Moves focus to the previous menu item',
                'ArrowRight' => 'Opens submenu (if present)',
                'ArrowLeft' => 'Closes submenu, moves focus to parent',
                'Home' => 'Moves focus to the first menu item',
                'End' => 'Moves focus to the last menu item',
                'Escape' => 'Closes the menu',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => [
                '[role="menu"]',
                '[role="menubar"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'menu'],
                ['type' => 'role', 'role' => 'menubar'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/menu/',
            'wcag_criteria' => '1.3.1,2.1.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function menuButton(): array
    {
        return [
            'name' => 'Menu Button',
            'slug' => 'menu-button',
            'description' => 'A menu button is a button that opens a menu. It is often styled as a typical push button with a downward pointing arrow or triangle.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['button'],
            'optional_roles' => ['menu'],
            'required_attributes' => [
                'button' => ['aria-haspopup', 'aria-expanded'],
            ],
            'optional_attributes' => [
                'button' => ['aria-controls'],
            ],
            'keyboard_interactions' => [
                'Enter' => 'Opens the menu and moves focus to the first menu item',
                'Space' => 'Opens the menu and moves focus to the first menu item',
                'ArrowDown' => 'Opens the menu and moves focus to the first menu item',
                'ArrowUp' => 'Opens the menu and moves focus to the last menu item',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'button[aria-haspopup="menu"]',
                'button[aria-haspopup="true"]',
            ],
            'detection_rules' => [
                [
                    'type' => 'combined',
                    'conditions' => [
                        ['type' => 'role', 'role' => 'button'],
                        ['type' => 'attribute', 'attribute' => 'aria-haspopup'],
                    ],
                ],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/menu-button/',
            'wcag_criteria' => '4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function meter(): array
    {
        return [
            'name' => 'Meter',
            'slug' => 'meter',
            'description' => 'A meter is a graphical display of a numeric value that varies within a defined range.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['meter'],
            'optional_roles' => [],
            'required_attributes' => [
                'meter' => ['aria-valuenow', 'aria-valuemin', 'aria-valuemax'],
            ],
            'optional_attributes' => [
                'meter' => ['aria-label', 'aria-labelledby', 'aria-valuetext'],
            ],
            'keyboard_interactions' => [],
            'focus_management' => [],
            'html_selectors' => [
                'meter',
                '[role="meter"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'meter'],
                ['type' => 'selector', 'selector' => 'meter'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/meter/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function radioGroup(): array
    {
        return [
            'name' => 'Radio Group',
            'slug' => 'radio-group',
            'description' => 'A radio group is a set of checkable buttons, known as radio buttons, where no more than one of the buttons can be checked at a time.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['radiogroup', 'radio'],
            'optional_roles' => [],
            'required_attributes' => [
                'radio' => ['aria-checked'],
            ],
            'optional_attributes' => [
                'radiogroup' => ['aria-label', 'aria-labelledby'],
            ],
            'keyboard_interactions' => [
                'Tab' => 'Moves focus into and out of the radio group',
                'Space' => 'Checks the focused radio button',
                'ArrowRight' => 'Moves focus to and checks the next radio button',
                'ArrowDown' => 'Moves focus to and checks the next radio button',
                'ArrowLeft' => 'Moves focus to and checks the previous radio button',
                'ArrowUp' => 'Moves focus to and checks the previous radio button',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => [
                '[role="radiogroup"]',
                'fieldset:has(input[type="radio"])',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'radiogroup'],
                ['type' => 'role', 'role' => 'radio'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/radio/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function slider(): array
    {
        return [
            'name' => 'Slider',
            'slug' => 'slider',
            'description' => 'A slider is an input where the user selects a value from within a given range.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['slider'],
            'optional_roles' => [],
            'required_attributes' => [
                'slider' => ['aria-valuenow', 'aria-valuemin', 'aria-valuemax'],
            ],
            'optional_attributes' => [
                'slider' => ['aria-label', 'aria-labelledby', 'aria-valuetext', 'aria-orientation'],
            ],
            'keyboard_interactions' => [
                'ArrowRight' => 'Increases the value',
                'ArrowUp' => 'Increases the value',
                'ArrowLeft' => 'Decreases the value',
                'ArrowDown' => 'Decreases the value',
                'Home' => 'Sets the slider to its minimum value',
                'End' => 'Sets the slider to its maximum value',
                'PageUp' => 'Increases the value by a larger amount',
                'PageDown' => 'Decreases the value by a larger amount',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'input[type="range"]',
                '[role="slider"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'slider'],
                ['type' => 'selector', 'selector' => 'input[type="range"]'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/slider/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function sliderMultiThumb(): array
    {
        return [
            'name' => 'Slider (Multi-Thumb)',
            'slug' => 'slider-multithumb',
            'description' => 'A multi-thumb slider is a slider with two or more thumbs that each set a value in a group of related values.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['slider'],
            'optional_roles' => ['group'],
            'required_attributes' => [
                'slider' => ['aria-valuenow', 'aria-valuemin', 'aria-valuemax'],
            ],
            'optional_attributes' => [
                'slider' => ['aria-label', 'aria-labelledby', 'aria-valuetext'],
                'group' => ['aria-label', 'aria-labelledby'],
            ],
            'keyboard_interactions' => [
                'ArrowRight' => 'Increases the value of the focused thumb',
                'ArrowUp' => 'Increases the value of the focused thumb',
                'ArrowLeft' => 'Decreases the value of the focused thumb',
                'ArrowDown' => 'Decreases the value of the focused thumb',
                'Home' => 'Sets the thumb to its minimum value',
                'End' => 'Sets the thumb to its maximum value',
                'PageUp' => 'Increases by larger amount',
                'PageDown' => 'Decreases by larger amount',
            ],
            'focus_management' => [],
            'html_selectors' => [
                '[data-multithumb-slider]',
            ],
            'detection_rules' => [
                [
                    'type' => 'combined',
                    'conditions' => [
                        ['type' => 'role', 'role' => 'slider'],
                        ['type' => 'attribute', 'attribute' => 'data-multithumb'],
                    ],
                ],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/slider-multithumb/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function spinButton(): array
    {
        return [
            'name' => 'Spinbutton',
            'slug' => 'spinbutton',
            'description' => 'A spinbutton is an input widget that restricts its value to a set or range of discrete values.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['spinbutton'],
            'optional_roles' => [],
            'required_attributes' => [
                'spinbutton' => ['aria-valuenow', 'aria-valuemin', 'aria-valuemax'],
            ],
            'optional_attributes' => [
                'spinbutton' => ['aria-label', 'aria-labelledby', 'aria-valuetext'],
            ],
            'keyboard_interactions' => [
                'ArrowUp' => 'Increases the value',
                'ArrowDown' => 'Decreases the value',
                'Home' => 'Sets the value to its minimum',
                'End' => 'Sets the value to its maximum',
                'PageUp' => 'Increases by larger amount',
                'PageDown' => 'Decreases by larger amount',
            ],
            'focus_management' => [],
            'html_selectors' => [
                'input[type="number"]',
                '[role="spinbutton"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'spinbutton'],
                ['type' => 'selector', 'selector' => 'input[type="number"]'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/spinbutton/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function switchPattern(): array
    {
        return [
            'name' => 'Switch',
            'slug' => 'switch',
            'description' => 'A switch is an input widget that allows users to choose one of two values: on or off.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['switch'],
            'optional_roles' => [],
            'required_attributes' => [
                'switch' => ['aria-checked'],
            ],
            'optional_attributes' => [
                'switch' => ['aria-label', 'aria-labelledby'],
            ],
            'keyboard_interactions' => [
                'Space' => 'Toggles the switch between on and off',
                'Enter' => 'Toggles the switch between on and off',
            ],
            'focus_management' => [],
            'html_selectors' => [
                '[role="switch"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'switch'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/switch/',
            'wcag_criteria' => '4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function tooltip(): array
    {
        return [
            'name' => 'Tooltip',
            'slug' => 'tooltip',
            'description' => 'A tooltip is a popup that displays information related to an element when the element receives keyboard focus or the mouse hovers over it.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['tooltip'],
            'optional_roles' => [],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [],
            'keyboard_interactions' => [
                'Escape' => 'Dismisses the tooltip',
            ],
            'focus_management' => [
                'auto_focus' => false,
            ],
            'html_selectors' => [
                '[role="tooltip"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'tooltip'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/tooltip/',
            'wcag_criteria' => '1.4.13',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function breadcrumb(): array
    {
        return [
            'name' => 'Breadcrumb',
            'slug' => 'breadcrumb',
            'description' => 'A breadcrumb trail consists of a list of links to the parent pages of the current page in hierarchical order.',
            'category' => AriaPattern::CATEGORY_STRUCTURE,
            'required_roles' => ['navigation'],
            'optional_roles' => ['list', 'listitem', 'link'],
            'required_attributes' => [
                'navigation' => ['aria-label'],
            ],
            'optional_attributes' => [
                'link' => ['aria-current'],
            ],
            'keyboard_interactions' => [],
            'focus_management' => [],
            'html_selectors' => [
                'nav[aria-label*="breadcrumb" i]',
                'nav[aria-label*="trail" i]',
                '[role="navigation"][aria-label*="breadcrumb" i]',
            ],
            'detection_rules' => [
                [
                    'type' => 'combined',
                    'conditions' => [
                        ['type' => 'role', 'role' => 'navigation'],
                        ['type' => 'attribute', 'attribute' => 'aria-label'],
                    ],
                ],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/',
            'wcag_criteria' => '2.4.8',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function carousel(): array
    {
        return [
            'name' => 'Carousel',
            'slug' => 'carousel',
            'description' => 'A carousel presents a set of items, referred to as slides, by sequentially displaying a subset of one or more slides.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['group', 'region'],
            'optional_roles' => ['tablist', 'tab', 'tabpanel'],
            'required_attributes' => [
                'group' => ['aria-roledescription', 'aria-label'],
            ],
            'optional_attributes' => [
                'group' => ['aria-live'],
            ],
            'keyboard_interactions' => [
                'Tab' => 'Moves focus through interactive elements',
                'ArrowRight' => 'Displays next slide (optional)',
                'ArrowLeft' => 'Displays previous slide (optional)',
            ],
            'focus_management' => [],
            'html_selectors' => [
                '[aria-roledescription="carousel"]',
                '.carousel',
                '[data-carousel]',
            ],
            'detection_rules' => [
                ['type' => 'attribute', 'attribute' => 'aria-roledescription', 'value' => 'carousel'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/carousel/',
            'wcag_criteria' => '2.2.2,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function feed(): array
    {
        return [
            'name' => 'Feed',
            'slug' => 'feed',
            'description' => 'A feed is a section of a page that automatically loads new sections of content as the user scrolls.',
            'category' => AriaPattern::CATEGORY_STRUCTURE,
            'required_roles' => ['feed', 'article'],
            'optional_roles' => [],
            'required_attributes' => [
                'article' => ['aria-posinset', 'aria-setsize'],
            ],
            'optional_attributes' => [
                'feed' => ['aria-busy', 'aria-label', 'aria-labelledby'],
                'article' => ['aria-label', 'aria-labelledby', 'aria-describedby'],
            ],
            'keyboard_interactions' => [
                'PageDown' => 'Move focus to next article',
                'PageUp' => 'Move focus to previous article',
            ],
            'focus_management' => [],
            'html_selectors' => [
                '[role="feed"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'feed'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/feed/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function grid(): array
    {
        return [
            'name' => 'Grid',
            'slug' => 'grid',
            'description' => 'A grid widget is a container that enables users to navigate the information or interactive elements it contains using directional navigation keys.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['grid', 'row', 'gridcell'],
            'optional_roles' => ['rowheader', 'columnheader', 'rowgroup'],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [
                'grid' => ['aria-label', 'aria-labelledby', 'aria-rowcount', 'aria-colcount'],
                'row' => ['aria-rowindex'],
                'gridcell' => ['aria-colindex', 'aria-selected'],
            ],
            'keyboard_interactions' => [
                'ArrowRight' => 'Moves focus one cell to the right',
                'ArrowLeft' => 'Moves focus one cell to the left',
                'ArrowDown' => 'Moves focus one cell down',
                'ArrowUp' => 'Moves focus one cell up',
                'Home' => 'Moves focus to the first cell in the row',
                'End' => 'Moves focus to the last cell in the row',
                'Ctrl+Home' => 'Moves focus to the first cell in the grid',
                'Ctrl+End' => 'Moves focus to the last cell in the grid',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => [
                '[role="grid"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'grid'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/grid/',
            'wcag_criteria' => '1.3.1,2.1.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function tabs(): array
    {
        return [
            'name' => 'Tabs',
            'slug' => 'tabs',
            'description' => 'Tabs are a set of layered sections of content, known as tab panels, that display one panel of content at a time.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['tablist', 'tab', 'tabpanel'],
            'optional_roles' => [],
            'required_attributes' => [
                'tab' => ['aria-selected', 'aria-controls'],
                'tabpanel' => ['aria-labelledby'],
            ],
            'optional_attributes' => [
                'tablist' => ['aria-label', 'aria-labelledby', 'aria-orientation'],
            ],
            'keyboard_interactions' => [
                'Tab' => 'Moves focus into the tab list, places focus on the active tab element',
                'ArrowRight' => 'Moves focus to the next tab',
                'ArrowLeft' => 'Moves focus to the previous tab',
                'Home' => 'Moves focus to the first tab',
                'End' => 'Moves focus to the last tab',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => [
                '[role="tablist"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'tablist'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/tabs/',
            'wcag_criteria' => '1.3.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function toolbar(): array
    {
        return [
            'name' => 'Toolbar',
            'slug' => 'toolbar',
            'description' => 'A toolbar is a container for grouping a set of controls, such as buttons, menubuttons, or checkboxes.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['toolbar'],
            'optional_roles' => ['group', 'button', 'checkbox', 'menubutton'],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [
                'toolbar' => ['aria-label', 'aria-labelledby', 'aria-orientation', 'aria-controls'],
            ],
            'keyboard_interactions' => [
                'Tab' => 'Moves focus into and out of the toolbar',
                'ArrowRight' => 'Moves focus to the next control',
                'ArrowLeft' => 'Moves focus to the previous control',
                'Home' => 'Moves focus to the first control',
                'End' => 'Moves focus to the last control',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => [
                '[role="toolbar"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'toolbar'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/toolbar/',
            'wcag_criteria' => '2.1.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function treeView(): array
    {
        return [
            'name' => 'Tree View',
            'slug' => 'treeview',
            'description' => 'A tree view widget presents a hierarchical list. Any item in the hierarchy may have child items, and items that have children may be expanded or collapsed to show or hide the children.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['tree', 'treeitem'],
            'optional_roles' => ['group'],
            'required_attributes' => [
                'treeitem' => ['aria-expanded'],
            ],
            'optional_attributes' => [
                'tree' => ['aria-label', 'aria-labelledby', 'aria-multiselectable'],
                'treeitem' => ['aria-selected', 'aria-level', 'aria-setsize', 'aria-posinset'],
            ],
            'keyboard_interactions' => [
                'Enter' => 'Performs default action',
                'Space' => 'Toggles selection (multi-select mode)',
                'ArrowDown' => 'Moves focus to next visible treeitem',
                'ArrowUp' => 'Moves focus to previous visible treeitem',
                'ArrowRight' => 'Expands closed node or moves to first child',
                'ArrowLeft' => 'Collapses open node or moves to parent',
                'Home' => 'Moves focus to the first treeitem',
                'End' => 'Moves focus to the last visible treeitem',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => [
                '[role="tree"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'tree'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/treeview/',
            'wcag_criteria' => '1.3.1,2.1.1,4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    protected function table(): array
    {
        return [
            'name' => 'Table',
            'slug' => 'table',
            'description' => 'A table is a widget that presents data in a two-dimensional format, organized into rows and columns.',
            'category' => AriaPattern::CATEGORY_STRUCTURE,
            'required_roles' => ['table', 'row', 'cell'],
            'optional_roles' => ['rowheader', 'columnheader', 'rowgroup', 'caption'],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [
                'table' => ['aria-label', 'aria-labelledby', 'aria-describedby', 'aria-rowcount', 'aria-colcount'],
                'row' => ['aria-rowindex'],
                'cell' => ['aria-colindex'],
            ],
            'keyboard_interactions' => [],
            'focus_management' => [],
            'html_selectors' => [
                'table',
                '[role="table"]',
            ],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'table'],
                ['type' => 'selector', 'selector' => 'table'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/table/',
            'wcag_criteria' => '1.3.1,1.3.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }
}
