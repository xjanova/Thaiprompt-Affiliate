<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\TPIXToken;
use App\Models\TPIXTokenBalance;
use App\Models\CryptoWallet;
use App\Services\TPIX\TokenFactoryService;
use App\Services\TPIX\ReferralService;
use App\Services\TPIX\TokenWalletIntegrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * User Token Controller
 */
class TokenController extends Controller
{
    protected TokenFactoryService $tokenFactory;
    protected ReferralService $referralService;
    protected TokenWalletIntegrationService $walletIntegration;

    public function __construct(
        TokenFactoryService $tokenFactory,
        ReferralService $referralService,
        TokenWalletIntegrationService $walletIntegration
    ) {
        $this->tokenFactory = $tokenFactory;
        $this->referralService = $referralService;
        $this->walletIntegration = $walletIntegration;
        $this->middleware('auth');
    }

    /**
     * Token Marketplace
     */
    public function index(Request $request)
    {
        $query = TPIXToken::where('status', 'active')
            ->where('is_listed', true);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('symbol', 'like', "%{$search}%");
            });
        }

        if ($request->filled('verified')) {
            $query->where('is_verified', true);
        }

        // Sort
        $sort = $request->get('sort', 'market_cap');
        switch ($sort) {
            case 'price':
                $query->orderBy('current_price_tpix', 'desc');
                break;
            case 'volume':
                $query->orderBy('volume_24h_tpix', 'desc');
                break;
            case 'holders':
                $query->orderBy('holders_count', 'desc');
                break;
            default:
                $query->orderBy('market_cap_tpix', 'desc');
        }

        $tokens = $query->paginate(20);

        // Featured tokens
        $featuredTokens = TPIXToken::where('is_featured', true)
            ->where('status', 'active')
            ->limit(5)
            ->get();

        return view('user.tokens.index', compact('tokens', 'featuredTokens'));
    }

    /**
     * My Tokens (Created by user)
     */
    public function myTokens()
    {
        $user = Auth::user();

        $tokens = TPIXToken::where('creator_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Statistics
        $stats = [
            'total_tokens' => $tokens->total(),
            'deployed_tokens' => TPIXToken::where('creator_id', $user->id)
                ->whereIn('status', ['deployed', 'verified', 'active'])
                ->count(),
            'total_holders' => TPIXToken::where('creator_id', $user->id)
                ->sum('holders_count'),
        ];

        return view('user.tokens.my-tokens', compact('tokens', 'stats'));
    }

    /**
     * My Token Balances (Holdings)
     */
    public function myBalances()
    {
        $user = Auth::user();

        $balances = TPIXTokenBalance::with('token')
            ->where('user_id', $user->id)
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->paginate(20);

        // Calculate total portfolio value in TPIX
        $totalValueTpix = $balances->sum(function ($balance) {
            return bcmul($balance->balance, $balance->token->current_price_tpix ?? '0', 8);
        });

        return view('user.tokens.balances', compact('balances', 'totalValueTpix'));
    }

    /**
     * Token Details
     */
    public function show($id)
    {
        $token = TPIXToken::with('creator')->findOrFail($id);

        $user = Auth::user();

        // User's balance of this token
        $myBalance = TPIXTokenBalance::where('token_id', $token->id)
            ->where('user_id', $user->id)
            ->first();

        // Recent transfers
        $recentTransfers = $token->transfers()
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Top holders
        $topHolders = $token->balances()
            ->where('balance', '>', 0)
            ->orderBy('balance', 'desc')
            ->limit(10)
            ->get();

        return view('user.tokens.show', compact('token', 'myBalance', 'recentTransfers', 'topHolders'));
    }

    /**
     * Create Token Page
     */
    public function create()
    {
        // Estimate deployment cost
        $estimatedCost = $this->tokenFactory->estimateDeploymentCost();

        return view('user.tokens.create', compact('estimatedCost'));
    }

    /**
     * Store New Token
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'symbol' => 'required|string|max:20|alpha_num',
            'total_supply' => 'required|numeric|min:1',
            'decimals' => 'required|integer|min:0|max:18',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url',
            'is_mintable' => 'boolean',
            'is_burnable' => 'boolean',
            'is_pausable' => 'boolean',
        ]);

        try {
            $token = $this->tokenFactory->createToken(Auth::user(), $request->all());

            return redirect()->route('user.tokens.my-tokens')
                ->with('success', 'Token created successfully! You can now deploy it to the blockchain.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create token: ' . $e->getMessage());
        }
    }

    /**
     * Deploy Token to Blockchain
     */
    public function deploy($id)
    {
        $token = TPIXToken::where('id', $id)
            ->where('creator_id', Auth::id())
            ->firstOrFail();

        if ($token->status !== 'draft') {
            return redirect()->back()->with('error', 'Token is not in draft status');
        }

        try {
            $result = $this->tokenFactory->deployToken($token);

            return redirect()->route('user.tokens.show', $token->id)
                ->with('success', 'Token deployed successfully! Contract: ' . $result['contract_address']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Deployment failed: ' . $e->getMessage());
        }
    }

    /**
     * Transfer Token to Another User
     */
    public function transfer(Request $request, $id)
    {
        $request->validate([
            'to_address_or_email' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        $user = Auth::user();
        $token = TPIXToken::findOrFail($id);

        try {
            $result = $this->walletIntegration->transferToken(
                $token,
                $user,
                $request->to_address_or_email,
                $request->amount
            );

            return redirect()->back()->with('success', 'Transfer successful! TX: ' . $result['tx_hash']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }

    /**
     * Use Referral Code
     */
    public function useReferral(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $referralUse = $this->referralService->useReferralCode(
                $request->code,
                Auth::user()
            );

            return redirect()->back()->with('success', 'Referral code used! Please check your email for verification code.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verify Referral
     */
    public function verifyReferral(Request $request)
    {
        $request->validate([
            'referral_use_id' => 'required|integer',
            'verification_code' => 'required|string|size:6',
        ]);

        try {
            $referralUse = \App\Models\TPIXReferralUse::findOrFail($request->referral_use_id);

            $this->referralService->verifyReferralUse($referralUse, $request->verification_code);

            return redirect()->back()->with('success', 'Referral verified! Your rewards will be processed soon.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * My Referrals
     */
    public function myReferrals()
    {
        $user = Auth::user();

        // My referral codes
        $referralCodes = \App\Models\TPIXReferralCode::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Statistics
        $stats = $this->referralService->getUserStats($user);

        return view('user.tokens.referrals', compact('referralCodes', 'stats'));
    }

    /**
     * Create Referral Code
     */
    public function createReferralCode(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
            'token_id' => 'nullable|exists:tpix_tokens,id',
            'reward_percentage' => 'required|numeric|min:0|max:100',
            'referrer_percentage' => 'required|numeric|min:0|max:100',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        try {
            $referralCode = $this->referralService->createReferralCode(Auth::user(), $request->all());

            return redirect()->back()->with('success', 'Referral code created: ' . $referralCode->code);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
