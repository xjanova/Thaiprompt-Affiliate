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
        Schema::create('user_video_watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('video_id')->constrained('video_contents')->onDelete('cascade');
            $table->integer('watch_time_seconds')->default(0); // เวลาที่ดูไปแล้ว
            $table->integer('total_watch_time')->default(0); // เวลารวมทั้งหมด (รวมดูซ้ำ)
            $table->decimal('watch_percentage', 5, 2)->default(0); // เปอร์เซ็นต์ที่ดู
            $table->boolean('completed')->default(false); // ดูครบตามเงื่อนไขหรือยัง
            $table->timestamp('completed_at')->nullable();
            $table->boolean('reward_claimed')->default(false); // รับรางวัลแล้วหรือยัง
            $table->timestamp('reward_claimed_at')->nullable();
            $table->decimal('coins_earned', 10, 2)->default(0);
            $table->integer('exp_earned')->default(0);
            $table->integer('watch_count')->default(0); // จำนวนครั้งที่ดู
            $table->timestamp('first_watched_at')->nullable();
            $table->timestamp('last_watched_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'video_id']);
            $table->index('user_id');
            $table->index('video_id');
            $table->index('completed');
            $table->index('reward_claimed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_video_watches');
    }
};
