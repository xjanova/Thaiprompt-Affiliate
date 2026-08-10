<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🏬 (2026-08-10) ระบบสาขา — ตารางเพจแม่หมอ (fortune_pages)
 *
 * ก่อนหน้านี้ระบบผูกกับ "เพจเดียว" ผ่าน FortuneTellingSetting (singleton แถวเดียว)
 * ทำให้เพิ่มเพจใหม่ไม่ได้เลย: token ส่งข้อความมีตัวเดียว, บิลไม่รู้ว่ามาจากเพจไหน
 *
 * ตารางนี้ = "สาขา" 1 แถวต่อ 1 เพจ เก็บเฉพาะสิ่งที่ต้องต่างกันจริงๆ
 * ส่วนค่าอื่นๆ (302 คอลัมน์ใน fortune_telling_settings) ยัง fallback ไปที่ตัว global
 * โดยเขียนทับเฉพาะคีย์ที่ใส่ไว้ใน settings_override
 *
 * ⚠️ ห้ามก็อป fortune_telling_settings ทั้งตารางมาต่อเพจ — 302 คอลัมน์ × N เพจ = นรก
 */
return new class extends Migration
{
    /**
     * สร้างตาราง fortune_pages
     */
    public function up(): void
    {
        if (Schema::hasTable('fortune_pages')) {
            return;
        }

        Schema::create('fortune_pages', function (Blueprint $table) {
            $table->id();

            // 🏷️ ข้อมูลสาขา
            $table->string('code', 40)->unique()->comment('รหัสสาขา slug เช่น chantra, mor-nid');
            $table->string('name', 120)->comment('ชื่อสาขา/ชื่อเพจที่แอดมินเห็น');
            $table->string('brand_name', 120)->nullable()->comment('ชื่อแบรนด์ที่ลูกค้าเห็น (override fortune_brand_name)');

            // 🔌 ช่องทาง
            $table->string('platform', 16)->default('facebook')->comment('facebook / line');
            $table->string('external_page_id', 64)->comment('Facebook Page ID (entry.id ใน webhook) / LINE channel id');

            // 🔑 ความลับต่อเพจ
            //    - page_access_token: จำเป็นเสมอ (ส่งข้อความด้วย token ของเพจนั้นเท่านั้น)
            //    - app_secret / verify_token: ใส่เฉพาะกรณีเพจอยู่คนละ Meta App
            //      ถ้าเว้นว่าง = ใช้ค่า global จาก fortune_telling_settings
            $table->text('page_access_token')->nullable();
            $table->string('app_secret', 255)->nullable()->comment('เว้นว่าง = ใช้ app secret ตัว global');
            $table->string('verify_token', 255)->nullable()->comment('เว้นว่าง = ใช้ verify token ตัว global');

            // ⚙️ ค่าที่ override เฉพาะสาขานี้ {คอลัมน์ใน fortune_telling_settings: ค่า}
            $table->json('settings_override')->nullable();

            // 👤 เจ้าของสาขา (ไว้ต่อยอดแบ่งรายได้ในอนาคต — เฟสนี้ยังไม่ใช้คำนวณเงิน)
            $table->foreignId('owner_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // 🚦 สถานะ
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false)->comment('สาขาหลัก — ใช้เป็น fallback เมื่อ resolve เพจไม่เจอ');

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // 🔍 Indexes
            $table->unique(['platform', 'external_page_id'], 'ftn_pages_platform_ext_unique');
            $table->index(['is_active', 'platform'], 'ftn_pages_active_platform_idx');
        });
    }

    /**
     * ลบตาราง fortune_pages
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_pages');
    }
};
