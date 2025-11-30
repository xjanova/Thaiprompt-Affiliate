<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์สำหรับ User Live Location
 *
 * ใช้สำหรับติดตามตำแหน่ง real-time ของลูกค้า
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง service_bookings มีอยู่แล้วหรือไม่
        if (!Schema::hasTable('service_bookings')) {
            return;
        }

        Schema::table('service_bookings', function (Blueprint $table) {
            // ตำแหน่ง live ของลูกค้า (สำหรับ real-time tracking)
            if (!Schema::hasColumn('service_bookings', 'user_live_latitude')) {
                $table->decimal('user_live_latitude', 10, 8)->nullable()->after('customer_address');
            }

            if (!Schema::hasColumn('service_bookings', 'user_live_longitude')) {
                $table->decimal('user_live_longitude', 11, 8)->nullable()->after('user_live_latitude');
            }

            if (!Schema::hasColumn('service_bookings', 'user_location_updated_at')) {
                $table->timestamp('user_location_updated_at')->nullable()->after('user_live_longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('service_bookings', 'user_live_latitude')) {
                $table->dropColumn('user_live_latitude');
            }

            if (Schema::hasColumn('service_bookings', 'user_live_longitude')) {
                $table->dropColumn('user_live_longitude');
            }

            if (Schema::hasColumn('service_bookings', 'user_location_updated_at')) {
                $table->dropColumn('user_location_updated_at');
            }
        });
    }
};
