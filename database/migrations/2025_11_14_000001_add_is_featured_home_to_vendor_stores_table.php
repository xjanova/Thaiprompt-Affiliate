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
            $table->boolean('is_featured_home')->default(false)->after('is_verified')
                ->comment('ร้านค้าที่ถูกเลือกให้แสดงในหน้าแรก (แอดมินเลือก)');
            $table->integer('featured_home_order')->nullable()->after('is_featured_home')
                ->comment('ลำดับการแสดงในหน้าแรก');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_stores', function (Blueprint $table) {
            $table->dropColumn(['is_featured_home', 'featured_home_order']);
        });
    }
};
