<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * ⏱️ (2026-10-04) หยุดคอลัมน์เวลาของระบบจ่ายเงินไม่ให้ถูกเขียนทับเป็น "ตอนนี้" ทุกครั้งที่แก้แถว
 *
 * prod เป็น MariaDB ที่ explicit_defaults_for_timestamp = 0 → คอลัมน์ TIMESTAMP ตัวแรกที่ไม่ได้ระบุ
 * DEFAULT ถูกเติม `DEFAULT current_timestamp() ON UPDATE current_timestamp()` ให้เอง
 * (migration เดิมเขียน `$table->timestamp('sms_timestamp')` เฉยๆ — MySQL 8 ใน CI ไม่เป็น เลยไม่มีใครเห็น)
 *
 * ผลบน prod (ตรวจ 2026-10-04): sms_payment_notifications.sms_timestamp — 1,817 จาก 2,398 แถว (76%)
 *   เพี้ยนเกิน 1 ชม. จากเวลา SMS จริง เพราะแถวถูกแก้ทีหลัง (matched / requires_admin_review / expired)
 *   ⇒ ด่านกันสลิปใช้ซ้ำ (slipMatchesUsedSmsPayment ±2 นาที) + ผูก SMS ตอนอนุมัติ/ตอนสลิปตัดบิล จับคู่พลาด
 *
 * ทำ 2 อย่าง:
 *   1. MODIFY คอลัมน์ให้เหลือ DEFAULT CURRENT_TIMESTAMP อย่างเดียว (ถอด ON UPDATE) — ทำเฉพาะเมื่อยังติดอยู่
 *      (พิสูจน์บน MariaDB ของ prod ด้วยตาราง TEMPORARY แล้ว: หลัง MODIFY แก้แถวแล้วเวลาไม่เปลี่ยน)
 *   2. คืนเวลา SMS จริงจาก raw_payload.sms_timestamp (ค่าที่แอพส่งมา) ด้วยสูตรเดียวกับตอนบันทึก
 *      (SmsPaymentService::processNotification ใช้ date('Y-m-d H:i:s', ms / 1000))
 *
 * ⚠️ ยังไม่แตะ unique_payment_amounts.expires_at ทั้งที่ติดกับดักเดียวกัน — ช่วงผ่อนผันจับคู่บิลที่ถูกยกเลิก
 *    (findFortuneReadingByExpiredAmount) ช่วงอ้างสิทธิ์ยอดของจันทรา และการวนใช้ยอดทศนิยม คำนวณจาก expires_at
 *    ซึ่งตอนนี้ "บังเอิญ" เท่ากับเวลาที่ยกเลิก — ถอดโดยไม่ไล่ทั้งหมด เงินที่โอนเข้าบิลที่เพิ่งยกเลิกอาจกลายเป็นเงินกำพร้า
 * ⚠️ อีก 32 คอลัมน์ (OTP / โทเคน / กระเป๋าเงิน …) ก็ติดเหมือนกัน — แยกงานไล่ทีละคอลัมน์ ห้ามเหมารวมในไฟล์นี้
 */
return new class extends Migration
{
    /** คอลัมน์ที่จะถอด ON UPDATE */
    private const COLUMNS = [
        ['sms_payment_notifications', 'sms_timestamp'],
    ];

    /**
     * ถอด ON UPDATE + คืนเวลา SMS จริง
     */
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $info = DB::selectOne(
                'SELECT IS_NULLABLE AS nullable, EXTRA AS extra FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [$table, $column]
            );

            if ($info === null || stripos((string) $info->extra, 'on update') === false) {
                continue; // ไม่ติดกับดัก (เช่น MySQL 8 ใน CI) — ไม่ต้องแตะ
            }

            $null = $info->nullable === 'YES' ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP {$null} DEFAULT CURRENT_TIMESTAMP");
        }

        $this->restoreSmsTimestamps();
    }

    /**
     * คืน sms_timestamp เป็นเวลาที่แอพส่งมาจริง (เฉพาะแถวที่เพี้ยนเกิน 60 วินาที)
     */
    private function restoreSmsTimestamps(): void
    {
        if (! Schema::hasColumn('sms_payment_notifications', 'raw_payload')) {
            return;
        }

        $restored = 0;

        DB::table('sms_payment_notifications')
            ->whereNotNull('raw_payload')
            ->select(['id', 'sms_timestamp', 'raw_payload'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$restored) {
                foreach ($rows as $row) {
                    $payload = json_decode((string) $row->raw_payload, true);
                    $raw = is_array($payload) ? ($payload['sms_timestamp'] ?? null) : null;
                    if (! is_numeric($raw) || (float) $raw <= 0) {
                        continue;
                    }

                    // แอพส่งเป็นมิลลิวินาที — เผื่อเครื่องรุ่นเก่าส่งเป็นวินาที
                    $seconds = (float) $raw > 1e12 ? intdiv((int) $raw, 1000) : (int) $raw;
                    $real = date('Y-m-d H:i:s', $seconds);

                    if ($row->sms_timestamp !== null && abs(strtotime((string) $row->sms_timestamp) - $seconds) <= 60) {
                        continue;
                    }

                    // DB::table ไม่แตะ updated_at — แถวยังบอกได้ว่าแก้ล่าสุดเมื่อไร
                    DB::table('sms_payment_notifications')->where('id', $row->id)->update(['sms_timestamp' => $real]);
                    $restored++;
                }
            });

        Log::info('Migration: คืนเวลา SMS จริงจาก raw_payload', ['restored' => $restored]);
    }

    /**
     * ไม่ย้อนกลับ — การเติม ON UPDATE กลับคือการคืนบั๊ก และเวลา SMS ที่เพี้ยนเดิมไม่มีประโยชน์ให้คืน
     */
    public function down(): void
    {
        //
    }
};
