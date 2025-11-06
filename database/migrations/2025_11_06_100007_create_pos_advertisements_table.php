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
        Schema::create('pos_advertisements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->nullable()->constrained('vendor_stores')->cascadeOnDelete();

            // Advertisement Info
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['image', 'video', 'html', 'promotion'])->default('image');

            // Media
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->text('html_content')->nullable();

            // Promotion Details
            $table->string('promotion_code')->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->timestamp('promotion_start')->nullable();
            $table->timestamp('promotion_end')->nullable();

            // Display Settings
            $table->integer('duration_seconds')->default(10); // display time
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_idle_only')->default(false); // show only when no transaction

            // Scheduling
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('display_hours')->nullable(); // specific hours to display
            $table->json('display_days')->nullable(); // specific days to display

            // Target Audience
            $table->json('target_devices')->nullable(); // specific POS devices
            $table->json('target_conditions')->nullable(); // conditions for display

            // Statistics
            $table->integer('view_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->timestamp('last_displayed_at')->nullable();

            // Link
            $table->string('link_url')->nullable();
            $table->boolean('link_opens_new_tab')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('store_id');
            $table->index('type');
            $table->index('is_active');
            $table->index('order');
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_advertisements');
    }
};
