<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class DashboardController extends Controller
{
    /**
     * แสดงหน้า Dashboard ของ User
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงข้อมูล affiliate ของ user
        $affiliate = $user->affiliate;

        // ดึงข้อมูล commissions ล่าสุด
        $commissions = $user->commissions()
            ->latest()
            ->limit(10)
            ->get();

        // คำนวณสถิติต่างๆ
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

        // รายได้รายเดือนย้อนหลัง 12 เดือน
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

        // คำนวณอัตราการเติบโต
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

        // สถานะ commission แยกตามประเภท
        $commissionStatus = [
            'pending' => $user->commissions()->where('status', 'pending')->count(),
            'approved' => $user->commissions()->where('status', 'approved')->count(),
            'paid' => $user->commissions()->where('status', 'paid')->count(),
            'rejected' => $user->commissions()->where('status', 'rejected')->count(),
        ];

        // Commission แยกตามประเภท
        $commissionTypes = $user->commissions()
            ->selectRaw('type, SUM(amount) as total')
            ->whereIn('status', ['approved', 'paid'])
            ->groupBy('type')
            ->get();

        // Commission รายวันย้อนหลัง 30 วัน
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

        // ความลึกของโครงสร้าง referral (จำนวนระดับสูงสุด)
        $maxLevel = 0;
        if ($affiliate) {
            $maxLevel = $this->getMaxLevel($affiliate);
        }

        // Top referrers ใน downline
        $topReferrers = [];
        if ($affiliate) {
            $topReferrers = $affiliate->children()
                ->with('user')
                ->withCount('children')
                ->orderBy('children_count', 'desc')
                ->limit(5)
                ->get();
        }

        // กิจกรรมล่าสุด (10 รายการล่าสุด)
        $recentActivity = $user->commissions()
            ->with('affiliate.user')
            ->latest()
            ->limit(10)
            ->get();

        // รายได้ตลอดอายุการใช้งาน
        $lifetimeEarnings = $user->commissions()
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        // ค่าเฉลี่ย commission ต่อรายการ
        $avgCommission = $user->commissions()
            ->whereIn('status', ['approved', 'paid'])
            ->avg('amount') ?? 0;

        // สถิติเดือนนี้
        $thisMonthCommissions = $user->commissions()
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        // อัตราการแปลง referral
        $totalClicks = $affiliate ? ($affiliate->click_count ?? 0) : 0;
        $conversionRate = $totalClicks > 0 ? ($totalReferrals / $totalClicks) * 100 : 0;

        // สถิติสำหรับ Arrow X Dashboard
        $stats = [
            'wallet_balance' => $user->wallet_balance ?? 0,
            'wallet_change' => $earningsGrowth,
            'total_earnings' => $lifetimeEarnings,
            'earnings_change' => $earningsGrowth,
            'team_members' => $totalReferrals,
            'team_change' => 0, // TODO: คำนวณอัตราการเติบโตของทีม
            'pending_commission' => $pendingEarnings,
            'commission_change' => 0, // TODO: คำนวณอัตราการเปลี่ยนแปลงของ commission
        ];

        // ข้อมูลสำหรับ Chart.js
        $chartData = [
            'labels' => $monthlyRevenue->pluck('month')->toArray(),
            'data' => $monthlyRevenue->pluck('total')->toArray(),
        ];

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
            'conversionRate',
            'stats',
            'chartData'
        ));
    }

    /**
     * แสดงหน้าโปรไฟล์ของ User
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    /**
     * อัปเดตโปรไฟล์ของ User
     *
     * @param Request $request
     * @param ImageUploadService $imageUploadService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request, ImageUploadService $imageUploadService)
    {
        $user = Auth::user();

        $rules = [
            // ข้อมูลส่วนตัว
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female,other,prefer_not_to_say'],

            // ที่อยู่หลัก (Billing Address)
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:2'],

            // ที่อยู่จัดส่ง (Shipping Address) - NEW
            'shipping_address' => ['nullable', 'string', 'max:500'],
            'shipping_city' => ['nullable', 'string', 'max:100'],
            'shipping_state' => ['nullable', 'string', 'max:100'],
            'shipping_postal_code' => ['nullable', 'string', 'max:20'],
            'shipping_country' => ['nullable', 'string', 'max:2'],
            'shipping_phone' => ['nullable', 'string', 'regex:/^(\+66|66|0)[0-9]{9}$/'],

            // รูปโปรไฟล์ (จะแปลงเป็น WebP อัตโนมัติ)
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:5120'], // 5MB max

            // รหัสผ่าน (ถ้ามีการเปลี่ยน)
            'current_password' => ['nullable', 'string'],
            'new_password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ];

        $validated = $request->validate($rules);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                $imageUploadService->deleteImage($user->profile_picture);
            }

            // Upload new profile picture with WebP conversion
            // Use 'avatars' directory, max 800x800 for avatar, quality 90
            $validated['profile_picture'] = $imageUploadService->uploadImage(
                $request->file('profile_picture'),
                'avatars',
                800,
                800,
                90
            );
        }

        // Don't update phone if it hasn't changed, or if it changed, require verification
        if (isset($validated['phone']) && $validated['phone'] !== $user->phone) {
            // Phone changed - reset verification status
            $validated['phone_verified'] = false;
            $validated['phone_verified_at'] = null;
        }

        // Handle password change
        if ($request->filled('current_password') && $request->filled('new_password')) {
            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return back()
                    ->withInput()
                    ->with('error', 'รหัสผ่านปัจจุบันไม่ถูกต้อง');
            }

            // Check if new password is same as current password
            if (Hash::check($request->new_password, $user->password)) {
                return back()
                    ->withInput()
                    ->with('error', 'รหัสผ่านใหม่ต้องไม่เหมือนกับรหัสผ่านเดิม');
            }

            // Update password
            $validated['password'] = Hash::make($request->new_password);
        }

        // Remove password fields from validated array if not changing password
        unset($validated['current_password'], $validated['new_password']);

        $user->update($validated);

        return redirect()->route('user.profile')
            ->with('success', 'อัปเดตโปรไฟล์เรียบร้อยแล้ว');
    }

    /**
     * แสดงรายการ Commissions ของ User
     *
     * @return \Illuminate\View\View
     */
    public function commissions()
    {
        $user = Auth::user();
        $commissions = $user->commissions()->paginate(20);

        return view('user.commissions', compact('commissions'));
    }

    /**
     * อัปเดตรหัสผ่านของ User
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
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
     * คำนวณความลึกสูงสุดของโครงสร้าง affiliate (Recursive)
     *
     * @param mixed $affiliate Affiliate object
     * @param int $currentLevel ระดับปัจจุบัน
     * @return int ความลึกสูงสุด
     */
    private function getMaxLevel($affiliate, $currentLevel = 1): int
    {
        // ดึง children ของ affiliate นี้
        $children = $affiliate->children;

        // ถ้าไม่มี children แสดงว่าถึงระดับสุดท้ายแล้ว
        if ($children->isEmpty()) {
            return $currentLevel;
        }

        // คำนวณระดับสูงสุดของแต่ละ child
        $maxChildLevel = $currentLevel;
        foreach ($children as $child) {
            $childLevel = $this->getMaxLevel($child, $currentLevel + 1);
            $maxChildLevel = max($maxChildLevel, $childLevel);
        }

        return $maxChildLevel;
    }
}
