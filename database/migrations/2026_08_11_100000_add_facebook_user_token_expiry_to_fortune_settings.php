<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⏳ (2026-08-11) เก็บ "วันหมดอายุ" ของ User Access Token ของเจ้าของเพจ
 *
 * ทำไมต้องมี 2 คอลัมน์ (นาฬิกาคนละเรือน หมดคนละเวลา):
 *
 *   1. facebook_user_token_expires_at
 *      = วันที่ token ใช้ส่ง/ขออะไรไม่ได้อีกเลย
 *      token จาก Graph API Explorer สดๆ อายุ ~1 ชม. → ต้องแลกเป็นตัวยาว (60 วัน) ก่อนใช้จริง
 *
 *   2. facebook_user_token_data_access_expires_at
 *      = วันที่ "สิทธิ์อ่านข้อมูลผู้ใช้" หมด (90 วันตามกติกา Meta)
 *      ⚠️ ตัวนี้คือฆาตกรเงียบ — token ยังส่งข้อความได้ปกติ (is_valid:true) แต่
 *         getUserProfile / ids_for_pages ตายหมด ขึ้น error_subcode 33
 *         เคยโดนมาแล้ว 2026-07-27 (ลูกค้าบอทเข้าวอลเลตตัวเองไม่ได้)
 *
 * ทั้งคู่ nullable และค่า 0 จาก Graph (= ไม่มีวันหมด) ต้องเก็บเป็น null
 * ห้ามเก็บ 0 ตรงๆ ไม่งั้นกลายเป็น 1970 = หน้าจอฟ้อง "หมดอายุแล้ว" ตลอดกาล
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์วันหมดอายุของ user token
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'facebook_user_token_expires_at')) {
                $table->timestamp('facebook_user_token_expires_at')->nullable();
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'facebook_user_token_data_access_expires_at')) {
                $table->timestamp('facebook_user_token_data_access_expires_at')->nullable();
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่ม
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'facebook_user_token_expires_at',
                'facebook_user_token_data_access_expires_at',
            ] as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
