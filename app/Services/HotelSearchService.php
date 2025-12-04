<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Province;
use App\Models\RoomType;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * HotelSearchService - บริการค้นหาโรงแรม
 *
 * รองรับการค้นหาแบบต่างๆ:
 * - ค้นหาตามจังหวัด/ภูมิภาค
 * - ค้นหาตามพิกัด GPS
 * - ค้นหาใกล้เคียงจากตำแหน่งปัจจุบัน
 * - ค้นหาตามราคา/ประเภท/ดาว
 */
class HotelSearchService
{
    /**
     * ค้นหาโรงแรมพร้อมตัวกรองต่างๆ
     *
     * @param array $params พารามิเตอร์การค้นหา
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function search(array $params)
    {
        $query = Hotel::query()->with(['roomTypes', 'facilities', 'province']);

        // กรองเฉพาะโรงแรมที่เปิดใช้งาน
        $query->active();

        // ========================================
        // GPS Location Search (ค้นหาจากพิกัด)
        // ========================================
        if (!empty($params['latitude']) && !empty($params['longitude'])) {
            $latitude = (float) $params['latitude'];
            $longitude = (float) $params['longitude'];
            $radius = (float) ($params['radius'] ?? 50); // รัศมีเริ่มต้น 50 กม.

            // ใช้ Haversine formula คำนวณระยะทาง
            $query->selectRaw('hotels.*,
                (6371 * acos(
                    cos(radians(?)) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) *
                    sin(radians(latitude))
                )) AS distance', [$latitude, $longitude, $latitude])
                ->having('distance', '<', $radius)
                ->orderBy('distance', 'asc');
        }

        // ========================================
        // Province Filter (กรองตามจังหวัด)
        // ========================================
        if (!empty($params['province_id'])) {
            $query->where('province_id', $params['province_id']);
        }

        // กรองตามภูมิภาค
        if (!empty($params['region'])) {
            $query->whereHas('province', function ($q) use ($params) {
                $q->where('region', $params['region']);
            });
        }

        // ========================================
        // City/Country Filter (กรองตามเมือง/ประเทศ)
        // ========================================
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
     * ค้นหาอัตโนมัติ (Autocomplete)
     *
     * รวมผลลัพธ์จาก:
     * - โรงแรม (ชื่อ, เมือง, ที่อยู่)
     * - จังหวัด (ชื่อไทย, ชื่ออังกฤษ)
     *
     * @param string $keyword คำค้นหา
     * @param int $limit จำนวนผลลัพธ์สูงสุด
     * @return array
     */
    public function autocomplete($keyword, $limit = 10)
    {
        $results = [];

        // ค้นหาจังหวัด
        $provinces = Province::active()
            ->search($keyword)
            ->ordered()
            ->limit(5)
            ->get()
            ->map(function ($province) {
                return [
                    'id' => $province->id,
                    'name' => $province->name_th,
                    'name_en' => $province->name_en,
                    'type' => 'province',
                    'region' => $province->region_name,
                    'hotel_count' => $province->hotel_count,
                    'latitude' => $province->latitude,
                    'longitude' => $province->longitude,
                ];
            });

        // ค้นหาโรงแรม
        $hotels = Hotel::active()
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('city', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('address', 'LIKE', '%' . $keyword . '%');
            })
            ->with('province:id,name_th,name_en')
            ->select('id', 'name', 'city', 'type', 'main_image', 'province_id', 'rating')
            ->limit($limit - $provinces->count())
            ->get()
            ->map(function ($hotel) {
                return [
                    'id' => $hotel->id,
                    'name' => $hotel->name,
                    'city' => $hotel->city,
                    'type' => 'hotel',
                    'hotel_type' => $hotel->type,
                    'province' => $hotel->province?->name_th,
                    'image' => $hotel->main_image_url,
                    'rating' => $hotel->rating,
                ];
            });

        return [
            'provinces' => $provinces,
            'hotels' => $hotels,
        ];
    }

    /**
     * ดึงจังหวัดที่มีโรงแรม (พร้อมจำนวน)
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProvincesWithHotels()
    {
        return Province::active()
            ->withCount(['hotels' => function ($query) {
                $query->where('is_active', true);
            }])
            ->having('hotels_count', '>', 0)
            ->ordered()
            ->get();
    }

    /**
     * ดึงโรงแรมในจังหวัด
     *
     * @param int $provinceId รหัสจังหวัด
     * @param int $limit จำนวนสูงสุด
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHotelsByProvince(int $provinceId, int $limit = 12)
    {
        return Hotel::active()
            ->where('province_id', $provinceId)
            ->with(['roomTypes' => function ($query) {
                $query->active()->orderBy('base_price', 'asc');
            }, 'province'])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงโรงแรมในภูมิภาค
     *
     * @param string $region รหัสภูมิภาค (central, north, northeast, east, west, south)
     * @param int $limit จำนวนสูงสุด
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHotelsByRegion(string $region, int $limit = 12)
    {
        return Hotel::active()
            ->whereHas('province', function ($query) use ($region) {
                $query->where('region', $region);
            })
            ->with(['roomTypes' => function ($query) {
                $query->active()->orderBy('base_price', 'asc');
            }, 'province'])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ดึงจังหวัดใกล้เคียงจากพิกัด GPS
     *
     * @param float $latitude ละติจูด
     * @param float $longitude ลองจิจูด
     * @param int $limit จำนวนจังหวัด
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getNearbyProvinces(float $latitude, float $longitude, int $limit = 5)
    {
        return Province::getNearbyProvinces($latitude, $longitude, $limit);
    }

    /**
     * ดึงจังหวัดตามภูมิภาค
     *
     * @param string|null $region รหัสภูมิภาค (null = ทั้งหมด)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProvincesByRegion(?string $region = null)
    {
        $query = Province::active()->ordered();

        if ($region) {
            $query->byRegion($region);
        }

        return $query->get();
    }

    /**
     * ดึงภูมิภาคทั้งหมดพร้อมจำนวนโรงแรม
     *
     * @return array
     */
    public function getRegionsWithHotelCount(): array
    {
        $regions = Province::REGIONS;
        $result = [];

        foreach ($regions as $code => $name) {
            $hotelCount = Hotel::active()
                ->whereHas('province', function ($query) use ($code) {
                    $query->where('region', $code);
                })
                ->count();

            $result[] = [
                'code' => $code,
                'name' => $name,
                'hotel_count' => $hotelCount,
            ];
        }

        return $result;
    }
}
