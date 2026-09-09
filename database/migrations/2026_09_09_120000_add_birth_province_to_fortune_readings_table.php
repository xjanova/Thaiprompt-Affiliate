<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ birth_province + birth_province_source ในตาราง fortune_readings
     *
     * 🗺️ (2026-09-09) จังหวัดเกิด = วัตถุดิบที่ขาดไปของการผูกลัคนา
     *
     *    ลัคนาคำนวณจาก Local Sidereal Time ซึ่งขึ้นกับ **ลองจิจูด** ของสถานที่เกิด
     *    และมุมขึ้นของราศีขึ้นกับ **ละติจูด** — ระบบเดิมฮาร์ดโค้ดกรุงเทพให้ทุกคน
     *    วัดจริง (เกิด 15 พ.ค. 2533 08:30): ภูเก็ต 3.4° · กรุงเทพ 7.8° · อุบลฯ 12.4°
     *    ⇒ สเปรด ~9° ⇒ ราว 1 ใน 6-7 ดวง ลัคนาข้ามไปคนละราศี
     *
     * ⚠️ ต่างจาก birth_time ตรงที่ **ไม่เติมค่ามาตรฐานลงคอลัมน์**
     *    NULL = ไม่ทราบจริง ๆ และผังจะพิมพ์บอกลูกค้าตามตรงว่าใช้พิกัดกรุงเทพเป็นค่ากลาง
     *    (บทเรียนจาก birth_time: เก็บค่ามาตรฐานลงคอลัมน์แล้วต้องมีป้ายกำกับเสมอ
     *     ไม่งั้นระบบนึกว่าลูกค้าบอกเอง — ที่นี่เลือกวิธีที่ไม่ต้องมีป้ายให้พลาด)
     *
     * ค่าที่ใช้ใน source: customer | admin | celtic_birthdate | time_answer | place_answer
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            $this->safeAddColumn($table, 'fortune_readings', 'birth_province', function (Blueprint $table) {
                $table->string('birth_province', 60)
                    ->nullable()
                    ->after('birth_time_source')
                    ->comment('จังหวัดเกิด (ชื่อทางการ) — ใช้หาพิกัดผูกลัคนา · NULL = ไม่ทราบ');
            });

            $this->safeAddColumn($table, 'fortune_readings', 'birth_province_source', function (Blueprint $table) {
                $table->string('birth_province_source', 20)
                    ->nullable()
                    ->after('birth_province')
                    ->comment('ที่มาของ birth_province: customer=ลูกค้าบอก · admin=แอดมินกรอก');
            });
        });
    }

    /**
     * ลบคอลัมน์จังหวัดเกิด
     */
    public function down(): void
    {
        $this->safeDropColumn('fortune_readings', ['birth_province', 'birth_province_source']);
    }
};
