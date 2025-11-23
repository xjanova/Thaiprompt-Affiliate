<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * ทำความสะอาด columns พิเศษที่ไม่ควรมีในตาราง line_signup_rewards
     *
     * ตาราง line_signup_rewards เป็นตาราง template รางวัล
     * ไม่ควรมี user_id, session_id, reward_name หรือ columns อื่นๆ ที่เกี่ยวกับ instance
     *
     * @return void
     */
    public function up(): void
    {
        // รายการ columns ที่ไม่ควรมีใน template table
        $unwantedColumns = ['user_id', 'session_id', 'reward_name', 'claim_id'];

        foreach ($unwantedColumns as $column) {
            if (Schema::hasColumn('line_signup_rewards', $column)) {
                // พยายามลบ foreign key ถ้ามี
                $foreignKeyName = "line_signup_rewards_{$column}_foreign";
                $this->safeDropForeign('line_signup_rewards', $foreignKeyName);

                // ลบ column
                Schema::table('line_signup_rewards', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * เพิ่ม columns กลับ (สำหรับ rollback)
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่ทำอะไร เพราะ columns เหล่านี้ไม่ควรมีตั้งแต่แรก
        // ถ้าต้องการ rollback ให้ดูใน migrations ก่อนหน้า
    }
};
