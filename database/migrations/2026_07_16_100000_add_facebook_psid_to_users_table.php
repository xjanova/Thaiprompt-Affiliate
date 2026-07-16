<?php

use Database\Migrations\Concerns\SafeMigration;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use SafeMigration;

    /**
     * เพิ่มคอลัมน์ facebook_psid ใน users — เก็บ Messenger Page-Scoped ID
     *
     * ทำไมต้องมี:
     * บัญชีที่บอทสมัครให้ลูกค้า FB ตอนดูดวง ระบุตัวตนด้วย PSID (Page-Scoped ID)
     * ที่ฝังอยู่ในอีเมล fb_{PSID}@thaiprompt.local เท่านั้น — ไม่มีคอลัมน์ตรงๆ
     * ขณะที่ FB OAuth ให้ facebook_user_id เป็น App-Scoped ID (ASID) คนละ ID space
     * → ลูกค้า FB 648 คนบน prod (2026-07-16) เข้าบัญชี/วอลเลตตัวเองไม่ได้เลย
     *
     * ใหม่: เก็บ PSID เป็นคอลัมน์จริง → OAuth callback map ASID→PSID
     * ผ่าน Graph API ids_for_pages แล้วหาบัญชีเดิมเจอ (FacebookLoginController)
     *
     * Backfill: บัญชีที่บอทสร้าง (email fb_{PSID}@thaiprompt.local) ดึง PSID
     * จากอีเมลมาใส่คอลัมน์ — เว้นแถวที่มี facebook_user_id เพราะบัญชีที่
     * OAuth สร้างเองก็ใช้สูตร fb_{ASID}@thaiprompt.local เหมือนกัน (คนละ ID space)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $this->safeAddColumn($table, 'users', 'facebook_psid', function ($table) {
                $table->string('facebook_psid', 64)
                    ->nullable()
                    ->after('facebook_user_id')
                    ->comment('Messenger Page-Scoped ID (PSID) — ใช้ map บัญชีที่บอทสมัครให้ตอนดูดวง');
            });
        });

        // Backfill ก่อนใส่ unique index — วนทีละแถวผ่าน query builder
        // (ไม่ใช้ Eloquent เพื่อเลี่ยง observers และไม่ใช้ raw SQL เพื่อให้ portable ทุก DB)
        if (Schema::hasColumn('users', 'facebook_psid')) {
            $rows = DB::table('users')
                ->where('email', 'like', 'fb\_%@thaiprompt.local')
                ->whereNull('facebook_user_id')
                ->whereNull('facebook_psid')
                ->select('id', 'email')
                ->cursor();

            foreach ($rows as $row) {
                // email = 'fb_' . PSID . '@thaiprompt.local' — PSID เป็นตัวเลขล้วน
                $psid = substr($row->email, 3, -strlen('@thaiprompt.local'));
                if ($psid !== '' && ctype_digit($psid)) {
                    DB::table('users')->where('id', $row->id)->update(['facebook_psid' => $psid]);
                }
            }
        }

        // Unique index — กัน PSID เดียวกันผูกหลายบัญชี (1 ลูกค้า Messenger = 1 account)
        // ใส่หลัง backfill: อีเมล unique อยู่แล้ว → PSID ที่ดึงออกมาไม่มีทางซ้ำ
        $this->safeAddIndex('users', 'facebook_psid', 'users_facebook_psid_unique', 'unique');
    }

    /**
     * ลบคอลัมน์ + index เมื่อ rollback
     */
    public function down(): void
    {
        $this->safeDropIndex('users', 'users_facebook_psid_unique');
        $this->safeDropColumn('users', ['facebook_psid']);
    }
};
