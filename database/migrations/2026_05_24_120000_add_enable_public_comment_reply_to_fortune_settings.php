<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🚫 (2026-05-24) เพิ่ม toggle enable_public_comment_reply
 *
 * User spec: "บอท api มีการตอบกลับโพส แต่มันไม่ได้โพสจริง เพราะ app ยังไม่ให้โพส
 *             ทำให้เสียโควต้าฟรีๆ เรา แค่ dm อย่างเดียว พอ"
 *
 * ปัญหาเดิม:
 *   - ProcessCommentEngagement::handle() เรียก AI gen comment_reply ทุก comment ที่ไม่ match pattern
 *   - แล้วเรียก replyToComment() ซึ่ง fail HTTP 403 เพราะ Page Token ขาด pages_manage_engagement
 *   - AI quota เผาเปล่า ทุกครั้ง (400 max_tokens × 40-60% comment ที่ไม่ match)
 *
 * Toggle ใหม่:
 *   - Default: false (ปลอดภัย — ยังไม่ได้รับ App Review approval)
 *   - เมื่อ false: ProcessCommentEngagement skip pattern match + AI gen + replyToComment + reactToComment
 *                  แต่ยังส่ง DM ตามปกติ (Page Messaging ใช้ scope คนละตัว ไม่กระทบ)
 *   - เมื่อ true: ทำงานเหมือนเดิม (สำหรับเมื่อ pages_manage_engagement approved แล้ว)
 *
 * Note: comment_engagement_enabled (toggle ใหญ่) ยังคงใช้ควบคุมทั้งระบบ
 *       toggle ใหม่นี้ = sub-toggle เฉพาะส่วน public reply (ภายใต้ toggle ใหญ่)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_public_comment_reply')) {
                $table->boolean('enable_public_comment_reply')
                    ->default(false)
                    ->after('comment_engagement_mode')
                    ->comment('🚫 (2026-05-24) Toggle: ตอบคอมเม้นต์สาธารณะหรือไม่ — default=false จนกว่าจะได้รับ pages_manage_engagement scope');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_public_comment_reply')) {
                $table->dropColumn('enable_public_comment_reply');
            }
        });
    }
};
