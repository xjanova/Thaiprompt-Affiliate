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
        Schema::create('coin_exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->decimal('coins_amount', 10, 2); // จำนวนเหรียญ
            $table->decimal('money_amount', 10, 2); // เท่ากับเงิน (THB)
            $table->decimal('rate', 10, 4); // อัตราแลกเปลี่ยน (1 coin = X THB)
            $table->integer('min_level')->default(1); // ระดับขั้นต่ำที่แลกได้
            $table->decimal('min_coins', 10, 2)->default(100); // เหรียญขั้นต่ำที่แลกได้
            $table->decimal('max_coins_per_day', 10, 2)->nullable(); // จำกัดแลกต่อวัน
            $table->decimal('max_coins_per_month', 10, 2)->nullable(); // จำกัดแลกต่อเดือน
            $table->boolean('is_active')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_exchange_rates');
    }
};
