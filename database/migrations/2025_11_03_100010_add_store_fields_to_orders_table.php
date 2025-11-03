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
        Schema::table('orders', function (Blueprint $table) {
            // Add store_id after user_id
            $table->foreignId('store_id')->nullable()->after('user_id')
                ->constrained('vendor_stores')->onDelete('set null');

            // Add store commission and earning fields after total_amount
            $table->decimal('store_commission', 10, 2)->default(0)->after('total_amount')
                ->comment('Commission for the store owner');
            $table->decimal('store_earning', 10, 2)->default(0)->after('store_commission')
                ->comment('Net earning for the store after platform commission');

            // Add index
            $table->index(['store_id', 'status'], 'idx_store_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropIndex('idx_store_status');
            $table->dropColumn([
                'store_id',
                'store_commission',
                'store_earning'
            ]);
        });
    }
};
