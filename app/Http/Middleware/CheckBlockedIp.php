<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use App\Models\Setting;
use App\Models\ThreatIp;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    /**
     * threat_type ที่แต่ละ checkbox ในหน้าตั้งค่าคุม
     *
     * @var array<string, array<int, string>>
     */
    protected const BLOCK_TYPE_SETTINGS = [
        'threat_block_proxy' => ['proxy'],
        'threat_block_vpn' => ['vpn'],
        'threat_block_tor' => ['tor'],
        'threat_block_abuse' => ['abuse', 'spam', 'malware', 'scanner'],
    ];

    /**
     * IP หนึ่งลง security_logs ได้ครั้งเดียวในช่วงนี้ (วินาที) — บอทยิงรัวต้องไม่กลายเป็นแถวใหม่ทุก request
     */
    protected const THREAT_LOG_INTERVAL_SECONDS = 600;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // ✅ ข้ามการตรวจสอบ IP ใน testing environment หรือเมื่อ database ไม่พร้อมใช้งาน
        try {
            // Check if IP is whitelisted (bypass all checks)
            if (BlockedIp::isWhitelisted($ip)) {
                return $next($request);
            }

            // Check if IP is blocked manually
            if (BlockedIp::isBlocked($ip)) {
                // Get the blocked IP record to get the reason
                $blockedRecord = BlockedIp::where('ip_address', $ip)
                    ->where('type', 'blacklist')
                    ->where('is_active', true)
                    ->first();

                // Log the blocked attempt
                SecurityLog::logEvent(
                    eventType: 'blocked_ip_attempt',
                    ipAddress: $ip,
                    severity: 'high',
                    description: 'Blocked IP attempted to access the application',
                    userAgent: $request->userAgent(),
                    metadata: [
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                    ]
                );

                // Return 403 Forbidden
                return response()->view('errors.blocked', [
                    'message' => 'Your IP address has been blocked due to security reasons.',
                    'ip' => $ip,
                    'reason' => $blockedRecord?->reason,
                ], 403);
            }

            // Check Threat Intelligence if enabled
            $threatInfo = $this->findBlockableThreat($request, $ip);
            if ($threatInfo) {
                $this->logThreatBlock($request, $ip, $threatInfo);

                // Return 403 Forbidden
                return response()->view('errors.blocked', [
                    'message' => 'Your IP address has been identified as a security threat and has been blocked.',
                    'ip' => $ip,
                    'reason' => "Threat Type: {$threatInfo->getThreatTypeLabel()} (Source: {$threatInfo->source})",
                ], 403);
            }

        } catch (\Exception $e) {
            // ⚠️ ถ้า database ไม่พร้อมใช้งาน (testing mode) ให้ข้ามการตรวจสอบและอนุญาตให้เข้าถึงได้
            // Error: could not find driver, Connection refused, etc.
            \Log::debug('CheckBlockedIp middleware skipped: '.$e->getMessage());
        }

        return $next($request);
    }

    /**
     * หาแถว threat ที่ต้องบล็อกตามการตั้งค่า — ไม่มีแถวใน settings = ปิด ไม่บล็อกใคร
     */
    protected function findBlockableThreat(Request $request, string $ip): ?ThreatIp
    {
        if (! Setting::get('threat_intelligence_enabled', false)) {
            return null;
        }

        $types = [];
        foreach (self::BLOCK_TYPE_SETTINGS as $setting => $settingTypes) {
            if (Setting::get($setting, false)) {
                array_push($types, ...$settingTypes);
            }
        }

        if ($types === [] || $this->isServerToServerRequest($request)) {
            return null;
        }

        $threshold = (int) Setting::get('threat_confidence_threshold', ThreatIp::DEFAULT_CONFIDENCE_THRESHOLD);

        return ThreatIp::getThreatInfo($ip, $threshold, $types);
    }

    /**
     * webhook/callback จากระบบภายนอก — ใช้รายการเดียวกับ path ที่เข้าได้ช่วงปิดปรับปรุง
     *
     * ต้นทางเป็น IP ของ cloud ที่อาจติดฟีด และโดน 403 แล้วข้อความหายถาวรเพราะ LINE ไม่ส่งซ้ำ
     * ยกเว้นเฉพาะ threat feed — IP ที่แอดมินบล็อกเองยังโดนบล็อกตามปกติ
     */
    protected function isServerToServerRequest(Request $request): bool
    {
        return $request->is(...app(PreventRequestsDuringMaintenance::class)->getExcludedPaths());
    }

    /**
     * บันทึกการบล็อกลง security_logs ไม่เกินหนึ่งครั้งต่อ IP ต่อช่วงเวลา
     *
     * log พังต้องไม่ทำให้ IP ที่กำลังโดนบล็อกหลุดผ่าน เพราะ catch ใน handle() ปล่อยผ่านเสมอ
     */
    protected function logThreatBlock(Request $request, string $ip, ThreatIp $threatInfo): void
    {
        try {
            if (! Cache::add("threat_block_logged:{$ip}", true, self::THREAT_LOG_INTERVAL_SECONDS)) {
                return;
            }

            // ข้าม hook ของ SecurityLog: hook ถาม geolocation ภายนอกสองเจ้า เจ้าละ 5 วินาที
            // request ที่กำลังปฏิเสธต้องไม่ถือ php-fpm worker ไว้ ไม่งั้นบอทหลาย IP ทำ pool เต็มได้
            SecurityLog::withoutEvents(fn () => SecurityLog::logEvent(
                eventType: 'threat_ip_blocked',
                ipAddress: $ip,
                severity: 'high',
                description: "Threat IP blocked: {$threatInfo->threat_type} (confidence: {$threatInfo->confidence_score}%)",
                userAgent: mb_substr((string) $request->userAgent(), 0, 255),
                metadata: [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'threat_type' => $threatInfo->threat_type,
                    'threat_source' => $threatInfo->source,
                    'confidence_score' => $threatInfo->confidence_score,
                ]
            ));
        } catch (\Exception $e) {
            \Log::warning('CheckBlockedIp could not log threat block: '.$e->getMessage());
        }
    }
}
