<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * ลบ column session_id จากตาราง line_signup_rewards
     *
     * ตาราง line_signup_rewards เป็นตาราง template รางวัล
     * ไม่ใช่รางวัลของ session เฉพาะ ดังนั้นไม่ควรมี session_id
     *
     * @return void
     */
    public function up(): void
    {
        // เช็คว่ามี column session_id หรือไม่
        if (Schema::hasColumn('line_signup_rewards', 'session_id')) {
            // ลบ foreign key constraint ถ้ามี (ใช้ SafeMigration trait)
            $this->safeDropForeign('line_signup_rewards', 'line_signup_rewards_session_id_foreign');

            // ลบ column
            Schema::table('line_signup_rewards', function (Blueprint $table) {
                $table->dropColumn('session_id');
            });
        }
    }

    /**
     * เพิ่ม column session_id กลับ (สำหรับ rollback)
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('line_signup_rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('line_signup_rewards', 'session_id')) {
                $table->foreignId('session_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('line_signup_sessions')
                    ->onDelete('cascade');
            }
        });
    }
};
