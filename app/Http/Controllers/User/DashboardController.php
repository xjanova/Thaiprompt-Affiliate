<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * Display user dashboard
     */
    public function index()
    {
        $user = Auth::user();

        // Get user's affiliate if exists
        $affiliate = $user->affiliate;

        // Get user's commissions
        $commissions = $user->commissions()
            ->latest()
            ->limit(10)
            ->get();

        // Calculate statistics
        $totalEarnings = $user->commissions()
            ->where('status', 'approved')
            ->sum('amount');

        $pendingEarnings = $user->commissions()
            ->where('status', 'pending')
            ->sum('amount');

        $paidEarnings = $user->commissions()
            ->where('status', 'paid')
            ->sum('amount');

        $totalReferrals = $affiliate ? $affiliate->children()->count() : 0;
        $activeReferrals = $affiliate ? $affiliate->children()->where('status', 'active')->count() : 0;

        // Monthly revenue for the last 12 months
        $monthlyRevenue = collect();
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $total = $user->commissions()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereIn('status', ['approved', 'paid'])
                ->sum('amount');

            $monthlyRevenue->push([
                'month' => $date->format('M Y'),
                'total' => $total
            ]);
        }

        // Calculate growth percentages
        $currentMonthEarnings = $user->commissions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $lastMonthEarnings = $user->commissions()
            ->whereYear('created_at', now()->subMonth()->year)
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $earningsGrowth = $lastMonthEarnings > 0
            ? (($currentMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100
            : 0;

        // Commission status breakdown
        $commissionStatus = [
            'pending' => $user->commissions()->where('status', 'pending')->count(),
            'approved' => $user->commissions()->where('status', 'approved')->count(),
            'paid' => $user->commissions()->where('status', 'paid')->count(),
            'rejected' => $user->commissions()->where('status', 'rejected')->count(),
        ];

        // Commission by type
        $commissionTypes = $user->commissions()
            ->selectRaw('type, SUM(amount) as total')
            ->whereIn('status', ['approved', 'paid'])
            ->groupBy('type')
            ->get();

        // Daily commissions for the last 30 days
        $dailyCommissions = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = $user->commissions()
                ->whereDate('created_at', $date->toDateString())
                ->count();

            $dailyCommissions->push([
                'date' => $date->format('d/m'),
                'count' => $count
            ]);
        }

        // Referral tree depth (max levels)
        $maxLevel = 0;
        if ($affiliate) {
            $maxLevel = $this->getMaxLevel($affiliate);
        }

        // Top referrers under this user
        $topReferrers = [];
        if ($affiliate) {
            $topReferrers = $affiliate->children()
                ->with('user')
                ->withCount('children')
                ->orderBy('children_count', 'desc')
                ->limit(5)
                ->get();
        }

        // Recent activity (last 10 commissions with details)
        $recentActivity = $user->commissions()
            ->with('affiliate.user')
            ->latest()
            ->limit(10)
            ->get();

        // Total lifetime earnings
        $lifetimeEarnings = $user->commissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        // Average commission amount
        $avgCommission = $user->commissions()
            ->whereIn('status', ['approved', 'paid'])
            ->avg('amount') ?? 0;

        // This month's stats
        $thisMonthCommissions = $user->commissions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // Calculate referral conversion rate
        $totalClicks = $affiliate ? ($affiliate->click_count ?? 0) : 0;
        $conversionRate = $totalClicks > 0 ? ($totalReferrals / $totalClicks) * 100 : 0;

        return view('user.dashboard', compact(
            'user',
            'affiliate',
            'commissions',
            'totalEarnings',
            'pendingEarnings',
            'paidEarnings',
            'totalReferrals',
            'activeReferrals',
            'monthlyRevenue',
            'earningsGrowth',
            'commissionStatus',
            'commissionTypes',
            'dailyCommissions',
            'maxLevel',
            'topReferrers',
            'recentActivity',
            'lifetimeEarnings',
            'avgCommission',
            'thisMonthCommissions',
            'conversionRate'
        ));
    }

    /**
     * Get maximum level depth in referral tree
     */
    private function getMaxLevel($affiliate, $level = 1)
    {
        $children = $affiliate->children;

        if ($children->isEmpty()) {
            return $level;
        }

        $maxChildLevel = $level;
        foreach ($children as $child) {
            $childLevel = $this->getMaxLevel($child, $level + 1);
            $maxChildLevel = max($maxChildLevel, $childLevel);
        }

        return $maxChildLevel;
    }

    /**
     * Display user profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:2'],
            'shipping_phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
        ];

        $validated = $request->validate($rules);

        // Don't update phone if it hasn't changed, or if it changed, require verification
        if (isset($validated['phone']) && $validated['phone'] !== $user->phone) {
            // Phone changed - reset verification status
            $validated['phone_verified'] = false;
            $validated['phone_verified_at'] = null;
        }

        $user->update($validated);

        return redirect()->route('user.profile')
            ->with('success', 'อัปเดตโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * Display user commissions
     */
    public function commissions()
    {
        $user = Auth::user();
        $commissions = $user->commissions()->paginate(20);

        return view('user.commissions', compact('commissions'));
    }

    /**
     * Display user referrals
     */
    public function referrals()
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;
        $referrals = $affiliate ? $affiliate->children : collect();

        return view('user.referrals', compact('referrals', 'affiliate'));
    }

    /**
     * Display user organization chart (downline only)
     */
    public function organizationChart()
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่มีสิทธิ์ในระบบแอฟฟิลิเอท');
        }

        // Load children recursively
        $affiliate->load(['children' => function ($query) {
            $query->with(['user', 'children'])->orderBy('created_at', 'desc');
        }]);

        // Calculate total network size
        $totalNetwork = $this->countTotalNetwork($affiliate);

        // Calculate total network earnings
        $totalNetworkEarnings = $this->sumNetworkEarnings($affiliate);

        // Get depth of organization
        $maxDepth = $this->getMaxLevel($affiliate);

        // Get commission depth setting (how many levels user can see)
        $commissionDepth = (int) \App\Models\Setting::get('commission_depth', 10);

        return view('user.organization-new', compact(
            'affiliate',
            'totalNetwork',
            'totalNetworkEarnings',
            'maxDepth',
            'commissionDepth'
        ));
    }

    /**
     * Count total network size (all downlines)
     */
    private function countTotalNetwork($affiliate)
    {
        $count = $affiliate->children->count();

        foreach ($affiliate->children as $child) {
            $count += $this->countTotalNetwork($child);
        }

        return $count;
    }

    /**
     * Sum total network earnings (all downlines)
     */
    private function sumNetworkEarnings($affiliate)
    {
        $sum = $affiliate->total_earnings;

        foreach ($affiliate->children as $child) {
            $sum += $this->sumNetworkEarnings($child);
        }

        return $sum;
    }

    /**
     * Get organization tree data via AJAX (for web session)
     */
    public function getOrganizationTreeData()
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return response()->json([
                'success' => false,
                'message' => 'คุณยังไม่มีสิทธิ์ในระบบแอฟฟิลิเอท'
            ], 404);
        }

        // Get max depth from commission settings
        $maxDepth = (int) \App\Models\Setting::get('commission_depth', 10);

        // Build tree data
        $treeData = $this->buildTreeNode($affiliate, 0, $maxDepth);

        return response()->json([
            'success' => true,
            'data' => $treeData,
            'max_depth' => $maxDepth,
            'stats' => [
                'direct_referrals' => $affiliate->children->count(),
                'total_network' => $this->countTotalNetwork($affiliate),
                'total_earnings' => $this->sumNetworkEarnings($affiliate),
                'max_level' => $this->getMaxLevel($affiliate),
            ]
        ]);
    }

    /**
     * Build tree node recursively
     */
    protected function buildTreeNode($affiliate, $currentDepth = 0, $maxDepth = 10)
    {
        // Load necessary relationships
        $affiliate->load(['user', 'user.retentionStatus', 'rank', 'children' => function ($query) {
            $query->with(['user', 'rank'])->orderBy('created_at', 'desc');
        }]);

        $node = [
            'id' => $affiliate->id,
            'name' => $affiliate->user->name,
            'email' => $affiliate->user->email,
            'avatar' => $this->getAvatar($affiliate->user),
            'referral_code' => $affiliate->referral_code,
            'level' => $affiliate->level,
            'depth' => $currentDepth,
            'status' => $affiliate->status,
            'total_earnings' => $affiliate->total_earnings,
            'total_referrals' => $affiliate->total_referrals,
            'direct_children' => $affiliate->children->count(),
            'created_at' => $affiliate->created_at->format('d/m/Y'),
            'rank' => $affiliate->rank ? [
                'name' => $affiliate->rank->display_name,
                'color' => $affiliate->rank->color,
                'level' => $affiliate->rank->level,
            ] : null,
        ];

        // Add retention status if available
        if ($affiliate->user->retentionStatus) {
            $retention = $affiliate->user->retentionStatus;
            $node['retention'] = [
                'status' => $retention->status,
                'days_remaining' => $retention->daysRemaining(),
                'current_points' => $retention->current_points,
                'required_points' => $retention->required_points,
                'points_percentage' => $retention->getPointsPercentage(),
                'health_color' => $retention->getHealthColor(),
                'next_renewal_date' => $retention->next_renewal_date ? $retention->next_renewal_date->format('d/m/Y') : null,
            ];
        }

        // Always initialize children array
        $node['children'] = [];

        // Recursively add children if not at max depth
        if ($currentDepth < $maxDepth && $affiliate->children->count() > 0) {
            foreach ($affiliate->children as $child) {
                $node['children'][] = $this->buildTreeNode($child, $currentDepth + 1, $maxDepth);
            }
        }

        // Store collapsed children count if there are hidden children
        if ($affiliate->children->count() > 0 && $currentDepth >= $maxDepth) {
            $node['_children'] = $affiliate->children->count();
        }

        return $node;
    }

    /**
     * Get user avatar (first letter or profile picture)
     */
    protected function getAvatar($user)
    {
        if ($user->profile_picture) {
            return [
                'type' => 'image',
                'url' => asset($user->profile_picture)
            ];
        }

        return [
            'type' => 'text',
            'text' => strtoupper(substr($user->name, 0, 1))
        ];
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ], [
            'current_password.required' => 'กรุณากรอกรหัสผ่านปัจจุบัน',
            'new_password.required' => 'กรุณากรอกรหัสผ่านใหม่',
            'new_password.min' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร',
            'new_password.confirmed' => 'รหัสผ่านไม่ตรงกัน',
        ]);

        $user = Auth::user();

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
        }

        // Check if new password is same as current password
        if (Hash::check($request->new_password, $user->password)) {
            return back()->with('error', 'รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม');
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
    }

    /**
     * Display user binary organization chart
     */
    public function binaryOrganizationChart()
    {
        $user = Auth::user();
        $affiliate = $user->affiliate;

        if (!$affiliate) {
            return redirect()->route('user.dashboard')
                ->with('error', 'คุณยังไม่มีสิทธิ์ในระบบแอฟฟิลิเอท');
        }

        // Load binary children recursively
        $affiliate->load(['binaryLeftChild', 'binaryRightChild']);

        // Get max binary depth
        $maxBinaryDepth = $this->getMaxBinaryLevel($affiliate);

        return view('user.organization-binary', compact(
            'affiliate',
            'maxBinaryDepth'
        ));
    }

    /**
     * Get maximum binary level depth
     */
    private function getMaxBinaryLevel($affiliate, $level = 1)
    {
        $leftChild = $affiliate->binaryLeftChild;
        $rightChild = $affiliate->binaryRightChild;

        if (!$leftChild && !$rightChild) {
            return $level;
        }

        $maxLevel = $level;

        if ($leftChild) {
            $leftLevel = $this->getMaxBinaryLevel($leftChild, $level + 1);
            $maxLevel = max($maxLevel, $leftLevel);
        }

        if ($rightChild) {
            $rightLevel = $this->getMaxBinaryLevel($rightChild, $level + 1);
            $maxLevel = max($maxLevel, $rightLevel);
        }

        return $maxLevel;
    }
}
