<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Jenssegers\Agent\Agent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Track Page View Middleware
 *
 * เก็บสถิติการเข้าชมหน้าเว็บของผู้ใช้
 * ใช้สำหรับวิเคราะห์พฤติกรรมและปรับปรุง UX
 *
 * Features:
 * - เก็บ path, referrer, device info
 * - ระบุ device type (mobile/tablet/desktop)
 * - ตรวจจับ bot/robot
 * - เก็บ UTM parameters
 * - Async recording (ใช้ queue ถ้ามี)
 */
class TrackPageView
{
    /**
     * Routes ที่ไม่ต้องเก็บสถิติ
     */
    protected array $excludedPaths = [
        'api/*',
        'livewire/*',
        'broadcasting/*',
        '_debugbar/*',
        'telescope/*',
        'horizon/*',
        'admin/analytics/realtime',
        'admin/analytics/page-views/realtime',
        'health',
        'up',
    ];

    /**
     * Extensions ที่ไม่ต้องเก็บสถิติ
     */
    protected array $excludedExtensions = [
        'js',
        'css',
        'png',
        'jpg',
        'jpeg',
        'gif',
        'svg',
        'ico',
        'woff',
        'woff2',
        'ttf',
        'eot',
        'map',
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // เก็บเวลาเริ่มต้น
        $startTime = microtime(true);

        // ประมวลผล request
        $response = $next($request);

        // ตรวจสอบว่าควรเก็บสถิติหรือไม่
        if (!$this->shouldTrack($request, $response)) {
            return $response;
        }

        // คำนวณเวลาโหลด
        $responseTime = (int) ((microtime(true) - $startTime) * 1000);

        // เก็บ page view
        $this->recordPageView($request, $response, $responseTime);

        return $response;
    }

    /**
     * ตรวจสอบว่าควรเก็บสถิติหรือไม่
     */
    protected function shouldTrack(Request $request, Response $response): bool
    {
        // เฉพาะ GET requests
        if (!$request->isMethod('GET')) {
            return false;
        }

        // ตรวจสอบ path ที่ยกเว้น
        $path = $request->path();
        foreach ($this->excludedPaths as $pattern) {
            if (fnmatch($pattern, $path)) {
                return false;
            }
        }

        // ตรวจสอบ extension ที่ยกเว้น
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $this->excludedExtensions)) {
            return false;
        }

        // ไม่เก็บ AJAX requests (ยกเว้นถ้าเป็น HTML)
        if ($request->ajax() && !str_contains($response->headers->get('Content-Type', ''), 'text/html')) {
            return false;
        }

        return true;
    }

    /**
     * บันทึก page view
     */
    protected function recordPageView(Request $request, Response $response, int $responseTime): void
    {
        try {
            // ใช้ Jenssegers Agent สำหรับ parse user agent
            $agent = new Agent();
            $agent->setUserAgent($request->userAgent());

            // ดึง referrer domain
            $referrer = $request->header('referer');
            $referrerDomain = null;
            if ($referrer) {
                $parsed = parse_url($referrer);
                $referrerDomain = $parsed['host'] ?? null;

                // ไม่เก็บถ้า referrer เป็น domain เดียวกัน
                if ($referrerDomain === $request->getHost()) {
                    $referrerDomain = null;
                }
            }

            // ดึง UTM parameters
            $utmSource = $request->query('utm_source');
            $utmMedium = $request->query('utm_medium');
            $utmCampaign = $request->query('utm_campaign');
            $utmTerm = $request->query('utm_term');
            $utmContent = $request->query('utm_content');

            // สร้าง page view record
            PageView::create([
                'user_id' => auth()->id(),
                'session_id' => session()->getId(),
                'url' => $request->fullUrl(),
                'path' => '/' . ltrim($request->path(), '/'),
                'route_name' => Route::currentRouteName(),
                'page_title' => null, // จะถูก update จาก JavaScript ถ้าต้องการ
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'referrer' => $referrer,
                'referrer_domain' => $referrerDomain,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_type' => $this->getDeviceType($agent),
                'browser' => $agent->browser(),
                'browser_version' => $agent->version($agent->browser()),
                'platform' => $agent->platform(),
                'is_robot' => $agent->isRobot(),
                'country' => null, // จะเพิ่ม GeoIP ทีหลังถ้าต้องการ
                'country_code' => null,
                'city' => null,
                'response_time_ms' => $responseTime,
                'utm_source' => $utmSource,
                'utm_medium' => $utmMedium,
                'utm_campaign' => $utmCampaign,
                'utm_term' => $utmTerm,
                'utm_content' => $utmContent,
                'viewed_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Log error แต่ไม่ทำให้ request fail
            Log::warning('Failed to record page view: ' . $e->getMessage());
        }
    }

    /**
     * ระบุประเภทอุปกรณ์
     */
    protected function getDeviceType(Agent $agent): string
    {
        if ($agent->isTablet()) {
            return 'tablet';
        }

        if ($agent->isMobile()) {
            return 'mobile';
        }

        return 'desktop';
    }
}
