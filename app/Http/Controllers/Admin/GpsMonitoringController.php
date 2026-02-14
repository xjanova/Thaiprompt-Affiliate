<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceBookingLocationLog;
use App\Models\ServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * GPS Monitoring Controller
 *
 * จัดการหน้า Dashboard สำหรับติดตาม GPS ทั้งหมดในระบบ
 * แสดงตำแหน่ง providers, users, และ service locations บนแผนที่
 */
class GpsMonitoringController extends Controller
{
    /**
     * แสดงหน้า GPS Monitoring Dashboard
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('admin.gps-monitoring.index');
    }

    /**
     * ดึงข้อมูล GPS สำหรับแสดงบนแผนที่
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getData(Request $request)
    {
        $showProviders = $request->boolean('show_providers', true);
        $showUsers = $request->boolean('show_users', true);
        $statuses = $request->input('statuses', 'provider_accepted,provider_on_way,in_progress');
        $statusArray = array_filter(explode(',', $statuses));
        $limit = $request->input('limit', '50');
        $timeRange = $request->input('time_range', 'live');

        // กำหนด time range
        $startTime = $this->getStartTime($timeRange);

        // ดึงข้อมูล bookings ที่กำลังดำเนินการ
        $bookingsQuery = ServiceBooking::with(['user', 'provider', 'service', 'locations'])
            ->whereIn('status', $statusArray);

        if ($startTime) {
            $bookingsQuery->where('updated_at', '>=', $startTime);
        }

        if ($limit !== 'all') {
            $bookingsQuery->limit((int) $limit);
        }

        $bookings = $bookingsQuery->latest('updated_at')->get();

        // เตรียมข้อมูลสำหรับ response
        $providers = [];
        $users = [];
        $serviceLocations = [];
        $routes = [];

        // ดึงข้อมูล providers ที่ online
        if ($showProviders) {
            $providers = $this->getOnlineProviders($bookings);
        }

        // ดึงข้อมูล users ที่กำลัง share location
        if ($showUsers) {
            $users = $this->getActiveUsers($bookings);
        }

        // ดึงข้อมูล service locations
        $serviceLocations = $this->getServiceLocations($bookings);

        // ดึงเส้นทาง (ถ้าต้องการ)
        $routes = $this->getRoutes($bookings);

        // คำนวณ stats
        $stats = $this->calculateStats($bookings);

        // เตรียมข้อมูล bookings สำหรับ sidebar
        $bookingsList = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'status' => $booking->status,
                'status_label' => $booking->status_text ?? $booking->status,
                'service_name' => $booking->service->name ?? 'ไม่ระบุ',
                'user_id' => $booking->user_id,
                'user_name' => $booking->user->name ?? 'ไม่ระบุ',
                'provider_id' => $booking->provider_id,
                'provider_name' => $booking->provider->display_name ?? null,
                'provider_latitude' => $booking->provider->current_latitude ?? null,
                'provider_longitude' => $booking->provider->current_longitude ?? null,
                'user_latitude' => $booking->user_live_latitude,
                'user_longitude' => $booking->user_live_longitude,
                'service_latitude' => $booking->locations->where('type', 'service')->first()?->latitude,
                'service_longitude' => $booking->locations->where('type', 'service')->first()?->longitude,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'providers' => $providers,
                'users' => $users,
                'serviceLocations' => $serviceLocations,
                'routes' => $routes,
                'bookings' => $bookingsList,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * ดึง providers ที่ online
     *
     * @param  \Illuminate\Support\Collection  $bookings
     * @return array
     */
    private function getOnlineProviders($bookings)
    {
        $providerIds = $bookings->pluck('provider_id')->filter()->unique();

        $providers = ServiceProvider::whereIn('id', $providerIds)
            ->whereNotNull('current_latitude')
            ->whereNotNull('current_longitude')
            ->get();

        return $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'name' => $provider->display_name ?? $provider->user->name ?? 'ผู้ให้บริการ',
                'latitude' => $provider->current_latitude,
                'longitude' => $provider->current_longitude,
                'last_update' => $provider->last_location_update,
                'is_available' => $provider->is_available ?? false,
            ];
        })->toArray();
    }

    /**
     * ดึง users ที่กำลัง share location
     *
     * @param  \Illuminate\Support\Collection  $bookings
     * @return array
     */
    private function getActiveUsers($bookings)
    {
        return $bookings
            ->filter(function ($booking) {
                return $booking->user_live_latitude && $booking->user_live_longitude;
            })
            ->map(function ($booking) {
                return [
                    'id' => $booking->user_id,
                    'name' => $booking->user->name ?? 'ผู้ใช้',
                    'latitude' => $booking->user_live_latitude,
                    'longitude' => $booking->user_live_longitude,
                    'booking_id' => $booking->id,
                    'last_update' => $booking->user_location_updated_at,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * ดึง service locations
     *
     * @param  \Illuminate\Support\Collection  $bookings
     * @return array
     */
    private function getServiceLocations($bookings)
    {
        $locations = [];

        foreach ($bookings as $booking) {
            $serviceLocation = $booking->locations->where('type', 'service')->first();
            if ($serviceLocation && $serviceLocation->latitude && $serviceLocation->longitude) {
                $locations[] = [
                    'id' => $serviceLocation->id,
                    'booking_id' => $booking->id,
                    'latitude' => $serviceLocation->latitude,
                    'longitude' => $serviceLocation->longitude,
                    'address' => $serviceLocation->address ?? 'ไม่ระบุ',
                ];
            }
        }

        return $locations;
    }

    /**
     * ดึงเส้นทาง
     *
     * @param  \Illuminate\Support\Collection  $bookings
     * @return array
     */
    private function getRoutes($bookings)
    {
        // สำหรับแสดงเส้นทางจาก provider ไป service location
        $routes = [];

        foreach ($bookings as $booking) {
            if (! $booking->provider || ! $booking->provider->current_latitude) {
                continue;
            }

            $serviceLocation = $booking->locations->where('type', 'service')->first();
            if (! $serviceLocation || ! $serviceLocation->latitude) {
                continue;
            }

            $routes[] = [
                'booking_id' => $booking->id,
                'path' => [
                    ['lat' => (float) $booking->provider->current_latitude, 'lng' => (float) $booking->provider->current_longitude],
                    ['lat' => (float) $serviceLocation->latitude, 'lng' => (float) $serviceLocation->longitude],
                ],
            ];
        }

        return $routes;
    }

    /**
     * คำนวณสถิติ
     *
     * @param  \Illuminate\Support\Collection  $bookings
     * @return array
     */
    private function calculateStats($bookings)
    {
        // นับ providers ที่ online
        $activeProviders = ServiceProvider::where('is_available', true)
            ->whereNotNull('current_latitude')
            ->where('last_location_update', '>=', now()->subMinutes(10))
            ->count();

        // นับ users ที่กำลัง share location
        $activeUsers = $bookings->filter(function ($booking) {
            return $booking->user_live_latitude
                && $booking->user_location_updated_at
                && $booking->user_location_updated_at >= now()->subMinutes(10);
        })->count();

        // นับ bookings ที่กำลังดำเนินการ
        $activeBookings = $bookings->whereIn('status', ['provider_accepted', 'provider_on_way', 'in_progress'])->count();

        // นับ location updates ในนาทีที่ผ่านมา
        $totalUpdates = ServiceBookingLocationLog::where('recorded_at', '>=', now()->subMinute())->count();

        return [
            'activeProviders' => $activeProviders,
            'activeUsers' => $activeUsers,
            'activeBookings' => $activeBookings,
            'totalUpdates' => $totalUpdates,
        ];
    }

    /**
     * กำหนด start time ตาม time range
     *
     * @param  string  $timeRange
     * @return Carbon|null
     */
    private function getStartTime($timeRange)
    {
        switch ($timeRange) {
            case 'live':
                return now()->subHours(2); // แสดง 2 ชั่วโมงล่าสุด
            case '1h':
                return now()->subHour();
            case '3h':
                return now()->subHours(3);
            case 'today':
                return now()->startOfDay();
            case 'yesterday':
                return now()->subDay()->startOfDay();
            case 'week':
                return now()->subWeek();
            default:
                return null;
        }
    }

    /**
     * ดูประวัติ GPS ของ booking ใดๆ
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBookingHistory(Request $request, ServiceBooking $booking)
    {
        $entityType = $request->input('entity_type', 'all'); // all, user, provider

        $query = ServiceBookingLocationLog::where('service_booking_id', $booking->id);

        if ($entityType !== 'all') {
            $query->where('actor_type', $entityType);
        }

        $logs = $query->orderBy('recorded_at', 'asc')->get();

        // แปลงเป็น path สำหรับแสดงบนแผนที่
        $userPath = [];
        $providerPath = [];

        foreach ($logs as $log) {
            $point = [
                'lat' => (float) $log->latitude,
                'lng' => (float) $log->longitude,
                'timestamp' => $log->recorded_at,
                'accuracy' => $log->accuracy,
            ];

            if ($log->actor_type === 'user') {
                $userPath[] = $point;
            } else {
                $providerPath[] = $point;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'status' => $booking->status,
                ],
                'user_path' => $userPath,
                'provider_path' => $providerPath,
                'total_logs' => $logs->count(),
            ],
        ]);
    }

    /**
     * Playback ประวัติการเดินทาง
     *
     * @return \Illuminate\View\View
     */
    public function playback(ServiceBooking $booking)
    {
        $logs = ServiceBookingLocationLog::where('service_booking_id', $booking->id)
            ->orderBy('recorded_at', 'asc')
            ->get();

        return view('admin.gps-monitoring.playback', [
            'booking' => $booking,
            'logs' => $logs,
        ]);
    }
}
