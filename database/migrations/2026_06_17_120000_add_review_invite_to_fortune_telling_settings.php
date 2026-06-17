<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ⭐ (2026-06-17) Review Invite — ชวนลูกค้ารีวิว/แนะนำเพจ Facebook หลังดูดวงจบ
 *
 * เจ้าของสั่ง: "นำลูกค้าที่ดูดวงเสร็จแล้วทุกคน (ที่จ่ายเงิน) ไปหน้ารีวิวอัตโนมัติ
 *              ส่งหลังสรุปคำทำนาย VIP — ไม่ต้องจับพอใจ/ไม่พอใจ เอาทุกคน"
 *
 * เพิ่ม 3 ค่า config (admin ปรับได้):
 *   - review_invite_enabled : เปิด/ปิดฟีเจอร์ (default FALSE — เปิดเองตอนพร้อม ปลอดภัยตอน deploy)
 *   - review_facebook_url   : ลิงก์รีวิว/แนะนำเพจ Facebook (เว้นว่าง = ใช้ default
 *                             https://www.facebook.com/{facebook_page_id}/reviews/ จากเพจจริง)
 *   - review_invite_text    : ข้อความชวนรีวิว (เว้นว่าง = ใช้ default; รองรับตัวแปร {name})
 *
 * จุดส่ง (chokepoint): หลังข้อความปิด session ที่ลูกค้าจ่ายเงิน
 *   - Celtic 99  → endCelticSession() (action celtic_session_ended) — ครอบ webhook + cron auto-finalize
 *   - Deep 39    → finalizeDeepProSessionTimeout() (action deep_pro_session_timeout)
 *
 * @see App\Services\Fortune\FortuneReviewInviteService
 */
return new class extends Migration
{
    /**
     * เพิ่มคอลัมน์ review invite ในตาราง fortune_telling_settings
     *
     * ⚠️ ALTER TABLE (เพิ่มคอลัมน์) → ห้ามใช้ Schema::hasTable() + return
     *    (เช็คทีละคอลัมน์ด้วย hasColumn แทน)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // nullable ทุกช่อง — กันเคสแอดมินล้างช่องแล้ว save error ; โค้ดมี ?? default รองรับ
            if (! Schema::hasColumn('fortune_telling_settings', 'review_invite_enabled')) {
                $table->boolean('review_invite_enabled')
                    ->nullable()
                    ->default(false)
                    ->after('satisfaction_close_message')
                    ->comment('เปิด/ปิด ชวนรีวิวเพจ Facebook หลังดูดวงจบ (เฉพาะลูกค้าจ่ายเงิน) — default ปิด');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'review_facebook_url')) {
                $table->string('review_facebook_url', 500)
                    ->nullable()
                    ->after('review_invite_enabled')
                    ->comment('ลิงก์รีวิว/แนะนำเพจ Facebook — เว้นว่าง = derive จาก facebook_page_id อัตโนมัติ');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'review_invite_text')) {
                $table->text('review_invite_text')
                    ->nullable()
                    ->after('review_facebook_url')
                    ->comment('ข้อความชวนรีวิว (เว้นว่าง = ใช้ default) — รองรับตัวแปร {name}');
            }
        });
    }

    /**
     * ลบคอลัมน์ review invite
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach (['review_invite_enabled', 'review_facebook_url', 'review_invite_text'] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
