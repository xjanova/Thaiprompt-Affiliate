<?php

namespace App\Console\Commands;

use App\Models\FortuneReading;
use App\Models\UniquePaymentAmount;
use Illuminate\Console\Command;

/**
 * fortune:debug-bill {amount}
 *
 * ตรวจสอบบิลดูดวงตามจำนวนเงิน เพื่อ debug ว่าทำไมไม่ขึ้นในแอพ smschecker
 *
 * แสดง:
 *   - UniquePaymentAmount records ที่ตรงกับ amount (ทุกสถานะ)
 *   - FortuneReading records ที่ link กับ UPA นั้น
 *   - สถานะ conversation_status, is_paid, bill_reference
 *   - cancellation_reason ถ้ามี
 *   - filter check: ผ่าน orders() filter ของ smschecker หรือไม่
 *
 * ใช้:
 *   php artisan fortune:debug-bill 39.34
 *   php artisan fortune:debug-bill 39.34 --hours=24
 */
class FortuneDebugBill extends Command
{
    /**
     * @var string
     */
    protected $signature = 'fortune:debug-bill
        {amount : ยอดเงินที่ต้องการตรวจ (เช่น 39.34)}
        {--hours=48 : ดูย้อนหลังกี่ชั่วโมง (default 48)}';

    /**
     * @var string
     */
    protected $description = 'Debug บิลดูดวงตามยอดเงิน — ดูว่าทำไมไม่ขึ้นในแอพ smschecker';

    /**
     * รัน command
     */
    public function handle(): int
    {
        $amount = (float) $this->argument('amount');
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $this->info('🔍 Debug Bill — ยอด '.number_format($amount, 2).' บาท');
        $this->info('Window: '.$hours.' ชั่วโมงย้อนหลัง (ตั้งแต่ '.$cutoff->format('Y-m-d H:i').')');
        $this->info('');

        // ───────────────────────────────────────────────
        // 1. UniquePaymentAmount records
        // ───────────────────────────────────────────────
        $this->info('═══════════════════════════════════════');
        $this->info('1️⃣  UniquePaymentAmount records');
        $this->info('═══════════════════════════════════════');

        $upas = UniquePaymentAmount::where('unique_amount', $amount)
            ->where('created_at', '>=', $cutoff)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($upas->isEmpty()) {
            $this->warn('❌ ไม่พบ UniquePaymentAmount ที่ยอด '.number_format($amount, 2).' บาท ใน window '.$hours.' ชม.');
            $this->warn('   → บิลอาจไม่เคยถูกสร้างจริง (ลูกค้าไม่ได้กดยืนยันคำถาม + ตั้งจิตเปิดไพ่)');
            $this->warn('   → หรือ amount ไม่ตรงกับ unique_amount (ทศนิยม) ที่ระบบ generate');
            $this->newLine();

            return self::SUCCESS;
        }

        $upaRows = $upas->map(function ($upa) {
            return [
                'id' => $upa->id,
                'amount' => number_format($upa->unique_amount, 2),
                'status' => $upa->status,
                'type' => $upa->transaction_type,
                'transaction_id' => $upa->transaction_id,
                'expires_at' => $upa->expires_at?->format('Y-m-d H:i'),
                'matched_at' => $upa->matched_at?->format('Y-m-d H:i') ?? '-',
                'created_at' => $upa->created_at->format('Y-m-d H:i'),
            ];
        })->toArray();

        $this->table(
            ['UPA ID', 'Amount', 'Status', 'Type', 'TXN ID', 'Expires', 'Matched', 'Created'],
            $upaRows
        );

        // ───────────────────────────────────────────────
        // 2. FortuneReading records
        // ───────────────────────────────────────────────
        $this->info('');
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
            $this->warn('   → UPA ถูกสร้าง แต่ไม่มี FortuneReading ผูก (data corruption)');
            $this->warn('   → ตรวจ log: createPaymentBill ที่ reading_id ที่อ้างอิง UPA ID '.$upas->first()->id);
            $this->newLine();

            return self::SUCCESS;
        }

        $readingRows = $readings->map(function ($r) {
            $cancelReason = '-';
            $state = $r->conversation_state ?? [];
            if (is_array($state) && ! empty($state['cancellation_reason'])) {
                $cancelReason = $state['cancellation_reason'];
            }

            return [
                'ID' => $r->id,
                'Bill Ref' => $r->bill_reference ?? '-',
                'Status' => $r->conversation_status,
                'Paid' => $r->is_paid ? '✅' : '❌',
                'UPA ID' => $r->unique_payment_amount_id ?? '-',
                'Cancel Reason' => $cancelReason,
                'Paid At' => $r->paid_at?->format('m-d H:i') ?? '-',
                'Updated' => $r->updated_at->format('m-d H:i'),
            ];
        })->toArray();

        $this->table(
            ['ID', 'Bill Ref', 'Status', 'Paid', 'UPA ID', 'Cancel Reason', 'Paid At', 'Updated'],
            $readingRows
        );

        // ───────────────────────────────────────────────
        // 3. orders() filter check
        // ───────────────────────────────────────────────
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('3️⃣  orders() endpoint filter check');
        $this->info('═══════════════════════════════════════');
        $this->info('แอพเรียก /api/v1/sms-payment/orders?status=waiting');
        $this->info('Filter: conversation_status IN [pending_payment, paid] OR (completed AND updated >= 24h)');
        $this->info('       AND (amount_paid > 0 OR uniquePaymentAmount.unique_amount > 0)');
        $this->newLine();

        $shownInOrders = [];
        $hiddenFromOrders = [];

        foreach ($readings as $r) {
            $passStatus = false;
            $reason = '';

            if (in_array($r->conversation_status, [
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_PAID,
            ])) {
                $passStatus = true;
                $reason = 'status='.$r->conversation_status;
            } elseif ($r->conversation_status === FortuneReading::STATUS_COMPLETED
                && $r->updated_at >= now()->subHours(24)) {
                $passStatus = true;
                $reason = 'completed within 24h';
            } else {
                $reason = 'status='.$r->conversation_status.', updated '.$r->updated_at->diffForHumans();
            }

            $hasAmount = ((float) ($r->amount_paid ?? 0) > 0)
                || ($r->uniquePaymentAmount && (float) $r->uniquePaymentAmount->unique_amount > 0);

            if ($passStatus && $hasAmount) {
                $shownInOrders[] = ['ID' => $r->id, 'Bill' => $r->bill_reference ?? '-', 'ผ่าน' => $reason];
            } else {
                $why = ! $passStatus ? "❌ status fail ({$reason})" : '❌ amount=0';
                $hiddenFromOrders[] = ['ID' => $r->id, 'Bill' => $r->bill_reference ?? '-', 'เหตุผล' => $why];
            }
        }

        if (! empty($shownInOrders)) {
            $this->info('✅ บิลที่ admin ในแอพ smschecker จะเห็น:');
            $this->table(['ID', 'Bill', 'ผ่านเงื่อนไข'], $shownInOrders);
        } else {
            $this->warn('⚠️  ไม่มีบิลใดผ่าน orders() filter — admin จะไม่เห็นเลย');
        }

        if (! empty($hiddenFromOrders)) {
            $this->newLine();
            $this->warn('🚫 บิลที่ถูกซ่อนจาก orders():');
            $this->table(['ID', 'Bill', 'เหตุผล'], $hiddenFromOrders);
        }

        // ───────────────────────────────────────────────
        // 4. คำแนะนำ
        // ───────────────────────────────────────────────
        $this->info('');
        $this->info('═══════════════════════════════════════');
        $this->info('💡 คำแนะนำ');
        $this->info('═══════════════════════════════════════');

        $pendingCount = $readings->where('conversation_status', FortuneReading::STATUS_PENDING_PAYMENT)->count();
        if ($pendingCount > 1) {
            $this->warn("⚠️  พบ {$pendingCount} บิล PENDING_PAYMENT พร้อมกัน — ลูกค้าอาจสร้างซ้อน");
            $this->warn('   → แนะนำให้รัน: php artisan fortune:expire-conversations');
        }

        $awaitingTarot = FortuneReading::where(function ($q) use ($amount, $cutoff) {
            $q->where('amount_paid', $amount);
        })
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_COLLECTING_QUESTIONS,
                FortuneReading::STATUS_COLLECTING_TAROT,
            ])
            ->where('created_at', '>=', $cutoff)
            ->count();

        if ($awaitingTarot > 0) {
            $this->warn("⚠️  พบ {$awaitingTarot} reading ที่ยังเก็บคำถาม/รอเปิดไพ่ ยังไม่สร้างบิล");
            $this->warn('   → ลูกค้ายังไม่กด "พร้อม" / "เปิดไพ่" → UPA ยังไม่ถูก generate → admin ยังไม่เห็น');
        }

        $this->info('');
        $this->info('🔧 ถ้าต้อง resync บิลทั้งหมดให้แอพ:');
        $this->info('   php artisan fortune:resync-cancelled-bills --days=7');
        $this->info('');

        return self::SUCCESS;
    }
}
