<?php

use App\Models\FortuneInviteMessage;
use Database\Seeders\FortuneWebOnlyInviteMessageSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🌐 (2026-11-03) seed ข้อความชวนชุด FB web-only (mode='web') ลง prod
     *
     * prod ไม่รัน db:seed ตอน deploy → ต้องสั่งผ่าน migration ไม่งั้นเปิดธง facebook_web_only แล้ว
     * pickActive() เจอชุด web ว่าง = DM ชวนเงียบทั้งระบบ (ตั้งใจไม่ตกไปชุดเดิมที่มีราคา)
     *
     * ไม่แก้ schema — เพิ่มแถวอย่างเดียว · idempotent (seeder จับคู่ด้วยข้อความ รวมแถวที่ถูกลบ)
     * ⚠️ seed พังห้ามทำให้ทั้ง deploy ล้ม — log แล้วไปต่อ (preflight ของ T18 จะเตือนว่าชุดว่าง)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return;
        }

        try {
            $created = FortuneWebOnlyInviteMessageSeeder::seed();

            Log::info('🌐 migration: seed ข้อความชวนชุด web แล้ว', ['created' => $created]);
        } catch (\Throwable $e) {
            Log::error('🌐 migration: seed ข้อความชวนชุด web ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ลบเฉพาะแถวที่ seed ไว้ (จับด้วยข้อความเดิมเป๊ะ) — แถวที่แอดมินเขียนเองในชุด web ไม่ถูกแตะ
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return;
        }

        $messages = array_column(FortuneWebOnlyInviteMessageSeeder::rows(), 'message');

        FortuneInviteMessage::withTrashed()
            ->where('mode', FortuneInviteMessage::MODE_WEB)
            ->whereIn('message', $messages)
            ->forceDelete();
    }
};
