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
        Schema::create('user_rank_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('target_rank_id')->constrained('ranks')->onDelete('cascade');

            // Progress metrics
            $table->integer('current_points')->default(0);
            $table->integer('current_referrals')->default(0);
            $table->decimal('current_sales', 12, 2)->default(0);
            $table->integer('active_referrals')->default(0);
            $table->decimal('team_sales', 12, 2)->default(0);

            // Progress percentage (0-100)
            $table->decimal('progress_percentage', 5, 2)->default(0);

            // Requirements status
            $table->json('requirements_status')->nullable(); // เก็บสถานะแต่ละ requirement

            // Achievements
            $table->integer('requirements_met')->default(0); // จำนวน requirements ที่ผ่านแล้ว
            $table->integer('total_requirements')->default(0); // จำนวน requirements ทั้งหมด

            // Dates
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamp('estimated_completion_at')->nullable(); // ประมาณการเมื่อจะถึง

            $table->timestamps();

            $table->unique(['user_id', 'target_rank_id']);
            $table->index('progress_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_rank_progress');
    }
};
