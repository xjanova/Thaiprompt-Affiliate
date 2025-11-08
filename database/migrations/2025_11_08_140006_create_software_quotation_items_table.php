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
        Schema::create('software_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_quotation_id')->constrained('software_quotations')->cascadeOnDelete();
            $table->foreignId('software_product_id')->nullable()->constrained('software_products')->nullOnDelete();

            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->json('selected_options')->nullable(); // Store selected options as JSON
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('software_quotation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_quotation_items');
    }
};
