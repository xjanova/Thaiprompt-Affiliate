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
        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->unique()->constrained('vendor_stores')->cascadeOnDelete();

            // General Settings
            $table->string('store_display_name')->nullable();
            $table->string('receipt_header')->nullable();
            $table->string('receipt_footer')->nullable();
            $table->string('receipt_logo')->nullable();
            $table->boolean('print_logo_on_receipt')->default(true);

            // Tax Settings
            $table->boolean('tax_enabled')->default(true);
            $table->decimal('tax_percentage', 5, 2)->default(7.00); // VAT 7%
            $table->boolean('tax_inclusive')->default(true);
            $table->string('tax_id_number')->nullable();

            // Service Charge
            $table->boolean('service_charge_enabled')->default(false);
            $table->decimal('service_charge_percentage', 5, 2)->default(0);

            // Discount Settings
            $table->boolean('allow_discounts')->default(true);
            $table->decimal('max_discount_percentage', 5, 2)->default(100);
            $table->boolean('require_manager_approval')->default(false);
            $table->decimal('manager_approval_threshold', 5, 2)->default(20); // >20% needs approval

            // Payment Settings
            $table->json('enabled_payment_methods')->nullable(); // ['cash', 'card', 'qr', 'e-wallet']
            $table->boolean('allow_credit_sales')->default(false);
            $table->boolean('allow_partial_payments')->default(false);

            // Receipt Settings
            $table->boolean('auto_print_receipt')->default(true);
            $table->enum('receipt_size', ['58mm', '80mm'])->default('80mm');
            $table->integer('receipt_copies')->default(1);
            $table->boolean('print_customer_copy')->default(false);

            // Display Settings
            $table->boolean('dual_screen_enabled')->default(true);
            $table->boolean('show_product_images')->default(true);
            $table->boolean('show_stock_levels')->default(true);
            $table->string('theme_color')->default('#4F46E5'); // Indigo

            // Offline Mode Settings
            $table->boolean('offline_mode_enabled')->default(true);
            $table->integer('max_offline_transactions')->default(1000);
            $table->boolean('auto_sync_when_online')->default(true);

            // Advertisement Settings
            $table->boolean('customer_display_ads_enabled')->default(true);
            $table->integer('ad_rotation_seconds')->default(10);
            $table->boolean('show_promotions')->default(true);

            // Inventory Settings
            $table->boolean('real_time_stock_sync')->default(true);
            $table->boolean('low_stock_warning')->default(true);
            $table->integer('low_stock_threshold')->default(10);
            $table->boolean('prevent_negative_stock')->default(true);

            // Session Settings
            $table->boolean('require_cash_management')->default(true);
            $table->boolean('require_opening_cash')->default(true);
            $table->boolean('require_closing_cash')->default(true);

            // Customer Settings
            $table->boolean('capture_customer_info')->default(false);
            $table->boolean('require_customer_phone')->default(false);
            $table->boolean('loyalty_points_enabled')->default(false);

            // Security
            $table->boolean('require_pin_for_void')->default(true);
            $table->boolean('require_pin_for_refund')->default(true);
            $table->boolean('require_pin_for_discount')->default(false);

            // Advanced Settings
            $table->json('custom_fields')->nullable();
            $table->json('keyboard_shortcuts')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_settings');
    }
};
