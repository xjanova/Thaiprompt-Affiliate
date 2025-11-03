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
        Schema::dropIfExists('vendor_stores');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::create('vendor_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained('vendor_packages')->onDelete('set null');

            // Store Information
            $table->string('store_name');
            $table->string('store_slug')->unique();
            $table->text('store_description')->nullable();
            $table->string('store_logo', 500)->nullable();
            $table->string('store_banner', 500)->nullable();
            $table->string('store_domain')->nullable();

            // Contact & Address
            $table->string('store_email')->nullable();
            $table->string('store_phone', 50)->nullable();
            $table->text('store_address')->nullable();
            $table->string('store_city', 100)->nullable();
            $table->string('store_state', 100)->nullable();
            $table->string('store_postal_code', 20)->nullable();
            $table->string('store_country', 100)->default('Thailand');

            // Business Information
            $table->enum('business_type', ['individual', 'company'])->default('individual');
            $table->string('tax_id', 50)->nullable();
            $table->string('company_name')->nullable();

            // Branding
            $table->string('primary_color', 7)->default('#3B82F6');
            $table->string('secondary_color', 7)->default('#10B981');
            $table->string('store_theme', 50)->default('default');
            $table->text('custom_css')->nullable();

            // Social Media
            $table->string('facebook_url', 500)->nullable();
            $table->string('line_oa_id')->nullable();
            $table->string('instagram_url', 500)->nullable();
            $table->string('twitter_url', 500)->nullable();
            $table->string('tiktok_url', 500)->nullable();

            // Settings
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('free_shipping_threshold', 10, 2)->nullable();
            $table->boolean('enable_cod')->default(false);
            $table->boolean('enable_reviews')->default(true);
            $table->boolean('auto_approve_orders')->default(false);

            // Analytics
            $table->integer('total_products')->default(0);
            $table->integer('total_orders')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_revenue', 15, 2)->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->integer('rating_count')->default(0);

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->enum('status', ['pending', 'active', 'suspended', 'closed'])->default('pending');
            $table->text('suspension_reason')->nullable();

            // Subscription
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'cancelled', 'trial'])->default('trial');
            $table->timestamp('trial_ends_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->index(['store_slug']);
            $table->index(['status', 'is_active']);
            $table->index(['subscription_status', 'subscription_expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_stores');
    }
};
