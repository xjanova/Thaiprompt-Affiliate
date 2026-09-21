<?php

namespace App\Services;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * งานแอดมินของค่าแนะนำดูดวง — ที่เดียวที่แก้สถานะ/ยอด/จ่ายเงินค่าแนะนำด้วยมือ
 *
 * 🌙 (2026-09-21) ย้ายออกมาจาก Admin\FortuneCommissionController ให้หลังบ้านแม่หมอ
 *   และหลังบ้านจันทรา (ผ่าน /juntra/server/affiliate/admin/*) ใช้กติกาชุดเดียวกัน
 *   พร้อมแก้สองจุดที่เคยทำเงินเพี้ยน:
 *     - จ่ายเงิน: เดิมไม่ล็อกทั้งแถวค่าแนะนำและกระเป๋า → กดซ้ำ/สองแท็บ = จ่ายซ้ำ ยอดกระเป๋าหาย
 *     - ปรับยอด: เดิมแก้ได้ทุกสถานะ → แก้รายการที่จ่ายเข้ากระเป๋าแล้ว ยอดกับกระเป๋าไม่ตรงกันอีก
 */
class FortuneCommissionAdminService
{
    /**
     * @param  array{status?: ?string, level?: int|string|null, date_from?: ?string, date_to?: ?string,
     *               search?: ?string, source?: ?string}  $filters
     */
    public function query(array $filters): Builder
    {
        $query = FortuneCommission::query();

        $status = $filters['status'] ?? null;
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $level = $filters['level'] ?? null;
        if ($level && $level !== 'all') {
            $query->where('level', (int) $level);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // ที่มาของบิล: juntra = บิลเว็บ/แอพจันทรา · maemor = บิลบอทแม่หมอ
        $source = $filters['source'] ?? null;
        if ($source === 'juntra' || $source === 'maemor') {
            $method = $source === 'juntra' ? 'whereHas' : 'whereDoesntHave';
            $query->{$method}('reading', fn ($q) => $q->where('reading_type', FortuneReading::READING_TYPE_JUNTRA));
        }

        $search = $filters['search'] ?? null;
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQ) use ($search) {
                    $userQ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                    ->orWhereHas('fromUser', function ($userQ) use ($search) {
                        $userQ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    /**
     * สถิติรูปเดียวกับ FortuneCommission::getStats แต่นับจาก query ที่ส่งมา
     *   (หลังบ้านจันทราเห็นเฉพาะค่าแนะนำจากบิลจันทรา — getStats นับทั้งระบบ)
     */
    public function statsFor(Builder $base): array
    {
        $sum = fn (?string $status = null, ?int $level = null) => (float) (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->sum('amount');
        $count = fn (?string $status = null, ?int $level = null) => (clone $base)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($level, fn ($q) => $q->where('level', $level))
            ->count();

        return [
            'total_count' => $count(),
            'total_amount' => $sum(),
            'l1_count' => $count(null, 1),
            'l1_amount' => $sum(null, 1),
            'l2_count' => $count(null, 2),
            'l2_amount' => $sum(null, 2),
            'pending_count' => $count(FortuneCommission::STATUS_PENDING),
            'pending_amount' => $sum(FortuneCommission::STATUS_PENDING),
            'approved_count' => $count(FortuneCommission::STATUS_APPROVED),
            'approved_amount' => $sum(FortuneCommission::STATUS_APPROVED),
            'paid_count' => $count(FortuneCommission::STATUS_PAID),
            'paid_amount' => $sum(FortuneCommission::STATUS_PAID),
            'rejected_count' => $count(FortuneCommission::STATUS_REJECTED),
        ];
    }

    /** อนุมัติ (pending → approved) — คืนจำนวนที่อนุมัติได้จริง */
    public function approve(array $ids): int
    {
        $count = 0;
        foreach (FortuneCommission::whereIn('id', $ids)->get() as $commission) {
            if ($commission->approve()) {
                $count++;
            }
        }

        Log::info('FortuneCommission Admin: อนุมัติ bulk', ['count' => $count, 'ids' => $ids]);

        return $count;
    }

    /** ปฏิเสธ (pending เท่านั้น — รายการที่จ่ายแล้วดึงคืนผ่านการยกเลิกบิล) */
    public function reject(FortuneCommission $commission, ?string $reason): bool
    {
        $ok = $commission->reject($reason);

        if ($ok) {
            Log::info('FortuneCommission Admin: ปฏิเสธ', [
                'commission_id' => $commission->id,
                'reason' => $reason ?? '-',
            ]);
        }

        return $ok;
    }

    /**
     * ปรับจำนวนเงิน — เฉพาะรายการที่ยังไม่เข้ากระเป๋า (pending/approved)
     *
     * @return string|null ข้อความผิดพลาด (null = สำเร็จ)
     */
    public function adjust(FortuneCommission $commission, float $newAmount, ?string $reason): ?string
    {
        return DB::transaction(function () use ($commission, $newAmount, $reason) {
            $locked = FortuneCommission::whereKey($commission->id)->lockForUpdate()->first();

            if (! $locked || ! in_array($locked->status, [FortuneCommission::STATUS_PENDING, FortuneCommission::STATUS_APPROVED], true)) {
                return 'ปรับยอดได้เฉพาะรายการที่ยังไม่จ่าย — รายการนี้สถานะ '.($locked?->status_name ?? '-');
            }

            $oldAmount = $locked->amount;
            $locked->update([
                'amount' => $newAmount,
                'notes' => ($locked->notes ? $locked->notes."\n" : '')
                    ."[ปรับจำนวน] {$oldAmount} → {$newAmount} บาท"
                    .($reason ? " เหตุผล: {$reason}" : ''),
            ]);

            Log::info('FortuneCommission Admin: ปรับจำนวนเงิน', [
                'commission_id' => $locked->id,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
                'reason' => $reason ?? '-',
            ]);

            $commission->setRawAttributes($locked->getAttributes(), true);

            return null;
        });
    }

    /**
     * จ่ายเข้ากระเป๋า (pending/approved → paid)
     *
     * 🔒 ล็อกแถวค่าแนะนำก่อนตัดสินสถานะ แล้วล็อกกระเป๋าก่อนอ่านยอด — แบบเดียวกับ
     *    FortuneCommissionService::depositToWallet · กดซ้ำ/สองแท็บ = คำขอหลังเห็นว่าจ่ายไปแล้ว
     *
     * @return array{count: int, errors: array<int, string>}
     */
    public function payOut(array $ids): array
    {
        $count = 0;
        $errors = [];

        foreach (array_unique(array_map('intval', $ids)) as $id) {
            try {
                $paid = DB::transaction(function () use ($id) {
                    $commission = FortuneCommission::whereKey($id)->lockForUpdate()->first();
                    if (! $commission
                        || ! in_array($commission->status, [FortuneCommission::STATUS_PENDING, FortuneCommission::STATUS_APPROVED], true)) {
                        return false;
                    }

                    $amount = (float) $commission->amount;
                    if ($amount <= 0) {
                        return false;
                    }

                    $wallet = Wallet::where('user_id', $commission->user_id)->lockForUpdate()->first();
                    if (! $wallet) {
                        $wallet = Wallet::create([
                            'user_id' => $commission->user_id,
                            'balance' => 0,
                            'currency' => 'THB',
                            'status' => 'active',
                        ]);
                        $wallet->refresh();
                    }

                    if ($wallet->status !== 'active') {
                        throw new \RuntimeException("Wallet ไม่ active สำหรับ user {$commission->user_id}");
                    }

                    $balanceBefore = (float) $wallet->balance;
                    $balanceAfter = $balanceBefore + $amount;

                    $transaction = WalletTransaction::create([
                        'wallet_id' => $wallet->id,
                        'user_id' => $wallet->user_id,
                        'type' => 'deposit',
                        'amount' => $amount,
                        'balance_before' => $balanceBefore,
                        'balance_after' => $balanceAfter,
                        'currency' => $wallet->currency,
                        'description' => "จ่ายคอมมิชชั่นดูดวง L{$commission->level} #{$commission->id} (Admin)",
                        'reference_type' => FortuneCommission::class,
                        'reference_id' => $commission->id,
                        'status' => 'completed',
                        'metadata' => [
                            'commission_id' => $commission->id,
                            'level' => $commission->level,
                            'mode' => 'fortune_commission_admin_payout',
                        ],
                        'completed_at' => now(),
                    ]);

                    $wallet->update([
                        'balance' => $balanceAfter,
                        'total_income' => (float) ($wallet->total_income ?? 0) + $amount,
                        'last_transaction_at' => now(),
                    ]);

                    $commission->update([
                        'status' => FortuneCommission::STATUS_PAID,
                        'paid_at' => now(),
                        'wallet_transaction_id' => $transaction->id,
                    ]);

                    return true;
                });

                if ($paid) {
                    $count++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Commission #{$id}: {$e->getMessage()}";
                Log::error('FortuneCommission Admin: จ่ายล้มเหลว', [
                    'commission_id' => $id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['count' => $count, 'errors' => $errors];
    }

    /**
     * สร้างค่าแนะนำด้วยมือ (สถานะ pending — ต้องกดจ่ายแยก)
     *
     * @param  array{user_id: int, from_user_id: int, fortune_reading_id: int, level: int, amount: float, notes?: ?string}  $data
     */
    public function createManual(array $data): FortuneCommission
    {
        $commission = FortuneCommission::create([
            'user_id' => $data['user_id'],
            'from_user_id' => $data['from_user_id'],
            'fortune_reading_id' => $data['fortune_reading_id'],
            'mlm_member_id' => MlmMember::where('user_id', $data['user_id'])->value('id'),
            'from_mlm_member_id' => MlmMember::where('user_id', $data['from_user_id'])->value('id'),
            'level' => (int) $data['level'],
            'commission_type' => 'fixed',
            'commission_rate' => (float) $data['amount'],
            'amount' => (float) $data['amount'],
            'reading_price' => 0,
            'status' => FortuneCommission::STATUS_PENDING,
            'notes' => '[สร้างด้วยมือ] '.($data['notes'] ?? 'สร้างโดย Admin'),
        ]);

        Log::info('FortuneCommission Admin: สร้างด้วยมือ', [
            'commission_id' => $commission->id,
            'user_id' => $data['user_id'],
            'amount' => $data['amount'],
        ]);

        return $commission;
    }

    /** อัตราค่าแนะนำทั้งหมดที่แอดมินตั้งได้ (บอท + บิลจันทรา) */
    public function rates(FortuneTellingSetting $settings): array
    {
        return [
            'fortune_affiliate_enabled' => (bool) $settings->isFortuneAffiliateEnabled(),
            'fortune_level1_commission_type' => $settings->fortune_level1_commission_type ?? 'fixed',
            'fortune_level1_commission_amount' => (float) ($settings->fortune_level1_commission_amount ?? 10),
            'fortune_level2_enabled' => (bool) ($settings->fortune_level2_enabled ?? true),
            'fortune_level2_commission_type' => $settings->fortune_level2_commission_type ?? 'fixed',
            'fortune_level2_commission_amount' => (float) ($settings->fortune_level2_commission_amount ?? 5),
            'fortune_juntra_l1_percent' => (float) ($settings->fortune_juntra_l1_percent ?? 10),
            'fortune_juntra_l2_enabled' => (bool) ($settings->fortune_juntra_l2_enabled ?? true),
            'fortune_juntra_l2_percent' => (float) ($settings->fortune_juntra_l2_percent ?? 5),
            'fortune_central_fallback_enabled' => (bool) $settings->isFortuneCentralFallbackEnabled(),
        ];
    }

    /** บันทึกอัตรา — รับเฉพาะคีย์ที่ส่งมา (ไม่แตะคีย์อื่นของ settings) */
    public function updateRates(FortuneTellingSetting $settings, array $values): void
    {
        $settings->update($values);
        FortuneTellingSetting::clearSettingsCache();

        Log::info('FortuneCommission Admin: อัพเดทการตั้งค่า', $values);
    }
}
