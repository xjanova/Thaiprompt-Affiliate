<?php

namespace App\Services;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\MlmMember;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FortuneCommissionService
 *
 * จัดการคอมมิชชั่นจากบิลดูดวง (แยกจากระบบ MLM commissions)
 * - Level 1 (สายตรง): จ่ายให้ sponsor ตรง
 * - Level 2 (ชั้นหลาน): จ่ายให้ sponsor ของ sponsor
 *
 * ใช้ผัง mlm_members เดิม แต่บันทึกคอมมิชชั่นใน fortune_commissions
 * ไม่กระทบ mlm_commissions (ระบบซื้อของจ่ายแยกกัน)
 */
class FortuneCommissionService
{
    /**
     * จ่ายคอมมิชชั่นดูดวงให้ Level 1 + Level 2
     *
     * Flow:
     * 1. ตรวจสอบว่าบิลนี้จ่ายไปแล้วหรือยัง (ป้องกันซ้ำ)
     * 2. หา sponsor (Level 1) → คำนวณ + จ่าย
     * 3. หา grandparent (Level 2) → คำนวณ + จ่าย (ถ้าเปิด)
     *
     * @param FortuneReading $reading บิลดูดวงที่ชำระเงินแล้ว
     * @param MlmMember $mlmMember สมาชิก MLM ของคนดูดวง
     * @param FortuneTellingSetting $settings การตั้งค่าดูดวง
     */
    public function distributeCommissions(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings
    ): void {
        // ตรวจสอบว่าบิลชำระเงินแล้ว (ไม่จ่ายคอมมิชชั่นสำหรับบิลฟรี)
        if (! $reading->is_paid || (float) ($reading->amount_paid ?? 0) <= 0) {
            Log::debug('FortuneCommission: บิลยังไม่ชำระเงินหรือจำนวน 0 ข้าม', [
                'reading_id' => $reading->id,
                'is_paid' => $reading->is_paid,
                'amount_paid' => $reading->amount_paid,
            ]);

            return;
        }

        // ใช้ amount_paid จริง, fallback ไปที่ deep_reading_price (กรณี amount_paid ยังไม่อัพเดท)
        $readingPrice = (float) ($reading->amount_paid ?? 0);
        if ($readingPrice <= 0) {
            $readingPrice = (float) ($settings->deep_reading_price ?? 0);
        }

        // 🐛 Fix 2026-06-12: เช็คจ่ายซ้ำแยกราย level (เดิมเช็ครวมทั้งบิล —
        // ถ้า L1 สำเร็จแต่ L2 fail การ retry จะถูกข้ามทั้งบิล L2 ไม่มีวันได้จ่าย)
        // + แยก try/catch ราย level — L1 ล้มเหลวต้องไม่บล็อก L2
        // (unique index fc_reading_user_level_unique เป็น backstop กัน race ที่ DB)

        // ===== Level 1: จ่ายให้ sponsor ตรง =====
        if ($this->levelAlreadyPaid($reading, 1)) {
            Log::info('FortuneCommission [L1]: บิลนี้จ่าย L1 ไปแล้ว ข้าม', ['reading_id' => $reading->id]);
        } else {
            try {
                $this->payLevel1($reading, $mlmMember, $settings, $readingPrice);
            } catch (\Throwable $l1Err) {
                Log::error('FortuneCommission [L1]: จ่ายล้มเหลว (ไม่บล็อก L2)', [
                    'reading_id' => $reading->id,
                    'error' => $l1Err->getMessage(),
                ]);
            }
        }

        // ===== Level 2: จ่ายให้ grandparent (ถ้าเปิด) =====
        if ($settings->isFortuneLevel2Enabled()) {
            if ($this->levelAlreadyPaid($reading, 2)) {
                Log::info('FortuneCommission [L2]: บิลนี้จ่าย L2 ไปแล้ว ข้าม', ['reading_id' => $reading->id]);
            } else {
                try {
                    $this->payLevel2($reading, $mlmMember, $settings, $readingPrice);
                } catch (\Throwable $l2Err) {
                    Log::error('FortuneCommission [L2]: จ่ายล้มเหลว', [
                        'reading_id' => $reading->id,
                        'error' => $l2Err->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * ตรวจว่าบิลนี้จ่ายคอมมิชชั่น level นี้ไปแล้วหรือยัง (กันจ่ายซ้ำราย level)
     */
    protected function levelAlreadyPaid(FortuneReading $reading, int $level): bool
    {
        return FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', $level)
            ->exists();
    }

    /**
     * จ่ายคอมมิชชั่น Level 1 (สายตรง)
     *
     * ถ้าหา sponsor ไม่ได้ (ไม่มีผู้แนะนำ / ไม่ active / user หาย):
     *   → fallback เข้ากระเป๋ากลาง (ถ้าเปิด isFortuneCentralFallbackEnabled)
     */
    protected function payLevel1(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings,
        float $readingPrice
    ): void {
        // คำนวณ amount ก่อน — ใช้ทั้ง path sponsor และ central fallback
        $commissionAmount = $settings->getFortuneLevel1Amount($readingPrice);
        if ($commissionAmount <= 0) {
            return;
        }

        $commissionType = $settings->getFortuneLevel1CommissionType();
        $commissionRate = (float) ($settings->fortune_level1_commission_amount ?? 10);

        // หา sponsor (ผู้แนะนำตรง)
        if (! $mlmMember->unilevel_sponsor_id) {
            Log::info('FortuneCommission [L1]: สมาชิกไม่มีผู้แนะนำ → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'mlm_member_id' => $mlmMember->id,
            ]);
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 1,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: 'no_referrer',
            );

            return;
        }

        $sponsor = MlmMember::with('user')->find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->user) {
            Log::info('FortuneCommission [L1]: ผู้แนะนำไม่พบหรือไม่มี user → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'sponsor_id' => $mlmMember->unilevel_sponsor_id,
            ]);
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 1,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: 'sponsor_missing',
            );

            return;
        }

        // เช็ค active: ผู้แนะนำต้อง active (ไม่ roll up — กฎดูดวง)
        $isActive = \App\Helpers\MlmRetentionHelper::isMemberActive($sponsor);
        if (! $isActive) {
            Log::info('FortuneCommission [L1]: ผู้แนะนำไม่ active → fallback กระเป๋ากลาง (ไม่ roll up)', [
                'reading_id' => $reading->id,
                'sponsor_id' => $sponsor->id,
            ]);
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 1,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: 'sponsor_inactive',
            );

            return;
        }

        // สร้าง record + จ่ายเข้า wallet (sponsor ตรง)
        $this->createCommissionAndPay(
            reading: $reading,
            recipientMember: $sponsor,
            fromMember: $mlmMember,
            level: 1,
            commissionType: $commissionType,
            commissionRate: $commissionRate,
            amount: $commissionAmount,
            readingPrice: $readingPrice,
        );

        Log::info('FortuneCommission [L1/สายตรง]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'buyer_user_id' => $reading->user_id,
            'sponsor_user_id' => $sponsor->user_id,
            'type' => $commissionType,
            'rate' => $commissionRate,
            'amount' => $commissionAmount,
            'reading_price' => $readingPrice,
        ]);
    }

    /**
     * จ่ายคอมมิชชั่น Level 2 (ชั้นหลาน)
     *
     * ถ้าหา grandparent ไม่ได้ (ไม่มี / ไม่ active / user หาย):
     *   → fallback เข้ากระเป๋ากลาง (ถ้าเปิด isFortuneCentralFallbackEnabled)
     */
    protected function payLevel2(
        FortuneReading $reading,
        MlmMember $mlmMember,
        FortuneTellingSetting $settings,
        float $readingPrice
    ): void {
        // คำนวณ amount ก่อน — ใช้ทั้ง path grandparent และ central fallback
        $commissionAmount = $settings->getFortuneLevel2Amount($readingPrice);
        if ($commissionAmount <= 0) {
            return;
        }

        $commissionType = $settings->getFortuneLevel2CommissionType();
        $commissionRate = (float) ($settings->fortune_level2_commission_amount ?? 5);

        // helper closure สำหรับ fallback
        $fallback = function (string $reason) use (
            $reading, $mlmMember, $settings, $commissionType, $commissionRate, $commissionAmount, $readingPrice
        ) {
            $this->payToCentralWallet(
                $reading, $mlmMember, $settings,
                level: 2,
                commissionType: $commissionType,
                commissionRate: $commissionRate,
                amount: $commissionAmount,
                readingPrice: $readingPrice,
                reason: $reason,
            );
        };

        // หา sponsor ตรง
        if (! $mlmMember->unilevel_sponsor_id) {
            Log::info('FortuneCommission [L2]: ไม่มีผู้แนะนำ → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
            ]);
            $fallback('no_referrer');

            return;
        }

        $sponsor = MlmMember::find($mlmMember->unilevel_sponsor_id);
        if (! $sponsor || ! $sponsor->unilevel_sponsor_id) {
            Log::info('FortuneCommission [L2]: ไม่มี grandparent → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
            ]);
            $fallback('no_grandparent');

            return;
        }

        // หา grandparent (sponsor ของ sponsor)
        $grandparent = MlmMember::with('user')->find($sponsor->unilevel_sponsor_id);
        if (! $grandparent || ! $grandparent->user) {
            Log::info('FortuneCommission [L2]: grandparent ไม่พบหรือไม่มี user → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'grandparent_id' => $sponsor->unilevel_sponsor_id,
            ]);
            $fallback('grandparent_missing');

            return;
        }

        // เช็ค active
        $isActive = \App\Helpers\MlmRetentionHelper::isMemberActive($grandparent);
        if (! $isActive) {
            Log::info('FortuneCommission [L2]: grandparent ไม่ active → fallback กระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'grandparent_id' => $grandparent->id,
            ]);
            $fallback('grandparent_inactive');

            return;
        }

        // สร้าง record + จ่ายเข้า wallet
        $this->createCommissionAndPay(
            reading: $reading,
            recipientMember: $grandparent,
            fromMember: $mlmMember,
            level: 2,
            commissionType: $commissionType,
            commissionRate: $commissionRate,
            amount: $commissionAmount,
            readingPrice: $readingPrice,
        );

        Log::info('FortuneCommission [L2/หลาน]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'buyer_user_id' => $reading->user_id,
            'grandparent_user_id' => $grandparent->user_id,
            'type' => $commissionType,
            'rate' => $commissionRate,
            'amount' => $commissionAmount,
            'reading_price' => $readingPrice,
        ]);
    }

    /**
     * สร้างข้อความแจ้งรายได้ค่าแนะนำที่ยังไม่ได้บอก user (FB 24h-safe)
     *
     * เรียกจาก FortuneChannelManager::processMessage ทุกครั้งที่ user ทักแชทมา
     * → ใช้ replyMessage ฟรี ไม่กิน push quota / ไม่ติดกฎ FB 24h
     *
     * Flow:
     * 1. หา FortuneCommission ของ user นี้ ที่ status=PAID + chat_notified_at IS NULL
     * 2. รวมยอด + นับ + build ข้อความสรุป
     * 3. mark chat_notified_at = now() ทั้งหมด → กันซ้ำ
     * 4. return ข้อความ หรือ null ถ้าไม่มี
     *
     * @param  int  $userId  ผู้รับคอมมิชชั่น (recipient)
     * @return string|null  ข้อความสรุปรายได้ หรือ null ถ้าไม่มี
     */
    public function buildPendingChatNotification(int $userId): ?string
    {
        // กันกระเป๋ากลาง — ไม่ต้องแจ้งตัวเอง (เป็น admin/system รู้อยู่แล้ว)
        $settings = FortuneTellingSetting::getSettings();
        $centralUserId = $settings->getFortuneCentralUserId();
        if ($centralUserId && $centralUserId === $userId) {
            return null;
        }

        try {
            $pending = FortuneCommission::where('user_id', $userId)
                ->where('status', FortuneCommission::STATUS_PAID)
                ->whereNull('chat_notified_at')
                ->orderBy('created_at')
                ->limit(20) // กันรายการเยอะเกิน — แสดง 20 ล่าสุด
                ->get();

            if ($pending->isEmpty()) {
                return null;
            }

            $totalAmount = (float) $pending->sum('amount');
            $count = $pending->count();
            $l1Count = $pending->where('level', 1)->count();
            $l2Count = $pending->where('level', 2)->count();

            // ดึงยอดรวมในกระเป๋า (เพื่อบอก current balance)
            $wallet = Wallet::where('user_id', $userId)->first();
            $balance = $wallet ? (float) $wallet->balance : 0;

            // build ข้อความ
            $lines = [];
            $lines[] = '💰 ข่าวดี! มีรายได้ค่าแนะนำเข้ากระเป๋า';
            $lines[] = '─────────────────────';
            $lines[] = sprintf('🎉 ได้รับ %d รายการ รวม %s บาท', $count, number_format($totalAmount, 2));

            $breakdown = [];
            if ($l1Count > 0) {
                $breakdown[] = "สายตรง {$l1Count} ราย";
            }
            if ($l2Count > 0) {
                $breakdown[] = "ชั้นหลาน {$l2Count} ราย";
            }
            if (! empty($breakdown)) {
                $lines[] = '🌳 ' . implode(' • ', $breakdown);
            }

            $lines[] = '💎 ยอดในกระเป๋าปัจจุบัน: ' . number_format($balance, 2) . ' บาท';

            // 💼 ลิงก์เข้ากระเป๋า — login ผ่าน Facebook OAuth (signed redirect → wallet)
            // ลูกค้ากดได้เลย: FB OAuth login → auto match → redirect ไปหน้า wallet
            $walletUrl = route('user.wallet.index');
            $loginUrl = route('facebook.login', ['redirect' => $walletUrl]);

            $lines[] = '';
            $lines[] = '👉 เข้าหน้ากระเป๋า/ถอนเงิน:';
            $lines[] = $loginUrl;
            $lines[] = '';
            $lines[] = '⚠️ การถอนต้องยืนยันตัวตน (KYC) ก่อน';
            $lines[] = '✨ ขอบคุณที่ช่วยแนะนำเพื่อนๆ มาดูดวงนะคะ';

            $message = implode("\n", $lines);

            // mark all as notified — กันส่งซ้ำ
            FortuneCommission::whereIn('id', $pending->pluck('id'))
                ->update(['chat_notified_at' => now()]);

            Log::info('FortuneCommission [Notify]: ส่งสรุปรายได้ผ่านแชท', [
                'user_id' => $userId,
                'count' => $count,
                'total_amount' => $totalAmount,
                'commission_ids' => $pending->pluck('id')->toArray(),
            ]);

            return $message;
        } catch (\Throwable $e) {
            // ห้าม fail การ reply ปกติเพราะ notify error
            Log::warning('FortuneCommission [Notify]: build notification ล้มเหลว (non-blocking)', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * จ่ายค่าแนะนำเข้ากระเป๋ากลาง (Central Wallet Fallback)
     *
     * เรียกเมื่อหา recipient จริงไม่ได้ (no_referrer/sponsor_inactive/no_grandparent/etc.)
     * → เงินไปกระเป๋ากลางพร้อม notes บอกเหตุผล → audit trail สมบูรณ์
     *
     * Skip เงียบๆ ถ้า:
     * - ปิด fortune_central_fallback_enabled
     * - ไม่ได้ตั้ง fortune_central_user_id
     * - User กลางถูกลบ / ไม่มี MlmMember
     * (เงินยังคงอยู่ในบัญชีบริษัทแต่ไม่มี record — เหมือนเดิม)
     *
     * @param string $reason เหตุผล fallback (no_referrer/sponsor_inactive/no_grandparent/etc.)
     */
    protected function payToCentralWallet(
        FortuneReading $reading,
        MlmMember $fromMember,
        FortuneTellingSetting $settings,
        int $level,
        string $commissionType,
        float $commissionRate,
        float $amount,
        float $readingPrice,
        string $reason,
    ): void {
        if (! $settings->isFortuneCentralFallbackEnabled()) {
            Log::debug('FortuneCommission [Central]: fallback ปิดอยู่ → ไม่จ่ายเข้ากระเป๋ากลาง', [
                'reading_id' => $reading->id,
                'level' => $level,
                'reason' => $reason,
                'amount' => $amount,
            ]);

            return;
        }

        $centralUserId = $settings->getFortuneCentralUserId();
        if (! $centralUserId) {
            return; // isFortuneCentralFallbackEnabled() น่าจะจัดการไว้แล้ว แต่กันชน
        }

        // หา MlmMember ของ user กลาง
        $centralMember = MlmMember::with('user')->where('user_id', $centralUserId)->first();
        if (! $centralMember || ! $centralMember->user) {
            Log::warning('FortuneCommission [Central]: หา MlmMember ของ user กลางไม่เจอ → ข้าม fallback', [
                'reading_id' => $reading->id,
                'level' => $level,
                'central_user_id' => $centralUserId,
                'reason' => $reason,
                'lost_amount' => $amount,
            ]);

            return;
        }

        // กัน edge case: ถ้า fromMember เป็นคนเดียวกับ centralMember → ไม่จ่ายตัวเอง
        if ($fromMember->id === $centralMember->id) {
            Log::info('FortuneCommission [Central]: fromMember = centralMember → ข้าม (ไม่จ่ายตัวเอง)', [
                'reading_id' => $reading->id,
                'central_user_id' => $centralUserId,
            ]);

            return;
        }

        // จ่ายเข้ากระเป๋ากลาง พร้อม notes บอกเหตุผล
        DB::transaction(function () use (
            $reading, $centralMember, $fromMember, $level, $commissionType, $commissionRate, $amount, $readingPrice, $reason
        ) {
            $levelName = $level === 1 ? 'สายตรง' : 'ชั้นหลาน';
            $reasonNote = $this->reasonToThaiNote($reason);

            $commission = FortuneCommission::create([
                'user_id' => $centralMember->user_id,
                'from_user_id' => $reading->user_id,
                'fortune_reading_id' => $reading->id,
                'mlm_member_id' => $centralMember->id,
                'from_mlm_member_id' => $fromMember->id,
                'level' => $level,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'amount' => $amount,
                'reading_price' => $readingPrice,
                'status' => FortuneCommission::STATUS_PAID,
                'paid_at' => now(),
                'notes' => "[CENTRAL_FALLBACK:{$reason}] ค่าแนะนำดูดวง L{$level} ({$levelName}) {$amount} บาท — {$reasonNote}",
            ]);

            $this->depositToWallet($centralMember, $commission, $reading, $level, $amount);
        });

        Log::info('FortuneCommission [Central/กระเป๋ากลาง]: จ่ายสำเร็จ', [
            'reading_id' => $reading->id,
            'level' => $level,
            'reason' => $reason,
            'central_user_id' => $centralMember->user_id,
            'amount' => $amount,
            'reading_price' => $readingPrice,
        ]);
    }

    /**
     * แปลง reason code → คำอธิบายภาษาไทย (ใช้ใน notes)
     */
    protected function reasonToThaiNote(string $reason): string
    {
        return match ($reason) {
            'no_referrer' => 'ลูกค้าไม่มีผู้แนะนำ',
            'sponsor_missing' => 'ผู้แนะนำหายจากระบบ',
            'sponsor_inactive' => 'ผู้แนะนำไม่ active (ไม่ roll up)',
            'no_grandparent' => 'ไม่มี grandparent (ผู้แนะนำของผู้แนะนำ)',
            'grandparent_missing' => 'grandparent หายจากระบบ',
            'grandparent_inactive' => 'grandparent ไม่ active',
            default => "เหตุผล: {$reason}",
        };
    }

    /**
     * สร้าง commission โดยตรง (ข้าม active check) — ใช้สำหรับ retroactive fix เท่านั้น
     *
     * @param FortuneReading $reading
     * @param MlmMember $recipient ผู้รับคอมมิชชั่น
     * @param MlmMember $fromMember สมาชิกที่จ่ายดูดวง
     * @param int $level 1 หรือ 2
     * @param float $amount จำนวนเงิน
     * @param float $readingPrice ราคา reading
     * @param FortuneTellingSetting $settings
     */
    public function forceCreateCommission(
        FortuneReading $reading,
        MlmMember $recipient,
        MlmMember $fromMember,
        int $level,
        float $amount,
        float $readingPrice,
        FortuneTellingSetting $settings
    ): void {
        // เช็คซ้ำ: ห้ามจ่าย reading+level เดียวกัน 2 ครั้ง
        $exists = FortuneCommission::where('fortune_reading_id', $reading->id)
            ->where('level', $level)
            ->exists();
        if ($exists) {
            return;
        }

        $commissionType = $level === 1
            ? $settings->getFortuneLevel1CommissionType()
            : ($settings->fortune_level2_commission_type ?? 'fixed');
        $commissionRate = $level === 1
            ? (float) ($settings->fortune_level1_commission_amount ?? 10)
            : (float) ($settings->fortune_level2_commission_amount ?? 1);

        $this->createCommissionAndPay(
            reading: $reading,
            recipientMember: $recipient,
            fromMember: $fromMember,
            level: $level,
            commissionType: $commissionType,
            commissionRate: $commissionRate,
            amount: $amount,
            readingPrice: $readingPrice,
        );
    }

    /**
     * สร้าง FortuneCommission record + เพิ่มเงินเข้า wallet
     */
    protected function createCommissionAndPay(
        FortuneReading $reading,
        MlmMember $recipientMember,
        MlmMember $fromMember,
        int $level,
        string $commissionType,
        float $commissionRate,
        float $amount,
        float $readingPrice,
    ): void {
        DB::transaction(function () use (
            $reading, $recipientMember, $fromMember,
            $level, $commissionType, $commissionRate, $amount, $readingPrice
        ) {
            $levelName = $level === 1 ? 'สายตรง' : 'ชั้นหลาน';

            // สร้าง FortuneCommission record
            $commission = FortuneCommission::create([
                'user_id' => $recipientMember->user_id,
                'from_user_id' => $reading->user_id,
                'fortune_reading_id' => $reading->id,
                'mlm_member_id' => $recipientMember->id,
                'from_mlm_member_id' => $fromMember->id,
                'level' => $level,
                'commission_type' => $commissionType,
                'commission_rate' => $commissionRate,
                'amount' => $amount,
                'reading_price' => $readingPrice,
                'status' => FortuneCommission::STATUS_PAID,
                'paid_at' => now(),
                'notes' => "ค่าแนะนำดูดวง L{$level} ({$levelName}) {$amount} บาท",
            ]);

            // เพิ่มเงินเข้า wallet
            $this->depositToWallet($recipientMember, $commission, $reading, $level, $amount);
        });
    }

    /**
     * เพิ่มเงินเข้า wallet ผู้รับคอมมิชชั่น
     *
     * ⚠️ ทำ wallet + transaction โดยตรง (ไม่ผ่าน WalletService)
     * เพื่อหลีกเลี่ยงปัญหา nested transaction + WalletLog
     * ที่ทำให้ deposit ล้มเหลวเมื่อสร้าง wallet ใหม่ภายใน DB::transaction เดียวกัน
     */
    protected function depositToWallet(
        MlmMember $recipientMember,
        FortuneCommission $commission,
        FortuneReading $reading,
        int $level,
        float $amount
    ): void {
        try {
            // หา wallet ที่มีอยู่ หรือสร้างใหม่โดยตรง
            // 🐛 Fix 2026-06-12: lockForUpdate กัน race — สองคอมมิชชั่นเข้ากระเป๋าเดียว
            // พร้อมกันเคยอ่าน balance เดิมซ้ำ → ยอดเงินหาย (lost update)
            $wallet = Wallet::where('user_id', $recipientMember->user_id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = Wallet::create([
                    'user_id' => $recipientMember->user_id,
                    'balance' => 0,
                    'currency' => 'THB',
                    'status' => 'active',
                ]);
                // refresh เพื่อโหลด default values (wallet_address, etc.) จาก DB
                $wallet->refresh();
            }

            if ($wallet->status !== 'active') {
                // 🐛 Fix 2026-06-12: ต้อง throw เพื่อให้ commission record rollback ด้วย
                // (เดิม return เงียบ → commission สถานะ PAID แต่เงินไม่เข้ากระเป๋า)
                throw new \RuntimeException(
                    "wallet ไม่ active สำหรับ user {$recipientMember->user_id} (status: {$wallet->status})"
                );
            }

            $balanceBefore = (float) ($wallet->balance ?? 0);
            $balanceAfter = $balanceBefore + $amount;
            $levelName = $level === 1 ? 'สายตรง' : 'หลาน';

            // สร้าง WalletTransaction โดยตรง
            $transaction = WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'currency' => $wallet->currency,
                'description' => "ค่าแนะนำดูดวง L{$level}({$levelName}) {$amount} บาท จากบิล #{$reading->id}",
                'reference_type' => FortuneCommission::class,
                'reference_id' => $commission->id,
                'status' => 'completed',
                'metadata' => [
                    'reading_id' => $reading->id,
                    'level' => $level,
                    'buyer_user_id' => $reading->user_id,
                    'mode' => 'fortune_commission',
                ],
                'completed_at' => now(),
            ]);

            // อัพเดท wallet balance โดยตรง
            $wallet->update([
                'balance' => $balanceAfter,
                'total_income' => (float) ($wallet->total_income ?? 0) + $amount,
                'last_transaction_at' => now(),
            ]);

            // อัพเดท wallet_transaction_id ใน commission record
            $commission->update(['wallet_transaction_id' => $transaction->id]);
        } catch (\Throwable $walletErr) {
            Log::warning("FortuneCommission: เพิ่มเงินเข้า wallet L{$level} ไม่สำเร็จ — rollback commission", [
                'user_id' => $recipientMember->user_id,
                'amount' => $amount,
                'error' => $walletErr->getMessage(),
                'trace' => $walletErr->getTraceAsString(),
            ]);

            // 🐛 Fix 2026-06-12: rethrow ให้ DB::transaction ที่ครอบอยู่ rollback
            // commission record ด้วย — ห้ามมี record สถานะ PAID โดยเงินไม่เข้ากระเป๋า
            // (caller จับ exception ราย level ใน distributeCommissions แล้ว ไม่กระทบคำทำนาย)
            throw $walletErr;
        }
    }
}
