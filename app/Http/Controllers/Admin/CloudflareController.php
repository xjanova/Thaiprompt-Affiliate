<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CloudflareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CloudflareController
 *
 * จัดการ Cloudflare ผ่าน Admin Panel
 * รองรับ: Cache Purge, DNS, Security, Analytics และอื่นๆ
 */
class CloudflareController extends Controller
{
    /**
     * Cloudflare Service
     */
    protected CloudflareService $cloudflare;

    /**
     * Constructor
     */
    public function __construct(CloudflareService $cloudflare)
    {
        $this->cloudflare = $cloudflare;
    }

    // ========================================
    // DASHBOARD
    // ========================================

    /**
     * หน้า Dashboard หลักของ Cloudflare
     */
    public function index(): View
    {
        $isConfigured = $this->cloudflare->isConfigured();
        $zoneInfo = null;
        $analytics = null;
        $securityLevel = null;
        $developmentMode = null;

        if ($isConfigured) {
            // ดึงข้อมูล Zone
            $zoneResult = $this->cloudflare->getZone();
            if ($zoneResult['success']) {
                $zoneInfo = $zoneResult['data'];
            }

            // ดึงข้อมูล Analytics
            $analyticsResult = $this->cloudflare->getAnalyticsDashboard();
            if ($analyticsResult['success']) {
                $analytics = $analyticsResult['data'];
            }

            // ดึง Security Level
            $securityResult = $this->cloudflare->getSecurityLevel();
            if ($securityResult['success']) {
                $securityLevel = $securityResult['data']['value'] ?? 'medium';
            }

            // ดึง Development Mode
            $devModeResult = $this->cloudflare->getDevelopmentMode();
            if ($devModeResult['success']) {
                $developmentMode = $devModeResult['data']['value'] ?? 'off';
            }
        }

        return view('admin.cloudflare.index', [
            'pageTitle' => 'Cloudflare Management',
            'isConfigured' => $isConfigured,
            'zoneInfo' => $zoneInfo,
            'analytics' => $analytics,
            'securityLevel' => $securityLevel,
            'developmentMode' => $developmentMode,
        ]);
    }

    // ========================================
    // CACHE MANAGEMENT
    // ========================================

    /**
     * หน้าจัดการ Cache
     */
    public function cache(): View
    {
        return view('admin.cloudflare.cache', [
            'pageTitle' => 'Cloudflare Cache Management',
            'isConfigured' => $this->cloudflare->isConfigured(),
        ]);
    }

    /**
     * Purge Cache ทั้งหมด
     */
    public function purgeAll(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า กรุณาเพิ่ม Zone ID และ API Token ใน .env',
            ], 400);
        }

        $result = $this->cloudflare->purgeEverything();

        return response()->json($result);
    }

    /**
     * Purge Cache ตาม URLs
     */
    public function purgeUrls(Request $request): JsonResponse
    {
        $request->validate([
            'urls' => 'required|array|min:1',
            'urls.*' => 'required|url',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->purgeUrls($request->urls);

        return response()->json($result);
    }

    /**
     * Purge Cache ตาม Prefixes
     */
    public function purgePrefixes(Request $request): JsonResponse
    {
        $request->validate([
            'prefixes' => 'required|array|min:1',
            'prefixes.*' => 'required|string',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->purgePrefixes($request->prefixes);

        return response()->json($result);
    }

    // ========================================
    // DNS MANAGEMENT
    // ========================================

    /**
     * หน้าจัดการ DNS
     */
    public function dns(): View
    {
        $dnsRecords = [];

        if ($this->cloudflare->isConfigured()) {
            $result = $this->cloudflare->getDnsRecords();
            if ($result['success']) {
                $dnsRecords = $result['data'];
            }
        }

        return view('admin.cloudflare.dns', [
            'pageTitle' => 'Cloudflare DNS Management',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'dnsRecords' => $dnsRecords,
        ]);
    }

    /**
     * สร้าง DNS Record ใหม่
     */
    public function createDns(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:A,AAAA,CNAME,TXT,MX,NS,SRV,CAA',
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:255',
            'ttl' => 'nullable|integer|min:1',
            'proxied' => 'nullable|boolean',
            'priority' => 'nullable|integer', // สำหรับ MX records
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $data = [
            'type' => $request->type,
            'name' => $request->name,
            'content' => $request->content,
            'ttl' => $request->ttl ?? 1, // 1 = auto
            'proxied' => $request->boolean('proxied', false),
        ];

        // เพิ่ม priority สำหรับ MX records
        if ($request->type === 'MX' && $request->priority) {
            $data['priority'] = $request->priority;
        }

        $result = $this->cloudflare->createDnsRecord($data);

        return response()->json($result);
    }

    /**
     * อัพเดท DNS Record
     */
    public function updateDns(Request $request, string $recordId): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:A,AAAA,CNAME,TXT,MX,NS,SRV,CAA',
            'name' => 'required|string|max:255',
            'content' => 'required|string|max:255',
            'ttl' => 'nullable|integer|min:1',
            'proxied' => 'nullable|boolean',
            'priority' => 'nullable|integer',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $data = [
            'type' => $request->type,
            'name' => $request->name,
            'content' => $request->content,
            'ttl' => $request->ttl ?? 1,
            'proxied' => $request->boolean('proxied', false),
        ];

        if ($request->type === 'MX' && $request->priority) {
            $data['priority'] = $request->priority;
        }

        $result = $this->cloudflare->updateDnsRecord($recordId, $data);

        return response()->json($result);
    }

    /**
     * ลบ DNS Record
     */
    public function deleteDns(string $recordId): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->deleteDnsRecord($recordId);

        return response()->json($result);
    }

    // ========================================
    // SECURITY SETTINGS
    // ========================================

    /**
     * หน้าตั้งค่า Security
     */
    public function security(): View
    {
        $securityLevel = 'medium';
        $firewallRules = [];
        $sslSettings = null;

        if ($this->cloudflare->isConfigured()) {
            // Security Level
            $result = $this->cloudflare->getSecurityLevel();
            if ($result['success']) {
                $securityLevel = $result['data']['value'] ?? 'medium';
            }

            // Firewall Rules
            $fwResult = $this->cloudflare->getFirewallRules();
            if ($fwResult['success']) {
                $firewallRules = $fwResult['data'];
            }

            // SSL Settings
            $sslResult = $this->cloudflare->getSslSettings();
            if ($sslResult['success']) {
                $sslSettings = $sslResult['data'];
            }
        }

        return view('admin.cloudflare.security', [
            'pageTitle' => 'Cloudflare Security Settings',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'securityLevel' => $securityLevel,
            'firewallRules' => $firewallRules,
            'sslSettings' => $sslSettings,
        ]);
    }

    /**
     * เปลี่ยน Security Level
     */
    public function setSecurityLevel(Request $request): JsonResponse
    {
        $request->validate([
            'level' => 'required|string|in:off,essentially_off,low,medium,high,under_attack',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->setSecurityLevel($request->level);

        return response()->json($result);
    }

    /**
     * เปิด Under Attack Mode
     */
    public function enableUnderAttack(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->enableUnderAttackMode();

        return response()->json($result);
    }

    /**
     * ปิด Under Attack Mode
     */
    public function disableUnderAttack(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->disableUnderAttackMode();

        return response()->json($result);
    }

    // ========================================
    // DEVELOPMENT MODE
    // ========================================

    /**
     * สลับ Development Mode
     */
    public function toggleDevelopmentMode(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->setDevelopmentMode($request->boolean('enabled'));

        return response()->json($result);
    }

    // ========================================
    // ANALYTICS
    // ========================================

    /**
     * หน้า Analytics
     */
    public function analytics(): View
    {
        $analytics = null;

        if ($this->cloudflare->isConfigured()) {
            $result = $this->cloudflare->getAnalyticsDashboard();
            if ($result['success']) {
                $analytics = $result['data'];
            }
        }

        return view('admin.cloudflare.analytics', [
            'pageTitle' => 'Cloudflare Analytics',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'analytics' => $analytics,
        ]);
    }

    /**
     * ดึงข้อมูล Analytics ล่าสุด (AJAX)
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $since = $request->get('since', '-1440'); // ค่าเริ่มต้น 24 ชั่วโมง

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->getAnalyticsDashboard(null, $since);

        return response()->json($result);
    }

    // ========================================
    // PAGE RULES
    // ========================================

    /**
     * หน้า Page Rules
     */
    public function pageRules(): View
    {
        $pageRules = [];

        if ($this->cloudflare->isConfigured()) {
            $result = $this->cloudflare->getPageRules();
            if ($result['success']) {
                $pageRules = $result['data'];
            }
        }

        return view('admin.cloudflare.page-rules', [
            'pageTitle' => 'Cloudflare Page Rules',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'pageRules' => $pageRules,
        ]);
    }

    // ========================================
    // ZONE INFORMATION
    // ========================================

    /**
     * ดึงข้อมูล Zones ทั้งหมด
     */
    public function getZones(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->getZones();

        return response()->json($result);
    }

    /**
     * ดึงข้อมูล Zone ปัจจุบัน
     */
    public function getZoneInfo(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->getZone();

        return response()->json($result);
    }

    // ========================================
    // SETTINGS
    // ========================================

    /**
     * หน้าตั้งค่า
     */
    public function settings(): View
    {
        // ดึง settings จาก database
        $storedSettings = CloudflareService::getStoredSettings();

        return view('admin.cloudflare.settings', [
            'pageTitle' => 'Cloudflare Settings',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'storedZoneId' => $storedSettings['zone_id'] ?? '',
            'storedApiToken' => $storedSettings['api_token'] ?? '',
            'configZoneId' => config('services.cloudflare.zone_id'),
            'hasConfigApiToken' => ! empty(config('services.cloudflare.api_token')),
        ]);
    }

    /**
     * บันทึกการตั้งค่า Cloudflare
     */
    public function saveSettings(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|string|min:32|max:32',
            'api_token' => 'required|string|min:40',
        ], [
            'zone_id.required' => 'กรุณาระบุ Zone ID',
            'zone_id.min' => 'Zone ID ต้องมี 32 ตัวอักษร',
            'zone_id.max' => 'Zone ID ต้องมี 32 ตัวอักษร',
            'api_token.required' => 'กรุณาระบุ API Token',
            'api_token.min' => 'API Token สั้นเกินไป',
        ]);

        // บันทึกลง database
        $result = CloudflareService::saveSettings(
            $request->input('zone_id'),
            $request->input('api_token')
        );

        if (! $result['success']) {
            return response()->json($result, 400);
        }

        // ทดสอบการเชื่อมต่อทันที
        $cloudflare = new CloudflareService;
        $connectionTest = $cloudflare->testConnection();

        return response()->json([
            'success' => true,
            'message' => 'บันทึกการตั้งค่าสำเร็จ',
            'connection_test' => $connectionTest,
        ]);
    }

    // ========================================
    // ONE-CLICK OPTIMIZATION
    // ========================================

    /**
     * หน้า Optimization
     */
    public function optimization(): View
    {
        $optimizationStatus = null;

        if ($this->cloudflare->isConfigured()) {
            $optimizationStatus = $this->cloudflare->checkOptimizationStatus();
        }

        return view('admin.cloudflare.optimization', [
            'pageTitle' => 'One-Click Optimization',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'optimizationStatus' => $optimizationStatus,
        ]);
    }

    /**
     * ดึงสถานะ Optimization (AJAX)
     */
    public function getOptimizationStatus(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->checkOptimizationStatus();

        return response()->json($result);
    }

    /**
     * รัน One-Click Optimization
     */
    public function runOptimization(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า กรุณาเพิ่ม Zone ID และ API Token',
            ], 400);
        }

        $result = $this->cloudflare->oneClickOptimization();

        return response()->json($result);
    }

    /**
     * ดึง Settings ทั้งหมด (AJAX)
     */
    public function getAllSettings(): JsonResponse
    {
        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        $result = $this->cloudflare->getAllSettings();

        return response()->json($result);
    }

    /**
     * ทดสอบการเชื่อมต่อ Cloudflare API
     */
    public function testConnection(): JsonResponse
    {
        $result = $this->cloudflare->testConnection();

        return response()->json($result);
    }

    // ========================================
    // AUTO UNDER ATTACK MODE
    // ========================================

    /**
     * หน้า Auto Under Attack Mode Settings
     */
    public function autoUnderAttack(): View
    {
        $settings = $this->cloudflare->getAutoUnderAttackSettings();
        $status = $this->cloudflare->getUnderAttackStatus();
        $checkResult = $this->cloudflare->checkAutoUnderAttackStatus();

        return view('admin.cloudflare.auto-under-attack', [
            'pageTitle' => 'Auto Under Attack Mode',
            'isConfigured' => $this->cloudflare->isConfigured(),
            'settings' => $settings,
            'status' => $status,
            'checkResult' => $checkResult,
        ]);
    }

    /**
     * บันทึกการตั้งค่า Auto Under Attack Mode
     */
    public function saveAutoUnderAttackSettings(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
            'threshold' => 'required|integer|min:10|max:1000',
            'time_window' => 'required|integer|min:1|max:60',
            'auto_disable_after' => 'required|integer|min:5|max:180',
            'max_failed_logins' => 'nullable|integer|min:10|max:500',
            'max_rate_limit' => 'nullable|integer|min:10|max:500',
            'max_unique_ips' => 'nullable|integer|min:5|max:200',
        ]);

        $result = CloudflareService::saveAutoUnderAttackSettings($request->all());

        return response()->json($result);
    }

    /**
     * ดึงสถานะ Auto Under Attack (AJAX)
     */
    public function getAutoUnderAttackStatus(): JsonResponse
    {
        $status = $this->cloudflare->getUnderAttackStatus();
        $checkResult = $this->cloudflare->checkAutoUnderAttackStatus();

        return response()->json([
            'success' => true,
            'status' => $status,
            'check_result' => $checkResult,
        ]);
    }

    /**
     * เปิด/ปิด Under Attack Mode ด้วยตนเอง
     */
    public function toggleUnderAttackMode(Request $request): JsonResponse
    {
        $request->validate([
            'enable' => 'required|boolean',
        ]);

        if (! $this->cloudflare->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'Cloudflare ยังไม่ได้ตั้งค่า',
            ], 400);
        }

        if ($request->boolean('enable')) {
            $result = $this->cloudflare->enableUnderAttackMode();
            if ($result['success']) {
                $result['message'] = 'เปิด Under Attack Mode สำเร็จ';
            }
        } else {
            $result = $this->cloudflare->disableUnderAttackMode();
            if ($result['success']) {
                $result['message'] = 'ปิด Under Attack Mode สำเร็จ';
            }
        }

        return response()->json($result);
    }

    /**
     * ทดสอบการตรวจจับการโจมตี (ไม่เปิด Under Attack จริง)
     */
    public function testAutoUnderAttack(): JsonResponse
    {
        $checkResult = $this->cloudflare->checkAutoUnderAttackStatus();

        return response()->json([
            'success' => true,
            'would_activate' => $checkResult['should_activate'],
            'threat_score' => $checkResult['threat_score'] ?? 0,
            'threshold' => $checkResult['threshold'] ?? 0,
            'reason' => $checkResult['reason'],
            'metrics' => $checkResult['metrics'],
        ]);
    }
}
