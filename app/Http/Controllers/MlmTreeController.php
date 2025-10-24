<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\MlmNetwork;
use App\Services\MLM\MlmService;
use Illuminate\Http\Request;

class MlmTreeController extends Controller
{
    protected MlmService $mlmService;

    public function __construct(MlmService $mlmService)
    {
        $this->mlmService = $mlmService;
        $this->middleware('auth');
    }

    /**
     * Show MLM tree for current user
     */
    public function index()
    {
        $user = auth()->user();

        return view('mlm.tree.index', compact('user'));
    }

    /**
     * Show MLM tree for specific user (Admin only)
     */
    public function show(User $user)
    {
        // Check if admin or viewing own tree
        if (!auth()->user()->isAdmin() && auth()->id() !== $user->id) {
            abort(403, 'Unauthorized');
        }

        return view('mlm.tree.show', compact('user'));
    }

    /**
     * Get tree data as JSON
     */
    public function getTreeData(Request $request, ?int $userId = null)
    {
        $targetUserId = $userId ?? auth()->id();
        $depth = $request->get('depth', 5);

        // Check permissions
        if (!auth()->user()->isAdmin() && auth()->id() !== $targetUserId) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($targetUserId);
        $treeData = $this->buildTreeData($user, $depth);

        return response()->json($treeData);
    }

    /**
     * Get user node details
     */
    public function getNodeDetails(User $user)
    {
        // Check permissions
        if (!auth()->user()->isAdmin() && !$this->isInDownline(auth()->user(), $user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user->load(['wallet', 'currentRank.rank', 'sponsor']);

        // Calculate sales volume
        $totalSales = $user->orders()->where('status', 'completed')->sum('total_amount');
        $monthSales = $user->orders()
            ->where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        // Get commission data
        $totalCommissions = $user->commissions()
            ->where('status', 'approved')
            ->sum('amount');

        // Check if maintaining minimum sales
        $minimumSales = 1000; // This should come from config or settings
        $maintainingSales = $monthSales >= $minimumSales;

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $this->getUserAvatar($user),
            'phone' => $user->phone,
            'referral_code' => $user->referral_code,
            'mlm_level' => $user->mlm_level,
            'rank' => $user->currentRank?->rank?->name ?? 'Member',
            'rank_color' => $user->currentRank?->rank?->color ?? '#6B7280',
            'sponsor' => $user->sponsor ? [
                'id' => $user->sponsor->id,
                'name' => $user->sponsor->name,
            ] : null,
            'direct_referrals' => $user->getDirectReferralsCount(),
            'total_downline' => $user->getTotalDownlineCount(),
            'wallet_balance' => $user->wallet?->balance ?? 0,
            'total_sales' => $totalSales,
            'month_sales' => $monthSales,
            'total_commissions' => $totalCommissions,
            'maintaining_sales' => $maintainingSales,
            'is_kyc_verified' => $user->is_kyc_verified,
            'joined_at' => $user->created_at->format('Y-m-d'),
        ]);
    }

    /**
     * Add new member to downline
     */
    public function addMember(Request $request)
    {
        $request->validate([
            'sponsor_id' => 'required|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        // Check permissions
        $sponsor = User::findOrFail($request->sponsor_id);
        if (!auth()->user()->isAdmin() && auth()->id() !== $sponsor->id && !$this->isInDownline(auth()->user(), $sponsor)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            // Create new user
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => bcrypt($request->password),
                'sponsor_id' => $request->sponsor_id,
                'referral_code' => User::generateReferralCode(),
            ]);

            // Assign role
            $newUser->assignRole('customer');

            // Create wallet
            $newUser->wallet()->create([
                'balance' => 0,
                'pending_balance' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Member added successfully',
                'user' => $newUser,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Build tree data recursively
     */
    private function buildTreeData(User $user, int $depth = 5, int $currentDepth = 0): array
    {
        if ($currentDepth >= $depth) {
            return [];
        }

        // Get direct referrals
        $referrals = $user->referrals()
            ->with(['wallet', 'currentRank.rank'])
            ->get();

        $children = [];
        foreach ($referrals as $referral) {
            $monthSales = $referral->orders()
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount');

            $minimumSales = 1000;
            $maintainingSales = $monthSales >= $minimumSales;

            $children[] = [
                'id' => $referral->id,
                'name' => $referral->name,
                'avatar' => $this->getUserAvatar($referral),
                'level' => $referral->mlm_level,
                'rank' => $referral->currentRank?->rank?->name ?? 'Member',
                'rank_color' => $referral->currentRank?->rank?->color ?? '#6B7280',
                'direct_referrals' => $referral->getDirectReferralsCount(),
                'wallet_balance' => $referral->wallet?->balance ?? 0,
                'maintaining_sales' => $maintainingSales,
                'is_kyc_verified' => $referral->is_kyc_verified,
                'children' => $this->buildTreeData($referral, $depth, $currentDepth + 1),
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $this->getUserAvatar($user),
            'level' => $user->mlm_level,
            'rank' => $user->currentRank?->rank?->name ?? 'Member',
            'rank_color' => $user->currentRank?->rank?->color ?? '#6B7280',
            'direct_referrals' => $user->getDirectReferralsCount(),
            'wallet_balance' => $user->wallet?->balance ?? 0,
            'children' => $children,
        ];
    }

    /**
     * Get user avatar based on settings
     */
    private function getUserAvatar(User $user): string
    {
        // If user has uploaded custom avatar
        if ($user->avatar && $user->profile_image_source === 'upload') {
            return asset('storage/' . $user->avatar);
        }

        // If user is KYC verified and using LINE avatar
        if ($user->is_kyc_verified && $user->use_line_avatar && $user->avatar) {
            return $user->avatar; // LINE avatar URL
        }

        // Default avatar
        return asset('images/default-avatar.png');
    }

    /**
     * Check if user is in downline
     */
    private function isInDownline(User $ancestor, User $descendant): bool
    {
        $currentSponsor = $descendant->sponsor;

        while ($currentSponsor) {
            if ($currentSponsor->id === $ancestor->id) {
                return true;
            }
            $currentSponsor = $currentSponsor->sponsor;
        }

        return false;
    }
}
