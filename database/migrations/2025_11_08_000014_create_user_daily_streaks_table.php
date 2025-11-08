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
        Schema::create('user_daily_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->integer('current_streak')->default(0); // จำนวนวันติดต่อกันปัจจุบัน
            $table->integer('longest_streak')->default(0); // สถิติสูงสุด
            $table->date('last_activity_date')->nullable(); // วันที่มีกิจกรรมล่าสุด
            $table->integer('total_active_days')->default(0); // จำนวนวันที่ใช้งานทั้งหมด
            $table->timestamps();

            $table->index('user_id');
            $table->index('current_streak');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_daily_streaks');
    }
};
