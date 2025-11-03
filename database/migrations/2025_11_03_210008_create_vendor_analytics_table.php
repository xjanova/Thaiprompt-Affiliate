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
        Schema::dropIfExists('vendor_analytics');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        Schema::create('vendor_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('vendor_stores')->onDelete('cascade');

            // Date
            $table->date('date');

            // Traffic
            $table->integer('page_views')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->decimal('bounce_rate', 5, 2)->default(0);
            $table->integer('avg_session_duration')->default(0);

            // Sales
            $table->integer('orders_count')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('avg_order_value', 10, 2)->default(0);

            // Products
            $table->integer('products_viewed')->default(0);
            $table->integer('products_added_to_cart')->default(0);

            // Conversion
            $table->decimal('conversion_rate', 5, 2)->default(0);
            $table->decimal('cart_abandonment_rate', 5, 2)->default(0);

            // Revenue
            $table->decimal('gross_revenue', 15, 2)->default(0);
            $table->decimal('net_revenue', 15, 2)->default(0);
            $table->decimal('commission_paid', 15, 2)->default(0);

            $table->timestamps();

            $table->unique(['store_id', 'date']);
            $table->index(['date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_analytics');
    }
};
