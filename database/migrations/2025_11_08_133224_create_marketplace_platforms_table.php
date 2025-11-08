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
        if (Schema::hasTable('marketplace_platforms')) {
            return;
        }

        Schema::create('marketplace_platforms', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->string('logo_url')->nullable();
            $table->string('api_documentation_url')->nullable();
            $table->boolean('is_active')->default(true);

            // API Configuration
            $table->boolean('requires_app_key')->default(true);
            $table->boolean('requires_app_secret')->default(true);
            $table->boolean('requires_access_token')->default(false);
            $table->boolean('requires_shop_id')->default(false);
            $table->json('additional_fields')->nullable();

            // Commission Settings
            $table->decimal('default_commission_rate', 5, 2)->default(0);
            $table->decimal('min_commission_rate', 5, 2)->default(0);
            $table->decimal('max_commission_rate', 5, 2)->default(100);

            // Features
            $table->boolean('supports_product_sync')->default(true);
            $table->boolean('supports_order_sync')->default(true);
            $table->boolean('supports_real_time_webhook')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_platforms');
    }
};
