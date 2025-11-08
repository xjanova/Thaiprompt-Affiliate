<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CookieTracking;
use App\Models\CookieConsent;
use App\Models\CookieAnalyticsKeyword;
use App\Models\CookieSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CookieAnalyticsController extends Controller
{
    /**
     * แสดงหน้าแดชบอร์ด Analytics
     */
    public function index(Request $request)
    {
        $dateRange = $request->get('range', '7days');

        $startDate = match($dateRange) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(7),
        };

        $stats = [
            'total_visitors' => CookieTracking::where('created_at', '>=', $startDate)->count(),
            'total_sessions' => CookieTracking::where('created_at', '>=', $startDate)->distinct('session_id')->count(),
            'avg_session_duration' => CookieTracking::where('created_at', '>=', $startDate)->avg('session_duration'),
            'total_page_views' => CookieTracking::where('created_at', '>=', $startDate)->sum('page_views'),
            'consent_rate' => $this->getConsentRate($startDate),
        ];

        // Top referrers
        $topReferrers = CookieTracking::select('referrer_domain', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('referrer_domain')
            ->groupBy('referrer_domain')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Top keywords
        $topKeywords = CookieAnalyticsKeyword::getTopKeywords(20, $startDate->toDateString(), now()->toDateString());

        // Device breakdown
        $deviceStats = CookieTracking::select('device_type', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('device_type')
            ->get();

        // Browser breakdown
        $browserStats = CookieTracking::select('browser', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('browser')
            ->groupBy('browser')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Top interests
        $interestsData = CookieTracking::whereNotNull('interests_detected')
            ->where('created_at', '>=', $startDate)
            ->get()
            ->pluck('interests_detected')
            ->flatten()
            ->countBy()
            ->sortDesc()
            ->take(10);

        // UTM Campaign performance
        $utmCampaigns = CookieTracking::select(
                'utm_source',
                'utm_medium',
                'utm_campaign',
                DB::raw('COUNT(*) as visitors'),
                DB::raw('SUM(page_views) as total_page_views'),
                DB::raw('AVG(session_duration) as avg_duration')
            )
            ->where('created_at', '>=', $startDate)
            ->whereNotNull('utm_campaign')
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('visitors')
            ->limit(10)
            ->get();

        return view('admin.cookie-analytics.index', compact(
            'stats',
            'topReferrers',
            'topKeywords',
            'deviceStats',
            'browserStats',
            'interestsData',
            'utmCampaigns',
            'dateRange'
        ));
    }

    /**
     * ดูรายละเอียด visitor แบบละเอียด
     */
    public function visitors(Request $request)
    {
        $query = CookieTracking::with('user')
            ->orderBy('last_activity_at', 'desc');

        // Filters
        if ($request->has('device_type')) {
            $query->where('device_type', $request->get('device_type'));
        }

        if ($request->has('country')) {
            $query->where('country', $request->get('country'));
        }

        if ($request->has('referrer')) {
            $query->where('referrer_domain', 'like', '%' . $request->get('referrer') . '%');
        }

        $visitors = $query->paginate(50);

        return view('admin.cookie-analytics.visitors', compact('visitors'));
    }

    /**
     * ดูรายละเอียด visitor คนเดียว
     */
    public function visitorDetail($id)
    {
        $visitor = CookieTracking::with('user')->findOrFail($id);

        return view('admin.cookie-analytics.visitor-detail', compact('visitor'));
    }

    /**
     * จัดการการตั้งค่าคุกกี้
     */
    public function settings()
    {
        $settings = [
            'cookie_banner_enabled' => CookieSetting::get('cookie_banner_enabled', true),
            'cookie_banner_title' => CookieSetting::get('cookie_banner_title', 'เราใช้คุกกี้เพื่อปรับปรุงประสบการณ์ของคุณ'),
            'cookie_banner_description' => CookieSetting::get('cookie_banner_description', 'เราใช้คุกกี้เพื่อวิเคราะห์การใช้งานเว็บไซต์ ปรับปรุงประสบการณ์ของคุณ และแสดงเนื้อหาที่เหมาะสม คุณสามารถจัดการการตั้งค่าคุกกี้ได้ตามต้องการ'),
            'cookie_policy_url' => CookieSetting::get('cookie_policy_url', '/cookie-policy'),
            'auto_block_without_consent' => CookieSetting::get('auto_block_without_consent', true),
            'cookie_expiry_days' => CookieSetting::get('cookie_expiry_days', 365),
        ];

        return view('admin.cookie-analytics.settings', compact('settings'));
    }

    /**
     * บันทึกการตั้งค่า
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'cookie_banner_enabled' => 'required|boolean',
            'cookie_banner_title' => 'required|string|max:255',
            'cookie_banner_description' => 'required|string',
            'cookie_policy_url' => 'required|string|max:255',
            'auto_block_without_consent' => 'required|boolean',
            'cookie_expiry_days' => 'required|integer|min:1|max:730',
        ]);

        foreach ($validated as $key => $value) {
            $type = is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'text');
            CookieSetting::set($key, $value, $type);
        }

        return redirect()
            ->route('admin.cookie-analytics.settings')
            ->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Export ข้อมูล
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'csv');
        $dateRange = $request->get('range', '30days');

        $startDate = match($dateRange) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $data = CookieTracking::where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($format === 'json') {
            return response()->json($data);
        }

        // CSV Export
        $filename = 'cookie-analytics-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, [
                'Session ID', 'IP Address', 'Country', 'Device Type', 'Browser',
                'Referrer Domain', 'UTM Source', 'UTM Campaign', 'Page Views',
                'Session Duration', 'Interests', 'Created At'
            ]);

            // Data
            foreach ($data as $row) {
                fputcsv($file, [
                    $row->session_id,
                    $row->ip_address,
                    $row->country,
                    $row->device_type,
                    $row->browser,
                    $row->referrer_domain,
                    $row->utm_source,
                    $row->utm_campaign,
                    $row->page_views,
                    $row->session_duration,
                    implode(', ', $row->interests_detected ?? []),
                    $row->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * คำนวณอัตราการยินยอม
     */
    protected function getConsentRate($startDate): float
    {
        $totalVisitors = CookieTracking::where('created_at', '>=', $startDate)->count();
        $totalConsents = CookieConsent::where('created_at', '>=', $startDate)
            ->where('analytics', true)
            ->count();

        if ($totalVisitors === 0) {
            return 0;
        }

        return round(($totalConsents / $totalVisitors) * 100, 2);
    }
}
