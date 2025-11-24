<?php

namespace App\Services;

use App\Models\ServicePricingRule;
use App\Models\Service;
use App\Models\ServiceBooking;

/**
 * ServicePricingService
 *
 * บริการคำนวณราคาสำหรับ Service Booking System
 * รองรับการคำนวณตามระยะทาง, ภาษี, ส่วนลด, และคอมมิชชั่น
 */
class ServicePricingService
{
    /**
     * คำนวณราคาค่าเดินทางตามระยะทาง
     *
     * @param float $distanceKm ระยะทาง (กิโลเมตร)
     * @param int|null $serviceId ID ของบริการ (null = ใช้กฎทั่วไป)
     * @return array ['price' => float, 'rule' => ServicePricingRule|null]
     */
    public function calculateDistancePrice(float $distanceKm, ?int $serviceId = null): array
    {
        // หากฎที่เหมาะสม (เรียงตาม priority)
        $rule = ServicePricingRule::active()
            ->when($serviceId, function ($query) use ($serviceId) {
                $query->forService($serviceId);
            })
            ->coveringDistance($distanceKm)
            ->ordered()
            ->first();

        // ถ้าไม่เจอกฎที่เหมาะสม ใช้ค่า default
        if (!$rule) {
            $defaultRate = config('services.default_price_per_km', 10);
            $price = $distanceKm * $defaultRate;

            return [
                'price' => round($price, 2),
                'rule' => null,
                'calculation' => "{$distanceKm} km × {$defaultRate}฿/km = {$price}฿",
            ];
        }

        // คำนวณตามกฎ
        $price = $rule->calculatePrice($distanceKm);

        return [
            'price' => $price,
            'rule' => $rule,
            'calculation' => $this->getCalculationExplanation($rule, $distanceKm, $price),
        ];
    }

    /**
     * คำนวณราคารวมทั้งหมดของการจอง
     *
     * @param array $data ข้อมูลการจอง
     * @return array
     */
    public function calculateTotalPrice(array $data): array
    {
        $basePrice = $data['base_price'] ?? 0;
        $distanceKm = $data['distance_km'] ?? 0;
        $serviceId = $data['service_id'] ?? null;
        $optionsPrice = $data['options_price'] ?? 0;
        $discountAmount = $data['discount_amount'] ?? 0;

        // คำนวณค่าเดินทาง
        $distancePricing = $this->calculateDistancePrice($distanceKm, $serviceId);
        $distancePrice = $distancePricing['price'];

        // รวมย่อย
        $subtotal = $basePrice + $distancePrice + $optionsPrice;

        // ภาษี VAT 7%
        $taxRate = config('services.tax_rate', 0.07);
        $taxAmount = $subtotal * $taxRate;

        // ราคารวมสุทธิ
        $totalAmount = $subtotal + $taxAmount - $discountAmount;

        return [
            'base_price' => round($basePrice, 2),
            'distance_km' => $distanceKm,
            'distance_price' => round($distancePrice, 2),
            'distance_pricing_rule' => $distancePricing['rule'],
            'distance_calculation' => $distancePricing['calculation'],
            'options_price' => round($optionsPrice, 2),
            'subtotal' => round($subtotal, 2),
            'tax_rate' => $taxRate,
            'tax_amount' => round($taxAmount, 2),
            'discount_amount' => round($discountAmount, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * คำนวณค่าออฟชั่นเพิ่มเติม
     *
     * @param Service $service
     * @param array $selectedOptions รายการออฟชั่นที่เลือก
     * @return array
     */
    public function calculateOptionsPrice(Service $service, array $selectedOptions = []): array
    {
        $totalPrice = 0;
        $optionDetails = [];

        if (empty($selectedOptions) || empty($service->options)) {
            return [
                'total_price' => 0,
                'options' => [],
            ];
        }

        foreach ($selectedOptions as $optionName) {
            // หาออฟชั่นที่ตรงกัน
            $option = collect($service->options)->firstWhere('name', $optionName);

            if ($option && isset($option['price'])) {
                $price = (float) $option['price'];
                $totalPrice += $price;

                $optionDetails[] = [
                    'name' => $option['name'],
                    'price' => $price,
                ];
            }
        }

        return [
            'total_price' => round($totalPrice, 2),
            'options' => $optionDetails,
        ];
    }

    /**
     * คำนวณคอมมิชชั่น MLM
     *
     * @param float $totalAmount ราคารวม
     * @param float $commissionRate อัตราคอมมิชชั่น (%)
     * @return float
     */
    public function calculateCommission(float $totalAmount, float $commissionRate): float
    {
        $commission = ($totalAmount * $commissionRate) / 100;
        return round($commission, 2);
    }

    /**
     * คำนวณส่วนลดจากคูปอง
     *
     * @param float $subtotal รวมย่อย
     * @param array $coupon ข้อมูลคูปอง
     * @return array
     */
    public function calculateDiscount(float $subtotal, ?array $coupon = null): array
    {
        if (!$coupon) {
            return [
                'discount_amount' => 0,
                'coupon_applied' => false,
            ];
        }

        $discountType = $coupon['type'] ?? 'percentage'; // percentage, fixed
        $discountValue = $coupon['value'] ?? 0;
        $maxDiscount = $coupon['max_discount'] ?? null;
        $minPurchase = $coupon['min_purchase'] ?? 0;

        // ตรวจสอบยอดขั้นต่ำ
        if ($subtotal < $minPurchase) {
            return [
                'discount_amount' => 0,
                'coupon_applied' => false,
                'error' => "ยอดซื้อขั้นต่ำ {$minPurchase}฿",
            ];
        }

        // คำนวณส่วนลด
        if ($discountType === 'percentage') {
            $discount = ($subtotal * $discountValue) / 100;

            // จำกัดส่วนลดสูงสุด
            if ($maxDiscount && $discount > $maxDiscount) {
                $discount = $maxDiscount;
            }
        } else {
            // ส่วนลดแบบคงที่
            $discount = $discountValue;
        }

        // ส่วนลดไม่เกินยอดรวม
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return [
            'discount_amount' => round($discount, 2),
            'coupon_applied' => true,
            'coupon_code' => $coupon['code'] ?? null,
        ];
    }

    /**
     * แสดงคำอธิบายการคำนวณ
     *
     * @param ServicePricingRule $rule
     * @param float $distanceKm
     * @param float $price
     * @return string
     */
    protected function getCalculationExplanation(ServicePricingRule $rule, float $distanceKm, float $price): string
    {
        $explanation = [];

        if ($rule->flat_fee > 0) {
            $explanation[] = "ค่าคงที่ {$rule->flat_fee}฿";
        }

        if ($rule->price_per_km > 0) {
            $explanation[] = "{$distanceKm} km × {$rule->price_per_km}฿/km = " . ($distanceKm * $rule->price_per_km) . "฿";
        }

        if (empty($explanation)) {
            return "ฟรี";
        }

        return implode(' + ', $explanation) . " = {$price}฿";
    }

    /**
     * ตรวจสอบราคาขั้นต่ำ
     *
     * @param float $totalAmount
     * @return bool
     */
    public function isAboveMinimumPrice(float $totalAmount): bool
    {
        $minimumPrice = config('services.minimum_booking_price', 50);
        return $totalAmount >= $minimumPrice;
    }

    /**
     * แยกราคาตามประเภทรายการ (สำหรับ ServiceBookingItem)
     *
     * @param ServiceBooking $booking
     * @return array
     */
    public function getItemizedPricing(ServiceBooking $booking): array
    {
        return [
            [
                'type' => 'service',
                'name' => $booking->service->name ?? 'บริการ',
                'quantity' => 1,
                'unit_price' => $booking->base_price,
                'subtotal' => $booking->base_price,
            ],
            [
                'type' => 'distance_fee',
                'name' => "ค่าเดินทาง ({$booking->distance_km} km)",
                'quantity' => 1,
                'unit_price' => $booking->distance_price,
                'subtotal' => $booking->distance_price,
            ],
            // ออฟชั่นจะถูกเพิ่มแยก
        ];
    }
}
