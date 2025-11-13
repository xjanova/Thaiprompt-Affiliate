<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // ชื่อเกม
            $table->string('title_en')->nullable(); // ชื่อเกมภาษาอังกฤษ
            $table->text('description')->nullable(); // คำอธิบาย
            $table->text('description_en')->nullable(); // คำอธิบายภาษาอังกฤษ
            $table->string('icon')->default('🎮'); // ไอคอน emoji
            $table->string('image')->nullable(); // รูปภาพ
            $table->string('url'); // URL ของเกม
            $table->string('primary_color')->default('#00ffff'); // สีหลัก
            $table->string('secondary_color')->default('#0080ff'); // สีรอง
            $table->string('glow_color')->default('rgba(0, 255, 255, 0.8)'); // สี glow
            $table->integer('order')->default(0); // ลำดับการแสดงผล
            $table->boolean('is_active')->default(true); // สถานะเปิด/ปิด
            $table->string('card_style')->default('default'); // รูปแบบการ์ด: default, gradient, glass, neon
            $table->json('meta_data')->nullable(); // ข้อมูลเพิ่มเติม
            $table->timestamps();
            $table->softDeletes(); // Soft delete
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
