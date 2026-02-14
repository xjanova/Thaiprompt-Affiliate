<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: แก้ไขตาราง kyc_verifications เพื่อรองรับ Mobile App
 *
 * ปัญหาที่พบ:
 * 1. status enum ไม่มี 'draft' - Controller พยายามสร้าง KYC draft ก่อนที่จะ upload รูปครบ
 * 2. id_card_image และ selfie_image ไม่ใช่ nullable - แต่ต้องเป็น null ได้เมื่อสร้าง draft
 *
 * การแก้ไข:
 * 1. เปลี่ยน status เป็น string และเพิ่ม 'draft' เป็นค่าที่รองรับ
 * 2. ทำให้ id_card_image และ selfie_image เป็น nullable
 */
return new class extends Migration
{
    /**
     * แก้ไขตาราง kyc_verifications
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง kyc_verifications มีอยู่หรือไม่
        if (! Schema::hasTable('kyc_verifications')) {
            // ถ้าไม่มี ให้ข้าม migration นี้
            return;
        }

        // 1. เปลี่ยน status จาก enum เป็น string เพื่อรองรับ 'draft'
        // MySQL enum ไม่ง่ายที่จะ add value ใหม่ใน Laravel
        // วิธีที่ปลอดภัยคือใช้ raw SQL
        DB::statement("ALTER TABLE kyc_verifications MODIFY COLUMN status VARCHAR(20) DEFAULT 'draft'");

        // 2. ทำให้ id_card_image และ selfie_image เป็น nullable
        Schema::table('kyc_verifications', function (Blueprint $table) {
            // เปลี่ยนเป็น nullable (ต้อง drop แล้ว recreate)
            $table->string('id_card_image')->nullable()->change();
            $table->string('selfie_image')->nullable()->change();
        });

        // 3. อัพเดท default status สำหรับ records ที่ไม่มี images ให้เป็น draft
        DB::table('kyc_verifications')
            ->whereNull('id_card_image')
            ->orWhereNull('selfie_image')
            ->update(['status' => 'draft']);
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        if (! Schema::hasTable('kyc_verifications')) {
            return;
        }

        // คืนค่า status เป็น enum เดิม (อาจไม่สำเร็จถ้ามี 'draft' อยู่)
        // ดังนั้นจะลบ draft records ก่อน
        DB::table('kyc_verifications')
            ->where('status', 'draft')
            ->delete();

        DB::statement("ALTER TABLE kyc_verifications MODIFY COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");

        // คืนค่า NOT NULL (ต้องมีค่าก่อน)
        // ข้าม step นี้เพราะอาจทำให้ข้อมูลหาย
    }
};
