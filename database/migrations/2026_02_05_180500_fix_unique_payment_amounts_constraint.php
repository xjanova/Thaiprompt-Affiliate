<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * แก้ไข unique constraint ที่ผิดพลาดใน unique_payment_amounts
 *
 * ปัญหา: มี unique constraint บน (base_amount, decimal_suffix, status)
 * ทำให้ไม่สามารถมี record ที่มี status=expired มากกว่า 1 record ต่อ base_amount+suffix
 *
 * วิธีแก้: ลบ unique constraint ออก และลบ expired records ที่ค้างอยู่
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ลบ expired records ทั้งหมดก่อน (ไม่จำเป็นต้องเก็บ)
        DB::table('unique_payment_amounts')
            ->where('status', 'expired')
            ->delete();

        // ลบ used records ที่เก่ากว่า 7 วัน
        DB::table('unique_payment_amounts')
            ->where('status', 'used')
            ->where('updated_at', '<', now()->subDays(7))
            ->delete();

        // ลบ unique index ที่ผิดพลาด (ถ้ามี)
        Schema::table('unique_payment_amounts', function (Blueprint $table) {
            // ลอง drop unique index ที่อาจถูกสร้างโดย mistake
            try {
                $table->dropUnique('unique_payment_amounts_base_amount_decimal_suffix_status_unique');
            } catch (\Exception $e) {
                // Index อาจไม่มีอยู่ - skip
            }
        });

        // สร้าง index ที่ถูกต้อง (ไม่ใช่ unique, เฉพาะ reserved เท่านั้น)
        // เพื่อให้ query หา suffix ที่ว่างได้เร็ว
        Schema::table('unique_payment_amounts', function (Blueprint $table) {
            // Index สำหรับหา reserved suffixes ที่ยังไม่หมดอายุ
            $table->index(['base_amount', 'status', 'expires_at'], 'upa_base_status_expires_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unique_payment_amounts', function (Blueprint $table) {
            try {
                $table->dropIndex('upa_base_status_expires_idx');
            } catch (\Exception $e) {
                // Index อาจไม่มี
            }
        });
    }
};
