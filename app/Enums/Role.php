<?php

namespace App\Enums;

/**
 * Roles for OnPageIQ application.
 */
enum Role: string
{
    // System-level roles (managed in Filament)
    case SuperAdmin = 'Super Admin';

    // Organization-level roles
    case Owner = 'Owner';
    case Admin = 'Admin';
    case Manager = 'Manager';
    case Member = 'Member';
    case Viewer = 'Viewer';

    /**
     * Get all role values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get organization-level roles only.
     *
     * @return array<string>
     */
    public static function organizationRoles(): array
    {
        return [
            self::Owner->value,
            self::Admin->value,
            self::Manager->value,
            self::Member->value,
            self::Viewer->value,
        ];
    }
}
