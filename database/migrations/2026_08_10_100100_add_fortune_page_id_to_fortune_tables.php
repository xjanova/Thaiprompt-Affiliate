<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🏬 (2026-08-10) ระบบสาขา — ติดป้าย "บิลนี้/ลูกค้าคนนี้มาจากเพจไหน"
 *
 * ⚠️ เป็น ALTER TABLE (เพิ่มคอลัมน์) → ห้ามใช้ Schema::hasTable() + return
 *    ต้องเช็คทีละคอลัมน์ด้วย Schema::hasColumn()
 *
 * ทำไมไม่ใส่ foreign key:
 *   fortune_readings/personas บน prod มีหลายแสนแถว การสร้าง FK ล็อกตารางนาน
 *   และแถวใน fortune_pages ใช้ softDeletes (ไม่มีการลบจริง) ความสัมพันธ์จึงไม่ขาด
 *   → ใช้ unsignedBigInteger + index พอ
 *
 * nullable เพราะ:
 *   1) แถวเก่าก่อน backfill
 *   2) งานที่ยิงนอก context เพจ (คอนโซล/แอดมิน) จะไม่มีเพจ — ยอมให้ null ดีกว่าใส่มั่ว
 */
return new class extends Migration
{
    /**
     * ตารางที่ต้องรู้ว่ามาจากเพจไหน => คอลัมน์ที่จะวาง fortune_page_id ต่อท้าย
     *
     * @var array<string, string|null>
     */
    protected array $targets = [
        'fortune_readings' => 'platform_user_id',
        'fortune_customer_personas' => 'platform_user_id',
        'fortune_user_credits' => null,
        'fortune_referrals' => null,
        'fortune_commissions' => null,
        'fortune_comment_engagements' => null,
        'fortune_user_bans' => null,
        'fortune_post_reactions' => null,
    ];

    /**
     * เพิ่มคอลัมน์ fortune_page_id
     */
    public function up(): void
    {
        foreach ($this->targets as $tableName => $after) {
            if (! Schema::hasTable($tableName)) {
                continue; // ตารางยังไม่มี (ติดตั้งใหม่/เวอร์ชันเก่า) — ข้ามอย่างปลอดภัย
            }

            if (Schema::hasColumn($tableName, 'fortune_page_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $after) {
                $column = $table->unsignedBigInteger('fortune_page_id')
                    ->nullable()
                    ->comment('สาขา/เพจที่เป็นต้นทางของแถวนี้ (fortune_pages.id)');

                // วางต่อท้ายคอลัมน์ที่เกี่ยวข้อง ถ้ามีจริง
                if ($after && Schema::hasColumn($tableName, $after)) {
                    $column->after($after);
                }

                // ชื่อ index สั้น (< 50 ตัว) กัน MySQL ปฏิเสธ
                $table->index('fortune_page_id', substr('ftn_pg_'.md5($tableName), 0, 30).'_idx');
            });
        }
    }

    /**
     * ลบคอลัมน์ fortune_page_id
     */
    public function down(): void
    {
        foreach (array_keys($this->targets) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'fortune_page_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex(substr('ftn_pg_'.md5($tableName), 0, 30).'_idx');
                $table->dropColumn('fortune_page_id');
            });
        }
    }
};
