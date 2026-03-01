<?php

namespace App\Services;

use App\Models\FreshMarketOrder;
use App\Models\Rider;
use App\Models\RiderJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rider Dispatch Service
 *
 * จัดการการจ่ายงานให้ไรเดอร์ คำนวณค่าส่ง และจัดการค่าประกัน
 */
class RiderDispatchService
{
    /**
     * ค่าส่งพื้นฐาน (บาท)
     */
    protected float $baseFee = 30.0;

    /**
     * ค่าส่งต่อกิโลเมตร (บาท)
     */
    protected float $perKmFee = 10.0;

    /**
     * ค่าส่งขั้นต่ำ (บาท)
     */
    protected float $minFee = 30.0;

    /**
     * อัตราส่วนรายได้ไรเดอร์ (80%)
     */
    protected float $riderEarningsRate = 0.80;

    /**
     * รัศมีค้นหาเริ่มต้น (กม.)
     */
    protected float $defaultSearchRadius = 5.0;

    /**
     * ค้นหาไรเดอร์ที่ใกล้ที่สุดสำหรับ order
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @param float $radiusKm รัศมีค้นหา (กม.)
     * @param int $limit จำนวนสูงสุดที่จะแสดง
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findNearestRiders(
        FreshMarketOrder $order,
        float $radiusKm = 0,
        int $limit = 5
    ) {
        if ($radiusKm <= 0) {
            $radiusKm = $this->defaultSearchRadius;
        }

        // ดึงพิกัดร้านค้าจาก seller
        $seller = $order->seller;
        if (! $seller) {
            Log::warning('RiderDispatch: order ไม่มี seller', ['order_id' => $order->id]);

            return collect();
        }

        // ถ้า seller ไม่มีพิกัด ให้ใช้พิกัดจาก listing
        $latitude = $seller->latitude ?? $order->listing?->latitude;
        $longitude = $seller->longitude ?? $order->listing?->longitude;

        if (! $latitude || ! $longitude) {
            Log::warning('RiderDispatch: ไม่มีพิกัดร้านค้า', [
                'order_id' => $order->id,
                'seller_id' => $seller->id,
            ]);

            return collect();
        }

        // ค้นหาไรเดอร์ที่พร้อมรับงานส่งของ ใกล้ร้านค้า
        return Rider::availableForDelivery()
            ->nearby($latitude, $longitude, $radiusKm)
            ->limit($limit)
            ->get();
    }

    /**
     * จ่ายงานให้ไรเดอร์
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @param Rider $rider ไรเดอร์ที่จะรับงาน
     * @return RiderJob|null งานที่สร้าง
     */
    public function dispatchToRider(FreshMarketOrder $order, Rider $rider): ?RiderJob
    {
        if (! $rider->canAcceptJobs()) {
            Log::warning('RiderDispatch: ไรเดอร์ไม่พร้อมรับงาน', [
                'rider_id' => $rider->id,
                'status' => $rider->status,
                'availability' => $rider->availability,
                'deposit_status' => $rider->deposit_status,
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $rider) {
            // คำนวณระยะทาง
            $seller = $order->seller;
            $sellerLat = $seller->latitude ?? $order->listing?->latitude;
            $sellerLng = $seller->longitude ?? $order->listing?->longitude;
            $buyerLat = $order->buyer_latitude;
            $buyerLng = $order->buyer_longitude;

            $distance = 0;
            if ($sellerLat && $sellerLng && $buyerLat && $buyerLng) {
                $distance = $this->calculateDistance($sellerLat, $sellerLng, $buyerLat, $buyerLng);
            }

            // คำนวณค่าส่ง
            $deliveryFee = $this->calculateDeliveryFee($distance);
            $riderEarnings = round($deliveryFee * $this->riderEarningsRate, 2);
            $platformFee = round($deliveryFee - $riderEarnings, 2);

            // สร้าง RiderJob
            $job = RiderJob::create([
                'rider_id' => $rider->id,
                'job_type' => 'fresh_market',
                'title' => 'ส่งของตลาดสด #' . $order->order_number,
                'description' => 'ส่งสินค้าจากร้าน ' . ($seller->shop_name ?? 'ตลาดสด'),
                'pickup_address' => $seller->address ?? '',
                'pickup_latitude' => $sellerLat,
                'pickup_longitude' => $sellerLng,
                'pickup_contact_name' => $seller->shop_name ?? $seller->name ?? '',
                'pickup_contact_phone' => $seller->phone ?? '',
                'delivery_address' => $order->delivery_address ?? '',
                'delivery_latitude' => $buyerLat,
                'delivery_longitude' => $buyerLng,
                'delivery_contact_name' => $order->buyer?->name ?? '',
                'delivery_contact_phone' => $order->buyer?->phone ?? '',
                'delivery_notes' => $order->delivery_notes,
                'distance_km' => $distance,
                'base_fee' => $this->baseFee,
                'distance_fee' => max(0, $deliveryFee - $this->baseFee),
                'total_fee' => $deliveryFee,
                'rider_earnings' => $riderEarnings,
                'platform_fee' => $platformFee,
                'customer_id' => $order->buyer_id,
                'status' => 'pending',
            ]);

            // อัปเดท order
            $order->update([
                'rider_id' => $rider->id,
                'rider_job_id' => $job->id,
                'rider_assigned_at' => now(),
                'delivery_fee' => $deliveryFee,
                'delivery_distance_km' => $distance,
            ]);

            // ตั้งสถานะไรเดอร์เป็น busy
            $rider->setBusy();

            Log::info('RiderDispatch: จ่ายงานสำเร็จ', [
                'order_id' => $order->id,
                'rider_id' => $rider->id,
                'job_id' => $job->id,
                'distance_km' => $distance,
                'delivery_fee' => $deliveryFee,
            ]);

            return $job;
        });
    }

    /**
     * คำนวณค่าส่ง
     *
     * @param float $distanceKm ระยะทาง (กม.)
     * @return float ค่าส่ง (บาท)
     */
    public function calculateDeliveryFee(float $distanceKm): float
    {
        $fee = $this->baseFee + ($distanceKm * $this->perKmFee);

        return max($this->minFee, round($fee, 2));
    }

    /**
     * คำนวณระยะทางด้วย Haversine formula
     *
     * @param float $lat1 ละติจูดจุดเริ่ม
     * @param float $lng1 ลองจิจูดจุดเริ่ม
     * @param float $lat2 ละติจูดจุดปลาย
     * @param float $lng2 ลองจิจูดจุดปลาย
     * @return float ระยะทาง (กม.)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * จัดการค่าประกัน - บันทึกการชำระ
     *
     * @param Rider $rider ไรเดอร์
     * @param float $amount จำนวนเงิน
     * @param string $transactionId หมายเลขอ้างอิง
     * @return bool
     */
    public function handleDepositPayment(Rider $rider, float $amount, string $transactionId): bool
    {
        if ($rider->deposit_status === 'paid') {
            Log::info('RiderDispatch: ไรเดอร์จ่ายค่าประกันแล้ว', ['rider_id' => $rider->id]);

            return false;
        }

        $rider->markDepositPaid($transactionId, $amount);

        Log::info('RiderDispatch: บันทึกค่าประกันสำเร็จ', [
            'rider_id' => $rider->id,
            'amount' => $amount,
            'transaction_id' => $transactionId,
        ]);

        return true;
    }

    /**
     * Auto-dispatch: หาไรเดอร์ใกล้สุดแล้วจ่ายงานอัตโนมัติ
     *
     * @param FreshMarketOrder $order คำสั่งซื้อ
     * @return RiderJob|null
     */
    public function autoDispatch(FreshMarketOrder $order): ?RiderJob
    {
        // ค้นหาไรเดอร์ใกล้ที่สุด
        $riders = $this->findNearestRiders($order, $this->defaultSearchRadius, 1);

        if ($riders->isEmpty()) {
            Log::info('RiderDispatch: ไม่พบไรเดอร์ใกล้เคียง', [
                'order_id' => $order->id,
            ]);

            return null;
        }

        $rider = $riders->first();

        return $this->dispatchToRider($order, $rider);
    }
}
