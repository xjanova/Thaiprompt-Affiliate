<?php

use App\Models\FortuneInviteMessage;
use Database\Seeders\FortuneWebInviteCampaignSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🎉 (2026-11-11) seed หมวด "เชิญมาเว็บ" 150 แบบ + ปิดหมวดข้อความชวนอื่นทั้งหมดชั่วคราว
     *
     * prod ไม่รัน db:seed ตอน deploy → ต้องสั่งผ่าน migration
     * ไม่แก้ schema — เพิ่มแถว + ตั้งค่า invite_disabled_categories (ไม่ลบข้อความใด ๆ)
     *
     * เปิดหมวดเดิมกลับ: หน้าแอดมิน → ข้อความชวน → ติ๊กหมวดที่ต้องการ
     * หรือ rollback migration นี้ (คืนรายการหมวดที่ปิดอยู่เดิมจาก storage/app/fortune/...json)
     *
     * ⚠️ seed พังห้ามทำให้ทั้ง deploy ล้ม — log แล้วไปต่อ
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return;
        }

        try {
            $created = FortuneWebInviteCampaignSeeder::seed();

            // ปิดหมวดอื่นเฉพาะเมื่อหมวดใหม่มีข้อความใช้ได้จริง — ไม่งั้น DM ชวนเงียบทั้งระบบ
            $ready = FortuneInviteMessage::query()
                ->where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)
                ->where('mode', FortuneInviteMessage::MODE_WEB)
                ->where('is_active', true)
                ->exists();

            $disabled = $ready
                ? FortuneWebInviteCampaignSeeder::focus(storage_path('app/'.FortuneWebInviteCampaignSeeder::BACKUP_PATH))
                : [];

            Log::info('🎉 migration: seed หมวดเชิญมาเว็บแล้ว', [
                'created' => $created,
                'focused' => $ready,
                'disabled_categories' => $disabled,
            ]);
        } catch (\Throwable $e) {
            Log::error('🎉 migration: seed หมวดเชิญมาเว็บไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * คืนหมวดที่ปิดอยู่เดิม + ลบเฉพาะแถวที่ seed ไว้ (จับด้วยข้อความเดิมเป๊ะ)
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')
            || ! Schema::hasColumn('fortune_invite_messages', 'mode')) {
            return;
        }

        FortuneWebInviteCampaignSeeder::unfocus(storage_path('app/'.FortuneWebInviteCampaignSeeder::BACKUP_PATH));

        $messages = array_column(FortuneWebInviteCampaignSeeder::rows(), 'message');

        FortuneInviteMessage::withTrashed()
            ->where('mode', FortuneInviteMessage::MODE_WEB)
            ->where('category', FortuneInviteMessage::CATEGORY_WEB_INVITE)
            ->whereIn('message', $messages)
            ->forceDelete();
    }
};
