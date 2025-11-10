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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_hotel_admin')->default(false)->after('is_super_admin');
            $table->unsignedBigInteger('managed_hotel_id')->nullable()->after('is_hotel_admin');

            $table->foreign('managed_hotel_id')
                ->references('id')
                ->on('hotels')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['managed_hotel_id']);
            $table->dropColumn(['is_hotel_admin', 'managed_hotel_id']);
        });
    }
};
