<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration สำหรับสร้างตาราง label_paper_sizes
 *
 * เก็บขนาดกระดาษมาตรฐานและกระดาษที่รองรับเครื่องพิมพ์ต่างๆ
 *
 * @author Claude AI
 */
return new class extends Migration
{
    /**
     * สร้างตาราง label_paper_sizes
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('label_paper_sizes')) {
            return;
        }

        Schema::create('label_paper_sizes', function (Blueprint $table) {
            $table->id();

            // ข้อมูลพื้นฐาน
            $table->string('name'); // ชื่อ (เช่น "A4", "สติ๊กเกอร์มาตรฐาน")
            $table->string('name_th')->nullable(); // ชื่อภาษาไทย
            $table->text('description')->nullable(); // คำอธิบาย
            $table->string('category', 50); // หมวดหมู่ (standard, label, receipt, etc.)

            // ขนาด (หน่วย: mm)
            $table->decimal('width', 8, 2); // ความกว้าง
            $table->decimal('height', 8, 2); // ความสูง

            // การจัดวาง (สำหรับ label sheets)
            $table->integer('labels_per_row')->default(1);
            $table->integer('labels_per_column')->default(1);
            $table->decimal('label_width', 8, 2)->nullable();
            $table->decimal('label_height', 8, 2)->nullable();
            $table->decimal('gap_horizontal', 8, 2)->default(0);
            $table->decimal('gap_vertical', 8, 2)->default(0);
            $table->decimal('margin_top', 8, 2)->default(0);
            $table->decimal('margin_left', 8, 2)->default(0);
            $table->decimal('margin_right', 8, 2)->default(0);
            $table->decimal('margin_bottom', 8, 2)->default(0);

            // เครื่องพิมพ์ที่รองรับ
            $table->json('supported_printers')->nullable(); // รายชื่อเครื่องพิมพ์

            // สถานะ
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('category', 'paper_sizes_category_idx');
            $table->index('is_active', 'paper_sizes_active_idx');
            $table->index('sort_order', 'paper_sizes_sort_idx');
        });
    }

    /**
     * ลบตาราง label_paper_sizes
     */
    public function down(): void
    {
        Schema::dropIfExists('label_paper_sizes');
    }
};
