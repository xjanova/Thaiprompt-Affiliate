<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 🏬 (2026-08-15) ให้แอดมินเลือกเองว่า "สาขาไหนรับโพสอัตโนมัติ"
 *
 * เจ้าของสั่ง: "การโพสอัตโนมัติต้องอย่าให้ผิดพลาด แอดมินต้องเลือกได้ด้วยว่าจะโพสเพจไหน"
 *
 * ⚠️ default = false โดยตั้งใจ (opt-in ไม่ใช่ opt-out)
 *    เปิดสาขาใหม่ = บอทตอบแชทได้ทันที แต่ยังไม่แอบไปโพสลงหน้าเพจเอง
 *    การโพสลงหน้าเพจคนเห็นเยอะและลบยาก — ต้องให้คนกดยืนยันก่อนเสมอ
 *    ยกเว้นสาขาหลักที่โพสอยู่แล้ววันนี้ → เติมเป็น true เพื่อไม่ให้พฤติกรรมเดิมหาย
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_pages')) {
            return;
        }

        // ⚠️ ALTER TABLE — เช็คทีละคอลัมน์ ห้าม hasTable() + return
        if (! Schema::hasColumn('fortune_pages', 'auto_post_enabled')) {
            Schema::table('fortune_pages', function (Blueprint $table) {
                $table->boolean('auto_post_enabled')
                    ->default(false)
                    ->after('is_active');
            });
        }

        // สาขาหลักโพสอยู่แล้ว — ห้ามให้เงียบไปหลัง deploy
        DB::table('fortune_pages')
            ->where('is_default', true)
            ->update(['auto_post_enabled' => true]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_pages') || ! Schema::hasColumn('fortune_pages', 'auto_post_enabled')) {
            return;
        }

        Schema::table('fortune_pages', function (Blueprint $table) {
            $table->dropColumn('auto_post_enabled');
        });
    }
};
