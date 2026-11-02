<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceCategory;
use App\Services\ServiceBookingService;
use App\Services\ServicePricingService;
use Illuminate\Http\Request;

/**
 * ServiceBookingController - จองบริการ (User/Customer)
 *
 * ค้นหา, จอง, ติดตามสถานะ, รีวิว
 */
class ServiceBookingController extends Controller
{
    /**
     * กลุ่มสถานะการจองสำหรับแท็บกรองในหน้ารายการจองของสมาชิก
     */
    private const BOOKING_GROUPS = [
        'waiting' => ['pending', 'paid', 'notifying_provider', 'waiting_provider'],
        'active' => ['provider_accepted', 'provider_on_way', 'in_progress'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled', 'provider_rejected'],
    ];

    /**
     * สถานะที่ลูกค้าเปิดหน้าติดตามแบบสด/แชร์ตำแหน่งได้ (ผู้ให้บริการรับงานแล้ว จนถึงกำลังให้บริการ)
     */
    private const TRACKABLE_STATUSES = ['provider_accepted', 'provider_on_way', 'in_progress'];

    public function __construct(
        protected ServiceBookingService $bookingService,
        protected ServicePricingService $pricingService
    ) {}

    /**
     * หน้าแรก - ค้นหาบริการ (Service Discovery)
     *
     * รองรับการค้นหา, กรองตามหมวดหมู่, ราคา, ระยะทาง และคะแนน
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ดึงหมวดหมู่ที่เปิดใช้งานทั้งหมด พร้อมนับจำนวนบริการ
        $categories = ServiceCategory::active()
            ->ordered()
            ->withCount(['services' => fn ($q) => $q->active()])
            ->get();

        // สร้าง Query สำหรับบริการ
        $query = Service::query()
            ->active()
            ->with(['category']);

        // ค้นหาด้วยคำค้น
        if ($request->filled('q')) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        // กรองตามหมวดหมู่
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // กรองตามราคา (ช่วงราคา)
        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // กรองตามคะแนน (ขั้นต่ำ)
        if ($request->filled('min_rating')) {
            $query->where('average_rating', '>=', $request->min_rating);
        }

        // เรียงลำดับ
        $sortBy = $request->input('sort', 'featured');
        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('base_price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('base_price', 'desc');
                break;
            case 'rating':
                $query->orderBy('average_rating', 'desc');
                break;
            case 'popular':
                $query->orderBy('booking_count', 'desc');
                break;
            case 'newest':
                $query->latest();
                break;
            default:
                $query->orderBy('is_featured', 'desc')
                    ->orderBy('booking_count', 'desc');
        }

        // Pagination
        $services = $query->paginate(12)->withQueryString();

        // ดึงบริการแนะนำ (featured) สำหรับแสดงส่วน Hero
        $featuredServices = Service::active()
            ->featured()
            ->with('category')
            ->limit(6)
            ->get();

        // ดึงบริการยอดนิยม
        $popularServices = Service::active()
            ->with('category')
            ->orderBy('booking_count', 'desc')
            ->limit(8)
            ->get();

        // สถิติ
        $stats = [
            'total_services' => Service::active()->count(),
            'total_categories' => ServiceCategory::active()->count(),
            'total_providers' => \App\Models\ServiceProvider::where('status', 'available')->count(),
            'total_bookings' => \App\Models\ServiceBooking::count(),
        ];

        return view('user.services.index', [
            'categories' => $categories,
            'services' => $services,
            'featuredServices' => $featuredServices,
            'popularServices' => $popularServices,
            'stats' => $stats,
            'filters' => [
                'q' => $request->q,
                'category' => $request->category,
                'min_price' => $request->min_price,
                'max_price' => $request->max_price,
                'min_rating' => $request->min_rating,
                'sort' => $sortBy,
            ],
            'pageTitle' => $request->filled('q')
                ? "ผลการค้นหา: {$request->q}"
                : 'ค้นหาบริการ',
        ]);
    }

    /**
     * รายการบริการในหมวดหมู่
     *
     * @return \Illuminate\View\View
     */
    public function category(ServiceCategory $category)
    {
        $services = Service::where('category_id', $category->id)
            ->active()
            ->with('category')
            ->paginate(12);

        return view('user.services.category', [
            'category' => $category,
            'services' => $services,
            'pageTitle' => $category->name,
        ]);
    }

    /**
     * รายละเอียดบริการ
     *
     * @return \Illuminate\View\View
     */
    public function show(Service $service)
    {
        $service->load(['category', 'pricingRules']);
        $service->loadCount(['reviews']);
        $service->incrementViewCount();

        // คำนวณ PV และ Cashback ที่ลูกค้าจะได้รับ
        $earningsSummary = $this->calculateEarningsPreview($service);

        return view('user.services.show', [
            'service' => $service,
            'pageTitle' => $service->name,
            'earningsSummary' => $earningsSummary,
        ]);
    }

    /**
     * ฟอร์มจองบริการ
     *
     * @return \Illuminate\View\View
     */
    public function book(Service $service)
    {
        // ตรวจสอบว่าบริการต้องการตำแหน่งหรือไม่
        if ($service->requires_location && ! request()->has(['latitude', 'longitude'])) {
            return redirect()
                ->back()
                ->with('error', 'บริการนี้ต้องระบุตำแหน่ง GPS กรุณาเปิดตำแหน่งของคุณ');
        }

        return view('user.services.book', [
            'service' => $service,
            'pageTitle' => 'จอง: '.$service->name,
        ]);
    }

    /**
     * บันทึกการจอง
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeBooking(Request $request, Service $service)
    {
        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'customer_latitude' => $service->requires_location ? 'required|numeric' : 'nullable|numeric',
            'customer_longitude' => $service->requires_location ? 'required|numeric' : 'nullable|numeric',
            'customer_address' => 'nullable|string|max:500',
            'building_name' => 'nullable|string|max:255',
            'floor_number' => 'nullable|string|max:50',
            'room_number' => 'nullable|string|max:50',
            'landmark' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'selected_options' => 'nullable|array',
            'customer_notes' => 'nullable|string|max:1000',
            // 🪙 tpix = จ่ายด้วยเหรียญ TPIX (เหรียญเข้ากระเป๋าก่อน แล้วชำระการจองจากกระเป๋าให้อัตโนมัติ)
            'payment_method' => 'required|string|in:wallet,promptpay,card,tpix',
        ]);

        $validated['service_id'] = $service->id;
        $validated['user'] = auth()->user();

        try {
            // สร้างการจอง
            $booking = $this->bookingService->createBooking($validated);

            // 🪙 เหรียญ TPIX: การจองรอชำระ → หน้าจ่าย (ได้เหรียญแล้วระบบชำระ + หาผู้ให้บริการให้)
            if ($validated['payment_method'] === 'tpix') {
                try {
                    $quote = app(\App\Services\TpixPay\TpixCheckoutService::class)->quoteServiceBooking($booking);
                } catch (\App\Services\TpixPay\TpixPayException $e) {
                    return redirect()
                        ->route('user.bookings.show', $booking)
                        ->with('error', 'สร้างการจองแล้ว แต่ออกยอดชำระด้วย TPIX ไม่สำเร็จ: '.$e->getMessage());
                }

                return redirect()->route('tpix-pay.show', $quote->reference);
            }

            // ชำระเงิน
            $this->bookingService->processPayment($booking, $validated['payment_method']);

            // มอบหมาย provider อัตโนมัติ
            $this->bookingService->assignProvider($booking);

            return redirect()
                ->route('user.bookings.show', $booking)
                ->with('success', 'จองบริการสำเร็จ! กำลังแจ้งเตือนผู้ให้บริการ');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'เกิดข้อผิดพลาด: '.$e->getMessage());
        }
    }

    /**
     * รายการจองของฉัน
     *
     * @return \Illuminate\View\View
     */
    public function myBookings(Request $request)
    {
        $userId = auth()->id();
        $query = ServiceBooking::where('user_id', $userId)
            ->with(['service.category', 'provider']);

        // กรองตามกลุ่มสถานะ (แท็บ) หรือสถานะเดี่ยว (ลิงก์เดิม ?status=pending)
        // 🐛 เดิมส่ง ?status[]=x แล้ว scopeStatus(string) โยน TypeError = 500 · แท็บ "กำลังทำ" เห็นแค่ in_progress
        //    ทั้งที่งานที่ผู้ให้บริการรับแล้ว/กำลังเดินทางก็อยู่ระหว่างทำ → รับเฉพาะข้อความ + กรองเป็นกลุ่ม
        $status = is_string($request->query('status')) ? $request->query('status') : '';
        if (isset(self::BOOKING_GROUPS[$status])) {
            $query->whereIn('status', self::BOOKING_GROUPS[$status]);
        } elseif ($status !== '') {
            $query->status($status);
        }

        $bookings = $query->latest()->paginate(10)->withQueryString();

        // 🐛 หน้าเดิมแสดง $stats แต่ไม่เคยถูกส่งมา (ตัวเลขบนหัวหน้าเป็น 0 เสมอ)
        $counts = ServiceBooking::where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $stats = ['total' => (int) $counts->sum()];
        foreach (self::BOOKING_GROUPS as $group => $statuses) {
            $stats[$group] = (int) $counts->only($statuses)->sum();
        }

        return view('user.bookings.index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'status' => $status,
            'pageTitle' => 'การจองของฉัน',
        ]);
    }

    /**
     * รายละเอียดการจอง
     *
     * @return \Illuminate\View\View
     */
    public function showBooking(ServiceBooking $booking)
    {
        // ตรวจสอบว่าเป็นเจ้าของการจอง
        if ((int) $booking->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $booking->load([
            'service.category',
            'provider',
            'items',
            'locations',
            'trackings' => fn ($q) => $q->latest()->limit(10),
        ]);

        return view('user.bookings.show', [
            'booking' => $booking,
            'pageTitle' => 'การจอง #'.$booking->booking_number,
        ]);
    }

    /**
     * ยกเลิกการจอง
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelBooking(Request $request, ServiceBooking $booking)
    {
        // ตรวจสอบว่าเป็นเจ้าของการจอง
        if ((int) $booking->user_id !== (int) auth()->id()) {
            abort(403);
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

            return redirect()
                ->route('user.bookings.show', $booking)
                ->with('success', 'ยกเลิกการจองสำเร็จ');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * คำนวณราคาตัวอย่าง (API)
     *
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

        // คำนวณค่าออฟชั่น
        $optionsPricing = $this->pricingService->calculateOptionsPrice(
            $service,
            $validated['selected_options'] ?? []
        );

        // คำนวณราคารวม
        $pricing = $this->pricingService->calculateTotalPrice([
            'base_price' => $service->base_price,
            'distance_km' => $validated['distance_km'],
            'service_id' => $service->id,
            'options_price' => $optionsPricing['total_price'],
            'discount_amount' => 0,
        ]);

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
            'options' => $optionsPricing['options'],
        ]);
    }

    /**
     * ติดตามสถานะแบบ Real-time
     *
     * - เปิดจากเบราว์เซอร์ (ลิงก์ "ติดตาม") → หน้าแผนที่ติดตาม user.bookings.track
     *   🐛 เดิมคืน JSON อย่างเดียว ปุ่ม "ติดตาม" ในหน้ารายการ/รายละเอียดเลยพาไปหน้าข้อความ JSON ดิบ
     *   และหน้า track.blade.php ไม่มีใคร render
     * - เรียกแบบ AJAX (Accept: application/json) → JSON ตำแหน่ง/สถานะ (หน้าติดตามโพลทุก 15 วินาที)
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function trackBooking(Request $request, ServiceBooking $booking)
    {
        // ตรวจสอบว่าเป็นเจ้าของการจอง
        if ((int) $booking->user_id !== (int) auth()->id()) {
            abort(403);
        }

        $booking->loadMissing(['service.category', 'provider', 'locations']);
        $trackable = in_array($booking->status, self::TRACKABLE_STATUSES, true);

        if (! $request->expectsJson()) {
            return view('user.bookings.track', [
                'booking' => $booking,
                'serviceLocation' => $booking->locations->firstWhere('type', 'service_location'),
                'trackable' => $trackable,
                'pageTitle' => 'ติดตามการจอง #'.$booking->booking_number,
            ]);
        }

        $latestTracking = $booking->trackings()->latest()->first();

        // 🔒 ตำแหน่งผู้ให้บริการส่งเฉพาะช่วงที่รับงานแล้วจนถึงกำลังให้บริการ (ก่อนรับงาน/จบงานแล้วไม่ต้องรู้ตำแหน่ง)
        $providerLocation = $booking->provider && $trackable && $booking->provider->current_latitude ? [
            'latitude' => $booking->provider->current_latitude,
            'longitude' => $booking->provider->current_longitude,
            'updated_at' => $booking->provider->last_location_update,
        ] : null;

        return response()->json([
            'success' => true,
            'booking' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'status_text' => $booking->status_text,
                'trackable' => $trackable,
            ],
            // 🔒 เบอร์ที่ผู้ให้บริการตั้งไว้สำหรับติดต่องาน (เดิมส่งเบอร์ส่วนตัวในบัญชีผู้ใช้ของผู้ให้บริการ)
            'provider' => $booking->provider ? [
                'name' => $booking->provider->display_name ?: $booking->provider->name,
                'phone' => $booking->provider->phone,
            ] : null,
            // 🔒 ส่งเฉพาะฟิลด์ที่หน้าติดตามใช้ (เดิมส่งทั้งแถว รวมความเร็ว/แบตเตอรี่เครื่องผู้ให้บริการ)
            'latest_tracking' => $latestTracking ? [
                'status' => $latestTracking->status,
                'distance_to_customer_km' => $latestTracking->distance_to_customer_km,
                'estimated_arrival_minutes' => $latestTracking->estimated_arrival_minutes,
                'notes' => $latestTracking->notes,
                'created_at' => $latestTracking->created_at,
            ] : null,
            'provider_location' => $providerLocation,
        ]);
    }

    /**
     * ลูกค้าแชร์ตำแหน่งตัวเองให้ผู้ให้บริการ (จากหน้าติดตาม — เลือกเปิดเอง)
     *
     * 🐛 หน้าติดตามเดิมยิง /api/v1/bookings/{id}/update-location ซึ่งต้องใช้โทเคน Sanctum
     *    (เว็บไม่ได้เปิด stateful API) จึงตอบ 401 ทุกครั้ง → เส้นทางเว็บที่ใช้ session เดียวกับหน้า
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function shareLocation(Request $request, ServiceBooking $booking)
    {
        if ((int) $booking->user_id !== (int) auth()->id()) {
            abort(403);
        }

        if (! in_array($booking->status, self::TRACKABLE_STATUSES, true)) {
            return response()->json([
                'success' => false,
                'message' => 'การจองนี้ไม่อยู่ในช่วงที่แชร์ตำแหน่งได้แล้ว',
            ], 422);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $booking->update([
            'user_live_latitude' => $validated['latitude'],
            'user_live_longitude' => $validated['longitude'],
            'user_location_updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * คำนวณ PV และ Cashback ที่ลูกค้าจะได้รับจากบริการ
     */
    private function calculateEarningsPreview(Service $service): array
    {
        // คำนวณ PV (ใช้ pv_value ถ้ามี ไม่งั้นใช้ base_price)
        $pvValue = $service->pv_value && $service->pv_value > 0
            ? (float) $service->pv_value
            : (float) $service->base_price;

        // คำนวณ Cashback (ถ้ามี)
        $cashbackPercentage = $service->cashback_percentage ?? 0;
        $cashbackAmount = ($cashbackPercentage > 0)
            ? ($service->base_price * $cashbackPercentage / 100)
            : 0;

        return [
            'pv_total' => $pvValue,
            'pv_breakdown' => [
                [
                    'service_name' => $service->name,
                    'pv_value' => $pvValue,
                    'base_price' => $service->base_price,
                ],
            ],
            'cashback_total' => $cashbackAmount,
            'cashback_percentage' => $cashbackPercentage,
        ];
    }

    /**
     * แสดงรีวิวบริการของผู้ใช้
     *
     * @return \Illuminate\View\View
     */
    public function myReviews(Request $request)
    {
        $user = auth()->user();

        $reviews = ServiceBooking::where('user_id', $user->id)
            ->whereNotNull('rating')
            ->with(['service', 'provider'])
            ->latest('rated_at')
            ->paginate(10);

        return view('user.service-reviews.index', [
            'reviews' => $reviews,
            'pageTitle' => 'รีวิวบริการของฉัน',
        ]);
    }

    /**
     * บันทึกรีวิวสำหรับการจอง
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeReview(Request $request, ServiceBooking $booking)
    {
        $user = auth()->user();

        // ตรวจสอบว่าเป็นการจองของผู้ใช้นี้
        if ($booking->user_id !== $user->id) {
            abort(403);
        }

        // ตรวจสอบว่าเสร็จสิ้นแล้ว
        if ($booking->status !== 'completed') {
            return redirect()->back()
                ->with('error', 'ไม่สามารถรีวิวการจองที่ยังไม่เสร็จสิ้น');
        }

        // ตรวจสอบว่ารีวิวแล้วหรือยัง
        if ($booking->rating) {
            return redirect()->back()
                ->with('error', 'คุณได้รีวิวการจองนี้แล้ว');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $booking->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
            'rated_at' => now(),
        ]);

        // อัพเดทคะแนนเฉลี่ยของ Provider
        if ($booking->provider_id) {
            $this->updateProviderRating($booking->provider_id);
        }

        return redirect()->back()
            ->with('success', 'ขอบคุณสำหรับรีวิว!');
    }

    /**
     * แก้ไขรีวิว
     *
     * @return \Illuminate\View\View
     */
    public function editReview(ServiceBooking $review)
    {
        $user = auth()->user();

        if ($review->user_id !== $user->id) {
            abort(403);
        }

        return view('user.service-reviews.edit', [
            'booking' => $review,
            'pageTitle' => 'แก้ไขรีวิว',
        ]);
    }

    /**
     * อัพเดทรีวิว
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateReview(Request $request, ServiceBooking $review)
    {
        $user = auth()->user();

        if ($review->user_id !== $user->id) {
            abort(403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);

        $review->update([
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        // อัพเดทคะแนนเฉลี่ยของ Provider
        if ($review->provider_id) {
            $this->updateProviderRating($review->provider_id);
        }

        return redirect()->route('user.service-reviews.index')
            ->with('success', 'อัพเดทรีวิวสำเร็จ');
    }

    /**
     * ลบรีวิว
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteReview(ServiceBooking $review)
    {
        $user = auth()->user();

        if ($review->user_id !== $user->id) {
            abort(403);
        }

        $providerId = $review->provider_id;

        $review->update([
            'rating' => null,
            'review' => null,
            'rated_at' => null,
        ]);

        // อัพเดทคะแนนเฉลี่ยของ Provider
        if ($providerId) {
            $this->updateProviderRating($providerId);
        }

        return redirect()->route('user.service-reviews.index')
            ->with('success', 'ลบรีวิวสำเร็จ');
    }

    /**
     * อัพเดทคะแนนเฉลี่ยของ Provider
     */
    private function updateProviderRating(int $providerId): void
    {
        $avgRating = ServiceBooking::where('provider_id', $providerId)
            ->whereNotNull('rating')
            ->avg('rating');

        $totalReviews = ServiceBooking::where('provider_id', $providerId)
            ->whereNotNull('rating')
            ->count();

        \App\Models\ServiceProvider::where('id', $providerId)->update([
            'average_rating' => round($avgRating ?? 0, 2),
            'total_reviews' => $totalReviews,
        ]);
    }
}
