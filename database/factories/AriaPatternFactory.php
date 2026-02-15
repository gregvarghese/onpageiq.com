<?php

namespace Database\Factories;

use App\Models\AriaPattern;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AriaPattern>
 */
class AriaPatternFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        $slug = str($name)->slug();

        return [
            'name' => ucfirst($name),
            'slug' => $slug,
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement([
                AriaPattern::CATEGORY_WIDGET,
                AriaPattern::CATEGORY_COMPOSITE,
                AriaPattern::CATEGORY_LANDMARK,
                AriaPattern::CATEGORY_STRUCTURE,
            ]),
            'required_roles' => ['button'],
            'optional_roles' => [],
            'required_attributes' => ['*' => []],
            'optional_attributes' => [],
            'keyboard_interactions' => [
                'Enter' => 'Activates the component',
                'Space' => 'Activates the component',
            ],
            'focus_management' => [],
            'html_selectors' => ['button', '[role="button"]'],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'button'],
                ['type' => 'selector', 'selector' => 'button'],
            ],
            'documentation_url' => "https://www.w3.org/WAI/ARIA/apg/patterns/{$slug}/",
            'wcag_criteria' => '4.1.2',
            'is_custom' => false,
            'organization_id' => null,
        ];
    }

    /**
     * Create a custom pattern for an organization.
     */
    public function custom(?Organization $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_custom' => true,
            'organization_id' => $organization?->id ?? Organization::factory(),
        ]);
    }

    /**
     * Create a button pattern.
     */
    public function button(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Button',
            'slug' => 'button',
            'description' => 'A button is a widget that enables users to trigger an action or event, such as submitting a form, opening a dialog, canceling an action, or performing a delete operation.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['button'],
            'required_attributes' => ['*' => []],
            'keyboard_interactions' => [
                'Enter' => 'Activates the button',
                'Space' => 'Activates the button',
            ],
            'html_selectors' => ['button', 'input[type="button"]', 'input[type="submit"]', '[role="button"]'],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'button'],
                ['type' => 'selector', 'selector' => 'button'],
                ['type' => 'selector', 'selector' => 'input[type="button"]'],
                ['type' => 'selector', 'selector' => 'input[type="submit"]'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/button/',
            'wcag_criteria' => '4.1.2',
        ]);
    }

    /**
     * Create a dialog pattern.
     */
    public function dialog(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Dialog (Modal)',
            'slug' => 'dialog-modal',
            'description' => 'A dialog is a window overlaid on either the primary window or another dialog window. Windows under a modal dialog are inert.',
            'category' => AriaPattern::CATEGORY_WIDGET,
            'required_roles' => ['dialog', 'alertdialog'],
            'required_attributes' => [
                'dialog' => ['aria-modal', 'aria-label', 'aria-labelledby'],
                'alertdialog' => ['aria-modal', 'aria-label', 'aria-labelledby'],
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
            'html_selectors' => ['[role="dialog"]', '[role="alertdialog"]', 'dialog'],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'dialog'],
                ['type' => 'role', 'role' => 'alertdialog'],
                ['type' => 'selector', 'selector' => 'dialog'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/',
            'wcag_criteria' => '2.1.2,2.4.3,4.1.2',
        ]);
    }

    /**
     * Create a tabs pattern.
     */
    public function tabs(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Tabs',
            'slug' => 'tabs',
            'description' => 'Tabs are a set of layered sections of content, known as tab panels, that display one panel of content at a time.',
            'category' => AriaPattern::CATEGORY_COMPOSITE,
            'required_roles' => ['tablist', 'tab', 'tabpanel'],
            'required_attributes' => [
                'tablist' => ['aria-label', 'aria-labelledby'],
                'tab' => ['aria-selected', 'aria-controls'],
                'tabpanel' => ['aria-labelledby'],
            ],
            'keyboard_interactions' => [
                'Tab' => 'When focus moves into the tab list, places focus on the active tab element',
                'ArrowLeft' => 'Moves focus to the previous tab',
                'ArrowRight' => 'Moves focus to the next tab',
                'Home' => 'Moves focus to the first tab',
                'End' => 'Moves focus to the last tab',
            ],
            'focus_management' => [
                'roving_tabindex' => true,
            ],
            'html_selectors' => ['[role="tablist"]'],
            'detection_rules' => [
                ['type' => 'role', 'role' => 'tablist'],
            ],
            'documentation_url' => 'https://www.w3.org/WAI/ARIA/apg/patterns/tabs/',
            'wcag_criteria' => '1.3.1,4.1.2',
        ]);
    }
}
