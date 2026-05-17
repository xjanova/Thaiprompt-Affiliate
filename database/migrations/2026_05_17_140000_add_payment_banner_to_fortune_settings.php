<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม fields สำหรับ Payment Banner Composite (QR + ลายธนาคาร) ใน fortune_telling_settings
     *
     * 🎯 (2026-05-17) Anti-FB-detection strategy:
     *   - ระบบ generate ภาพรวม "banner ธนาคาร + dynamic QR + ยอดเงิน" เป็นภาพเดียว
     *   - FB classifier ดูเป็น "promotional graphic" → ลด suspicion + ลดการ flag
     *   - Admin upload custom banner ได้ (override default ที่ระบบ generate)
     *   - Dynamic QR ทุกครั้ง (มียอด+ทศนิยม) → SMS checker จับคู่ได้
     *
     * ⚠️ ใช้ Schema::table() เพื่อเพิ่มคอลัมน์ — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'payment_banner_enabled')) {
                $table->boolean('payment_banner_enabled')
                    ->default(true)
                    ->after('payment_qr_image')
                    ->comment('เปิดใช้ banner composite (QR + ลายธนาคาร) — ลด FB detection');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'payment_banner_template')) {
                $table->string('payment_banner_template', 500)
                    ->nullable()
                    ->after('payment_banner_enabled')
                    ->comment('Path ของ banner template (admin upload) — null = ใช้ default ที่ระบบ generate');
            }

            // ตำแหน่ง+ขนาดของ QR ใน banner (ถ้า admin upload custom banner)
            if (! Schema::hasColumn('fortune_telling_settings', 'payment_banner_qr_x')) {
                $table->integer('payment_banner_qr_x')
                    ->default(100)
                    ->after('payment_banner_template')
                    ->comment('ตำแหน่ง QR x (px) ใน banner');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'payment_banner_qr_y')) {
                $table->integer('payment_banner_qr_y')
                    ->default(150)
                    ->after('payment_banner_qr_x')
                    ->comment('ตำแหน่ง QR y (px) ใน banner');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'payment_banner_qr_size')) {
                $table->integer('payment_banner_qr_size')
                    ->default(400)
                    ->after('payment_banner_qr_y')
                    ->comment('ขนาด QR (px) ใน banner');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $cols = [
                'payment_banner_enabled',
                'payment_banner_template',
                'payment_banner_qr_x',
                'payment_banner_qr_y',
                'payment_banner_qr_size',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
