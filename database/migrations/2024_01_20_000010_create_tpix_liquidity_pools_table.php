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
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('tpix_liquidity_pools')) {
            return;
        }

        Schema::create('tpix_liquidity_pools', function (Blueprint $table) {
            $table->id();
            // ⚠️ ใช้ unsignedBigInteger แทน foreignId()->constrained() เพราะ tpix_tokens อาจยังไม่มี
            $table->unsignedBigInteger('token_a_id');
            $table->unsignedBigInteger('token_b_id');

            // Pool reserves
            $table->decimal('reserve_a', 30, 8)->default(0);
            $table->decimal('reserve_b', 30, 8)->default(0);
            $table->decimal('total_liquidity', 30, 8)->default(0);

            // Pool settings
            $table->decimal('fee_percentage', 5, 3)->default(0.3); // 0.3% default fee

            // Pool statistics
            $table->decimal('volume_24h', 30, 8)->default(0);
            $table->decimal('volume_7d', 30, 8)->default(0);
            $table->decimal('fees_collected', 30, 8)->default(0);
            $table->integer('tx_count')->default(0);

            // Pool status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_trade_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['token_a_id', 'token_b_id']);
            $table->index('is_active');
            $table->index('volume_24h');
            $table->index('token_a_id');
            $table->index('token_b_id');
        });

        // เพิ่ม foreign keys ถ้า tpix_tokens มีอยู่แล้ว
        if (Schema::hasTable('tpix_tokens')) {
            Schema::table('tpix_liquidity_pools', function (Blueprint $table) {
                $table->foreign('token_a_id')->references('id')->on('tpix_tokens')->onDelete('cascade');
                $table->foreign('token_b_id')->references('id')->on('tpix_tokens')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpix_liquidity_pools');
    }
};
