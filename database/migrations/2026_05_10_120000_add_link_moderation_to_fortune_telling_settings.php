<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 🛡️ (2026-05-10) เพิ่มระบบจัดการคอมเม้นต์สแปมที่มี URL/ลิงค์
 *
 * Columns:
 *   - auto_hide_link_comments (bool): kill switch (default false ปิดไว้รอ test)
 *   - link_comment_action (string): 'hide' | 'delete' (default 'hide' — ผู้คอมยังเห็นเอง)
 *   - link_whitelist_domains (json): array โดเมนที่อนุญาต (default มีของเรา)
 *   - link_moderation_log_only (bool): dry-run mode — log อย่างเดียวไม่ลบจริง (default false)
 *
 * Permission ที่ต้องใช้: pages_manage_engagement (ตรวจสอบแล้วว่า XmanApp มีแล้ว ✅)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'auto_hide_link_comments')) {
                $table->boolean('auto_hide_link_comments')
                    ->default(false)
                    ->comment('เปิดระบบซ่อน/ลบคอมเม้นต์ที่มีลิงค์ภายนอกอัตโนมัติ');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'link_comment_action')) {
                $table->string('link_comment_action', 16)
                    ->default('hide')
                    ->comment('การกระทำเมื่อพบลิงค์: hide (ซ่อน) หรือ delete (ลบ)');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'link_whitelist_domains')) {
                $table->json('link_whitelist_domains')
                    ->nullable()
                    ->comment('โดเมนที่อนุญาต (json array) — ลิงค์ที่ตรงโดเมนเหล่านี้จะไม่ถูกซ่อน');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'link_moderation_log_only')) {
                $table->boolean('link_moderation_log_only')
                    ->default(false)
                    ->comment('Dry-run mode — log อย่างเดียว ไม่ซ่อน/ลบจริง (สำหรับทดสอบ)');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'link_moderation_log_only',
                'link_whitelist_domains',
                'link_comment_action',
                'auto_hide_link_comments',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('fortune_telling_settings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
