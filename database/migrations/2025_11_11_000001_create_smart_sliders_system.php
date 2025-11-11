<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Smart Slider Pro System - Complete Database Schema
     */
    public function up(): void
    {
        // Main Sliders Table
        Schema::create('smart_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias')->unique()->nullable();
            $table->text('description')->nullable();

            // Slider Type: simple, block, carousel, showcase
            $table->enum('type', ['simple', 'block', 'carousel', 'showcase'])->default('simple');

            // Size Settings
            $table->integer('width')->default(1200);
            $table->integer('height')->default(600);
            $table->enum('responsive_mode', ['auto', 'fullwidth', 'boxed', 'fullpage'])->default('auto');

            // Layout Settings (JSON)
            // - slide_duration, animation_duration, animation_type
            $table->json('settings')->nullable();

            // Controls Settings (JSON)
            // - arrows, bullets, autoplay, thumbnails, bar
            $table->json('controls')->nullable();

            // Animation Settings (JSON)
            // - slide_animation, background_animation, layer_animation
            $table->json('animations')->nullable();

            // Status
            $table->boolean('is_published')->default(false);
            $table->integer('order')->default(0);

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_published');
            $table->index('order');
        });

        // Slides Table
        Schema::create('smart_slides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slider_id')->constrained('smart_sliders')->onDelete('cascade');

            // Basic Info
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Background Settings (JSON)
            // - type: image, video, color, gradient
            // - image: path
            // - video: url or path
            // - color: hex
            // - gradient: array
            // - animation: type and settings
            $table->json('background')->nullable();

            // Slide Link
            $table->string('link_url')->nullable();
            $table->enum('link_target', ['_self', '_blank'])->default('_self');

            // Thumbnail
            $table->string('thumbnail')->nullable();

            // Slide Duration Override (seconds)
            $table->integer('duration')->nullable();

            // Order
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['slider_id', 'order']);
            $table->index('is_active');
        });

        // Layers Table (Smart Slider 3 style)
        Schema::create('smart_slide_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slide_id')->constrained('smart_slides')->onDelete('cascade');

            // Layer Type: heading, text, image, button, video_youtube, video_vimeo, html
            $table->enum('type', [
                'heading',
                'text',
                'image',
                'button',
                'video_youtube',
                'video_vimeo',
                'video_upload',
                'html'
            ]);

            // Content
            $table->text('content')->nullable();

            // Positioning (JSON)
            // - mode: default (flex), absolute
            // - x, y, width, height (for absolute)
            // - align, justify (for default)
            $table->json('position')->nullable();

            // Style Settings (JSON)
            // - font family, size, weight, color
            // - background, padding, margin, border, shadow
            $table->json('style')->nullable();

            // Responsive Settings (JSON)
            // - desktop, tablet, mobile visibility
            // - responsive font sizes
            $table->json('responsive')->nullable();

            // Animation Settings (JSON)
            // - animation_in, animation_out
            // - delay, duration, easing
            $table->json('animation')->nullable();

            // Link
            $table->string('link_url')->nullable();
            $table->enum('link_target', ['_self', '_blank'])->default('_self');

            // Parent Layer (for nesting)
            $table->foreignId('parent_id')->nullable()->constrained('smart_slide_layers')->onDelete('cascade');

            // Order
            $table->integer('order')->default(0);
            $table->boolean('is_visible')->default(true);

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index(['slide_id', 'order']);
            $table->index('parent_id');
        });

        // Slider Templates
        Schema::create('smart_slider_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('category')->default('general'); // business, portfolio, ecommerce, etc.

            // Template Data (JSON) - Complete slider configuration
            $table->json('template_data');

            // Metadata
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('downloads')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('category');
            $table->index('is_active');
        });

        // Slider Analytics
        Schema::create('smart_slider_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('slider_id')->constrained('smart_sliders')->onDelete('cascade');
            $table->foreignId('slide_id')->nullable()->constrained('smart_slides')->onDelete('cascade');

            // Event Type: view, click, slide_change
            $table->enum('event_type', ['view', 'click', 'slide_change']);

            // User Info
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();

            // Session
            $table->string('session_id')->nullable();

            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index(['slider_id', 'event_type', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_slider_analytics');
        Schema::dropIfExists('smart_slider_templates');
        Schema::dropIfExists('smart_slide_layers');
        Schema::dropIfExists('smart_slides');
        Schema::dropIfExists('smart_sliders');
    }
};
