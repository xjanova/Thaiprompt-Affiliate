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
        Schema::create('user_video_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->foreignId('current_level_id')->constrained('video_levels')->onDelete('cascade');
            $table->integer('current_exp')->default(0); // ประสบการณ์ปัจจุบัน
            $table->integer('total_exp')->default(0); // ประสบการณ์รวมทั้งหมด
            $table->integer('videos_watched')->default(0); // วิดีโอที่ดูแล้ว
            $table->integer('quests_completed')->default(0); // ภาระกิจที่ทำสำเร็จ
            $table->decimal('total_coins_earned', 15, 2)->default(0); // เหรียญที่ได้รับทั้งหมด
            $table->timestamp('last_level_up_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('current_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_video_levels');
    }
};
