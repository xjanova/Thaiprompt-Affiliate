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
        Schema::create('video_coins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0); // ยอดเหรียญคงเหลือ
            $table->decimal('lifetime_earned', 15, 2)->default(0); // เหรียญที่ได้รับทั้งหมดตลอดชีพ
            $table->decimal('lifetime_spent', 15, 2)->default(0); // เหรียญที่ใช้ไปทั้งหมด
            $table->decimal('lifetime_exchanged', 15, 2)->default(0); // เหรียญที่แลกเป็นเงินแล้ว
            $table->timestamp('last_earned_at')->nullable();
            $table->timestamp('last_spent_at')->nullable();
            $table->timestamp('last_exchanged_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_coins');
    }
};
