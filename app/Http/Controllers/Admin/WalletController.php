<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
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
     * Show specific user's wallet
     */
    public function showWallet($id)
    {
        if (!auth()->user()->hasPermission('view_all_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        $wallet = Wallet::with(['user', 'transactions', 'logs'])->findOrFail($id);
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
     * Adjust wallet balance (Admin only)
     */
    public function adjustBalance(Request $request, $id)
    {
        if (!auth()->user()->isSuperAdmin()) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        $request->validate([
            'amount' => 'required|numeric|not_in:0',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $wallet = Wallet::findOrFail($id);

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
     * Lock user's wallet (Admin only)
     */
    public function lockUserWallet($id)
    {
        if (!auth()->user()->hasPermission('manage_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        try {
            $wallet = Wallet::findOrFail($id);
            $wallet->lockPermanent();
            $this->walletService->logAction($wallet, 'wallet_locked', 'Wallet locked by admin: ' . auth()->user()->name, 'critical');

            return redirect()->back()->with('success', 'ล็อกกระเป๋าเงินเรียบร้อยแล้ว');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Unlock user's wallet (Admin only)
     */
    public function unlockUserWallet($id)
    {
        if (!auth()->user()->hasPermission('manage_wallets')) {
            return redirect()->back()->with('error', 'คุณไม่มีสิทธิ์ในการดำเนินการนี้');
        }

        try {
            $wallet = Wallet::findOrFail($id);
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
}
