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
        // Themes table - สำหรับเก็บ theme ที่ admin สร้าง
        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // ชื่อ theme (เช่น "default", "ocean", "sunset")
            $table->string('display_name'); // ชื่อแสดง (ภาษาไทย)
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false); // theme เริ่มต้น
            $table->boolean('is_system')->default(false); // theme ของระบบ (ห้ามลบ)
            $table->boolean('is_active')->default(true);

            // Theme configuration (JSON)
            $table->json('colors')->nullable(); // สีต่างๆ
            $table->json('typography')->nullable(); // ฟอนต์
            $table->json('spacing')->nullable(); // ระยะห่าง
            $table->json('borders')->nullable(); // border radius
            $table->json('shadows')->nullable(); // shadows
            $table->json('animations')->nullable(); // animation settings
            $table->json('custom_css')->nullable(); // custom CSS

            // Dark mode colors (สีสำหรับ dark mode)
            $table->json('dark_colors')->nullable();

            // Preview images
            $table->string('preview_light')->nullable(); // รูป preview โหมดสว่าง
            $table->string('preview_dark')->nullable(); // รูป preview โหมดมืด

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('name');
            $table->index('is_default');
            $table->index('is_active');
        });

        // User themes - เก็บว่า user เลือกใช้ theme ไหน
        Schema::create('user_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theme_id')->constrained()->cascadeOnDelete();
            $table->enum('mode', ['light', 'dark', 'auto'])->default('auto'); // โหมดที่เลือก
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // User สามารถมี theme ได้ทีละ 1 theme
            $table->unique(['user_id', 'is_active']);

            // Indexes
            $table->index('user_id');
            $table->index('theme_id');
        });

        // Theme presets - สำหรับเก็บ preset ที่ admin บันทึกไว้
        Schema::create('theme_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('category')->default('general'); // general, professional, colorful, minimal
            $table->json('config'); // configuration ทั้งหมด
            $table->string('preview_image')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('usage_count')->default(0); // นับจำนวนการใช้งาน
            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_themes');
        Schema::dropIfExists('theme_presets');
        Schema::dropIfExists('themes');
    }
};
