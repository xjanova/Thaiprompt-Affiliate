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
        Schema::create('mlm_pv_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('mlm_plan_id')->constrained()->onDelete('cascade');

            // Transaction Details
            $table->string('transaction_type'); // 'purchase', 'bonus', 'adjustment', 'deduction'
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // PV Details
            $table->decimal('pv_amount', 15, 2);
            $table->decimal('sales_amount', 15, 2)->default(0);
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->decimal('new_balance', 15, 2)->default(0);

            // Binary Leg Attribution
            $table->enum('attributed_leg', ['left', 'right', 'personal'])->nullable();

            // Description
            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            // Admin tracking
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['mlm_member_id', 'created_at']);
            $table->index(['mlm_plan_id', 'transaction_type']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_pv_transactions');
    }
};
