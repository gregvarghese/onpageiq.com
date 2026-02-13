<?php

namespace Database\Seeders;

use App\Enums\Permission;
use App\Enums\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role as RoleModel;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        foreach (Permission::values() as $permission) {
            PermissionModel::findOrCreate($permission, 'web');
        }

        // Create roles and assign permissions

        // Super Admin - gets all permissions via Gate::before, but we assign for clarity
        RoleModel::findOrCreate(Role::SuperAdmin->value, 'web');

        // Owner - All permissions except system admin
        $ownerPermissions = array_merge(
            Permission::projectPermissions(),
            Permission::scanPermissions(),
            Permission::teamPermissions(),
            Permission::billingPermissions(),
            Permission::apiPermissions(),
            [Permission::AdminAll->value]
        );
        RoleModel::findOrCreate(Role::Owner->value, 'web')
            ->syncPermissions($ownerPermissions);

        // Admin - All except billing management and delete org
        $adminPermissions = array_merge(
            Permission::projectPermissions(),
            Permission::scanPermissions(),
            Permission::teamPermissions(),
            [Permission::ViewBilling->value, Permission::PurchaseCredits->value],
            Permission::apiPermissions()
        );
        RoleModel::findOrCreate(Role::Admin->value, 'web')
            ->syncPermissions($adminPermissions);

        // Manager - Department management + all project permissions
        $managerPermissions = array_merge(
            Permission::projectPermissions(),
            Permission::scanPermissions(),
            [
                Permission::ManageTeamMembers->value,
                Permission::ManageDepartmentBudgets->value,
                Permission::InviteTeamMembers->value,
                Permission::ViewBilling->value,
                Permission::ManageApiKeys->value,
                Permission::ViewWebhooks->value,
            ]
        );
        RoleModel::findOrCreate(Role::Manager->value, 'web')
            ->syncPermissions($managerPermissions);

        // Member - Run scans, view reports, export, basic project access
        $memberPermissions = [
            Permission::CreateProject->value,
            Permission::EditProject->value,
            Permission::ViewProject->value,
            Permission::RunScan->value,
            Permission::ViewReports->value,
            Permission::ExportReports->value,
        ];
        RoleModel::findOrCreate(Role::Member->value, 'web')
            ->syncPermissions($memberPermissions);

        // Viewer - View reports only
        $viewerPermissions = [
            Permission::ViewProject->value,
            Permission::ViewReports->value,
        ];
        RoleModel::findOrCreate(Role::Viewer->value, 'web')
            ->syncPermissions($viewerPermissions);
    }
}
