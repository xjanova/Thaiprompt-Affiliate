<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง game_missions สำหรับเก็บภารกิจและเควสต์ของเกม
     *
     * @return void
     */
    public function up(): void
    {
        // ✅ CRITICAL: ตรวจสอบตารางก่อนสร้างเสมอ
        if (Schema::hasTable('game_missions')) {
            return;
        }

        Schema::create('game_missions', function (Blueprint $table) {
            $table->id();

            // Foreign keys with constraints
            $table->foreignId('game_id')
                ->constrained('games')
                ->onDelete('cascade')
                ->comment('รหัสเกมที่เชื่อมโยง');

            // Mission identification
            $table->string('slug')->unique()->comment('รหัสเฉพาะของภารกิจ');
            $table->string('name')->comment('ชื่อภารกิจ');
            $table->text('description')->nullable()->comment('คำอธิบายภารกิจ');

            // Mission type and objective
            $table->string('type')->comment('ประเภท: daily, weekly, special');
            $table->string('objective')->comment('วัตถุประสงค์: play_games, reach_score, survive_time, kill_count');
            $table->integer('target')->comment('เป้าหมายที่ต้องบรรลุ');
            $table->json('requirements')->nullable()->comment('เงื่อนไขเพิ่มเติม (JSON)');

            // Rewards
            $table->string('reward_type')->comment('ประเภทรางวัล: coins, points, items');
            $table->integer('reward_amount')->default(0)->comment('จำนวนรางวัลเงิน/เหรียญ');
            $table->string('reward_item')->nullable()->comment('ไอเทมรางวัล');
            $table->integer('reward_points')->default(0)->comment('คะแนนรางวัล');

            // Mission properties
            $table->integer('difficulty')->default(1)->comment('ระดับความยาก 1-5');
            $table->boolean('is_active')->default(true)->comment('สถานะเปิดใช้งาน');

            // Availability period
            $table->date('available_from')->nullable()->comment('วันที่เริ่มให้ทำภารกิจ');
            $table->date('available_until')->nullable()->comment('วันที่สิ้นสุดภารกิจ');

            // Timestamps
            $table->timestamps();

            // Indexes
            $table->index('game_id', 'game_missions_game_id_idx');
            $table->index('type', 'game_missions_type_idx');
            $table->index('is_active', 'game_missions_is_active_idx');
            $table->index(['game_id', 'type'], 'game_missions_game_type_idx');
        });
    }

    /**
     * ลบตาราง game_missions
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('game_missions');
    }
};
