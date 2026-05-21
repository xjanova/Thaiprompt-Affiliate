<?php

namespace App\Services\Fortune;

use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\PaymentBankAccount;
use App\Models\SmsPaymentNotification;
use App\Models\UniquePaymentAmount;
use App\Services\LineAlertService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 🔍 Fortune Payment Fuzzy Matcher (2026-05-15)
 *
 * Auto-approve บิลที่ลูกค้าโอนยอดใกล้เคียง — เช็คจาก SMS Payment Notifications
 *
 * เคสจริงที่พบ:
 *   - โอนเกิน:  ฿39.48 → ฿40.00 (ปัดเศษขึ้น), ฿99.27 → ฿100.00
 *   - โอนขาด:  ฿39.48 → ฿39.00 (ตัดเศษทศนิยม)
 *   - ใช้บัญชีคนอื่น: ชื่อใน SMS ≠ Facebook name (เพื่อน/ครอบครัว)
 *   - ชื่อภาษาอังกฤษ: "SOMCHAI JITDEE" vs "สมชาย จิตดี"
 *
 * Decision Tree:
 *   - 1 candidate + amount OK + name score ≥ threshold → AUTO_APPROVE
 *   - 1 candidate + amount OK + name uncertain → ASK_CONFIRMATION
 *   - >1 candidates → AMBIGUOUS → push admin LINE OA
 *   - 0 candidates → NONE → fallback "ยังไม่พบยอด"
 *
 * Anti-fraud:
 *   - SMS ต้องเป็น credit + status=pending + matched_transaction_id IS NULL
 *   - SMS timestamp > bill created_at (ห้าม SMS ก่อนสร้างบิล)
 *   - Bank ต้องอยู่ใน active PaymentBankAccount + sms_checker_enabled
 *   - Asymmetric delta: เกินยอม +overpay_max, ขาดยอม -underpay_max
 *
 * @see FortuneConversationService::handlePaymentClaim
 */
class FortunePaymentFuzzyMatcher
{
    public const DECISION_AUTO_APPROVE = 'auto_approve';

    public const DECISION_ASK_CONFIRMATION = 'ask_confirmation';

    public const DECISION_AMBIGUOUS = 'ambiguous';

    public const DECISION_NONE = 'none';

    public const DECISION_DISABLED = 'disabled';

    public const CACHE_KEY_PREFIX = 'fortune:fuzzy_pending:';

    public const CACHE_TTL_MINUTES = 10;

    protected FortuneTellingSetting $settings;

    public function __construct(?FortuneTellingSetting $settings = null)
    {
        $this->settings = $settings ?? FortuneTellingSetting::getSettings();
    }

    /**
     * ตัดสินใจหลัก — ค้น candidates + score + return decision
     *
     * @return array{
     *   decision: string,
     *   candidates?: Collection<SmsPaymentNotification>,
     *   best?: SmsPaymentNotification,
     *   amount_score?: int,
     *   name_score?: int,
     *   delta?: float,
     *   reason?: string
     * }
     */
    public function evaluate(FortuneReading $reading, UniquePaymentAmount $uniqueAmount): array
    {
        if (! $this->isEnabled()) {
            return ['decision' => self::DECISION_DISABLED];
        }

        $candidates = $this->findCandidates($reading, $uniqueAmount);

        if ($candidates->isEmpty()) {
            return ['decision' => self::DECISION_NONE];
        }

        // >1 candidates ใน window — ambiguous (อาจ steal บิลคนอื่น)
        if ($candidates->count() > 1) {
            return [
                'decision' => self::DECISION_AMBIGUOUS,
                'candidates' => $candidates,
                'reason' => "พบ {$candidates->count()} SMS ใกล้เคียงในช่วงเวลาเดียวกัน — ตัดบิลอัตโนมัติไม่ได้",
            ];
        }

        // 1 candidate — ตัดสินใจตาม name score
        $sms = $candidates->first();
        $expectedAmount = (float) $uniqueAmount->unique_amount;
        $smsAmount = (float) $sms->amount;
        $delta = round($smsAmount - $expectedAmount, 2);

        $nameScore = $this->scoreNameMatch(
            (string) ($sms->sender_or_receiver ?? ''),
            (string) ($reading->facebook_user_name ?? '')
        );

        $autoThreshold = (int) ($this->settings->fuzzy_name_auto_threshold ?? 70);

        if ($nameScore >= $autoThreshold) {
            return [
                'decision' => self::DECISION_AUTO_APPROVE,
                'best' => $sms,
                'delta' => $delta,
                'name_score' => $nameScore,
            ];
        }

        // Name score ต่ำกว่า threshold → ขอลูกค้ายืนยัน
        return [
            'decision' => self::DECISION_ASK_CONFIRMATION,
            'best' => $sms,
            'delta' => $delta,
            'name_score' => $nameScore,
        ];
    }

    /**
     * 🎯 อนุมัติบิล — เรียก processPaymentConfirmed (entry point เดียวกับ SMS auto + admin)
     *
     * Pattern: ใช้ DB::transaction + lockForUpdate เพื่อกัน race condition
     *   (SMS auto-match + fuzzy ทำงานพร้อมกัน → อันที่ commit ก่อนชนะ)
     *
     * @return bool true = approve สำเร็จ
     */
    public function approve(
        FortuneReading $reading,
        UniquePaymentAmount $uniqueAmount,
        SmsPaymentNotification $sms,
        float $delta,
        int $nameScore
    ): bool {
        try {
            return DB::transaction(function () use ($reading, $uniqueAmount, $sms, $delta, $nameScore) {
                // Lock both rows
                $smsLocked = SmsPaymentNotification::lockForUpdate()->find($sms->id);
                $upaLocked = UniquePaymentAmount::lockForUpdate()->find($uniqueAmount->id);
                $readingLocked = FortuneReading::lockForUpdate()->find($reading->id);

                if (! $smsLocked || ! $upaLocked || ! $readingLocked) {
                    Log::warning('FortunePaymentFuzzyMatcher: lockForUpdate failed', [
                        'reading_id' => $reading->id,
                        'sms_id' => $sms->id,
                    ]);

                    return false;
                }

                // กัน race: ถ้า SMS หรือ UPA หรือ Reading ถูกตัดบิลไปแล้ว → abort
                if ($smsLocked->matched_transaction_id !== null
                    || $smsLocked->status !== 'pending'
                    || $upaLocked->status !== 'reserved'
                    || $readingLocked->is_paid
                ) {
                    Log::info('FortunePaymentFuzzyMatcher: race lost — already matched', [
                        'reading_id' => $reading->id,
                        'sms_id' => $sms->id,
                        'sms_status' => $smsLocked->status,
                        'upa_status' => $upaLocked->status,
                        'reading_paid' => $readingLocked->is_paid,
                    ]);

                    return false;
                }

                // อัพเดท SMS + UPA + Reading audit
                $smsLocked->update([
                    'status' => 'matched',
                    'matched_transaction_id' => $readingLocked->id,
                ]);

                $upaLocked->update([
                    'status' => 'used',
                    'matched_at' => now(),
                ]);

                $readingLocked->update([
                    'fuzzy_approved_at' => now(),
                    'fuzzy_approved_delta' => $delta,
                    'fuzzy_approved_sms_id' => $smsLocked->id,
                    'fuzzy_approved_name_score' => $nameScore,
                ]);

                Log::info('FortunePaymentFuzzyMatcher: APPROVED', [
                    'reading_id' => $readingLocked->id,
                    'sms_id' => $smsLocked->id,
                    'expected_amount' => $upaLocked->unique_amount,
                    'sms_amount' => $smsLocked->amount,
                    'delta' => $delta,
                    'name_score' => $nameScore,
                    'sender' => $smsLocked->sender_or_receiver,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('FortunePaymentFuzzyMatcher: approve failed', [
                'reading_id' => $reading->id,
                'sms_id' => $sms->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * 🔎 ค้น SMS pending ที่เข้าเงื่อนไข
     *
     * @return Collection<int, SmsPaymentNotification>
     */
    public function findCandidates(FortuneReading $reading, UniquePaymentAmount $uniqueAmount): Collection
    {
        $expectedAmount = (float) $uniqueAmount->unique_amount;
        $overpayMax = (float) ($this->settings->fuzzy_overpay_max_baht ?? 11.00);
        $underpayMax = (float) ($this->settings->fuzzy_underpay_max_baht ?? 1.00);
        $windowMin = (int) ($this->settings->fuzzy_window_minutes ?? 60);

        $lowerBound = $expectedAmount - $underpayMax;
        $upperBound = $expectedAmount + $overpayMax;

        // active banks ที่เปิด SMS Checker (ตัด SMS จากธนาคารที่ไม่ใช่ของเรา)
        $activeBanks = PaymentBankAccount::active()
            ->smsCheckerEnabled()
            ->pluck('bank_name')
            ->unique()
            ->values();

        // 🔒 SMS ต้องมาหลังบิลถูกสร้าง (ใช้ created_at เป็น baseline)
        $billCreatedAt = $reading->created_at ?? now()->subMinutes($windowMin);
        $windowStart = max(
            $billCreatedAt->copy(),
            now()->subMinutes($windowMin)
        );

        return SmsPaymentNotification::query()
            ->where('status', 'pending')
            ->where('type', 'credit')
            ->whereNull('matched_transaction_id')
            ->whereBetween('amount', [$lowerBound, $upperBound])
            ->where('sms_timestamp', '>=', $windowStart)
            ->when($activeBanks->isNotEmpty(), function ($q) use ($activeBanks) {
                $q->whereIn('bank', $activeBanks);
            })
            ->orderBy('sms_timestamp', 'desc')
            ->get();
    }

    /**
     * 📛 Score ความตรงของชื่อ (0-100)
     *
     * Normalize:
     *   - lowercase
     *   - strip honorifics: นาย/นาง/นางสาว/MR/MS/MRS/MISS/คุณ
     *   - strip spaces, punctuation, dots
     *
     * เปรียบเทียบ:
     *   - similar_text() — % similarity
     *   - bonus +20 ถ้า substring (เช่น "SOMCHAI" อยู่ใน "Somchai Jitdee")
     */
    public function scoreNameMatch(string $smsName, string $customerName): int
    {
        $smsName = $this->normalizeName($smsName);
        $customerName = $this->normalizeName($customerName);

        if ($smsName === '' || $customerName === '') {
            return 0;
        }

        // Exact match
        if ($smsName === $customerName) {
            return 100;
        }

        // similar_text — % similarity
        similar_text($smsName, $customerName, $percent);
        $score = (int) round($percent);

        // Substring bonus
        if (mb_stripos($customerName, $smsName) !== false
            || mb_stripos($smsName, $customerName) !== false) {
            $score = min(100, $score + 20);
        }

        // Token bonus — แต่ละ token ใน SMS หาเจอใน customer (รองรับ first/last name swap)
        $smsTokens = preg_split('/\s+/u', $smsName, -1, PREG_SPLIT_NO_EMPTY);
        $customerTokens = preg_split('/\s+/u', $customerName, -1, PREG_SPLIT_NO_EMPTY);
        if (! empty($smsTokens) && ! empty($customerTokens)) {
            $matchedTokens = 0;
            foreach ($smsTokens as $st) {
                if (mb_strlen($st) < 2) {
                    continue;
                }
                foreach ($customerTokens as $ct) {
                    if (mb_strlen($ct) < 2) {
                        continue;
                    }
                    similar_text($st, $ct, $tokenPct);
                    if ($tokenPct >= 70) {
                        $matchedTokens++;
                        break;
                    }
                }
            }
            if ($matchedTokens > 0) {
                $bonus = min(20, $matchedTokens * 10);
                $score = min(100, $score + $bonus);
            }
        }

        return $score;
    }

    /**
     * 🧹 Normalize ชื่อ — lowercase + strip honorific/space/punctuation
     */
    protected function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));

        // Strip honorifics (Thai + English)
        $name = preg_replace(
            '/^(นาย|นาง|นางสาว|น\.ส\.|ด\.ช\.|ด\.ญ\.|mr\.?|ms\.?|mrs\.?|miss|คุณ|เจ้าชะตา)\s+/u',
            '',
            $name
        );

        // ลบ punctuation/dots
        $name = preg_replace('/[.,_\-\'"`]/u', '', $name);

        // ยุบ whitespace
        $name = preg_replace('/\s+/u', ' ', $name);

        return trim($name);
    }

    /**
     * 💾 เก็บ pending fuzzy match ไว้ใน cache — รอลูกค้ายืนยัน
     */
    public function storePendingConfirmation(
        string $platform,
        string $userId,
        int $readingId,
        SmsPaymentNotification $sms,
        UniquePaymentAmount $uniqueAmount,
        float $delta,
        int $nameScore
    ): void {
        $cacheKey = self::CACHE_KEY_PREFIX.$platform.':'.$userId;
        Cache::put($cacheKey, [
            'reading_id' => $readingId,
            'sms_id' => $sms->id,
            'unique_amount_id' => $uniqueAmount->id,
            'delta' => $delta,
            'name_score' => $nameScore,
            'sms_amount' => (float) $sms->amount,
            'sms_sender' => $sms->sender_or_receiver,
            'sms_timestamp' => optional($sms->sms_timestamp)->toIso8601String(),
            'expected_amount' => (float) $uniqueAmount->unique_amount,
            'asked_at' => now()->toIso8601String(),
        ], now()->addMinutes(self::CACHE_TTL_MINUTES));
    }

    /**
     * 🔁 ดึง pending confirmation จาก cache
     *
     * @return array|null cache payload หรือ null ถ้าไม่มี/หมดอายุ
     */
    public function getPendingConfirmation(string $platform, string $userId): ?array
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$platform.':'.$userId;

        return Cache::get($cacheKey);
    }

    /**
     * 🗑️ ลบ pending confirmation (หลังตอบยืนยันแล้ว)
     */
    public function clearPendingConfirmation(string $platform, string $userId): void
    {
        $cacheKey = self::CACHE_KEY_PREFIX.$platform.':'.$userId;
        Cache::forget($cacheKey);
    }

    /**
     * 🚨 Push แอดมิน LINE OA — ambiguous case ต้องแอดมินตัดสิน
     */
    public function pushAdminAlert(FortuneReading $reading, UniquePaymentAmount $uniqueAmount, array $context = []): void
    {
        try {
            $message = "🔍 Fuzzy Payment Unresolved\n"
                ."Bill: {$reading->bill_reference}\n"
                ."Customer: {$reading->facebook_user_name}\n"
                ."Expected: ฿".number_format((float) $uniqueAmount->unique_amount, 2)."\n";

            if (! empty($context['candidates']) && $context['candidates'] instanceof Collection) {
                $message .= "Candidates: {$context['candidates']->count()}\n";
                foreach ($context['candidates']->take(3) as $i => $c) {
                    $idx = $i + 1;
                    $time = optional($c->sms_timestamp)->format('H:i');
                    $message .= "  {$idx}. ฿".number_format((float) $c->amount, 2)
                        ." @ {$time} from \"{$c->sender_or_receiver}\"\n";
                }
            }

            if (! empty($context['reason'])) {
                $message .= "Reason: {$context['reason']}\n";
            }

            $message .= "\nadmin manual approve: /admin/sms-payment/orders/{$reading->id}";

            app(LineAlertService::class)->alertSystemError('Fuzzy Payment Needs Admin', [
                'detail' => $message,
                'reading_id' => $reading->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('FortunePaymentFuzzyMatcher: pushAdminAlert failed', [
                'reading_id' => $reading->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 📊 Build เนื้อความสำหรับลูกค้า — แสดง evidence + ขอยืนยัน
     */
    public function buildConfirmationMessage(
        FortuneReading $reading,
        UniquePaymentAmount $uniqueAmount,
        SmsPaymentNotification $sms,
        float $delta,
        int $nameScore
    ): string {
        $expected = number_format((float) $uniqueAmount->unique_amount, 2);
        $actual = number_format((float) $sms->amount, 2);
        $time = optional($sms->sms_timestamp)->format('H:i');
        $sender = $sms->sender_or_receiver ?? '-';
        $billRef = $reading->bill_reference ?? '-';
        $customerName = $reading->facebook_user_name ?? 'เจ้าชะตา';

        $deltaText = $delta >= 0
            ? '(เกินบิล ฿'.number_format(abs($delta), 2).')'
            : '(ขาดบิล ฿'.number_format(abs($delta), 2).')';

        $msg = "🔍 *เจอยอดเข้าบัญชีใกล้เคียง*\n\n"
            ."🔖 เลขที่บิล: {$billRef}\n"
            ."💰 ยอดที่รอ: ฿{$expected}\n"
            ."💸 ยอดที่เจอ: ฿{$actual} {$deltaText}\n"
            ."⏰ เวลาโอน: {$time} น.\n"
            ."👤 ชื่อผู้โอน: {$sender}\n\n";

        if ($nameScore < 50) {
            $msg .= "ℹ️ ชื่อในระบบของเจ้าชะตาคือ \"{$customerName}\"\n"
                ."ชื่อผู้โอนแตกต่างกัน — อาจใช้บัญชีเพื่อน/ครอบครัว?\n\n";
        }

        $msg .= "👉 *ใช่ยอดที่เจ้าชะตาโอนใช่ไหมคะ?*\n\n"
            ."✅ ตอบ \"ใช่\" → ระบบจะตัดบิลและส่งคำทำนายให้\n"
            ."❌ ตอบ \"ไม่ใช่\" → แอดมินจะรับเรื่องตรวจสอบ\n\n"
            ."🙏 ยืนยันภายใน 10 นาทีนะคะ";

        return $msg;
    }

    /**
     * 📊 Build เนื้อความ approve สำเร็จ
     */
    public function buildApprovedMessage(
        FortuneReading $reading,
        UniquePaymentAmount $uniqueAmount,
        SmsPaymentNotification $sms,
        float $delta
    ): string {
        $expected = number_format((float) $uniqueAmount->unique_amount, 2);
        $actual = number_format((float) $sms->amount, 2);
        $time = optional($sms->sms_timestamp)->format('H:i');
        $billRef = $reading->bill_reference ?? '-';

        $deltaLine = '';
        if (abs($delta) >= 0.01) {
            $deltaLine = $delta >= 0
                ? '   (เกินบิล ฿'.number_format(abs($delta), 2).' — ขอบคุณค่ะ 🙏)'."\n"
                : '   (ขาดบิล ฿'.number_format(abs($delta), 2).' — บอทเช็คให้ผ่านแล้ว 🙏)'."\n";
        }

        return "✅ *ระบบตัดบิลเรียบร้อยแล้วค่ะ*\n\n"
            ."🔖 เลขที่บิล: {$billRef}\n"
            ."💰 ยอดที่รอ: ฿{$expected}\n"
            ."💸 ยอดที่รับ: ฿{$actual}\n"
            .$deltaLine
            ."⏰ เวลาโอน: {$time} น.\n\n"
            ."🌙 *แม่หมอจันทรากำลังคำนวณดวงดาวให้นะคะ*\n"
            ."ใช้เวลา 1-3 นาที — รอสักครู่ค่ะ ✨";
    }

    public function isEnabled(): bool
    {
        return (bool) ($this->settings->enable_fuzzy_payment_match ?? false);
    }

    // ================================================================
    // 🚀 (2026-05-21) Admin-side Orphan Match — reverse lookup
    // ใช้กรณี: admin เห็น SMS ไม่จับคู่บิล (เช่น ลูกค้าโอนเลขกลม 39 บาท)
    //         → admin กดปุ่ม "หาบิลที่ตรงกัน" ใน SmsChecker app
    //         → backend หา FortuneReading pending ที่ name+time match
    //         → admin เลือก confirm → ระบบ approve ทันที
    //
    // ต่างจาก findCandidates() เดิม:
    //   - เดิม: bill → หา SMS (customer side, ลูกค้าเช็คสถานะ)
    //   - ใหม่: SMS → หา bills (admin side, manual review)
    //
    // Amount rule (user spec 2026-05-21):
    //   - ลูกค้าโอน >= bill.base_amount (ราคาเต็ม 39, 99)
    //   - ห้ามต่ำกว่า (กัน fraud โอนขาด) — ระบบใช้ admin confirm ทุกครั้ง
    //   - ไม่จำกัดเกิน (admin ตัดสินใจ)
    // ================================================================

    /**
     * 🔎 (2026-05-21) Reverse lookup: SMS → list of candidate bills
     *
     * ใช้กรณี admin เห็น orphan SMS ใน SmsChecker app, กดปุ่ม "หาบิลที่ตรงกัน"
     *
     * Criteria:
     *   - reading_type IN [deep, celtic_cross] (เฉพาะบิลเสียเงิน)
     *   - is_paid = false
     *   - conversation_status IN [pending_payment, celtic_pending_payment]
     *   - bill.base_amount <= sms.amount (ลูกค้าโอน >= ราคาเต็ม)
     *   - bill.created_at <= sms.sms_timestamp (SMS ต้องมาหลังบิล)
     *   - sms.sms_timestamp - bill.created_at <= window_hours (default 24h)
     *
     * Return: bills sorted by name_score DESC (best match first)
     * แต่ละ candidate มี: bill, name_score, time_delta_minutes, amount_delta
     *
     * @return array<int, array{
     *   reading: FortuneReading,
     *   name_score: int,
     *   time_delta_minutes: int,
     *   amount_delta: float
     * }>
     */
    public function findBillCandidatesForOrphan(
        float $amount,
        ?string $senderName,
        Carbon $smsTimestamp,
        int $windowHours = 24
    ): array {
        $smsName = trim((string) $senderName);

        // 1) Query bills ที่ผ่านเงื่อนไข hard (amount, time, status)
        //    name match ทำใน PHP เพราะ similar_text ไม่มีใน SQL
        $bills = FortuneReading::query()
            ->whereIn('reading_type', [
                FortuneReading::READING_TYPE_DEEP ?? 'deep',
                FortuneReading::READING_TYPE_CELTIC_CROSS,
            ])
            ->where('is_paid', false)
            ->whereIn('conversation_status', [
                FortuneReading::STATUS_PENDING_PAYMENT,
                FortuneReading::STATUS_CELTIC_PENDING_PAYMENT,
            ])
            ->where('created_at', '<=', $smsTimestamp)
            ->where('created_at', '>=', $smsTimestamp->copy()->subHours($windowHours))
            ->whereHas('uniquePaymentAmount', function ($q) use ($amount) {
                // base_amount <= SMS amount → ลูกค้าโอน >= ราคาเต็ม
                $q->whereColumn('base_amount', '<=', DB::raw($amount))
                    ->orWhere('base_amount', '<=', $amount);
            })
            ->with('uniquePaymentAmount')
            ->get();

        $candidates = [];
        foreach ($bills as $bill) {
            $basePrice = (float) ($bill->uniquePaymentAmount?->base_amount ?? 0);
            if ($basePrice <= 0 || $amount < $basePrice) {
                continue; // double-check (กัน eager-load คาด)
            }

            $nameScore = $this->scoreNameMatch(
                $smsName,
                (string) ($bill->facebook_user_name ?? '')
            );

            $timeDeltaMin = (int) abs($bill->created_at->diffInMinutes($smsTimestamp, true));
            $amountDelta = round($amount - $basePrice, 2);

            $candidates[] = [
                'reading' => $bill,
                'name_score' => $nameScore,
                'time_delta_minutes' => $timeDeltaMin,
                'amount_delta' => $amountDelta,
            ];
        }

        // Sort: name_score DESC, time_delta ASC (best match first)
        usort($candidates, function ($a, $b) {
            if ($a['name_score'] !== $b['name_score']) {
                return $b['name_score'] - $a['name_score'];
            }
            return $a['time_delta_minutes'] - $b['time_delta_minutes'];
        });

        return $candidates;
    }

    /**
     * ✅ (2026-05-21) Admin confirm: ผูก SMS เข้ากับบิล + approve
     *
     * ใช้หลัง admin ดู candidate list ใน Android app แล้วเลือกบิลที่ใช่
     *
     * Pattern: ใช้ DB::transaction + lockForUpdate (เหมือน approve() เดิม)
     *
     * @param  FortuneReading  $reading  บิลที่จะ approve
     * @param  SmsPaymentNotification|null  $sms  SMS ที่จะผูก (null = force approve no SMS link)
     * @param  string  $adminContext  audit info (e.g. "device_id=X via Android orphan match")
     * @return bool true = approve สำเร็จ
     */
    public function confirmMatchByAdmin(
        FortuneReading $reading,
        ?SmsPaymentNotification $sms,
        string $adminContext = ''
    ): bool {
        try {
            return DB::transaction(function () use ($reading, $sms, $adminContext) {
                $readingLocked = FortuneReading::lockForUpdate()->find($reading->id);
                $smsLocked = $sms ? SmsPaymentNotification::lockForUpdate()->find($sms->id) : null;

                if (! $readingLocked) {
                    Log::warning('FortunePaymentFuzzyMatcher::confirmMatchByAdmin: reading not found', [
                        'reading_id' => $reading->id,
                    ]);
                    return false;
                }

                // กัน race — ถ้าจ่ายไปแล้ว → idempotent success
                if ($readingLocked->is_paid) {
                    Log::info('FortunePaymentFuzzyMatcher::confirmMatchByAdmin: already paid', [
                        'reading_id' => $reading->id,
                    ]);
                    return true;
                }

                $basePrice = (float) ($readingLocked->uniquePaymentAmount?->base_amount ?? 0);
                $smsAmount = $sms ? (float) $sms->amount : (float) $readingLocked->amount_paid;
                $delta = round($smsAmount - $basePrice, 2);

                $nameScore = $sms
                    ? $this->scoreNameMatch(
                        (string) ($sms->sender_or_receiver ?? ''),
                        (string) ($readingLocked->facebook_user_name ?? '')
                    )
                    : 0;

                // confirmPayment เซ็ต is_paid + paid_at + status=PAID + (ถ้ามี SMS) อัปเดต sms_notification_id
                $readingLocked->confirmPayment($smsLocked);

                // อัปเดต SMS notification (idempotent — ถ้า matched_transaction_id ว่างเท่านั้น)
                if ($smsLocked && $smsLocked->matched_transaction_id === null) {
                    $smsLocked->update([
                        'status' => 'matched',
                        'matched_transaction_id' => $readingLocked->id,
                        'matched_at' => now(),
                    ]);
                }

                // Audit fields
                $readingLocked->update([
                    'fuzzy_approved_at' => now(),
                    'fuzzy_approved_delta' => $delta,
                    'fuzzy_approved_sms_id' => $smsLocked?->id,
                    'fuzzy_approved_name_score' => $nameScore,
                ]);

                Log::warning('💎 FortunePaymentFuzzyMatcher::confirmMatchByAdmin', [
                    'reading_id' => $readingLocked->id,
                    'bill_reference' => $readingLocked->bill_reference,
                    'sms_id' => $smsLocked?->id,
                    'sms_amount' => $smsAmount,
                    'base_price' => $basePrice,
                    'delta' => $delta,
                    'name_score' => $nameScore,
                    'admin_context' => $adminContext,
                ]);

                return true;
            });
        } catch (\Throwable $e) {
            Log::error('FortunePaymentFuzzyMatcher::confirmMatchByAdmin failed', [
                'reading_id' => $reading->id,
                'sms_id' => $sms?->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
