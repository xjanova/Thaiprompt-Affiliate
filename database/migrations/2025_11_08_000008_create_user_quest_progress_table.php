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
        Schema::create('user_quest_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quest_id')->constrained('video_quests')->onDelete('cascade');
            $table->integer('current_progress')->default(0); // ความคืบหน้าปัจจุบัน
            $table->integer('target_value'); // เป้าหมาย (copy จาก quest)
            $table->decimal('progress_percentage', 5, 2)->default(0); // %
            $table->boolean('completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('reward_claimed_at')->nullable();
            $table->decimal('coins_earned', 10, 2)->default(0);
            $table->integer('exp_earned')->default(0);
            $table->decimal('money_earned', 10, 2)->default(0);
            $table->date('period_start')->nullable(); // วันที่เริ่มรอบ (สำหรับ daily/weekly)
            $table->date('period_end')->nullable(); // วันที่จบรอบ
            $table->json('metadata')->nullable(); // ข้อมูลเพิ่มเติม
            $table->timestamps();

            $table->unique(['user_id', 'quest_id', 'period_start']);
            $table->index('user_id');
            $table->index('quest_id');
            $table->index('completed');
            $table->index('reward_claimed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_quest_progress');
    }
};
