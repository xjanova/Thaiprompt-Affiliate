<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Models\HotelFacility;
use App\Models\Province;
use App\Services\HotelSearchService;
use App\Services\RoomAvailabilityService;
use Illuminate\Http\Request;

/**
 * HotelController - จัดการหน้าค้นหาและแสดงรายละเอียดโรงแรม
 *
 * รองรับ:
 * - ค้นหาตามจังหวัด/ภูมิภาค
 * - ค้นหาจาก GPS (ใกล้เคียง)
 * - กรองตามประเภท/ราคา/ดาว
 */
class HotelController extends Controller
{
    protected $searchService;
    protected $availabilityService;

    public function __construct(
        HotelSearchService $searchService,
        RoomAvailabilityService $availabilityService
    ) {
        $this->searchService = $searchService;
        $this->availabilityService = $availabilityService;
    }

    /**
     * แสดงหน้าค้นหาโรงแรม
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ค้นหาโรงแรม
        $hotels = $this->searchService->search($request->all());

        // ดึงข้อมูลสำหรับตัวกรอง
        $facilities = HotelFacility::active()->ordered()->get();
        $provinces = Province::active()->ordered()->get();
        $popularDestinations = $this->searchService->getPopularDestinations();
        $regions = $this->searchService->getRegionsWithHotelCount();

        // ดึงจังหวัดที่เลือก (ถ้ามี)
        $selectedProvince = null;
        if ($request->has('province_id')) {
            $selectedProvince = Province::find($request->province_id);
        }

        return view('hotels.index', compact(
            'hotels',
            'facilities',
            'provinces',
            'popularDestinations',
            'regions',
            'selectedProvince'
        ));
    }

    /**
     * แสดงรายละเอียดโรงแรม
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\View\View
     */
    public function show(Request $request, $slug)
    {
        $hotel = Hotel::where('slug', $slug)
            ->with([
                'roomTypes' => function ($query) {
                    $query->active()->orderBy('base_price', 'asc');
                },
                'facilities',
                'province',
                'reviews' => function ($query) {
                    $query->approved()->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->firstOrFail();

        // นับจำนวนการดู
        $hotel->incrementViews();

        // ตรวจสอบห้องว่าง (ถ้ามีวันที่)
        $availability = [];
        if ($request->has('check_in') && $request->has('check_out')) {
            foreach ($hotel->roomTypes as $roomType) {
                $availability[$roomType->id] = $this->availabilityService->checkAvailability(
                    $roomType,
                    $request->check_in,
                    $request->check_out,
                    $request->rooms ?? 1
                );
            }
        }

        // ดึงโรงแรมใกล้เคียง
        $relatedHotels = $this->searchService->getRelatedHotels($hotel);

        // ดึงโรงแรมในจังหวัดเดียวกัน
        $provinceHotels = null;
        if ($hotel->province_id) {
            $provinceHotels = Hotel::active()
                ->where('id', '!=', $hotel->id)
                ->where('province_id', $hotel->province_id)
                ->orderBy('rating', 'desc')
                ->limit(4)
                ->get();
        }

        return view('hotels.show', compact('hotel', 'availability', 'relatedHotels', 'provinceHotels'));
    }

    /**
     * ค้นหาโรงแรม (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function search(Request $request)
    {
        $hotels = $this->searchService->search($request->all());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'hotels' => $hotels,
            ]);
        }

        return redirect()->route('hotels.index', $request->all());
    }

    /**
     * ค้นหาโรงแรมใกล้เคียง (GPS)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nearby(Request $request)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:200',
        ]);

        $latitude = (float) $request->latitude;
        $longitude = (float) $request->longitude;
        $radius = (float) ($request->radius ?? 50);

        // ค้นหาโรงแรมใกล้เคียง
        $hotels = $this->searchService->getNearbyHotels($latitude, $longitude, $radius);

        // ค้นหาจังหวัดใกล้เคียง
        $nearestProvince = Province::getNearestProvince($latitude, $longitude);

        return response()->json([
            'success' => true,
            'hotels' => $hotels,
            'nearest_province' => $nearestProvince ? [
                'id' => $nearestProvince->id,
                'name' => $nearestProvince->name_th,
                'name_en' => $nearestProvince->name_en,
                'distance' => $nearestProvince->distance ?? null,
            ] : null,
            'location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'radius' => $radius,
            ],
        ]);
    }

    /**
     * Autocomplete ค้นหา
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocomplete(Request $request)
    {
        $keyword = $request->get('q', '');

        if (strlen($keyword) < 2) {
            return response()->json([
                'provinces' => [],
                'hotels' => [],
            ]);
        }

        $results = $this->searchService->autocomplete($keyword);

        return response()->json($results);
    }

    /**
     * แสดงโรงแรมแนะนำ
     *
     * @return \Illuminate\View\View
     */
    public function featured()
    {
        $hotels = $this->searchService->getFeaturedHotels();
        $provinces = Province::active()->ordered()->get();

        return view('hotels.featured', compact('hotels', 'provinces'));
    }

    /**
     * แสดงโรงแรมตามเมือง
     *
     * @param string $city
     * @return \Illuminate\View\View
     */
    public function byCity($city)
    {
        $hotels = $this->searchService->search(['city' => $city]);
        $facilities = HotelFacility::active()->ordered()->get();
        $provinces = Province::active()->ordered()->get();
        $popularDestinations = $this->searchService->getPopularDestinations();

        return view('hotels.index', compact('hotels', 'facilities', 'provinces', 'popularDestinations', 'city'));
    }

    /**
     * แสดงโรงแรมตามจังหวัด
     *
     * @param int $provinceId
     * @return \Illuminate\View\View
     */
    public function byProvince($provinceId)
    {
        $province = Province::findOrFail($provinceId);
        $hotels = $this->searchService->search(['province_id' => $provinceId]);
        $facilities = HotelFacility::active()->ordered()->get();
        $provinces = Province::active()->ordered()->get();
        $popularDestinations = $this->searchService->getPopularDestinations();

        return view('hotels.index', compact(
            'hotels',
            'facilities',
            'provinces',
            'popularDestinations',
            'province'
        ))->with('selectedProvince', $province);
    }

    /**
     * แสดงโรงแรมตามภูมิภาค
     *
     * @param string $region
     * @return \Illuminate\View\View
     */
    public function byRegion($region)
    {
        // ตรวจสอบว่า region ถูกต้อง
        if (!array_key_exists($region, Province::REGIONS)) {
            abort(404, 'ไม่พบภูมิภาคที่ระบุ');
        }

        $hotels = $this->searchService->search(['region' => $region]);
        $facilities = HotelFacility::active()->ordered()->get();
        $provinces = Province::active()->byRegion($region)->ordered()->get();
        $popularDestinations = $this->searchService->getPopularDestinations();
        $regionName = Province::REGIONS[$region];

        return view('hotels.index', compact(
            'hotels',
            'facilities',
            'provinces',
            'popularDestinations',
            'region',
            'regionName'
        ));
    }

    /**
     * ดึงรายการจังหวัด (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvinces(Request $request)
    {
        $region = $request->get('region');

        $provinces = $this->searchService->getProvincesByRegion($region);

        return response()->json([
            'success' => true,
            'provinces' => $provinces->map(function ($province) {
                return [
                    'id' => $province->id,
                    'name_th' => $province->name_th,
                    'name_en' => $province->name_en,
                    'region' => $province->region,
                    'region_name' => $province->region_name,
                    'hotel_count' => $province->hotel_count,
                ];
            }),
        ]);
    }

    /**
     * ตรวจสอบห้องว่าง (AJAX)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'rooms' => 'nullable|integer|min:1',
        ]);

        $roomType = \App\Models\RoomType::findOrFail($request->room_type_id);

        $availability = $this->availabilityService->checkAvailability(
            $roomType,
            $request->check_in,
            $request->check_out,
            $request->rooms ?? 1
        );

        $isAvailable = collect($availability)->every(fn($day) => $day['is_available']);

        return response()->json([
            'success' => true,
            'available' => $isAvailable,
            'availability' => $availability,
        ]);
    }
}
