<?php

namespace App\Http\Controllers;

use App\Models\CookieConsent;
use App\Models\CookieTracking;
use App\Models\CookieAnalyticsKeyword;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class CookieConsentController extends Controller
{
    /**
     * บันทึกการยินยอมคุกกี้
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'necessary' => 'required|boolean',
            'analytics' => 'required|boolean',
            'marketing' => 'required|boolean',
            'preferences' => 'required|boolean',
        ]);

        $sessionId = session()->getId();

        CookieConsent::create([
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'necessary' => $validated['necessary'],
            'analytics' => $validated['analytics'],
            'marketing' => $validated['marketing'],
            'preferences' => $validated['preferences'],
            'consented_at' => now(),
        ]);

        // เริ่มการติดตามถ้ายินยอม analytics
        if ($validated['analytics']) {
            $this->startTracking($request, $sessionId);
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการยินยอมเรียบร้อยแล้ว',
        ]);
    }

    /**
     * เริ่มการติดตาม
     */
    protected function startTracking(Request $request, string $sessionId)
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $referrer = $request->header('referer');
        $referrerDomain = $referrer ? parse_url($referrer, PHP_URL_HOST) : null;

        // ดึง UTM parameters
        $utmParams = [
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'utm_term' => $request->get('utm_term'),
            'utm_content' => $request->get('utm_content'),
        ];

        // ตรวจสอบประเภทอุปกรณ์
        $deviceType = $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop');

        CookieTracking::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'referrer_url' => $referrer,
                'referrer_domain' => $referrerDomain,
                'utm_source' => $utmParams['utm_source'],
                'utm_medium' => $utmParams['utm_medium'],
                'utm_campaign' => $utmParams['utm_campaign'],
                'utm_term' => $utmParams['utm_term'],
                'utm_content' => $utmParams['utm_content'],
                'landing_page' => $request->fullUrl(),
                'current_page' => $request->fullUrl(),
                'device_type' => $deviceType,
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'os' => $agent->platform(),
                'os_version' => $agent->version($agent->platform()),
                'first_visit_at' => now(),
                'last_activity_at' => now(),
            ]
        );
    }

    /**
     * อัปเดตการติดตามหน้า
     */
    public function trackPage(Request $request)
    {
        $sessionId = session()->getId();

        $tracking = CookieTracking::where('session_id', $sessionId)->first();

        if (!$tracking) {
            return response()->json(['success' => false, 'message' => 'No tracking session found']);
        }

        $tracking->increment('page_views');
        $tracking->current_page = $request->get('page_url');
        $tracking->last_activity_at = now();

        // คำนวณระยะเวลา session
        $sessionDuration = now()->diffInSeconds($tracking->first_visit_at);
        $tracking->session_duration = $sessionDuration;

        $tracking->save();

        return response()->json(['success' => true]);
    }

    /**
     * บันทึกการค้นหา keyword
     */
    public function trackKeyword(Request $request)
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:255',
        ]);

        $sessionId = session()->getId();
        $tracking = CookieTracking::where('session_id', $sessionId)->first();

        if ($tracking) {
            $keywords = $tracking->searched_keywords ?? [];
            $keywords[] = [
                'keyword' => $validated['keyword'],
                'timestamp' => now()->toISOString(),
            ];

            $tracking->searched_keywords = $keywords;
            $tracking->save();

            // บันทึกใน analytics
            CookieAnalyticsKeyword::recordKeyword($validated['keyword'], $sessionId);
        }

        return response()->json(['success' => true]);
    }

    /**
     * บันทึกการดูสินค้า
     */
    public function trackProduct(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required',
            'product_name' => 'required|string',
            'category' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        $sessionId = session()->getId();
        $tracking = CookieTracking::where('session_id', $sessionId)->first();

        if ($tracking) {
            $products = $tracking->viewed_products ?? [];
            $products[] = [
                'product_id' => $validated['product_id'],
                'product_name' => $validated['product_name'],
                'category' => $validated['category'] ?? null,
                'price' => $validated['price'] ?? null,
                'timestamp' => now()->toISOString(),
            ];

            $tracking->viewed_products = array_slice($products, -50); // Keep last 50 products

            // วิเคราะห์ความสนใจ
            $interests = $tracking->detectInterests();
            $tracking->interests_detected = $interests;

            // วิเคราะห์พฤติกรรม
            $behaviors = $tracking->analyzeBehavior();
            $tracking->behavior_tags = $behaviors;

            $tracking->save();
        }

        return response()->json(['success' => true]);
    }

    /**
     * ดึงข้อมูล consent ปัจจุบัน
     */
    public function getConsent(Request $request)
    {
        $sessionId = session()->getId();

        $consent = CookieConsent::where('session_id', $sessionId)
            ->latest()
            ->first();

        return response()->json([
            'consent' => $consent,
            'has_consent' => $consent !== null,
        ]);
    }
}
