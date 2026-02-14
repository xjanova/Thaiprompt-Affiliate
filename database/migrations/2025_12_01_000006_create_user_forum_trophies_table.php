<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * สร้างตาราง user_forum_trophies (pivot table)
     * เก็บโทรฟี่ที่ผู้ใช้ได้รับ
     */
    public function up(): void
    {
        // ตรวจสอบว่าตารางมีอยู่แล้วหรือไม่
        if (Schema::hasTable('user_forum_trophies')) {
            return;
        }

        Schema::create('user_forum_trophies', function (Blueprint $table) {
            $table->id();

            // ความสัมพันธ์
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('trophy_id')
                ->constrained('forum_trophies')
                ->onDelete('cascade');

            // ข้อมูลการมอบรางวัล
            $table->timestamp('awarded_at')->useCurrent();  // วันที่ได้รับ

            // ผู้มอบ (null = ระบบให้อัตโนมัติ)
            $table->foreignId('awarded_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->text('reason')->nullable();             // เหตุผลที่ได้รับ

            // Unique constraint - ผู้ใช้ได้รับโทรฟี่แต่ละประเภทได้ครั้งเดียว
            $table->unique(['user_id', 'trophy_id'], 'user_trophy_unique');

            // Indexes
            $table->index('user_id');
            $table->index('trophy_id');
            $table->index('awarded_at');
        });
    }

    /**
     * ลบตาราง user_forum_trophies
     */
    public function down(): void
    {
        Schema::dropIfExists('user_forum_trophies');
    }
};
