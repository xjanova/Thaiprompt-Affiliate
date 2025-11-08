<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\HotelSpecialOffer;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HotelPricingService
{
    protected $taxRate = 7; // 7% VAT
    protected $serviceChargeRate = 10; // 10% service charge

    /**
     * Calculate booking price
     */
    public function calculateBookingPrice(
        RoomType $roomType,
        $checkIn,
        $checkOut,
        $rooms = 1,
        $adults = 2,
        $children = 0,
        $promoCode = null
    ) {
        // Calculate room price
        $roomPrice = $this->calculateRoomPrice(
            $roomType,
            $checkIn,
            $checkOut,
            $rooms,
            $adults,
            $children
        );

        $subtotal = $roomPrice;

        // Apply discount if promo code provided
        $discount = 0;
        if ($promoCode) {
            $discount = $this->applyPromoCode(
                $roomType,
                $promoCode,
                $checkIn,
                $checkOut,
                $subtotal
            );
        }

        // Calculate tax and service charge
        $tax = $subtotal * ($this->taxRate / 100);
        $serviceCharge = $subtotal * ($this->serviceChargeRate / 100);

        // Calculate total
        $total = $subtotal + $tax + $serviceCharge - $discount;

        return [
            'room_price' => $roomPrice / $rooms, // Price per room per night
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'service_charge' => $serviceCharge,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'currency' => 'THB',
            'breakdown' => $this->getPriceBreakdown(
                $roomType,
                $checkIn,
                $checkOut,
                $rooms,
                $adults,
                $children
            ),
        ];
    }

    /**
     * Calculate room price for date range
     */
    protected function calculateRoomPrice(
        RoomType $roomType,
        $checkIn,
        $checkOut,
        $rooms = 1,
        $adults = 2,
        $children = 0
    ) {
        $period = CarbonPeriod::create($checkIn, $checkOut)->excludeEndDate();
        $totalPrice = 0;

        foreach ($period as $date) {
            $datePrice = $roomType->getPriceForDate($date->format('Y-m-d'));
            $totalPrice += $datePrice * $rooms;
        }

        // Add extra charges for adults
        if ($adults > $roomType->max_adults) {
            $extraAdults = $adults - $roomType->max_adults;
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
            $totalPrice += $extraAdults * $roomType->extra_adult_price * $nights * $rooms;
        }

        // Add extra charges for children
        if ($children > $roomType->max_children) {
            $extraChildren = $children - $roomType->max_children;
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
            $totalPrice += $extraChildren * $roomType->extra_child_price * $nights * $rooms;
        }

        return $totalPrice;
    }

    /**
     * Get detailed price breakdown by date
     */
    public function getPriceBreakdown(
        RoomType $roomType,
        $checkIn,
        $checkOut,
        $rooms = 1,
        $adults = 2,
        $children = 0
    ) {
        $period = CarbonPeriod::create($checkIn, $checkOut)->excludeEndDate();
        $breakdown = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $price = $roomType->getPriceForDate($dateStr);

            $breakdown[] = [
                'date' => $dateStr,
                'day_name' => $date->format('l'),
                'is_weekend' => $date->isWeekend(),
                'price_per_room' => $price,
                'rooms' => $rooms,
                'subtotal' => $price * $rooms,
            ];
        }

        // Add extra charges
        $extraCharges = [];

        if ($adults > $roomType->max_adults) {
            $extraAdults = $adults - $roomType->max_adults;
            $nights = count($breakdown);
            $extraCharges[] = [
                'type' => 'extra_adults',
                'description' => "Extra {$extraAdults} adult(s)",
                'amount' => $extraAdults * $roomType->extra_adult_price * $nights * $rooms,
            ];
        }

        if ($children > $roomType->max_children) {
            $extraChildren = $children - $roomType->max_children;
            $nights = count($breakdown);
            $extraCharges[] = [
                'type' => 'extra_children',
                'description' => "Extra {$extraChildren} child(ren)",
                'amount' => $extraChildren * $roomType->extra_child_price * $nights * $rooms,
            ];
        }

        return [
            'nights' => $breakdown,
            'extra_charges' => $extraCharges,
            'total_nights' => count($breakdown),
        ];
    }

    /**
     * Apply promo code
     */
    protected function applyPromoCode(
        RoomType $roomType,
        $code,
        $checkIn,
        $checkOut,
        $subtotal
    ) {
        $offer = HotelSpecialOffer::where('hotel_id', $roomType->hotel_id)
            ->where('code', $code)
            ->active()
            ->first();

        if (!$offer) {
            return 0;
        }

        // Validate offer
        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));

        if (!$offer->isValid($checkIn, $checkOut, $roomType->id, $subtotal)) {
            return 0;
        }

        // Calculate discount
        return $offer->calculateDiscount($subtotal);
    }

    /**
     * Get available offers for booking
     */
    public function getAvailableOffers(
        RoomType $roomType,
        $checkIn,
        $checkOut,
        $subtotal
    ) {
        return HotelSpecialOffer::where('hotel_id', $roomType->hotel_id)
            ->active()
            ->get()
            ->filter(function ($offer) use ($checkIn, $checkOut, $roomType, $subtotal) {
                return $offer->isValid($checkIn, $checkOut, $roomType->id, $subtotal);
            })
            ->map(function ($offer) use ($subtotal) {
                return [
                    'id' => $offer->id,
                    'name' => $offer->name,
                    'code' => $offer->code,
                    'description' => $offer->description,
                    'discount_type' => $offer->discount_type,
                    'discount_value' => $offer->discount_value,
                    'discount_amount' => $offer->calculateDiscount($subtotal),
                ];
            });
    }

    /**
     * Set custom tax rate
     */
    public function setTaxRate($rate)
    {
        $this->taxRate = $rate;
        return $this;
    }

    /**
     * Set custom service charge rate
     */
    public function setServiceChargeRate($rate)
    {
        $this->serviceChargeRate = $rate;
        return $this;
    }

    /**
     * Calculate average nightly rate
     */
    public function calculateAverageNightlyRate(
        RoomType $roomType,
        $checkIn,
        $checkOut
    ) {
        $period = CarbonPeriod::create($checkIn, $checkOut)->excludeEndDate();
        $totalPrice = 0;
        $nights = 0;

        foreach ($period as $date) {
            $totalPrice += $roomType->getPriceForDate($date->format('Y-m-d'));
            $nights++;
        }

        return $nights > 0 ? $totalPrice / $nights : 0;
    }
}
