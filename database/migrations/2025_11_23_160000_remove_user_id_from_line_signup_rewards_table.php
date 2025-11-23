<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * ลบ column user_id จากตาราง line_signup_rewards
     *
     * ตาราง line_signup_rewards เป็นตาราง template รางวัล
     * ไม่ใช่รางวัลของผู้ใช้เฉพาะคน ดังนั้นไม่ควรมี user_id
     *
     * @return void
     */
    public function up(): void
    {
        // เช็คว่ามี column user_id หรือไม่
        if (Schema::hasColumn('line_signup_rewards', 'user_id')) {
            // ลบ foreign key constraint ถ้ามี (ใช้ SafeMigration trait)
            $this->safeDropForeign('line_signup_rewards', 'line_signup_rewards_user_id_foreign');

            // ลบ column
            Schema::table('line_signup_rewards', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }

    /**
     * เพิ่ม column user_id กลับ (สำหรับ rollback)
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('line_signup_rewards', function (Blueprint $table) {
            if (!Schema::hasColumn('line_signup_rewards', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->onDelete('cascade');
            }
        });
    }
};
