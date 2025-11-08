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
        if (Schema::hasTable('marketplace_orders')) {
            return;
        }

        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('marketplace_accounts')->onDelete('cascade');
            $table->foreignId('platform_id')->constrained('marketplace_platforms')->onDelete('cascade');
            $table->foreignId('affiliate_link_id')->nullable()->constrained('marketplace_affiliate_links')->onDelete('set null');
            $table->foreignId('affiliate_user_id')->nullable()->constrained('users')->onDelete('set null');

            // Order IDs
            $table->string('external_order_id', 100);
            $table->string('order_number', 100)->nullable();

            // Customer Info (if available)
            $table->string('customer_name', 200)->nullable();
            $table->string('customer_email', 200)->nullable();
            $table->string('customer_phone', 50)->nullable();

            // Order Details
            $table->decimal('subtotal', 15, 2);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 10)->default('THB');

            // Commission
            $table->decimal('commission_rate', 5, 2)->nullable();
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('platform_fee', 15, 2)->default(0);
            $table->decimal('net_commission', 15, 2)->default(0);

            // Status
            $table->string('order_status', 50)->nullable();
            $table->string('payment_status', 50)->nullable();
            $table->string('fulfillment_status', 50)->nullable();

            // Dates
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Tracking
            $table->string('tracking_number', 100)->nullable();
            $table->string('shipping_provider', 100)->nullable();

            // Commission Status
            $table->enum('commission_status', ['pending', 'calculated', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('commission_calculated_at')->nullable();
            $table->timestamp('commission_approved_at')->nullable();
            $table->timestamp('commission_paid_at')->nullable();

            // MLM Integration
            $table->boolean('mlm_commission_distributed')->default(false);
            $table->unsignedBigInteger('mlm_distribution_id')->nullable();

            // Metadata
            $table->json('order_data')->nullable();

            // Sync
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['account_id', 'external_order_id'], 'unique_external_order');
            $table->index('affiliate_user_id');
            $table->index('commission_status');
            $table->index('ordered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
    }
};
