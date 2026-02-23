<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง horoscope_numerology_readings — บันทึกการวิเคราะห์เลขศาสตร์
     *
     * รองรับ 5 ประเภท: ชื่อ, เบอร์โทร, ทะเบียนรถ, เลขบัตรประชาชน, วันเกิด
     * เก็บข้อมูลการวิเคราะห์ + ผล AI + เลข/สีมงคล
     *
     * @return void
     */
    public function up(): void
    {
        if (Schema::hasTable('horoscope_numerology_readings')) {
            return;
        }

        Schema::create('horoscope_numerology_readings', function (Blueprint $table) {
            $table->id();

            // ผู้ใช้ (nullable สำหรับ guest)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->string('session_id', 64)->comment('Session ID สำหรับ guest tracking');

            // ประเภทการวิเคราะห์
            $table->enum('reading_type', ['name', 'phone', 'license_plate', 'id_card', 'birthday'])
                ->comment('ประเภท: ชื่อ/เบอร์โทร/ทะเบียนรถ/บัตรประชาชน/วันเกิด');

            // ข้อมูลที่วิเคราะห์
            $table->string('input_value', 255)->comment('ค่าที่ป้อน (ชื่อ/เบอร์/ทะเบียน)');
            $table->json('input_data')->nullable()->comment('ข้อมูลแยกส่วน เช่น {first_name, last_name}');

            // ผลวิเคราะห์
            $table->text('ai_analysis')->nullable()->comment('ผลวิเคราะห์จาก AI');
            $table->text('summary_th')->nullable()->comment('สรุปสั้นๆ ภาษาไทย');
            $table->unsignedSmallInteger('numerology_number')->nullable()->comment('ตัวเลขประจำตัว (1-9)');
            $table->text('numerology_meaning_th')->nullable()->comment('ความหมายตัวเลข');

            // เลข/สีมงคล
            $table->json('lucky_numbers')->nullable()->comment('เลขมงคล');
            $table->json('lucky_colors')->nullable()->comment('สีมงคล');

            // AI ที่ใช้
            $table->string('ai_provider_used', 50)->nullable();
            $table->string('ai_model_used', 100)->nullable();

            // สถิติ
            $table->boolean('is_free')->default(true);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('share_count')->default(0);

            // ข้อมูลผู้ใช้
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('session_id', 'numer_read_session_idx');
            $table->index('user_id', 'numer_read_user_idx');
            $table->index(['reading_type', 'created_at'], 'numer_read_type_idx');
        });
    }

    /**
     * ลบตาราง horoscope_numerology_readings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('horoscope_numerology_readings');
    }
};
