<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get roles and permissions
        $roles = DB::table('roles')->get()->keyBy('name');
        $permissions = DB::table('permissions')->get()->keyBy('name');

        // Define default permissions for each role
        $rolePermissions = [
            'user' => [
                'view_dashboard',
            ],
            'seller' => [
                'view_dashboard',
                'view_reports',
            ],
            'admin' => [
                'view_dashboard',
                'view_reports',
                'manage_users',
                'manage_affiliates',
                'manage_commissions',
                'manage_wallets',
                'approve_withdrawals',
                'view_all_wallets',
                'manage_ranks',
                'approve_rank_promotions',
                'view_rank_analytics',
                'view_kyc_verifications',
                'approve_kyc',
            ],
            'super_admin' => [
                // Super admin gets all permissions
                'view_dashboard',
                'view_reports',
                'manage_settings',
                'manage_branding',
                'manage_users',
                'manage_permissions',
                'manage_affiliates',
                'manage_commissions',
                'manage_wallets',
                'approve_withdrawals',
                'view_all_wallets',
                'manage_wallet_settings',
                'manage_ranks',
                'approve_rank_promotions',
                'view_rank_analytics',
                'view_kyc_verifications',
                'approve_kyc',
                'manage_kyc',
            ],
        ];

        // Assign permissions to roles
        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = $roles[$roleName] ?? null;
            if (!$role) {
                continue;
            }

            foreach ($permissionNames as $permissionName) {
                $permission = $permissions[$permissionName] ?? null;
                if (!$permission) {
                    continue;
                }

                DB::table('role_permissions')->insert([
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('role_permissions')->truncate();
    }
};
