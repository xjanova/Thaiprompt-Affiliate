<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง id_card_settings
 *
 * เก็บการตั้งค่า Virtual ID Card สำหรับแต่ละระดับ Rank
 * รวมถึงตำแหน่งขององค์ประกอบต่างๆ บนบัตร
 *
 * @date 2025-11-26
 */
return new class extends Migration
{
    /**
     * สร้างตาราง id_card_settings
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('id_card_settings')) {
            return;
        }

        Schema::create('id_card_settings', function (Blueprint $table) {
            $table->id();

            // ระดับ Rank ที่ใช้การตั้งค่านี้ (1-8, null = ใช้กับทุกระดับ)
            $table->unsignedTinyInteger('rank_level')->nullable()->index();

            // ชื่อการตั้งค่า (สำหรับแยกแยะ)
            $table->string('name', 100)->default('Default');
            $table->string('name_th', 100)->nullable();

            // ===== พื้นหลังบัตร =====
            // ประเภทพื้นหลัง: gradient, image, color
            $table->enum('background_type', ['gradient', 'image', 'color'])->default('gradient');

            // สีพื้นหลัง (สำหรับ type = color)
            $table->string('background_color', 7)->nullable();

            // Gradient (สำหรับ type = gradient) - เก็บเป็น JSON
            // { "from": "#color", "via": "#color", "to": "#color", "direction": "br" }
            $table->json('background_gradient')->nullable();

            // รูปภาพพื้นหลัง (สำหรับ type = image)
            $table->string('background_image', 500)->nullable();

            // Overlay (สีทับบนพื้นหลัง)
            $table->string('overlay_color', 50)->nullable();
            $table->unsignedTinyInteger('overlay_opacity')->default(0);

            // ===== ตำแหน่งองค์ประกอบ (เก็บเป็น JSON) =====
            // แต่ละ element มี: { x: number, y: number, visible: boolean, style: {} }

            // Logo position
            $table->json('logo_position')->nullable();

            // Profile picture position
            $table->json('profile_position')->nullable();

            // Name position
            $table->json('name_position')->nullable();

            // Rank badge position
            $table->json('rank_badge_position')->nullable();

            // Member number position
            $table->json('member_number_position')->nullable();

            // QR code position
            $table->json('qr_position')->nullable();

            // Member since position
            $table->json('member_since_position')->nullable();

            // Stars position
            $table->json('stars_position')->nullable();

            // ===== สไตล์เพิ่มเติม =====
            // Border style
            $table->json('border_style')->nullable();

            // Shadow style
            $table->json('shadow_style')->nullable();

            // Effects (animations, particles, etc.)
            $table->json('effects')->nullable();

            // Custom CSS (สำหรับ advanced customization)
            $table->text('custom_css')->nullable();

            // ===== สถานะ =====
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['rank_level', 'is_active'], 'idx_rank_active');
        });
    }

    /**
     * ลบตาราง id_card_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('id_card_settings');
    }
};
