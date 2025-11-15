<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม expiry date columns สำหรับ carried PV
     *
     * แก้ Bug #3: Missing carry forward PV expiry logic
     * เพิ่ม columns เพื่อเก็บวันหมดอายุของ PV ที่ carry forward
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบตารางก่อนแก้ไข
        if (!Schema::hasTable('mlm_members')) {
            return;
        }

        Schema::table('mlm_members', function (Blueprint $table) {
            // เพิ่ม expiry date สำหรับ carried PV
            if (!Schema::hasColumn('mlm_members', 'carried_left_pv_expires_at')) {
                $table->timestamp('carried_left_pv_expires_at')->nullable()->after('carried_left_pv');
            }

            if (!Schema::hasColumn('mlm_members', 'carried_right_pv_expires_at')) {
                $table->timestamp('carried_right_pv_expires_at')->nullable()->after('carried_right_pv');
            }

            // เพิ่ม days สำหรับ carry forward (default 30 วัน)
            if (!Schema::hasColumn('mlm_members', 'carry_forward_days')) {
                $table->integer('carry_forward_days')->default(30)->after('carried_right_pv_expires_at');
            }
        });
    }

    /**
     * ลบ columns ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        if (!Schema::hasTable('mlm_members')) {
            return;
        }

        Schema::table('mlm_members', function (Blueprint $table) {
            if (Schema::hasColumn('mlm_members', 'carried_left_pv_expires_at')) {
                $table->dropColumn('carried_left_pv_expires_at');
            }

            if (Schema::hasColumn('mlm_members', 'carried_right_pv_expires_at')) {
                $table->dropColumn('carried_right_pv_expires_at');
            }

            if (Schema::hasColumn('mlm_members', 'carry_forward_days')) {
                $table->dropColumn('carry_forward_days');
            }
        });
    }
};
