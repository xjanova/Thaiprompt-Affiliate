<?php

namespace App\Services\Hotel;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Vendor;
use Illuminate\Support\Str;

class HotelService
{
    /**
     * Create a new hotel
     */
    public function createHotel(Vendor $vendor, array $data): Hotel
    {
        // Check if vendor has hotel management addon
        if (!$vendor->hasHotelManagement()) {
            throw new \Exception('Vendor does not have hotel management addon');
        }

        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter = 1;
        while (Hotel::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $data['vendor_id'] = $vendor->id;

        return Hotel::create($data);
    }

    /**
     * Update hotel information
     */
    public function updateHotel(Hotel $hotel, array $data): Hotel
    {
        // If updating slug, ensure uniqueness
        if (isset($data['slug']) && $data['slug'] !== $hotel->slug) {
            $originalSlug = $data['slug'];
            $counter = 1;
            while (Hotel::where('slug', $data['slug'])->where('id', '!=', $hotel->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $hotel->update($data);
        return $hotel->fresh();
    }

    /**
     * Create a room type for a hotel
     */
    public function createRoomType(Hotel $hotel, array $data): RoomType
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Ensure unique slug within hotel
        $originalSlug = $data['slug'];
        $counter = 1;
        while (RoomType::where('hotel_id', $hotel->id)->where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        $data['hotel_id'] = $hotel->id;

        return RoomType::create($data);
    }

    /**
     * Create multiple rooms for a room type
     */
    public function createRooms(Hotel $hotel, RoomType $roomType, array $roomNumbers): array
    {
        $rooms = [];

        foreach ($roomNumbers as $roomNumber) {
            // Check if room number already exists
            if (Room::where('hotel_id', $hotel->id)->where('room_number', $roomNumber)->exists()) {
                continue;
            }

            $rooms[] = Room::create([
                'hotel_id' => $hotel->id,
                'room_type_id' => $roomType->id,
                'room_number' => $roomNumber,
                'status' => 'available',
            ]);
        }

        return $rooms;
    }

    /**
     * Bulk create rooms with floor and number range
     */
    public function bulkCreateRooms(
        Hotel $hotel,
        RoomType $roomType,
        int $startFloor,
        int $endFloor,
        int $roomsPerFloor
    ): array {
        $rooms = [];

        for ($floor = $startFloor; $floor <= $endFloor; $floor++) {
            for ($room = 1; $room <= $roomsPerFloor; $room++) {
                $roomNumber = $floor . str_pad($room, 2, '0', STR_PAD_LEFT);

                // Check if room number already exists
                if (Room::where('hotel_id', $hotel->id)->where('room_number', $roomNumber)->exists()) {
                    continue;
                }

                $rooms[] = Room::create([
                    'hotel_id' => $hotel->id,
                    'room_type_id' => $roomType->id,
                    'room_number' => $roomNumber,
                    'floor' => (string) $floor,
                    'status' => 'available',
                ]);
            }
        }

        return $rooms;
    }

    /**
     * Get hotel availability for date range
     */
    public function getAvailability(Hotel $hotel, $checkIn, $checkOut)
    {
        $roomTypes = $hotel->roomTypes()->active()->get();

        $availability = [];

        foreach ($roomTypes as $roomType) {
            $availableRooms = $roomType->getAvailableRooms($checkIn, $checkOut);

            if ($availableRooms > 0) {
                $availability[] = [
                    'room_type' => $roomType,
                    'available_rooms' => $availableRooms,
                    'prices' => $this->calculatePriceRange($roomType, $checkIn, $checkOut),
                ];
            }
        }

        return $availability;
    }

    /**
     * Calculate price for date range
     */
    public function calculatePriceRange(RoomType $roomType, $checkIn, $checkOut): array
    {
        $checkInDate = \Carbon\Carbon::parse($checkIn);
        $checkOutDate = \Carbon\Carbon::parse($checkOut);
        $nights = $checkInDate->diffInDays($checkOutDate);

        $total = 0;
        $priceBreakdown = [];

        $currentDate = $checkInDate->copy();
        while ($currentDate < $checkOutDate) {
            $price = $roomType->calculatePrice($currentDate);
            $total += $price;
            $priceBreakdown[] = [
                'date' => $currentDate->toDateString(),
                'price' => $price,
            ];
            $currentDate->addDay();
        }

        return [
            'nights' => $nights,
            'total' => round($total, 2),
            'average_per_night' => $nights > 0 ? round($total / $nights, 2) : 0,
            'breakdown' => $priceBreakdown,
        ];
    }

    /**
     * Get hotel statistics
     */
    public function getHotelStats(Hotel $hotel): array
    {
        $totalRooms = $hotel->rooms()->count();
        $availableRooms = $hotel->rooms()->where('status', 'available')->count();
        $occupiedRooms = $hotel->rooms()->where('status', 'occupied')->count();

        $todayBookings = $hotel->bookings()
            ->whereDate('check_in_date', today())
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        $upcomingBookings = $hotel->bookings()
            ->where('check_in_date', '>', today())
            ->where('status', 'confirmed')
            ->count();

        $currentGuests = $hotel->bookings()
            ->where('status', 'checked_in')
            ->sum('adults');

        $totalRevenue = $hotel->bookings()
            ->where('payment_status', 'paid')
            ->sum('total_amount');

        $monthlyRevenue = $hotel->bookings()
            ->where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->sum('total_amount');

        return [
            'total_rooms' => $totalRooms,
            'available_rooms' => $availableRooms,
            'occupied_rooms' => $occupiedRooms,
            'occupancy_rate' => $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0,
            'today_checkins' => $todayBookings,
            'upcoming_bookings' => $upcomingBookings,
            'current_guests' => $currentGuests,
            'total_revenue' => $totalRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'average_rating' => $hotel->average_rating,
            'total_reviews' => $hotel->total_reviews,
        ];
    }

    /**
     * Search hotels
     */
    public function searchHotels(array $filters)
    {
        $query = Hotel::query()->active()->with(['vendor', 'roomTypes']);

        // Location filters
        if (!empty($filters['city'])) {
            $query->where('city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        // Hotel type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Star rating
        if (!empty($filters['min_stars'])) {
            $query->where('star_rating', '>=', $filters['min_stars']);
        }

        // Amenities
        if (!empty($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        // Price range (requires checking room types)
        if (!empty($filters['min_price']) || !empty($filters['max_price'])) {
            $query->whereHas('roomTypes', function ($q) use ($filters) {
                if (!empty($filters['min_price'])) {
                    $q->where('base_price', '>=', $filters['min_price']);
                }
                if (!empty($filters['max_price'])) {
                    $q->where('base_price', '<=', $filters['max_price']);
                }
            });
        }

        // Availability for dates
        if (!empty($filters['check_in']) && !empty($filters['check_out'])) {
            $query->whereHas('rooms', function ($q) use ($filters) {
                $q->where('status', 'available')
                    ->whereDoesntHave('bookings', function ($q2) use ($filters) {
                        $q2->where(function ($q3) use ($filters) {
                            $q3->whereBetween('check_in_date', [$filters['check_in'], $filters['check_out']])
                                ->orWhereBetween('check_out_date', [$filters['check_in'], $filters['check_out']])
                                ->orWhere(function ($q4) use ($filters) {
                                    $q4->where('check_in_date', '<=', $filters['check_in'])
                                        ->where('check_out_date', '>=', $filters['check_out']);
                                });
                        })->whereIn('status', ['confirmed', 'checked_in']);
                    });
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'rating';
        switch ($sortBy) {
            case 'price_low':
                $query->join('room_types', 'hotels.id', '=', 'room_types.hotel_id')
                    ->select('hotels.*')
                    ->groupBy('hotels.id')
                    ->orderByRaw('MIN(room_types.base_price) ASC');
                break;
            case 'price_high':
                $query->join('room_types', 'hotels.id', '=', 'room_types.hotel_id')
                    ->select('hotels.*')
                    ->groupBy('hotels.id')
                    ->orderByRaw('MAX(room_types.base_price) DESC');
                break;
            case 'rating':
                $query->orderByDesc('average_rating');
                break;
            case 'popularity':
                $query->orderByDesc('total_bookings');
                break;
            default:
                $query->orderByDesc('created_at');
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }
}
