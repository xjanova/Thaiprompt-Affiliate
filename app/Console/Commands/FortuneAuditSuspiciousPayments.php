<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use Illuminate\Console\Command;

/**
 * ตรวจหา fortune readings ที่อาจถูก mark paid ผิดจากบั๊กเก่า
 *
 * บั๊กเก่า (ก่อน 2026-04-28):
 *   - SMS เก่ามา match กับบิลใหม่ที่ลูกค้ายังไม่ได้โอน
 *   - signs:
 *     a) sms.sms_timestamp < reading.created_at  (SMS มาก่อนบิล)
 *     b) sms.sms_timestamp > UPA.expires_at เกิน grace period
 *     c) UPA ที่ใช้ยอดเดียวกันถูก used หลายครั้ง (suffix reuse + match ผิด)
 *
 * Output: รายงาน + (option) export CSV
 */
class FortuneAuditSuspiciousPayments extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:audit-suspicious-payments
                            {--days=30 : ตรวจย้อนหลังกี่วัน}
                            {--export= : path สำหรับ export CSV (เช่น storage/audit.csv)}
                            {--fix : อัพเดท reading ที่พบให้กลับเป็น pending_payment + is_paid=false (ต้อง confirm)}';

    /**
     * @var string
     */
    protected $description = 'ตรวจหา fortune readings ที่อาจถูก mark paid ผิดจากบั๊ก SMS matching เก่า';

    /**
     * รัน command
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $exportPath = $this->option('export');
        $fix = (bool) $this->option('fix');

        $this->info("🔍 ตรวจ fortune readings ย้อนหลัง {$days} วัน...");

        $cutoff = now()->subDays($days);

        // ดึง paid readings ที่มี sms_notification_id
        $readings = FortuneReading::with('user')
            ->where('is_paid', true)
            ->whereNotNull('sms_notification_id')
            ->where('paid_at', '>=', $cutoff)
            ->orderBy('paid_at', 'desc')
            ->get();

        $this->info("รวม {$readings->count()} readings ที่ชำระแล้วในช่วงเวลานี้");

        $suspicious = [];
        foreach ($readings as $reading) {
            $sms = SmsPaymentNotification::find($reading->sms_notification_id);
            if (! $sms) {
                continue;
            }

            $reasons = [];

            // Sign A: SMS มาก่อน reading ถูกสร้าง
            if ($sms->sms_timestamp && $sms->sms_timestamp->lt($reading->created_at)) {
                $reasons[] = 'sms_before_bill';
            }

            // Sign B: ยอดของ SMS ตรงกับ UPA หลายตัว (suffix reuse)
            if ($reading->unique_payment_amount_id) {
                $upa = UniquePaymentAmount::find($reading->unique_payment_amount_id);
                if ($upa) {
                    $duplicateCount = UniquePaymentAmount::where('unique_amount', $upa->unique_amount)
                        ->where('transaction_type', 'fortune_reading')
                        ->where('id', '!=', $upa->id)
                        ->where('created_at', '<=', $reading->created_at)
                        ->count();

                    if ($duplicateCount > 0) {
                        $reasons[] = "ambiguous_amount_{$duplicateCount}_dupes";
                    }
                }
            }

            // Sign C: SMS อันเดียวกันถูก match กับ readings หลายตัว (ไม่ควรเกิด)
            $smsMatchCount = FortuneReading::where('sms_notification_id', $sms->id)->count();
            if ($smsMatchCount > 1) {
                $reasons[] = "sms_matched_to_{$smsMatchCount}_readings";
            }

            if (! empty($reasons)) {
                $suspicious[] = [
                    'reading_id' => $reading->id,
                    'bill_reference' => $reading->bill_reference,
                    'user_name' => $reading->facebook_user_name ?? 'ไม่ระบุ',
                    'amount' => $reading->amount_paid,
                    'reading_created' => $reading->created_at->toDateTimeString(),
                    'sms_timestamp' => $sms->sms_timestamp?->toDateTimeString() ?? '-',
                    'paid_at' => $reading->paid_at?->toDateTimeString() ?? '-',
                    'reasons' => implode(', ', $reasons),
                ];
            }
        }

        if (empty($suspicious)) {
            $this->info('✅ ไม่พบ readings ที่น่าสงสัย — ระบบปลอดภัย');
            return Command::SUCCESS;
        }

        $count = count($suspicious);
        $this->warn("⚠️ พบ readings น่าสงสัย {$count} รายการ");
        $this->table(
            ['Reading ID', 'Bill Ref', 'User', 'Amount', 'Created', 'SMS Time', 'Paid At', 'Reasons'],
            array_map(function ($r) {
                return [
                    $r['reading_id'],
                    $r['bill_reference'] ?? '-',
                    mb_substr($r['user_name'], 0, 20),
                    '฿' . $r['amount'],
                    $r['reading_created'],
                    $r['sms_timestamp'],
                    $r['paid_at'],
                    $r['reasons'],
                ];
            }, $suspicious)
        );

        // Export CSV
        if ($exportPath) {
            $fp = fopen($exportPath, 'w');
            fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($fp, ['Reading ID', 'Bill Ref', 'User', 'Amount', 'Created', 'SMS Time', 'Paid At', 'Reasons']);
            foreach ($suspicious as $r) {
                fputcsv($fp, $r);
            }
            fclose($fp);
            $this->info("📄 Export CSV → {$exportPath}");
        }

        // Fix mode — กลับเป็น pending_payment
        if ($fix) {
            if (! $this->confirm("⚠️ ต้องการ rollback {$count} readings เป็น pending_payment + is_paid=false?")) {
                $this->info('ยกเลิก fix');
                return Command::SUCCESS;
            }

            $fixed = 0;
            foreach ($suspicious as $r) {
                $reading = FortuneReading::find($r['reading_id']);
                if (! $reading) continue;

                $reading->update([
                    'is_paid' => false,
                    'paid_at' => null,
                    'conversation_status' => FortuneReading::STATUS_PENDING_PAYMENT,
                ]);
                $reading->setConversationState('audit_rolled_back', now()->toIso8601String());
                $reading->setConversationState('audit_reasons', $r['reasons']);
                $fixed++;
            }
            $this->info("🔧 Rollback เสร็จ {$fixed} รายการ");
        }

        return Command::SUCCESS;
    }
}
