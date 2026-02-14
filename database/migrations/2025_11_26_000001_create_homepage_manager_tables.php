<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * สร้างตารางสำหรับระบบ Homepage Manager
 *
 * ระบบนี้ช่วยให้ Admin สามารถจัดการหน้าแรกได้แบบ Visual Editor
 * - แบ่งเป็น Sections ที่ลากย้ายลำดับได้
 * - แต่ละ Section มี Elements ที่ลากวางตำแหน่งได้อิสระ
 * - รองรับการปรับแต่ง สี พื้นหลัง ขนาด ตำแหน่ง
 *
 * @return void
 */
return new class extends Migration
{
    use SafeMigration;

    /**
     * สร้างตาราง homepage_sections และ homepage_elements
     */
    public function up(): void
    {
        // ตาราง homepage_sections - แต่ละ section บนหน้าแรก
        if (! Schema::hasTable('homepage_sections')) {
            Schema::create('homepage_sections', function (Blueprint $table) {
                $table->id();

                // ข้อมูลพื้นฐาน
                $table->string('name')->comment('ชื่อ section สำหรับแสดงใน admin');
                $table->string('type')->default('custom')->comment('ประเภท: hero, features, stats, testimonials, cta, gallery, custom');
                $table->integer('order')->default(0)->comment('ลำดับการแสดงผล');
                $table->boolean('is_active')->default(true)->comment('เปิด/ปิดการแสดงผล');
                $table->boolean('is_fullwidth')->default(true)->comment('แสดงเต็มความกว้างหรือไม่');

                // การตั้งค่า Layout
                $table->string('min_height')->default('400px')->comment('ความสูงขั้นต่ำ');
                $table->string('max_height')->nullable()->comment('ความสูงสูงสุด (nullable = auto)');
                $table->string('padding')->default('60px 20px')->comment('padding ของ section');

                // พื้นหลัง
                $table->string('background_type')->default('color')->comment('ประเภทพื้นหลัง: color, gradient, image, video');
                $table->string('background_color')->default('#ffffff')->comment('สีพื้นหลัง');
                $table->string('background_color_dark')->default('#1f2937')->comment('สีพื้นหลัง Dark Mode');
                $table->string('background_gradient')->nullable()->comment('Gradient CSS');
                $table->string('background_image')->nullable()->comment('URL รูปพื้นหลัง');
                $table->string('background_video')->nullable()->comment('URL วิดีโอพื้นหลัง');
                $table->string('background_overlay')->nullable()->comment('Overlay color with opacity');
                $table->string('background_position')->default('center center')->comment('ตำแหน่งพื้นหลัง');
                $table->string('background_size')->default('cover')->comment('ขนาดพื้นหลัง: cover, contain, auto');
                $table->boolean('background_fixed')->default(false)->comment('พื้นหลังแบบ parallax');

                // Animation
                $table->string('animation')->nullable()->comment('Animation เมื่อ scroll เข้ามา');
                $table->integer('animation_delay')->default(0)->comment('Delay ก่อนเริ่ม animation (ms)');

                // JSON settings สำหรับข้อมูลเพิ่มเติม
                $table->json('settings')->nullable()->comment('การตั้งค่าเพิ่มเติมในรูปแบบ JSON');
                $table->json('responsive_settings')->nullable()->comment('การตั้งค่าสำหรับแต่ละ breakpoint');

                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index('order');
                $table->index('is_active');
                $table->index('type');
            });
        }

        // ตาราง homepage_elements - elements ภายใน section
        if (! Schema::hasTable('homepage_elements')) {
            Schema::create('homepage_elements', function (Blueprint $table) {
                $table->id();

                // ความสัมพันธ์กับ section
                $table->foreignId('homepage_section_id')
                    ->constrained('homepage_sections')
                    ->onDelete('cascade');

                // ข้อมูลพื้นฐาน
                $table->string('name')->nullable()->comment('ชื่อ element สำหรับ reference');
                $table->string('type')->comment('ประเภท: text, heading, image, button, video, icon, shape, html');
                $table->integer('order')->default(0)->comment('ลำดับ z-index');
                $table->boolean('is_active')->default(true)->comment('เปิด/ปิดการแสดงผล');

                // ตำแหน่ง (สำหรับ Absolute positioning)
                $table->string('position_type')->default('relative')->comment('relative หรือ absolute');
                $table->decimal('position_x', 10, 2)->default(0)->comment('ตำแหน่ง X (% หรือ px)');
                $table->decimal('position_y', 10, 2)->default(0)->comment('ตำแหน่ง Y (% หรือ px)');
                $table->string('position_x_unit')->default('%')->comment('หน่วย X: %, px');
                $table->string('position_y_unit')->default('%')->comment('หน่วย Y: %, px');
                $table->string('anchor_x')->default('left')->comment('จุดยึด X: left, center, right');
                $table->string('anchor_y')->default('top')->comment('จุดยึด Y: top, center, bottom');

                // ขนาด
                $table->string('width')->default('auto')->comment('ความกว้าง');
                $table->string('height')->default('auto')->comment('ความสูง');
                $table->string('max_width')->nullable()->comment('ความกว้างสูงสุด');

                // เนื้อหา (ขึ้นอยู่กับ type)
                $table->text('content')->nullable()->comment('เนื้อหาหลัก (text, html)');
                $table->string('content_en')->nullable()->comment('เนื้อหาภาษาอังกฤษ');
                $table->string('image_url')->nullable()->comment('URL รูปภาพ');
                $table->string('image_url_dark')->nullable()->comment('URL รูปภาพ Dark Mode');
                $table->string('video_url')->nullable()->comment('URL วิดีโอ');
                $table->string('link_url')->nullable()->comment('URL ปลายทางเมื่อคลิก');
                $table->string('link_target')->default('_self')->comment('_self หรือ _blank');
                $table->string('icon_class')->nullable()->comment('Class ของ icon (Font Awesome)');

                // Typography
                $table->string('font_family')->nullable()->comment('Font family');
                $table->string('font_size')->default('16px')->comment('ขนาดตัวอักษร');
                $table->string('font_weight')->default('400')->comment('น้ำหนักตัวอักษร');
                $table->string('line_height')->default('1.5')->comment('ระยะห่างบรรทัด');
                $table->string('letter_spacing')->default('normal')->comment('ระยะห่างตัวอักษร');
                $table->string('text_align')->default('left')->comment('การจัดตำแหน่งข้อความ');
                $table->string('text_transform')->default('none')->comment('uppercase, lowercase, capitalize');

                // สี
                $table->string('color')->default('#000000')->comment('สีข้อความ/สีหลัก');
                $table->string('color_dark')->default('#ffffff')->comment('สี Dark Mode');
                $table->string('background_color')->nullable()->comment('สีพื้นหลัง');
                $table->string('background_color_dark')->nullable()->comment('สีพื้นหลัง Dark Mode');
                $table->string('border_color')->nullable()->comment('สีขอบ');
                $table->string('border_color_dark')->nullable()->comment('สีขอบ Dark Mode');

                // Border & Radius
                $table->string('border_width')->default('0')->comment('ความหนาขอบ');
                $table->string('border_style')->default('solid')->comment('รูปแบบขอบ');
                $table->string('border_radius')->default('0')->comment('ความโค้งมน');

                // Spacing
                $table->string('padding')->default('0')->comment('padding');
                $table->string('margin')->default('0')->comment('margin');

                // Shadow
                $table->string('box_shadow')->nullable()->comment('เงา');
                $table->string('text_shadow')->nullable()->comment('เงาข้อความ');

                // Animation
                $table->string('animation')->nullable()->comment('Animation');
                $table->integer('animation_delay')->default(0)->comment('Delay animation (ms)');
                $table->integer('animation_duration')->default(500)->comment('Duration animation (ms)');

                // Hover effects
                $table->json('hover_effects')->nullable()->comment('Effects เมื่อ hover');

                // Responsive
                $table->json('responsive_settings')->nullable()->comment('การตั้งค่าสำหรับแต่ละ breakpoint');

                // Additional settings
                $table->json('settings')->nullable()->comment('การตั้งค่าเพิ่มเติม');

                $table->timestamps();
                $table->softDeletes();

                // Indexes
                $table->index(['homepage_section_id', 'order'], 'hp_elem_section_order_idx');
                $table->index('type');
                $table->index('is_active');
            });
        }

        // ตาราง homepage_templates - เทมเพลตสำเร็จรูป
        if (! Schema::hasTable('homepage_templates')) {
            Schema::create('homepage_templates', function (Blueprint $table) {
                $table->id();

                $table->string('name')->comment('ชื่อเทมเพลต');
                $table->string('slug')->unique()->comment('Slug สำหรับ reference');
                $table->string('category')->default('general')->comment('หมวดหมู่');
                $table->text('description')->nullable()->comment('คำอธิบาย');
                $table->string('thumbnail')->nullable()->comment('รูปตัวอย่าง');
                $table->json('sections_data')->nullable()->comment('ข้อมูล sections และ elements');
                $table->boolean('is_premium')->default(false)->comment('เป็นเทมเพลต Premium หรือไม่');
                $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหรือไม่');
                $table->integer('usage_count')->default(0)->comment('จำนวนครั้งที่ถูกใช้');

                $table->timestamps();
                $table->softDeletes();

                $table->index('category');
                $table->index('is_active');
            });
        }
    }

    /**
     * ลบตารางที่สร้างขึ้น
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_elements');
        Schema::dropIfExists('homepage_sections');
        Schema::dropIfExists('homepage_templates');
    }
};
