<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🪪 AI eKYC (2026-10-04 รอบแก้ผลรีวิว) — ลายนิ้วมือภาพของเฟรมใบหน้า
 *
 * ekyc_frame_hashes = dHash 256 บิตของแต่ละเฟรม (hex) ไว้จับการเอาภาพนิ่งชุดเดิมมาส่งซ้ำข้ามรอบ/ข้ามบัญชี
 * ไม่ใช่รูปและย้อนกลับเป็นใบหน้าไม่ได้ · ลบไปพร้อมแถวตอนลบบัญชี
 *
 * เพิ่มคอลัมน์อย่างเดียว · รันซ้ำได้ (SafeMigration)
 */
return new class extends Migration
{
    use SafeMigration;

    public function up(): void
    {
        if (! Schema::hasTable('kyc_verifications')) {
            return;
        }

        Schema::table('kyc_verifications', function (Blueprint $table) {
            $this->safeAddColumn($table, 'kyc_verifications', 'ekyc_frame_hashes', fn ($c) => $c->json('ekyc_frame_hashes')->nullable());
        });
    }

    public function down(): void
    {
        $this->safeDropColumn('kyc_verifications', ['ekyc_frame_hashes']);
    }
};
