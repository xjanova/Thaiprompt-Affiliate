<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * ลบ columns เก่าที่ไม่ควรมีในตาราง line_signup_rewards
     *
     * ตาราง line_signup_rewards เป็นตาราง TEMPLATE สำหรับกำหนดรางวัล
     * ไม่ใช่ตารางเก็บรางวัลที่ผู้ใช้ได้รับ (ใช้ line_signup_reward_claims แทน)
     *
     * Columns ที่จะลบ:
     * - user_id: ไม่ควรมี (มีใน reward_claims แทน)
     * - session_id: ไม่ควรมี (มีใน reward_claims แทน)
     * - reward_name: duplicate ของ name
     * - reward_amount: duplicate ของ amount
     * - reward_description: duplicate ของ description
     * - status: ไม่ควรมีใน template (มีใน reward_claims)
     * - granted_at: ไม่ควรมีใน template (มีใน reward_claims)
     * - claimed_at: ไม่ควรมีใน template (มีใน reward_claims)
     * - expires_at: ไม่ควรมีใน template (มีใน reward_claims)
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasTable('line_signup_rewards')) {
            return;
        }

        // รายการ columns ที่ต้องลบทั้งหมด
        $columnsToRemove = [
            'user_id',
            'session_id',
            'reward_name',
            'reward_amount',
            'reward_description',
            'status',
            'granted_at',
            'claimed_at',
            'expires_at',
        ];

        Schema::table('line_signup_rewards', function (Blueprint $table) use ($columnsToRemove) {
            // 1. ลบ indexes ที่เกี่ยวข้องก่อน
            $indexesToDrop = [
                'idx_user_status',           // composite index (user_id, status)
                'idx_session_status',         // อาจมี composite index
                'line_signup_rewards_user_id_index',
                'line_signup_rewards_session_id_index',
                'line_signup_rewards_status_index',
            ];

            foreach ($indexesToDrop as $indexName) {
                try {
                    $table->dropIndex($indexName);
                } catch (\Exception $e) {
                    // Ignore if index doesn't exist
                }
            }

            // 2. ลบ foreign key constraints
            $foreignKeys = [
                'line_signup_rewards_user_id_foreign',
                'line_signup_rewards_session_id_foreign',
            ];

            foreach ($foreignKeys as $fkName) {
                try {
                    $table->dropForeign($fkName);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
            }

            // ลบ foreign keys โดยใช้ column names
            foreach (['user_id', 'session_id'] as $column) {
                try {
                    $table->dropForeign([$column]);
                } catch (\Exception $e) {
                    // Ignore if FK doesn't exist
                }
            }

            // 3. ลบ indexes แบบ single column
            foreach ($columnsToRemove as $column) {
                try {
                    $table->dropIndex([$column]);
                } catch (\Exception $e) {
                    // Ignore if index doesn't exist
                }
            }
        });

        // 4. ลบ columns ทีละตัว (แยกออกจาก closure ข้างบนเพื่อความปลอดภัย)
        foreach ($columnsToRemove as $column) {
            if (Schema::hasColumn('line_signup_rewards', $column)) {
                Schema::table('line_signup_rewards', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }

    /**
     * Rollback: ไม่มี rollback เพราะเป็นการลบ columns ที่ผิดโครงสร้าง
     *
     * หาก rollback จำเป็น ให้ restore จาก backup database
     *
     * @return void
     */
    public function down(): void
    {
        // ไม่มี rollback - หากต้องการกู้คืน ให้ใช้ database backup
        // เพราะเป็นการลบ columns ที่ไม่ควรมีในตาราง template
    }
};
