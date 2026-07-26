<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 🔀 (2026-07-26) โหมด TRANSFER — ดักหน้าแชท FB แล้วส่งลูกค้าไปเว็บ/LINE
     *
     * เจ้าของสั่ง (2026-07-25/26): FB ถูกแบนมั่ว + สลิปเยอะโดนกล่าวหาว่าหลอกลวง
     * → ใช้ FB ตักปลาเหมือนเดิม แต่เมื่อลูกค้าทักเข้ามาให้พาไปดูดวงฟรีที่เว็บ
     *   จันทรา.online หรือ LINE แทนการทำนายในแชท FB
     *
     * ⚠️ ADD COLUMN — ห้ามใช้ Schema::hasTable()+return (คอลัมน์ใหม่จะไม่ถูกสร้าง)
     *
     * ทุกค่า default = พฤติกรรมเดิม 100% (mode = 'classic') — เปิดเมื่อไรก็ค่อยสลับ
     */
    public function up(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            // โหมดหลัก: classic = เดิมทุกอย่าง · transfer = ดักหน้าแล้วพาไปเว็บ/LINE
            if (! Schema::hasColumn('fortune_telling_settings', 'fortune_bot_mode')) {
                $table->string('fortune_bot_mode', 16)->default('classic')
                    ->comment('classic = โฟลเดิม · transfer = ดักหน้าแชท FB พาไปเว็บ/LINE');
            }

            // ส่งกล่องพาไปซ้ำได้ทุกกี่ชั่วโมง (กันลูกค้าประจำรำคาญ)
            if (! Schema::hasColumn('fortune_telling_settings', 'transfer_box_cooldown_hours')) {
                $table->unsignedSmallInteger('transfer_box_cooldown_hours')->default(24);
            }

            // พยายามพาไปกี่ครั้งก่อนยอมให้ดูในแชท FB (ทางถอย)
            if (! Schema::hasColumn('fortune_telling_settings', 'transfer_fallback_attempts')) {
                $table->unsignedTinyInteger('transfer_fallback_attempts')->default(3);
            }

            // จำ "ลูกค้ายืนยันขอดูบน FB" กี่วัน (ไม่ต้องถามซ้ำ)
            if (! Schema::hasColumn('fortune_telling_settings', 'transfer_fallback_days')) {
                $table->unsignedSmallInteger('transfer_fallback_days')->default(30);
            }

            // เปิดให้ลูกค้ากี่ % (แฮชจาก psid) — ทดลองทีละกลุ่มก่อนเปิดหมด
            if (! Schema::hasColumn('fortune_telling_settings', 'transfer_rollout_percent')) {
                $table->unsignedTinyInteger('transfer_rollout_percent')->default(100);
            }

            // ความยาวคำทำนายฟรีของบอท (0 = ใช้ของเดิม 1,500-2,000 ตัวอักษร)
            // ตั้ง 500 = โหมดสั้นแบบเดียวกับดูดวงฟรีบนเว็บ
            if (! Schema::hasColumn('fortune_telling_settings', 'free_card_max_chars')) {
                $table->unsignedSmallInteger('free_card_max_chars')->default(0);
            }

            // รอบแจกสิทธิ์ฟรีใหม่ — สิทธิ์ที่ใช้ก่อนเวลานี้ไม่นับ (แจกใหม่ทุกคน)
            if (! Schema::hasColumn('fortune_telling_settings', 'free_card_regrant_at')) {
                $table->timestamp('free_card_regrant_at')->nullable()
                    ->comment('เลื่อนเวลานี้ = แจกสิทธิ์ดูฟรีใหม่ให้ทุกคน');
            }
        });

        // สถานะรายลูกค้าของโหมด transfer — เก็บในตารางเดิมที่ผูกกับ
        // (platform, facebook_user_id) อยู่แล้ว ไม่ใช้ Cache เพราะ deploy
        // รัน cache:clear เกือบทุกวัน แล้ว "ลูกค้ายืนยันขอดูบน FB 30 วัน"
        // จะหายไปกลางทาง (บทเรียนเดียวกับ weekly-image dedup ที่ต้องย้ายลง DB)
        Schema::table('fortune_user_credits', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_user_credits', 'transfer_box_sent_at')) {
                $table->timestamp('transfer_box_sent_at')->nullable();
            }

            if (! Schema::hasColumn('fortune_user_credits', 'transfer_attempts')) {
                $table->unsignedSmallInteger('transfer_attempts')->default(0);
            }

            if (! Schema::hasColumn('fortune_user_credits', 'fb_fallback_granted_at')) {
                $table->timestamp('fb_fallback_granted_at')->nullable()
                    ->comment('ลูกค้ายืนยันขอดูดวงในแชท FB (ทำเว็บ/ไลน์ไม่ได้)');
            }
        });
    }

    /**
     * ถอนคอลัมน์ที่เพิ่มเข้าไป
     */
    public function down(): void
    {
        Schema::table('fortune_telling_settings', function (Blueprint $table) {
            foreach ([
                'fortune_bot_mode',
                'transfer_box_cooldown_hours',
                'transfer_fallback_attempts',
                'transfer_fallback_days',
                'transfer_rollout_percent',
                'free_card_max_chars',
                'free_card_regrant_at',
            ] as $col) {
                if (Schema::hasColumn('fortune_telling_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('fortune_user_credits', function (Blueprint $table) {
            foreach ([
                'transfer_box_sent_at',
                'transfer_attempts',
                'fb_fallback_granted_at',
            ] as $col) {
                if (Schema::hasColumn('fortune_user_credits', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
