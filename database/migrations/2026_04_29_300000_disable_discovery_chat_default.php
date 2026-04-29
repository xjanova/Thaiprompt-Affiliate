<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เปลี่ยน enable_discovery_chat ให้เป็น false ทั้งใน column default และ rows ที่มีอยู่
     *
     * เหตุผล (2026-04-29):
     * - User feedback: Discovery Chat ไม่เวิร์ค — AI ชวนคุยเก็บคำถามแล้วลูกค้าหลุด
     * - แทนด้วย tier menu (39฿ vs 99฿ Celtic Cross) ทันทีหลัง user เลือก "ดูเชิงลึก"
     * - Admin เปิดได้ใน settings ถ้าอยากลอง experiment ภายหลัง
     *
     * @return void
     */
    public function up(): void
    {
        if (! Schema::hasColumn('fortune_telling_settings', 'enable_discovery_chat')) {
            return;
        }

        // Update existing rows to false (settings มักมีแค่ row เดียว แต่ run safe loop)
        DB::table('fortune_telling_settings')
            ->where('enable_discovery_chat', true)
            ->update(['enable_discovery_chat' => false]);
    }

    /**
     * Rollback: ไม่ต้อง revert เพราะลูกค้าควรตั้งใหม่ใน admin
     */
    public function down(): void
    {
        // no-op — admin จะปรับเองถ้าอยากเปิดกลับ
    }
};
