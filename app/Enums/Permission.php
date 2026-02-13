<?php

namespace App\Enums;

/**
 * Fine-grained permissions for OnPageIQ application.
 */
enum Permission: string
{
    // Project permissions
    case CreateProject = 'create_project';
    case EditProject = 'edit_project';
    case DeleteProject = 'delete_project';
    case ViewProject = 'view_project';

    // Scan permissions
    case RunScan = 'run_scan';
    case ViewReports = 'view_reports';
    case ExportReports = 'export_reports';

    // Team management
    case ManageTeamMembers = 'manage_team_members';
    case ManageDepartmentBudgets = 'manage_department_budgets';
    case InviteTeamMembers = 'invite_team_members';

    // Billing permissions
    case ViewBilling = 'view_billing';
    case ManageBilling = 'manage_billing';
    case PurchaseCredits = 'purchase_credits';

    // API permissions
    case ManageApiKeys = 'manage_api_keys';
    case ViewWebhooks = 'view_webhooks';
    case ManageWebhooks = 'manage_webhooks';

    // Admin permissions
    case AdminAll = 'admin_all';
    case ImpersonateUsers = 'impersonate_users';
    case ManageOrganizations = 'manage_organizations';
    case ManageSystemSettings = 'manage_system_settings';
    case ViewAuditLogs = 'view_audit_logs';

    /**
     * Get all permission values.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get permissions for a specific category.
     *
     * @return array<string>
     */
    public static function projectPermissions(): array
    {
        return [
            self::CreateProject->value,
            self::EditProject->value,
            self::DeleteProject->value,
            self::ViewProject->value,
        ];
    }

    /**
     * @return array<string>
     */
    public static function scanPermissions(): array
    {
        return [
            self::RunScan->value,
            self::ViewReports->value,
            self::ExportReports->value,
        ];
    }

    /**
     * @return array<string>
     */
    public static function teamPermissions(): array
    {
        return [
            self::ManageTeamMembers->value,
            self::ManageDepartmentBudgets->value,
            self::InviteTeamMembers->value,
        ];
    }

    /**
     * @return array<string>
     */
    public static function billingPermissions(): array
    {
        return [
            self::ViewBilling->value,
            self::ManageBilling->value,
            self::PurchaseCredits->value,
        ];
    }

    /**
     * @return array<string>
     */
    public static function apiPermissions(): array
    {
        return [
            self::ManageApiKeys->value,
            self::ViewWebhooks->value,
            self::ManageWebhooks->value,
        ];
    }

    /**
     * @return array<string>
     */
    public static function adminPermissions(): array
    {
        return [
            self::AdminAll->value,
            self::ImpersonateUsers->value,
            self::ManageOrganizations->value,
            self::ManageSystemSettings->value,
            self::ViewAuditLogs->value,
        ];
    }
}
