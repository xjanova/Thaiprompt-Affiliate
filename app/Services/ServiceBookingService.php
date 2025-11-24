<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingItem;
use App\Models\ServiceBookingLocation;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ServiceBookingService
 *
 * บริการหลักสำหรับ Service Booking System
 * จัดการการจอง, มอบหมายผู้ให้บริการ, ชำระเงิน, tracking
 */
class ServiceBookingService
{
    public function __construct(
        protected ServicePricingService $pricingService,
        protected ServiceBookingNotificationService $notificationService,
        protected GoogleMapsService $mapsService,
        protected WalletService $walletService
    ) {
    }

    /**
     * สร้างการจองบริการ
     *
     * @param array $data ข้อมูลการจอง
     * @return ServiceBooking
     * @throws \Exception
     */
    public function createBooking(array $data): ServiceBooking
    {
        return DB::transaction(function () use ($data) {
            $user = $data['user'] ?? auth()->user();
            $service = Service::findOrFail($data['service_id']);

            // ตรวจสอบว่าบริการต้องการ location หรือไม่
            if ($service->requires_location && (!isset($data['customer_latitude']) || !isset($data['customer_longitude']))) {
                throw new \Exception('บริการนี้ต้องระบุตำแหน่ง GPS');
            }

            // คำนวณระยะทาง (ถ้ามีตำแหน่ง)
            $distanceKm = 0;
            if (isset($data['customer_latitude']) && isset($data['provider_latitude'])) {
                $distanceKm = $this->calculateDistance(
                    $data['provider_latitude'],
                    $data['provider_longitude'],
                    $data['customer_latitude'],
                    $data['customer_longitude']
                );
            }

            // คำนวณราคาออฟชั่น
            $optionsPricing = $this->pricingService->calculateOptionsPrice(
                $service,
                $data['selected_options'] ?? []
            );

            // คำนวณราคารวม
            $pricing = $this->pricingService->calculateTotalPrice([
                'base_price' => $service->base_price,
                'distance_km' => $distanceKm,
                'service_id' => $service->id,
                'options_price' => $optionsPricing['total_price'],
                'discount_amount' => $data['discount_amount'] ?? 0,
            ]);

            // คำนวณค่าธรรมเนียม (Platform Fee + Provider PV)
            $totalAmount = $pricing['total_amount'];
            $platformFeePercentage = $service->platform_fee_percentage ?? 10.00;
            $providerPvPercentage = $service->provider_pv_percentage ?? 5.00;

            $platformFeeAmount = $totalAmount * ($platformFeePercentage / 100);
            $providerPvAmount = $totalAmount * ($providerPvPercentage / 100);
            $providerEarnings = $totalAmount - $platformFeeAmount - $providerPvAmount;
            $systemRevenue = $platformFeeAmount + $providerPvAmount;

            // สร้างการจอง (พร้อม snapshot ค่าธรรมเนียม)
            $booking = ServiceBooking::create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'scheduled_at' => $data['scheduled_at'],
                'service_duration_minutes' => $service->duration_minutes,
                'base_price' => $pricing['base_price'],
                'distance_km' => $pricing['distance_km'],
                'distance_price' => $pricing['distance_price'],
                'options_price' => $pricing['options_price'],
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'discount_amount' => $pricing['discount_amount'],
                'total_amount' => $pricing['total_amount'],
                // Platform Fee & Provider PV (snapshot ณ เวลาจอง)
                'platform_fee_percentage' => $platformFeePercentage,
                'platform_fee_amount' => round($platformFeeAmount, 2),
                'provider_pv_percentage' => $providerPvPercentage,
                'provider_pv_amount' => round($providerPvAmount, 2),
                'provider_earnings' => round($providerEarnings, 2),
                'system_revenue' => round($systemRevenue, 2),
                // อื่นๆ
                'payment_method' => $data['payment_method'] ?? 'wallet',
                'payment_status' => 'pending',
                'status' => 'pending',
                'customer_notes' => $data['customer_notes'] ?? null,
                'metadata' => [
                    'selected_options' => $data['selected_options'] ?? [],
                    'pricing_breakdown' => $pricing,
                    'fee_breakdown' => [
                        'platform_fee' => round($platformFeeAmount, 2),
                        'provider_pv' => round($providerPvAmount, 2),
                        'provider_earnings' => round($providerEarnings, 2),
                        'system_revenue' => round($systemRevenue, 2),
                    ],
                ],
            ]);

            // สร้างรายการย่อย
            $this->createBookingItems($booking, $service, $optionsPricing);

            // บันทึกตำแหน่ง GPS
            if (isset($data['customer_latitude'])) {
                $this->saveCustomerLocation($booking, $data);
            }

            // Log action
            $booking->logAction('created', [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $booking;
        });
    }

    /**
     * คำนวณระยะทางระหว่าง 2 จุด (km)
     *
     * ใช้ Google Maps Directions API เพื่อคำนวณระยะทางจริงตามถนน
     * ถ้า Google Maps API ไม่พร้อมใช้งาน จะ fallback เป็น Haversine formula
     *
     * @param float $lat1 ละติจูดจุดเริ่มต้น
     * @param float $lng1 ลองจิจูดจุดเริ่มต้น
     * @param float $lat2 ละติจูดจุดปลายทาง
     * @param float $lng2 ลองจิจูดจุดปลายทาง
     * @param string $mode โหมดการเดินทาง (driving, walking, bicycling, transit)
     * @return float ระยะทาง (กิโลเมตร)
     */
    public function calculateDistance(
        float $lat1,
        float $lng1,
        float $lat2,
        float $lng2,
        string $mode = 'driving'
    ): float {
        try {
            // ใช้ Google Maps API เพื่อคำนวณระยะทางจริงตามถนน
            $directions = $this->mapsService->getDirections(
                ['lat' => $lat1, 'lng' => $lng1],
                ['lat' => $lat2, 'lng' => $lng2],
                $mode
            );

            // แปลงจาก meters เป็น kilometers
            $distanceKm = $directions['distance']['value'] / 1000;

            return round($distanceKm, 2);
        } catch (\Exception $e) {
            // Fallback: ใช้ Haversine formula ถ้า Google Maps API ไม่พร้อมใช้งาน
            \Log::warning('Google Maps API failed in ServiceBookingService, using Haversine fallback', [
                'error' => $e->getMessage(),
                'coordinates' => compact('lat1', 'lng1', 'lat2', 'lng2'),
            ]);

            return $this->calculateDistanceHaversine($lat1, $lng1, $lat2, $lng2);
        }
    }

    /**
     * คำนวณระยะทางแบบเส้นตรงด้วย Haversine formula (km)
     *
     * ⚠️ หมายเหตุ: ใช้เป็น fallback เท่านั้น ไม่ใช่ระยะทางจริงตามถนน
     * ระยะทางจริงตามถนนอาจมากกว่า 20-50% ขึ้นอยู่กับภูมิประเทศ
     *
     * @param float $lat1
     * @param float $lng1
     * @param float $lat2
     * @param float $lng2
     * @return float
     */
    protected function calculateDistanceHaversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($angle * $earthRadius, 2);
    }

    /**
     * ชำระเงิน
     *
     * @param ServiceBooking $booking
     * @param string $paymentMethod
     * @return PaymentTransaction
     * @throws \Exception
     */
    public function processPayment(ServiceBooking $booking, string $paymentMethod = 'wallet'): PaymentTransaction
    {
        if ($booking->payment_status === 'paid') {
            throw new \Exception('การจองนี้ชำระเงินแล้ว');
        }

        return DB::transaction(function () use ($booking, $paymentMethod) {
            if ($paymentMethod === 'wallet') {
                // ชำระผ่าน Wallet
                $transaction = $this->walletService->deduct(
                    $booking->user,
                    $booking->total_amount,
                    'service_booking',
                    "ชำระค่าบริการ #{$booking->booking_number}",
                    [
                        'booking_id' => $booking->id,
                        'service_id' => $booking->service_id,
                    ]
                );

                $booking->markAsPaid($transaction);

                // อัพเดทสถานะเป็น paid (พร้อมมอบหมาย provider)
                $booking->updateStatus('paid');

                return $transaction;
            }

            // TODO: รองรับ payment methods อื่น ๆ (PromptPay, Card, etc.)
            throw new \Exception('Payment method ไม่รองรับ');
        });
    }

    /**
     * มอบหมายผู้ให้บริการ
     *
     * @param ServiceBooking $booking
     * @param ServiceProvider|null $provider
     * @param int $responseMinutes
     * @return bool
     * @throws \Exception
     */
    public function assignProvider(
        ServiceBooking $booking,
        ?ServiceProvider $provider = null,
        int $responseMinutes = 15
    ): bool {
        return DB::transaction(function () use ($booking, $provider, $responseMinutes) {
            // ถ้าไม่ระบุ provider ให้หาอัตโนมัติ
            if (!$provider) {
                $provider = $this->findAvailableProvider($booking);

                if (!$provider) {
                    throw new \Exception('ไม่พบผู้ให้บริการที่พร้อมรับงาน');
                }
            }

            // ตรวจสอบว่า provider พร้อมรับงานหรือไม่
            if (!$provider->isAvailable()) {
                throw new \Exception('ผู้ให้บริการไม่พร้อมรับงานในขณะนี้');
            }

            // มอบหมายและแจ้งเตือน
            $booking->notifyProvider($provider, $responseMinutes);

            // ส่ง notification
            $results = $this->notificationService->notifyNewBooking($booking, $provider);

            // ตั้งเวลา provider เป็น busy
            $provider->setBusy();

            Log::info('Provider assigned', [
                'booking_id' => $booking->id,
                'provider_id' => $provider->id,
                'notification_results' => $results,
            ]);

            return true;
        });
    }

    /**
     * หาผู้ให้บริการที่พร้อมรับงาน
     *
     * @param ServiceBooking $booking
     * @return ServiceProvider|null
     */
    protected function findAvailableProvider(ServiceBooking $booking): ?ServiceProvider
    {
        // TODO: ใช้ algorithm หาผู้ให้บริการที่เหมาะสม
        // - ระยะทางใกล้ที่สุด
        // - คะแนนสูงสุด
        // - เวลาตอบกลับเร็วที่สุด
        // - Auto-accept settings

        return ServiceProvider::where('status', 'available')
            ->where('is_active', true)
            ->first();
    }

    /**
     * Provider รับงาน
     *
     * @param ServiceBooking $booking
     * @param string|null $notes
     * @return bool
     */
    public function acceptBooking(ServiceBooking $booking, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($booking, $notes) {
            // รับงาน
            $booking->acceptByProvider($notes);

            // ส่ง notification ให้ลูกค้า
            $this->notificationService->sendInAppNotification(
                $booking,
                $booking->user,
                'booking_accepted'
            );

            return true;
        });
    }

    /**
     * Provider ปฏิเสธงาน
     *
     * @param ServiceBooking $booking
     * @param string $reason
     * @return bool
     */
    public function rejectBooking(ServiceBooking $booking, string $reason): bool
    {
        return DB::transaction(function () use ($booking, $reason) {
            // ปฏิเสธงาน
            $booking->rejectByProvider($reason);

            // ปลดล็อค provider
            if ($booking->provider) {
                $booking->provider->setAvailable();
            }

            // ส่ง notification ให้ลูกค้า
            $this->notificationService->sendInAppNotification(
                $booking,
                $booking->user,
                'booking_rejected'
            );

            // TODO: หา provider ใหม่อัตโนมัติ
            // $this->assignProvider($booking);

            return true;
        });
    }

    /**
     * เริ่มเดินทาง
     *
     * @param ServiceBooking $booking
     * @return bool
     */
    public function startJourney(ServiceBooking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking->startJourney();

            // ส่ง notification
            $this->notificationService->sendInAppNotification(
                $booking,
                $booking->user,
                'provider_on_way'
            );

            return true;
        });
    }

    /**
     * เริ่มให้บริการ
     *
     * @param ServiceBooking $booking
     * @return bool
     */
    public function startService(ServiceBooking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking->startService();

            return true;
        });
    }

    /**
     * เสร็จสิ้นบริการ
     *
     * @param ServiceBooking $booking
     * @return bool
     */
    public function completeService(ServiceBooking $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            $booking->completeService();

            // จ่ายเงินให้ provider
            $this->payProvider($booking);

            // คำนวณและจ่ายคอมมิชชั่น MLM
            $this->payCommission($booking);

            // ปลดล็อค provider
            if ($booking->provider) {
                $booking->provider->setAvailable();
            }

            // ส่ง notification
            $this->notificationService->sendInAppNotification(
                $booking,
                $booking->user,
                'service_completed'
            );

            return true;
        });
    }

    /**
     * ยกเลิกการจอง
     *
     * @param ServiceBooking $booking
     * @param string $reason
     * @param string $cancelledBy
     * @return bool
     * @throws \Exception
     */
    public function cancelBooking(ServiceBooking $booking, string $reason, string $cancelledBy = 'customer'): bool
    {
        return DB::transaction(function () use ($booking, $reason, $cancelledBy) {
            // ยกเลิก
            $booking->cancel($reason, $cancelledBy);

            // คืนเงิน (ถ้าชำระแล้ว)
            if ($booking->payment_status === 'paid') {
                $this->refundPayment($booking);
            }

            // ปลดล็อค provider
            if ($booking->provider) {
                $booking->provider->setAvailable();
            }

            return true;
        });
    }

    /**
     * คืนเงิน
     *
     * @param ServiceBooking $booking
     * @return void
     */
    protected function refundPayment(ServiceBooking $booking): void
    {
        if ($booking->payment_method === 'wallet' && $booking->paymentTransaction) {
            $this->walletService->refund(
                $booking->user,
                $booking->total_amount,
                'service_booking_refund',
                "คืนเงินการจอง #{$booking->booking_number}",
                [
                    'booking_id' => $booking->id,
                    'original_transaction_id' => $booking->payment_transaction_id,
                ]
            );

            $booking->refund();
        }
    }

    /**
     * จ่ายเงินให้ provider
     *
     * @param ServiceBooking $booking
     * @return void
     */
    protected function payProvider(ServiceBooking $booking): void
    {
        if (!$booking->provider) {
            return;
        }

        // คำนวณเงินที่ provider ได้รับ (หักค่า platform fee)
        $platformFeeRate = config('services.platform_fee_rate', 0.15); // 15%
        $platformFee = $booking->total_amount * $platformFeeRate;
        $providerAmount = $booking->total_amount - $platformFee;

        // เพิ่มเงินเข้า wallet ของ provider
        $this->walletService->deposit(
            $booking->provider->user,
            $providerAmount,
            'service_income',
            "รายได้จากบริการ #{$booking->booking_number}",
            [
                'booking_id' => $booking->id,
                'service_id' => $booking->service_id,
                'platform_fee' => $platformFee,
            ]
        );
    }

    /**
     * จ่ายคอมมิชชั่น MLM
     *
     * @param ServiceBooking $booking
     * @return void
     */
    protected function payCommission(ServiceBooking $booking): void
    {
        if (!$booking->service->commission_rate) {
            return;
        }

        // คำนวณคอมมิชชั่น
        $commissionAmount = $this->pricingService->calculateCommission(
            $booking->total_amount,
            $booking->service->commission_rate
        );

        // TODO: จ่ายคอมมิชชั่นตาม MLM structure
        // ใช้ระบบ MLM ที่มีอยู่แล้ว

        $booking->commission_amount = $commissionAmount;
        $booking->save();
    }

    /**
     * อัพเดทตำแหน่ง GPS ของ provider
     *
     * @param ServiceBooking $booking
     * @param float $latitude
     * @param float $longitude
     * @return void
     */
    public function updateProviderLocation(ServiceBooking $booking, float $latitude, float $longitude): void
    {
        if (!$booking->provider) {
            return;
        }

        // อัพเดทตำแหน่งปัจจุบันของ provider
        $booking->provider->updateLocation($latitude, $longitude);

        // คำนวณระยะทางถึงลูกค้า
        $customerLocation = $booking->locations()->serviceLocation()->first();

        if ($customerLocation) {
            $distanceToCustomer = $this->calculateDistance(
                $latitude,
                $longitude,
                $customerLocation->latitude,
                $customerLocation->longitude
            );

            // บันทึก tracking log
            ServiceBookingTracking::log($booking, $booking->provider, [
                'status' => $booking->status,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'distance_to_customer_km' => $distanceToCustomer,
                'estimated_arrival_minutes' => $this->estimateArrivalTime($distanceToCustomer),
            ]);
        }
    }

    /**
     * ประเมินเวลาถึง (นาที)
     *
     * @param float $distanceKm
     * @return int
     */
    protected function estimateArrivalTime(float $distanceKm): int
    {
        $averageSpeedKmh = config('services.average_speed_kmh', 30); // 30 km/h
        $hours = $distanceKm / $averageSpeedKmh;
        return (int) ceil($hours * 60); // แปลงเป็นนาที
    }

    /**
     * สร้างรายการย่อย (ServiceBookingItem)
     *
     * @param ServiceBooking $booking
     * @param Service $service
     * @param array $optionsPricing
     * @return void
     */
    protected function createBookingItems(ServiceBooking $booking, Service $service, array $optionsPricing): void
    {
        // รายการบริการหลัก
        ServiceBookingItem::create([
            'booking_id' => $booking->id,
            'item_type' => 'service',
            'item_id' => $service->id,
            'name' => $service->name,
            'description' => $service->description,
            'quantity' => 1,
            'unit_price' => $service->base_price,
            'subtotal' => $service->base_price,
        ]);

        // ค่าเดินทาง
        if ($booking->distance_price > 0) {
            ServiceBookingItem::create([
                'booking_id' => $booking->id,
                'item_type' => 'distance_fee',
                'name' => "ค่าเดินทาง ({$booking->distance_km} km)",
                'quantity' => 1,
                'unit_price' => $booking->distance_price,
                'subtotal' => $booking->distance_price,
            ]);
        }

        // ออฟชั่นเพิ่มเติม
        foreach ($optionsPricing['options'] as $option) {
            ServiceBookingItem::create([
                'booking_id' => $booking->id,
                'item_type' => 'option',
                'name' => $option['name'],
                'quantity' => 1,
                'unit_price' => $option['price'],
                'subtotal' => $option['price'],
            ]);
        }
    }

    /**
     * บันทึกตำแหน่งลูกค้า
     *
     * @param ServiceBooking $booking
     * @param array $data
     * @return void
     */
    protected function saveCustomerLocation(ServiceBooking $booking, array $data): void
    {
        ServiceBookingLocation::create([
            'booking_id' => $booking->id,
            'type' => 'service_location',
            'latitude' => $data['customer_latitude'],
            'longitude' => $data['customer_longitude'],
            'address' => $data['customer_address'] ?? '',
            'building_name' => $data['building_name'] ?? null,
            'floor_number' => $data['floor_number'] ?? null,
            'room_number' => $data['room_number'] ?? null,
            'landmark' => $data['landmark'] ?? null,
            'contact_name' => $data['contact_name'] ?? $booking->user->name,
            'contact_phone' => $data['contact_phone'] ?? $booking->user->phone,
        ]);
    }
}
