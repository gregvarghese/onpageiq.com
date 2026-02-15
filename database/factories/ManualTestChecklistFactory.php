<?php

namespace Database\Factories;

use App\Enums\ManualTestStatus;
use App\Enums\WcagLevel;
use App\Models\AccessibilityAudit;
use App\Models\ManualTestChecklist;
use App\Models\User;
use App\Models\VpatEvaluation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ManualTestChecklist>
 */
class ManualTestChecklistFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'accessibility_audit_id' => AccessibilityAudit::factory(),
            'vpat_evaluation_id' => null,
            'tester_user_id' => User::factory(),
            'criterion_id' => fake()->randomElement(['1.1.1', '1.4.3', '2.1.1', '2.4.7', '4.1.2']),
            'wcag_level' => fake()->randomElement([WcagLevel::A, WcagLevel::AA]),
            'test_name' => fake()->sentence(4),
            'test_description' => fake()->paragraph(),
            'test_steps' => [
                'Navigate to the page using Tab key',
                'Verify all interactive elements receive focus',
                'Check that focus indicator is visible',
                'Verify focus order is logical',
            ],
            'expected_results' => [
                'All interactive elements can receive keyboard focus',
                'Focus indicator is clearly visible',
                'Focus order follows reading order',
            ],
            'status' => ManualTestStatus::Pending,
            'actual_results' => null,
            'tester_notes' => null,
            'browser' => null,
            'browser_version' => null,
            'assistive_technology' => null,
            'operating_system' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    /**
     * For a specific VPAT evaluation.
     */
    public function forVpat(VpatEvaluation $vpat): static
    {
        return $this->state(fn (array $attributes) => [
            'vpat_evaluation_id' => $vpat->id,
            'accessibility_audit_id' => $vpat->accessibility_audit_id,
        ]);
    }

    /**
     * In progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ManualTestStatus::InProgress,
            'started_at' => now()->subMinutes(10),
            'browser' => 'Chrome',
            'browser_version' => '120.0',
            'operating_system' => 'Windows 11',
        ]);
    }

    /**
     * Passed.
     */
    public function passed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ManualTestStatus::Passed,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now(),
            'actual_results' => 'All tests passed as expected.',
            'browser' => 'Chrome',
            'browser_version' => '120.0',
            'operating_system' => 'Windows 11',
        ]);
    }

    /**
     * Failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ManualTestStatus::Failed,
            'started_at' => now()->subMinutes(15),
            'completed_at' => now(),
            'actual_results' => 'Focus indicator not visible on some buttons.',
            'tester_notes' => 'CSS outline:none applied to buttons without alternative focus style.',
            'browser' => 'Chrome',
            'browser_version' => '120.0',
            'operating_system' => 'Windows 11',
        ]);
    }

    /**
     * With screen reader testing.
     */
    public function withScreenReader(): static
    {
        return $this->state(fn (array $attributes) => [
            'browser' => 'Firefox',
            'browser_version' => '121.0',
            'assistive_technology' => 'NVDA 2023.3',
            'operating_system' => 'Windows 11',
        ]);
    }

    /**
     * Keyboard navigation test.
     */
    public function keyboardTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'criterion_id' => '2.1.1',
            'wcag_level' => WcagLevel::A,
            'test_name' => 'Keyboard Navigation Test',
            'test_description' => 'Verify all functionality is operable through keyboard interface.',
            'test_steps' => [
                'Start at the beginning of the page',
                'Press Tab to move through all interactive elements',
                'Verify each element can be activated using Enter or Space',
                'Press Shift+Tab to move backwards',
                'Verify there are no keyboard traps',
            ],
            'expected_results' => [
                'All interactive elements are reachable via Tab',
                'All elements can be activated with Enter or Space',
                'Backwards navigation works correctly',
                'No keyboard traps exist',
            ],
        ]);
    }

    /**
     * Screen reader test.
     */
    public function screenReaderTest(): static
    {
        return $this->state(fn (array $attributes) => [
            'criterion_id' => '4.1.2',
            'wcag_level' => WcagLevel::A,
            'test_name' => 'Screen Reader Compatibility Test',
            'test_description' => 'Verify all UI components have proper name, role, and value announced.',
            'test_steps' => [
                'Enable screen reader (NVDA, JAWS, or VoiceOver)',
                'Navigate to each interactive element',
                'Listen for name announcement',
                'Listen for role announcement',
                'Verify state changes are announced',
            ],
            'expected_results' => [
                'All elements have accessible names announced',
                'Roles are correctly announced (button, link, checkbox, etc.)',
                'State changes are announced (expanded/collapsed, checked/unchecked)',
            ],
            'assistive_technology' => 'NVDA 2023.3',
        ]);
    }
}
