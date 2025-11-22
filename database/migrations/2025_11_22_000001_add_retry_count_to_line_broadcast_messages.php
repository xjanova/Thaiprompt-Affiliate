<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ retry_count และ last_retry_at สำหรับระบบ retry อัตโนมัติ
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('line_broadcast_messages', function (Blueprint $table) {
            // เพิ่มคอลัมน์สำหรับการ retry
            $this->safeAddColumn($table, 'line_broadcast_messages', 'retry_count', function($table) {
                $table->integer('retry_count')->default(0)->comment('จำนวนครั้งที่พยายามส่งซ้ำ')->after('failed_count');
            });

            $this->safeAddColumn($table, 'line_broadcast_messages', 'last_retry_at', function($table) {
                $table->timestamp('last_retry_at')->nullable()->comment('เวลาที่ retry ครั้งล่าสุด')->after('retry_count');
            });

            $this->safeAddColumn($table, 'line_broadcast_messages', 'error_message', function($table) {
                $table->text('error_message')->nullable()->comment('ข้อความ error (ถ้ามี)')->after('last_retry_at');
            });
        });

        // เพิ่ม index สำหรับ query ที่ต้อง retry
        $this->safeAddIndex('line_broadcast_messages', ['status', 'retry_count', 'last_retry_at'], 'idx_retry_status');
    }

    /**
     * ลบคอลัมน์ที่เพิ่มเข้าไป
     *
     * @return void
     */
    public function down(): void
    {
        $this->safeDropIndex('line_broadcast_messages', 'line_broadcast_messages_status_retry_count_last_retry_at_index');

        $this->safeDropColumn('line_broadcast_messages', [
            'retry_count',
            'last_retry_at',
            'error_message',
        ]);
    }
};
