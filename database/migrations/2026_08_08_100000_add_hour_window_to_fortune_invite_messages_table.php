<?php

use App\Models\FortuneInviteMessage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * เพิ่มช่วงเวลาที่อนุญาตให้ส่ง (hour_from / hour_to) ให้คลังข้อความชวนดูดวง
     *
     * ⏰ (2026-08-08) เหตุที่ต้องมี — pickActive() สุ่มล้วน ไม่รู้จักเวลา
     *    หลักฐาน prod: engagement #429488 ส่งตอน 11:57 น. ด้วยข้อความ
     *    "🌙 ดึกแล้วแต่แม่หมอยังเปิดตำราอยู่ค่ะ ... เปิดให้ดูฟรีก่อนนอนนะคะ"
     *    = ทักลูกค้าตอนเที่ยงว่าดึกแล้ว (ชุด daily มีข้อความผูกเวลาอยู่หลายข้อ)
     *
     * กติกาของคอลัมน์:
     *   - NULL ทั้งคู่  = ส่งได้ทุกเวลา (ค่าของแถวเดิม *ทุกแถว* → พฤติกรรมเดิม 100%)
     *   - 5 ถึง 9      = 05:00–09:59 (รวมปลายทั้งสองข้าง)
     *   - 21 ถึง 2     = 21:00–02:59 (คร่อมเที่ยงคืน — from > to ถือว่าข้ามวัน)
     *   - ตั้งมาข้างเดียว = ถือว่าไม่ได้ตั้ง (ส่งได้ทุกเวลา) กันหน้าต่างครึ่งใบ
     *
     * ⚠️ ไม่ใส่ index ให้คอลัมน์นี้โดยตั้งใจ — เงื่อนไขเป็น range OR NULL ที่ MySQL
     *    ใช้ index ไม่ได้อยู่ดี และตารางนี้มีไม่ถึงพันแถว (full scan ถูกกว่า)
     */
    public function up(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')) {
            return;
        }

        // ⚠️ ALTER TABLE เพิ่มคอลัมน์ — ห้าม Schema::hasTable() + return
        //    (จะทำให้คอลัมน์ใหม่ไม่ถูกสร้างบนตารางที่มีอยู่แล้ว) เช็คทีละคอลัมน์แทน
        Schema::table('fortune_invite_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('fortune_invite_messages', 'hour_from')) {
                $table->unsignedTinyInteger('hour_from')
                    ->nullable()
                    ->after('mode')
                    ->comment('ชั่วโมงเริ่มส่งได้ 0-23 (NULL = ส่งได้ทุกเวลา)');
            }

            if (! Schema::hasColumn('fortune_invite_messages', 'hour_to')) {
                $table->unsignedTinyInteger('hour_to')
                    ->nullable()
                    ->after('hour_from')
                    ->comment('ชั่วโมงสุดท้ายที่ส่งได้ 0-23 แบบรวมปลาย (NULL = ส่งได้ทุกเวลา)');
            }
        });

        $this->backfillWindows();
    }

    /**
     * ลบคอลัมน์ช่วงเวลา
     */
    public function down(): void
    {
        if (! Schema::hasTable('fortune_invite_messages')) {
            return;
        }

        Schema::table('fortune_invite_messages', function (Blueprint $table) {
            foreach (['hour_from', 'hour_to'] as $column) {
                if (Schema::hasColumn('fortune_invite_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * เติมช่วงเวลาให้ข้อความเดิมที่เขียนผูกเวลาไว้ (เช้า/ก่อนนอน)
     *
     * ใช้แผนที่กลางที่ FortuneInviteMessage::DEFAULT_TIME_WINDOWS
     * (seeder ใช้ตัวเดียวกัน — ติดตั้งใหม่กับ prod จะได้ค่าตรงกัน)
     *
     * ⚠️ ห่อ try/catch ไว้ — งาน backfill ล้มไม่ควรทำให้ทั้ง deploy ตาย
     *    คอลัมน์ถูกสร้างแล้ว = ระบบทำงานได้ปกติ (แค่ยังไม่มีใครถูกล็อกเวลา)
     */
    protected function backfillWindows(): void
    {
        if (! Schema::hasColumn('fortune_invite_messages', 'hour_from')
            || ! Schema::hasColumn('fortune_invite_messages', 'hour_to')) {
            return;
        }

        try {
            $updated = FortuneInviteMessage::applyDefaultTimeWindows();

            Log::info('⏰ Migration: เติมช่วงเวลาให้ข้อความชวนดูดวงเดิม', ['updated' => $updated]);
        } catch (\Throwable $e) {
            Log::warning('⏰ Migration: เติมช่วงเวลาไม่สำเร็จ (ข้ามไปก่อน แอดมินตั้งเองได้)', [
                'err' => $e->getMessage(),
            ]);
        }
    }
};
