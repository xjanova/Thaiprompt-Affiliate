<?php

namespace App\Services;

use App\Models\FreshMarketOrder;
use App\Models\FreshMarketSetting;
use App\Models\Rider;
use App\Models\RiderJob;
use App\Models\RiderLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * RiderGpsTrackingService - จัดการระบบ GPS Tracking ของไรเดอร์
 *
 * ฟีเจอร์หลัก:
 * - สร้าง/ตรวจสอบ tracking token สำหรับลูกค้าดูตำแหน่ง
 * - จัดการ GPS lost/resume flow
 * - ดึงตำแหน่งปัจจุบันและเส้นทาง
 * - ส่ง LINE notification เมื่อ GPS เปลี่ยนสถานะ
 */
class RiderGpsTrackingService
{
    public FreshMarketSetting $settings;
    protected FreshMarketLineService $lineService;

    public function __construct(?FreshMarketSetting $settings = null)
    {
        $this->settings = $settings ?? FreshMarketSetting::getSettings();
        $this->lineService = new FreshMarketLineService($this->settings);
    }

    /**
     * สร้าง tracking token สำหรับงาน (เรียกตอน dispatch)
     */
    public function generateTrackingToken(RiderJob $job, ?string $buyerLineUserId = null): string
    {
        $token = Str::random(48);
        $expiryHours = $this->settings->tracking_link_expiry_hours ?? 24;

        $job->update([
            'tracking_token' => $token,
            'tracking_expires_at' => now()->addHours($expiryHours),
            'gps_active' => true,
            'gps_warning_count' => 0,
            'buyer_line_user_id' => $buyerLineUserId,
        ]);

        return $token;
    }

    /**
     * ตรวจสอบ tracking token แล้วคืน RiderJob
     */
    public function validateToken(string $token): ?RiderJob
    {
        $job = RiderJob::where('tracking_token', $token)
            ->with(['rider', 'freshMarketOrder'])
            ->first();

        if (!$job || !$job->isTrackingValid()) {
            return null;
        }

        return $job;
    }

    /**
     * สร้าง URL สำหรับติดตาม
     */
    public function getTrackingUrl(RiderJob $job): string
    {
        return url('/taladsod/track/' . $job->tracking_token);
    }

    /**
     * ดึงตำแหน่งปัจจุบันของไรเดอร์
     */
    public function getCurrentLocation(RiderJob $job): array
    {
        $rider = $job->rider;
        if (!$rider) {
            return ['available' => false];
        }

        $isStale = $rider->last_location_update
            ? $rider->last_location_update->diffInSeconds(now()) > ($this->settings->gps_lost_timeout_seconds ?? 120)
            : true;

        return [
            'available' => !$isStale && $job->gps_active,
            'latitude' => (float) $rider->last_latitude,
            'longitude' => (float) $rider->last_longitude,
            'updated_at' => $rider->last_location_update?->toIso8601String(),
            'updated_ago' => $rider->last_location_update?->diffForHumans(),
            'gps_active' => (bool) $job->gps_active,
            'speed' => null, // จาก rider_locations ล่าสุด
            'heading' => null,
        ];
    }

    /**
     * ดึงเส้นทางการเดินทางของไรเดอร์
     */
    public function getRouteHistory(RiderJob $job, int $limit = 100): array
    {
        $locations = RiderLocation::forJob($job->id)
            ->orderBy('recorded_at', 'asc')
            ->limit($limit)
            ->get(['latitude', 'longitude', 'speed', 'recorded_at']);

        return $locations->map(fn($loc) => [
            'lat' => (float) $loc->latitude,
            'lng' => (float) $loc->longitude,
            'speed' => $loc->speed,
            'time' => $loc->recorded_at?->toIso8601String(),
        ])->toArray();
    }

    /**
     * อัพเดทตำแหน่ง GPS ของไรเดอร์
     */
    public function updateLocation(Rider $rider, RiderJob $job, array $locationData): void
    {
        // บันทึกตำแหน่งใหม่
        RiderLocation::recordLocation($rider->id, $locationData, $job->id);

        // อัพเดทตำแหน่งล่าสุดของไรเดอร์
        $rider->update([
            'last_latitude' => $locationData['latitude'],
            'last_longitude' => $locationData['longitude'],
            'last_location_update' => now(),
        ]);

        // ถ้า GPS กลับมาจากที่หาย → resume
        if (!$job->gps_active) {
            $this->handleGpsResume($job);
        }
    }

    /**
     * จัดการเมื่อ GPS ของไรเดอร์หาย
     */
    public function handleGpsLost(RiderJob $job): array
    {
        $maxWarnings = $this->settings->gps_warning_max ?? 3;
        $currentWarnings = ($job->gps_warning_count ?? 0) + 1;

        $job->pauseForGpsLoss();

        // แจ้งเตือนลูกค้าผ่าน LINE (try-catch ป้องกัน LINE API พังไม่ให้กระทบ GPS flow)
        if ($job->buyer_line_user_id) {
            try {
                $this->lineService->pushMessage($job->buyer_line_user_id, [
                    ['type' => 'text', 'text' => "⚠️ ไรเดอร์ปิด GPS ชั่วคราว\n\nกรุณารอสักครู่ค่ะ ระบบจะแจ้งเมื่อไรเดอร์เปิด GPS อีกครั้ง\n\n📦 งาน #{$job->job_number}"],
                ]);
            } catch (\Throwable $e) {
                Log::warning('RiderGPS: ส่ง LINE แจ้ง GPS lost ล้มเหลว', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        // ถ้าเกินจำนวนครั้งที่กำหนด → หยุดโฟลทันที
        $flowStopped = $currentWarnings >= $maxWarnings;
        if ($flowStopped) {
            Log::warning('RiderGPS: GPS หายเกินจำนวนที่กำหนด หยุดโฟล', [
                'job_id' => $job->id,
                'warnings' => $currentWarnings,
                'max' => $maxWarnings,
            ]);

            // แจ้งลูกค้า (try-catch ป้องกัน LINE API พังไม่ให้กระทบ GPS flow)
            if ($job->buyer_line_user_id) {
                try {
                    $this->lineService->pushMessage($job->buyer_line_user_id, [
                        ['type' => 'text', 'text' => "🚫 การจัดส่งถูกระงับชั่วคราว\n\nไรเดอร์ปิด GPS เกินจำนวนครั้งที่กำหนด ทีมงานกำลังดูแลค่ะ\n\n📦 งาน #{$job->job_number}"],
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('RiderGPS: ส่ง LINE แจ้ง flow stopped ล้มเหลว', ['job_id' => $job->id, 'error' => $e->getMessage()]);
                }
            }
        }

        return [
            'warning_count' => $currentWarnings,
            'max_warnings' => $maxWarnings,
            'flow_stopped' => $flowStopped,
            'message' => $flowStopped
                ? "🚫 GPS ถูกปิดเกินจำนวนที่กำหนด ({$currentWarnings}/{$maxWarnings})\nงานถูกระงับชั่วคราว กรุณาติดต่อแอดมิน"
                : "⚠️ คุณปิด GPS แล้ว ({$currentWarnings}/{$maxWarnings})\nกรุณาเปิด GPS เพื่อดำเนินการต่อ\n\nถ้าต้องการปิดจริงๆ งานจัดส่งจะถูกระงับชั่วคราว",
        ];
    }

    /**
     * จัดการเมื่อ GPS ของไรเดอร์กลับมา
     */
    public function handleGpsResume(RiderJob $job): void
    {
        $job->resumeFromGpsLoss();

        // แจ้งลูกค้าผ่าน LINE (try-catch ป้องกัน LINE API พังไม่ให้กระทบ GPS flow)
        if ($job->buyer_line_user_id) {
            try {
                $trackingUrl = $this->getTrackingUrl($job);
                $this->lineService->pushMessage($job->buyer_line_user_id, [
                    ['type' => 'text', 'text' => "✅ ไรเดอร์เปิด GPS แล้ว\n\nสามารถติดตามตำแหน่งได้อีกครั้งค่ะ\n🗺️ {$trackingUrl}\n\n📦 งาน #{$job->job_number}"],
                ]);
            } catch (\Throwable $e) {
                Log::warning('RiderGPS: ส่ง LINE แจ้ง GPS resume ล้มเหลว', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('RiderGPS: GPS กลับมาแล้ว', ['job_id' => $job->id]);
    }

    /**
     * ไรเดอร์ยืนยันปิด GPS จริงๆ → หยุดโฟลทันที
     */
    public function confirmGpsOff(RiderJob $job): array
    {
        $job->update([
            'gps_active' => false,
            'gps_lost_at' => now(),
        ]);

        // แจ้งลูกค้า (try-catch ป้องกัน LINE API พังไม่ให้กระทบ GPS flow)
        if ($job->buyer_line_user_id) {
            try {
                $this->lineService->pushMessage($job->buyer_line_user_id, [
                    ['type' => 'text', 'text' => "⏸️ ไรเดอร์หยุดพักการจัดส่งชั่วคราว\n\nระบบจะแจ้งเมื่อไรเดอร์กลับมาดำเนินการต่อค่ะ\n\n📦 งาน #{$job->job_number}"],
                ]);
            } catch (\Throwable $e) {
                Log::warning('RiderGPS: ส่ง LINE แจ้ง GPS off ล้มเหลว', ['job_id' => $job->id, 'error' => $e->getMessage()]);
            }
        }

        Log::info('RiderGPS: ไรเดอร์ยืนยันปิด GPS', ['job_id' => $job->id]);

        return [
            'message' => "⏸️ ปิด GPS แล้ว งานจัดส่งถูกหยุดชั่วคราว\n\nเปิด GPS อีกครั้งเพื่อดำเนินการต่อ",
        ];
    }

    /**
     * ดึงตำแหน่งลูกค้าสำหรับไรเดอร์
     */
    public function getCustomerLocation(RiderJob $job): array
    {
        return [
            'latitude' => (float) $job->delivery_latitude,
            'longitude' => (float) $job->delivery_longitude,
            'address' => $job->delivery_address,
            'contact_name' => $job->delivery_contact_name,
            'contact_phone' => $job->delivery_contact_phone,
            'google_maps_url' => "https://www.google.com/maps?q={$job->delivery_latitude},{$job->delivery_longitude}",
        ];
    }

    /**
     * ดึงตำแหน่งร้านค้า (pickup) สำหรับไรเดอร์
     */
    public function getPickupLocation(RiderJob $job): array
    {
        return [
            'latitude' => (float) $job->pickup_latitude,
            'longitude' => (float) $job->pickup_longitude,
            'address' => $job->pickup_address,
            'contact_name' => $job->pickup_contact_name,
            'contact_phone' => $job->pickup_contact_phone,
            'google_maps_url' => "https://www.google.com/maps?q={$job->pickup_latitude},{$job->pickup_longitude}",
        ];
    }

    /**
     * สร้าง Google Maps static image URL สำหรับ LINE Flex
     */
    public function getStaticMapUrl(float $lat, float $lng, int $zoom = 15, string $size = '600x300'): string
    {
        $apiKey = config('services.google_maps.api_key', '');
        return "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom={$zoom}&size={$size}&markers=color:red|{$lat},{$lng}&key={$apiKey}";
    }

    /**
     * ส่ง tracking link พร้อม map preview ให้ลูกค้าทาง LINE
     */
    public function sendTrackingLinkToBuyer(RiderJob $job): void
    {
        if (!$job->buyer_line_user_id || !$job->tracking_token) {
            return;
        }

        $trackingUrl = $this->getTrackingUrl($job);
        $riderName = $job->rider?->full_name ?? 'ไรเดอร์';
        $vehicleInfo = $job->rider?->vehicle_type ?? '';
        $plateInfo = $job->rider?->vehicle_plate ?? '';
        $primaryColor = $this->settings->line_flex_primary_color ?? '#22C55E';

        // สร้าง Flex Message
        $flex = [
            'type' => 'bubble',
            'styles' => ['header' => ['backgroundColor' => $primaryColor]],
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => '🏍️ ไรเดอร์กำลังมา!', 'color' => '#FFFFFF', 'weight' => 'bold', 'size' => 'lg'],
                    ['type' => 'text', 'text' => "งาน #{$job->job_number}", 'color' => '#FFFFFFCC', 'size' => 'sm', 'margin' => 'sm'],
                ],
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'md',
                'contents' => [
                    ['type' => 'box', 'layout' => 'horizontal', 'contents' => [
                        ['type' => 'text', 'text' => '👤 ไรเดอร์', 'size' => 'sm', 'color' => '#888888', 'flex' => 0],
                        ['type' => 'text', 'text' => $riderName, 'size' => 'sm', 'align' => 'end'],
                    ]],
                    ['type' => 'box', 'layout' => 'horizontal', 'contents' => [
                        ['type' => 'text', 'text' => '🚗 ยานพาหนะ', 'size' => 'sm', 'color' => '#888888', 'flex' => 0],
                        ['type' => 'text', 'text' => trim("{$vehicleInfo} {$plateInfo}") ?: '-', 'size' => 'sm', 'align' => 'end'],
                    ]],
                    ['type' => 'separator', 'margin' => 'md'],
                    ['type' => 'text', 'text' => '📍 ติดตามตำแหน่งแบบเรียลไทม์', 'size' => 'sm', 'color' => '#555555', 'margin' => 'md'],
                ],
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'spacing' => 'sm',
                'contents' => [
                    ['type' => 'button', 'style' => 'primary', 'color' => $primaryColor, 'height' => 'sm',
                        'action' => ['type' => 'uri', 'label' => '🗺️ ดูตำแหน่งไรเดอร์', 'uri' => $trackingUrl]],
                    // เพิ่มปุ่มโทรเฉพาะเมื่อไรเดอร์มีเบอร์โทร (ป้องกัน empty tel: URI)
                    ...($job->rider?->phone ? [
                        ['type' => 'button', 'style' => 'secondary', 'height' => 'sm',
                            'action' => ['type' => 'uri', 'label' => '📞 โทรหาไรเดอร์', 'uri' => 'tel:' . $job->rider->phone]],
                    ] : []),
                ],
            ],
        ];

        // try-catch ป้องกัน LINE API พังไม่ให้กระทบ tracking flow
        try {
            $this->lineService->pushMessage($job->buyer_line_user_id, [
                ['type' => 'flex', 'altText' => "🏍️ ไรเดอร์กำลังมา! ติดตามตำแหน่ง: {$trackingUrl}", 'contents' => $flex],
            ]);
        } catch (\Throwable $e) {
            Log::warning('RiderGPS: ส่ง LINE tracking link ล้มเหลว', ['job_id' => $job->id, 'error' => $e->getMessage()]);
        }
    }
}
