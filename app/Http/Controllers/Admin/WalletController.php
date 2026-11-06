<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\WalletAdminException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletAdminAdjustment;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Display wallet dashboard (Admin's own wallet)
     */
    public function index()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);
        $statistics = $this->walletService->getWalletStatistics($wallet);

        $recentTransactions = $wallet->transactions()
            ->with('relatedWallet.user')
            ->latest()
            ->take(10)
            ->get();

        $recentLogs = $wallet->logs()
            ->latest()
            ->take(10)
            ->get();

        return view('admin.wallet.index', compact('wallet', 'statistics', 'recentTransactions', 'recentLogs'));
    }

    /**
     * Display all wallets in system (Admin only)
     */
    public function allWallets(Request $request)
    {
        if (! auth()->user()->hasPermission('view_all_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        $filters = [
            'user_id' => $request->input('user_id'),
            'status' => $request->input('status'),
            'min_balance' => $request->input('min_balance'),
            'max_balance' => $request->input('max_balance'),
            'search' => $request->input('search'),
        ];

        $wallets = $this->walletService->getAllWallets($filters, 20);
        $systemStats = $this->walletService->getSystemStatistics();

        return view('admin.wallet.all', compact('wallets', 'systemStats', 'filters'));
    }

    /**
     * แสดง wallet ของ user ที่ระบุ
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ด้วย Policy
     */
    public function showWallet($id)
    {
        $wallet = Wallet::with(['user', 'transactions', 'logs'])->findOrFail($id);

        // ✅ ใช้ Policy แทน manual check
        $this->authorize('view', $wallet);

        $statistics = $this->walletService->getWalletStatistics($wallet);

        $recentTransactions = $wallet->transactions()
            ->with('relatedWallet.user')
            ->latest()
            ->take(20)
            ->get();

        $recentLogs = $wallet->logs()
            ->latest()
            ->take(20)
            ->get();

        // ประวัติการปรับยอดโดยแอดมินของกระเป๋าใบนี้ (audit trail)
        $adminAdjustments = WalletAdminAdjustment::where('wallet_id', $wallet->id)
            ->latest('id')
            ->take(10)
            ->get();

        // รายการที่ย้อนไปแล้ว — ซ่อนปุ่ม "ย้อนรายการ" ของแถวนั้น
        $rolledBackIds = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('reference_type', 'rollback')
            ->whereIn('reference_id', $recentTransactions->pluck('id'))
            ->pluck('reference_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // รายการหักที่มีการคืนเงินอ้างอยู่แล้ว — ย้อนไม่ได้ (เจ้าของจะได้เงินคืน 2 ทาง)
        $refundedIds = WalletAdminAdjustment::where('action', 'refund')
            ->whereIn('original_transaction_id', $recentTransactions->pluck('id'))
            ->pluck('original_transaction_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // ปุ่ม "ย้อน" แสดงเฉพาะรายการที่แอดมินสร้างเอง ยังไม่ถูกย้อน และยังไม่มีการคืนเงินอ้าง (เซิร์ฟเวอร์ตรวจซ้ำเสมอ)
        $rollbackableIds = $recentTransactions
            ->filter(fn (WalletTransaction $tx) => $this->walletService->isAdminRollbackable($tx)
                && ! in_array((int) $tx->id, $rolledBackIds, true)
                && ! in_array((int) $tx->id, $refundedIds, true))
            ->map(fn (WalletTransaction $tx) => (int) $tx->id)
            ->values()
            ->all();

        // รายการหักที่คืนเงินอ้างอิงได้ (พร้อมยอดที่ยังคืนได้)
        $refundableDebits = $this->walletService->adminRefundableDebits($wallet);

        return view('admin.wallet.show', compact(
            'wallet', 'statistics', 'recentTransactions', 'recentLogs', 'adminAdjustments',
            'rolledBackIds', 'rollbackableIds', 'refundableDebits'
        ));
    }

    /**
     * แอดมินเติมเงิน / หักเงิน กระเป๋าผู้ใช้
     *
     * ฟอร์มต้องส่ง: direction (credit|debit) · amount (บวกเสมอ) · reason · idempotency_key (โทเคนฟอร์ม)
     * · expected_balance (ยอดที่เห็นตอนยืนยัน) · confirmed=1 (ผ่านหน้าต่างยืนยันแล้ว)
     *
     * 🐛 (2026-11-04) ฟอร์มเดิมกลับเครื่องหมายจำนวนเงินตอนเปลี่ยน dropdown เท่านั้น — เลือก "หักเงิน"
     *    ก่อนแล้วค่อยพิมพ์จำนวน = ส่งค่าบวก = กลายเป็น "เติมเงิน" · ตอนนี้ส่งทิศทางแยกจากจำนวนเงิน
     *
     * ⚠️ SECURITY: ตรวจ Policy deposit/withdraw ตามทิศทาง
     */
    public function adjustBalance(Request $request, $id)
    {
        $wallet = Wallet::findOrFail($id);

        $request->validate($this->adminMoneyRules() + [
            'direction' => 'required|in:credit,debit',
        ], $this->adminMoneyMessages());

        $isCredit = $request->input('direction') === 'credit';
        $this->authorize($isCredit ? 'deposit' : 'withdraw', $wallet);

        try {
            $transaction = $this->walletService->adminAdjust(
                $wallet,
                $request->input('direction'),
                (float) $request->input('amount'),
                (string) $request->input('reason'),
                auth()->user(),
                (string) $request->input('idempotency_key'),
                (float) $request->input('expected_balance')
            );

            return $this->adminMoneyDone(
                $wallet->id,
                $transaction,
                ($isCredit ? 'เติมเงิน' : 'หักเงิน').'สำเร็จ ฿'.number_format((float) $transaction->amount, 2)
                    .' · ยอดคงเหลือ ฿'.number_format((float) $transaction->balance_after, 2)
            );
        } catch (\Throwable $e) {
            return $this->adminMoneyFailed($e, 'adjust', $wallet->id);
        }
    }

    /**
     * ประวัติการปรับยอดโดยแอดมินทั้งระบบ (audit trail)
     */
    public function adjustments(Request $request)
    {
        $this->authorize('viewAny', Wallet::class);

        $filters = $request->only(['wallet_id', 'admin_id', 'action', 'search', 'date_from', 'date_to']);

        $query = WalletAdminAdjustment::with(['wallet:id,wallet_address', 'user:id,name,email', 'walletTransaction:id,transaction_id'])
            ->latest('id');

        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', (int) $filters['wallet_id']);
        }
        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', (int) $filters['admin_id']);
        }
        if (! empty($filters['action']) && array_key_exists($filters['action'], WalletAdminAdjustment::ACTION_LABELS)) {
            $query->where('action', $filters['action']);
        }
        if (! empty($filters['search'])) {
            $term = '%'.addcslashes(mb_substr((string) $filters['search'], 0, 100), '%_\\').'%';
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', $term)
                    ->orWhere('admin_name', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term))
                    ->orWhereHas('wallet', fn ($w) => $w->where('wallet_address', 'like', $term));
            });
        }
        if (! empty($filters['date_from']) && strtotime((string) $filters['date_from'])) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($filters['date_from'])->startOfDay());
        }
        if (! empty($filters['date_to']) && strtotime((string) $filters['date_to'])) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($filters['date_to'])->endOfDay());
        }

        // ทิศทางดูจากยอดก่อน/หลัง — rollback เป็นได้ทั้งเข้าและออก (ขึ้นกับรายการเดิม) จึงนับตาม action ไม่ได้
        $summary = (clone $query)->reorder()
            ->selectRaw('COALESCE(SUM(CASE WHEN balance_after > balance_before THEN amount ELSE 0 END),0) as total_in')
            ->selectRaw('COALESCE(SUM(CASE WHEN balance_after < balance_before THEN amount ELSE 0 END),0) as total_out')
            ->selectRaw('COUNT(*) as total_count')
            ->first();

        $adjustments = $query->paginate(30)->withQueryString();

        return view('admin.wallet.adjustments', [
            'adjustments' => $adjustments,
            'filters' => $filters,
            'summary' => $summary,
            'actionLabels' => WalletAdminAdjustment::ACTION_LABELS,
        ]);
    }

    /**
     * กฎตรวจฟอร์มเงินของแอดมิน (ใช้ร่วม: เติม/หัก/คืนเงิน/โอน)
     *
     * @return array<string, string>
     */
    private function adminMoneyRules(): array
    {
        return [
            'amount' => 'required|numeric|decimal:0,2|min:0.01|max:'.WalletService::ADMIN_MAX_AMOUNT,
            'reason' => 'required|string|min:3|max:500',
            'idempotency_key' => 'required|string|max:64',
            'expected_balance' => 'required|numeric',
            'confirmed' => 'accepted',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function adminMoneyMessages(): array
    {
        return [
            'direction.required' => 'กรุณาเลือกประเภท (เติมเงิน/หักเงิน)',
            'direction.in' => 'ประเภทรายการไม่ถูกต้อง',
            'amount.required' => 'กรุณาระบุจำนวนเงิน',
            'amount.numeric' => 'จำนวนเงินต้องเป็นตัวเลข',
            'amount.decimal' => 'จำนวนเงินมีทศนิยมได้ไม่เกิน 2 ตำแหน่ง',
            'amount.min' => 'จำนวนเงินต้องมากกว่า 0',
            'amount.max' => 'จำนวนเงินเกินเพดานต่อครั้ง (฿'.number_format(WalletService::ADMIN_MAX_AMOUNT).')',
            'reason.required' => 'กรุณาระบุเหตุผล',
            'reason.min' => 'เหตุผลต้องยาวอย่างน้อย 3 ตัวอักษร',
            'reason.max' => 'เหตุผลยาวได้ไม่เกิน 500 ตัวอักษร',
            'idempotency_key.required' => 'ฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่แล้วทำรายการอีกครั้ง',
            'expected_balance.required' => 'ฟอร์มหมดอายุ กรุณาโหลดหน้าใหม่แล้วทำรายการอีกครั้ง',
            'confirmed.accepted' => 'กรุณากดยืนยันในหน้าต่างยืนยันก่อนทำรายการ',
            'to_wallet_id.required' => 'กรุณาเลือกกระเป๋าปลายทาง',
            'to_wallet_id.exists' => 'ไม่พบกระเป๋าปลายทาง',
            'to_wallet_id.different' => 'กระเป๋าปลายทางต้องไม่ใช่ใบเดียวกับต้นทาง',
            'transaction_id.required' => 'กรุณาเลือกรายการที่ต้องการย้อน',
            'transaction_id.exists' => 'ไม่พบรายการที่ต้องการย้อน',
        ];
    }

    /**
     * ทำรายการเสร็จ → กลับหน้ากระเป๋า (ส่งซ้ำ = แจ้งว่าทำไปแล้ว ไม่ได้ทำซ้ำ)
     */
    private function adminMoneyDone(int $walletId, WalletTransaction $transaction, string $successText)
    {
        $redirect = redirect()->route('admin.wallet.show', $walletId);

        if (! $transaction->wasRecentlyCreated) {
            return $redirect->with('info', 'รายการนี้ถูกบันทึกไปแล้ว (กดซ้ำ/ส่งฟอร์มเดิมซ้ำ) — ระบบไม่ได้ทำซ้ำ · เลขที่ '.$transaction->transaction_id);
        }

        return $redirect->with('success', $successText.' · เลขที่ '.$transaction->transaction_id);
    }

    /**
     * ทำรายการไม่สำเร็จ → แสดงข้อความภาษาไทย (ไม่โชว์ข้อความ exception ดิบ)
     */
    private function adminMoneyFailed(\Throwable $e, string $action, int $walletId)
    {
        if ($e instanceof WalletAdminException) {
            return redirect()->route('admin.wallet.show', $walletId)->withInput()->with('error', $e->getMessage());
        }

        Log::error('Admin wallet action failed', [
            'action' => $action,
            'wallet_id' => $walletId,
            'admin_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);

        return redirect()->route('admin.wallet.show', $walletId)->withInput()
            ->with('error', 'ทำรายการไม่สำเร็จ ระบบยังไม่ได้แตะยอดเงิน กรุณาลองใหม่อีกครั้ง');
    }

    /**
     * ล็อ็ก wallet ของ user (Admin เท่านั้น)
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ด้วย Policy
     */
    public function lockUserWallet($id)
    {
        $wallet = Wallet::findOrFail($id);

        // ✅ ใช้ Policy แทน manual check
        $this->authorize('delete', $wallet);  // ใช้ delete เพราะเป็นการกระทำที่รุนแรง

        try {
            $wallet->lockPermanent();
            $this->walletService->logAction($wallet, 'wallet_locked', 'Wallet locked by admin: '.auth()->user()->name, 'critical');

            return redirect()->back()->with('success', 'ล็อกกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ปลดล็อก wallet ของ user (Admin เท่านั้น)
     *
     * ⚠️ SECURITY: ตรวจสอบสิทธิ์ด้วย Policy
     */
    public function unlockUserWallet($id)
    {
        $wallet = Wallet::findOrFail($id);

        // ✅ ใช้ Policy แทน manual check
        $this->authorize('restore', $wallet);

        try {
            $wallet->unlock();
            $this->walletService->logAction($wallet, 'wallet_unlocked', 'Wallet unlocked by admin: '.auth()->user()->name, 'info');

            return redirect()->back()->with('success', 'ปลดล็อกกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Display all transactions
     */
    public function transactions(Request $request)
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        $query = $wallet->transactions()->with('relatedWallet.user');

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $transactions = $query->latest()->paginate(20);

        return view('admin.wallet.transactions', compact('wallet', 'transactions'));
    }

    /**
     * Display security logs
     */
    public function logs(Request $request)
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        $query = $wallet->logs();

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $logs = $query->latest()->paginate(20);

        return view('admin.wallet.logs', compact('wallet', 'logs'));
    }

    /**
     * Display wallet settings
     */
    public function settings()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        return view('admin.wallet.settings', compact('wallet'));
    }

    /**
     * Set or update PIN
     */
    public function setPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:6|regex:/^[0-9]+$/',
            'pin_confirmation' => 'required|same:pin',
        ]);

        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        try {
            $wallet->setPIN($request->pin);
            $this->walletService->logAction($wallet, 'pin_changed', 'PIN code updated', 'info');

            return redirect()->back()->with('success', 'PIN ถูกตั้งค่าเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ฟอร์มฝากเงินแบบเก่า (Super Admin) — POST /admin/wallet/deposit
     *
     * 🛡️ (2026-11-06) เดิมเรียก deposit() ตรง: ไม่มีเหตุผล ไม่มี audit ไม่มีกันกดซ้ำ และฟอร์มในหน้า
     *    /admin/wallet ส่ง user_id ของตัวเอง = super admin เติมเงินให้ตัวเองได้ไม่จำกัด
     *    ตอนนี้ผ่าน adminAdjust() เส้นเดียวกับหน้ากระเป๋า: ต้องมีเหตุผล + idempotency_key, ลง audit,
     *    และห้ามเติมเข้ากระเป๋าตัวเอง (route คงไว้ให้สคริปต์/บุ๊กมาร์กเดิม)
     */
    public function deposit(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $rules = $this->adminMoneyRules();
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'amount' => $rules['amount'],
            'reason' => $rules['reason'],
            'idempotency_key' => $rules['idempotency_key'],
            'expected_balance' => 'nullable|numeric',
        ], $this->adminMoneyMessages() + [
            'user_id.required' => 'กรุณาเลือกผู้ใช้',
            'user_id.exists' => 'ไม่พบผู้ใช้',
        ]);

        $user = User::findOrFail((int) $request->input('user_id'));
        if ((int) $user->id === (int) auth()->id()) {
            return redirect()->back()->withInput()
                ->with('error', 'แอดมินจัดการยอดเงินในกระเป๋าของตัวเองไม่ได้ (รวมถึง Super Admin) — ให้แอดมินคนอื่นทำแทน');
        }

        $wallet = $this->walletService->getOrCreateWallet($user);

        try {
            $transaction = $this->walletService->adminAdjust(
                $wallet,
                'credit',
                (float) $request->input('amount'),
                (string) $request->input('reason'),
                auth()->user(),
                (string) $request->input('idempotency_key'),
                $request->filled('expected_balance') ? (float) $request->input('expected_balance') : null
            );

            return $this->adminMoneyDone(
                $wallet->id,
                $transaction,
                'เติมเงินสำเร็จ ฿'.number_format((float) $transaction->amount, 2)
                    .' · ยอดคงเหลือ ฿'.number_format((float) $transaction->balance_after, 2)
            );
        } catch (\Throwable $e) {
            return $this->adminMoneyFailed($e, 'legacy_deposit', $wallet->id);
        }
    }

    /**
     * Withdraw money
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        try {
            $transaction = $this->walletService->withdraw(
                $wallet,
                $request->amount,
                $request->pin,
                $request->description ?? 'Withdrawal'
            );

            return redirect()->back()->with('success', 'ถอนเงินสำเร็จ: '.number_format($request->amount, 2).' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Transfer money to another user
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'wallet_address' => 'required|string|exists:wallets,wallet_address',
            'amount' => 'required|numeric|min:0.01',
            'pin' => 'required|string',
            'description' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $fromWallet = $this->walletService->getOrCreateWallet($user);
        $toWallet = Wallet::where('wallet_address', $request->wallet_address)->firstOrFail();

        if ($fromWallet->id === $toWallet->id) {
            return redirect()->back()->with('error', 'ไม่สามารถโอนเงินให้ตัวเองได้');
        }

        try {
            $result = $this->walletService->transfer(
                $fromWallet,
                $toWallet,
                $request->amount,
                $request->pin,
                $request->description ?? 'Transfer'
            );

            return redirect()->back()->with('success', 'โอนเงินสำเร็จ: '.number_format($request->amount, 2).' บาท ไปยัง '.$toWallet->wallet_address);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Lock wallet
     */
    public function lock()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        try {
            $wallet->lockPermanent();
            $this->walletService->logAction($wallet, 'wallet_locked', 'Wallet locked by user', 'warning');

            return redirect()->back()->with('success', 'กระเป๋าเงินถูกล็อกเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Unlock wallet
     */
    public function unlock()
    {
        $user = auth()->user();
        $wallet = $this->walletService->getOrCreateWallet($user);

        try {
            $wallet->unlock();
            $this->walletService->logAction($wallet, 'wallet_unlocked', 'Wallet unlocked by user', 'info');

            return redirect()->back()->with('success', 'กระเป๋าเงินถูกปลดล็อกเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Refund to user's wallet (Admin only)
     */
    public function refund(Request $request, $id)
    {
        $wallet = Wallet::findOrFail($id);
        $this->authorize('deposit', $wallet);

        // ต้องอ้างรายการหักเงินของกระเป๋าใบนี้ — reference_type/reference_id จากฟอร์มไม่ถูกใช้แล้ว
        //   (เดิมรับค่าอิสระ: ส่ง reference_type=rollback,reference_id=X ทำให้รายการ X "ย้อนไม่ได้" ได้)
        $request->validate($this->adminMoneyRules() + [
            'transaction_id' => 'required|integer',
        ], [
            'transaction_id.required' => 'กรุณาเลือกรายการหักเงินที่ต้องการคืน',
            'transaction_id.integer' => 'กรุณาเลือกรายการหักเงินที่ต้องการคืน',
        ] + $this->adminMoneyMessages());

        try {
            $transaction = $this->walletService->refund(
                $wallet,
                (int) $request->input('transaction_id'),
                (float) $request->input('amount'),
                (string) $request->input('reason'),
                auth()->user(),
                (string) $request->input('idempotency_key'),
                (float) $request->input('expected_balance')
            );

            return $this->adminMoneyDone(
                $wallet->id,
                $transaction,
                'คืนเงินสำเร็จ ฿'.number_format((float) $transaction->amount, 2)
                    .' · ยอดคงเหลือ ฿'.number_format((float) $transaction->balance_after, 2)
            );
        } catch (\Throwable $e) {
            return $this->adminMoneyFailed($e, 'refund', $wallet->id);
        }
    }

    /**
     * ย้อนรายการ (สร้างรายการกลับทิศ) — รายการต้องเป็นของกระเป๋าใบนี้
     */
    public function rollbackTransaction(Request $request, $id)
    {
        $wallet = Wallet::findOrFail($id);
        $this->authorize('update', $wallet);

        $request->validate([
            'transaction_id' => 'required|integer|exists:wallet_transactions,id',
            'reason' => 'required|string|min:3|max:500',
            'idempotency_key' => 'required|string|max:64',
            'expected_balance' => 'required|numeric',
            'confirmed' => 'accepted',
        ], $this->adminMoneyMessages());

        $transaction = WalletTransaction::whereKey((int) $request->input('transaction_id'))
            ->where('wallet_id', $wallet->id)
            ->first();
        if (! $transaction) {
            return redirect()->route('admin.wallet.show', $wallet->id)
                ->with('error', 'รายการนี้ไม่ได้อยู่ในกระเป๋าใบนี้');
        }

        try {
            $rollbackTx = $this->walletService->rollbackTransaction(
                $transaction,
                (string) $request->input('reason'),
                auth()->user(),
                (string) $request->input('idempotency_key'),
                (float) $request->input('expected_balance')
            );

            return $this->adminMoneyDone(
                $wallet->id,
                $rollbackTx,
                'ย้อนรายการ '.$transaction->transaction_id.' สำเร็จ · ยอดคงเหลือ ฿'.number_format((float) $rollbackTx->balance_after, 2)
            );
        } catch (\Throwable $e) {
            return $this->adminMoneyFailed($e, 'rollback', $wallet->id);
        }
    }

    /**
     * โอนเงินระหว่างกระเป๋าโดย Admin
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adminTransfer(Request $request)
    {
        $request->validate($this->adminMoneyRules() + [
            'from_wallet_id' => 'required|integer|exists:wallets,id',
            'to_wallet_id' => 'required|integer|exists:wallets,id|different:from_wallet_id',
        ], $this->adminMoneyMessages());

        $fromWallet = Wallet::with('user')->findOrFail((int) $request->input('from_wallet_id'));
        $toWallet = Wallet::with('user')->findOrFail((int) $request->input('to_wallet_id'));
        $this->authorize('withdraw', $fromWallet);
        $this->authorize('deposit', $toWallet);

        try {
            $result = $this->walletService->adminTransfer(
                $fromWallet,
                $toWallet,
                (float) $request->input('amount'),
                (string) $request->input('reason'),
                auth()->user(),
                (string) $request->input('idempotency_key'),
                (float) $request->input('expected_balance')
            );

            $out = $result['out_transaction'];
            if (! empty($result['replayed'])) {
                $out->wasRecentlyCreated = false;
            }

            return $this->adminMoneyDone(
                $fromWallet->id,
                $out,
                'โอนเงินสำเร็จ ฿'.number_format((float) $out->amount, 2).' จาก '
                    .($fromWallet->user->name ?? '-').' ไปยัง '.($toWallet->user->name ?? '-')
            );
        } catch (\Throwable $e) {
            return $this->adminMoneyFailed($e, 'transfer', $fromWallet->id);
        }
    }

    /**
     * ระงับการใช้งานกระเป๋า (Suspend)
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspendUserWallet(Request $request, $id)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $wallet = Wallet::findOrFail($id);
            $admin = auth()->user();

            $this->walletService->suspendWallet($wallet, $request->reason, $admin);

            return redirect()->back()->with('success', 'ระงับกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * ยกเลิกการระงับกระเป๋า (Unsuspend)
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsuspendUserWallet(Request $request, $id)
    {
        if (! auth()->user()->isSuperAdmin() && ! auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        try {
            $wallet = Wallet::findOrFail($id);
            $admin = auth()->user();

            $this->walletService->unsuspendWallet(
                $wallet,
                $request->input('reason', 'ยกเลิกการระงับโดยแอดมิน'),
                $admin
            );

            return redirect()->back()->with('success', 'ยกเลิกการระงับกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * แสดงธุรกรรมทั้งหมดในระบบ
     *
     * @return \Illuminate\View\View
     */
    public function allTransactions(Request $request)
    {
        if (! auth()->user()->hasPermission('view_all_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        $filters = [
            'wallet_id' => $request->input('wallet_id'),
            'user_id' => $request->input('user_id'),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'min_amount' => $request->input('min_amount'),
            'max_amount' => $request->input('max_amount'),
            'search' => $request->input('search'),
        ];

        $transactions = $this->walletService->getAllTransactions($filters, 25);

        // สถิติธุรกรรม
        $stats = [
            'total_today' => WalletTransaction::whereDate('created_at', today())->count(),
            'volume_today' => WalletTransaction::whereDate('created_at', today())->where('status', 'completed')->sum('amount'),
            'total_pending' => WalletTransaction::where('status', 'pending')->count(),
            'total_failed' => WalletTransaction::where('status', 'failed')->count(),
        ];

        // ประเภทธุรกรรมสำหรับ dropdown
        $transactionTypes = [
            'deposit' => 'ฝากเงิน',
            'withdrawal' => 'ถอนเงิน',
            'transfer_in' => 'รับโอน',
            'transfer_out' => 'โอนออก',
            'commission' => 'คอมมิชชั่น',
            'refund' => 'คืนเงิน',
            'fee' => 'ค่าธรรมเนียม',
            'bonus' => 'โบนัส',
        ];

        return view('admin.wallet.all-transactions', compact('transactions', 'filters', 'stats', 'transactionTypes'));
    }

    /**
     * แสดง logs ทั้งหมดในระบบ
     *
     * @return \Illuminate\View\View
     */
    public function allLogs(Request $request)
    {
        if (! auth()->user()->hasPermission('view_all_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        $filters = [
            'wallet_id' => $request->input('wallet_id'),
            'action' => $request->input('action'),
            'severity' => $request->input('severity'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
        ];

        $logs = $this->walletService->getAllLogs($filters, 25);

        // ประเภท action สำหรับ dropdown
        $actionTypes = [
            'login' => 'เข้าสู่ระบบ',
            'logout' => 'ออกจากระบบ',
            'transaction_attempt' => 'พยายามทำธุรกรรม',
            'transaction_success' => 'ธุรกรรมสำเร็จ',
            'transaction_failed' => 'ธุรกรรมล้มเหลว',
            'pin_changed' => 'เปลี่ยน PIN',
            'pin_failed' => 'PIN ผิด',
            'two_factor_enabled' => 'เปิด 2FA',
            'two_factor_disabled' => 'ปิด 2FA',
            'wallet_locked' => 'ล็อกกระเป๋า',
            'wallet_unlocked' => 'ปลดล็อกกระเป๋า',
            'suspicious_activity' => 'กิจกรรมน่าสงสัย',
            'settings_changed' => 'เปลี่ยนการตั้งค่า',
        ];

        // สถิติ logs
        $stats = [
            'total_today' => \App\Models\WalletLog::whereDate('created_at', today())->count(),
            'critical_today' => \App\Models\WalletLog::whereDate('created_at', today())->where('severity', 'critical')->count(),
            'warning_today' => \App\Models\WalletLog::whereDate('created_at', today())->where('severity', 'warning')->count(),
        ];

        return view('admin.wallet.all-logs', compact('logs', 'filters', 'actionTypes', 'stats'));
    }

    /**
     * ดึงรายการกระเป๋าสำหรับ dropdown (AJAX)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletsDropdown(Request $request)
    {
        $excludeId = $request->filled('exclude') ? (int) $request->input('exclude') : null;

        // 🔎 (2026-11-04) มีคำค้น → คืนแค่ 20 ใบที่ตรง (ชื่อ/อีเมล/เบอร์กระเป๋า) แทนการโหลดกระเป๋าทั้งระบบ
        if ($request->filled('q')) {
            $term = '%'.addcslashes(mb_substr((string) $request->input('q'), 0, 100), '%_\\').'%';
            $wallets = Wallet::with('user:id,name,email')
                ->select(['id', 'user_id', 'wallet_address', 'balance', 'currency', 'status'])
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->where(function ($q) use ($term) {
                    $q->where('wallet_address', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
                })
                ->orderBy('id', 'desc')
                ->limit(20)
                ->get();
        } else {
            $wallets = $this->walletService->getWalletsForDropdown($excludeId);
        }

        return response()->json([
            'success' => true,
            'data' => $wallets->map(function ($wallet) {
                return [
                    'id' => $wallet->id,
                    'wallet_address' => $wallet->wallet_address,
                    'user_name' => $wallet->user->name ?? 'N/A',
                    'user_email' => $wallet->user->email ?? 'N/A',
                    'balance' => number_format($wallet->balance, 2),
                    'status' => $wallet->status ?? 'active',
                    'label' => ($wallet->user->name ?? 'N/A').' ('.$wallet->wallet_address.') - '.number_format($wallet->balance, 2).' THB',
                ];
            }),
        ]);
    }

    /**
     * รีเซ็ต PIN ของกระเป๋า (Admin only)
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetUserPin(Request $request, $id)
    {
        if (! auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'เฉพาะ Super Admin เท่านั้นที่สามารถรีเซ็ต PIN ได้');
        }

        try {
            $wallet = Wallet::findOrFail($id);

            // รีเซ็ต PIN (ตรรกะเดียวกับแอปแอดมิน)
            $this->walletService->adminResetPin($wallet, auth()->user());

            return redirect()->back()->with('success', 'รีเซ็ต PIN สำเร็จ ผู้ใช้สามารถตั้ง PIN ใหม่ได้');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * Export ธุรกรรมเป็น CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportTransactions(Request $request)
    {
        if (! auth()->user()->hasPermission('view_all_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงฟีเจอร์นี้');
        }

        $filters = [
            'wallet_id' => $request->input('wallet_id'),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $filename = 'wallet_transactions_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($filters) {
            $file = fopen('php://output', 'w');

            // BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($file, [
                'รหัสธุรกรรม',
                'ผู้ใช้',
                'อีเมล',
                'Wallet Address',
                'ประเภท',
                'จำนวนเงิน',
                'ยอดก่อน',
                'ยอดหลัง',
                'สถานะ',
                'รายละเอียด',
                'วันที่',
            ]);

            // Query transactions
            $query = WalletTransaction::with(['wallet.user']);

            if (! empty($filters['wallet_id'])) {
                $query->where('wallet_id', $filters['wallet_id']);
            }
            if (! empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (! empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            $query->orderBy('created_at', 'desc')
                ->chunk(500, function ($transactions) use ($file) {
                    foreach ($transactions as $tx) {
                        fputcsv($file, [
                            $tx->transaction_id,
                            $tx->wallet->user->name ?? 'N/A',
                            $tx->wallet->user->email ?? 'N/A',
                            $tx->wallet->wallet_address ?? 'N/A',
                            $tx->type_label ?? $tx->type,
                            number_format($tx->amount, 2),
                            number_format($tx->balance_before, 2),
                            number_format($tx->balance_after, 2),
                            $tx->status_label ?? $tx->status,
                            $tx->description,
                            $tx->created_at->format('Y-m-d H:i:s'),
                        ]);
                    }
                });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
