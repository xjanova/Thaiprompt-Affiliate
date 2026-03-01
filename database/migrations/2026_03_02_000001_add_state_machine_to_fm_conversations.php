<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มคอลัมน์ State Machine ใน fresh_market_conversations
 *
 * สำหรับ Conversation State Machine ใหม่ (13 states)
 * แทนที่ 6 states เดิม (new, browsing, searching, listing, ordering, chatting)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fresh_market_conversations', function (Blueprint $table) {
            // เวลาที่เปลี่ยนสถานะล่าสุด (สำหรับ timeout)
            if (! Schema::hasColumn('fresh_market_conversations', 'state_updated_at')) {
                $table->timestamp('state_updated_at')->nullable()->after('conversation_state')
                    ->comment('เวลาที่เปลี่ยนสถานะล่าสุด');
            }

            // ขั้นตอนปัจจุบัน (เช่น 1/5, 2/5)
            if (! Schema::hasColumn('fresh_market_conversations', 'state_step')) {
                $table->unsignedTinyInteger('state_step')->default(0)->after('state_updated_at')
                    ->comment('ขั้นตอนปัจจุบันในกระบวนการ');
            }

            // จำนวนขั้นตอนทั้งหมด
            if (! Schema::hasColumn('fresh_market_conversations', 'state_total_steps')) {
                $table->unsignedTinyInteger('state_total_steps')->default(0)->after('state_step')
                    ->comment('จำนวนขั้นตอนทั้งหมด');
            }

            // สถานะก่อนหน้า (สำหรับ back/undo)
            if (! Schema::hasColumn('fresh_market_conversations', 'previous_state')) {
                $table->string('previous_state', 30)->nullable()->after('state_total_steps')
                    ->comment('สถานะก่อนหน้า');
            }

            // เชื่อมกับ seller (ถ้ามี)
            if (! Schema::hasColumn('fresh_market_conversations', 'seller_id')) {
                $table->unsignedBigInteger('seller_id')->nullable()->after('user_id')
                    ->comment('FK to fresh_market_sellers');
            }
        });

        // Data migration: แปลงสถานะเดิมเป็น idle
        DB::table('fresh_market_conversations')
            ->whereIn('conversation_state', ['new', 'browsing', 'searching', 'listing', 'ordering', 'chatting'])
            ->update([
                'conversation_state' => 'idle',
                'context' => null,
                'state_updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('fresh_market_conversations', function (Blueprint $table) {
            $columns = ['state_updated_at', 'state_step', 'state_total_steps', 'previous_state', 'seller_id'];

            foreach ($columns as $col) {
                if (Schema::hasColumn('fresh_market_conversations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
