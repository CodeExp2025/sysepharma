<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'create_drug',
            'view_drug',
            'edit_drug',
            'delete_drug',
            'create_category',
            'view_category',
            'create_pharmacy',
            'create_depot',
            'transfer_stock',
            'view_stock',
            'sell_unit',
            'view_reports',
            'manage_users',
            'manage_roles',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        // Create Roles and Assign Permissions
        
        // Super Admin
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Pharmacy Admin
        $pharmacyAdmin = Role::firstOrCreate(['name' => 'pharmacy_admin']);
        $pharmacyAdmin->syncPermissions([
            'create_drug', 'view_drug', 'edit_drug', 'delete_drug',
            'create_category', 'view_category',
            'create_depot',
            'transfer_stock', 'view_stock',
            'sell_unit',
            'view_reports', 'manage_users',
        ]);

        // Pharmacy Staff
        $pharmacyStaff = Role::firstOrCreate(['name' => 'pharmacy_staff']);
        $pharmacyStaff->syncPermissions([
            'create_drug', 'view_drug',
            'view_category',
            'transfer_stock', 'view_stock',
            'sell_unit',
            'view_reports',
        ]);

        // Depot Staff
        $depotStaff = Role::firstOrCreate(['name' => 'depot_staff']);
        $depotStaff->givePermissionTo([
            'view_stock', 'sell_unit'
        ]);
    }
}
