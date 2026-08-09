<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่ม "กรอบเวลา" ให้เกณฑ์คอมเมนต์รัว
     *
     * 🐛 แก้ข้อบกพร่องของ migration ก่อนหน้า (2026_08_09_030000):
     * ตัวนับใช้ TTL 7 วัน = นับสะสมทั้งสัปดาห์ → แฟนเพจที่มาคอมวันละครั้ง
     * จะครบ 6 ครั้งใน 6 วันแล้วโดนแบน ทั้งที่เป็นพฤติกรรมปกติ
     *
     * เจ้าของท้วง (2026-08-09): "ต้องมากกว่า 5 ครั้งใน 1 ชั่วโมงถึงเป็นสแปม
     * เพราะบางคนมาโพสวันหนึ่งหลายครั้งได้ ปกตินะ" — ถูกต้อง
     * สแปมคือ "อัตราเร็ว" ไม่ใช่ "ยอดสะสม"
     *
     * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'comment_flood_window_minutes')) {
                $table->unsignedSmallInteger('comment_flood_window_minutes')
                    ->default(60)
                    ->comment('กรอบเวลานับคอมเมนต์รัว (นาที) — เกิน threshold ภายในกรอบนี้ = สแปม');
            }
        });
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'comment_flood_window_minutes')) {
                $table->dropColumn('comment_flood_window_minutes');
            }
        });
    }
};
