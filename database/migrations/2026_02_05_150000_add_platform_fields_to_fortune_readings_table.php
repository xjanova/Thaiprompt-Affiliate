<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม platform fields สำหรับรองรับหลายช่องทาง
 * (Facebook, LINE, และช่องทางอื่นในอนาคต)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            // เพิ่ม platform field (facebook, line, etc.)
            if (! Schema::hasColumn('fortune_readings', 'platform')) {
                $table->string('platform', 20)->default('facebook')->after('response_type');
            }

            // เพิ่ม platform_user_id (สำหรับเก็บ user ID ของแต่ละ platform)
            if (! Schema::hasColumn('fortune_readings', 'platform_user_id')) {
                $table->string('platform_user_id', 100)->nullable()->after('platform');
            }

            // เพิ่ม index
            if (! Schema::hasColumn('fortune_readings', 'platform')) {
                $table->index(['platform', 'platform_user_id'], 'fortune_platform_user_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            // ลบ index ก่อน
            $table->dropIndex('fortune_platform_user_idx');

            // ลบ columns
            if (Schema::hasColumn('fortune_readings', 'platform')) {
                $table->dropColumn('platform');
            }
            if (Schema::hasColumn('fortune_readings', 'platform_user_id')) {
                $table->dropColumn('platform_user_id');
            }
        });
    }
};
