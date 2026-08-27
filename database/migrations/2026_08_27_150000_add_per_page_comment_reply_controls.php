<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 💬 คุมการตอบคอมเมนต์ได้ละเอียดขึ้น — เพดานตั้งค่าได้ + สวิตช์รายเพจ + โควตาผูกกับเพจ
 *
 * เจ้าของสั่ง (2026-08-27): "จำนวนการตอบ เราสามารถตั้งค่าได้ ตอนนี้ใช้ 5 เท่าเดิมไปก่อน"
 * พร้อมกับ "ล็อกโควตาเป็นรายเพจให้ชัด" และ "สวิตช์เปิด/ปิดรายเพจ"
 *
 * 🚨 ทำไม `comment_reply_enabled` ต้อง **nullable** ไม่ใช่ boolean ธรรมดา
 *   ต้องมี 3 สถานะ: ตามค่ากลาง (null) · บังคับเปิด (1) · บังคับปิด (0)
 *   ถ้าใช้ boolean default 0 ⇒ วันที่ deploy ทุกเพจกลายเป็น "ปิด" ทันที
 *   เพจ #1 ที่ตอบคอมเมนต์อยู่ 7,953 ครั้ง/24ชม. จะเงียบสนิทโดยไม่มีใครสั่ง
 *   (บทเรียนเดียวกับ `auto_post_enabled` ที่ตั้งใจให้เป็น opt-in — แต่เคสนั้นเริ่มจากศูนย์
 *    เคสนี้มีของวิ่งอยู่แล้ว ค่าปริยายจึงต้องแปลว่า "อย่าเปลี่ยนอะไร")
 *
 * ⚠️ ALTER TABLE — ห้ามใช้ Schema::hasTable() + return (คอลัมน์ใหม่จะไม่ถูกสร้าง)
 */
return new class extends Migration
{
    public function up(): void
    {
        // 🏬 สวิตช์รายเพจ
        Schema::table('fortune_pages', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_pages', 'comment_reply_enabled')) {
                $table->boolean('comment_reply_enabled')
                    ->nullable()
                    ->default(null)
                    ->after('auto_post_enabled')
                    ->comment('ตอบคอมเมนต์ของเพจนี้: null=ตามค่ากลาง, 1=เปิด, 0=ปิด');
            }
        });

        // 🔢 เพดานการตอบต่อคนต่อวัน (ค่ากลาง)
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'comment_public_reply_daily_cap')) {
                $table->unsignedSmallInteger('comment_public_reply_daily_cap')
                    ->default(5)
                    ->after('enable_public_comment_reply')
                    ->comment('ตอบคอมเมนต์สาธารณะได้กี่ครั้ง/คน/24ชม. (0 = ไม่จำกัด)');
            }
        });

        // 🚀 ดัชนีสำหรับด่านโควตาที่ตอนนี้กรอง 3 คอลัมน์
        //    เดิมมีแค่ `fce_user_idx` (facebook_user_id เดี่ยว) ⇒ ต้องอ่านทุกแถวของคนนั้น
        //    มาไล่กรอง engaged_at เอง. ตารางโต 490,985 แถวแล้ว และ `engaged_at`
        //    ไม่มีดัชนีเลยสักตัว — คิวรีสรุปย้อนหลังช่วงกว้างค้างจนต้องตัดจบ
        if (Schema::hasTable('fortune_comment_engagements')
            && ! $this->indexExists('fortune_comment_engagements', 'fce_quota_idx')) {
            Schema::table('fortune_comment_engagements', function (Blueprint $table) {
                $table->index(['facebook_user_id', 'fortune_page_id', 'engaged_at'], 'fce_quota_idx');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('fortune_comment_engagements', 'fce_quota_idx')) {
            Schema::table('fortune_comment_engagements', function (Blueprint $table) {
                $table->dropIndex('fce_quota_idx');
            });
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'comment_public_reply_daily_cap')) {
                $table->dropColumn('comment_public_reply_daily_cap');
            }
        });

        Schema::table('fortune_pages', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_pages', 'comment_reply_enabled')) {
                $table->dropColumn('comment_reply_enabled');
            }
        });
    }

    /**
     * ดัชนีชื่อนี้มีอยู่แล้วไหม (รันซ้ำได้ไม่พัง)
     */
    private function indexExists(string $table, string $index): bool
    {
        try {
            return ! empty(\Illuminate\Support\Facades\DB::select(
                "SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]
            ));
        } catch (\Throwable) {
            return false;
        }
    }
};
