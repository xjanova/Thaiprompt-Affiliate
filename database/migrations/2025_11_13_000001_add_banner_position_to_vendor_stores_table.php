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
        Schema::table('vendor_stores', function (Blueprint $table) {
            $table->integer('banner_position_y')->default(0)->after('store_banner');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_stores', function (Blueprint $table) {
            $table->dropColumn('banner_position_y');
        });
    }
};
