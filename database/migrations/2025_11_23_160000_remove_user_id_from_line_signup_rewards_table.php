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
            Schema::table('line_signup_rewards', function (Blueprint $table) {
                // ลบ foreign key constraint ก่อนเสมอ (ต้องลบก่อน indexes!)
                // Method 1: ลบด้วยชื่อ constraint
                try {
                    $table->dropForeign('line_signup_rewards_user_id_foreign');
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }

                // Method 2: ลบด้วย column name (Laravel จะหาชื่อ constraint ให้เอง)
                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Ignore if constraint doesn't exist
                }

                // ลบ composite index idx_user_status ถ้ามี
                try {
                    $table->dropIndex('idx_user_status');
                } catch (\Exception $e) {
                    // Ignore if index doesn't exist
                }

                // ลบ index ทั่วไปที่อาจมี user_id
                try {
                    $table->dropIndex(['user_id']);
                } catch (\Exception $e) {
                    // Ignore if index doesn't exist
                }

                // ลบ column user_id
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
