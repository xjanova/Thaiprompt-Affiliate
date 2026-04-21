<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ลบ unique constraint (facebook_user_id, facebook_post_id) ออกจาก fortune_comment_engagements
 *
 * เหตุผล: เจ้าของต้องการให้บอททักแชทลูกค้าทุกคอมเม้นต์ — แม้คนเดิมคอมเม้นต์ซ้ำในโพสต์เดิม
 * dedupe จะอิงจาก facebook_comment_id แทน (ผ่าน application-level check)
 *
 * เพิ่ม index บน facebook_comment_id เพื่อให้ hasEngagedComment() เร็วขึ้น
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. ลบ unique index (user, post) — ไม่ใช้แล้ว
        Schema::table('fortune_comment_engagements', function (Blueprint $table) {
            // ใช้ try/catch เพราะ index อาจถูกลบไปก่อนหน้านี้แล้ว (idempotent)
            try {
                $table->dropUnique('unique_user_post_engagement');
            } catch (\Throwable $e) {
                // ignore — index ไม่มีอยู่แล้ว
            }
        });

        // 2. เพิ่ม index บน facebook_comment_id เพื่อ lookup เร็ว
        Schema::table('fortune_comment_engagements', function (Blueprint $table) {
            // เช็คก่อน — ป้องกัน duplicate index
            $indexName = 'fortune_comment_engagements_facebook_comment_id_index';
            $exists = collect(DB::select("SHOW INDEX FROM fortune_comment_engagements"))
                ->pluck('Key_name')
                ->contains($indexName);

            if (! $exists) {
                $table->index('facebook_comment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_comment_engagements', function (Blueprint $table) {
            // กู้คืน unique — อาจ fail ถ้ามี duplicate (user, post) แล้ว
            // (ตั้งแต่ migration up ไปแล้ว อาจมีการ engage ซ้ำ)
            try {
                $table->unique(['facebook_user_id', 'facebook_post_id'], 'unique_user_post_engagement');
            } catch (\Throwable $e) {
                // ignore — ถ้ามี duplicate อยู่แล้ว → ไม่สามารถ restore ได้
            }

            try {
                $table->dropIndex('fortune_comment_engagements_facebook_comment_id_index');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};
