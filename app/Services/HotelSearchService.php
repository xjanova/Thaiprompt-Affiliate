<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\RoomType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HotelSearchService
{
    /**
     * Search hotels with filters
     */
    public function search(array $params)
    {
        $query = Hotel::query()->with(['roomTypes', 'facilities']);

        // Active hotels only
        $query->active();

        // Location filters
        if (!empty($params['city'])) {
            $query->where('city', 'LIKE', '%' . $params['city'] . '%');
        }

        if (!empty($params['country'])) {
            $query->where('country', $params['country']);
        }

        // Hotel type
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        // Star rating
        if (!empty($params['star_rating'])) {
            $query->where('star_rating', '>=', $params['star_rating']);
        }

        // Guest rating
        if (!empty($params['min_rating'])) {
            $query->where('rating', '>=', $params['min_rating']);
        }

        // Price range
        if (!empty($params['min_price']) || !empty($params['max_price'])) {
            $query->whereHas('roomTypes', function ($q) use ($params) {
                $q->active();
                if (!empty($params['min_price'])) {
                    $q->where('base_price', '>=', $params['min_price']);
                }
                if (!empty($params['max_price'])) {
                    $q->where('base_price', '<=', $params['max_price']);
                }
            });
        }

        // Facilities/Amenities
        if (!empty($params['facilities']) && is_array($params['facilities'])) {
            foreach ($params['facilities'] as $facilityId) {
                $query->whereHas('facilities', function ($q) use ($facilityId) {
                    $q->where('hotel_facilities.id', $facilityId);
                });
            }
        }

        // Availability check
        if (!empty($params['check_in']) && !empty($params['check_out'])) {
            $rooms = $params['rooms'] ?? 1;
            $query->whereHas('roomTypes', function ($q) use ($params, $rooms) {
                $q->active()
                    ->whereHas('availability', function ($avQuery) use ($params, $rooms) {
                        $avQuery->whereBetween('date', [$params['check_in'], $params['check_out']])
                            ->where('is_available', true)
                            ->where('available_rooms', '>=', $rooms);
                    });
            });
        }

        // Occupancy
        if (!empty($params['adults']) || !empty($params['children'])) {
            $adults = $params['adults'] ?? 2;
            $children = $params['children'] ?? 0;

            $query->whereHas('roomTypes', function ($q) use ($adults, $children) {
                $q->active()
                    ->where('max_adults', '>=', $adults)
                    ->where('max_children', '>=', $children);
            });
        }

        // Featured hotels
        if (!empty($params['featured'])) {
            $query->where('is_featured', true);
        }

        // Sorting
        $sortBy = $params['sort_by'] ?? 'popular';
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('(SELECT MIN(base_price) FROM room_types WHERE room_types.hotel_id = hotels.id AND room_types.is_active = 1) ASC');
                break;
            case 'price_high':
                $query->orderByRaw('(SELECT MIN(base_price) FROM room_types WHERE room_types.hotel_id = hotels.id AND room_types.is_active = 1) DESC');
                break;
            case 'rating':
                $query->orderBy('rating', 'DESC');
                break;
            case 'reviews':
                $query->orderBy('review_count', 'DESC');
                break;
            case 'name':
                $query->orderBy('name', 'ASC');
                break;
            case 'popular':
            default:
                $query->orderBy('booking_count', 'DESC')
                    ->orderBy('rating', 'DESC');
                break;
        }

        // Pagination
        $perPage = $params['per_page'] ?? 12;
        return $query->paginate($perPage);
    }

    /**
     * Get featured hotels
     */
    public function getFeaturedHotels($limit = 6)
    {
        return Hotel::active()
            ->featured()
            ->with(['roomTypes' => function ($query) {
                $query->active()->orderBy('base_price', 'asc');
            }])
            ->orderBy('rating', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular destinations
     */
    public function getPopularDestinations($limit = 10)
    {
        return Hotel::active()
            ->select('city', DB::raw('COUNT(*) as hotel_count'))
            ->groupBy('city')
            ->orderBy('hotel_count', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Get hotels near location
     */
    public function getNearbyHotels($latitude, $longitude, $radius = 10, $limit = 10)
    {
        // Using Haversine formula to calculate distance
        $hotels = Hotel::active()
            ->select('*')
            ->selectRaw('
                ( 6371 * acos( cos( radians(?) ) *
                cos( radians( latitude ) ) *
                cos( radians( longitude ) - radians(?) ) +
                sin( radians(?) ) *
                sin( radians( latitude ) ) ) ) AS distance',
                [$latitude, $longitude, $latitude]
            )
            ->having('distance', '<', $radius)
            ->orderBy('distance', 'ASC')
            ->limit($limit)
            ->get();

        return $hotels;
    }

    /**
     * Get related hotels
     */
    public function getRelatedHotels(Hotel $hotel, $limit = 6)
    {
        return Hotel::active()
            ->where('id', '!=', $hotel->id)
            ->where(function ($query) use ($hotel) {
                $query->where('city', $hotel->city)
                    ->orWhere('type', $hotel->type);
            })
            ->orderBy('rating', 'DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Autocomplete search
     */
    public function autocomplete($keyword, $limit = 10)
    {
        return Hotel::active()
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('city', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('address', 'LIKE', '%' . $keyword . '%');
            })
            ->select('id', 'name', 'city', 'type', 'main_image')
            ->limit($limit)
            ->get();
    }
}
