<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง recruit_templates
     *
     * ตารางนี้เก็บเทมเพลตหน้า Recruit ที่แอดมินสร้างไว้
     * แม่ทีมจะใช้เทมเพลตนี้เป็นพื้นฐาน แล้วปรับแต่งข้อมูลส่วนตัว
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('recruit_templates')) {
            return;
        }

        Schema::create('recruit_templates', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐานของเทมเพลต
            $table->string('title')->comment('ชื่อเทมเพลต');
            $table->text('description')->nullable()->comment('คำอธิบายเทมเพลต');

            // ข้อความและเนื้อหา
            $table->text('welcome_message')->comment('ข้อความต้อนรับ');
            $table->text('pitch_message')->nullable()->comment('คำชักชวนตัวอย่าง');

            // ข้อมูล JSON สำหรับความยืดหยุ่น
            $table->json('benefits')->nullable()->comment('ประโยชน์ที่ได้รับ (Array)');
            $table->json('instructions')->nullable()->comment('คู่มือการสมัคร (Array of steps)');
            $table->json('required_steps')->nullable()->comment('ขั้นตอนที่บังคับ (Array)');
            $table->json('features')->nullable()->comment('ฟีเจอร์พิเศษ (Array)');

            // การตั้งค่า
            $table->boolean('require_line_friend')->default(true)->comment('บังคับเพิ่มเพื่อน LINE ก่อนสมัคร');
            $table->boolean('show_team_leader_info')->default(true)->comment('แสดงข้อมูลแม่ทีม');
            $table->boolean('show_statistics')->default(true)->comment('แสดงสถิติ');

            // สถานะ
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->boolean('is_default')->default(false)->comment('เทมเพลต default');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('is_default');
        });
    }

    /**
     * ลบตาราง recruit_templates
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('recruit_templates');
    }
};
