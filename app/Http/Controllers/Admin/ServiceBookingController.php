<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Services\ServiceBookingService;
use Illuminate\Http\Request;

/**
 * ServiceBookingController - จัดการการจองบริการ (Admin)
 *
 * ดู, มอบหมาย provider, ยกเลิก, คืนเงิน
 */
class ServiceBookingController extends Controller
{
    public function __construct(
        protected ServiceBookingService $bookingService
    ) {
    }

    /**
     * แสดงรายการจองทั้งหมด
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = ServiceBooking::with(['user', 'service', 'provider']);

        // กรองตามสถานะ
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        // กรองตามวันที่
        if ($request->filled('date')) {
            $query->scheduledOn($request->date);
        }

        // ค้นหา
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('booking_number', 'like', "%{$request->search}%")
                    ->orWhereHas('user', function ($q) use ($request) {
                        $q->where('name', 'like', "%{$request->search}%");
                    });
            });
        }

        $bookings = $query->latest()->paginate(20);

        // สถิติ
        $stats = [
            'total' => ServiceBooking::count(),
            'pending' => ServiceBooking::pending()->count(),
            'waiting_provider' => ServiceBooking::waitingProvider()->count(),
            'active' => ServiceBooking::active()->count(),
            'completed' => ServiceBooking::completed()->count(),
            'cancelled' => ServiceBooking::cancelled()->count(),
        ];

        return view('admin.service-bookings.index', [
            'bookings' => $bookings,
            'stats' => $stats,
            'pageTitle' => 'จัดการการจองบริการ',
        ]);
    }

    /**
     * แสดงรายละเอียดการจอง
     *
     * @param ServiceBooking $serviceBooking
     * @return \Illuminate\View\View
     */
    public function show(ServiceBooking $serviceBooking)
    {
        $serviceBooking->load([
            'user',
            'service',
            'provider',
            'items',
            'locations',
            'trackings' => fn($q) => $q->latest()->limit(20),
            'notifications' => fn($q) => $q->latest(),
            'actions' => fn($q) => $q->latest(),
            'review',
        ]);

        return view('admin.service-bookings.show', [
            'booking' => $serviceBooking,
            'pageTitle' => 'การจอง #' . $serviceBooking->booking_number,
        ]);
    }

    /**
     * มอบหมายผู้ให้บริการ
     *
     * @param Request $request
     * @param ServiceBooking $serviceBooking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function assignProvider(Request $request, ServiceBooking $serviceBooking)
    {
        $validated = $request->validate([
            'provider_id' => 'required|exists:service_providers,id',
            'response_minutes' => 'nullable|integer|min:5|max:60',
        ]);

        $provider = ServiceProvider::findOrFail($validated['provider_id']);
        $responseMinutes = $validated['response_minutes'] ?? 15;

        try {
            $this->bookingService->assignProvider($serviceBooking, $provider, $responseMinutes);

            return redirect()
                ->back()
                ->with('success', "มอบหมายให้ {$provider->name} แล้ว");
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ยกเลิกการจอง
     *
     * @param Request $request
     * @param ServiceBooking $serviceBooking
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request, ServiceBooking $serviceBooking)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->bookingService->cancelBooking(
                $serviceBooking,
                $validated['reason'],
                'admin'
            );

            return redirect()
                ->back()
                ->with('success', 'ยกเลิกการจองสำเร็จ');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    /**
     * ผู้ให้บริการที่พร้อมรับงาน
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableProviders(Request $request)
    {
        $providers = ServiceProvider::where('status', 'available')
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'name' => $provider->name,
                    'rating' => $provider->average_rating,
                    'total_bookings' => $provider->total_bookings,
                    'acceptance_rate' => $provider->acceptance_rate,
                ];
            });

        return response()->json([
            'success' => true,
            'providers' => $providers,
        ]);
    }

    /**
     * อัพเดทสถานะการจอง
     *
     * @param Request $request
     * @param ServiceBooking $serviceBooking
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, ServiceBooking $serviceBooking)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,paid,provider_accepted,provider_on_way,in_progress,completed,cancelled',
            'notes' => 'nullable|string|max:500',
        ]);

        $serviceBooking->updateStatus($validated['status'], $validated['notes'] ?? null);

        return response()->json([
            'success' => true,
            'message' => 'อัพเดทสถานะสำเร็จ',
            'status' => $serviceBooking->status,
            'status_text' => $serviceBooking->status_text,
        ]);
    }

    /**
     * Export รายงาน
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function export(Request $request)
    {
        // TODO: Implement export to Excel/PDF
        return response()->download(/* file path */);
    }
}
