<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔒 (2026-09-21) ป้าย "บัญชีนี้ระบบ (บอท) สร้างให้" — เส้นหาบัญชีลูกค้าจากอีเมลสังเคราะห์
     *   (fb_{PSID}@thaiprompt.local / line_{uid}@… / tg_…@…) ต้องเชื่อเฉพาะบัญชีที่มีป้ายนี้
     *
     * เดิมเชื่ออีเมลอย่างเดียว → ใครจองอีเมลที่เดาได้ไว้ก่อน (สมัคร/แก้โปรไฟล์) ยึดบัญชีลูกค้าจริงได้
     *   (ตอนนี้หน้าสมัคร/แก้โปรไฟล์ปฏิเสธโดเมนนี้แล้ว — ป้ายนี้คือชั้นกันที่สอง เผื่อมีทางเข้าที่หลุด)
     *
     * Backfill: ตรวจ prod 2026-09-21 (อ่านอย่างเดียว) — บัญชี @thaiprompt.local มี 1,059 บัญชี
     *   เป็น fb_ 1,013 + line_ 46 ทั้งหมด · fb_ ทุกบัญชี facebook_psid ตรงกับอีเมลและถูกสร้างหลังบิลแรก
     *   ของ PSID นั้น · line_ ตรงกัน 45 (อีก 1 = บัญชีบอทจริงที่ uid ไปผูกกับบัญชี LINE login อีกใบ)
     *   ไม่มีรูปแบบอื่น/โดเมนย่อย → ติดป้ายให้ทุกบัญชีรูปแบบบอทได้โดยไม่พาบัญชีผู้บุกรุกเข้ามาด้วย
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'bot_provisioned')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('bot_provisioned')->default(false)->index();
            });
        }

        DB::table('users')
            ->where('bot_provisioned', false)
            ->where(function ($q) {
                $q->where('email', 'like', 'fb\_%@thaiprompt.local')
                    ->orWhere('email', 'like', 'line\_%@thaiprompt.local')
                    ->orWhere('email', 'like', 'tg\_%@thaiprompt.local');
            })
            ->update(['bot_provisioned' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'bot_provisioned')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['bot_provisioned']);
                $table->dropColumn('bot_provisioned');
            });
        }
    }
};
