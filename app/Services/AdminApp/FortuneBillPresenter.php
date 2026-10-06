<?php

namespace App\Services\AdminApp;

use App\Http\Controllers\Admin\FortuneBillsController;
use App\Models\FortuneCelticQuestion;
use App\Models\FortuneReading;
use App\Models\SlipVerificationLog;
use App\Support\FortuneFunnelStage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * 🧾 แปลงบิลดูดวงเป็น JSON ของแอปแอดมิน — ทำงานทีละ "หน้า" (กัน N+1)
 *
 * ข้อมูลประกอบทุกอย่าง (ชื่อลูกค้า / สลิป / SMS / คำถามแรกของ Celtic / จำนวนบิลที่เคยจ่าย)
 * ดึงรวดเดียวต่อหน้า แล้วค่อยประกอบทีละใบ
 *
 * ⚠️ อ่านอย่างเดียว — ห้ามเรียก resolveCustomerName() (เมธอดนั้นเขียนชื่อกลับลง DB)
 */
class FortuneBillPresenter
{
    /** ความยาวสูงสุดของ question_preview */
    public const QUESTION_PREVIEW_CHARS = 120;

    /**
     * คอลัมน์ที่รายการของแอปใช้จริง — ไม่โหลดคำทำนายยาว ๆ (ai_response / deep_response / basic_response)
     * และบริบทโพสต์ (user_posts_context) · ความว่าง/ไม่ว่างของคำทำนายส่งมาเป็นธง has_* แทน
     */
    public const LIST_COLUMNS = [
        'id', 'bill_reference', 'reading_type', 'amount_paid', 'amount_received', 'is_paid', 'is_floating',
        'paid_at', 'conversation_status', 'conversation_state', 'platform', 'platform_user_id',
        'facebook_user_id', 'facebook_user_name', 'user_profile', 'user_id', 'questions', 'created_at',
        'updated_at', 'slip_received_at', 'slipok_verified_at', 'slip_image_path', 'user_image_url',
        'sms_notification_id', 'unique_payment_amount_id', 'transfer_reported', 'transfer_reported_at',
        'celtic_questions_used', 'admin_takeover_until', 'admin_takeover_started_at', 'admin_takeover_reason',
        'admin_takeover_by',
    ];

    /**
     * เลือกเฉพาะคอลัมน์ของรายการ + ธง has_ai_response / has_deep_response (ใช้ตอนดึงแถวเท่านั้น ไม่ใช้กับ count/aggregate)
     */
    public static function selectListColumns(Builder $query): Builder
    {
        $t = $query->getModel()->getTable();

        return $query
            ->select(array_map(fn (string $c) => "{$t}.{$c}", self::LIST_COLUMNS))
            ->selectRaw("(COALESCE({$t}.ai_response, '') <> '') AS has_ai_response")
            ->selectRaw("(COALESCE({$t}.deep_response, '') <> '') AS has_deep_response");
    }

    /** @var array<int, string> reading_id → คำถามแรกของ Celtic */
    private array $celticFirstQuestion = [];

    /** @var array<int, string|null> reading_id → path รูปสลิปใน disk local */
    private array $slipPath = [];

    /** @var array<int, int> reading_id → จำนวนบิลที่ลูกค้าคนนี้จ่ายก่อนหน้า */
    private array $priorPaid = [];

    /**
     * ประกอบ JSON ของบิลทั้งหน้า
     *
     * @param  Collection<int, FortuneReading>  $readings
     * @return array<int, array<string, mixed>>
     */
    public function presentMany(Collection $readings): array
    {
        $this->preload($readings);

        return $readings->map(fn (FortuneReading $r) => $this->present($r))->values()->all();
    }

    /**
     * JSON แบบย่อทั้งชุด (ตัวอย่างในหน้าแรก)
     *
     * @param  Collection<int, FortuneReading>  $readings
     * @return array<int, array<string, mixed>>
     */
    public function presentManyMini(Collection $readings): array
    {
        $this->preload($readings);

        return $readings->map(fn (FortuneReading $r) => $this->presentMini($r))->values()->all();
    }

    /**
     * โหลดข้อมูลประกอบของทั้งชุดในไม่กี่คิวรี
     *
     * @param  Collection<int, FortuneReading>  $readings
     */
    public function preload(Collection $readings): void
    {
        if ($readings->isEmpty()) {
            return;
        }

        $readings->loadMissing(['user:id,name', 'smsNotification', 'uniquePaymentAmount']);

        $this->preloadCelticQuestions($readings);
        $this->preloadSlips($readings);
        $this->preloadPriorPaid($readings);
    }

    /**
     * JSON ของบิลหนึ่งใบ (เรียก presentMany ก่อนเพื่อให้ข้อมูลประกอบพร้อม)
     *
     * @return array<string, mixed>
     */
    public function present(FortuneReading $r): array
    {
        $status = FortuneBillBuckets::statusOf($r);
        $reason = $this->statusReason($r, $status);
        $isJuntra = $r->isJuntraBill();
        $slipUrl = $this->slipUrl($r);
        $sms = $r->relationLoaded('smsNotification') ? $r->smsNotification : null;
        $cancelReason = (string) ($r->getConversationState('cancellation_reason') ?? '');

        return [
            'id' => (int) $r->id,
            'bill_number' => self::billNumber($r),
            'package' => self::packageKey($r),
            'package_label' => self::packageLabel($r),
            'reading_type' => $r->reading_type,
            'amount_thb' => $this->billAmount($r),
            'amount_received_thb' => $r->amount_received !== null ? round((float) $r->amount_received, 2) : null,
            'status' => $status,
            'status_label' => $this->statusLabel($r, $status, $reason),
            'status_reason' => $reason,
            'conversation_status' => $r->conversation_status,
            'stage' => self::stage($r),
            'platform' => self::platform($r),
            'customer_name' => self::customerName($r),
            'customer' => [
                'name' => self::customerName($r),
                'platform_user_id' => $r->platform_user_id ?: $r->facebook_user_id,
                'user_id' => $r->user_id !== null ? (int) $r->user_id : null,
            ],
            'created_at' => $r->created_at?->toIso8601String(),
            'updated_at' => $r->updated_at?->toIso8601String(),
            'paid_at' => $r->paid_at?->toIso8601String(),
            'slip_image_url' => $slipUrl['url'],
            'slip' => [
                'received_at' => $r->slip_received_at?->toIso8601String(),
                'verified_at' => $r->slipok_verified_at?->toIso8601String(),
                'image_url' => $slipUrl['url'],
                'image_requires_auth' => $slipUrl['requires_auth'],
            ],
            'sms_match' => [
                'matched' => $sms !== null,
                'sms_id' => $sms?->id,
                'amount' => $sms !== null ? round((float) $sms->amount, 2) : null,
                'at' => $sms?->sms_timestamp?->toIso8601String(),
                'bank' => $sms?->bank,
                'sender' => $sms?->sender_or_receiver,
            ],
            'question_preview' => $this->questionPreview($r),
            'customer_prior_paid_count' => (int) ($this->priorPaid[(int) $r->id] ?? 0),
            'is_juntra' => $isJuntra,
            'actions' => [
                // คำใบ้ให้ UI เท่านั้น — การตัดสินจริงอยู่ที่ endpoint เดิม (readings/{id}/mark-paid|refund|cancel)
                // superseded_by_paid = ลูกค้าจ่ายบิลอื่นแทนแล้ว อนุมัติซ้ำ = เก็บเงินซ้ำ
                'can_mark_paid' => ! $r->is_paid && ! $isJuntra && ! $r->is_floating && $cancelReason !== 'superseded_by_paid',
                'can_refund' => (bool) $r->is_paid && ! $isJuntra,
                'can_cancel' => ! $r->is_paid && ! $isJuntra,
            ],
        ];
    }

    /**
     * JSON แบบย่อสำหรับ preview ในหน้าแรก
     *
     * @return array<string, mixed>
     */
    public function presentMini(FortuneReading $r): array
    {
        $full = $this->present($r);

        return array_intersect_key($full, array_flip([
            'id', 'bill_number', 'package_label', 'amount_thb', 'status', 'status_label', 'status_reason',
            'platform', 'customer_name', 'created_at', 'slip_image_url', 'sms_match',
        ])) + ['claimed_at' => FortuneBillBuckets::claimedAt($r)?->toIso8601String()];
    }

    // ────────────────────────────────────────────────────────────
    // ข้อมูลกลางที่ endpoint อื่นใช้ร่วม (กล่องแชท / บิลค้าง)
    // ────────────────────────────────────────────────────────────

    /**
     * เลขบิลแบบที่หน้าเว็บ/Warroom โชว์ — ไม่มีเลข FTU ใช้ #id
     */
    public static function billNumber(FortuneReading $r): string
    {
        return $r->bill_reference ?: '#'.$r->id;
    }

    /**
     * คีย์แพคเกจ: deep | celtic | free_card | basic | juntra
     */
    public static function packageKey(FortuneReading $r): string
    {
        return match ($r->reading_type) {
            FortuneReading::READING_TYPE_DEEP => 'deep',
            FortuneReading::READING_TYPE_CELTIC_CROSS => 'celtic',
            FortuneReading::READING_TYPE_FREE_CARD => 'free_card',
            FortuneReading::READING_TYPE_JUNTRA => 'juntra',
            default => 'basic',
        };
    }

    /**
     * ป้ายแพคเกจภาษาไทย — ใช้ชุดเดียวกับศูนย์รวมบิลบนเว็บ
     */
    public static function packageLabel(FortuneReading $r): string
    {
        $key = self::packageKey($r);
        if ($key === 'juntra') {
            return 'เว็บจันทรา';
        }

        return FortuneBillsController::PACKAGES[$key][0] ?? 'พื้นฐาน';
    }

    /**
     * ช่องทาง — อ่านคอลัมน์ platform เท่านั้น (ห้ามเดาจาก facebook_user_id: LINE id ก็อยู่คอลัมน์นั้น)
     */
    public static function platform(FortuneReading $r): string
    {
        $p = (string) $r->platform;

        return in_array($p, ['facebook', 'line', 'telegram'], true) ? $p : 'other';
    }

    /**
     * ขั้นในกรวยขาย (ชุดเดียวกับ Warroom)
     *
     * @return array{key: string, label: string, icon: string, detail: string|null}
     */
    public static function stage(FortuneReading $r): array
    {
        $subject = $r;
        $attrs = $r->getAttributes();

        // แถวที่ดึงแบบประหยัด (selectListColumns) ไม่มี ai_response — FortuneFunnelStage ใช้แค่ "ว่างไหม"
        // ตอนเดาขั้นของสถานะแปลก ๆ → ใส่ค่าลงสำเนาชั่วคราวเท่านั้น (ไม่แตะโมเดลจริง กันเผลอ save ค่าปลอมลงคอลัมน์)
        if (! array_key_exists('ai_response', $attrs) && array_key_exists('has_ai_response', $attrs)) {
            $subject = clone $r;
            $subject->setRawAttributes(array_merge($attrs, ['ai_response' => $attrs['has_ai_response'] ? '1' : null]));
        }

        $key = FortuneFunnelStage::of($subject);
        $meta = FortuneFunnelStage::meta($key);

        return [
            'key' => $key,
            'label' => $meta['label'],
            'icon' => $meta['icon'],
            'detail' => FortuneFunnelStage::detail($subject),
        ];
    }

    /**
     * ชื่อลูกค้าสำหรับแสดงผล — อ่านอย่างเดียว ไม่เขียนกลับ DB
     */
    public static function customerName(FortuneReading $r): string
    {
        $candidates = [
            $r->facebook_user_name,
            is_array($r->user_profile) ? ($r->user_profile['name'] ?? null) : null,
            $r->relationLoaded('user') ? $r->user?->name : null,
        ];

        foreach ($candidates as $name) {
            if (self::looksLikeName($name)) {
                return trim((string) $name);
            }
        }

        $pid = (string) ($r->platform_user_id ?: $r->facebook_user_id);
        if ($pid !== '') {
            return strtoupper(self::platform($r) === 'other' ? 'fb' : self::platform($r)).'-'.substr($pid, -6);
        }

        return 'ลูกค้าดูดวง';
    }

    // ────────────────────────────────────────────────────────────
    // ภายใน
    // ────────────────────────────────────────────────────────────

    private static function looksLikeName(mixed $name): bool
    {
        if (! is_string($name)) {
            return false;
        }
        $name = trim($name);
        if ($name === '' || in_array($name, ['คุณ', 'ลูกค้า', 'เจ้าชะตา'], true)) {
            return false;
        }
        // รหัสแทนชื่อ เช่น FACEBOOK-123456 / U + 32 hex / PSID ตัวเลขยาว
        if (preg_match('/^(FACEBOOK|LINE|FB|TG|TELEGRAM|MESSENGER|IG|INSTAGRAM)-[A-Z0-9]+$/i', $name)
            || preg_match('/^U[0-9a-f]{32}$/i', $name)
            || preg_match('/^\d{15,}$/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * ยอดบิล — amount_paid ถ้า > 0 ไม่งั้นยอดทศนิยมจาก UPA (ลำดับเดียวกับแอป SMS Checker)
     */
    private function billAmount(FortuneReading $r): float
    {
        if ((float) $r->amount_paid > 0) {
            return round((float) $r->amount_paid, 2);
        }

        $upa = $r->relationLoaded('uniquePaymentAmount') ? $r->uniquePaymentAmount : null;

        return $upa ? round((float) $upa->unique_amount, 2) : 0.0;
    }

    private function statusReason(FortuneReading $r, string $status): ?string
    {
        return match ($status) {
            'awaiting' => FortuneBillBuckets::claimReason($r),
            'cancelled' => strtolower((string) $r->conversation_status) === 'cancelled' && ! $r->isCancelled()
                ? 'rejected_in_app'
                : ((string) ($r->getConversationState('cancellation_reason') ?: 'unknown')),
            'refunded' => $r->getConversationState('approval_voided') ? 'approval_voided' : 'refund_flagged',
            default => null,
        };
    }

    private function statusLabel(FortuneReading $r, string $status, ?string $reason): string
    {
        return match ($status) {
            'paid' => 'ชำระแล้ว',
            'awaiting' => match ($reason) {
                'floating' => 'บิลลอย · เงินเข้าแต่ยังไม่รู้เจ้าของ',
                'transfer_reported' => 'รอตรวจ · ลูกค้าแจ้งโอนแล้ว',
                default => 'รอตรวจ · ลูกค้าส่งสลิปแล้ว',
            },
            'refunded' => $reason === 'approval_voided' ? 'ยกเลิกการอนุมัติแล้ว' : 'คืนเงินแล้ว',
            'cancelled' => $reason === 'rejected_in_app'
                ? 'ยกเลิกโดยระบบ (ปฏิเสธจากแอป)'
                : FortuneReading::getCancellationReasonLabel($reason),
            'unpaid' => 'รอลูกค้าโอน',
            default => 'ปิดโดยไม่ได้ชำระ',
        };
    }

    private function questionPreview(FortuneReading $r): ?string
    {
        $q = is_array($r->questions) ? trim((string) ($r->questions[0] ?? '')) : '';
        if ($q === '') {
            $q = $this->celticFirstQuestion[(int) $r->id] ?? '';
        }
        if ($q === '') {
            return null;
        }

        return mb_strlen($q) > self::QUESTION_PREVIEW_CHARS
            ? mb_substr($q, 0, self::QUESTION_PREVIEW_CHARS).'…'
            : $q;
    }

    /**
     * URL รูปสลิป — รูปในเซิร์ฟเวอร์ต้องผ่าน endpoint ที่ล็อกอินแอดมิน (PDPA: มีชื่อ/เลขบัญชีผู้โอน)
     *
     * @return array{url: string|null, requires_auth: bool}
     */
    private function slipUrl(FortuneReading $r): array
    {
        if (! empty($this->slipPath[(int) $r->id])) {
            return [
                'url' => URL::to('/api/admin/fortune/bills/'.$r->id.'/slip'),
                'requires_auth' => true,
            ];
        }

        if (! empty($r->user_image_url)) {
            return ['url' => (string) $r->user_image_url, 'requires_auth' => false];
        }

        return ['url' => null, 'requires_auth' => false];
    }

    /**
     * path รูปสลิปของบิล (ใช้ใน endpoint สตรีมรูปด้วย) — รูปที่รอตรวจก่อน ไม่มีค่อยหาใน log ตรวจสลิป
     */
    public static function resolveSlipPath(FortuneReading $r): ?string
    {
        $disk = Storage::disk('local');

        $own = (string) ($r->slip_image_path ?? '');
        if ($own !== '' && $disk->exists($own)) {
            return $own;
        }

        try {
            $logPath = (string) (SlipVerificationLog::query()
                ->where('fortune_reading_id', $r->id)
                ->whereNotNull('slip_image_path')
                ->orderByDesc('id')
                ->value('slip_image_path') ?? '');
        } catch (\Throwable $e) {
            $logPath = '';
        }

        return $logPath !== '' && $disk->exists($logPath) ? $logPath : null;
    }

    /**
     * @param  Collection<int, FortuneReading>  $readings
     */
    private function preloadCelticQuestions(Collection $readings): void
    {
        $ids = $readings
            ->filter(fn (FortuneReading $r) => $r->reading_type === FortuneReading::READING_TYPE_CELTIC_CROSS
                && trim((string) (is_array($r->questions) ? ($r->questions[0] ?? '') : '')) === '')
            ->pluck('id')
            ->all();

        if ($ids === []) {
            return;
        }

        try {
            FortuneCelticQuestion::query()
                ->whereIn('fortune_reading_id', $ids)
                ->orderBy('sequence')
                ->get(['fortune_reading_id', 'question'])
                ->each(function ($q) {
                    $rid = (int) $q->fortune_reading_id;
                    if (! isset($this->celticFirstQuestion[$rid])) {
                        $this->celticFirstQuestion[$rid] = trim((string) $q->question);
                    }
                });
        } catch (\Throwable $e) {
            // ตารางคำถาม Celtic อ่านไม่ได้ = ขาดแค่ตัวอย่างคำถาม ไม่ทำให้รายการพัง
        }
    }

    /**
     * @param  Collection<int, FortuneReading>  $readings
     */
    private function preloadSlips(Collection $readings): void
    {
        $disk = Storage::disk('local');
        $missing = [];

        foreach ($readings as $r) {
            $own = (string) ($r->slip_image_path ?? '');
            if ($own !== '' && $disk->exists($own)) {
                $this->slipPath[(int) $r->id] = $own;
            } else {
                $missing[] = (int) $r->id;
            }
        }

        if ($missing === []) {
            return;
        }

        try {
            SlipVerificationLog::query()
                ->whereIn('fortune_reading_id', $missing)
                ->whereNotNull('slip_image_path')
                ->orderByDesc('id')
                ->get(['fortune_reading_id', 'slip_image_path'])
                ->each(function ($log) use ($disk) {
                    $rid = (int) $log->fortune_reading_id;
                    if (! empty($this->slipPath[$rid])) {
                        return;
                    }
                    $path = (string) $log->slip_image_path;
                    if ($path !== '' && $disk->exists($path)) {
                        $this->slipPath[$rid] = $path;
                    }
                });
        } catch (\Throwable $e) {
            // log ตรวจสลิปอ่านไม่ได้ = ไม่มีรูปให้ดู ไม่ใช่ error ของรายการ
        }
    }

    /**
     * นับบิลที่ลูกค้าคนเดียวกันจ่ายแล้ว "ก่อน" บิลนี้ — query เดียวทั้งหน้า
     *
     * @param  Collection<int, FortuneReading>  $readings
     */
    private function preloadPriorPaid(Collection $readings): void
    {
        $uids = $readings
            ->flatMap(fn (FortuneReading $r) => [$r->platform_user_id, $r->facebook_user_id])
            ->filter(fn ($v) => is_string($v) && $v !== '')
            ->unique()
            ->values()
            ->all();

        if ($uids === []) {
            return;
        }

        $paidRows = DB::table('fortune_readings')
            ->whereNull('deleted_at')
            ->where('is_paid', true)
            ->where(function ($q) use ($uids) {
                $q->whereIn('platform_user_id', $uids)->orWhereIn('facebook_user_id', $uids);
            })
            ->limit(5000)
            ->get(['id', 'platform_user_id', 'facebook_user_id', 'created_at']);

        foreach ($readings as $r) {
            $mine = array_filter([$r->platform_user_id, $r->facebook_user_id], fn ($v) => is_string($v) && $v !== '');
            if ($mine === []) {
                continue;
            }
            $createdTs = $r->created_at?->getTimestamp() ?? PHP_INT_MAX;

            $this->priorPaid[(int) $r->id] = $paidRows->filter(function ($row) use ($r, $mine, $createdTs) {
                if ((int) $row->id === (int) $r->id) {
                    return false;
                }
                $sameCustomer = in_array($row->platform_user_id, $mine, true) || in_array($row->facebook_user_id, $mine, true);

                return $sameCustomer && strtotime((string) $row->created_at) < $createdTs;
            })->count();
        }
    }
}
