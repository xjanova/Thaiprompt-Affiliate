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
        Schema::create('marketplace_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained('marketplace_orders')->onDelete('cascade');
            $table->foreignId('affiliate_link_id')->nullable()->constrained('marketplace_affiliate_links')->onDelete('set null');
            $table->foreignId('platform_id')->constrained('marketplace_platforms')->onDelete('cascade');

            // Commission Type
            $table->enum('commission_type', ['direct', 'mlm_unilevel', 'mlm_binary', 'bonus'])->default('direct');

            // MLM Details (if applicable)
            $table->integer('mlm_level')->nullable();
            $table->foreignId('mlm_sponsor_id')->nullable()->constrained('users')->onDelete('set null');

            // Amounts
            $table->decimal('order_amount', 15, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 15, 2);
            $table->decimal('platform_fee', 15, 2)->default(0);
            $table->decimal('net_commission', 15, 2);
            $table->string('currency', 10)->default('THB');

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejected_reason')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Payment
            $table->foreignId('wallet_transaction_id')->nullable()->constrained()->onDelete('set null');

            // Metadata
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('order_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_commissions');
    }
};
