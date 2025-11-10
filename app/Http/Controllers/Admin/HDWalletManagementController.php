<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoWallet;
use App\Models\User;
use App\Services\Crypto\HDWalletService;
use App\Services\Crypto\CryptoWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HDWalletManagementController extends Controller
{
    protected HDWalletService $hdWalletService;
    protected CryptoWalletService $cryptoWalletService;

    public function __construct(
        HDWalletService $hdWalletService,
        CryptoWalletService $cryptoWalletService
    ) {
        $this->hdWalletService = $hdWalletService;
        $this->cryptoWalletService = $cryptoWalletService;
    }

    /**
     * Display overview of all HD Wallets in the system
     */
    public function index(Request $request)
    {
        $query = CryptoWallet::with(['user', 'childWallets', 'masterWallet'])
            ->withCount('childWallets');

        // Filter by wallet type
        if ($request->filled('type')) {
            if ($request->type === 'master') {
                $query->where('is_master_wallet', true);
            } elseif ($request->type === 'child') {
                $query->where('is_master_wallet', false)
                    ->whereNotNull('master_wallet_id');
            }
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($uq) use ($request) {
                      $uq->where('name', 'like', "%{$request->search}%")
                         ->orWhere('email', 'like', "%{$request->search}%");
                  });
            });
        }

        $wallets = $query->latest()->paginate(20);

        // Get statistics
        $stats = $this->getSystemStatistics();

        return view('admin.crypto.hd-wallets.index', compact('wallets', 'stats'));
    }

    /**
     * Show details of a specific wallet
     */
    public function show($id)
    {
        $wallet = CryptoWallet::with([
            'user',
            'masterWallet',
            'childWallets.cryptoAddresses.currency',
            'cryptoAddresses.currency',
            'cryptoTransactions',
        ])->findOrFail($id);

        // Get statistics for this wallet
        if ($wallet->is_master_wallet) {
            $statistics = $this->hdWalletService->getMasterWalletStatistics($wallet);
        } else {
            $statistics = $this->cryptoWalletService->getWalletStatistics($wallet);
        }

        return view('admin.crypto.hd-wallets.show', compact('wallet', 'statistics'));
    }

    /**
     * Show all master wallets
     */
    public function masterWallets(Request $request)
    {
        $query = CryptoWallet::masterWallets()
            ->with(['user'])
            ->withCount('childWallets');

        // Search
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($uq) use ($request) {
                      $uq->where('name', 'like', "%{$request->search}%")
                         ->orWhere('email', 'like', "%{$request->search}%");
                  });
            });
        }

        $masterWallets = $query->latest()->paginate(20);

        return view('admin.crypto.hd-wallets.master-wallets', compact('masterWallets'));
    }

    /**
     * Show child wallets for a specific master wallet
     */
    public function childWallets($masterWalletId)
    {
        $masterWallet = CryptoWallet::masterWallets()->findOrFail($masterWalletId);

        $childWallets = $this->hdWalletService->getChildWallets($masterWallet);

        $statistics = $this->hdWalletService->getMasterWalletStatistics($masterWallet);

        return view('admin.crypto.hd-wallets.child-wallets', compact(
            'masterWallet',
            'childWallets',
            'statistics'
        ));
    }

    /**
     * Show wallets for a specific user
     */
    public function userWallets($userId)
    {
        $user = User::findOrFail($userId);

        $masterWallet = $this->cryptoWalletService->getMasterWallet($user);
        $childWallets = $this->cryptoWalletService->getAllChildWallets($user);

        $statistics = $masterWallet
            ? $this->hdWalletService->getMasterWalletStatistics($masterWallet)
            : null;

        return view('admin.crypto.hd-wallets.user-wallets', compact(
            'user',
            'masterWallet',
            'childWallets',
            'statistics'
        ));
    }

    /**
     * Get system-wide wallet statistics
     */
    private function getSystemStatistics(): array
    {
        return [
            'total_master_wallets' => CryptoWallet::masterWallets()->count(),
            'total_child_wallets' => CryptoWallet::childWallets()->count(),
            'total_users_with_wallets' => CryptoWallet::distinct('user_id')->count('user_id'),
            'active_wallets' => CryptoWallet::active()->count(),
            'locked_wallets' => CryptoWallet::where('status', 'locked')->count(),
            'suspended_wallets' => CryptoWallet::where('status', 'suspended')->count(),
            'total_balance_thb' => $this->getTotalSystemBalance(),
            'avg_child_wallets_per_master' => CryptoWallet::masterWallets()
                ->avg('total_derived_wallets'),
        ];
    }

    /**
     * Calculate total balance across all wallets in the system
     */
    private function getTotalSystemBalance(): float
    {
        $totalBalance = 0;

        CryptoWallet::childWallets()
            ->chunk(100, function ($wallets) use (&$totalBalance) {
                foreach ($wallets as $wallet) {
                    $totalBalance += $wallet->getTotalBalanceInTHB();
                }
            });

        return $totalBalance;
    }

    /**
     * Lock a wallet (admin action)
     */
    public function lockWallet(Request $request, $id)
    {
        $wallet = CryptoWallet::findOrFail($id);

        $minutes = $request->input('minutes', 30);
        $reason = $request->input('reason', 'Locked by admin');

        $wallet->lock($minutes);

        // Log admin action
        activity()
            ->performedOn($wallet)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason, 'minutes' => $minutes])
            ->log('Admin locked wallet');

        return back()->with('success', 'กระเป๋าถูกล็อกเรียบร้อยแล้ว');
    }

    /**
     * Unlock a wallet (admin action)
     */
    public function unlockWallet($id)
    {
        $wallet = CryptoWallet::findOrFail($id);

        $wallet->unlock();

        // Log admin action
        activity()
            ->performedOn($wallet)
            ->causedBy(auth()->user())
            ->log('Admin unlocked wallet');

        return back()->with('success', 'กระเป๋าถูกปลดล็อกเรียบร้อยแล้ว');
    }

    /**
     * Suspend a wallet (admin action)
     */
    public function suspendWallet(Request $request, $id)
    {
        $wallet = CryptoWallet::findOrFail($id);

        $reason = $request->input('reason', 'Suspended by admin');

        $wallet->update(['status' => 'suspended']);

        // Log admin action
        activity()
            ->performedOn($wallet)
            ->causedBy(auth()->user())
            ->withProperties(['reason' => $reason])
            ->log('Admin suspended wallet');

        return back()->with('success', 'กระเป๋าถูกระงับเรียบร้อยแล้ว');
    }

    /**
     * Reactivate a suspended wallet (admin action)
     */
    public function reactivateWallet($id)
    {
        $wallet = CryptoWallet::findOrFail($id);

        $wallet->update(['status' => 'active']);

        // Log admin action
        activity()
            ->performedOn($wallet)
            ->causedBy(auth()->user())
            ->log('Admin reactivated wallet');

        return back()->with('success', 'กระเป๋าถูกเปิดใช้งานเรียบร้อยแล้ว');
    }

    /**
     * Export wallet data (for auditing)
     */
    public function export(Request $request)
    {
        $query = CryptoWallet::with(['user', 'masterWallet']);

        if ($request->filled('type')) {
            if ($request->type === 'master') {
                $query->masterWallets();
            } elseif ($request->type === 'child') {
                $query->childWallets();
            }
        }

        $wallets = $query->get();

        $data = $wallets->map(function ($wallet) {
            return [
                'ID' => $wallet->id,
                'Type' => $wallet->is_master_wallet ? 'Master' : 'Child',
                'User' => $wallet->user->name,
                'Email' => $wallet->user->email,
                'Name' => $wallet->name,
                'Status' => $wallet->status,
                'Derivation Index' => $wallet->derivation_index,
                'Total Child Wallets' => $wallet->total_derived_wallets,
                'Balance (THB)' => $wallet->getTotalBalanceInTHB(),
                'Created At' => $wallet->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $filename = 'hd-wallets-export-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, array_keys($data->first()));

            // Add data
            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
