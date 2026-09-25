<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletSetting;
use App\Models\WithdrawalRequest;
use App\Services\NotificationService;
use App\Services\WalletService;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ถอนเงิน + บัญชีรับเงิน + PIN กระเป๋าเงิน สำหรับแอป (audit CC-06)
 *
 * ใช้ WithdrawalService ตัวเดียวกับเว็บ (KYC, ขั้นต่ำ/สูงสุด, ค่าธรรมเนียม, หักเงินพร้อม lock, แอดมินอนุมัติ)
 * ความปลอดภัยฝั่งแอป:
 *  - ถอนเงิน / เพิ่ม-ลบบัญชีรับเงิน ต้องยืนยันด้วย PIN กระเป๋าเงินทุกครั้ง (ยังไม่ตั้ง PIN = ต้องตั้งก่อน)
 *  - PIN ผิด 5 ครั้ง กระเป๋าถูกล็อก 30 นาที (Wallet::incrementFailedAttempts)
 *  - กันกดซ้ำด้วย lock ต่อผู้ใช้ + throttle ที่ route
 */
class WalletWithdrawalApiController extends Controller
{
    private const MAX_PAYMENT_METHODS = 5;

    public function __construct(
        protected WalletService $wallets,
        protected WithdrawalService $withdrawals
    ) {}

    /**
     * GET /api/v1/wallet/withdraw/info — ข้อมูลก่อนถอน (ยอด, เงื่อนไข, บัญชีรับเงิน, สถานะ PIN/KYC)
     */
    public function info(Request $request): JsonResponse
    {
        $user = $request->user();
        $wallet = $this->wallets->getOrCreateWallet($user);

        return $this->ok('ดึงข้อมูลการถอนเงินสำเร็จ', [
            'balance' => round((float) $wallet->balance, 2),
            'currency' => $wallet->currency ?? 'THB',
            'has_pin' => $wallet->hasPIN(),
            'wallet_locked' => ! $wallet->isActive(),
            'kyc_verified' => $user->isKycVerified(),
            'limits' => [
                'min_amount' => (float) WalletSetting::get('withdrawal_min_amount', 0),
                'max_amount' => (float) WalletSetting::get('withdrawal_max_amount', 999999999),
            ],
            'fee' => [
                'type' => (string) WalletSetting::get('withdrawal_fee_type', 'percentage'),
                'amount' => (float) WalletSetting::get('withdrawal_fee_amount', 0),
                'min' => (float) WalletSetting::get('withdrawal_fee_min', 0),
                'max' => (float) WalletSetting::get('withdrawal_fee_max', 999999),
            ],
            'pending_withdrawal_amount' => round((float) WithdrawalRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'processing', 'approved'])
                ->sum('amount'), 2),
            'payment_methods' => $this->paymentMethodsFor($user),
        ]);
    }

    /**
     * GET /api/v1/wallet/withdraw/preview?amount= — คำนวณค่าธรรมเนียม/ภาษีก่อนยืนยัน
     */
    public function preview(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:99999999',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $amount = round((float) $request->input('amount'), 2);
        $fee = (float) WalletSetting::calculateWithdrawalFee($amount);
        $tax = (float) WalletSetting::calculateWithdrawalTax($amount);

        return $this->ok('คำนวณยอดถอนสำเร็จ', [
            'amount' => $amount,
            'fee' => round($fee, 2),
            'tax' => round($tax, 2),
            'net_amount' => round($amount - $fee - $tax, 2),
            'errors' => WalletSetting::validateWithdrawalAmount($amount),
        ]);
    }

    /**
     * POST /api/v1/wallet/withdraw — ส่งคำขอถอนเงิน
     *
     * body: amount (บาท), pin (6 หลัก), payment_method_id (แนะนำ) หรือ payment_method (bank|bank_transfer|promptpay), note?
     */
    public function withdraw(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:99999999',
            'pin' => 'required|string|max:20',
            'payment_method_id' => 'nullable|integer',
            'payment_method' => 'nullable|string|in:bank,bank_transfer,promptpay',
            'note' => 'nullable|string|max:500',
        ], [
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลข',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
            'pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน',
            'payment_method.in' => 'ช่องทางรับเงินไม่ถูกต้อง',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        /** @var User $user */
        $user = $request->user();
        $amount = round((float) $request->input('amount'), 2);

        if (! $user->isKycVerified() && ! $user->is_super_admin && ! in_array($user->role, ['admin', 'super_admin'], true)) {
            return $this->error('กรุณายืนยันตัวตน (KYC) ก่อนถอนเงิน', 'KYC_REQUIRED', 403);
        }

        $wallet = $this->wallets->getOrCreateWallet($user);

        if ($blocked = $this->walletGuard($wallet)) {
            return $blocked;
        }

        $method = $this->resolvePaymentMethod($user, $request->input('payment_method_id'), $request->input('payment_method'));
        if (! $method) {
            return $this->error('กรุณาเพิ่มบัญชีรับเงินก่อนถอนเงิน', 'PAYMENT_METHOD_REQUIRED', 422);
        }

        $rangeErrors = WalletSetting::validateWithdrawalAmount($amount);
        if (! empty($rangeErrors)) {
            return $this->error(implode(', ', $rangeErrors), 'AMOUNT_OUT_OF_RANGE', 422);
        }

        if ((float) $wallet->balance < $amount) {
            return $this->error('ยอดเงินในกระเป๋าไม่เพียงพอ', 'INSUFFICIENT_BALANCE', 422, [
                'balance' => round((float) $wallet->balance, 2),
                'requested' => $amount,
            ]);
        }

        if ($pinError = $this->checkPin($wallet, (string) $request->input('pin'))) {
            return $pinError;
        }

        $lock = Cache::lock('api-wallet-withdraw:'.$user->id, 15);
        if (! $lock->get()) {
            return $this->error('กำลังส่งคำขอถอนเงินก่อนหน้า กรุณารอสักครู่', 'REQUEST_IN_PROGRESS', 409);
        }

        try {
            $withdrawal = $this->withdrawals->createWithdrawalRequest(
                $user,
                $amount,
                (int) $method->id,
                $request->input('note'),
                (string) $request->input('pin')
            );

            $withdrawal->update([
                'metadata' => array_merge($withdrawal->metadata ?? [], ['source' => 'mobile_app']),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            return $this->ok('ส่งคำขอถอนเงินสำเร็จ รอการอนุมัติภายใน 1-3 วันทำการ', $this->serializeWithdrawal($withdrawal->fresh(['paymentMethod'])), 201);
        } catch (\Throwable $e) {
            return $this->mapWithdrawError($e);
        } finally {
            $lock->release();
        }
    }

    /**
     * GET /api/v1/wallet/withdrawals — ประวัติคำขอถอนเงิน (แบ่งหน้า 20)
     */
    public function history(Request $request): JsonResponse
    {
        $page = WithdrawalRequest::where('user_id', $request->user()->id)
            ->with('paymentMethod')
            ->latest('id')
            ->paginate(20);

        return $this->ok('ดึงประวัติการถอนเงินสำเร็จ', [
            'items' => collect($page->items())->map(fn (WithdrawalRequest $w) => $this->serializeWithdrawal($w))->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/wallet/withdrawals/{id}/cancel — ยกเลิกคำขอที่ยังรอตรวจ (เงินคืนเข้ากระเป๋า)
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $withdrawal = WithdrawalRequest::where('user_id', $request->user()->id)->find($id);
        if (! $withdrawal) {
            return $this->error('ไม่พบคำขอถอนเงินนี้', 'NOT_FOUND', 404);
        }

        if (! $withdrawal->isPending()) {
            return $this->error('ยกเลิกได้เฉพาะคำขอที่ยังรอตรวจสอบ', 'NOT_CANCELLABLE', 409);
        }

        try {
            $this->withdrawals->cancelWithdrawal($withdrawal, $request->user());
        } catch (\Throwable $e) {
            Log::warning('API cancel withdrawal failed', ['withdrawal_id' => $id, 'error' => $e->getMessage()]);

            return $this->error('ยกเลิกคำขอถอนเงินไม่ได้ คำขออาจถูกดำเนินการไปแล้ว', 'NOT_CANCELLABLE', 409);
        }

        return $this->ok('ยกเลิกคำขอถอนเงินแล้ว เงินคืนเข้ากระเป๋าเรียบร้อย', $this->serializeWithdrawal($withdrawal->fresh(['paymentMethod'])));
    }

    /**
     * GET /api/v1/wallet/bank-accounts — บัญชีรับเงินของฉัน
     */
    public function bankAccounts(Request $request): JsonResponse
    {
        return $this->ok('ดึงบัญชีรับเงินสำเร็จ', [
            'items' => $this->paymentMethodsFor($request->user()),
            'max_accounts' => self::MAX_PAYMENT_METHODS,
        ]);
    }

    /**
     * POST /api/v1/wallet/bank-accounts — เพิ่มบัญชีรับเงิน (ต้องยืนยัน PIN)
     *
     * body: type (bank_transfer|promptpay), account_name, account_number, bank_name (บังคับเมื่อ bank_transfer), bank_code?, name?, is_default?, pin
     */
    public function storeBankAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:bank_transfer,promptpay',
            'account_name' => 'required|string|max:255',
            'account_number' => ['required', 'string', 'max:30', 'regex:/^[0-9\-\s]{9,30}$/'],
            'bank_name' => 'required_if:type,bank_transfer|nullable|string|max:255',
            'bank_code' => 'nullable|string|max:20',
            'name' => 'nullable|string|max:255',
            'is_default' => 'nullable|boolean',
            'pin' => 'required|string|max:20',
        ], [
            'type.required' => 'กรุณาเลือกประเภทบัญชี',
            'type.in' => 'ประเภทบัญชีไม่ถูกต้อง',
            'account_name.required' => 'กรุณากรอกชื่อบัญชี',
            'account_number.required' => 'กรุณากรอกเลขบัญชีหรือเบอร์พร้อมเพย์',
            'account_number.regex' => 'เลขบัญชีหรือเบอร์พร้อมเพย์ไม่ถูกต้อง',
            'bank_name.required_if' => 'กรุณาเลือกธนาคาร',
            'pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        /** @var User $user */
        $user = $request->user();
        $wallet = $this->wallets->getOrCreateWallet($user);

        if ($blocked = $this->walletGuard($wallet)) {
            return $blocked;
        }
        if ($pinError = $this->checkPin($wallet, (string) $request->input('pin'))) {
            return $pinError;
        }

        if (PaymentMethod::where('user_id', $user->id)->where('is_active', true)->count() >= self::MAX_PAYMENT_METHODS) {
            return $this->error('เพิ่มบัญชีรับเงินได้สูงสุด '.self::MAX_PAYMENT_METHODS.' บัญชี กรุณาลบบัญชีเดิมก่อน', 'LIMIT_REACHED', 422);
        }

        $number = preg_replace('/[\s\-]/', '', (string) $request->input('account_number'));
        $type = (string) $request->input('type');

        $duplicate = PaymentMethod::where('user_id', $user->id)
            ->where('type', $type)
            ->where('account_number', $number)
            ->where('is_active', true)
            ->exists();
        if ($duplicate) {
            return $this->error('บัญชีนี้ถูกเพิ่มไว้แล้ว', 'DUPLICATE_ACCOUNT', 409);
        }

        $isFirst = ! PaymentMethod::where('user_id', $user->id)->where('is_active', true)->exists();
        $bankName = $type === 'promptpay' ? 'พร้อมเพย์' : trim((string) $request->input('bank_name'));

        // ไม่รับ is_verified จาก client — แอดมินเป็นผู้ยืนยันบัญชี
        $method = new PaymentMethod;
        $method->forceFill([
            'user_id' => $user->id,
            'type' => $type,
            'name' => trim((string) ($request->input('name') ?: ($bankName.' '.substr($number, -4)))),
            'account_name' => trim((string) $request->input('account_name')),
            'account_number' => $number,
            'bank_name' => $bankName,
            'bank_code' => $request->input('bank_code'),
            'is_active' => true,
            'is_default' => $isFirst || (bool) $request->boolean('is_default'),
            'is_verified' => false,
        ])->save();

        $this->notifySecurityChange($user, 'เพิ่มบัญชีรับเงินใหม่', 'มีการเพิ่มบัญชีรับเงิน '.$method->bank_name.' ลงท้าย '.substr($number, -4).' ถ้าไม่ใช่คุณ กรุณาติดต่อทีมงานทันที');

        return $this->ok('เพิ่มบัญชีรับเงินสำเร็จ', $this->serializePaymentMethod($method->fresh()), 201);
    }

    /**
     * DELETE /api/v1/wallet/bank-accounts/{id} (หรือ POST .../{id}/delete) — ลบบัญชีรับเงิน (ต้องยืนยัน PIN)
     */
    public function destroyBankAccount(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), ['pin' => 'required|string|max:20'], ['pin.required' => 'กรุณากรอก PIN กระเป๋าเงิน']);
        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $method = PaymentMethod::where('user_id', $user->id)->find($id);
        if (! $method) {
            return $this->error('ไม่พบบัญชีรับเงินนี้', 'NOT_FOUND', 404);
        }

        $wallet = $this->wallets->getOrCreateWallet($user);
        if ($blocked = $this->walletGuard($wallet)) {
            return $blocked;
        }
        if ($pinError = $this->checkPin($wallet, (string) $request->input('pin'))) {
            return $pinError;
        }

        $inUse = WithdrawalRequest::where('user_id', $user->id)
            ->where('payment_method_id', $method->id)
            ->whereIn('status', ['pending', 'processing', 'approved'])
            ->exists();
        if ($inUse) {
            return $this->error('บัญชีนี้มีคำขอถอนเงินที่ยังไม่เสร็จ ลบได้หลังโอนเงินเสร็จ', 'IN_USE', 409);
        }

        $wasDefault = (bool) $method->is_default;
        $method->delete();

        if ($wasDefault) {
            $next = PaymentMethod::where('user_id', $user->id)->where('is_active', true)->latest('id')->first();
            $next?->update(['is_default' => true]);
        }

        $this->notifySecurityChange($user, 'ลบบัญชีรับเงิน', 'มีการลบบัญชีรับเงินลงท้าย '.substr((string) $method->account_number, -4).' ถ้าไม่ใช่คุณ กรุณาติดต่อทีมงานทันที');

        return $this->ok('ลบบัญชีรับเงินแล้ว', ['id' => (int) $id]);
    }

    /**
     * POST /api/v1/wallet/bank-accounts/{id}/default — ตั้งเป็นบัญชีหลัก
     */
    public function setDefaultBankAccount(Request $request, int $id): JsonResponse
    {
        $method = PaymentMethod::where('user_id', $request->user()->id)->where('is_active', true)->find($id);
        if (! $method) {
            return $this->error('ไม่พบบัญชีรับเงินนี้', 'NOT_FOUND', 404);
        }

        // PaymentMethod::saving ล้าง default ของบัญชีอื่นให้เอง
        $method->update(['is_default' => true]);

        return $this->ok('ตั้งเป็นบัญชีหลักแล้ว', $this->serializePaymentMethod($method->fresh()));
    }

    /**
     * POST /api/v1/wallet/pin — ตั้ง/เปลี่ยน PIN กระเป๋าเงิน (6 หลัก)
     *
     * body: pin, pin_confirmation, current_pin (บังคับเมื่อเคยตั้ง PIN แล้ว)
     */
    public function setPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pin' => ['required', 'string', 'regex:/^\d{6}$/', 'confirmed'],
            'current_pin' => 'nullable|string|max:20',
        ], [
            'pin.required' => 'กรุณากรอก PIN ใหม่',
            'pin.regex' => 'PIN ต้องเป็นตัวเลข 6 หลัก',
            'pin.confirmed' => 'PIN ยืนยันไม่ตรงกัน',
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $pin = (string) $request->input('pin');
        if (preg_match('/^(\d)\1{5}$/', $pin) || in_array($pin, ['123456', '654321', '012345', '543210'], true)) {
            return $this->error('PIN ง่ายเกินไป กรุณาตั้ง PIN ที่คาดเดายากกว่านี้', 'PIN_TOO_WEAK', 422);
        }

        $user = $request->user();
        $wallet = $this->wallets->getOrCreateWallet($user);

        if ($blocked = $this->walletGuard($wallet, false)) {
            return $blocked;
        }

        $hadPin = $wallet->hasPIN();
        if ($hadPin) {
            if (! $request->filled('current_pin')) {
                return $this->error('กรุณากรอก PIN เดิม', 'CURRENT_PIN_REQUIRED', 422);
            }
            if ($pinError = $this->checkPin($wallet, (string) $request->input('current_pin'))) {
                return $pinError;
            }
        }

        $wallet->setPIN($pin);
        $wallet->resetFailedAttempts();

        $this->notifySecurityChange($user, $hadPin ? 'เปลี่ยน PIN กระเป๋าเงินแล้ว' : 'ตั้ง PIN กระเป๋าเงินแล้ว',
            'PIN กระเป๋าเงินของคุณถูก'.($hadPin ? 'เปลี่ยน' : 'ตั้ง').'เมื่อ '.now()->format('d/m/Y H:i').' ถ้าไม่ใช่คุณ กรุณาติดต่อทีมงานทันที');

        return $this->ok($hadPin ? 'เปลี่ยน PIN สำเร็จ' : 'ตั้ง PIN สำเร็จ', ['has_pin' => true]);
    }

    // =====================================================================
    // ภายใน
    // =====================================================================

    /**
     * กระเป๋าต้องใช้งานได้ (และตั้ง PIN แล้ว ถ้า $requirePin)
     */
    private function walletGuard(Wallet $wallet, bool $requirePin = true): ?JsonResponse
    {
        if (! $wallet->isActive()) {
            return $this->error('กระเป๋าเงินถูกล็อกชั่วคราว (กรอก PIN ผิดหลายครั้งหรือถูกระงับ) กรุณาลองใหม่ภายหลัง', 'WALLET_LOCKED', 403, [
                'locked_until' => $wallet->locked_until?->toIso8601String(),
            ]);
        }

        if ($requirePin && ! $wallet->hasPIN()) {
            return $this->error('กรุณาตั้ง PIN กระเป๋าเงินก่อน', 'PIN_NOT_SET', 422);
        }

        return null;
    }

    /**
     * ตรวจ PIN — ผิดนับจำนวนครั้ง (5 ครั้งล็อก 30 นาที)
     */
    private function checkPin(Wallet $wallet, string $pin): ?JsonResponse
    {
        if ($wallet->verifyPIN($pin)) {
            $wallet->resetFailedAttempts();

            return null;
        }

        $wallet->incrementFailedAttempts();
        $wallet->refresh();
        $remaining = max(0, 5 - (int) $wallet->failed_attempts);

        return $this->error(
            $remaining > 0 ? "PIN ไม่ถูกต้อง (เหลือ {$remaining} ครั้ง)" : 'PIN ไม่ถูกต้องเกินกำหนด กระเป๋าถูกล็อก 30 นาที',
            $remaining > 0 ? 'INVALID_PIN' : 'WALLET_LOCKED',
            403,
            ['attempts_remaining' => $remaining]
        );
    }

    /**
     * หาช่องทางรับเงิน: id ที่ส่งมา → ประเภทที่ส่งมา (บัญชีหลักก่อน) → บัญชีหลัก
     */
    private function resolvePaymentMethod(User $user, mixed $id, ?string $type): ?PaymentMethod
    {
        $base = PaymentMethod::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('type', ['bank_transfer', 'promptpay']);

        if ($id !== null && $id !== '') {
            return (clone $base)->find((int) $id);
        }

        $mapped = match ($type) {
            'bank', 'bank_transfer' => 'bank_transfer',
            'promptpay' => 'promptpay',
            default => null,
        };

        if ($mapped !== null) {
            $query = (clone $base)->where('type', $mapped);
        } else {
            $query = clone $base;
        }

        return $query->orderByDesc('is_default')->latest('id')->first();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentMethodsFor(User $user): array
    {
        return PaymentMethod::where('user_id', $user->id)
            ->where('is_active', true)
            ->whereIn('type', ['bank_transfer', 'promptpay'])
            ->orderByDesc('is_default')
            ->latest('id')
            ->get()
            ->map(fn (PaymentMethod $m) => $this->serializePaymentMethod($m))
            ->values()
            ->all();
    }

    private function serializePaymentMethod(PaymentMethod $m): array
    {
        $number = (string) $m->account_number;

        return [
            'id' => (int) $m->id,
            'type' => $m->type,
            'name' => $m->name,
            'bank_name' => $m->bank_name,
            'bank_code' => $m->bank_code,
            'account_name' => $m->account_name,
            'account_number_masked' => strlen($number) > 4 ? str_repeat('•', max(0, strlen($number) - 4)).substr($number, -4) : $number,
            'account_last4' => substr($number, -4),
            'is_default' => (bool) $m->is_default,
            'is_verified' => (bool) $m->is_verified,
            'created_at' => $m->created_at?->toIso8601String(),
        ];
    }

    private function serializeWithdrawal(WithdrawalRequest $w): array
    {
        $labels = [
            'pending' => 'รอตรวจสอบ',
            'processing' => 'กำลังดำเนินการ',
            'approved' => 'อนุมัติแล้ว รอโอนเงิน',
            'rejected' => 'ถูกปฏิเสธ',
            'cancelled' => 'ยกเลิกแล้ว',
            'completed' => 'โอนเงินแล้ว',
        ];
        $details = is_array($w->payment_details) ? $w->payment_details : [];
        $number = (string) ($details['account_number'] ?? $w->paymentMethod?->account_number ?? '');

        return [
            'id' => (int) $w->id,
            'request_id' => $w->request_id,
            'amount' => round((float) $w->amount, 2),
            'fee' => round((float) $w->fee, 2),
            'tax' => round((float) $w->tax, 2),
            'net_amount' => round((float) $w->net_amount, 2),
            'currency' => $w->currency ?? 'THB',
            'status' => $w->status,
            'status_label' => $labels[$w->status] ?? $w->status,
            'can_cancel' => $w->status === 'pending',
            'payment_method' => [
                'type' => $w->payment_type,
                'bank_name' => $details['bank_name'] ?? $w->paymentMethod?->bank_name,
                'account_name' => $details['account_name'] ?? $w->paymentMethod?->account_name,
                'account_last4' => $number !== '' ? substr($number, -4) : null,
            ],
            'note' => $w->user_note,
            'rejection_reason' => $w->rejection_reason,
            'created_at' => $w->created_at?->toIso8601String(),
            'approved_at' => $w->approved_at?->toIso8601String(),
            'rejected_at' => $w->rejected_at?->toIso8601String(),
            'transfer_completed_at' => $w->transfer_completed_at?->toIso8601String(),
        ];
    }

    private function mapWithdrawError(\Throwable $e): JsonResponse
    {
        $message = $e->getMessage();
        Log::warning('API withdraw failed', ['error' => $message]);

        return match (true) {
            str_contains($message, 'KYC') => $this->error('กรุณายืนยันตัวตน (KYC) ก่อนถอนเงิน', 'KYC_REQUIRED', 403),
            str_contains($message, 'PIN') => $this->error('PIN ไม่ถูกต้อง', 'INVALID_PIN', 403),
            str_contains($message, 'ไม่เพียงพอ'), $message === 'Insufficient balance' => $this->error('ยอดเงินในกระเป๋าไม่เพียงพอ', 'INSUFFICIENT_BALANCE', 422),
            str_contains($message, 'ขั้นต่ำ'), str_contains($message, 'สูงสุด') => $this->error($message, 'AMOUNT_OUT_OF_RANGE', 422),
            str_contains($message, 'ไม่สามารถใช้งานได้'), $message === 'Wallet is not active' => $this->error('กระเป๋าเงินถูกล็อกชั่วคราว', 'WALLET_LOCKED', 403),
            default => $this->error('ส่งคำขอถอนเงินไม่สำเร็จ กรุณาลองใหม่อีกครั้ง', 'WITHDRAW_FAILED', 500),
        };
    }

    private function notifySecurityChange(User $user, string $title, string $message): void
    {
        try {
            app(NotificationService::class)->create($user, 'wallet_security', $title, $message, [], '/user/wallet');
        } catch (\Throwable $e) {
            Log::warning('Wallet security notification failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function ok(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    private function error(string $message, string $code, int $status, array $data = []): JsonResponse
    {
        $body = ['success' => false, 'message' => $message, 'code' => $code];
        if ($data !== []) {
            $body['data'] = $data;
        }

        return response()->json($body, $status);
    }

    private function validationError(array $errors): JsonResponse
    {
        $first = collect($errors)->flatten()->first() ?? 'ข้อมูลไม่ถูกต้อง';

        return response()->json([
            'success' => false,
            'message' => $first,
            'code' => 'VALIDATION_ERROR',
            'errors' => $errors,
        ], 422);
    }
}
