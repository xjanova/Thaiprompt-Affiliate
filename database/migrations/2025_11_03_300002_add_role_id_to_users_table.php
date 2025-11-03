<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add role_id column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->onDelete('restrict');
        });

        // Migrate existing role data to role_id
        $this->migrateRoleData();

        // After migration, we'll keep the old 'role' column for backward compatibility
        // but role_id will be the primary source of truth
    }

    /**
     * Migrate existing role data from string to role_id
     */
    private function migrateRoleData(): void
    {
        // Get all roles
        $roles = DB::table('roles')->get()->keyBy('name');

        // Update users to use role_id
        foreach ($roles as $roleName => $role) {
            DB::table('users')
                ->where('role', $roleName)
                ->update(['role_id' => $role->id]);
        }

        // Handle any users without a matching role (set to 'user' by default)
        $defaultRole = $roles['user'] ?? null;
        if ($defaultRole) {
            DB::table('users')
                ->whereNull('role_id')
                ->update(['role_id' => $defaultRole->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
};
