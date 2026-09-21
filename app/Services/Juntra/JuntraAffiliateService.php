<?php

namespace App\Services\Juntra;

use App\Models\FortuneCommission;
use App\Models\FortuneReading;
use App\Models\FortuneTellingSetting;
use App\Models\JuntraAccount;
use App\Models\JuntraVoidedBill;
use App\Models\MlmMember;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use App\Services\FortuneCommissionService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 🌙 (2026-09-21) บิลของเว็บ/แอพจันทรา → ผังแม่หมอคำนวณปันผล
 *
 * เจ้าของสั่ง: จันทราไม่คำนวณค่าแนะนำเอง ทุกบิลที่ขายบนเว็บและในแอพต้องส่งมาที่นี่
 *   ที่นี่เก็บข้อมูลและคำนวณทั้งหมด จันทราเป็นแค่ที่แสดงผล/สั่งงานผ่าน API
 *
 * สามงาน (ทุกงาน idempotent — จันทรายิงซ้ำได้เสมอ):
 *   - ensureAccount: ลูกค้าจันทรา → ผู้ใช้ Thaiprompt + สมาชิกผังแม่หมอ (ใต้ผู้เชิญ หรือผู้แนะนำเริ่มต้น)
 *   - recordBill:    บิลที่จ่ายแล้ว → แถว fortune_readings ประเภท juntra → ค่าแนะนำ L1/L2 (% ของยอดบิล)
 *   - voidBill:      จันทราคืนเงินลูกค้า → ดึงค่าแนะนำคืน (voidApproval ตัวเดียวกับหลังบ้าน)
 *
 * ล็อกทุกตัวรอไม่เกิน 5 วิ — สั้นกว่าเวลาที่จันทรารอคำตอบ (10-15 วิ) ไม่งั้นจันทราเลิกรอ
 *   ทั้งที่ที่นี่ยังทำงานอยู่ แล้วเข้าใจผิดว่าบิลยังไม่ถึง
 */
class JuntraAffiliateService
{
    public function __construct(
        private FortuneAffiliateService $affiliate,
        private FortuneCommissionService $commissions,
        private JuntraMlmReadService $reads,
        private JuntraAccountMerger $merger,
    ) {}

    /**
     * หา/สร้างตัวตนแม่หมอของลูกค้าจันทรา แล้วต่อสายงาน
     *
     * ไม่ย้ายสายงานเด็ดขาด: มีสมาชิกอยู่แล้ว = ใช้ผู้แนะนำเดิม (รหัสเชิญที่ส่งมาทีหลังไม่มีผล)
     *
     * @param  int|null  $thaipromptUserId  ผู้ใช้ Thaiprompt ที่ลูกค้าผูกไว้ (SSO) — ใช้ตอนสร้างครั้งแรกเท่านั้น
     * @param  string|null  $referralCode  member_code ของผู้เชิญ (จันทรา.online/r/{code})
     * @return array{account: JuntraAccount, user: User, member: ?MlmMember, enrolled_now: bool,
     *               referral: array{code: ?string, applied: bool, reason_code: ?string}}
     *
     * @throws JuntraAffiliateException
     */
    public function ensureAccount(int $juntraUserId, string $name, ?int $thaipromptUserId = null, ?string $referralCode = null): array
    {
        $code = $this->sanitizeCode($referralCode);

        // หนึ่งลูกค้าทีละคำขอ — บิลกับหน้าแดชบอร์ดมาพร้อมกันได้ ห้ามได้สองบัญชี/สองตำแหน่งในผัง
        $lock = $this->lockOrBusy("juntra:affiliate-account:{$juntraUserId}", 'มีคำขอของลูกค้าคนนี้ค้างอยู่ กรุณาลองใหม่');

        try {
            $account = $this->resolveAccount($juntraUserId, $name, $thaipromptUserId);

            // ลูกค้าเพิ่งผูก Thaiprompt ทั้งที่เคยมีบัญชีเงา → รวมเข้าบัญชี Thaiprompt (เจ้าของสั่ง: Thaiprompt เป็นตัวหลัก)
            if ($thaipromptUserId && $account->linked_via === JuntraAccount::LINKED_VIA_AUTO
                && (int) $account->user_id !== $thaipromptUserId) {
                $account = $this->merger->mergeIntoThaiprompt($account, $thaipromptUserId);
            }

            $user = $account->user;

            $member = MlmMember::where('user_id', $user->id)->first();
            if ($member) {
                return [
                    'account' => $account,
                    'user' => $user,
                    'member' => $member,
                    'enrolled_now' => false,
                    'referral' => [
                        'code' => $code,
                        'applied' => false,
                        'reason_code' => $code !== null ? 'already_enrolled' : null,
                    ],
                ];
            }

            [$sponsor, $referral] = $this->pickSponsor($user, $code);
            if (! $sponsor) {
                throw new JuntraAffiliateException('no_default_sponsor', 'ยังไม่มีผู้แนะนำเริ่มต้นในผังแม่หมอ', 503);
            }

            $member = $this->affiliate->enrollUserUnderSponsor($user, $sponsor);
            if (! $member) {
                throw new JuntraAffiliateException('enrollment_failed', 'ต่อสายงานไม่สำเร็จ กรุณาลองใหม่', 503);
            }
            // ตำแหน่งนี้จันทราสร้าง — หลังบ้านจันทราย้ายสายได้ (ตำแหน่งเดิมของบัญชี Thaiprompt ไม่ได้)
            $account->update(['enrolled_member_id' => $member->id]);

            $this->reads->forgetCachesForUpline($member);

            Log::info('Juntra affiliate: ลูกค้าจันทราเข้าผังแม่หมอแล้ว', [
                'juntra_user_id' => $juntraUserId,
                'user_id' => $user->id,
                'member_id' => $member->id,
                'sponsor_member_id' => $sponsor->id,
                'referral' => $referral,
            ]);

            return [
                'account' => $account,
                'user' => $user,
                'member' => $member,
                'enrolled_now' => true,
                'referral' => $referral,
            ];
        } finally {
            $lock->release();
        }
    }

    /**
     * บันทึกบิลที่จ่ายแล้วของจันทรา แล้วแจกค่าแนะนำ
     *
     * บิลเลขเดียวกันทำทีละคำขอ (ล็อกรายบิล — ตัวเดียวกับ voidBill) และบิลที่ถูกสั่งยกเลิกไว้ก่อน
     * (juntra_voided_bills) จะไม่แจกค่าแนะนำอีก
     *
     * @param  array{bill_id: int, user_ref: int, name: string, amount: float|string, product: string,
     *               paid_at?: ?string, thaiprompt_user_id?: ?int, referral_code?: ?string}  $bill
     * @return array{reading: ?FortuneReading, bill_reference: string, status: 'paid'|'voided', member: ?MlmMember,
     *               duplicate: bool, commissions: array<int, FortuneCommission>}
     *
     * @throws JuntraAffiliateException
     */
    public function recordBill(array $bill): array
    {
        $billId = (int) $bill['bill_id'];
        $billReference = FortuneReading::JUNTRA_BILL_PREFIX.$billId;

        $ensured = $this->ensureAccount(
            (int) $bill['user_ref'],
            (string) $bill['name'],
            isset($bill['thaiprompt_user_id']) ? (int) $bill['thaiprompt_user_id'] : null,
            $bill['referral_code'] ?? null,
        );
        $user = $ensured['user'];
        $member = $ensured['member'];

        $lock = $this->lockOrBusy("juntra:affiliate-bill:{$billId}", 'บิลนี้กำลังถูกบันทึก/ยกเลิกอยู่ กรุณาลองใหม่');

        try {
            $reading = FortuneReading::where('bill_reference', $billReference)->first();

            if (! $reading && JuntraVoidedBill::where('juntra_bill_id', $billId)->exists()) {
                // จันทราสั่งยกเลิกบิลนี้ไว้ก่อนบิลมาถึง = ลูกค้าได้เงินคืนแล้ว ห้ามแจกค่าแนะนำ
                return [
                    'reading' => null,
                    'bill_reference' => $billReference,
                    'status' => 'voided',
                    'member' => $member,
                    'duplicate' => true,
                    'commissions' => [],
                ];
            }

            $duplicate = $reading !== null;
            if ($reading && (! $reading->isJuntraBill() || (int) $reading->user_id !== (int) $user->id)) {
                throw new JuntraAffiliateException('bill_conflict', 'เลขบิลนี้ถูกใช้กับลูกค้าคนอื่นแล้ว', 409);
            }

            if (! $reading) {
                $amount = round((float) $bill['amount'], 2);
                $paidAt = ! empty($bill['paid_at']) ? Carbon::parse($bill['paid_at']) : now();

                $reading = FortuneReading::create([
                    'bill_reference' => $billReference,
                    'user_id' => $user->id,
                    'platform' => 'juntra',
                    // คอลัมน์นี้ห้ามว่าง — ใช้ id ที่ไม่ใช่ช่องทางแชทใด ๆ (ส่งข้อความหาไม่ได้โดยตั้งใจ)
                    'facebook_user_id' => 'jw-u'.(int) $bill['user_ref'],
                    'platform_user_id' => null,
                    'facebook_user_name' => Str::limit((string) $bill['name'], 190, ''),
                    'questions' => [],
                    'reading_type' => FortuneReading::READING_TYPE_JUNTRA,
                    'conversation_status' => FortuneReading::STATUS_COMPLETED,
                    'conversation_state' => [
                        'source' => 'juntra',
                        'juntra_bill_id' => $billId,
                        'juntra_product' => Str::limit((string) $bill['product'], 60, ''),
                    ],
                    'is_paid' => true,
                    'amount_paid' => $amount,
                    'amount_received' => $amount,
                    'service_fee' => 0,
                    'paid_at' => $paidAt,
                    'responded_at' => $paidAt,
                ]);
            }

            // บิลที่ถูกยกเลิกไปแล้วห้ามจ่ายค่าแนะนำซ้ำ — ส่งซ้ำมาก็แค่รายงานสถานะ
            if ($reading->is_paid && $member) {
                $settings = FortuneTellingSetting::getSettings();
                if ($settings->isFortuneAffiliateEnabled()) {
                    // เรียกซ้ำได้ปลอดภัย: กันจ่ายซ้ำรายชั้นอยู่ในตัว (ซ่อมชั้นที่เคยจ่ายไม่สำเร็จด้วย)
                    $this->commissions->distributeCommissions($reading, $member, $settings);
                }
            }
        } finally {
            $lock->release();
        }

        $commissions = FortuneCommission::with('user:id,name')
            ->where('fortune_reading_id', $reading->id)
            ->orderBy('level')
            ->get();

        foreach ($commissions as $commission) {
            $this->reads->forgetCachesFor((int) $commission->user_id);
        }
        $this->reads->forgetCachesFor((int) $user->id);

        return [
            'reading' => $reading,
            'bill_reference' => $billReference,
            'status' => $reading->is_paid ? 'paid' : 'voided',
            'member' => $member,
            'duplicate' => $duplicate,
            'commissions' => $commissions->all(),
        ];
    }

    /**
     * จันทราคืนเงินบิลนี้ให้ลูกค้าแล้ว → ดึงค่าแนะนำคืนจากทุกคนที่ได้ไป
     *
     * บิลที่ยังไม่เคยมาถึง: จดป้ายไว้ (juntra_voided_bills) — ถ้าบิลตามมาทีหลังจะไม่แจกค่าแนะนำ
     *
     * @return array{found: bool, already_voided: bool, reverted: array, warnings: array}
     *
     * @throws JuntraAffiliateException
     */
    public function voidBill(int $billId, ?string $reason = null): array
    {
        $lock = $this->lockOrBusy("juntra:affiliate-bill:{$billId}", 'บิลนี้กำลังถูกบันทึกอยู่ กรุณาลองใหม่');

        try {
            $reading = FortuneReading::where('bill_reference', FortuneReading::JUNTRA_BILL_PREFIX.$billId)
                ->where('reading_type', FortuneReading::READING_TYPE_JUNTRA)
                ->first();

            if (! $reading) {
                JuntraVoidedBill::query()->insertOrIgnore([
                    'juntra_bill_id' => $billId,
                    'reason' => Str::limit((string) $reason, 250, ''),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return ['found' => false, 'already_voided' => false, 'reverted' => [], 'warnings' => []];
            }

            $recipientIds = FortuneCommission::where('fortune_reading_id', $reading->id)->pluck('user_id')->all();

            $note = 'จันทราคืนเงินลูกค้า'.($reason ? ': '.Str::limit($reason, 200, '') : '');
            $result = $reading->voidApproval($note, null, juntraRefund: true);

            // voidApproval ดึงคืนเฉพาะรายการที่จ่ายเข้ากระเป๋าแล้ว — รายการที่ยังรอ/อนุมัติ (เช่นสร้างด้วยมือ)
            //   ต้องปิดด้วย ไม่งั้นแอดมินยังกดจ่ายค่าแนะนำของบิลที่คืนเงินไปแล้วได้
            DB::transaction(function () use ($reading, $note) {
                FortuneCommission::where('fortune_reading_id', $reading->id)
                    ->whereIn('status', [FortuneCommission::STATUS_PENDING, FortuneCommission::STATUS_APPROVED])
                    ->lockForUpdate() // ชนกับการกดจ่ายพร้อมกัน — payOut ล็อกแถวเดียวกันแล้วตรวจสถานะซ้ำ
                    ->get()
                    ->each(fn (FortuneCommission $c) => $c->forceFill([
                        'status' => FortuneCommission::STATUS_REJECTED,
                        'rejected_at' => now(),
                        'notes' => trim(($c->notes ?? '').' · ยกเลิก: '.$note),
                    ])->save());
            });
        } finally {
            $lock->release();
        }

        foreach ($recipientIds as $uid) {
            $this->reads->forgetCachesFor((int) $uid);
        }
        if ($reading->user_id) {
            $this->reads->forgetCachesFor((int) $reading->user_id);
        }

        return [
            'found' => true,
            'already_voided' => ! $result['ok'],
            'reverted' => $result['reverted'] ?? [],
            'warnings' => $result['warnings'] ?? [],
        ];
    }

    /** ผู้ใช้ Thaiprompt ของลูกค้าจันทราคนนี้ — null = ยังไม่เคยเข้าระบบ (ยังไม่มีบิล/ยังไม่เปิดหน้าสายงาน) */
    public function userIdFor(int $juntraUserId): ?int
    {
        $id = JuntraAccount::where('juntra_user_id', $juntraUserId)->value('user_id');

        return $id !== null ? (int) $id : null;
    }

    /* ============================================================
       INTERNAL
       ============================================================ */

    /**
     * ตัวตนถาวรของลูกค้าจันทรา — สร้างครั้งแรกแล้วไม่เปลี่ยนอีก
     *
     * ผูก Thaiprompt ไว้ → ใช้ผู้ใช้คนนั้น (ถ้ายังไม่ถูกลูกค้าจันทราคนอื่นจองไป)
     * ไม่ผูก → สร้างผู้ใช้ให้ แบบเดียวกับที่บอทสร้างให้ลูกค้า FB/LINE (รหัสผ่านสุ่ม ไม่มีใครรู้)
     *
     * 🔒 ห้ามหาผู้ใช้ด้วยอีเมล: id ฝั่งจันทราเป็นเลขเรียง และหน้าสมัคร/แก้โปรไฟล์ของเรารับอีเมล
     *
     *   @thaiprompt.local ได้ → ใครก็จองอีเมลที่เดาได้ไว้ก่อน แล้วรอยึดตัวตน (และค่าแนะนำทั้งสาย)
     *   ของลูกค้าคนนั้นได้ ตัวเชื่อมมีทางเดียวคือ juntra_accounts · อีเมลเงาจึงสุ่มท้ายให้เดาไม่ได้
     */
    private function resolveAccount(int $juntraUserId, string $name, ?int $thaipromptUserId): JuntraAccount
    {
        $existing = JuntraAccount::where('juntra_user_id', $juntraUserId)->first();
        if ($existing) {
            $user = User::find($existing->user_id);
            if (! $user) {
                throw new JuntraAffiliateException('account_disabled', 'บัญชีแม่หมอของลูกค้าคนนี้ถูกปิดไปแล้ว', 409);
            }

            return $existing->setRelation('user', $user);
        }

        return DB::transaction(function () use ($juntraUserId, $name, $thaipromptUserId) {
            $user = null;
            $via = JuntraAccount::LINKED_VIA_AUTO;

            if ($thaipromptUserId) {
                $candidate = User::find($thaipromptUserId);
                $taken = $candidate && JuntraAccount::where('user_id', $candidate->id)->exists();
                if ($candidate && ! $taken) {
                    $user = $candidate;
                    $via = JuntraAccount::LINKED_VIA_SSO;
                } elseif ($taken) {
                    Log::warning('Juntra affiliate: ผู้ใช้ Thaiprompt นี้ผูกกับลูกค้าจันทราคนอื่นแล้ว — สร้างตัวตนแยกให้', [
                        'juntra_user_id' => $juntraUserId,
                        'thaiprompt_user_id' => $thaipromptUserId,
                    ]);
                }
            }

            $createdShadow = false;
            if (! $user) {
                $user = User::create([
                    'name' => Str::limit(trim($name) !== '' ? trim($name) : 'ลูกค้าจันทรา', 190, ''),
                    'email' => 'juntra_'.$juntraUserId.'_'.Str::lower(Str::random(16)).'@thaiprompt.local',
                    // ลูกค้ากลุ่มนี้ไม่เคยล็อกอิน Thaiprompt ด้วยรหัสผ่าน — สุ่มทิ้งไว้ ไม่มีใครรู้
                    'password' => Hash::make(Str::random(48)),
                ]);
                $createdShadow = true;
            }

            try {
                return JuntraAccount::create([
                    'juntra_user_id' => $juntraUserId,
                    'user_id' => $user->id,
                    'linked_via' => $via,
                ])->setRelation('user', $user);
            } catch (UniqueConstraintViolationException $e) {
                // อีกคำขอสร้างให้ไปแล้ว (ล็อกหมดอายุระหว่างทาง) — ทิ้งผู้ใช้เงาที่เพิ่งสร้าง แล้วใช้ของเดิม
                if ($createdShadow) {
                    $user->forceDelete();
                }
                $winner = JuntraAccount::where('juntra_user_id', $juntraUserId)->firstOrFail();

                return $winner->setRelation('user', User::findOrFail($winner->user_id));
            }
        });
    }

    /**
     * ผู้แนะนำของสมาชิกใหม่: ผู้เชิญตามรหัส ถ้าใช้ได้ — ไม่งั้นผู้แนะนำเริ่มต้นแบบเดียวกับบอท
     *
     * @return array{0: ?MlmMember, 1: array{code: ?string, applied: bool, reason_code: ?string}}
     */
    private function pickSponsor(User $user, ?string $code): array
    {
        $reason = null;

        if ($code !== null) {
            $sponsor = MlmMember::with('user:id,name')->where('member_code', $code)->first();
            // ลิงก์เชิญเดิมของบัญชีเงาที่รวมเข้าบัญชี Thaiprompt แล้ว → ต่อใต้ตำแหน่งของบัญชีจริง
            //   (ตำแหน่งเงาถูกปิด แต่ลิงก์ที่ลูกค้าแจกไปแล้วยังต้องใช้ได้)
            if ($sponsor && $sponsor->status !== 'active'
                && ($merged = JuntraAccount::where('merged_from_user_id', $sponsor->user_id)->first())) {
                $sponsor = MlmMember::with('user:id,name')->where('user_id', $merged->user_id)->first() ?? $sponsor;
            }
            if (! $sponsor) {
                $reason = 'invalid_code';
            } elseif ((int) $sponsor->user_id === (int) $user->id) {
                $reason = 'self_referral';
            } elseif ($sponsor->status !== 'active') {
                $reason = 'sponsor_inactive';
            } else {
                return [$sponsor, ['code' => $code, 'applied' => true, 'reason_code' => null]];
            }
        }

        return [
            $this->affiliate->defaultSponsor(),
            ['code' => $code, 'applied' => false, 'reason_code' => $reason],
        ];
    }

    /**
     * ล็อกแบบรอสั้น (5 วิ) — ไม่ได้ = 503 busy ให้จันทราลองใหม่รอบหน้า
     *
     * @throws JuntraAffiliateException
     */
    private function lockOrBusy(string $key, string $message): Lock
    {
        $lock = Cache::lock($key, 30);
        try {
            $lock->block(5);
        } catch (LockTimeoutException $e) {
            throw new JuntraAffiliateException('busy', $message, 503);
        }

        return $lock;
    }

    private function sanitizeCode(?string $code): ?string
    {
        $clean = substr(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $code) ?? '', 0, 64);

        return $clean !== '' ? $clean : null;
    }
}
