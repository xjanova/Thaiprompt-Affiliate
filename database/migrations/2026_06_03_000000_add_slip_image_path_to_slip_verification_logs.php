<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ slip_image_path ในตาราง slip_verification_logs
     *
     * เก็บ path รูปสลิป "ที่ส่งไปตรวจจริง" (archived) เพื่อให้แอดมินเปิดดูได้ว่า
     * ส่งรูปอะไรไป SlipOK — debug เคส no_qr (ลูกค้าบอกมี QR แต่ระบบอ่านไม่เจอ)
     *
     * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) → ห้ามใช้ Schema::hasTable() + return
     *    ใช้ Schema::hasColumn() เช็คทีละคอลัมน์แทน
     */
    public function up(): void
    {
        if (! Schema::hasTable('slip_verification_logs')) {
            return;
        }

        Schema::table('slip_verification_logs', function (Blueprint $table) {
            // path รูปสลิปที่ archived ไว้ (disk local: fortune/slip_archive/...)
            if (! Schema::hasColumn('slip_verification_logs', 'slip_image_path')) {
                $table->string('slip_image_path', 255)->nullable()->after('receiver_account')
                    ->comment('path รูปสลิปที่ส่งไปตรวจ (archived 30 วัน — debug no_qr)');
            }
        });
    }

    /**
     * ลบคอลัมน์ slip_image_path
     */
    public function down(): void
    {
        if (! Schema::hasTable('slip_verification_logs')) {
            return;
        }

        Schema::table('slip_verification_logs', function (Blueprint $table) {
            if (Schema::hasColumn('slip_verification_logs', 'slip_image_path')) {
                $table->dropColumn('slip_image_path');
            }
        });
    }
};
