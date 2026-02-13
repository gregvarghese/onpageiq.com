<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolesAndPermissionsSeeder::class);

        // Create Super Admin user
        $superAdminOrg = Organization::factory()->enterprise()->create([
            'name' => 'OnPageIQ Admin',
        ]);

        $superAdmin = User::factory()->forOrganization($superAdminOrg)->create([
            'name' => 'Gregory Varghese',
            'email' => 'admin@onpageiq.com',
        ]);
        $superAdmin->assignRole(Role::SuperAdmin->value);

        // Create a test organization with owner
        $testOrg = Organization::factory()->create([
            'name' => 'OnPageIQ',
        ]);

        $testOwner = User::factory()->forOrganization($testOrg)->create([
            'name' => 'Test Owner',
            'email' => 'owner@example.com',
        ]);
        $testOwner->assignRole(Role::Owner->value);

        // Create a test member
        $testMember = User::factory()->forOrganization($testOrg)->create([
            'name' => 'Test Member',
            'email' => 'member@example.com',
        ]);
        $testMember->assignRole(Role::Member->value);
    }
}
