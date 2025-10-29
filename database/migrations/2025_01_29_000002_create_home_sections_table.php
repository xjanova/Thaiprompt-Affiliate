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
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ชื่อ section
            $table->string('title')->nullable(); // หัวข้อที่แสดง
            $table->string('subtitle')->nullable(); // คำบรรยายรอง
            $table->text('content')->nullable(); // เนื้อหา
            $table->enum('type', ['hero', 'features', 'statistics', 'testimonials', 'cta', 'custom'])->default('custom');
            $table->json('settings')->nullable(); // การตั้งค่าเพิ่มเติม (สี, รูปภาพ, etc.)
            $table->json('allowed_roles')->nullable(); // ['admin', 'user', 'affiliate'] หรือ null = ทุกคน
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('schedule_start')->nullable(); // เริ่มแสดงเมื่อไหร่
            $table->timestamp('schedule_end')->nullable(); // สิ้นสุดเมื่อไหร่
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('home_sections');
    }
};
