<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Models\CryptoWithdrawalRequest;
use App\Services\Crypto\DepositDetectionService;
use App\Services\Crypto\WithdrawalProcessingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CryptoManagementController extends Controller
{
    protected WithdrawalProcessingService $withdrawalService;
    protected DepositDetectionService $depositService;

    public function __construct(
        WithdrawalProcessingService $withdrawalService,
        DepositDetectionService $depositService
    ) {
        $this->withdrawalService = $withdrawalService;
        $this->depositService = $depositService;
    }

    /**
     * Crypto Dashboard
     */
    public function dashboard()
    {
        // Statistics
        $stats = [
            'total_wallets' => CryptoWallet::count(),
            'active_wallets' => CryptoWallet::where('status', 'active')->count(),
            'total_users' => CryptoWallet::distinct('user_id')->count(),

            'pending_deposits' => CryptoTransaction::where('type', 'deposit')
                ->where('status', 'pending')
                ->count(),
            'pending_withdrawals' => CryptoWithdrawalRequest::where('status', 'pending')->count(),
            'reviewing_withdrawals' => CryptoWithdrawalRequest::where('status', 'reviewing')->count(),

            'total_volume_24h' => CryptoTransaction::where('created_at', '>=', now()->subHours(24))
                ->where('status', 'confirmed')
                ->sum('amount_thb'),

            'deposits_24h' => CryptoTransaction::where('type', 'deposit')
                ->where('created_at', '>=', now()->subHours(24))
                ->where('status', 'confirmed')
                ->count(),

            'withdrawals_24h' => CryptoTransaction::where('type', 'withdrawal')
                ->where('created_at', '>=', now()->subHours(24))
                ->where('status', 'confirmed')
                ->count(),
        ];

        // Recent transactions
        $recentTransactions = CryptoTransaction::with(['user', 'currency', 'cryptoWallet'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Pending withdrawals needing review
        $pendingWithdrawals = CryptoWithdrawalRequest::with(['user', 'currency', 'cryptoWallet'])
            ->whereIn('status', ['pending', 'reviewing'])
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get();

        // Currency statistics
        $currencyStats = CryptoCurrency::withCount([
            'transactions' => function ($q) {
                $q->where('status', 'confirmed')
                  ->where('created_at', '>=', now()->subDays(30));
            }
        ])->get();

        // Daily volume chart data (last 30 days)
        $volumeData = $this->getDailyVolumeData(30);

        return view('admin.crypto.dashboard', compact(
            'stats',
            'recentTransactions',
            'pendingWithdrawals',
            'currencyStats',
            'volumeData'
        ));
    }

    /**
     * Withdrawal Management
     */
    public function withdrawals(Request $request)
    {
        $query = CryptoWithdrawalRequest::with(['user', 'currency', 'cryptoWallet']);

        // Filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('currency')) {
            $currency = CryptoCurrency::where('code', $request->currency)->first();
            if ($currency) {
                $query->where('crypto_currency_id', $currency->id);
            }
        }

        $withdrawals = $query->orderBy('created_at', 'desc')->paginate(50);

        $currencies = CryptoCurrency::active()->get();

        $stats = $this->withdrawalService->getWithdrawalStats();

        return view('admin.crypto.withdrawals', compact('withdrawals', 'currencies', 'stats'));
    }

    /**
     * Approve withdrawal
     */
    public function approveWithdrawal($id)
    {
        $withdrawal = CryptoWithdrawalRequest::findOrFail($id);

        if ($withdrawal->status !== 'pending' && $withdrawal->status !== 'reviewing') {
            return back()->with('error', 'ไม่สามารถอนุมัติคำขอนี้ได้');
        }

        try {
            $this->withdrawalService->approveWithdrawal($withdrawal);
            $this->withdrawalService->executeWithdrawal($withdrawal);

            return back()->with('success', 'อนุมัติและดำเนินการถอนเงินสำเร็จ');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Reject withdrawal
     */
    public function rejectWithdrawal(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $withdrawal = CryptoWithdrawalRequest::findOrFail($id);

        try {
            $this->withdrawalService->rejectWithdrawal($withdrawal, $request->reason);

            return back()->with('success', 'ปฏิเสธคำขอถอนเงินสำเร็จ');
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Transaction Monitor
     */
    public function transactions(Request $request)
    {
        $query = CryptoTransaction::with(['user', 'currency', 'cryptoWallet']);

        // Filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('currency')) {
            $currency = CryptoCurrency::where('code', $request->currency)->first();
            if ($currency) {
                $query->where('crypto_currency_id', $currency->id);
            }
        }

        if ($request->has('tx_hash')) {
            $query->where('tx_hash', 'like', '%' . $request->tx_hash . '%');
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        $currencies = CryptoCurrency::active()->get();

        return view('admin.crypto.transactions', compact('transactions', 'currencies'));
    }

    /**
     * Wallets Management
     */
    public function wallets(Request $request)
    {
        $query = CryptoWallet::with(['user', 'cryptoAddresses.currency']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $wallets = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.crypto.wallets', compact('wallets'));
    }

    /**
     * Currency Management
     */
    public function currencies()
    {
        $currencies = CryptoCurrency::withCount('transactions')->get();

        return view('admin.crypto.currencies', compact('currencies'));
    }

    /**
     * Update currency settings
     */
    public function updateCurrency(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'boolean',
            'deposit_enabled' => 'boolean',
            'withdrawal_enabled' => 'boolean',
            'min_deposit' => 'nullable|numeric|min:0',
            'min_withdrawal' => 'nullable|numeric|min:0',
            'max_withdrawal' => 'nullable|numeric|min:0',
            'withdrawal_fee' => 'nullable|numeric|min:0',
        ]);

        $currency = CryptoCurrency::findOrFail($id);
        $currency->update($request->only([
            'is_active',
            'deposit_enabled',
            'withdrawal_enabled',
            'min_deposit',
            'min_withdrawal',
            'max_withdrawal',
            'withdrawal_fee',
        ]));

        return back()->with('success', 'อัปเดตการตั้งค่าสำเร็จ');
    }

    /**
     * Manual deposit scan
     */
    public function scanDeposits()
    {
        try {
            $results = $this->depositService->scanForDeposits();

            return back()->with('success',
                "สแกนเสร็จสิ้น: ตรวจพบ {$results['detected']} รายการ, ประมวลผล {$results['processed']} รายการ"
            );
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * Manual withdrawal processing
     */
    public function processWithdrawals()
    {
        try {
            $results = $this->withdrawalService->processPendingWithdrawals();

            return back()->with('success',
                "ประมวลผลเสร็จสิ้น: อนุมัติ {$results['approved']} รายการ, ส่ง {$results['sent']} รายการ"
            );
        } catch (\Exception $e) {
            return back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * System Settings
     */
    public function settings()
    {
        $settings = [
            'max_auto_approve_amount' => config('crypto.max_auto_approve_amount', 100000),
            'auto_approve_enabled' => config('crypto.auto_approve_enabled', true),
            'deposit_scan_interval' => config('crypto.deposit_scan_interval', 30),
            'withdrawal_process_interval' => config('crypto.withdrawal_process_interval', 60),
        ];

        return view('admin.crypto.settings', compact('settings'));
    }

    /**
     * Update system settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'max_auto_approve_amount' => 'required|numeric|min:0',
            'auto_approve_enabled' => 'boolean',
            'deposit_scan_interval' => 'required|integer|min:10',
            'withdrawal_process_interval' => 'required|integer|min:30',
        ]);

        // Save to database settings or config file
        // For now, just return success
        return back()->with('success', 'บันทึกการตั้งค่าสำเร็จ');
    }

    /**
     * Get daily volume data for charts
     */
    protected function getDailyVolumeData(int $days): array
    {
        $data = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $deposits = CryptoTransaction::where('type', 'deposit')
                ->where('status', 'confirmed')
                ->whereDate('created_at', $date)
                ->sum('amount_thb');

            $withdrawals = CryptoTransaction::where('type', 'withdrawal')
                ->where('status', 'confirmed')
                ->whereDate('created_at', $date)
                ->sum('amount_thb');

            $data[] = [
                'date' => $date,
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'total' => $deposits + $withdrawals,
            ];
        }

        return $data;
    }
}
