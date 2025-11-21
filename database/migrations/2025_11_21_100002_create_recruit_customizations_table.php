<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง recruit_customizations
     *
     * ตารางนี้เก็บข้อมูลที่แม่ทีมปรับแต่งเอง
     * แต่ละ user มีได้แค่ 1 recruit page
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตาราง users มีอยู่หรือไม่
        if (!Schema::hasTable('users')) {
            throw new \Exception('Table "users" must exist before creating "recruit_customizations" table.');
        }

        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('recruit_customizations')) {
            return;
        }

        Schema::create('recruit_customizations', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('แม่ทีม (User)');

            $table->foreignId('template_id')
                ->nullable()
                ->constrained('recruit_templates')
                ->onDelete('set null')
                ->comment('เทมเพลตที่ใช้');

            // ข้อมูลที่แม่ทีมแก้ไขได้
            $table->string('custom_name')->nullable()->comment('ชื่อที่แสดง');
            $table->string('custom_phone', 20)->nullable()->comment('เบอร์โทรติดต่อ');
            $table->text('custom_address')->nullable()->comment('ที่อยู่');
            $table->string('custom_image')->nullable()->comment('รูปภาพแม่ทีม');
            $table->text('custom_pitch')->nullable()->comment('คำชักชวนส่วนตัว');

            // การตั้งค่าเพิ่มเติม
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหน้า recruit');

            // สถิติ (จะอัพเดทโดยระบบ)
            $table->integer('total_views')->default(0)->comment('จำนวนการเข้าชมทั้งหมด');
            $table->integer('total_leads')->default(0)->comment('จำนวน leads ทั้งหมด');
            $table->integer('total_conversions')->default(0)->comment('จำนวนคนที่สมัครสำเร็จ');
            $table->decimal('conversion_rate', 5, 2)->default(0)->comment('อัตราการแปลง (%)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique('user_id', 'recruit_custom_user_unique');
            $table->index('is_active');
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * ลบตาราง recruit_customizations
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('recruit_customizations');
    }
};
