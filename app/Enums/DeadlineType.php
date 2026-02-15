<?php

namespace App\Enums;

enum DeadlineType: string
{
    case WcagCompliance = 'wcag_compliance';
    case Section508 = 'section_508';
    case EN301549 = 'en_301549';
    case InternalReview = 'internal_review';
    case ClientDelivery = 'client_delivery';
    case LegalRequirement = 'legal_requirement';
    case Custom = 'custom';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::WcagCompliance => 'WCAG Compliance',
            self::Section508 => 'Section 508 Compliance',
            self::EN301549 => 'EN 301 549 Compliance',
            self::InternalReview => 'Internal Review',
            self::ClientDelivery => 'Client Delivery',
            self::LegalRequirement => 'Legal Requirement',
            self::Custom => 'Custom Deadline',
        };
    }

    /**
     * Get the description for this deadline type.
     */
    public function description(): string
    {
        return match ($this) {
            self::WcagCompliance => 'Target date for achieving WCAG conformance',
            self::Section508 => 'Section 508 compliance deadline (US Federal)',
            self::EN301549 => 'EN 301 549 compliance deadline (EU)',
            self::InternalReview => 'Internal accessibility review milestone',
            self::ClientDelivery => 'Client accessibility deliverable due date',
            self::LegalRequirement => 'Legal or regulatory compliance deadline',
            self::Custom => 'Custom deadline',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::LegalRequirement, self::Section508, self::EN301549 => 'red',
            self::WcagCompliance => 'blue',
            self::ClientDelivery => 'purple',
            self::InternalReview => 'green',
            self::Custom => 'gray',
        };
    }

    /**
     * Get reminder days before deadline.
     */
    public function defaultReminderDays(): array
    {
        return match ($this) {
            self::LegalRequirement, self::Section508, self::EN301549 => [30, 14, 7, 3, 1],
            self::WcagCompliance, self::ClientDelivery => [14, 7, 3, 1],
            self::InternalReview => [7, 3, 1],
            self::Custom => [7, 1],
        };
    }
}
