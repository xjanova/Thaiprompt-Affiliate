<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🪪 (2026-11-03) FB web-only — ตาราง PSID สำรอง (alias) ของลูกค้า Messenger
     *
     * ทำไมต้องมี:
     *   PSID เป็นเลขผูกกับ "เพจ" — ลูกค้าคนเดียวที่ทักทั้งเพจหลักและเพจสาขาจะมี PSID หลายตัว
     *   แต่ users.facebook_psid เก็บได้ช่องเดียว → PSID สาขาที่สองหาบัญชีเดิมไม่เจอ
     *   → ได้บัญชีใหม่ใต้ผู้แนะนำเริ่มต้น สายงาน MLM แตก (invariant M1)
     *
     * กฎ: PSID หนึ่งตัวผูกได้ user เดียว (unique) · user หนึ่งคนมีได้หลาย PSID (คนละเพจ)
     *   User::findByMessengerPsid ค้นตารางนี้ต่อจากคอลัมน์ facebook_psid
     *
     * Backfill: คัดลอก users.facebook_psid ทุกแถวมาใส่ (source = backfill, ไม่รู้เพจ = null)
     *   เพิ่มข้อมูลอย่างเดียว — ผลค้นหาเท่าเดิม (findByMessengerPsid ค้นคอลัมน์ facebook_psid ก่อนเสมอ
     *   แถว backfill จึงไม่เคยเป็นตัวตัดสิน) ใช้ได้ทั้งตอนธง facebook_web_only ปิดและเปิด
     *
     * 🔁 (2026-11-03 รีวิว) ย้าย "เติมชื่อบัญชีบอทที่ยังชื่อ 'User'" ออกจาก migration แล้ว
     *   (เขียนทับ users.name ย้อนกลับไม่ได้ และรันทันทีตอน deploy แม้ธงปิด)
     *   → ไปอยู่ใน fortune:web-only-cutover --execute (ทำเฉพาะตอนเจ้าของเปิดธงแล้ว)
     */
    public function up(): void
    {
        // สร้างตารางใหม่ — มีอยู่แล้วข้ามการสร้าง (แต่ backfill ด้านล่างยังทำงาน idempotent)
        if (! Schema::hasTable('fortune_messenger_psids')) {
            Schema::create('fortune_messenger_psids', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->cascadeOnDelete();
                // PSID ตัวเลขล้วน (ยาว ~16-17 หลัก) — 64 ให้ตรงกับ users.facebook_psid
                $table->string('psid', 64)->comment('Messenger Page-Scoped ID');
                // เพจ/สาขาที่ PSID นี้สังกัด (null = ไม่ทราบ หรือเพจหลัก)
                $table->unsignedBigInteger('fortune_page_id')->nullable()->comment('fortune_pages.id ของ PSID นี้');
                // ที่มาของการผูก: backfill | bot_signup | web_gateway | session_alias | oauth_hint
                $table->string('source', 32)->default('backfill');
                $table->timestamps();

                $table->unique('psid', 'fmp_psid_unique');
                $table->index(['user_id', 'fortune_page_id'], 'fmp_user_page_idx');
            });
        }

        $this->backfillFromUsers();
    }

    /**
     * คัดลอก users.facebook_psid → fortune_messenger_psids (ข้ามแถวที่มีแล้ว)
     */
    private function backfillFromUsers(): void
    {
        if (! Schema::hasColumn('users', 'facebook_psid')) {
            return;
        }

        $now = now();

        DB::table('users')
            ->whereNotNull('facebook_psid')
            ->where('facebook_psid', '!=', '')
            ->select('id', 'facebook_psid')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($now) {
                $insert = [];
                foreach ($rows as $row) {
                    $psid = trim((string) $row->facebook_psid);
                    if ($psid === '' || ! ctype_digit($psid)) {
                        continue;
                    }
                    $insert[] = [
                        'user_id' => $row->id,
                        'psid' => $psid,
                        'fortune_page_id' => null,
                        'source' => 'backfill',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // insertOrIgnore: PSID ที่มีอยู่แล้ว (รันซ้ำ) ข้ามไปเงียบ ๆ
                if ($insert !== []) {
                    DB::table('fortune_messenger_psids')->insertOrIgnore($insert);
                }
            });
    }

    /**
     * ลบตาราง alias (migration นี้ไม่แตะ users.name แล้ว — ดู up())
     */
    public function down(): void
    {
        Schema::dropIfExists('fortune_messenger_psids');
    }
};
