<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์ระบบเทคโอเวอร์ (Admin Takeover) ในตาราง fortune_readings
 *
 * ใช้สำหรับบันทึกสถานะที่แอดมิน/แม่หมอเข้ามาคุยแทน AI
 * ระบบเดิมใช้ Cache (fortune_admin_active:{userId}) ต่อเนื่องได้
 * ระบบใหม่เก็บใน DB เพื่อให้มี per-conversation timeout และ audit trail
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ใหม่ในตาราง fortune_readings
     *
     * ⚠️ ห้ามใช้ Schema::hasTable() + return เพราะจะทำให้คอลัมน์ใหม่ไม่ถูกสร้าง
     */
    public function up(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            // หมดเวลาเทคโอเวอร์ (AI กลับมาทำงานหลังเวลานี้)
            if (! Schema::hasColumn('fortune_readings', 'admin_takeover_until')) {
                $table->timestamp('admin_takeover_until')
                    ->nullable()
                    ->after('conversation_state')
                    ->comment('เวลาหมดเทคโอเวอร์ — AI กลับมาทำงานหลังเวลานี้');
            }

            // ผู้ที่เทคโอเวอร์ (user ในระบบ admin)
            if (! Schema::hasColumn('fortune_readings', 'admin_takeover_by')) {
                $table->unsignedBigInteger('admin_takeover_by')
                    ->nullable()
                    ->after('admin_takeover_until')
                    ->comment('user_id ของแอดมินที่เทคโอเวอร์');
            }

            // เหตุผลการเทคโอเวอร์ (manual, auto_reply, customer_request)
            if (! Schema::hasColumn('fortune_readings', 'admin_takeover_reason')) {
                $table->string('admin_takeover_reason', 50)
                    ->nullable()
                    ->after('admin_takeover_by')
                    ->comment('เหตุผล: manual=กดเทค, auto_reply=แอดมินพิมพ์, customer_request=ลูกค้าขอ');
            }

            // เวลาเริ่มเทคโอเวอร์ (สำหรับคำนวณระยะเวลา)
            if (! Schema::hasColumn('fortune_readings', 'admin_takeover_started_at')) {
                $table->timestamp('admin_takeover_started_at')
                    ->nullable()
                    ->after('admin_takeover_reason')
                    ->comment('เวลาเริ่มเทคโอเวอร์ครั้งล่าสุด');
            }

            // Index สำหรับ query conversation ที่กำลังถูก takeover อยู่
            if (! $this->hasIndex('fortune_readings', 'fortune_readings_takeover_idx')) {
                $table->index(['admin_takeover_until', 'conversation_status'], 'fortune_readings_takeover_idx');
            }
        });
    }

    /**
     * ย้อนกลับ: ลบคอลัมน์และ index
     */
    public function down(): void
    {
        Schema::table('fortune_readings', function (Blueprint $table) {
            if ($this->hasIndex('fortune_readings', 'fortune_readings_takeover_idx')) {
                $table->dropIndex('fortune_readings_takeover_idx');
            }

            $columns = [
                'admin_takeover_until',
                'admin_takeover_by',
                'admin_takeover_reason',
                'admin_takeover_started_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_readings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * ตรวจสอบว่ามี index ชื่อนี้ในตารางหรือไม่
     */
    protected function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $schema = $connection->getDatabaseName();

        $result = $connection->select(
            'SELECT COUNT(*) as cnt FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$schema, $table, $indexName]
        );

        return ! empty($result) && ($result[0]->cnt ?? 0) > 0;
    }
};
