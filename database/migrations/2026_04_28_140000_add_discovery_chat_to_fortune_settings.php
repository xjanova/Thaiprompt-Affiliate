<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * เพิ่ม settings สำหรับ Discovery Chat Mode
 *
 * - enable_discovery_chat: เปิด/ปิด AI psychology chat แทน flow แข็ง
 * - discovery_chat_max_turns: จำนวน turn สูงสุดก่อนบังคับสรุป (default 8)
 *
 * Default = true (เปิดทันทีหลัง deploy เพราะลดการหลุดของลูกค้า)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fortune_telling_settings')) {
            return;
        }

        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_telling_settings', 'enable_discovery_chat')) {
                $table->boolean('enable_discovery_chat')->default(true)->after('enable_dm_banner');
            }

            if (! Schema::hasColumn('fortune_telling_settings', 'discovery_chat_max_turns')) {
                $table->unsignedTinyInteger('discovery_chat_max_turns')->default(8)->after('enable_discovery_chat');
            }
        });

        // Force enable สำหรับ row ที่มีอยู่แล้ว (default ใช้แค่ตอน insert ใหม่)
        DB::table('fortune_telling_settings')->update([
            'enable_discovery_chat' => true,
            'discovery_chat_max_turns' => 8,
        ]);
    }

    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            if (Schema::hasColumn('fortune_telling_settings', 'enable_discovery_chat')) {
                $table->dropColumn('enable_discovery_chat');
            }
            if (Schema::hasColumn('fortune_telling_settings', 'discovery_chat_max_turns')) {
                $table->dropColumn('discovery_chat_max_turns');
            }
        });
    }
};
