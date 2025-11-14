<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoCurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Services\Crypto\TPIXBlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * TPIX Admin Controller
 *
 * Admin interface for managing TPIX blockchain and wallets
 */
class TPIXController extends Controller
{
    protected TPIXBlockchainService $tpixService;

    public function __construct(TPIXBlockchainService $tpixService)
    {
        $this->tpixService = $tpixService;
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * TPIX Dashboard
     */
    public function dashboard()
    {
        try {
            // Get TPIX currency
            $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

            if (!$tpixCurrency) {
                return redirect()->route('admin.crypto.dashboard')
                    ->with('error', 'TPIX currency not found. Please run the seeder first.');
            }

            // Get network info from blockchain
            $networkInfo = $this->tpixService->getNetworkInfo();

            // TPIX Statistics
            $stats = [
                'total_supply' => $networkInfo['totalSupply'] ?? '7000000000',
                'circulating_supply' => $networkInfo['totalSupply'] ?? '7000000000',
                'current_block' => $networkInfo['blockNumber'] ?? 0,
                'total_wallets' => CryptoWallet::where('currency_id', $tpixCurrency->id)->count(),
                'active_wallets' => CryptoWallet::where('currency_id', $tpixCurrency->id)
                    ->where('status', 'active')
                    ->count(),
                'total_balance' => CryptoWallet::where('currency_id', $tpixCurrency->id)
                    ->sum('balance'),

                'transactions_24h' => CryptoTransaction::where('crypto_currency_id', $tpixCurrency->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->count(),

                'volume_24h' => CryptoTransaction::where('crypto_currency_id', $tpixCurrency->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->where('status', 'confirmed')
                    ->sum('amount'),

                'deposits_pending' => CryptoTransaction::where('crypto_currency_id', $tpixCurrency->id)
                    ->where('type', 'deposit')
                    ->where('status', 'pending')
                    ->count(),

                'withdrawals_pending' => CryptoTransaction::where('crypto_currency_id', $tpixCurrency->id)
                    ->where('type', 'withdrawal')
                    ->where('status', 'pending')
                    ->count(),
            ];

            // Recent transactions
            $recentTransactions = CryptoTransaction::with(['user', 'cryptoWallet'])
                ->where('crypto_currency_id', $tpixCurrency->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get();

            // Top wallets
            $topWallets = CryptoWallet::with('user')
                ->where('currency_id', $tpixCurrency->id)
                ->orderBy('balance', 'desc')
                ->limit(10)
                ->get();

            // Daily transaction volume (last 30 days)
            $volumeData = $this->getDailyVolumeData($tpixCurrency->id, 30);

            return view('admin.tpix.dashboard', compact(
                'tpixCurrency',
                'networkInfo',
                'stats',
                'recentTransactions',
                'topWallets',
                'volumeData'
            ));
        } catch (Exception $e) {
            Log::error('TPIX dashboard error', ['error' => $e->getMessage()]);

            // ถ้า TPIX currency ไม่มี ให้ redirect ไป crypto dashboard
            $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();
            if (!$tpixCurrency) {
                return redirect()->route('admin.crypto.dashboard')
                    ->with('error', 'TPIX currency not found. Please run the seeder first.');
            }

            // ถ้ามี currency แต่ blockchain error ให้แสดง dashboard พร้อม warning
            $networkInfo = [
                'error' => true,
                'message' => $e->getMessage(),
            ];

            $stats = [
                'total_supply' => '7000000000',
                'circulating_supply' => '7000000000',
                'current_block' => 0,
                'total_wallets' => CryptoWallet::where('currency_id', $tpixCurrency->id)->count(),
                'active_wallets' => CryptoWallet::where('currency_id', $tpixCurrency->id)
                    ->where('status', 'active')->count(),
                'total_balance' => CryptoWallet::where('currency_id', $tpixCurrency->id)->sum('balance'),
                'transactions_24h' => CryptoTransaction::where('currency_id', $tpixCurrency->id)
                    ->where('created_at', '>=', now()->subHours(24))->count(),
                'volume_24h' => CryptoTransaction::where('currency_id', $tpixCurrency->id)
                    ->where('created_at', '>=', now()->subHours(24))
                    ->where('status', 'confirmed')->sum('amount'),
                'deposits_pending' => CryptoTransaction::where('currency_id', $tpixCurrency->id)
                    ->where('type', 'deposit')->where('status', 'pending')->count(),
                'withdrawals_pending' => CryptoTransaction::where('currency_id', $tpixCurrency->id)
                    ->where('type', 'withdrawal')->where('status', 'pending')->count(),
            ];

            $recentTransactions = CryptoTransaction::with(['user', 'cryptoWallet'])
                ->where('currency_id', $tpixCurrency->id)
                ->orderBy('created_at', 'desc')->limit(20)->get();

            $topWallets = CryptoWallet::with('user')
                ->where('currency_id', $tpixCurrency->id)
                ->orderBy('balance', 'desc')->limit(10)->get();

            $volumeData = $this->getDailyVolumeData($tpixCurrency->id, 30);

            return view('admin.tpix.dashboard', compact(
                'tpixCurrency',
                'networkInfo',
                'stats',
                'recentTransactions',
                'topWallets',
                'volumeData'
            ))->with('warning', 'Cannot connect to TPIX blockchain. Showing database data only.');
        }
    }

    /**
     * Network Status
     */
    public function networkStatus()
    {
        try {
            $networkInfo = $this->tpixService->getNetworkInfo();
            $blockNumber = $this->tpixService->getBlockNumber();
            $gasPrice = $this->tpixService->getGasPrice();

            $status = [
                'network_name' => $networkInfo['name'],
                'chain_id' => $networkInfo['chainId'],
                'block_number' => $blockNumber,
                'gas_price' => hexdec($gasPrice),
                'gas_price_gwei' => hexdec($gasPrice) / 1000000000,
                'total_supply' => $networkInfo['totalSupply'],
                'block_time' => $networkInfo['features']['blockTime'] ?? 2,
                'rpc_url' => $networkInfo['rpcUrl'],
                'explorer_url' => $networkInfo['explorerUrl'],
            ];

            return view('admin.tpix.network-status', compact('status', 'networkInfo'));
        } catch (Exception $e) {
            Log::error('TPIX network status error', ['error' => $e->getMessage()]);

            // แสดง view พร้อม error message แทน redirect
            $status = [
                'network_name' => 'TPIX Blockchain',
                'chain_id' => 'N/A',
                'block_number' => 0,
                'gas_price' => 0,
                'gas_price_gwei' => 0,
                'total_supply' => 'N/A',
                'block_time' => 2,
                'rpc_url' => config('tpix.rpc_url', 'N/A'),
                'explorer_url' => config('tpix.explorer_url', 'N/A'),
            ];

            $networkInfo = [
                'error' => true,
                'message' => 'Failed to connect to TPIX blockchain: ' . $e->getMessage(),
            ];

            return view('admin.tpix.network-status', compact('status', 'networkInfo'))
                ->with('error', 'Cannot connect to TPIX blockchain. Please check RPC configuration.');
        }
    }

    /**
     * Wallets Management
     */
    public function wallets(Request $request)
    {
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (!$tpixCurrency) {
            return redirect()->route('admin.crypto.dashboard')
                ->with('error', 'TPIX currency not found.');
        }

        $query = CryptoWallet::with('user')
            ->where('currency_id', $tpixCurrency->id);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort
        $sort = $request->get('sort', 'created_at');
        $order = $request->get('order', 'desc');
        $query->orderBy($sort, $order);

        $wallets = $query->paginate(50);

        return view('admin.tpix.wallets', compact('wallets', 'tpixCurrency'));
    }

    /**
     * Transactions Management
     */
    public function transactions(Request $request)
    {
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->first();

        if (!$tpixCurrency) {
            return redirect()->route('admin.crypto.dashboard')
                ->with('error', 'TPIX currency not found.');
        }

        $query = CryptoTransaction::with(['user', 'cryptoWallet'])
            ->where('crypto_currency_id', $tpixCurrency->id);

        // Type filter
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tx_hash', 'like', "%{$search}%")
                  ->orWhere('from_address', 'like', "%{$search}%")
                  ->orWhere('to_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $query->orderBy('created_at', 'desc');

        $transactions = $query->paginate(50);

        // Stats
        $stats = [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'confirmed_count' => CryptoTransaction::where('crypto_currency_id', $tpixCurrency->id)
                ->where('status', 'confirmed')->count(),
            'pending_count' => CryptoTransaction::where('crypto_currency_id', $tpixCurrency->id)
                ->where('status', 'pending')->count(),
        ];

        return view('admin.tpix.transactions', compact('transactions', 'tpixCurrency', 'stats'));
    }

    /**
     * Transaction Details
     */
    public function transactionDetails($id)
    {
        $transaction = CryptoTransaction::with(['user', 'cryptoWallet', 'currency'])
            ->findOrFail($id);

        // Get blockchain transaction data if available
        $blockchainData = null;
        if ($transaction->tx_hash) {
            try {
                $blockchainData = $this->tpixService->getTransaction($transaction->tx_hash);
            } catch (Exception $e) {
                Log::warning('Failed to fetch blockchain data for transaction', [
                    'tx_id' => $id,
                    'tx_hash' => $transaction->tx_hash,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('admin.tpix.transaction-details', compact('transaction', 'blockchainData'));
    }

    /**
     * Settings
     */
    public function settings()
    {
        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->firstOrFail();

        return view('admin.tpix.settings', compact('tpixCurrency'));
    }

    /**
     * Update Settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'manual_price_thb' => 'required|numeric|min:0',
            'min_deposit' => 'required|numeric|min:0',
            'min_withdrawal' => 'required|numeric|min:0',
            'max_withdrawal' => 'nullable|numeric|min:0',
            'withdrawal_fee' => 'required|numeric|min:0',
            'exchange_fee_percentage' => 'required|numeric|min:0|max:100',
            'confirmations_required' => 'required|integer|min:1|max:100',
            'deposit_enabled' => 'boolean',
            'withdrawal_enabled' => 'boolean',
            'exchange_enabled' => 'boolean',
            'payment_gateway_enabled' => 'boolean',
        ]);

        $tpixCurrency = CryptoCurrency::where('code', 'TPIX')->firstOrFail();

        $tpixCurrency->update([
            'manual_price_thb' => $request->manual_price_thb,
            'min_deposit' => $request->min_deposit,
            'min_withdrawal' => $request->min_withdrawal,
            'max_withdrawal' => $request->max_withdrawal,
            'withdrawal_fee' => $request->withdrawal_fee,
            'exchange_fee_percentage' => $request->exchange_fee_percentage,
            'confirmations_required' => $request->confirmations_required,
            'deposit_enabled' => $request->boolean('deposit_enabled'),
            'withdrawal_enabled' => $request->boolean('withdrawal_enabled'),
            'exchange_enabled' => $request->boolean('exchange_enabled'),
            'payment_gateway_enabled' => $request->boolean('payment_gateway_enabled'),
        ]);

        return redirect()->route('admin.tpix.settings')
            ->with('success', 'TPIX settings updated successfully!');
    }

    /**
     * Get daily volume data
     */
    protected function getDailyVolumeData($currencyId, $days = 30)
    {
        $data = CryptoTransaction::where('crypto_currency_id', $currencyId)
            ->where('status', 'confirmed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as volume'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return $data;
    }

    /**
     * Check blockchain connection
     */
    public function checkConnection()
    {
        try {
            $blockNumber = $this->tpixService->getBlockNumber();

            return response()->json([
                'success' => true,
                'message' => 'Connected to TPIX blockchain',
                'block_number' => $blockNumber
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to TPIX blockchain',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
