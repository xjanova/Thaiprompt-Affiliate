<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::dropIfExists('vendor_packages');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::create('vendor_packages', function (Blueprint $table) {
            $table->id();

            // Package Information
            $table->string('package_name', 100);
            $table->string('package_slug', 100)->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->json('features')->nullable();

            // Pricing
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('THB');

            // Limitations
            $table->integer('max_products')->nullable();
            $table->integer('max_images_per_product')->nullable()->default(10);
            $table->integer('max_categories')->nullable();
            $table->integer('max_storage_mb')->nullable();
            $table->integer('max_monthly_orders')->nullable();

            // Commission
            $table->decimal('commission_rate', 5, 2)->default(10.00);

            // Feature Flags
            $table->boolean('allow_custom_domain')->default(false);
            $table->boolean('allow_custom_theme')->default(false);
            $table->boolean('allow_api_access')->default(false);
            $table->boolean('allow_export_data')->default(false);
            $table->boolean('allow_advanced_analytics')->default(false);
            $table->boolean('allow_bulk_operations')->default(false);
            $table->boolean('allow_ai_bot')->default(false);
            $table->boolean('allow_marketing_tools')->default(false);
            $table->boolean('priority_support')->default(false);

            // Trial Settings
            $table->integer('trial_days')->default(0);

            // Display
            $table->string('badge', 50)->nullable();
            $table->string('badge_color', 7)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['package_slug']);
            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_packages');
    }
};
