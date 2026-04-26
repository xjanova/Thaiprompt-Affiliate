<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\UniquePaymentAmount;
use Illuminate\Console\Command;

/**
 * fortune:debug-bill {query}
 *
 * ตรวจสอบบิลดูดวง — รับ amount หรือ bill_reference (auto-detect)
 *
 * ใช้:
 *   php artisan fortune:debug-bill 39.34
 *   php artisan fortune:debug-bill FTU-260425-T4022
 *   php artisan fortune:debug-bill FR-12345
 *   php artisan fortune:debug-bill 39.34 --hours=24
 *
 * แสดง:
 *   - FortuneReading record (สถานะ, paid_at, conversation_state)
 *   - UniquePaymentAmount link
 *   - Filter check: ผ่าน orders() filter ของ smschecker หรือไม่
 *   - คำแนะนำการ recovery
 */
class FortuneDebugBill extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:debug-bill
        {query : amount (เช่น 39.34) หรือ bill_reference (เช่น FTU-260425-T4022)}
        {--hours=48 : ดูย้อนหลังกี่ชั่วโมง (default 48)}';

    /**
     * @var string
     */
    protected $description = 'Debug บิลดูดวงตามยอด หรือ bill_reference — ดูว่าทำไมไม่ขึ้นในแอพ smschecker';

    /**
     * รัน command
     */
    public function handle(): int
    {
        $query = trim((string) $this->argument('query'));
        $hours = max(1, (int) $this->option('hours'));

        // Auto-detect: bill reference (FTU-xxx / FR-xxx) หรือ amount (numeric)
        if (preg_match('/^(FTU|FR|TXN|PRE|SEL|TAROT)-/i', $query)) {
            return $this->debugByBillReference($query, $hours);
        }

        if (is_numeric($query)) {
            return $this->debugByAmount((float) $query, $hours);
        }

        $this->error('❌ Query ไม่ถูกต้อง — ต้องเป็น amount (เช่น 39.34) หรือ bill_reference (เช่น FTU-260425-T4022)');

        return self::FAILURE;
    }

    /**
     * Debug ตาม bill_reference (เคสเร่งด่วน — ลูกค้าเห็นบิลใน chat แต่ admin ไม่เห็น)
     */
    protected function debugByBillReference(string $billRef, int $hours): int
    {
        $cutoff = now()->subHours($hours);

        $this->info('🔍 Debug Bill — '.$billRef);
        $this->info('Window: '.$hours.' ชั่วโมงย้อนหลัง');
        $this->newLine();

        // ───────────────────────────────────────────────
        // 1. FortuneReading by bill_reference
        // ───────────────────────────────────────────────
        $this->info('═══════════════════════════════════════');
        $this->info('1️⃣  FortuneReading record');
        $this->info('═══════════════════════════════════════');

        $reading = FortuneReading::where('bill_reference', $billRef)
            ->with('uniquePaymentAmount')
            ->first();

        if (! $reading) {
            $this->error('❌ ไม่พบ FortuneReading ที่ bill_reference = '.$billRef);
            $this->warn('   → บิลอาจไม่ได้ถูก commit ลง DB (transaction rollback / error)');
            $this->warn('   → ตรวจ laravel.log เวลาที่ลูกค้าเห็นข้อความ "บิลของคุณ" (grep "createPaymentBill")');

            // Fuzzy search — บางทีมี typo ใน reference
            $similar = FortuneReading::where('bill_reference', 'like', '%'.substr($billRef, -6).'%')
                ->whereNotNull('bill_reference')
                ->latest()
                ->limit(5)
                ->get(['id', 'bill_reference', 'conversation_status', 'is_paid', 'created_at']);

            if ($similar->isNotEmpty()) {
                $this->newLine();
                $this->info('🔎 บิลคล้าย ๆ ที่มีใน DB (fuzzy match):');
                $rows = $similar->map(fn ($r) => [
                    'id' => $r->id,
                    'bill_ref' => $r->bill_reference,
                    'status' => $r->conversation_status,
                    'paid' => $r->is_paid ? '✅' : '❌',
                    'created' => $r->created_at->format('m-d H:i'),
                ])->toArray();
                $this->table(['ID', 'Bill Ref', 'Status', 'Paid', 'Created'], $rows);
            }

            return self::SUCCESS;
        }

        $this->dumpReadingDetail($reading);

        // ───────────────────────────────────────────────
        // 2. UPA link
        // ───────────────────────────────────────────────
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('2️⃣  UniquePaymentAmount link');
        $this->info('═══════════════════════════════════════');

        if (! $reading->unique_payment_amount_id) {
            $this->warn('⚠️  ไม่มี unique_payment_amount_id');
            $this->warn('   → บิลถูกสร้างแบบไม่มี UPA — แอพจะกรองออก (filter เช็ค amount > 0)');
            $this->warn('   → ตรวจ createPaymentBill ใน FortuneConversationService line 3773 ว่า UPA generate ทันไหม');
        } else {
            $upa = $reading->uniquePaymentAmount;
            if (! $upa) {
                $this->error('❌ unique_payment_amount_id = '.$reading->unique_payment_amount_id.' แต่ UPA record หาย!');
                $this->warn('   → DB inconsistency — UPA ถูกลบ/cleanup แต่ FortuneReading ยังอ้างอิงอยู่');
            } else {
                $this->table(
                    ['UPA ID', 'Amount', 'Status', 'Expires', 'Matched', 'Created'],
                    [[
                        $upa->id,
                        number_format($upa->unique_amount, 2),
                        $upa->status,
                        $upa->expires_at?->format('Y-m-d H:i') ?? '-',
                        $upa->matched_at?->format('Y-m-d H:i') ?? '-',
                        $upa->created_at->format('Y-m-d H:i'),
                    ]]
                );
            }
        }

        // ───────────────────────────────────────────────
        // 3. Filter check
        // ───────────────────────────────────────────────
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('3️⃣  orders() filter check');
        $this->info('═══════════════════════════════════════');

        $this->checkOrdersFilter($reading);

        // ───────────────────────────────────────────────
        // 4. Recovery suggestions
        // ───────────────────────────────────────────────
        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('💡 คำแนะนำ');
        $this->info('═══════════════════════════════════════');

        $this->suggestRecovery($reading);

        return self::SUCCESS;
    }

    /**
     * Debug ตาม amount (เดิม)
     */
    protected function debugByAmount(float $amount, int $hours): int
    {
        $cutoff = now()->subHours($hours);

        $this->info('🔍 Debug Bill — ยอด '.number_format($amount, 2).' บาท');
        $this->info('Window: '.$hours.' ชั่วโมงย้อนหลัง');
        $this->newLine();

        $this->info('═══════════════════════════════════════');
        $this->info('1️⃣  UniquePaymentAmount records');
        $this->info('═══════════════════════════════════════');

        $upas = UniquePaymentAmount::where('unique_amount', $amount)
            ->where('created_at', '>=', $cutoff)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($upas->isEmpty()) {
            $this->warn('❌ ไม่พบ UPA ที่ยอด '.number_format($amount, 2));
            $this->warn('   → บิลอาจไม่เคยถูกสร้างจริง');

            return self::SUCCESS;
        }

        $upaRows = $upas->map(fn ($upa) => [
            'id' => $upa->id,
            'amount' => number_format($upa->unique_amount, 2),
            'status' => $upa->status,
            'type' => $upa->transaction_type,
            'expires' => $upa->expires_at?->format('m-d H:i') ?? '-',
            'created' => $upa->created_at->format('m-d H:i'),
        ])->toArray();
        $this->table(['UPA ID', 'Amount', 'Status', 'Type', 'Expires', 'Created'], $upaRows);

        $this->newLine();
        $this->info('═══════════════════════════════════════');
        $this->info('2️⃣  FortuneReading records');
        $this->info('═══════════════════════════════════════');

        $upaIds = $upas->pluck('id')->toArray();
        $readings = FortuneReading::where(function ($q) use ($amount, $upaIds, $cutoff) {
            $q->whereIn('unique_payment_amount_id', $upaIds)
                ->orWhere(function ($q2) use ($amount, $cutoff) {
                    $q2->where('amount_paid', $amount)
                        ->where('created_at', '>=', $cutoff);
                });
        })
            ->orderBy('created_at', 'desc')
            ->get();

        if ($readings->isEmpty()) {
            $this->warn('❌ ไม่พบ FortuneReading ที่ link กับยอดนี้');

            return self::SUCCESS;
        }

        foreach ($readings as $reading) {
            $this->newLine();
            $this->dumpReadingDetail($reading);
            $this->newLine();
            $this->checkOrdersFilter($reading);
        }

        return self::SUCCESS;
    }

    /**
     * แสดงรายละเอียด FortuneReading
     */
    protected function dumpReadingDetail(FortuneReading $r): void
    {
        $cancelReason = '-';
        $state = $r->conversation_state ?? [];
        if (is_array($state) && ! empty($state['cancellation_reason'])) {
            $cancelReason = $state['cancellation_reason'];
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $r->id],
                ['Bill Reference', $r->bill_reference ?? '-'],
                ['conversation_status', $r->conversation_status],
                ['is_paid', $r->is_paid ? '✅ TRUE' : '❌ FALSE'],
                ['amount_paid', number_format((float) ($r->amount_paid ?? 0), 2)],
                ['unique_payment_amount_id', $r->unique_payment_amount_id ?? '-'],
                ['platform', $r->platform ?? '-'],
                ['platform_user_id', $r->platform_user_id ?? '-'],
                ['facebook_user_id', $r->facebook_user_id ?? '-'],
                ['facebook_user_name', $r->facebook_user_name ?? '-'],
                ['cancellation_reason', $cancelReason],
                ['paid_at', $r->paid_at?->format('Y-m-d H:i:s') ?? '-'],
                ['created_at', $r->created_at->format('Y-m-d H:i:s')],
                ['updated_at', $r->updated_at->format('Y-m-d H:i:s')],
                ['has_deep_response', ! empty($r->deep_response) ? '✅' : '❌'],
                ['is_floating', ($r->is_floating ?? false) ? '⚠️ TRUE' : 'FALSE'],
            ]
        );
    }

    /**
     * เช็คว่า reading ผ่าน orders() filter หรือไม่
     */
    protected function checkOrdersFilter(FortuneReading $r): void
    {
        $this->info('แอพเรียก /api/v1/sms-payment/orders?status=waiting');
        $this->info('Filter: status IN [pending_payment, paid] OR (completed AND updated >= 24h)');
        $this->info('       AND (amount_paid > 0 OR uniquePaymentAmount.unique_amount > 0)');
        $this->newLine();

        // Status check
        $passStatus = false;
        $statusReason = '';
        if (in_array($r->conversation_status, [
            FortuneReading::STATUS_PENDING_PAYMENT,
            FortuneReading::STATUS_PAID,
        ])) {
            $passStatus = true;
            $statusReason = '✅ status='.$r->conversation_status;
        } elseif ($r->conversation_status === FortuneReading::STATUS_COMPLETED
            && $r->updated_at >= now()->subHours(24)) {
            $passStatus = true;
            $statusReason = '✅ completed within 24h';
        } else {
            $statusReason = '❌ status='.$r->conversation_status.' (updated '.$r->updated_at->diffForHumans().')';
        }

        // Amount check
        $hasAmount = ((float) ($r->amount_paid ?? 0) > 0)
            || ($r->uniquePaymentAmount && (float) $r->uniquePaymentAmount->unique_amount > 0);
        $amountReason = $hasAmount ? '✅ amount > 0' : '❌ amount = 0';

        $passed = $passStatus && $hasAmount;

        $this->info('Status check: '.$statusReason);
        $this->info('Amount check: '.$amountReason);
        $this->newLine();

        if ($passed) {
            $this->info('✅ ผ่าน filter — admin ในแอพ smschecker จะเห็นบิลนี้');
        } else {
            $this->error('🚫 ไม่ผ่าน filter — admin ในแอพจะไม่เห็นบิลนี้');
        }

        // Floating bill check (admin จะต้อง manual review)
        if ($r->is_floating ?? false) {
            $this->warn('⚠️  is_floating = TRUE — บิลนี้เป็น "บิลลอย" ต้องให้ admin assign user manual');
        }
    }

    /**
     * แนะนำการ recovery
     */
    protected function suggestRecovery(FortuneReading $r): void
    {
        $hasIssue = false;

        // Issue: Reading ถูก cancel แล้ว
        if ($r->conversation_status === FortuneReading::STATUS_COMPLETED && ! $r->is_paid) {
            $this->warn('🔧 บิลถูกยกเลิก (COMPLETED + !is_paid)');
            $this->warn('   ถ้าต้องการให้แอพลบบิลนี้ออก:');
            $this->warn('     php artisan fortune:resync-cancelled-bills --days=2');
            $hasIssue = true;
        }

        // Issue: ไม่มี UPA
        if (! $r->unique_payment_amount_id) {
            $this->warn('🔧 ไม่มี unique_payment_amount_id');
            $this->warn('   ลูกค้าจ่ายเงินไม่ได้ — บิลนี้เสีย ควรให้ลูกค้าเริ่มใหม่');
            $hasIssue = true;
        }

        // Issue: PENDING_PAYMENT แต่ UPA หมดอายุ
        if ($r->conversation_status === FortuneReading::STATUS_PENDING_PAYMENT
            && $r->uniquePaymentAmount
            && $r->uniquePaymentAmount->expires_at < now()) {
            $this->warn('🔧 PENDING_PAYMENT แต่ UPA หมดอายุแล้ว');
            $this->warn('   รัน: php artisan fortune:expire-conversations');
            $hasIssue = true;
        }

        // Issue: PAID แต่ไม่มี deep_response
        if ($r->conversation_status === FortuneReading::STATUS_PAID && empty($r->deep_response)) {
            $waitedMin = (int) abs($r->updated_at->diffInMinutes(now(), true));
            $this->warn("🔧 PAID แต่ยังไม่มี deep_response (รอมา {$waitedMin} นาที)");
            $this->warn('   AI processing อาจ stuck — เช็ค queue worker หรือ rerun:');
            $this->warn('     php artisan fortune:process-deep-reading '.$r->id);
            $hasIssue = true;
        }

        // Issue: Floating bill
        if ($r->is_floating ?? false) {
            $this->warn('🔧 is_floating = TRUE — assign user manual ผ่าน admin panel');
            $hasIssue = true;
        }

        if (! $hasIssue) {
            $this->info('✅ ไม่พบปัญหา — บิลควรขึ้นที่แอพปกติ');
            $this->info('   ถ้าแอพยังไม่เห็น → ตรวจ FCM token / ลอง force sync ในแอพ');
        }
    }
}
