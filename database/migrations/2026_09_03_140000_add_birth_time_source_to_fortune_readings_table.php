<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ birth_time_source ในตาราง fortune_readings
     *
     * 🕛 (2026-09-03 owner directive) *"เวลาเกิดควรมีเวลามาตรฐานที่กำหนดไว้
     *    แม้ไม่ได้บอก หรือไม่ทราบ"*
     *
     *    เดิม `birth_time` = NULL แปลว่า "ไม่ทราบ" แล้วค่อยไปใช้ 12:00 ตอนคำนวณ
     *    ⇒ เปิดหลังบ้านมาเห็นช่องว่าง ไม่รู้ว่าผังใบนั้นผูกจากกี่โมง
     *    ใหม่: **เก็บ 12:00 ลงคอลัมน์เลย** แต่ติดป้ายที่มาไว้ว่าเป็นค่ามาตรฐาน
     *
     * ⚠️ ป้ายนี้ขาดไม่ได้ — ถ้าเก็บ 12:00 โดยไม่มีป้าย ระบบจะนึกว่าลูกค้าบอกเอง
     *    แล้วพรอมต์จะพิมพ์ว่า "จากเวลาเกิด 12:00 น. *ที่เจ้าชะตาบอก*" ให้ทุกคน
     *    = โกหกลูกค้าทั้งระบบ (เทียบเคส FTU-260903-X0866 ที่โกหกไปแล้ว 1 ใบ)
     *
     * ค่าที่ใช้: default | customer | admin | backfill
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_readings', 'birth_time_source', function (Blueprint $table) {
                $table->string('birth_time_source', 20)
                    ->nullable()
                    ->after('birth_time')
                    ->comment('ที่มาของ birth_time: default=ค่ามาตรฐาน 12:00 · customer=ลูกค้าบอก · admin=แอดมินกรอก · backfill=กู้ย้อนหลัง');
            });
        });

        // 🕛 บิลเก่าที่รู้วันเกิดแล้วแต่ยังไม่มีเวลา → เติมค่ามาตรฐานย้อนหลังให้ครบ
        //    (owner: *"และคนอื่นๆ ก็เช่นกัน"*) — prod 3 ก.ย. 2569 มีราว 1,130 ใบ
        //
        //    ⚠️ ต้องเติมที่นี่ ไม่ใช่รอ saving hook ของโมเดล — hook ทำงานตอน "เซฟ" เท่านั้น
        //      บิลเก่าที่ปิดไปแล้วจะไม่มีใครไปเซฟอีก ⇒ ค้างเป็นช่องว่างตลอดไป
        //
        //    ⚠️ แตะเฉพาะแถวที่ `birth_time IS NULL` — ห้ามทับของที่ลูกค้าบอกมาจริง
        if (Schema::hasColumn('fortune_readings', 'birth_time_source')) {
            DB::table('fortune_readings')
                ->whereNotNull('birth_date')
                ->whereNull('birth_time')
                ->update([
                    'birth_time' => '12:00:00',
                    'birth_time_source' => 'default',
                ]);
        }
    }

    /**
     * ลบคอลัมน์ birth_time_source
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_readings', ['birth_time_source']);
    }
};
