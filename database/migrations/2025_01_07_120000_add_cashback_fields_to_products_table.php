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
        Schema::table('products', function (Blueprint $table) {
            // Cashback fields
            $table->decimal('customer_cashback', 10, 2)->default(0)->after('commission_rate')
                ->comment('Fixed cashback amount for customer');
            $table->decimal('cashback_percentage', 5, 2)->default(0)->after('customer_cashback')
                ->comment('Cashback percentage of price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['customer_cashback', 'cashback_percentage']);
        });
    }
};
