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
        if (Schema::hasTable('tpix_swaps')) {
            return;
        }

        Schema::create('tpix_swaps', function (Blueprint $table) {
            $table->id();
            // ⚠️ ใช้ unsignedBigInteger แทน foreignId()->constrained() เพราะบางตารางอาจยังไม่มี
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('pool_id');
            $table->unsignedBigInteger('token_in_id');
            $table->unsignedBigInteger('token_out_id');

            // Swap amounts
            $table->decimal('amount_in', 30, 8);
            $table->decimal('amount_out', 30, 8);
            $table->decimal('fee', 30, 8);

            // Price info
            $table->decimal('price_impact', 10, 4); // Percentage
            $table->decimal('price_before', 20, 8)->nullable();
            $table->decimal('price_after', 20, 8)->nullable();

            // Slippage protection
            $table->decimal('min_amount_out', 30, 8)->nullable();
            $table->decimal('slippage_tolerance', 5, 2)->nullable(); // Percentage

            // Transaction details
            $table->string('tx_hash', 66)->unique();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();

            // Blockchain data
            $table->bigInteger('block_number')->nullable();
            $table->integer('gas_used')->nullable();
            $table->decimal('gas_price_tpix', 20, 8)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('pool_id');
            $table->index('status');
            $table->index('created_at');
            $table->index('token_in_id');
            $table->index('token_out_id');
        });

        // เพิ่ม foreign keys ถ้าตารางที่อ้างอิงมีอยู่แล้ว
        if (Schema::hasTable('users')) {
            Schema::table('tpix_swaps', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('tpix_liquidity_pools')) {
            Schema::table('tpix_swaps', function (Blueprint $table) {
                $table->foreign('pool_id')->references('id')->on('tpix_liquidity_pools')->onDelete('cascade');
            });
        }
        if (Schema::hasTable('tpix_tokens')) {
            Schema::table('tpix_swaps', function (Blueprint $table) {
                $table->foreign('token_in_id')->references('id')->on('tpix_tokens')->onDelete('cascade');
                $table->foreign('token_out_id')->references('id')->on('tpix_tokens')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tpix_swaps');
    }
};
