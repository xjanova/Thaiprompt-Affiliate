<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\LeadLock;
use App\Models\MlmMember;
use App\Models\RecruitCustomization;
use App\Models\RecruitPageVisit;
use App\Models\RecruitTemplate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * User Controller สำหรับจัดการหน้า Recruit
 *
 * แม่ทีมสามารถ:
 * - แก้ไขข้อมูลส่วนตัว (ชื่อ, เบอร์, รูป, คำชักชวน)
 * - ดู QR Code และลิงก์แนะนำ
 * - ดูรายการผู้มุ่งหวัง (Leads)
 * - ดูสถิติการตลาด
 */
class RecruitPageController extends Controller
{
    /**
     * แสดงหน้าจัดการ Recruit Page
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();

        // ดึงหรือสร้าง customization
        $customization = RecruitCustomization::firstOrCreate(
            ['user_id' => $user->id],
            [
                'template_id' => RecruitTemplate::getDefault()?->id,
                'is_active' => true,
            ]
        );

        $customization->load('template');

        // ดึงข้อมูล MLM Member
        $mlmMember = MlmMember::where('user_id', $user->id)->first();

        // สร้าง URL หน้า recruit
        $recruitUrl = $customization->getRecruitUrl();

        // สถิติ 7 วันล่าสุด
        $sevenDaysAgo = now()->subDays(7);
        $stats = [
            'total_views' => $customization->total_views,
            'total_leads' => $customization->total_leads,
            'total_conversions' => $customization->total_conversions,
            'conversion_rate' => $customization->conversion_rate,
            'views_last_7_days' => RecruitPageVisit::where('team_leader_id', $user->id)
                ->where('visited_at', '>=', $sevenDaysAgo)
                ->unique()
                ->count(),
            'leads_last_7_days' => LeadLock::where('team_leader_id', $user->id)
                ->where('locked_at', '>=', $sevenDaysAgo)
                ->count(),
        ];

        return view('user.recruit.index', [
            'customization' => $customization,
            'mlmMember' => $mlmMember,
            'recruitUrl' => $recruitUrl,
            'stats' => $stats,
            'pageTitle' => 'หน้าแนะนำสมาชิก',
        ]);
    }

    /**
     * อัพเดทข้อมูลส่วนตัว
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validate ข้อมูล
        $validated = $request->validate([
            'custom_name' => 'nullable|string|max:255',
            'custom_phone' => 'nullable|string|max:20',
            'custom_address' => 'nullable|string',
            'custom_pitch' => 'nullable|string|max:1000',
            'custom_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        try {
            $customization = RecruitCustomization::where('user_id', $user->id)->firstOrFail();

            // จัดการอัพโหลดรูปภาพ
            if ($request->hasFile('custom_image')) {
                // ลบรูปเก่า (ถ้ามี และไม่ใช่ URL)
                if ($customization->custom_image && !filter_var($customization->custom_image, FILTER_VALIDATE_URL)) {
                    Storage::disk('public')->delete($customization->custom_image);
                }

                // อัพโหลดรูปใหม่
                $path = $request->file('custom_image')->store('recruit-images', 'public');
                $validated['custom_image'] = $path;
            }

            // อัพเดทข้อมูล
            $customization->update($validated);

            return back()->with('success', 'อัพเดทข้อมูลสำเร็จ');

        } catch (\Exception $e) {
            \Log::error('Failed to update recruit customization', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * แสดงรายการผู้มุ่งหวัง (Leads)
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function leads(Request $request)
    {
        $user = Auth::user();

        // Filter
        $status = $request->get('status', 'all');

        $query = LeadLock::where('team_leader_id', $user->id)
            ->with('mlmMember.user');

        if ($status === 'active') {
            $query->locked();
        } elseif ($status === 'converted') {
            $query->converted();
        } elseif ($status === 'expired') {
            $query->expired();
        }

        $leads = $query->latest('locked_at')->paginate(20);

        // สถิติ
        $stats = [
            'total' => LeadLock::where('team_leader_id', $user->id)->count(),
            'active' => LeadLock::where('team_leader_id', $user->id)->locked()->count(),
            'converted' => LeadLock::where('team_leader_id', $user->id)->converted()->count(),
            'expired' => LeadLock::where('team_leader_id', $user->id)
                ->where('status', LeadLock::STATUS_LOCKED)
                ->where('expires_at', '<=', now())
                ->count(),
        ];

        return view('user.recruit.leads', [
            'leads' => $leads,
            'stats' => $stats,
            'currentStatus' => $status,
            'pageTitle' => 'ผู้มุ่งหวัง (Leads)',
        ]);
    }

    /**
     * แสดงสถิติการตลาด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();

        // ระยะเวลา (default: 30 วัน)
        $days = $request->get('days', 30);
        $startDate = now()->subDays($days);

        // Visits per day
        $visitsPerDay = RecruitPageVisit::where('team_leader_id', $user->id)
            ->where('visited_at', '>=', $startDate)
            ->unique()
            ->selectRaw('DATE(visited_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Leads per day
        $leadsPerDay = LeadLock::where('team_leader_id', $user->id)
            ->where('locked_at', '>=', $startDate)
            ->selectRaw('DATE(locked_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Device breakdown
        $deviceBreakdown = RecruitPageVisit::where('team_leader_id', $user->id)
            ->where('visited_at', '>=', $startDate)
            ->unique()
            ->selectRaw('device_type, COUNT(*) as count')
            ->groupBy('device_type')
            ->get();

        // Top referrers
        $topReferrers = RecruitPageVisit::where('team_leader_id', $user->id)
            ->where('visited_at', '>=', $startDate)
            ->whereNotNull('referrer_url')
            ->selectRaw('referrer_url, COUNT(*) as count')
            ->groupBy('referrer_url')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Conversion funnel
        $totalVisits = RecruitPageVisit::where('team_leader_id', $user->id)
            ->where('visited_at', '>=', $startDate)
            ->unique()
            ->count();

        $clickedRegister = RecruitPageVisit::where('team_leader_id', $user->id)
            ->where('visited_at', '>=', $startDate)
            ->clickedRegister()
            ->count();

        $totalLeads = LeadLock::where('team_leader_id', $user->id)
            ->where('locked_at', '>=', $startDate)
            ->count();

        $totalConversions = LeadLock::where('team_leader_id', $user->id)
            ->where('locked_at', '>=', $startDate)
            ->converted()
            ->count();

        $funnel = [
            'visits' => $totalVisits,
            'clicked_register' => $clickedRegister,
            'leads' => $totalLeads,
            'conversions' => $totalConversions,
        ];

        return view('user.recruit.analytics', [
            'visitsPerDay' => $visitsPerDay,
            'leadsPerDay' => $leadsPerDay,
            'deviceBreakdown' => $deviceBreakdown,
            'topReferrers' => $topReferrers,
            'funnel' => $funnel,
            'days' => $days,
            'pageTitle' => 'สถิติการตลาด',
        ]);
    }
}
