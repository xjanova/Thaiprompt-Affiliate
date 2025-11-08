<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\RoomAvailability;
use App\Models\HotelBooking;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class RoomAvailabilityService
{
    /**
     * Initialize availability for room type
     */
    public function initializeAvailability(RoomType $roomType, $startDate, $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $data = [];

        foreach ($period as $date) {
            $data[] = [
                'room_type_id' => $roomType->id,
                'date' => $date->format('Y-m-d'),
                'available_rooms' => $roomType->total_rooms,
                'price' => $this->getPriceForDate($roomType, $date),
                'is_available' => true,
                'min_stay' => 1,
                'max_stay' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Use upsert to avoid duplicates
        RoomAvailability::upsert(
            $data,
            ['room_type_id', 'date'],
            ['available_rooms', 'price', 'is_available', 'updated_at']
        );

        return true;
    }

    /**
     * Get price for specific date
     */
    protected function getPriceForDate(RoomType $roomType, Carbon $date)
    {
        // Check if it's weekend
        if ($date->isWeekend() && $roomType->weekend_price) {
            return $roomType->weekend_price;
        }

        return $roomType->base_price;
    }

    /**
     * Check availability for date range
     */
    public function checkAvailability(RoomType $roomType, $checkIn, $checkOut, $roomsNeeded = 1)
    {
        $period = CarbonPeriod::create($checkIn, $checkOut)->excludeEndDate();
        $availability = [];

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $available = $this->getAvailableRooms($roomType, $dateStr);

            $availability[$dateStr] = [
                'date' => $dateStr,
                'available_rooms' => $available,
                'is_available' => $available >= $roomsNeeded,
                'price' => $this->getPriceForSpecificDate($roomType, $dateStr),
            ];
        }

        return $availability;
    }

    /**
     * Get available rooms for specific date
     */
    public function getAvailableRooms(RoomType $roomType, $date)
    {
        // Try to get from availability table
        $availability = RoomAvailability::where('room_type_id', $roomType->id)
            ->where('date', $date)
            ->first();

        if ($availability) {
            return $availability->available_rooms;
        }

        // Calculate based on bookings
        $bookedRooms = HotelBooking::where('room_type_id', $roomType->id)
            ->whereIn('status', ['confirmed', 'checked_in', 'pending'])
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->sum('number_of_rooms');

        return max(0, $roomType->total_rooms - $bookedRooms);
    }

    /**
     * Get price for specific date
     */
    public function getPriceForSpecificDate(RoomType $roomType, $date)
    {
        // Try to get from availability table
        $availability = RoomAvailability::where('room_type_id', $roomType->id)
            ->where('date', $date)
            ->first();

        if ($availability) {
            return $availability->price;
        }

        // Use room type's base/weekend price
        $carbonDate = Carbon::parse($date);
        if ($carbonDate->isWeekend() && $roomType->weekend_price) {
            return $roomType->weekend_price;
        }

        return $roomType->base_price;
    }

    /**
     * Update availability after booking
     */
    public function decreaseAvailability(HotelBooking $booking)
    {
        $period = CarbonPeriod::create(
            $booking->check_in_date,
            $booking->check_out_date
        )->excludeEndDate();

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            $availability = RoomAvailability::where('room_type_id', $booking->room_type_id)
                ->where('date', $dateStr)
                ->first();

            if ($availability) {
                $availability->decreaseAvailability($booking->number_of_rooms);
            } else {
                // Create availability record if doesn't exist
                $this->initializeAvailability(
                    $booking->roomType,
                    $dateStr,
                    $dateStr
                );

                // Try again
                $availability = RoomAvailability::where('room_type_id', $booking->room_type_id)
                    ->where('date', $dateStr)
                    ->first();

                if ($availability) {
                    $availability->decreaseAvailability($booking->number_of_rooms);
                }
            }
        }

        return true;
    }

    /**
     * Restore availability after cancellation
     */
    public function increaseAvailability(HotelBooking $booking)
    {
        $period = CarbonPeriod::create(
            $booking->check_in_date,
            $booking->check_out_date
        )->excludeEndDate();

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            $availability = RoomAvailability::where('room_type_id', $booking->room_type_id)
                ->where('date', $dateStr)
                ->first();

            if ($availability) {
                $availability->increaseAvailability($booking->number_of_rooms);
            }
        }

        return true;
    }

    /**
     * Bulk update prices for date range
     */
    public function updatePrices(RoomType $roomType, $startDate, $endDate, $price)
    {
        RoomAvailability::where('room_type_id', $roomType->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->update(['price' => $price]);

        return true;
    }

    /**
     * Block dates (set unavailable)
     */
    public function blockDates(RoomType $roomType, $startDate, $endDate)
    {
        RoomAvailability::where('room_type_id', $roomType->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->update([
                'is_available' => false,
                'available_rooms' => 0,
            ]);

        return true;
    }

    /**
     * Unblock dates (set available)
     */
    public function unblockDates(RoomType $roomType, $startDate, $endDate)
    {
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            $availability = RoomAvailability::where('room_type_id', $roomType->id)
                ->where('date', $dateStr)
                ->first();

            if ($availability) {
                $availableRooms = $this->getAvailableRooms($roomType, $dateStr);
                $availability->update([
                    'is_available' => $availableRooms > 0,
                    'available_rooms' => $availableRooms,
                ]);
            }
        }

        return true;
    }

    /**
     * Get calendar data for room type
     */
    public function getCalendarData(RoomType $roomType, $month, $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        return RoomAvailability::where('room_type_id', $roomType->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'ASC')
            ->get()
            ->keyBy('date');
    }
}
