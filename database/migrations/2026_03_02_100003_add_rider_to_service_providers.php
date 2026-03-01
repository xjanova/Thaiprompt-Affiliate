<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เชื่อม Service Provider กับ Rider (ไรเดอร์เซอร์วิส)
     *
     * คอลัมน์ใหม่:
     * - rider_id — เชื่อมกับ riders table (nullable)
     * - accepts_delivery_jobs — รับงานส่งของด้วยหรือไม่
     */
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            if (!Schema::hasColumn('service_providers', 'rider_id')) {
                $table->unsignedBigInteger('rider_id')->nullable()->after('owner_id');
            }
            if (!Schema::hasColumn('service_providers', 'accepts_delivery_jobs')) {
                $table->boolean('accepts_delivery_jobs')->default(false)->after('rider_id');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            if (Schema::hasColumn('service_providers', 'rider_id')) {
                $table->dropColumn('rider_id');
            }
            if (Schema::hasColumn('service_providers', 'accepts_delivery_jobs')) {
                $table->dropColumn('accepts_delivery_jobs');
            }
        });
    }
};
