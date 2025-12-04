<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง store_layout_settings
     *
     * เก็บการตั้งค่า layout ของร้านค้าผู้เช่า (Seller Store)
     * รองรับ: สี, แบนเนอร์, สไลด์, sections, และ custom styles
     *
     * @return void
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('store_layout_settings')) {
            return;
        }

        Schema::create('store_layout_settings', function (Blueprint $table) {
            $table->id();

            // เชื่อมกับ User (Seller)
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // === Theme Colors ===
            $table->string('primary_color', 7)->default('#6366f1'); // Indigo
            $table->string('secondary_color', 7)->default('#8b5cf6'); // Purple
            $table->string('accent_color', 7)->default('#ec4899'); // Pink
            $table->string('text_color', 7)->default('#1f2937'); // Gray 800
            $table->string('background_color', 7)->default('#ffffff'); // White
            $table->string('header_bg_color', 7)->nullable(); // Header background
            $table->string('footer_bg_color', 7)->nullable(); // Footer background

            // === Header Section ===
            $table->string('header_style')->default('gradient'); // gradient, solid, image, transparent
            $table->string('header_image')->nullable(); // Background image path
            $table->integer('header_height')->default(200); // Height in pixels
            $table->boolean('show_store_logo')->default(true);
            $table->boolean('show_store_name')->default(true);
            $table->boolean('show_store_description')->default(true);
            $table->boolean('show_store_stats')->default(true); // Products count, rating, etc.

            // === Banner Slider ===
            $table->boolean('slider_enabled')->default(false);
            $table->json('slider_images')->nullable(); // Array of {image, link, title, subtitle}
            $table->integer('slider_autoplay_speed')->default(5000); // ms
            $table->boolean('slider_show_arrows')->default(true);
            $table->boolean('slider_show_dots')->default(true);
            $table->string('slider_effect')->default('slide'); // slide, fade, cube

            // === Layout Style ===
            $table->string('layout_style')->default('modern'); // modern, classic, minimal, bold
            $table->string('product_card_style')->default('default'); // default, minimal, detailed
            $table->integer('products_per_row')->default(4); // 2, 3, 4, 5, 6
            $table->string('sidebar_position')->default('left'); // left, right, none
            $table->boolean('show_sidebar')->default(true);

            // === Sections Order & Visibility ===
            $table->json('sections_order')->nullable(); // Array of section names in order
            $table->json('sections_visibility')->nullable(); // {section_name: boolean}

            // === Featured Section ===
            $table->boolean('show_featured_products')->default(true);
            $table->string('featured_title')->default('สินค้าแนะนำ');
            $table->integer('featured_products_count')->default(8);

            // === Categories Section ===
            $table->boolean('show_categories')->default(true);
            $table->string('categories_title')->default('หมวดหมู่สินค้า');
            $table->string('categories_style')->default('grid'); // grid, list, carousel

            // === Promotions Banner ===
            $table->boolean('show_promotion_banner')->default(false);
            $table->string('promotion_image')->nullable();
            $table->string('promotion_link')->nullable();
            $table->string('promotion_text')->nullable();

            // === Social Links ===
            $table->json('social_links')->nullable(); // {facebook, line, instagram, etc.}

            // === Custom CSS/JS ===
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();

            // === Footer ===
            $table->boolean('show_footer')->default(true);
            $table->text('footer_content')->nullable(); // Custom HTML content
            $table->boolean('show_contact_info')->default(true);
            $table->boolean('show_social_links')->default(true);

            // === SEO ===
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            // === Status ===
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique('user_id', 'store_layout_user_unique');
            $table->index('is_published');
        });
    }

    /**
     * ลบตาราง store_layout_settings
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('store_layout_settings');
    }
};
