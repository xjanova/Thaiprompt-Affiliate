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
        Schema::create('store_themes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('preview_image')->nullable();
            $table->json('screenshots')->nullable();

            // Theme Configuration
            $table->json('color_scheme')->nullable(); // Primary, secondary, accent colors
            $table->json('typography')->nullable(); // Font families, sizes
            $table->json('layout_options')->nullable(); // Grid, list, masonry
            $table->string('category')->default('general'); // fashion, electronics, food, hotel, etc.

            // Features
            $table->boolean('has_custom_header')->default(true);
            $table->boolean('has_custom_footer')->default(true);
            $table->boolean('has_slider')->default(false);
            $table->boolean('has_product_filters')->default(true);
            $table->boolean('has_mega_menu')->default(false);
            $table->boolean('is_responsive')->default(true);

            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_premium')->default(false);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('popularity_score')->default(0);

            $table->timestamps();
        });

        Schema::create('vendor_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->onDelete('cascade');
            $table->foreignId('store_theme_id')->nullable()->constrained('store_themes')->onDelete('set null');

            // Custom Colors
            $table->string('primary_color')->default('#3490dc');
            $table->string('secondary_color')->default('#ffed4e');
            $table->string('accent_color')->default('#38c172');
            $table->string('text_color')->default('#212529');
            $table->string('background_color')->default('#ffffff');

            // Typography
            $table->string('heading_font')->default('Poppins');
            $table->string('body_font')->default('Inter');
            $table->integer('base_font_size')->default(16);

            // Layout
            $table->string('layout_style')->default('grid'); // grid, list, masonry
            $table->integer('products_per_row')->default(4);
            $table->boolean('show_sidebar')->default(true);
            $table->string('header_style')->default('default'); // default, centered, minimal

            // Custom Banners
            $table->string('hero_banner')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->string('hero_button_text')->nullable();
            $table->string('hero_button_link')->nullable();

            // Promotional Banners
            $table->json('promotional_banners')->nullable(); // Array of banner objects

            // Custom CSS/JS
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();

            // SEO
            $table->string('favicon')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_mobile')->nullable();

            // Social Media
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('line_url')->nullable();
            $table->string('youtube_url')->nullable();

            // Contact Widget
            $table->boolean('show_contact_widget')->default(true);
            $table->string('contact_widget_position')->default('bottom-right'); // bottom-right, bottom-left, etc.

            // Footer
            $table->text('footer_about')->nullable();
            $table->json('footer_links')->nullable(); // Custom footer menu links

            $table->boolean('is_active')->default(true);
            $table->timestamp('activated_at')->nullable();

            $table->timestamps();

            $table->unique('vendor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_themes');
        Schema::dropIfExists('store_themes');
    }
};
