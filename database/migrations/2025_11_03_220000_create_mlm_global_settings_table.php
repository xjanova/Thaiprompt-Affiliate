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
        Schema::create('mlm_global_settings', function (Blueprint $table) {
            $table->id();

            // Basic Settings
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, decimal, boolean, json, array
            $table->string('group')->default('general'); // general, unilevel, binary, commission, rollup
            $table->text('description')->nullable();
            $table->text('description_th')->nullable();

            // Metadata
            $table->integer('sort_order')->default(0);
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_visible')->default(true);

            $table->timestamps();

            $table->index(['group', 'sort_order']);
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_global_settings');
    }
};
