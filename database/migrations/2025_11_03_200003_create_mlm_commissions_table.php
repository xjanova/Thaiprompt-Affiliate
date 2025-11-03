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
        Schema::create('mlm_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('mlm_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Beneficiary
            $table->foreignId('from_member_id')->nullable()->constrained('mlm_members')->onDelete('set null'); // Who generated this

            // Source
            $table->string('source_type')->nullable(); // Order, Joining Fee, Bonus
            $table->unsignedBigInteger('source_id')->nullable(); // Related order/transaction ID

            // Commission Details
            $table->enum('type', [
                'unilevel_direct',
                'unilevel_indirect',
                'binary_pair',
                'binary_matching',
                'sponsor_bonus',
                'rank_bonus',
                'leadership_bonus',
                'matching_bonus',
                'pool_bonus'
            ]);
            $table->integer('level')->nullable(); // For unilevel
            $table->enum('leg', ['left', 'right'])->nullable(); // For binary

            // Amounts
            $table->decimal('pv_amount', 15, 2)->default(0); // PV that generated this commission
            $table->decimal('sales_amount', 15, 2)->default(0); // Actual sales amount
            $table->decimal('commission_amount', 15, 2); // Commission earned
            $table->decimal('percentage', 5, 2)->nullable(); // Commission %

            // Binary Specific
            $table->decimal('left_leg_pv', 15, 2)->nullable();
            $table->decimal('right_leg_pv', 15, 2)->nullable();
            $table->integer('pairs_count')->nullable();

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Payment
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['mlm_member_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['mlm_plan_id', 'created_at']);
            $table->index(['source_type', 'source_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_commissions');
    }
};
