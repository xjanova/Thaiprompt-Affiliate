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
        Schema::create('cashback_settings', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['global', 'product'])->default('global');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->enum('value_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2)->default(0); // Percentage or fixed amount
            $table->boolean('is_active')->default(true);
            $table->decimal('min_order_amount', 10, 2)->nullable(); // Minimum order amount to qualify
            $table->decimal('max_cashback_amount', 10, 2)->nullable(); // Maximum cashback cap
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Additional conditions
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['type', 'is_active']);
            $table->index('product_id');
        });

        // Add cashback tracking to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('cashback_amount', 10, 2)->default(0)->after('total_amount');
            $table->boolean('cashback_processed')->default(false)->after('cashback_amount');
            $table->timestamp('cashback_processed_at')->nullable()->after('cashback_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cashback_amount', 'cashback_processed', 'cashback_processed_at']);
        });

        Schema::dropIfExists('cashback_settings');
    }
};
