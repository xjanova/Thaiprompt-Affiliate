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
        Schema::create('video_coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', [
                'earned_video',      // ได้จากดูวิดีโอ
                'earned_quest',      // ได้จากภาระกิจ
                'earned_level_up',   // ได้จากขึ้นระดับ
                'earned_referral',   // ได้จากชวนเพื่อน
                'earned_bonus',      // ได้จากโบนัส
                'spent_exchange',    // ใช้ไปแลกเงิน
                'spent_shop',        // ใช้ไปซื้อของในร้าน
                'adjustment',        // ปรับยอดโดย admin
            ]);
            $table->decimal('amount', 15, 2); // จำนวน (+ หรือ -)
            $table->decimal('balance_before', 15, 2); // ยอดก่อนทำรายการ
            $table->decimal('balance_after', 15, 2); // ยอดหลังทำรายการ
            $table->string('reference_type')->nullable(); // ประเภทการอ้างอิง (video_content, video_quest, etc.)
            $table->unsignedBigInteger('reference_id')->nullable(); // ID ที่อ้างอิง
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_coin_transactions');
    }
};
