<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TPIXToken;
use App\Models\User;
use App\Services\TPIX\TokenFactoryService;
use App\Services\TPIX\CoinControlService;
use App\Services\TPIX\CMCIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Admin Token Management Controller
 */
class TokenManagementController extends Controller
{
    protected TokenFactoryService $tokenFactory;
    protected CoinControlService $coinControl;
    protected CMCIntegrationService $cmcService;

    public function __construct(
        TokenFactoryService $tokenFactory,
        CoinControlService $coinControl,
        CMCIntegrationService $cmcService
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->coinControl = $coinControl;
        $this->cmcService = $cmcService;
        $this->middleware('auth');
        $this->middleware('admin');
    }

    /**
     * Token Dashboard
     */
    public function index(Request $request)
    {
        $query = TPIXToken::with('creator');

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('verified')) {
            $query->where('is_verified', $request->verified);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%")
                  ->orWhere('contract_address', 'like', "%{$search}%");
            });
        }

        $tokens = $query->orderBy('created_at', 'desc')->paginate(20);

        // Statistics
        $stats = [
            'total_tokens' => TPIXToken::count(),
            'deployed_tokens' => TPIXToken::whereIn('status', ['deployed', 'verified', 'active'])->count(),
            'verified_tokens' => TPIXToken::where('is_verified', true)->count(),
            'listed_tokens' => TPIXToken::where('is_listed', true)->count(),
            'total_holders' => TPIXToken::sum('holders_count'),
            'total_transfers' => TPIXToken::sum('transfers_count'),
        ];

        return view('admin.tokens.index', compact('tokens', 'stats'));
    }

    /**
     * Token Details
     */
    public function show($id)
    {
        $token = TPIXToken::with(['creator', 'balances', 'transfers', 'controlActions'])
            ->findOrFail($id);

        // Get top holders
        $topHolders = $token->balances()
            ->with('user')
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->limit(10)
            ->get();

        // Recent transfers
        $recentTransfers = $token->transfers()
            ->with(['fromUser', 'toUser'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Control actions
        $controlActions = $token->controlActions()
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('admin.tokens.show', compact('token', 'topHolders', 'recentTransfers', 'controlActions'));
    }

    /**
     * Approve/Verify Token
     */
    public function verify($id)
    {
        $token = TPIXToken::findOrFail($id);

        $token->update([
            'is_verified' => true,
            'status' => 'verified',
        ]);

        return redirect()->back()->with('success', 'Token verified successfully!');
    }

    /**
     * Feature Token
     */
    public function feature($id)
    {
        $token = TPIXToken::findOrFail($id);

        $token->update(['is_featured' => !$token->is_featured]);

        $message = $token->is_featured ? 'Token featured!' : 'Token unfeatured!';
        return redirect()->back()->with('success', $message);
    }

    /**
     * Coin Control Page
     */
    public function coinControl($id)
    {
        $token = TPIXToken::findOrFail($id);

        // Get control actions history
        $actions = $token->controlActions()
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.tokens.coin-control', compact('token', 'actions'));
    }

    /**
     * Mint Tokens (Admin Action)
     */
    public function mint(Request $request, $id)
    {
        $request->validate([
            'to_address' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->coinControl->mint(
                $token,
                auth()->user(),
                $request->to_address,
                $request->amount,
                $request->reason
            );

            return redirect()->back()->with('success', 'Tokens minted successfully! TX: ' . $result['tx_hash']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to mint: ' . $e->getMessage());
        }
    }

    /**
     * Burn Tokens (Admin Action)
     */
    public function burn(Request $request, $id)
    {
        $request->validate([
            'from_address' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:500',
        ]);

        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->coinControl->burn(
                $token,
                auth()->user(),
                $request->from_address,
                $request->amount,
                $request->reason
            );

            return redirect()->back()->with('success', 'Tokens burned successfully! TX: ' . $result['tx_hash']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to burn: ' . $e->getMessage());
        }
    }

    /**
     * Freeze Address
     */
    public function freezeAddress(Request $request, $id)
    {
        $request->validate([
            'address' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->coinControl->freezeAddress(
                $token,
                auth()->user(),
                $request->address,
                $request->reason
            );

            return redirect()->back()->with('success', 'Address frozen successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to freeze: ' . $e->getMessage());
        }
    }

    /**
     * Unfreeze Address
     */
    public function unfreezeAddress(Request $request, $id)
    {
        $request->validate([
            'address' => 'required|string',
            'reason' => 'required|string|max:500',
        ]);

        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->coinControl->unfreezeAddress(
                $token,
                auth()->user(),
                $request->address,
                $request->reason
            );

            return redirect()->back()->with('success', 'Address unfrozen successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to unfreeze: ' . $e->getMessage());
        }
    }

    /**
     * Pause Token
     */
    public function pause(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->coinControl->pauseContract(
                $token,
                auth()->user(),
                $request->reason
            );

            return redirect()->back()->with('success', 'Token paused successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to pause: ' . $e->getMessage());
        }
    }

    /**
     * Unpause Token
     */
    public function unpause(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->coinControl->unpauseContract(
                $token,
                auth()->user(),
                $request->reason
            );

            return redirect()->back()->with('success', 'Token unpaused successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to unpause: ' . $e->getMessage());
        }
    }

    /**
     * CMC Integration Page
     */
    public function cmcIntegration()
    {
        $tokens = TPIXToken::whereNotNull('cmc_id')->with('creator')->paginate(20);

        return view('admin.tokens.cmc-integration', compact('tokens'));
    }

    /**
     * Import from CoinMarketCap
     */
    public function importFromCMC(Request $request)
    {
        $request->validate([
            'cmc_id' => 'required|string',
        ]);

        try {
            $listing = $this->cmcService->importToken($request->cmc_id, auth()->user());

            return redirect()->back()->with('success', 'Token imported from CMC: ' . $listing->name . ' (' . $listing->symbol . ')');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to import: ' . $e->getMessage());
        }
    }

    /**
     * Sync Token with CMC
     */
    public function syncWithCMC($id)
    {
        $token = TPIXToken::findOrFail($id);

        if (!$token->cmc_id) {
            return redirect()->back()->with('error', 'Token does not have CMC ID');
        }

        try {
            $syncLog = $this->cmcService->syncTPIXToken($token, auth()->user());

            return redirect()->back()->with('success', 'Token synced with CMC successfully! New price: ' . $token->fresh()->current_price_tpix . ' TPIX');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to sync: ' . $e->getMessage());
        }
    }

    /**
     * Link Token to CMC
     */
    public function linkToCMC(Request $request, $id)
    {
        $request->validate([
            'cmc_id' => 'required|string',
        ]);

        $token = TPIXToken::findOrFail($id);

        $token->update(['cmc_id' => $request->cmc_id]);

        return redirect()->back()->with('success', 'Token linked to CMC ID: ' . $request->cmc_id);
    }

    /**
     * Update Token Settings
     */
    public function updateSettings(Request $request, $id)
    {
        $request->validate([
            'is_verified' => 'boolean',
            'is_listed' => 'boolean',
            'is_featured' => 'boolean',
            'status' => 'in:draft,deploying,deployed,verified,active,paused,suspended',
        ]);

        $token = TPIXToken::findOrFail($id);

        $token->update($request->only(['is_verified', 'is_listed', 'is_featured', 'status']));

        return redirect()->back()->with('success', 'Token settings updated!');
    }
}
