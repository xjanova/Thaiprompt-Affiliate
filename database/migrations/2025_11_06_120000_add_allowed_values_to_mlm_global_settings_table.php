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
        Schema::table('mlm_global_settings', function (Blueprint $table) {
            $table->json('allowed_values')->nullable()->after('type');
            $table->string('input_type')->default('text')->after('type'); // text, number, toggle, select, textarea, json_editor
            $table->string('placeholder')->nullable()->after('description_th');
            $table->string('unit')->nullable()->after('placeholder'); // %, บาท, PV, วัน
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mlm_global_settings', function (Blueprint $table) {
            $table->dropColumn(['allowed_values', 'input_type', 'placeholder', 'unit']);
        });
    }
};
