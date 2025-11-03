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
        Schema::create('mlm_rank_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mlm_member_id')->constrained()->onDelete('cascade');
            $table->foreignId('mlm_plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('rank_id')->constrained()->onDelete('cascade');

            // Achievement Details
            $table->timestamp('achieved_at');
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->boolean('bonus_paid')->default(false);
            $table->timestamp('bonus_paid_at')->nullable();

            // Requirements Met
            $table->json('requirements_snapshot')->nullable(); // What requirements were met

            $table->timestamps();

            // Indexes
            $table->index(['mlm_member_id', 'rank_id']);
            $table->index(['mlm_plan_id', 'achieved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mlm_rank_achievements');
    }
};
