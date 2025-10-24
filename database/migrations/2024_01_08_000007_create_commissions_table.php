<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Who receives
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('referrer_id')->nullable()->constrained('users')->onDelete('set null'); // Who referred

            $table->enum('type', [
                'direct_sale',      // Direct sale commission
                'level_commission', // MLM level commission
                'rank_bonus',       // Rank achievement bonus
                'matching_bonus',   // Binary matching bonus
                'performance_bonus' // Performance bonus
            ]);

            $table->integer('level')->default(0); // MLM level (0 = direct sale)
            $table->decimal('amount', 10, 2);
            $table->decimal('percentage', 5, 2);
            $table->decimal('order_total', 10, 2);

            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->text('notes')->nullable();
            $table->json('calculation_details')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['order_id', 'type']);
        });

        Schema::create('commission_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('level');
            $table->decimal('percentage', 5, 2);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('level');
        });

        Schema::create('bonuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('type', [
                'rank_achievement',  // New rank achieved
                'monthly_target',    // Monthly sales target met
                'recruitment',       // Recruitment bonus
                'performance',       // Performance bonus
                'special'            // Special bonus
            ]);

            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2);

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->enum('status', ['pending', 'approved', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bonuses');
        Schema::dropIfExists('commission_settings');
        Schema::dropIfExists('commissions');
    }
};
