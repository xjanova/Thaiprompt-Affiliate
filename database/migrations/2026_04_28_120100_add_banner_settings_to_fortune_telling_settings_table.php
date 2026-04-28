<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่มฟิลด์ banner settings ใน fortune_telling_settings
 *
 * - enable_dm_banner: เปิด/ปิดส่งแบนเนอร์ DM
 * - banner_pick_strategy: 'random' (สุ่ม) หรือ 'rotation' (วนรอบ) หรือ 'sequential' (ตามลำดับ sort_order)
 * - banner_send_on_reaction: ส่งเมื่อกด reaction
 * - banner_send_on_comment: ส่งเมื่อ comment
 * - banner_send_on_welcome: ส่งเมื่อ user ทักครั้งแรก
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_dm_banner')) {
                $table->boolean('enable_dm_banner')->default(false)->after('enable_ai_chat');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'banner_pick_strategy')) {
                $table->string('banner_pick_strategy', 20)->default('rotation')->after('enable_dm_banner');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'banner_send_on_reaction')) {
                $table->boolean('banner_send_on_reaction')->default(true)->after('banner_pick_strategy');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'banner_send_on_comment')) {
                $table->boolean('banner_send_on_comment')->default(true)->after('banner_send_on_reaction');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'banner_send_on_welcome')) {
                $table->boolean('banner_send_on_welcome')->default(true)->after('banner_send_on_comment');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            $columns = [
                'enable_dm_banner',
                'banner_pick_strategy',
                'banner_send_on_reaction',
                'banner_send_on_comment',
                'banner_send_on_welcome',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
