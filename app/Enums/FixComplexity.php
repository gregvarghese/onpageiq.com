<?php

namespace App\Enums;

enum FixComplexity: string
{
    case Quick = 'quick';
    case Easy = 'easy';
    case Medium = 'medium';
    case Complex = 'complex';
    case Architectural = 'architectural';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Quick => 'Quick Fix',
            self::Easy => 'Easy',
            self::Medium => 'Medium',
            self::Complex => 'Complex',
            self::Architectural => 'Architectural Change',
        };
    }

    /**
     * Get the description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Quick => 'Single attribute or text change',
            self::Easy => 'Single element modification',
            self::Medium => 'Multiple elements or component update',
            self::Complex => 'Multiple components or logic changes',
            self::Architectural => 'Requires design or structural changes',
        };
    }

    /**
     * Get typical effort estimate.
     */
    public function effortMinutes(): int
    {
        return match ($this) {
            self::Quick => 5,
            self::Easy => 15,
            self::Medium => 60,
            self::Complex => 240,
            self::Architectural => 480,
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Quick => 'green',
            self::Easy => 'blue',
            self::Medium => 'yellow',
            self::Complex => 'orange',
            self::Architectural => 'red',
        };
    }

    /**
     * Get priority weight (lower = should fix first).
     */
    public function priorityWeight(): int
    {
        return match ($this) {
            self::Quick => 1,
            self::Easy => 2,
            self::Medium => 3,
            self::Complex => 4,
            self::Architectural => 5,
        };
    }

    /**
     * Determine complexity from WCAG criterion and issue type.
     */
    public static function fromCriterion(string $criterionId, ?string $issueType = null): self
    {
        // Quick fixes - single attribute changes
        $quickFixes = [
            '1.1.1' => ['missing-alt'],           // Add alt text
            '1.3.1' => ['missing-label'],         // Add label
            '2.4.2' => ['empty-title'],           // Add page title
            '3.1.1' => ['missing-lang'],          // Add lang attribute
        ];

        // Easy fixes - single element changes
        $easyFixes = [
            '1.4.3',  // Contrast - change color
            '2.4.4',  // Link purpose - improve text
            '2.4.6',  // Headings - add/modify heading
            '3.3.2',  // Labels - add instructions
        ];

        // Medium fixes - component changes
        $mediumFixes = [
            '1.3.1',  // Info and relationships (general)
            '2.1.1',  // Keyboard accessibility
            '2.4.7',  // Focus visible
            '4.1.2',  // Name, role, value
        ];

        // Complex fixes - multiple component changes
        $complexFixes = [
            '1.2.1',  // Audio/video alternatives
            '1.2.2',  // Captions
            '2.4.1',  // Bypass blocks
            '2.4.3',  // Focus order
        ];

        // Check quick fixes first (criterion + issue type match)
        if ($issueType && isset($quickFixes[$criterionId])) {
            if (in_array($issueType, $quickFixes[$criterionId])) {
                return self::Quick;
            }
        }

        if (in_array($criterionId, $easyFixes)) {
            return self::Easy;
        }

        if (in_array($criterionId, $mediumFixes)) {
            return self::Medium;
        }

        if (in_array($criterionId, $complexFixes)) {
            return self::Complex;
        }

        // Default based on criterion prefix
        $principle = substr($criterionId, 0, 1);

        return match ($principle) {
            '1' => self::Easy,      // Perceivable - often attribute/content changes
            '2' => self::Medium,    // Operable - often keyboard/focus changes
            '3' => self::Easy,      // Understandable - often text/label changes
            '4' => self::Medium,    // Robust - often ARIA changes
            default => self::Medium,
        };
    }
}
