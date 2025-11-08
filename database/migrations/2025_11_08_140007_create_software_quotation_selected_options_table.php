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
        if (Schema::hasTable('software_quotation_selected_options')) {
            return;
        }

        Schema::create('software_quotation_selected_options', function (Blueprint $table) {
            $table->id();

            // Foreign key with custom constraint name to avoid MySQL 64-char limit
            $table->unsignedBigInteger('software_quotation_item_id');
            $table->foreign('software_quotation_item_id', 'sqso_sqi_id_fk')
                ->references('id')
                ->on('software_quotation_items')
                ->onDelete('cascade');

            $table->unsignedBigInteger('software_product_option_id')->nullable();
            $table->foreign('software_product_option_id', 'sqso_spo_id_fk')
                ->references('id')
                ->on('software_product_options')
                ->onDelete('set null');

            $table->unsignedBigInteger('software_product_option_value_id')->nullable();
            $table->foreign('software_product_option_value_id', 'sqso_spov_id_fk')
                ->references('id')
                ->on('software_product_option_values')
                ->onDelete('set null');

            $table->string('option_name');
            $table->string('option_value');
            $table->string('option_display_label');
            $table->decimal('price_modifier', 12, 2)->default(0);
            $table->decimal('setup_fee', 12, 2)->default(0);
            $table->decimal('monthly_fee', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->timestamps();

            // Custom index name to avoid MySQL 64-char limit
            $table->index('software_quotation_item_id', 'sqso_sqi_id_idx');
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
