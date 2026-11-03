<?php

namespace App\Services\Juntra;

use App\Models\FortuneCommission;
use App\Models\FortuneMessengerPsid;
use App\Models\FortunePage;
use App\Models\FortuneReading;
use App\Models\FortuneReferral;
use App\Models\FortuneTellingSetting;
use App\Models\JuntraAccount;
use App\Models\JuntraVoidedBill;
use App\Models\MlmMember;
use App\Models\User;
use App\Services\FortuneAffiliateService;
use App\Services\FortuneCommissionService;
use App\Services\LineAlertService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
 * 🌐 (2026-11-03) FB web-only (ลูกค้า FB ทั้งหมดย้ายมาจ่ายบนเว็บจันทรา):
 *   - recordBill ติดป้ายสาขา (fortune_page_id) ให้บิลและค่าแนะนำ — page_code จากจันทรา ไม่งั้นสาขาต้นทางของผู้ใช้ (M6)
 *   - ผู้ใช้ Thaiprompt ที่ถูกบัญชีจันทราอื่นผูกไว้ → 409 thaiprompt_taken + แจ้งแอดมิน ห้ามสร้างเงาเงียบ ๆ (M4)
 *   - releaseAccount: จันทราลบบัญชี → ปลดแถว ให้บัญชีใหม่ที่ผูก Thaiprompt คนเดิมชี้มาที่แถวเดิมได้
 *   - pickSponsor ลองผู้เชิญจาก FortuneReferral (LINE/PSID/alias ของผู้ใช้) ก่อนตกไปผู้แนะนำเริ่มต้น (M7)
 *   🔁 (รีวิว) ทั้ง 4 ข้อข้างบนทำงานเฉพาะตอนธง facebook_web_only เปิด — ธงปิด = พฤติกรรมเดิมทุกประการ
 *      (ไม่ติดป้ายสาขา · สร้างเงาเมื่อผู้ใช้ Thaiprompt ถูกจองแล้ว · ไม่สนแถวที่ปลดแล้ว · ผู้เชิญ = รหัส/ค่าเริ่มต้น)
 *      endpoint/คำสั่ง release และตาราง merge alias ยังอยู่ (เก็บข้อมูลไว้เฉย ๆ ไม่มีผลกับผลลัพธ์ตอนธงปิด)
 *
 * ล็อกทุกตัวรอไม่เกิน 5 วิ — สั้นกว่าเวลาที่จันทรารอคำตอบ (10-15 วิ) ไม่งั้นจันทราเลิกรอ
 *   ทั้งที่ที่นี่ยังทำงานอยู่ แล้วเข้าใจผิดว่าบิลยังไม่ถึง
 */
class JuntraAffiliateService
{
    /** แจ้งแอดมินเรื่อง thaiprompt_taken ซ้ำได้ทุกกี่ชั่วโมงต่อคู่ (จันทรายิงบิลซ้ำทุกนาทีจนกว่าจะผ่าน) */
    public const TAKEN_ALERT_COOLDOWN_HOURS = 6;

    /** memo ต่อโปรเซส: คอลัมน์ใหม่มีหรือยัง (โค้ดขึ้นก่อน migrate ต้องไม่พัง) */
    private static ?bool $releaseColumnsReady = null;

    private static ?bool $originPageColumnReady = null;

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
     * @param  string|null  $pageCode  fortune_pages.code ของสาขาที่พาลูกค้ามา (ไม่บังคับ) — จดเป็นสาขาต้นทางครั้งเดียว
     * @return array{account: JuntraAccount, user: User, member: ?MlmMember, enrolled_now: bool,
     *               referral: array{code: ?string, applied: bool, reason_code: ?string}}
     *
     * @throws JuntraAffiliateException
     */
    public function ensureAccount(int $juntraUserId, string $name, ?int $thaipromptUserId = null, ?string $referralCode = null, ?string $pageCode = null): array
    {
        $code = $this->sanitizeCode($referralCode);
        // 🔁 ธงปิด = เส้นเดิมทั้งหมด (ดู docblock ของคลาส)
        $webOnly = $this->fbWebOnlyOn();

        // หนึ่งลูกค้าทีละคำขอ — บิลกับหน้าแดชบอร์ดมาพร้อมกันได้ ห้ามได้สองบัญชี/สองตำแหน่งในผัง
        $lock = $this->lockOrBusy("juntra:affiliate-account:{$juntraUserId}", 'มีคำขอของลูกค้าคนนี้ค้างอยู่ กรุณาลองใหม่');

        try {
            $account = $this->resolveAccount($juntraUserId, $name, $thaipromptUserId, $webOnly);

            // ลูกค้าเพิ่งผูก Thaiprompt ทั้งที่เคยมีบัญชีเงา → รวมเข้าบัญชี Thaiprompt (เจ้าของสั่ง: Thaiprompt เป็นตัวหลัก)
            if ($thaipromptUserId && $account->linked_via === JuntraAccount::LINKED_VIA_AUTO
                && (int) $account->user_id !== $thaipromptUserId) {
                $account = $webOnly
                    ? $this->mergeIntoThaiprompt($account, $thaipromptUserId)
                    : $this->merger->mergeIntoThaiprompt($account, $thaipromptUserId);
            }

            $user = $account->user;

            // สาขาต้นทาง (เขียนครั้งเดียว) — gateway จดจาก token แล้ว ที่นี่เติมให้เฉพาะตอนยังว่าง (ธงเปิดเท่านั้น)
            if ($webOnly) {
                $this->rememberOriginPage($user, $this->pageIdFromCode($pageCode));
            }

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

            [$sponsor, $referral, $fortuneReferral] = $this->pickSponsor($user, $code, $webOnly);
            if (! $sponsor) {
                throw new JuntraAffiliateException('no_default_sponsor', 'ยังไม่มีผู้แนะนำเริ่มต้นในผังแม่หมอ', 503);
            }

            $member = $this->affiliate->enrollUserUnderSponsor($user, $sponsor);
            if (! $member) {
                throw new JuntraAffiliateException('enrollment_failed', 'ต่อสายงานไม่สำเร็จ กรุณาลองใหม่', 503);
            }
            // ตำแหน่งนี้จันทราสร้าง — หลังบ้านจันทราย้ายสายได้ (ตำแหน่งเดิมของบัญชี Thaiprompt ไม่ได้)
            $account->update(['enrolled_member_id' => $member->id]);

            // ผู้เชิญจากลิงก์ของบอท (FortuneReferral) — ปิดลิงก์ว่าแปลงเป็นสมาชิกแล้ว เหมือน createMlmMember ของบอท
            if ($fortuneReferral) {
                try {
                    $fortuneReferral->markAsConverted($user);
                } catch (\Throwable $e) {
                    Log::warning('Juntra affiliate: ปิดลิงก์เชิญของบอทไม่สำเร็จ (สายงานต่อแล้ว)', [
                        'referral_id' => $fortuneReferral->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

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
     * 🌙 (2026-09-23) history_only = บิลที่จ่ายก่อนเปิดระบบค่าแนะนำ (ก่อน 2026-09-21) — บันทึกไว้ว่า
     *   ลูกค้า "เคยมีบิลที่ชำระแล้ว" (สิทธิ์รับค่าแนะนำ ดู FortuneCommissionService::isEligibleRecipient)
     *   แต่ไม่แจกค่าแนะนำจากบิลนั้น (เจ้าของสั่ง: ไม่จ่ายย้อนหลัง) ส่งซ้ำมาก็ไม่แจก
     *
     * @param  array{bill_id: int, user_ref: int, name: string, amount: float|string, product: string,
     *               paid_at?: ?string, thaiprompt_user_id?: ?int, referral_code?: ?string, history_only?: bool,
     *               page_code?: ?string}  $bill
     *
     * 🏬 (2026-11-03) สาขาของบิล = page_code ที่จันทราส่งมา (ถ้ารู้จัก) ไม่งั้น users.fortune_origin_page_id
     *   ติดทั้งแถวบิลและแถวค่าแนะนำ — รายงานรายสาขานับบิลเว็บของลูกค้าที่ย้ายมาจากเพจนั้นได้
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
            $bill['page_code'] ?? null,
        );
        $user = $ensured['user'];
        $member = $ensured['member'];
        // 🏬 ป้ายสาขาของบิลเว็บ — เฉพาะธง facebook_web_only เปิด (ธงปิด = บิลจันทราไม่มีสาขาแบบเดิม)
        $stampPage = $this->fbWebOnlyOn();
        $pageId = $stampPage
            ? ($this->pageIdFromCode($bill['page_code'] ?? null) ?? $this->originPageIdOf($user))
            : null;

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
                    'conversation_state' => array_filter([
                        'source' => 'juntra',
                        'juntra_bill_id' => $billId,
                        'juntra_product' => Str::limit((string) $bill['product'], 60, ''),
                        'history_only' => ! empty($bill['history_only']) ? true : null,
                    ], fn ($v) => $v !== null),
                    'is_paid' => true,
                    'amount_paid' => $amount,
                    'amount_received' => $amount,
                    'service_fee' => 0,
                    'paid_at' => $paidAt,
                    'responded_at' => $paidAt,
                    'fortune_page_id' => $pageId,
                ]);
            } elseif ($pageId !== null && $reading->fortune_page_id === null) {
                // ส่งซ้ำหลังรู้สาขาแล้ว — เติมให้ครั้งเดียว (ไม่ย้ายสาขาของบิลที่มีป้ายแล้ว)
                $reading->forceFill(['fortune_page_id' => $pageId])->save();
            }

            // บิลที่ถูกยกเลิกไปแล้วห้ามจ่ายค่าแนะนำซ้ำ — ส่งซ้ำมาก็แค่รายงานสถานะ
            //   บิลประวัติก่อนเปิดระบบไม่แจกเลย (ตัดสินจากแถวที่บันทึกไว้ ไม่ใช่จากคำขอรอบนี้)
            if ($reading->is_paid && $member && ! $reading->isJuntraHistoryBill()) {
                $settings = FortuneTellingSetting::getSettings();
                if ($settings->isFortuneAffiliateEnabled()) {
                    // เรียกซ้ำได้ปลอดภัย: กันจ่ายซ้ำรายชั้นอยู่ในตัว (ซ่อมชั้นที่เคยจ่ายไม่สำเร็จด้วย)
                    $this->commissions->distributeCommissions($reading, $member, $settings);
                }
            }

            // ค่าแนะนำของบิลนี้อยู่สาขาเดียวกับบิล (เซิร์ฟเวอร์จันทราไม่มี FortunePageContext → trait เติมให้ไม่ได้)
            if ($stampPage && $reading->fortune_page_id !== null) {
                FortuneCommission::where('fortune_reading_id', $reading->id)
                    ->whereNull('fortune_page_id')
                    ->update(['fortune_page_id' => (int) $reading->fortune_page_id]);
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

    /**
     * 🌙 (2026-11-03) จันทราลบบัญชี user_ref นี้แล้ว → ปลดแถว juntra_accounts (ส่งซ้ำได้)
     *
     * แถวยังชี้ผู้ใช้เดิม — บิลค้างส่งของบัญชีเก่ายังลงคนเดิม สายงานและค่าแนะนำไม่ขยับ
     *   ต่อมาบัญชีจันทราใหม่ผูก Thaiprompt คนเดิม → resolveAccount ชี้แถวนี้ไปที่ user_ref ใหม่ (ไม่สร้างเงา)
     *
     * @return array{found: bool, already_released: bool, linked_via: ?string}
     *
     * @throws JuntraAffiliateException
     */
    public function releaseAccount(int $juntraUserId, ?string $reason = null): array
    {
        if (! self::releaseColumnsReady()) {
            // migration ยังไม่ขึ้น — ให้จันทราเก็บงานไว้ส่งซ้ำ
            throw new JuntraAffiliateException('not_ready', 'ระบบปลดบัญชียังไม่พร้อม กรุณาลองใหม่', 503);
        }

        $lock = $this->lockOrBusy("juntra:affiliate-account:{$juntraUserId}", 'มีคำขอของลูกค้าคนนี้ค้างอยู่ กรุณาลองใหม่');

        try {
            $account = JuntraAccount::where('juntra_user_id', $juntraUserId)->first();
            if (! $account) {
                return ['found' => false, 'already_released' => false, 'linked_via' => null];
            }

            if ($account->released_at !== null) {
                return ['found' => true, 'already_released' => true, 'linked_via' => $account->linked_via];
            }

            $account->update(['released_at' => now()]);

            // บันทึกตรวจสอบย้อนหลัง — การปลดเปลี่ยนว่าใครผูกผู้ใช้ Thaiprompt คนนี้ได้
            Log::info('Juntra affiliate: จันทราลบบัญชีแล้ว — ปลดการผูกให้บัญชีใหม่ผูกได้', [
                'juntra_user_id' => $juntraUserId,
                'user_id' => $account->user_id,
                'linked_via' => $account->linked_via,
                'reason' => Str::limit((string) $reason, 200, ''),
            ]);

            return ['found' => true, 'already_released' => false, 'linked_via' => $account->linked_via];
        } finally {
            $lock->release();
        }
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
     *
     * 🌐 (2026-11-03) ผู้ใช้ Thaiprompt ที่ขอผูกถูกบัญชีจันทราอื่นจองไว้:
     *   - แถวนั้นถูกปลดแล้ว (จันทราลบบัญชีเก่า) → ชี้แถวเดิมมาที่ user_ref นี้ (สายงาน/ตำแหน่งเดิมทั้งหมด)
     *   - ยังไม่ถูกปลด → 409 thaiprompt_taken + แจ้งแอดมิน · ห้ามสร้างเงาใต้ผู้แนะนำเริ่มต้นแบบเงียบ ๆ
     *     (เดิมสร้างเงา → บิลของคนจริงไปจ่ายค่าแนะนำให้ผู้แนะนำเริ่มต้นถาวร) จันทราถือ 409 นี้เป็นงานที่ลองใหม่ได้
     */
    private function resolveAccount(int $juntraUserId, string $name, ?int $thaipromptUserId, bool $webOnly = true): JuntraAccount
    {
        $existing = JuntraAccount::where('juntra_user_id', $juntraUserId)->first();
        if (! $existing && self::releaseColumnsReady()) {
            // บิลค้างส่งของบัญชีจันทราที่ถูกลบ หลังแถวถูกชี้ไปบัญชีใหม่แล้ว — ยังเป็นคนเดิม
            $existing = JuntraAccount::where('released_juntra_user_id', $juntraUserId)->first();
        }
        if ($existing) {
            $user = User::find($existing->user_id);
            if (! $user) {
                throw new JuntraAffiliateException('account_disabled', 'บัญชีแม่หมอของลูกค้าคนนี้ถูกปิดไปแล้ว', 409);
            }

            return $existing->setRelation('user', $user);
        }

        return DB::transaction(function () use ($juntraUserId, $name, $thaipromptUserId, $webOnly) {
            $user = null;
            $via = JuntraAccount::LINKED_VIA_AUTO;

            if ($thaipromptUserId && ! $webOnly) {
                // 🔁 ธงปิด = เส้นเดิม: ผู้ใช้ Thaiprompt ถูกบัญชีจันทราอื่นผูกแล้ว → สร้างตัวตนแยก (เงา)
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
            } elseif ($thaipromptUserId && ($candidate = User::find($thaipromptUserId))) {
                $holder = JuntraAccount::where('user_id', $candidate->id)->lockForUpdate()->first();

                if (! $holder) {
                    $user = $candidate;
                    $via = JuntraAccount::LINKED_VIA_SSO;
                } elseif (self::releaseColumnsReady() && $holder->released_at !== null) {
                    // บัญชีจันทราเก่าถูกลบแล้ว → ชี้แถวเดิมมาที่บัญชีใหม่ (ไม่สร้างเงา ไม่ย้ายสาย)
                    $oldRef = (int) $holder->juntra_user_id;
                    $holder->update([
                        'juntra_user_id' => $juntraUserId,
                        'released_juntra_user_id' => $oldRef,
                        'released_at' => null,
                        'linked_via' => JuntraAccount::LINKED_VIA_SSO,
                    ]);

                    Log::info('Juntra affiliate: บัญชีจันทราใหม่ผูก Thaiprompt คนเดิมหลังบัญชีเก่าถูกลบ — ชี้แถวเดิมมาที่บัญชีใหม่', [
                        'juntra_user_id' => $juntraUserId,
                        'previous_juntra_user_id' => $oldRef,
                        'user_id' => $candidate->id,
                    ]);

                    return $holder->setRelation('user', $candidate);
                } else {
                    $this->alertThaipromptTaken($juntraUserId, (int) $candidate->id, (int) $holder->juntra_user_id);

                    throw new JuntraAffiliateException(
                        'thaiprompt_taken',
                        'บัญชี Thaiprompt นี้ผูกกับบัญชีจันทราอื่นอยู่ — รอแอดมินตรวจ แล้วลองส่งใหม่',
                        409,
                    );
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
                if (! $webOnly) {
                    // 🔁 ธงปิด = เส้นเดิม (ไม่เจอ = ModelNotFoundException เหมือนเดิม)
                    $winner = JuntraAccount::where('juntra_user_id', $juntraUserId)->firstOrFail();

                    return $winner->setRelation('user', User::findOrFail($winner->user_id));
                }

                $winner = JuntraAccount::where('juntra_user_id', $juntraUserId)->first();
                if (! $winner) {
                    // ชน unique ของ user_id = บัญชีจันทราอื่นเพิ่งผูกผู้ใช้ Thaiprompt คนนี้ไปพร้อมกัน → ไม่สร้างเงา
                    throw new JuntraAffiliateException(
                        'thaiprompt_taken',
                        'บัญชี Thaiprompt นี้ผูกกับบัญชีจันทราอื่นอยู่ — รอแอดมินตรวจ แล้วลองส่งใหม่',
                        409,
                    );
                }

                return $winner->setRelation('user', User::findOrFail($winner->user_id));
            }
        });
    }

    /**
     * ผู้แนะนำของสมาชิกใหม่: ผู้เชิญตามรหัส ถ้าใช้ได้ — ไม่งั้นผู้เชิญจากลิงก์ของบอท (FortuneReferral)
     *   ของ LINE/PSID/alias ของผู้ใช้ — ไม่งั้นผู้แนะนำเริ่มต้นแบบเดียวกับบอท
     *
     * 🌐 (2026-11-03) ขั้น FortuneReferral = กฎเดียวกับ FortuneAffiliateService::createMlmMember ของบอท (M7)
     *   ลูกค้าที่ย้ายจาก FB/LINE มาเว็บแล้วยังไม่มีตำแหน่ง ต้องได้ผู้เชิญคนเดียวกับที่บอทจะให้
     *
     * 🔁 ขั้น FortuneReferral ทำเฉพาะตอนธง facebook_web_only เปิด — ธงปิด = รหัสเชิญ → ผู้แนะนำเริ่มต้น (เดิม)
     *
     * @return array{0: ?MlmMember, 1: array{code: ?string, applied: bool, reason_code: ?string, sponsor_source?: string}, 2: ?FortuneReferral}
     */
    private function pickSponsor(User $user, ?string $code, bool $webOnly = true): array
    {
        $reason = null;

        if ($code !== null) {
            $sponsor = MlmMember::with('user:id,name')->where('member_code', $code)->first();
            // ลิงก์เชิญเดิมของบัญชีเงาที่รวมเข้าบัญชี Thaiprompt แล้ว → ต่อใต้ตำแหน่งของบัญชีจริง
            //   (ตำแหน่งเงาถูกปิด แต่ลิงก์ที่ลูกค้าแจกไปแล้วยังต้องใช้ได้)
            if ($sponsor && $sponsor->status !== 'active'
                && ($mergedInto = self::mergedIntoUserId((int) $sponsor->user_id)) !== null) {
                $sponsor = MlmMember::with('user:id,name')->where('user_id', $mergedInto)->first() ?? $sponsor;
            }
            if (! $sponsor) {
                $reason = 'invalid_code';
            } elseif ((int) $sponsor->user_id === (int) $user->id) {
                $reason = 'self_referral';
            } elseif ($sponsor->status !== 'active') {
                $reason = 'sponsor_inactive';
            } else {
                return [$sponsor, ['code' => $code, 'applied' => true, 'reason_code' => null], null];
            }
        }

        [$referralSponsor, $fortuneReferral] = $webOnly ? $this->sponsorFromFortuneReferral($user) : [null, null];
        if ($referralSponsor) {
            return [
                $referralSponsor,
                ['code' => $code, 'applied' => false, 'reason_code' => $reason, 'sponsor_source' => 'fortune_referral'],
                $fortuneReferral,
            ];
        }

        return [
            $this->affiliate->defaultSponsor(),
            ['code' => $code, 'applied' => false, 'reason_code' => $reason],
            null,
        ];
    }

    /**
     * ผู้เชิญที่บอทรู้จาก FortuneReferral — ค้นด้วยทุก id แชทของผู้ใช้ (LINE, PSID หลัก, PSID สาขาใน alias)
     *
     * ผู้เชิญต้องยัง active และไม่ใช่ตัวเอง · พังกลางทาง = ไม่มีผู้เชิญ (ตกไปผู้แนะนำเริ่มต้นเหมือนเดิม)
     *
     * @return array{0: ?MlmMember, 1: ?FortuneReferral}
     */
    private function sponsorFromFortuneReferral(User $user): array
    {
        try {
            foreach ($this->platformIdsOf($user) as $platformUserId) {
                $referral = FortuneReferral::findActiveByPlatformUserId($platformUserId);
                $sponsor = $referral?->referrerMlmMember;

                if ($sponsor && $sponsor->status === 'active' && (int) $sponsor->user_id !== (int) $user->id) {
                    Log::info('Juntra affiliate: พบผู้เชิญจากลิงก์ของบอท', [
                        'user_id' => $user->id,
                        'referral_id' => $referral->id,
                        'sponsor_member_id' => $sponsor->id,
                    ]);

                    return [$sponsor, $referral];
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Juntra affiliate: หาผู้เชิญจาก FortuneReferral ไม่สำเร็จ — ใช้ผู้แนะนำเริ่มต้น', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        return [null, null];
    }

    /**
     * id แชททุกตัวของผู้ใช้ — line_user_id, facebook_psid และ PSID สาขาอื่นในตาราง alias (fortune_messenger_psids)
     *
     * @return array<int, string>
     */
    private function platformIdsOf(User $user): array
    {
        $attributes = $user->getAttributes();
        $ids = [];

        foreach (['line_user_id', 'facebook_psid'] as $column) {
            $value = $attributes[$column] ?? null;
            if (is_string($value) && $value !== '') {
                $ids[] = $value;
            }
        }

        if (FortuneMessengerPsid::tableReady()) {
            $ids = array_merge(
                $ids,
                FortuneMessengerPsid::where('user_id', $user->id)->orderBy('id')->pluck('psid')->all()
            );
        }

        return array_values(array_unique(array_map('strval', $ids)));
    }

    /**
     * รวมบัญชีเงาเข้าบัญชี Thaiprompt — ถ้าบัญชี Thaiprompt ถูกแถวที่ "ปลดแล้ว" ถืออยู่ ให้คืนที่ก่อนแล้วค่อยรวม
     *
     * รวมไม่สำเร็จ (ผังชนกัน ฯลฯ) → คืนแถวที่ปลดไว้กลับเหมือนเดิม (บิลค้างของบัญชีเก่ายังหาคนเดิมเจอ)
     */
    private function mergeIntoThaiprompt(JuntraAccount $account, int $thaipromptUserId): JuntraAccount
    {
        $released = self::releaseColumnsReady()
            ? JuntraAccount::where('user_id', $thaipromptUserId)
                ->whereNotNull('released_at')
                ->whereKeyNot($account->id)
                ->first()
            : null;

        // เพิ่งรวมไม่สำเร็จ = merger ไม่ลองอยู่แล้ว — อย่าแตะแถวที่ปลดไว้
        $backingOff = $account->merge_failed_at
            && $account->merge_failed_at->gt(now()->subHours(JuntraAccount::MERGE_RETRY_HOURS));

        if (! $released || $backingOff) {
            return $this->merger->mergeIntoThaiprompt($account, $thaipromptUserId);
        }

        // 🔐 (รีวิว) ล็อกบัญชีจันทราเก่าด้วย — บิลค้างของบัญชีเก่า (หาด้วย released_juntra_user_id) ต้องไม่วิ่งเข้ามา
        //    ระหว่าง "ลบแถวเก่า → รวม → เขียนกลับ" แล้วหาเจ้าของไม่เจอ · ทั้งก้อนอยู่ใน transaction เดียว
        $oldRef = (int) $released->juntra_user_id;
        $oldLock = $this->lockOrBusy("juntra:affiliate-account:{$oldRef}", 'มีคำขอของบัญชีจันทราเดิมค้างอยู่ กรุณาลองใหม่');

        try {
            return DB::transaction(fn () => $this->mergeReplacingReleased($account, $thaipromptUserId, $released));
        } finally {
            $oldLock->release();
        }
    }

    /**
     * แทนแถวที่ปลดแล้วด้วยบัญชีที่รวม (เรียกใน transaction + ล็อกทั้งสองบัญชีจันทรา)
     *
     * 🔐 (รีวิว) เก็บประวัติการรวมของแถวเก่า (merged_from_user_id) ลง juntra_account_merge_aliases ก่อนลบ
     *    ไม่งั้นลิงก์เชิญเดิมของเงาเก่า + sweep ของเงาเก่าใช้ไม่ได้อีก
     */
    private function mergeReplacingReleased(JuntraAccount $account, int $thaipromptUserId, JuntraAccount $released): JuntraAccount
    {
        $released = JuntraAccount::whereKey($released->id)->lockForUpdate()->first() ?? $released;
        $snapshot = $released->getAttributes();

        if (! empty($snapshot['merged_from_user_id'])) {
            self::rememberMergeAlias(
                (int) $snapshot['merged_from_user_id'],
                (int) $snapshot['user_id'],
                (int) $snapshot['juntra_user_id'],
                $snapshot['merged_at'] ?? null,
            );
        }

        $released->delete();

        $merged = $this->merger->mergeIntoThaiprompt($account, $thaipromptUserId);

        if ((int) $merged->user_id === $thaipromptUserId) {
            $realMemberId = MlmMember::where('user_id', $thaipromptUserId)->value('id');
            $carryMember = $merged->enrolled_member_id === null
                && ! empty($snapshot['enrolled_member_id'])
                && (int) $snapshot['enrolled_member_id'] === (int) $realMemberId;

            $merged->update(array_merge(
                ['released_juntra_user_id' => (int) $snapshot['juntra_user_id']],
                // ตำแหน่งของบัญชีจริงที่จันทราเคยสร้างไว้ (แถวเก่า) ยังเป็นของจันทรา — หลังบ้านจันทรายังจัดการได้
                $carryMember ? ['enrolled_member_id' => (int) $snapshot['enrolled_member_id']] : [],
            ));

            Log::info('Juntra affiliate: รวมบัญชีเงาเข้า Thaiprompt ที่เคยผูกกับบัญชีจันทราที่ถูกลบ', [
                'juntra_user_id' => $merged->juntra_user_id,
                'previous_juntra_user_id' => (int) $snapshot['juntra_user_id'],
                'user_id' => $thaipromptUserId,
            ]);

            return $merged;
        }

        // รวมไม่สำเร็จ — คืนแถวที่ปลดไว้ตามเดิม
        JuntraAccount::query()->insert($snapshot);

        return $merged;
    }

    /**
     * 🔐 (รีวิว) ผู้ใช้เงาคนนี้ถูกรวมเข้าบัญชี Thaiprompt ไหน (null = ไม่เคยรวม)
     *   ดูทั้งแถว juntra_accounts ปัจจุบัน และประวัติที่เหลือจากแถวที่ถูกแทนที่
     */
    public static function mergedIntoUserId(int $shadowUserId): ?int
    {
        $id = JuntraAccount::where('merged_from_user_id', $shadowUserId)->value('user_id');
        if ($id !== null) {
            return (int) $id;
        }

        try {
            if (Schema::hasTable('juntra_account_merge_aliases')) {
                $id = DB::table('juntra_account_merge_aliases')
                    ->where('merged_from_user_id', $shadowUserId)
                    ->orderByDesc('id')
                    ->value('user_id');
            }
        } catch (\Throwable $e) {
            $id = null;
        }

        return $id !== null ? (int) $id : null;
    }

    /**
     * 🔐 (รีวิว) จดคู่ (เงา → บัญชีจริง) ลงประวัติถาวร — ซ้ำได้ (unique คู่)
     */
    public static function rememberMergeAlias(int $shadowUserId, int $userId, ?int $juntraUserId, $mergedAt = null): void
    {
        if ($shadowUserId <= 0 || $userId <= 0 || $shadowUserId === $userId || ! Schema::hasTable('juntra_account_merge_aliases')) {
            return;
        }

        DB::table('juntra_account_merge_aliases')->insertOrIgnore([
            'merged_from_user_id' => $shadowUserId,
            'user_id' => $userId,
            'juntra_user_id' => $juntraUserId,
            'merged_at' => $mergedAt ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * ผู้ใช้ Thaiprompt ถูกบัญชีจันทราอื่นผูกไว้ — log ทุกครั้ง แจ้งแอดมิน (Telegram/LINE) ตามรอบ
     */
    private function alertThaipromptTaken(int $juntraUserId, int $thaipromptUserId, int $holderJuntraUserId): void
    {
        Log::warning('Juntra affiliate: ผู้ใช้ Thaiprompt นี้ผูกกับบัญชีจันทราอื่นแล้ว — ตอบ 409 ไม่สร้างเงา', [
            'juntra_user_id' => $juntraUserId,
            'thaiprompt_user_id' => $thaipromptUserId,
            'held_by_juntra_user_id' => $holderJuntraUserId,
        ]);

        try {
            $key = "juntra:affiliate-taken-alert:{$juntraUserId}:{$thaipromptUserId}";
            if (! Cache::add($key, 1, now()->addHours(self::TAKEN_ALERT_COOLDOWN_HOURS))) {
                return;
            }

            app(LineAlertService::class)->alertUnusualActivity(
                'จันทรา: บัญชี Thaiprompt ถูกบัญชีจันทราอื่นผูกไว้แล้ว (บิลรอแอดมิน)',
                [
                    'juntra_user_ref' => $juntraUserId,
                    'thaiprompt_user_id' => $thaipromptUserId,
                    'held_by_juntra_user_ref' => $holderJuntraUserId,
                    'admin_action' => 'ตรวจว่าเป็นคนเดียวกันไหม — ถ้าใช่ (บัญชีจันทราเก่าเลิกใช้แล้ว) รัน '
                        ."php artisan juntra:affiliate-release {$holderJuntraUserId} --reason=\"...\" แล้วบิลจะผ่านเองรอบถัดไป",
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Juntra affiliate: แจ้งแอดมินเรื่อง thaiprompt_taken ไม่สำเร็จ', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 🌐 ธง facebook_web_only เปิดอยู่ไหม (นโยบายกลาง FortuneBotMode::fbWebOnly)
     */
    private function fbWebOnlyOn(): bool
    {
        return (new \App\Services\Fortune\FortuneBotMode)->fbWebOnly();
    }

    /** fortune_pages.id จาก code ที่จันทราส่งมา — ไม่รู้จัก/ว่าง = null (ไม่ทำให้บิลล้ม) */
    private function pageIdFromCode(?string $pageCode): ?int
    {
        $code = Str::limit(trim((string) $pageCode), 40, '');
        if ($code === '') {
            return null;
        }

        $id = FortunePage::where('code', $code)->value('id');
        if ($id === null) {
            Log::info('Juntra affiliate: page_code ที่จันทราส่งมาไม่ตรงสาขาใด — ข้ามป้ายสาขา', ['page_code' => $code]);
        }

        return $id !== null ? (int) $id : null;
    }

    /** สาขาต้นทางของผู้ใช้ (users.fortune_origin_page_id) — คอลัมน์ยังไม่มี/ว่าง/สาขาถูกลบ = null */
    private function originPageIdOf(User $user): ?int
    {
        if (! self::originPageColumnReady()) {
            return null;
        }

        $pageId = User::whereKey($user->id)->value('fortune_origin_page_id');
        if ($pageId === null || ! FortunePage::whereKey((int) $pageId)->exists()) {
            return null;
        }

        return (int) $pageId;
    }

    /** จดสาขาต้นทางครั้งเดียว (เฉพาะตอนยังว่าง — ไม่ย้ายสาขาภายหลัง) */
    private function rememberOriginPage(User $user, ?int $pageId): void
    {
        if ($pageId === null || ! self::originPageColumnReady()) {
            return;
        }

        User::whereKey($user->id)
            ->whereNull('fortune_origin_page_id')
            ->update(['fortune_origin_page_id' => $pageId]);
    }

    private static function releaseColumnsReady(): bool
    {
        if (self::$releaseColumnsReady === null) {
            try {
                self::$releaseColumnsReady = Schema::hasColumn('juntra_accounts', 'released_at')
                    && Schema::hasColumn('juntra_accounts', 'released_juntra_user_id');
            } catch (\Throwable $e) {
                return false;
            }
        }

        return self::$releaseColumnsReady;
    }

    private static function originPageColumnReady(): bool
    {
        if (self::$originPageColumnReady === null) {
            try {
                self::$originPageColumnReady = Schema::hasColumn('users', 'fortune_origin_page_id');
            } catch (\Throwable $e) {
                return false;
            }
        }

        return self::$originPageColumnReady;
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
