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
        Schema::create('mlm_product_pv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('mlm_plan_id')->nullable()->constrained()->onDelete('cascade');

            // PV Configuration
            $table->decimal('pv_value', 10, 2); // PV for this product
            $table->boolean('use_global_rate')->default(false); // Use plan's global rate
            $table->decimal('custom_commission_per_pv', 10, 2)->nullable(); // Override global rate

            // Display in E-commerce
            $table->boolean('show_pv_on_product_page')->default(true);
            $table->boolean('show_commission_preview')->default(true);
            $table->text('pv_description')->nullable(); // Custom text to show
            $table->text('pv_description_th')->nullable();

            // Qualification
            $table->boolean('requires_qualification')->default(false);
            $table->json('qualification_rules')->nullable(); // Min PV, Rank, etc.

            $table->timestamps();

            // Indexes
            $table->index('product_id');
            $table->index(['mlm_plan_id', 'product_id']);
            $table->unique(['product_id', 'mlm_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_product_pv');
    }
};
