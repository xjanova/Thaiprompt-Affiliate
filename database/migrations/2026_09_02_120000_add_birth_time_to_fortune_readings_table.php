<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ birth_time (เวลาเกิด) ในตาราง fortune_readings
     *
     * 🕛 (2026-09-02 owner directive) "เราไม่ได้ถามเวลาเกิดอยู่แล้ว ให้ยึด 12.00 น. แต่ถ้าลูกค้าบอก
     *   ก็เอามาคำนวณด้วย บอทถามทีหลัง/ลูกค้าบอกทีหลัง ต้องคำนวณใหม่ได้ และหลังบ้านต้องบันทึกเวลาเกิดได้"
     *
     * ทำไมต้องแยกคอลัมน์ ไม่ยัดใส่ birth_date (datetime):
     *   - โมเดล cast birth_date เป็น 'date' → เวลาถูกปัดเป็น 00:00 ตอนอ่าน/serialize
     *   - 00:00 กำกวม: "ไม่รู้เวลา" หรือ "เกิดเที่ยงคืนจริง" แยกไม่ออก
     *   - แอดมินแก้ผ่าน <input type="date"> อยู่แล้ว — เพิ่ม <input type="time"> ข้างๆ ตรงไปตรงมากว่า
     *
     * NULL = ไม่ทราบเวลา → ThaiAstrologyService ใช้ DEFAULT_BIRTH_HOUR (12:00 น.)
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_readings', 'birth_time', function ($table) {
                $table->time('birth_time')->nullable()->after('birth_date')
                    ->comment('เวลาเกิด (ลูกค้าบอก/แอดมินกรอก) — NULL = ไม่ทราบ ใช้ 12:00 น.');
            });
        });
    }

    /**
     * ลบคอลัมน์ birth_time
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_readings', ['birth_time']);
    }
};
