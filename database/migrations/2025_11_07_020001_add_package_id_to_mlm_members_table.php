<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mlm_members', function (Blueprint $table) {
            // Add package_id (แพคเกจที่สมาชิกซื้อ)
            $table->foreignId('package_id')->nullable()->after('mlm_plan_id')
                  ->constrained('mlm_packages')->onDelete('set null');

            // Add index for package queries
            $table->index('package_id');
        });

        // Drop old unique constraint (user_id + mlm_plan_id)
        // Since now everyone uses the same plan, we only need user_id to be unique
        Schema::table('mlm_members', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'mlm_plan_id']);
        });

        // Add new unique constraint (user_id only)
        // One user can only have one MLM membership
        Schema::table('mlm_members', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_members', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('mlm_members', function (Blueprint $table) {
            $table->unique(['user_id', 'mlm_plan_id']);
        });

        Schema::table('mlm_members', function (Blueprint $table) {
            $table->dropIndex(['package_id']);
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
        });
    }
};
