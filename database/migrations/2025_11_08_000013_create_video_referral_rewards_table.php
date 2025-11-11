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
        Schema::create('video_referral_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referrer_id')->constrained('users')->onDelete('cascade'); // ผู้ชวน
            $table->foreignId('referred_id')->constrained('users')->onDelete('cascade'); // ผู้ถูกชวน
            $table->enum('reward_type', [
                'signup',            // รางวัลเมื่อสมัคร
                'first_video',       // รางวัลเมื่อดูวิดีโอแรก
                'video_milestone',   // รางวัลเมื่อดูครบ X วิดีโอ
                'quest_complete',    // รางวัลเมื่อทำภาระกิจสำเร็จ
                'level_up',          // รางวัลเมื่อขึ้นระดับ
                'ongoing',           // รางวัลต่อเนื่อง (% จากที่เพื่อนได้)
            ]);
            $table->decimal('coins_amount', 10, 2)->default(0);
            $table->integer('exp_amount')->default(0);
            $table->decimal('money_amount', 10, 2)->default(0);
            $table->decimal('percentage', 5, 2)->nullable(); // % ของรางวัลที่เพื่อนได้ (สำหรับ ongoing)
            $table->integer('milestone_value')->nullable(); // เช่น ดูครบ 10 วิดีโอ
            $table->boolean('claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->string('reference_type')->nullable(); // video_content, video_quest, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index('referrer_id');
            $table->index('referred_id');
            $table->index('reward_type');
            $table->index('claimed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_referral_rewards');
    }
};
