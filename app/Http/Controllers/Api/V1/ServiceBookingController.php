<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Models\ServiceProvider;
use App\Services\ServiceBookingService;
use App\Services\ServicePricingService;
use Illuminate\Http\Request;

/**
 * ServiceBookingController - API V1 (Mobile App)
 *
 * RESTful API สำหรับ Mobile Application
 */
class ServiceBookingController extends Controller
{
    public function __construct(
        protected ServiceBookingService $bookingService,
        protected ServicePricingService $pricingService
    ) {
    }

    /**
     * หมวดหมู่บริการทั้งหมด
     *
     * GET /api/v1/service-categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories()
    {
        $categories = ServiceCategory::active()
            ->ordered()
            ->withCount('activeServices')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * บริการในหมวดหมู่
     *
     * GET /api/v1/service-categories/{category}/services
     *
     * @param ServiceCategory $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function categoryServices(ServiceCategory $category)
    {
        $services = Service::where('category_id', $category->id)
            ->active()
            ->with('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $services,
        ]);
    }

    /**
     * รายละเอียดบริการ
     *
     * GET /api/v1/services/{service}
     *
     * @param Service $service
     * @return \Illuminate\Http\JsonResponse
     */
    public function showService(Service $service)
    {
        $service->load(['category', 'pricingRules']);
        $service->loadCount('reviews');
        $service->incrementViewCount();

        return response()->json([
            'success' => true,
            'data' => $service,
        ]);
    }

    /**
     * คำนวณราคา
     *
     * POST /api/v1/services/calculate-price
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function calculatePrice(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'distance_km' => 'required|numeric|min:0',
            'selected_options' => 'nullable|array',
        ]);

        $service = Service::findOrFail($validated['service_id']);

        $optionsPricing = $this->pricingService->calculateOptionsPrice(
            $service,
            $validated['selected_options'] ?? []
        );

        $pricing = $this->pricingService->calculateTotalPrice([
            'base_price' => $service->base_price,
            'distance_km' => $validated['distance_km'],
            'service_id' => $service->id,
            'options_price' => $optionsPricing['total_price'],
            'discount_amount' => 0,
        ]);

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    /**
     * สร้างการจอง
     *
     * POST /api/v1/bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createBooking(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date|after:now',
            'customer_latitude' => 'required|numeric',
            'customer_longitude' => 'required|numeric',
            'customer_address' => 'required|string|max:500',
            'building_name' => 'nullable|string|max:255',
            'floor_number' => 'nullable|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'selected_options' => 'nullable|array',
            'customer_notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|in:wallet,promptpay,card',
        ]);

        $validated['user'] = auth()->user();

        try {
            $booking = $this->bookingService->createBooking($validated);
            $this->bookingService->processPayment($booking, $validated['payment_method']);
            $this->bookingService->assignProvider($booking);

            return response()->json([
                'success' => true,
                'message' => 'จองบริการสำเร็จ',
                'data' => $booking->load(['service', 'provider']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * รายการจองของฉัน
     *
     * GET /api/v1/bookings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myBookings(Request $request)
    {
        $query = ServiceBooking::where('user_id', auth()->id())
            ->with(['service', 'provider']);

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        $bookings = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * รายละเอียดการจอง
     *
     * GET /api/v1/bookings/{booking}
     *
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function showBooking(ServiceBooking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        $booking->load([
            'service',
            'provider',
            'items',
            'locations',
            'trackings' => fn($q) => $q->latest()->limit(10),
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    /**
     * ติดตามสถานะแบบ Real-time
     *
     * GET /api/v1/bookings/{booking}/track
     *
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackBooking(ServiceBooking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        $latestTracking = $booking->trackings()->latest()->first();

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                    'status_text' => $booking->status_text,
                ],
                'provider' => $booking->provider ? [
                    'id' => $booking->provider->id,
                    'name' => $booking->provider->name,
                    'phone' => $booking->provider->user->phone ?? null,
                    'current_location' => [
                        'latitude' => $booking->provider->current_latitude,
                        'longitude' => $booking->provider->current_longitude,
                        'updated_at' => $booking->provider->last_location_update,
                    ],
                ] : null,
                'latest_tracking' => $latestTracking,
            ],
        ]);
    }

    /**
     * ยกเลิกการจอง
     *
     * POST /api/v1/bookings/{booking}/cancel
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelBooking(Request $request, ServiceBooking $booking)
    {
        if ($booking->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->bookingService->cancelBooking(
                $booking,
                $validated['reason'],
                'customer'
            );

            return response()->json([
                'success' => true,
                'message' => 'ยกเลิกการจองสำเร็จ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    //===========================================
    // Provider APIs
    //===========================================

    /**
     * Provider: รับงาน
     *
     * POST /api/v1/provider/bookings/{booking}/accept
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function providerAccept(Request $request, ServiceBooking $booking)
    {
        $provider = ServiceProvider::where('user_id', auth()->id())->first();

        if (!$provider || $booking->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->bookingService->acceptBooking($booking, $validated['notes'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'รับงานสำเร็จ',
                'data' => $booking->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Provider: ปฏิเสธงาน
     *
     * POST /api/v1/provider/bookings/{booking}/reject
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function providerReject(Request $request, ServiceBooking $booking)
    {
        $provider = ServiceProvider::where('user_id', auth()->id())->first();

        if (!$provider || $booking->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->bookingService->rejectBooking($booking, $validated['reason']);

            return response()->json([
                'success' => true,
                'message' => 'ปฏิเสธงานแล้ว',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Provider: อัพเดทตำแหน่ง GPS
     *
     * POST /api/v1/provider/bookings/{booking}/update-location
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function providerUpdateLocation(Request $request, ServiceBooking $booking)
    {
        $provider = ServiceProvider::where('user_id', auth()->id())->first();

        if (!$provider || $booking->provider_id !== $provider->id) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มีสิทธิ์เข้าถึง',
            ], 403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $this->bookingService->updateProviderLocation(
            $booking,
            $validated['latitude'],
            $validated['longitude']
        );

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทตำแหน่งสำเร็จ',
        ]);
    }
}
