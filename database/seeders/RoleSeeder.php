<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'vendor']);
        Role::create(['name' => 'customer']);

        // Create permissions
        $permissions = [
            'manage-users',
            'manage-vendors',
            'manage-products',
            'manage-orders',
            'manage-commissions',
            'manage-withdrawals',
            'manage-settings',
            'view-dashboard',
            'create-products',
            'edit-products',
            'delete-products',
            'view-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign permissions to roles
        $adminRole = Role::findByName('admin');
        $adminRole->givePermissionTo(Permission::all());

        $vendorRole = Role::findByName('vendor');
        $vendorRole->givePermissionTo([
            'view-dashboard',
            'create-products',
            'edit-products',
            'delete-products',
        ]);
    }
}
