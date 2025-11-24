<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Services\ServiceBookingService;
use Illuminate\Http\Request;

/**
 * ServiceBookingController - จัดการงาน (Provider)
 *
 * รับงาน, ปฏิเสธงาน, อัพเดทสถานะ, tracking
 */
class ServiceBookingController extends Controller
{
    public function __construct(
        protected ServiceBookingService $bookingService
    ) {
    }

    /**
     * รายการงานทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $provider = $this->getCurrentProvider();

        if (!$provider) {
            return redirect()->route('provider.register')
                ->with('error', 'กรุณาลงทะเบียนเป็นผู้ให้บริการก่อน');
        }

        $query = ServiceBooking::where('provider_id', $provider->id)
            ->with(['service', 'user']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        $bookings = $query->latest()->paginate(15);

        // สถิติ
        $stats = [
            'total' => $provider->total_bookings,
            'waiting' => ServiceBooking::where('provider_id', $provider->id)
                ->where('status', 'waiting_provider')
                ->count(),
            'accepted' => ServiceBooking::where('provider_id', $provider->id)
                ->where('status', 'provider_accepted')
                ->count(),
            'in_progress' => ServiceBooking::where('provider_id', $provider->id)
                ->whereIn('status', ['provider_on_way', 'in_progress'])
                ->count(),
            'completed' => ServiceBooking::where('provider_id', $provider->id)
                ->where('status', 'completed')
                ->count(),
        ];

        return view('provider.bookings.index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'provider' => $provider,
            'pageTitle' => 'งานของฉัน',
        ]);
    }

    /**
     * งานใหม่ที่รอการตอบกลับ
     *
     * @return \Illuminate\View\View
     */
    public function pending()
    {
        $provider = $this->getCurrentProvider();

        $pendingBookings = ServiceBooking::where('provider_id', $provider->id)
            ->whereIn('status', ['notifying_provider', 'waiting_provider'])
            ->with(['service', 'user', 'locations'])
            ->latest('provider_notified_at')
            ->get();

        return view('provider.bookings.pending', [
            'bookings' => $pendingBookings,
            'provider' => $provider,
            'pageTitle' => 'งานใหม่',
        ]);
    }

    /**
     * รายละเอียดงาน
     *
     * @param ServiceBooking $booking
     * @return \Illuminate\View\View
     */
    public function show(ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        // ตรวจสอบว่าเป็นงานของ provider นี้
        if ($booking->provider_id !== $provider->id) {
            abort(403);
        }

        // ทำเครื่องหมายว่าดูแล้ว
        if (!$booking->provider_viewed_at) {
            $booking->markAsViewed();
        }

        $booking->load([
            'service',
            'user',
            'items',
            'locations',
        ]);

        return view('provider.bookings.show', [
            'booking' => $booking,
            'provider' => $provider,
            'pageTitle' => 'งาน #' . $booking->booking_number,
        ]);
    }

    /**
     * รับงาน
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function accept(Request $request, ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        // ตรวจสอบว่าเป็นงานของ provider นี้
        if ($booking->provider_id !== $provider->id) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->bookingService->acceptBooking($booking, $validated['notes'] ?? null);

            return redirect()
                ->route('provider.bookings.show', $booking)
                ->with('success', 'รับงานสำเร็จ! กรุณาเดินทางไปยังสถานที่ให้บริการ');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * ปฏิเสธงาน
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject(Request $request, ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        // ตรวจสอบว่าเป็นงานของ provider นี้
        if ($booking->provider_id !== $provider->id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->bookingService->rejectBooking($booking, $validated['reason']);

            return redirect()
                ->route('provider.bookings.index')
                ->with('info', 'ปฏิเสธงานแล้ว');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * เริ่มเดินทาง
     *
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startJourney(ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        if ($booking->provider_id !== $provider->id) {
            abort(403);
        }

        try {
            $this->bookingService->startJourney($booking);

            return redirect()
                ->back()
                ->with('success', 'เริ่มเดินทางแล้ว ลูกค้าได้รับแจ้งเตือนแล้ว');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * เริ่มให้บริการ
     *
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startService(ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        if ($booking->provider_id !== $provider->id) {
            abort(403);
        }

        try {
            $this->bookingService->startService($booking);

            return redirect()
                ->back()
                ->with('success', 'เริ่มให้บริการแล้ว');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * เสร็จสิ้นบริการ
     *
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function complete(ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        if ($booking->provider_id !== $provider->id) {
            abort(403);
        }

        try {
            $this->bookingService->completeService($booking);

            return redirect()
                ->route('provider.bookings.show', $booking)
                ->with('success', 'เสร็จสิ้นบริการ! เงินจะเข้าบัญชีของคุณในไม่ช้า');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * อัพเดทตำแหน่ง GPS
     *
     * @param Request $request
     * @param ServiceBooking $booking
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateLocation(Request $request, ServiceBooking $booking)
    {
        $provider = $this->getCurrentProvider();

        if ($booking->provider_id !== $provider->id) {
            abort(403);
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

    /**
     * เปลี่ยนสถานะพร้อมรับงาน
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailability(Request $request)
    {
        $provider = $this->getCurrentProvider();

        if ($provider->isAvailable()) {
            $provider->setOffline();
            $message = 'ตั้งสถานะเป็นออฟไลน์แล้ว';
        } else {
            $provider->setAvailable();
            $message = 'ตั้งสถานะเป็นพร้อมรับงานแล้ว';
        }

        return response()->json([
            'success' => true,
            'status' => $provider->status,
            'message' => $message,
        ]);
    }

    /**
     * หา Provider ปัจจุบัน
     *
     * @return ServiceProvider|null
     */
    protected function getCurrentProvider(): ?ServiceProvider
    {
        return ServiceProvider::where('user_id', auth()->id())->first();
    }
}
