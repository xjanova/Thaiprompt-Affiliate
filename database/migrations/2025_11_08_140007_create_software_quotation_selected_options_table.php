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
        Schema::create('software_quotation_selected_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('software_quotation_item_id')->constrained('software_quotation_items')->cascadeOnDelete();
            $table->foreignId('software_product_option_id')->nullable()->constrained('software_product_options')->nullOnDelete();
            $table->foreignId('software_product_option_value_id')->nullable()->constrained('software_product_option_values')->nullOnDelete();

            $table->string('option_name');
            $table->string('option_value');
            $table->string('option_display_label');
            $table->decimal('price_modifier', 12, 2)->default(0);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            $table->index('software_quotation_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_quotation_selected_options');
    }
};
