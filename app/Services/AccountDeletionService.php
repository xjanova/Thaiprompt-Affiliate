<?php

namespace App\Services;

use App\Exceptions\AccountDeletionBlockedException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 🗑️ ลบบัญชีผู้ใช้ (PDPA + Google Play "Account deletion") — ทางเดียวที่ใช้ลบบัญชีในระบบ
 *
 * ใช้ร่วมกันทั้ง 3 ทาง: แอป (DELETE /api/v1/account), เว็บ (/account/delete) และแอดมิน (admin.users.destroy)
 *
 * ── หลักการ ────────────────────────────────────────────────────────────────
 *  1. ไม่ลบแถว users ถาวร — FK ของ orders/order_items/wallets/wallet_transactions/earnings_ledger/
 *     riders/rider_jobs ตั้ง ON DELETE CASCADE ไว้ ลบถาวร 1 คน = ประวัติการเงินของคู่ค้าหายตาม
 *     → "ปกปิด PII" ทุกคอลัมน์ที่ระบุตัวตนได้ แล้วค่อย soft delete (users.deleted_at)
 *  2. เก็บธุรกรรมการเงินไว้ตามกฎหมาย (ไม่ผูกกับตัวตนแล้วเพราะ PII ถูกล้าง)
 *  3. บล็อกการลบ (พร้อมเหตุผลภาษาไทย) ถ้ายังมีเงินในกระเป๋า / ถอนเงินค้าง / รายได้รอโอน / หนี้ค้าง /
 *     ออเดอร์หรืองานไรเดอร์ที่ยังไม่จบ — กันเงินลูกค้า/ร้าน/ไรเดอร์ค้างในบัญชีที่ไม่มีเจ้าของ
 *  4. อีเมลถูกเปลี่ยนเป็น deleted+{id}@invalid → สมัครใหม่ด้วยอีเมลเดิมได้ (unique ไม่ค้าง)
 *     LINE/Facebook id ถูกล้าง → ล็อกอินด้วย LINE/FB เดิมจะได้บัญชีใหม่สะอาด
 *  5. เพิกถอนทุกช่องทางเข้าระบบ: Sanctum token, Passport token, session, push token
 */
class AccountDeletionService
{
    /** ข้อความที่ผู้ใช้ต้องพิมพ์ยืนยัน */
    public const CONFIRM_TEXT = 'ลบบัญชี';

    /** ชื่อที่แสดงแทนผู้ใช้ที่ลบบัญชีแล้ว */
    public const ANONYMIZED_NAME = 'ผู้ใช้ที่ลบบัญชี';

    /** สถานะออเดอร์ร้านค้า (orders.status) ที่ยังไม่จบ ฝั่งผู้ซื้อ */
    private const BUYER_ACTIVE_ORDER_STATUSES = ['paid', 'processing', 'shipped'];

    /** สถานะออเดอร์ร้านค้าที่ยังไม่จบ ฝั่งผู้ขาย (ส่งแล้วแต่ยังไม่ปิดงาน = ยังอาจมีคืนเงิน/ข้อพิพาท) */
    private const SELLER_ACTIVE_ORDER_STATUSES = ['pending', 'paid', 'processing', 'shipped', 'delivered'];

    /** สถานะงานไรเดอร์ที่ยังไม่จบ */
    private const ACTIVE_RIDER_JOB_STATUSES = ['pending', 'accepted', 'picking_up', 'picked_up', 'delivering', 'delivered'];

    /** สถานะออเดอร์ตลาดสดที่ยังไม่จบ */
    private const ACTIVE_FRESH_MARKET_STATUSES = ['pending', 'accepted', 'preparing', 'ready', 'picked_up', 'delivering', 'delivered'];

    /** @var array<string, array<int, string>> cache รายชื่อคอลัมน์ต่อตาราง */
    private array $columnCache = [];

    /**
     * เหตุผลที่ยังลบบัญชีไม่ได้ (ว่าง = ลบได้)
     *
     * @return array<int, array{code: string, message: string}>
     */
    public function blockers(User $user): array
    {
        $blockers = [];
        $userId = (int) $user->id;

        // 0) บัญชีผู้ดูแลระบบ — ต้องลดสิทธิ์ก่อน (กันแอดมินเผลอลบตัวเอง/ลบกันเองจนระบบไม่มีคนดูแล)
        if ($user->is_super_admin || in_array($user->role, ['admin', 'super_admin'], true)) {
            $blockers[] = [
                'code' => 'ADMIN_ACCOUNT',
                'message' => 'บัญชีผู้ดูแลระบบลบไม่ได้ กรุณาลดสิทธิ์เป็นผู้ใช้ทั่วไปก่อน',
            ];
        }

        // 1) ยอดเงินในกระเป๋า (ต้องเป็น 0 พอดี — ติดลบก็ห้ามลบ เพราะคือหนี้)
        if ($this->hasTable('wallets')) {
            $balance = (float) DB::table('wallets')
                ->where('user_id', $userId)
                ->when($this->hasColumn('wallets', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->sum('balance');
            $balance = round($balance, 2);

            if ($balance > 0) {
                $blockers[] = [
                    'code' => 'WALLET_NOT_EMPTY',
                    'message' => 'ยังมียอดเงินในกระเป๋า '.number_format($balance, 2).' บาท กรุณาถอนเงินหรือใช้ให้หมดก่อนลบบัญชี',
                ];
            } elseif ($balance < 0) {
                $blockers[] = [
                    'code' => 'WALLET_NEGATIVE',
                    'message' => 'กระเป๋าเงินมียอดติดลบ '.number_format(abs($balance), 2).' บาท กรุณาชำระให้เรียบร้อยก่อนลบบัญชี',
                ];
            }
        }

        // 2) คำขอถอนเงินที่ยังไม่เสร็จ
        if ($this->hasTable('withdrawal_requests')) {
            $pending = DB::table('withdrawal_requests')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'processing', 'approved'])
                ->when($this->hasColumn('withdrawal_requests', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->exists();
            if ($pending) {
                $blockers[] = [
                    'code' => 'PENDING_WITHDRAWAL',
                    'message' => 'มีคำขอถอนเงินที่ยังดำเนินการไม่เสร็จ กรุณารอให้เสร็จก่อนลบบัญชี',
                ];
            }
        }

        // 3) รายได้ที่ยังรอโอนเข้ากระเป๋า (ผู้ขาย/ผู้ให้บริการ/คอมมิชชั่น)
        if ($this->hasTable('earnings_ledger')) {
            $pendingEarnings = DB::table('earnings_ledger')
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'available', 'processing', 'held'])
                ->when($this->hasColumn('earnings_ledger', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->exists();
            if ($pendingEarnings) {
                $blockers[] = [
                    'code' => 'PENDING_EARNINGS',
                    'message' => 'ยังมีรายได้ที่รอโอนเข้ากระเป๋า กรุณารอให้โอนเสร็จและถอนออกก่อนลบบัญชี',
                ];
            }
        }

        // 4) หนี้ค้างชำระกับแพลตฟอร์ม
        if ($this->hasTable('wallet_debts')) {
            $debt = DB::table('wallet_debts')
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->where('remaining_amount', '>', 0)
                ->when($this->hasColumn('wallet_debts', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->exists();
            if ($debt) {
                $blockers[] = [
                    'code' => 'OUTSTANDING_DEBT',
                    'message' => 'มีหนี้ค้างชำระกับระบบ กรุณาชำระให้เรียบร้อยก่อนลบบัญชี',
                ];
            }
        }

        // 5) ออเดอร์ร้านค้าที่ยังไม่จบ (ฝั่งผู้ซื้อ + ฝั่งผู้ขาย)
        if ($this->hasTable('orders')) {
            $buyerActive = DB::table('orders')
                ->where('user_id', $userId)
                ->where(function ($q) {
                    $q->whereIn('status', self::BUYER_ACTIVE_ORDER_STATUSES)
                        ->orWhere(function ($q2) {
                            $q2->where('status', 'pending')->where('payment_status', 'paid');
                        });
                })
                ->exists();

            $storeIds = $this->hasTable('vendor_stores')
                ? DB::table('vendor_stores')->where('user_id', $userId)->pluck('id')->all()
                : [];

            $sellerActive = DB::table('orders')
                ->where('payment_status', 'paid')
                ->whereIn('status', self::SELLER_ACTIVE_ORDER_STATUSES)
                ->where(function ($q) use ($storeIds, $userId) {
                    $q->whereRaw('1 = 0');
                    if (! empty($storeIds)) {
                        $q->orWhereIn('store_id', $storeIds);
                    }
                    if ($this->hasTable('order_items')) {
                        $q->orWhereExists(function ($sub) use ($userId) {
                            $sub->select(DB::raw(1))
                                ->from('order_items')
                                ->whereColumn('order_items.order_id', 'orders.id')
                                ->where('order_items.seller_id', $userId);
                        });
                    }
                })
                ->exists();

            if ($buyerActive || $sellerActive) {
                $blockers[] = [
                    'code' => 'ACTIVE_ORDERS',
                    'message' => 'ยังมีคำสั่งซื้อที่ยังไม่เสร็จสิ้น กรุณารอให้จัดส่ง/ปิดคำสั่งซื้อให้เรียบร้อยก่อนลบบัญชี',
                ];
            }
        }

        // 6) งานไรเดอร์ที่ยังไม่จบ (ทั้งในฐานะไรเดอร์ และในฐานะลูกค้าที่เรียกไรเดอร์)
        if ($this->hasTable('rider_jobs')) {
            $riderIds = $this->hasTable('riders')
                ? DB::table('riders')->where('user_id', $userId)->pluck('id')->all()
                : [];

            $asRider = ! empty($riderIds) && DB::table('rider_jobs')
                ->whereIn('rider_id', $riderIds)
                ->whereIn('status', array_diff(self::ACTIVE_RIDER_JOB_STATUSES, ['pending']))
                ->exists();

            $asCustomer = $this->hasColumn('rider_jobs', 'customer_id') && DB::table('rider_jobs')
                ->where('customer_id', $userId)
                ->whereIn('status', self::ACTIVE_RIDER_JOB_STATUSES)
                ->exists();

            if ($asRider || $asCustomer) {
                $blockers[] = [
                    'code' => 'ACTIVE_RIDER_JOBS',
                    'message' => 'ยังมีงานรับ-ส่งของที่ยังไม่เสร็จ กรุณาส่งงานให้เรียบร้อยก่อนลบบัญชี',
                ];
            }

            // 6b) เงินเก็บปลายทาง (COD) ที่ไรเดอร์ส่งของแล้วแต่ยังนำส่งเข้าระบบไม่ได้
            //     ห้ามลบ — การลบจะล้างชื่อ/บัตรประชาชน/เอกสาร ทำให้ตามเงินที่ไรเดอร์ถือไว้ไม่ได้
            if (! empty($riderIds) && $this->hasColumn('rider_jobs', 'cod_settled_at')) {
                $unsettledCod = (float) DB::table('rider_jobs')
                    ->whereIn('rider_id', $riderIds)
                    ->where('status', 'completed')
                    ->where('cod_amount', '>', 0)
                    ->whereNull('cod_settled_at')
                    ->when($this->hasColumn('rider_jobs', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                    ->sum(DB::raw('GREATEST(cod_amount - rider_earnings, 0)'));

                if ($unsettledCod > 0) {
                    $blockers[] = [
                        'code' => 'UNSETTLED_COD',
                        'message' => 'ยังมีเงินเก็บปลายทาง '.number_format($unsettledCod, 2).' บาท ที่ยังไม่ได้นำส่งเข้าระบบ กรุณาเติมเงินเข้ากระเป๋าให้ระบบหักก่อนลบบัญชี',
                    ];
                }
            }
        }

        // 7) ออเดอร์ตลาดสดที่ยังไม่จบ (ผู้ซื้อ + ร้านค้า)
        if ($this->hasTable('fresh_market_orders')) {
            $sellerIds = $this->hasTable('fresh_market_sellers')
                ? DB::table('fresh_market_sellers')->where('user_id', $userId)->pluck('id')->all()
                : [];

            $freshActive = DB::table('fresh_market_orders')
                ->whereIn('order_status', self::ACTIVE_FRESH_MARKET_STATUSES)
                ->when($this->hasColumn('fresh_market_orders', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
                ->where(function ($q) use ($userId, $sellerIds) {
                    $q->where('buyer_id', $userId);
                    if (! empty($sellerIds)) {
                        $q->orWhereIn('seller_id', $sellerIds);
                    }
                })
                ->exists();

            if ($freshActive) {
                $blockers[] = [
                    'code' => 'ACTIVE_FRESH_MARKET_ORDERS',
                    'message' => 'ยังมีคำสั่งซื้อตลาดสดที่ยังไม่เสร็จสิ้น กรุณารอให้ปิดคำสั่งซื้อก่อนลบบัญชี',
                ];
            }
        }

        return $blockers;
    }

    /**
     * บัญชีนี้ต้องยืนยันด้วยรหัสผ่านก่อนลบหรือไม่
     *
     * บัญชีที่สมัครผ่าน LINE/Facebook/บอท (อีเมลสังเคราะห์ @thaiprompt.local) ไม่เคยรู้รหัสผ่านของตัวเอง
     * → ใช้การพิมพ์ "ลบบัญชี" ยืนยันแทน ; บัญชีอีเมล+รหัสผ่านล้วน ต้องใส่รหัสผ่านเสมอ
     */
    public function requiresPassword(User $user): bool
    {
        if (! empty($user->line_user_id) || ! empty($user->facebook_user_id) || ! empty($user->facebook_psid)) {
            return false;
        }

        return ! str_ends_with(mb_strtolower((string) $user->email), '@thaiprompt.local');
    }

    /**
     * ตรวจการยืนยันของผู้ใช้ก่อนลบ (ใช้ร่วมกันทั้งแอปและเว็บ)
     *
     * - ต้องพิมพ์ "ลบบัญชี" ตรงตัว
     * - บัญชีอีเมล+รหัสผ่าน ต้องใส่รหัสผ่านถูก ; บัญชี LINE/FB ไม่บังคับ แต่ถ้าใส่มาต้องถูก
     *
     * @return array{code: string, message: string, field: string}|null null = ผ่าน
     */
    public function confirmationError(User $user, mixed $confirmText, mixed $password): ?array
    {
        if (! is_string($confirmText) || trim($confirmText) !== self::CONFIRM_TEXT) {
            return [
                'code' => 'CONFIRMATION_REQUIRED',
                'message' => 'กรุณาพิมพ์คำว่า "'.self::CONFIRM_TEXT.'" เพื่อยืนยัน',
                'field' => 'confirm_text',
            ];
        }

        $password = is_string($password) ? $password : '';

        if ($password === '' && $this->requiresPassword($user)) {
            return [
                'code' => 'PASSWORD_REQUIRED',
                'message' => 'กรุณากรอกรหัสผ่านเพื่อยืนยันการลบบัญชี',
                'field' => 'password',
            ];
        }

        if ($password !== '' && ! Hash::check($password, (string) $user->getAuthPassword())) {
            return [
                'code' => 'PASSWORD_INCORRECT',
                'message' => 'รหัสผ่านไม่ถูกต้อง',
                'field' => 'password',
            ];
        }

        return null;
    }

    /**
     * ลบบัญชี: ตรวจเงื่อนไข → ปกปิด PII → ปิดร้าน/โปรไฟล์ไรเดอร์ → เพิกถอนการเข้าถึง → soft delete
     *
     * @param  string  $initiatedBy  'self' | 'admin'
     * @return string เลขอ้างอิงการลบ (pdpa_deletion_ref) ไว้แจ้งผู้ใช้/ตรวจสอบย้อนหลัง
     *
     * @throws AccountDeletionBlockedException เมื่อยังมีเงื่อนไขค้าง (ข้อความไทยพร้อมแสดง)
     */
    public function delete(User $user, string $initiatedBy = 'self', ?User $actor = null): string
    {
        $filesToDelete = [];

        $ref = DB::transaction(function () use ($user, $initiatedBy, $actor, &$filesToDelete) {
            /** @var User|null $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (! $locked) {
                throw new AccountDeletionBlockedException([[
                    'code' => 'ACCOUNT_NOT_FOUND',
                    'message' => 'ไม่พบบัญชีนี้ หรือบัญชีถูกลบไปแล้ว',
                ]]);
            }

            // ล็อกกระเป๋าเงินไว้ระหว่างตรวจ — กันเงินเข้าระหว่างกำลังลบ (ยอดจะค้างในบัญชีที่ไม่มีเจ้าของ)
            if ($this->hasTable('wallets')) {
                DB::table('wallets')->where('user_id', $locked->id)->lockForUpdate()->get(['id']);
            }

            $blockers = $this->blockers($locked);
            if (! empty($blockers)) {
                throw new AccountDeletionBlockedException($blockers);
            }

            $ref = 'DEL-'.now()->format('ymd').'-'.Str::upper(Str::random(6));

            $filesToDelete = array_merge(
                $this->collectUserFiles($locked),
                $this->collectRiderFiles((int) $locked->id)
            );

            $this->anonymizeUserRow($locked, $ref);
            $this->scrubRelatedPersonalData((int) $locked->id);
            $this->closeBusinessProfiles((int) $locked->id);
            $this->revokeAccess((int) $locked->id);

            // soft delete — ทริกเกอร์ User::deleting (ลบเอกสาร KYC + รูป) ด้วย
            $locked->refresh();
            $locked->delete();

            Log::info('🗑️ Account deleted', [
                'user_id' => $locked->id,
                'ref' => $ref,
                'initiated_by' => $initiatedBy,
                'actor_id' => $actor?->id,
            ]);

            return $ref;
        });

        // ไฟล์ลบย้อนกลับไม่ได้ → ทำหลัง commit เท่านั้น (best-effort)
        $this->deleteFiles($filesToDelete);

        return $ref;
    }

    /**
     * ปกปิดข้อมูลส่วนบุคคลในแถว users (เฉพาะคอลัมน์ที่มีจริง)
     */
    private function anonymizeUserRow(User $user, string $ref): void
    {
        $scrub = [
            'name' => self::ANONYMIZED_NAME,
            'email' => 'deleted+'.$user->id.'@invalid',
            'password' => Hash::make(Str::random(48)),
            'remember_token' => null,
            'email_verified_at' => null,
            'phone' => null, 'phone_verified' => false, 'phone_verified_at' => null,
            'line_user_id' => null, 'line_display_name' => null, 'line_picture_url' => null,
            'line_access_token' => null, 'line_verified' => false, 'line_linked_at' => null,
            'facebook_user_id' => null, 'facebook_psid' => null, 'facebook_email' => null,
            'facebook_name' => null, 'facebook_picture_url' => null, 'facebook_verified' => false,
            'facebook_linked_at' => null,
            'profile_picture' => null, 'avatar_url' => null, 'bio' => null,
            'bank_name' => null, 'bank_account' => null, 'bank_account_name' => null,
            'address' => null, 'city' => null, 'state' => null, 'postal_code' => null,
            'date_of_birth' => null, 'gender' => null,
            'id_card_number' => null, 'thai_first_name' => null, 'thai_last_name' => null,
            'english_first_name' => null, 'english_last_name' => null,
            'id_card_birth_date' => null, 'id_card_religion' => null, 'id_card_address' => null,
            'id_card_issue_date' => null, 'id_card_expiry_date' => null,
            'permissions' => null,
            'pdpa_deleted_at' => now(),
            'pdpa_deletion_ref' => $ref,
            'updated_at' => now(),
        ];

        $columns = $this->columns('users');
        $update = array_intersect_key($scrub, array_flip($columns));

        DB::table('users')->where('id', $user->id)->update($update);
    }

    /**
     * ล้างข้อมูลส่วนบุคคลในตารางลูก (ที่อยู่จัดส่ง, บัญชีรับเงิน, กล่องแจ้งเตือน, สมาชิก MLM)
     */
    private function scrubRelatedPersonalData(int $userId): void
    {
        // ที่อยู่จัดส่ง — ออเดอร์เก็บ snapshot ของตัวเองแล้ว (orders.shipping_address_snapshot)
        if ($this->hasTable('shipping_addresses')) {
            $this->updateExisting('shipping_addresses', ['user_id' => $userId], [
                'recipient_name' => self::ANONYMIZED_NAME,
                'phone_number' => '-',
                'address_line_1' => '-',
                'address_line_2' => null,
                'sub_district' => null,
                'district' => null,
                'province' => '-',
                'postal_code' => '-',
                'notes' => null,
                'latitude' => null,
                'longitude' => null,
                'is_default' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // บัญชีรับเงิน/ถอนเงิน — คำขอถอนเงินเก่ามี payment_details snapshot ของตัวเองแล้ว
        if ($this->hasTable('payment_methods')) {
            $this->updateExisting('payment_methods', ['user_id' => $userId], [
                'account_name' => null,
                'account_number' => null,
                'branch' => null,
                'paypal_email' => null,
                'stripe_customer_id' => null,
                'stripe_payment_method_id' => null,
                'qr_code' => null,
                'metadata' => null,
                'notes' => null,
                'is_active' => false,
                'is_default' => false,
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // กล่องแจ้งเตือน (เว็บ + แอป) — ไม่ใช่เอกสารการเงิน ลบทิ้งได้
        foreach (['notifications', 'user_notifications'] as $table) {
            if ($this->hasTable($table)) {
                DB::table($table)->where('user_id', $userId)->delete();
            }
        }

        // สมาชิก MLM — คงโครงสร้างสายงานไว้ (ลบ node = ผังของสมาชิกคนอื่นพัง) แค่ยุติสิทธิ์
        if ($this->hasTable('mlm_members')) {
            $this->updateExisting('mlm_members', ['user_id' => $userId], [
                'status' => 'inactive',
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * ปิดร้านค้า/ร้านตลาดสด/โปรไฟล์ไรเดอร์ของบัญชีนี้ และล้างเบอร์/ตัวตนที่ผูกอยู่
     */
    private function closeBusinessProfiles(int $userId): void
    {
        if ($this->hasTable('vendor_stores')) {
            $this->updateExisting('vendor_stores', ['user_id' => $userId], [
                'status' => 'closed',
                'is_active' => false,
                'is_featured_home' => false,
                'store_email' => null,
                'store_phone' => null,
                'updated_at' => now(),
            ]);
        }

        if ($this->hasTable('products') && $this->hasColumn('products', 'seller_id')) {
            $this->updateExisting('products', ['seller_id' => $userId], [
                'is_active' => false,
                'updated_at' => now(),
            ]);
        }

        if ($this->hasTable('fresh_market_sellers')) {
            $this->updateExisting('fresh_market_sellers', ['user_id' => $userId], [
                'is_active' => false,
                'phone' => null,
                'line_user_id' => null,
                'line_display_name' => null,
                'updated_at' => now(),
            ]);
        }

        if ($this->hasTable('riders')) {
            // full_name / phone เป็น NOT NULL → ใส่ค่าปกปิดแทน null
            $this->updateExisting('riders', ['user_id' => $userId], [
                'full_name' => self::ANONYMIZED_NAME,
                'phone' => '-',
                'line_user_id' => null,
                'id_card_number' => null,
                'birth_date' => null,
                'address' => null,
                'vehicle_plate' => null,
                'id_card_image' => null,
                'driver_license_image' => null,
                'vehicle_registration_image' => null,
                'profile_image' => null,
                'last_latitude' => null,
                'last_longitude' => null,
                'status' => 'inactive',
                'availability' => 'offline',
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * เพิกถอนทุกช่องทางเข้าระบบ + ถอด push token (เครื่องเดิมจะไม่ได้แจ้งเตือนของบัญชีนี้อีก)
     */
    private function revokeAccess(int $userId): void
    {
        if ($this->hasTable('personal_access_tokens')) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $userId)
                ->delete();
        }

        // Passport (SSO เว็บจันทรา) — revoke แทนการลบ ให้ refresh token ใช้ต่อไม่ได้ด้วย
        if ($this->hasTable('oauth_access_tokens')) {
            $accessTokenIds = DB::table('oauth_access_tokens')->where('user_id', $userId)->pluck('id')->all();
            DB::table('oauth_access_tokens')->where('user_id', $userId)->update(['revoked' => true]);

            if (! empty($accessTokenIds) && $this->hasTable('oauth_refresh_tokens')) {
                DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $accessTokenIds)->update(['revoked' => true]);
            }
        }

        // session แบบ database driver (ถ้าใช้ driver อื่น session จะหา user ไม่เจอเองเพราะถูก soft delete)
        if ($this->hasTable('sessions') && $this->hasColumn('sessions', 'user_id')) {
            DB::table('sessions')->where('user_id', $userId)->delete();
        }

        if ($this->hasTable('user_notification_tokens')) {
            DB::table('user_notification_tokens')->where('user_id', $userId)->delete();
        }

        if ($this->hasTable('mobile_devices')) {
            DB::table('mobile_devices')->where('user_id', $userId)->update(['user_id' => null, 'updated_at' => now()]);
        }
    }

    /**
     * ไฟล์รูปโปรไฟล์ที่อัปโหลดไว้ในเครื่องเรา (URL ภายนอก เช่นรูป LINE ข้ามไป)
     *
     * @return array<int, string>
     */
    private function collectUserFiles(User $user): array
    {
        $path = (string) ($user->getRawOriginal('profile_picture') ?? '');

        return ($path !== '' && ! preg_match('#^https?://#i', $path)) ? [$path] : [];
    }

    /**
     * ไฟล์เอกสารไรเดอร์ (บัตรประชาชน/ใบขับขี่/ทะเบียนรถ/รูปโปรไฟล์)
     *
     * @return array<int, string>
     */
    private function collectRiderFiles(int $userId): array
    {
        if (! $this->hasTable('riders')) {
            return [];
        }

        $cols = array_values(array_intersect(
            ['id_card_image', 'driver_license_image', 'vehicle_registration_image', 'profile_image'],
            $this->columns('riders')
        ));
        if (empty($cols)) {
            return [];
        }

        $files = [];
        foreach (DB::table('riders')->where('user_id', $userId)->get($cols) as $row) {
            foreach ($cols as $col) {
                $value = (string) ($row->{$col} ?? '');
                if ($value !== '' && ! preg_match('#^https?://#i', $value)) {
                    $files[] = $value;
                }
            }
        }

        return $files;
    }

    /**
     * ลบไฟล์แบบ best-effort ทั้ง disk private (local) และ public
     *
     * @param  array<int, string>  $paths
     */
    private function deleteFiles(array $paths): void
    {
        foreach (array_unique($paths) as $raw) {
            $path = preg_replace('#^(public|storage)/#', '', ltrim(trim($raw), '/'));
            if ($path === '' || str_contains($path, '..')) {
                continue;
            }

            foreach (['local', 'public'] as $disk) {
                try {
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Account deletion: file delete failed (non-blocking)', [
                        'disk' => $disk,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * update เฉพาะคอลัมน์ที่มีจริงในตาราง (schema ต่างกันระหว่าง prod/CI ได้)
     *
     * @param  array<string, mixed>  $where
     * @param  array<string, mixed>  $values
     */
    private function updateExisting(string $table, array $where, array $values): void
    {
        $update = array_intersect_key($values, array_flip($this->columns($table)));
        if (empty($update)) {
            return;
        }

        DB::table($table)->where($where)->update($update);
    }

    private function hasTable(string $table): bool
    {
        return ! empty($this->columns($table));
    }

    private function hasColumn(string $table, string $column): bool
    {
        return in_array($column, $this->columns($table), true);
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $table): array
    {
        if (! array_key_exists($table, $this->columnCache)) {
            $this->columnCache[$table] = Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }

        return $this->columnCache[$table];
    }
}
