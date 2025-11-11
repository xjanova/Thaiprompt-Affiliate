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
        Schema::create('video_levels', function (Blueprint $table) {
            $table->id();
            $table->integer('level')->unique(); // ระดับ
            $table->string('name'); // ชื่อระดับ
            $table->string('icon')->nullable(); // ไอคอนระดับ
            $table->integer('required_exp'); // ประสบการณ์ที่ต้องการ
            $table->integer('max_daily_quests')->default(3); // ภาระกิจสูงสุดต่อวัน
            $table->integer('max_weekly_quests')->default(10); // ภาระกิจสูงสุดต่อสัปดาห์
            $table->decimal('reward_multiplier', 5, 2)->default(1.00); // ตัวคูณรางวัล (1.5 = +50%)
            $table->decimal('coin_bonus', 10, 2)->default(0); // โบนัสเหรียญเมื่อขึ้นระดับ
            $table->json('perks')->nullable(); // สิทธิพิเศษต่างๆ
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_levels');
    }
};
