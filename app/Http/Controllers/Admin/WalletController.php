<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Exception;

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
        if (!auth()->user()->hasPermission('view_all_wallets')) {
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

        return view('admin.wallet.show', compact('wallet', 'statistics', 'recentTransactions', 'recentLogs'));
    }

    /**
     * ปรับยอด wallet balance (Super Admin เท่านั้น)
     *
     * ⚠️ SECURITY: การกระทำที่อันตราย ต้องตรวจสอบ Policy
     */
    public function adjustBalance(Request $request, $id)
    {
        $wallet = Wallet::findOrFail($id);

        // ✅ ใช้ Policy - deposit/withdraw methods
        if ($request->amount > 0) {
            $this->authorize('deposit', $wallet);
        } else {
            $this->authorize('withdraw', $wallet);
        }

        $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $transaction = $this->walletService->adjustBalance(
                $wallet,
                $request->amount,
                $request->reason,
                auth()->user()
            );

            $action = $request->amount > 0 ? 'เพิ่ม' : 'หัก';
            return redirect()->back()->with('success', "{$action}ยอดเงินสำเร็จ: " . number_format(abs($request->amount), 2) . ' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
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
            $this->walletService->logAction($wallet, 'wallet_locked', 'Wallet locked by admin: ' . auth()->user()->name, 'critical');

            return redirect()->back()->with('success', 'ล็อกกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            $this->walletService->logAction($wallet, 'wallet_unlocked', 'Wallet unlocked by admin: ' . auth()->user()->name, 'info');

            return redirect()->back()->with('success', 'ปลดล็อกกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Deposit money (Admin only)
     */
    public function deposit(Request $request)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $wallet = $this->walletService->getOrCreateWallet($user);

            $transaction = $this->walletService->deposit(
                $wallet,
                $request->amount,
                $request->description ?? 'Admin deposit',
                'admin_deposit',
                auth()->id()
            );

            return redirect()->back()->with('success', 'ฝากเงินสำเร็จ: ' . number_format($request->amount, 2) . ' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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

            return redirect()->back()->with('success', 'ถอนเงินสำเร็จ: ' . number_format($request->amount, 2) . ' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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

            return redirect()->back()->with('success', 'โอนเงินสำเร็จ: ' . number_format($request->amount, 2) . ' บาท ไปยัง ' . $toWallet->wallet_address);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Refund to user's wallet (Admin only)
     */
    public function refund(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'reference_type' => 'nullable|string',
            'reference_id' => 'nullable|integer',
        ]);

        try {
            $wallet = Wallet::findOrFail($id);
            $admin = auth()->user();

            $transaction = $this->walletService->refund(
                $wallet,
                $request->amount,
                $request->reason,
                $admin,
                $request->reference_type,
                $request->reference_id
            );

            return redirect()->back()->with('success', 'คืนเงินสำเร็จ: ' . number_format($request->amount, 2) . ' บาท');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Rollback a transaction (Admin only)
     */
    public function rollbackTransaction(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'transaction_id' => 'required|exists:wallet_transactions,id',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $transaction = WalletTransaction::findOrFail($request->transaction_id);
            $admin = auth()->user();

            $rollbackTx = $this->walletService->rollbackTransaction(
                $transaction,
                $request->reason,
                $admin
            );

            return redirect()->back()->with('success', 'Rollback ธุรกรรมสำเร็จ');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * โอนเงินระหว่างกระเป๋าโดย Admin
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function adminTransfer(Request $request)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasRole('admin')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'from_wallet_id' => 'required|exists:wallets,id',
            'to_wallet_id' => 'required|exists:wallets,id|different:from_wallet_id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $fromWallet = Wallet::findOrFail($request->from_wallet_id);
            $toWallet = Wallet::findOrFail($request->to_wallet_id);
            $admin = auth()->user();

            $result = $this->walletService->adminTransfer(
                $fromWallet,
                $toWallet,
                $request->amount,
                $request->reason,
                $admin
            );

            return redirect()->back()->with('success',
                'โอนเงินสำเร็จ: ' . number_format($request->amount, 2) . ' บาท จาก ' .
                $fromWallet->user->name . ' ไปยัง ' . $toWallet->user->name
            );
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ระงับการใช้งานกระเป๋า (Suspend)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function suspendUserWallet(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasRole('admin')) {
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ยกเลิกการระงับกระเป๋า (Unsuspend)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function unsuspendUserWallet(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin() && !auth()->user()->hasRole('admin')) {
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
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงธุรกรรมทั้งหมดในระบบ
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function allTransactions(Request $request)
    {
        if (!auth()->user()->hasPermission('view_all_wallets')) {
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
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function allLogs(Request $request)
    {
        if (!auth()->user()->hasPermission('view_all_wallets')) {
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletsDropdown(Request $request)
    {
        $excludeId = $request->input('exclude');
        $wallets = $this->walletService->getWalletsForDropdown($excludeId);

        return response()->json([
            'success' => true,
            'data' => $wallets->map(function($wallet) {
                return [
                    'id' => $wallet->id,
                    'wallet_address' => $wallet->wallet_address,
                    'user_name' => $wallet->user->name ?? 'N/A',
                    'user_email' => $wallet->user->email ?? 'N/A',
                    'balance' => number_format($wallet->balance, 2),
                    'label' => $wallet->user->name . ' (' . $wallet->wallet_address . ') - ' . number_format($wallet->balance, 2) . ' THB',
                ];
            }),
        ]);
    }

    /**
     * รีเซ็ต PIN ของกระเป๋า (Admin only)
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetUserPin(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'เฉพาะ Super Admin เท่านั้นที่สามารถรีเซ็ต PIN ได้');
        }

        try {
            $wallet = Wallet::findOrFail($id);

            // รีเซ็ต PIN
            $wallet->update([
                'pin_hash' => null,
                'failed_attempts' => 0,
                'locked_until' => null,
            ]);

            $this->walletService->logAction(
                $wallet,
                'pin_changed',
                'PIN ถูกรีเซ็ตโดยแอดมิน: ' . auth()->user()->name,
                'warning'
            );

            return redirect()->back()->with('success', 'รีเซ็ต PIN สำเร็จ ผู้ใช้สามารถตั้ง PIN ใหม่ได้');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Export ธุรกรรมเป็น CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportTransactions(Request $request)
    {
        if (!auth()->user()->hasPermission('view_all_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงฟีเจอร์นี้');
        }

        $filters = [
            'wallet_id' => $request->input('wallet_id'),
            'type' => $request->input('type'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $filename = 'wallet_transactions_' . date('Y-m-d_His') . '.csv';

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

            if (!empty($filters['wallet_id'])) {
                $query->where('wallet_id', $filters['wallet_id']);
            }
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }
            if (!empty($filters['date_to'])) {
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
