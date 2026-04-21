<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // เช็คว่าตารางมีอยู่จริง (กัน migration ก่อนหน้ายังไม่ทัน)
        if (! Schema::hasTable('fortune_comment_engagements')) {
            return;
        }

        // 1. ลบ unique index (user, post) — ไม่ใช้แล้ว
        //    ⚠️ try/catch ต้องห่อ Schema::table() ทั้งก้อน เพราะ schema commands
        //       ถูก deferred — flush เมื่อ closure return → error เกิดนอก closure
        try {
            Schema::table('fortune_comment_engagements', function (Blueprint $table) {
                $table->dropUnique('unique_user_post_engagement');
            });
        } catch (\Throwable $e) {
            // index ไม่มีอยู่แล้ว (อาจถูกลบด้วยมือ หรือ migration รันซ้ำ) → ข้าม
            Log::info('loosen_dedupe: dropUnique unique_user_post_engagement skipped: '.$e->getMessage());
        }

        // 2. เพิ่ม index บน facebook_comment_id เพื่อ lookup เร็ว (ถ้ายังไม่มี)
        try {
            $indexName = 'fortune_comment_engagements_facebook_comment_id_index';
            $exists = collect(DB::select('SHOW INDEX FROM fortune_comment_engagements'))
                ->pluck('Key_name')
                ->contains($indexName);

            if (! $exists) {
                Schema::table('fortune_comment_engagements', function (Blueprint $table) {
                    $table->index('facebook_comment_id');
                });
            }
        } catch (\Throwable $e) {
            Log::info('loosen_dedupe: add index(facebook_comment_id) skipped: '.$e->getMessage());
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_comment_engagements')) {
            return;
        }

        // กู้คืน unique — อาจ fail ถ้ามี duplicate (user, post) แล้ว
        try {
            Schema::table('fortune_comment_engagements', function (Blueprint $table) {
                $table->unique(['facebook_user_id', 'facebook_post_id'], 'unique_user_post_engagement');
            });
        } catch (\Throwable $e) {
            Log::info('loosen_dedupe rollback: restore unique skipped: '.$e->getMessage());
        }

        try {
            Schema::table('fortune_comment_engagements', function (Blueprint $table) {
                $table->dropIndex('fortune_comment_engagements_facebook_comment_id_index');
            });
        } catch (\Throwable $e) {
            Log::info('loosen_dedupe rollback: dropIndex skipped: '.$e->getMessage());
        }
    }
};
