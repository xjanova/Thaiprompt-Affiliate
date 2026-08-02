<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์คำทำนายราย "ช่วงเวลาของวัน" (เช้า/เที่ยง/บ่าย/เย็น/กลางคืน)
     *
     * เจ้าของสั่ง (2026-08-02): "คำนวณช่วงเวลาของวันไปด้วย มีตอนเช้า เที่ยง บ่าย เย็น
     *   กลางคืน ให้คำทำนายครบ จะได้ครบข้อมูล"
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return — เป็นการเพิ่มคอลัมน์ (ALTER TABLE)
     *
     * 🕐 ทำไมแยกคอลัมน์ ไม่ยัดรวมใน overall_prediction_th:
     *   1. กล่อง DM มีเพดานความยาว — ต้องเลือกได้ว่าจะแนบช่วงเวลาด้วยไหม
     *      ถ้ายัดรวมจะตัดแยกไม่ได้ ต้อง regex หาหัวข้อในเนื้อความซึ่งเปราะ
     *   2. หน้าเว็บดวงรายวันแสดงเป็นบล็อกแยก (timeline) ได้เลยโดยไม่ต้อง parse
     *   3. ของเดิมที่ generate ไว้แล้วยังใช้ได้ปกติ (คอลัมน์ nullable)
     */
    public function up(): void
    {
        Schema::table('horoscope_daily_predictions', function (Blueprint $table) {
            if (! Schema::hasColumn('horoscope_daily_predictions', 'time_prediction_th')) {
                $table->text('time_prediction_th')->nullable()
                    ->after('health_prediction_th')
                    ->comment('คำทำนายรายช่วงเวลาของวัน เช้า/เที่ยง/บ่าย/เย็น/กลางคืน');
            }
        });
    }

    /**
     * ลบคอลัมน์คำทำนายรายช่วงเวลา
     */
    public function down(): void
    {
        Schema::table('horoscope_daily_predictions', function (Blueprint $table) {
            if (Schema::hasColumn('horoscope_daily_predictions', 'time_prediction_th')) {
                $table->dropColumn('time_prediction_th');
            }
        });
    }
};
