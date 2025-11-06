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
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_device_id')->constrained('pos_devices')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // cashier
            $table->string('session_code')->unique();

            // Session Info
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->enum('status', ['open', 'closed', 'forced_close'])->default('open');

            // Cash Management
            $table->decimal('opening_cash', 15, 2)->default(0);
            $table->decimal('closing_cash', 15, 2)->nullable();
            $table->decimal('expected_cash', 15, 2)->nullable();
            $table->decimal('cash_difference', 15, 2)->nullable(); // ส่วนต่างเงินสด

            // Transaction Summary
            $table->integer('total_transactions')->default(0);
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->decimal('total_cash_sales', 15, 2)->default(0);
            $table->decimal('total_card_sales', 15, 2)->default(0);
            $table->decimal('total_other_sales', 15, 2)->default(0);
            $table->integer('total_refunds')->default(0);
            $table->decimal('total_refund_amount', 15, 2)->default(0);

            // Notes
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('pos_device_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
