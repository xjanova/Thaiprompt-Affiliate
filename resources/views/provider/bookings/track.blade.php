{{--
    หน้าติดตามตำแหน่งลูกค้า (Provider Live Tracking)
    แสดงตำแหน่งลูกค้า, ตำแหน่งตัวเอง, และสถานที่ให้บริการบน Google Maps
    รองรับการใช้งานผ่าน Web Browser บนมือถือโดยไม่ต้องลงแอพ
--}}
@extends('layouts.app')

@section('title', 'ติดตามตำแหน่ง #' . $booking->booking_number)

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900" x-data="providerLiveTracker()">
    {{-- GPS Permission Banner (แสดงเมื่อยังไม่ได้ให้สิทธิ์) --}}
    <div x-show="!gpsPermissionGranted && !gpsPermissionDenied"
         x-cloak
         class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-green-600 to-teal-600 text-white p-4 shadow-lg">
        <div class="max-w-lg mx-auto">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 pt-1">
                    <i class="fas fa-location-arrow text-2xl animate-bounce"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold">อนุญาตให้เข้าถึง GPS</p>
                    <p class="text-sm text-white/80 mt-1">
                        เพื่อให้ลูกค้าเห็นตำแหน่งของคุณ และนำทางไปถึงที่หมายได้ถูกต้อง
                    </p>
                </div>
                <button @click="requestGpsPermission()"
                        class="flex-shrink-0 px-4 py-2 bg-white text-green-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                    อนุญาต
                </button>
            </div>
        </div>
    </div>

    {{-- GPS Permission Denied Banner --}}
    <div x-show="gpsPermissionDenied"
         x-cloak
         class="fixed top-0 left-0 right-0 z-50 bg-red-500 text-white p-4 shadow-lg">
        <div class="max-w-lg mx-auto">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 pt-1">
                    <i class="fas fa-exclamation-triangle text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold">ไม่ได้รับอนุญาตให้เข้าถึง GPS</p>
                    <p class="text-sm text-white/80 mt-1">
                        ลูกค้าจะไม่เห็นตำแหน่งของคุณ กรุณาเปิดการอนุญาตในการตั้งค่า Browser
                    </p>
                </div>
                <button @click="retryGpsPermission()"
                        class="flex-shrink-0 px-4 py-2 bg-white text-red-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                    ลองใหม่
                </button>
            </div>
        </div>
    </div>

    {{-- Header (Fixed) --}}
    <div class="fixed top-0 left-0 right-0 z-40 backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700 shadow-lg"
         :class="{ 'mt-16': !gpsPermissionGranted || gpsPermissionDenied }">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('provider.bookings.show', $booking) }}"
                       class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                            <i class="fas fa-satellite-dish text-green-500 mr-2 animate-pulse"></i>
                            Live Tracking
                        </h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            #{{ $booking->booking_number }} - {{ $booking->service->name }}
                        </p>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="px-4 py-2 rounded-full font-semibold text-sm"
                    :class="{
                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400': status === 'provider_accepted',
                        'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400': status === 'provider_on_way',
                        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400': status === 'in_progress',
                        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400': status === 'completed'
                    }">
                    <i class="fas fa-circle text-xs mr-1 animate-pulse"></i>
                    <span x-text="statusLabel">{{ $booking->getStatusLabel() ?? $booking->status }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Map Container --}}
    <div class="pb-72 md:pb-48"
         :class="{ 'pt-36': !gpsPermissionGranted || gpsPermissionDenied, 'pt-20': gpsPermissionGranted && !gpsPermissionDenied }">
        <div id="tracking-map" class="w-full h-[calc(100vh-320px)] md:h-[calc(100vh-250px)]"></div>
    </div>

    {{-- Legend (Floating) --}}
    <div class="fixed z-30 p-3 rounded-xl backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 shadow-xl text-sm"
         :class="{ 'top-40': !gpsPermissionGranted || gpsPermissionDenied, 'top-24': gpsPermissionGranted && !gpsPermissionDenied }"
         style="left: 1rem;">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-4 h-4 rounded-full bg-green-500"></div>
            <span class="text-gray-700 dark:text-gray-300">ตำแหน่งของคุณ</span>
        </div>
        <div class="flex items-center gap-2 mb-2">
            <div class="w-4 h-4 rounded-full bg-blue-500"></div>
            <span class="text-gray-700 dark:text-gray-300">ตำแหน่งลูกค้า</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-4 h-4 bg-red-500" style="clip-path: polygon(50% 0%, 100% 100%, 0% 100%);"></div>
            <span class="text-gray-700 dark:text-gray-300">สถานที่ให้บริการ</span>
        </div>
    </div>

    {{-- GPS Accuracy Indicator (Mobile Friendly) --}}
    <div x-show="sharingLocation"
         x-cloak
         class="fixed z-30 right-4 p-3 rounded-xl backdrop-blur-xl bg-green-500/90 text-white shadow-xl text-sm"
         :class="{ 'top-40': !gpsPermissionGranted || gpsPermissionDenied, 'top-24': gpsPermissionGranted && !gpsPermissionDenied }">
        <div class="flex items-center gap-2">
            <div class="relative">
                <i class="fas fa-broadcast-tower animate-pulse"></i>
                <span class="absolute -top-1 -right-1 w-2 h-2 bg-white rounded-full animate-ping"></span>
            </div>
            <div>
                <p class="font-semibold text-xs">กำลังแชร์ GPS</p>
                <p class="text-xs text-white/80" x-text="'±' + (gpsAccuracy ? Math.round(gpsAccuracy) : '--') + 'm'"></p>
            </div>
        </div>
    </div>

    {{-- Bottom Panel (Fixed) --}}
    <div class="fixed bottom-0 left-0 right-0 z-40">
        {{-- Pull Handle --}}
        <div class="flex justify-center py-2 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 rounded-t-2xl cursor-pointer"
             @click="panelExpanded = !panelExpanded">
            <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        {{-- Panel Content --}}
        <div class="backdrop-blur-xl bg-white/95 dark:bg-gray-800/95 border-t border-gray-200 dark:border-gray-700 shadow-xl transition-all duration-300"
             :class="panelExpanded ? 'h-96' : 'h-56'">
            <div class="max-w-lg mx-auto px-4 py-4 h-full overflow-y-auto">
                {{-- ETA Card --}}
                <div class="bg-gradient-to-r from-green-600 to-teal-600 rounded-xl p-4 mb-4 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-white/80">เวลาถึงโดยประมาณ</p>
                            <p class="text-3xl font-bold" x-text="eta">กำลังคำนวณ...</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-white/80">ระยะทาง</p>
                            <p class="text-2xl font-bold" x-text="distance + ' km'">-- km</p>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-4">
                        <div class="h-2 bg-white/30 rounded-full overflow-hidden">
                            <div class="h-full bg-white rounded-full transition-all duration-500"
                                 :style="{ width: progress + '%' }"></div>
                        </div>
                        <div class="flex justify-between mt-1 text-xs text-white/70">
                            <span>ตำแหน่งคุณ</span>
                            <span>สถานที่ให้บริการ</span>
                        </div>
                    </div>
                </div>

                {{-- Location Sharing Status --}}
                <div class="flex items-center justify-between p-3 rounded-lg mb-4"
                     :class="sharingLocation ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'">
                    <div class="flex items-center gap-2">
                        <i class="fas" :class="sharingLocation ? 'fa-broadcast-tower text-green-500 animate-pulse' : 'fa-exclamation-triangle text-yellow-500'"></i>
                        <span class="text-sm" :class="sharingLocation ? 'text-green-700 dark:text-green-400' : 'text-yellow-700 dark:text-yellow-400'"
                              x-text="sharingLocation ? 'กำลังแชร์ตำแหน่งให้ลูกค้า' : 'ยังไม่ได้แชร์ตำแหน่ง'"></span>
                    </div>
                    <button @click="toggleLocationSharing()"
                            class="px-3 py-1 rounded-lg text-sm font-medium transition-colors"
                            :class="sharingLocation ? 'bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'">
                        <span x-text="sharingLocation ? 'หยุดแชร์' : 'แชร์ตำแหน่ง'"></span>
                    </button>
                </div>

                {{-- Customer Location Status --}}
                <div class="flex items-center justify-between p-3 rounded-lg mb-4"
                     :class="customerLocationAvailable ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-700/30'">
                    <div class="flex items-center gap-2">
                        <i class="fas" :class="customerLocationAvailable ? 'fa-map-marker-alt text-blue-500' : 'fa-map-marker-alt text-gray-400'"></i>
                        <span class="text-sm" :class="customerLocationAvailable ? 'text-blue-700 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'"
                              x-text="customerLocationAvailable ? 'ลูกค้ากำลังแชร์ตำแหน่ง' : 'ลูกค้ายังไม่ได้แชร์ตำแหน่ง'"></span>
                    </div>
                    <span x-show="customerLocationUpdatedAt" class="text-xs text-gray-500 dark:text-gray-400" x-text="'อัพเดท: ' + customerLocationUpdatedAt"></span>
                </div>

                {{-- Customer Info --}}
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0">
                        {{-- ใช้ component เพื่อความสอดคล้องทั้งระบบ --}}
                        <x-user-avatar :user="$booking->user" size="lg" :ring="true" ringColor="blue" />
                    </div>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            {{ $booking->user->name }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">ลูกค้า</p>
                    </div>

                    {{-- Contact Actions --}}
                    <div class="flex gap-2">
                        @if($booking->contact_phone ?? $booking->user->phone)
                            <a href="tel:{{ $booking->contact_phone ?? $booking->user->phone }}"
                               class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                                <i class="fas fa-phone"></i>
                            </a>
                        @endif
                        <button @click="openChat()"
                                class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                            <i class="fas fa-comment"></i>
                        </button>
                        <a href="https://www.google.com/maps/dir/?api=1&destination={{ $serviceLocation->latitude ?? '' }},{{ $serviceLocation->longitude ?? '' }}"
                           target="_blank"
                           class="p-3 bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors">
                            <i class="fas fa-directions"></i>
                        </a>
                    </div>
                </div>

                {{-- Service Info (Expanded) --}}
                <div x-show="panelExpanded" x-collapse>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ข้อมูลงาน</h4>

                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">บริการ</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $booking->service->name }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">วันเวลา</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ $booking->scheduled_at?->format('d/m/Y H:i') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 dark:text-gray-400">รายได้ของคุณ</span>
                                <span class="font-bold text-green-600 dark:text-green-400">
                                    ฿{{ number_format($booking->provider_earnings ?? 0, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Destination --}}
                    @if($serviceLocation)
                        <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">
                                <i class="fas fa-map-marker-alt text-red-500 mr-2"></i>
                                สถานที่ให้บริการ
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $serviceLocation->address ?? $booking->customer_address ?? 'ไม่ระบุที่อยู่' }}
                            </p>
                        </div>
                    @endif

                    {{-- Quick Actions --}}
                    <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex gap-3">
                            @if($booking->status === 'provider_accepted')
                                <form action="{{ route('provider.bookings.start-journey', $booking) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold transition-all">
                                        <i class="fas fa-route mr-2"></i>เริ่มเดินทาง
                                    </button>
                                </form>
                            @elseif($booking->status === 'provider_on_way')
                                <form action="{{ route('provider.bookings.start-service', $booking) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold transition-all">
                                        <i class="fas fa-play mr-2"></i>เริ่มบริการ
                                    </button>
                                </form>
                            @elseif($booking->status === 'in_progress')
                                <form action="{{ route('provider.bookings.complete', $booking) }}" method="POST" class="flex-1">
                                    @csrf
                                    <button type="submit" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold transition-all">
                                        <i class="fas fa-check-double mr-2"></i>เสร็จสิ้นบริการ
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Last Update --}}
                    <div class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <i class="fas fa-sync-alt mr-1"></i>
                            อัพเดทล่าสุด: <span x-text="lastUpdate">-</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating Action Buttons --}}
    <div class="fixed right-4 bottom-64 md:bottom-52 z-50 flex flex-col gap-3">
        {{-- Refresh --}}
        <button @click="refreshLocation()"
                class="p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                :class="{ 'animate-spin': loading }">
            <i class="fas fa-sync-alt text-green-600 dark:text-green-400"></i>
        </button>

        {{-- Center on Me --}}
        <button @click="centerOnMe()"
                class="p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="ตำแหน่งของฉัน">
            <i class="fas fa-motorcycle text-green-600 dark:text-green-400"></i>
        </button>

        {{-- Center on Customer --}}
        <button @click="centerOnCustomer()"
                class="p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="ตำแหน่งลูกค้า">
            <i class="fas fa-user text-blue-600 dark:text-blue-400"></i>
        </button>

        {{-- Center on Destination --}}
        <button @click="centerOnDestination()"
                class="p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="สถานที่ให้บริการ">
            <i class="fas fa-map-pin text-red-600 dark:text-red-400"></i>
        </button>

        {{-- Show All --}}
        <button @click="fitAllMarkers()"
                class="p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="แสดงทั้งหมด">
            <i class="fas fa-expand text-gray-600 dark:text-gray-400"></i>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function providerLiveTracker() {
    return {
        panelExpanded: false,
        loading: false,
        map: null,
        providerMarker: null,
        customerMarker: null,
        serviceMarker: null,
        directionsRenderer: null,
        directionsService: null,

        // ข้อมูล Booking
        bookingId: {{ $booking->id }},
        status: '{{ $booking->status }}',
        statusLabel: '{{ $booking->getStatusLabel() ?? $booking->status }}',

        // ข้อมูล Location
        providerLat: {{ $provider->current_latitude ?? 'null' }},
        providerLng: {{ $provider->current_longitude ?? 'null' }},
        customerLat: {{ $booking->user_live_latitude ?? 'null' }},
        customerLng: {{ $booking->user_live_longitude ?? 'null' }},
        serviceLat: {{ $serviceLocation->latitude ?? 'null' }},
        serviceLng: {{ $serviceLocation->longitude ?? 'null' }},

        // Customer location status
        customerLocationAvailable: {{ $booking->user_live_latitude ? 'true' : 'false' }},
        customerLocationUpdatedAt: null,

        // ข้อมูล ETA
        eta: 'กำลังคำนวณ...',
        distance: '--',
        progress: 0,
        lastUpdate: '-',

        // Location sharing
        sharingLocation: false,
        watchId: null,

        // GPS Permission State
        gpsPermissionGranted: false,
        gpsPermissionDenied: false,
        gpsAccuracy: null,

        // Refresh interval
        refreshInterval: null,

        init() {
            this.loadGoogleMaps();
            this.startAutoRefresh();
            this.checkGpsPermission();
        },

        // ตรวจสอบสถานะ GPS Permission
        async checkGpsPermission() {
            if (!navigator.geolocation) {
                this.gpsPermissionDenied = true;
                return;
            }

            // ลองตรวจสอบผ่าน Permissions API (ถ้ารองรับ)
            if ('permissions' in navigator) {
                try {
                    const result = await navigator.permissions.query({ name: 'geolocation' });
                    if (result.state === 'granted') {
                        this.gpsPermissionGranted = true;
                        this.gpsPermissionDenied = false;
                        // เริ่มแชร์ตำแหน่งอัตโนมัติถ้าได้สิทธิ์แล้ว และกำลังเดินทาง
                        if (this.status === 'provider_on_way' || this.status === 'provider_accepted') {
                            this.startLocationSharing();
                        }
                    } else if (result.state === 'denied') {
                        this.gpsPermissionDenied = true;
                        this.gpsPermissionGranted = false;
                    }
                    // Listen การเปลี่ยนแปลง
                    result.onchange = () => {
                        this.gpsPermissionGranted = result.state === 'granted';
                        this.gpsPermissionDenied = result.state === 'denied';
                        if (this.gpsPermissionGranted && (this.status === 'provider_on_way' || this.status === 'provider_accepted')) {
                            this.startLocationSharing();
                        }
                    };
                } catch (e) {
                    console.log('Permissions API not fully supported');
                }
            }
        },

        // ขอ Permission GPS
        requestGpsPermission() {
            if (!navigator.geolocation) {
                this.gpsPermissionDenied = true;
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.gpsPermissionGranted = true;
                    this.gpsPermissionDenied = false;
                    this.providerLat = position.coords.latitude;
                    this.providerLng = position.coords.longitude;
                    this.gpsAccuracy = position.coords.accuracy;
                    this.updateProviderMarker();
                    // เริ่มแชร์ตำแหน่งอัตโนมัติ
                    this.startLocationSharing();
                },
                (error) => {
                    if (error.code === error.PERMISSION_DENIED) {
                        this.gpsPermissionDenied = true;
                    }
                    console.error('GPS permission error:', error);
                },
                { enableHighAccuracy: true, timeout: 15000 }
            );
        },

        // ลองขอ Permission ใหม่
        retryGpsPermission() {
            this.gpsPermissionDenied = false;
            this.requestGpsPermission();
        },

        loadGoogleMaps() {
            if (typeof google !== 'undefined') {
                this.initMap();
            } else {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initProviderTrackingMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
                window.initProviderTrackingMap = () => this.initMap();
            }
        },

        initMap() {
            // ศูนย์กลางแผนที่: สถานที่ให้บริการ หรือ กรุงเทพ
            const center = this.serviceLat && this.serviceLng
                ? { lat: this.serviceLat, lng: this.serviceLng }
                : { lat: 13.7563, lng: 100.5018 };

            this.map = new google.maps.Map(document.getElementById('tracking-map'), {
                center: center,
                zoom: 15,
                styles: this.getMapStyles(),
                disableDefaultUI: true,
                zoomControl: true,
                gestureHandling: 'greedy',
            });

            this.directionsService = new google.maps.DirectionsService();
            this.directionsRenderer = new google.maps.DirectionsRenderer({
                map: this.map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#10B981',
                    strokeWeight: 5,
                    strokeOpacity: 0.8,
                }
            });

            // Service Location Marker (Red Pin)
            if (this.serviceLat && this.serviceLng) {
                this.serviceMarker = new google.maps.Marker({
                    position: { lat: this.serviceLat, lng: this.serviceLng },
                    map: this.map,
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="50" viewBox="0 0 40 50">
                                <path d="M20 0 C8.954 0 0 8.954 0 20 C0 35 20 50 20 50 S40 35 40 20 C40 8.954 31.046 0 20 0 Z" fill="#EF4444"/>
                                <circle cx="20" cy="20" r="10" fill="white"/>
                            </svg>
                        `),
                        anchor: new google.maps.Point(20, 50),
                        scaledSize: new google.maps.Size(40, 50),
                    },
                    title: 'สถานที่ให้บริการ',
                });
            }

            // Provider Marker (Green - ตำแหน่งของเรา)
            if (this.providerLat && this.providerLng) {
                this.providerMarker = new google.maps.Marker({
                    position: { lat: this.providerLat, lng: this.providerLng },
                    map: this.map,
                    icon: this.getProviderIcon(),
                    title: 'ตำแหน่งของคุณ',
                });
            }

            // Customer Marker (Blue)
            if (this.customerLat && this.customerLng) {
                this.customerMarker = new google.maps.Marker({
                    position: { lat: this.customerLat, lng: this.customerLng },
                    map: this.map,
                    icon: this.getCustomerIcon(),
                    title: 'ตำแหน่งลูกค้า',
                });
            }

            // คำนวณเส้นทาง
            this.calculateRoute();

            // ดึงตำแหน่งปัจจุบัน
            this.getCurrentLocation();
        },

        getProviderIcon() {
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="22" fill="#10B981" stroke="white" stroke-width="3"/>
                        <circle cx="25" cy="25" r="8" fill="white"/>
                        <circle cx="25" cy="25" r="4" fill="#10B981"/>
                    </svg>
                `),
                anchor: new google.maps.Point(25, 25),
                scaledSize: new google.maps.Size(50, 50),
            };
        },

        getCustomerIcon() {
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
                        <circle cx="20" cy="20" r="18" fill="#3B82F6" stroke="white" stroke-width="3"/>
                        <circle cx="20" cy="20" r="6" fill="white"/>
                    </svg>
                `),
                anchor: new google.maps.Point(20, 20),
                scaledSize: new google.maps.Size(40, 40),
            };
        },

        getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        this.providerLat = position.coords.latitude;
                        this.providerLng = position.coords.longitude;
                        this.updateProviderMarker();
                        this.calculateRoute();
                    },
                    (error) => console.log('Location error:', error),
                    { enableHighAccuracy: true }
                );
            }
        },

        toggleLocationSharing() {
            if (this.sharingLocation) {
                this.stopLocationSharing();
            } else {
                this.startLocationSharing();
            }
        },

        startLocationSharing() {
            if (!navigator.geolocation) {
                alert('เบราว์เซอร์ไม่รองรับ GPS');
                return;
            }

            this.sharingLocation = true;
            this.gpsPermissionGranted = true;

            this.watchId = navigator.geolocation.watchPosition(
                async (position) => {
                    this.providerLat = position.coords.latitude;
                    this.providerLng = position.coords.longitude;
                    this.gpsAccuracy = position.coords.accuracy;
                    this.updateProviderMarker();
                    this.calculateRoute();

                    // ส่งตำแหน่งไปเซิร์ฟเวอร์ (รองรับทั้ง Web GPS และ Mobile App)
                    try {
                        await fetch(`/provider/bookings/${this.bookingId}/update-location`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                latitude: this.providerLat,
                                longitude: this.providerLng,
                                accuracy: this.gpsAccuracy,
                                source: 'web_browser',
                            }),
                        });
                    } catch (error) {
                        console.error('Failed to update location:', error);
                    }
                },
                (error) => {
                    console.error('Watch location error:', error);
                    this.sharingLocation = false;
                    if (error.code === error.PERMISSION_DENIED) {
                        this.gpsPermissionDenied = true;
                        this.gpsPermissionGranted = false;
                    }
                },
                {
                    enableHighAccuracy: true,
                    maximumAge: 5000,
                    timeout: 10000,
                }
            );
        },

        stopLocationSharing() {
            if (this.watchId) {
                navigator.geolocation.clearWatch(this.watchId);
                this.watchId = null;
            }
            this.sharingLocation = false;
        },

        updateProviderMarker() {
            if (!this.providerLat || !this.providerLng || !this.map) return;

            if (this.providerMarker) {
                this.providerMarker.setPosition({ lat: this.providerLat, lng: this.providerLng });
            } else {
                this.providerMarker = new google.maps.Marker({
                    position: { lat: this.providerLat, lng: this.providerLng },
                    map: this.map,
                    icon: this.getProviderIcon(),
                    title: 'ตำแหน่งของคุณ',
                });
            }
        },

        updateCustomerMarker() {
            if (!this.customerLat || !this.customerLng || !this.map) return;

            if (this.customerMarker) {
                this.customerMarker.setPosition({ lat: this.customerLat, lng: this.customerLng });
            } else {
                this.customerMarker = new google.maps.Marker({
                    position: { lat: this.customerLat, lng: this.customerLng },
                    map: this.map,
                    icon: this.getCustomerIcon(),
                    title: 'ตำแหน่งลูกค้า',
                });
            }
        },

        calculateRoute() {
            if (!this.providerLat || !this.providerLng || !this.serviceLat || !this.serviceLng) return;

            const request = {
                origin: { lat: this.providerLat, lng: this.providerLng },
                destination: { lat: this.serviceLat, lng: this.serviceLng },
                travelMode: google.maps.TravelMode.DRIVING,
            };

            this.directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    this.directionsRenderer.setDirections(result);

                    const leg = result.routes[0].legs[0];
                    this.distance = (leg.distance.value / 1000).toFixed(1);
                    this.eta = leg.duration.text;

                    // คำนวณ progress (สมมติเดินทางไม่เกิน 10 km)
                    const maxDistance = 10;
                    const currentDistance = leg.distance.value / 1000;
                    this.progress = Math.min(100, Math.max(0, (1 - currentDistance / maxDistance) * 100));
                }
            });
        },

        async refreshLocation() {
            if (this.loading) return;
            this.loading = true;

            try {
                // ดึงข้อมูลตำแหน่งลูกค้าจาก API
                const response = await fetch(`/api/v1/provider/bookings/${this.bookingId}/track`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.status = data.data.booking.status;
                    this.statusLabel = data.data.booking.status_label;

                    // อัพเดทตำแหน่งลูกค้า
                    if (data.data.customer_location) {
                        this.customerLat = parseFloat(data.data.customer_location.latitude);
                        this.customerLng = parseFloat(data.data.customer_location.longitude);
                        this.customerLocationAvailable = true;
                        this.customerLocationUpdatedAt = data.data.customer_location.updated_at || new Date().toLocaleTimeString('th-TH');
                        this.updateCustomerMarker();
                    }

                    this.lastUpdate = new Date().toLocaleTimeString('th-TH');

                    // ถ้างานเสร็จหรือยกเลิก redirect กลับ
                    if (['completed', 'cancelled'].includes(this.status)) {
                        this.stopAutoRefresh();
                        this.stopLocationSharing();
                        setTimeout(() => {
                            window.location.href = `/provider/bookings/${this.bookingId}`;
                        }, 2000);
                    }
                }
            } catch (error) {
                console.error('Failed to refresh location:', error);
            } finally {
                this.loading = false;
            }
        },

        startAutoRefresh() {
            // รีเฟรชทุก 10 วินาที
            this.refreshInterval = setInterval(() => this.refreshLocation(), 10000);
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        },

        centerOnMe() {
            if (this.map && this.providerLat && this.providerLng) {
                this.map.setCenter({ lat: this.providerLat, lng: this.providerLng });
                this.map.setZoom(16);
            } else {
                this.getCurrentLocation();
            }
        },

        centerOnCustomer() {
            if (this.map && this.customerLat && this.customerLng) {
                this.map.setCenter({ lat: this.customerLat, lng: this.customerLng });
                this.map.setZoom(16);
            } else {
                alert('ลูกค้ายังไม่ได้แชร์ตำแหน่ง');
            }
        },

        centerOnDestination() {
            if (this.map && this.serviceLat && this.serviceLng) {
                this.map.setCenter({ lat: this.serviceLat, lng: this.serviceLng });
                this.map.setZoom(16);
            }
        },

        fitAllMarkers() {
            if (!this.map) return;

            const bounds = new google.maps.LatLngBounds();
            let hasMarkers = false;

            if (this.providerLat && this.providerLng) {
                bounds.extend({ lat: this.providerLat, lng: this.providerLng });
                hasMarkers = true;
            }
            if (this.customerLat && this.customerLng) {
                bounds.extend({ lat: this.customerLat, lng: this.customerLng });
                hasMarkers = true;
            }
            if (this.serviceLat && this.serviceLng) {
                bounds.extend({ lat: this.serviceLat, lng: this.serviceLng });
                hasMarkers = true;
            }

            if (hasMarkers) {
                this.map.fitBounds(bounds);
            }
        },

        openChat() {
            alert('ฟีเจอร์แชทกำลังพัฒนา');
        },

        getMapStyles() {
            const isDark = document.documentElement.classList.contains('dark');

            if (isDark) {
                return [
                    { elementType: "geometry", stylers: [{ color: "#242f3e" }] },
                    { elementType: "labels.text.stroke", stylers: [{ color: "#242f3e" }] },
                    { elementType: "labels.text.fill", stylers: [{ color: "#746855" }] },
                    { featureType: "road", elementType: "geometry", stylers: [{ color: "#38414e" }] },
                    { featureType: "road", elementType: "geometry.stroke", stylers: [{ color: "#212a37" }] },
                    { featureType: "water", elementType: "geometry", stylers: [{ color: "#17263c" }] },
                ];
            }

            return [
                { elementType: "geometry", stylers: [{ color: "#f5f5f5" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#616161" }] },
                { featureType: "road", elementType: "geometry", stylers: [{ color: "#ffffff" }] },
                { featureType: "road.arterial", elementType: "labels.text.fill", stylers: [{ color: "#757575" }] },
                { featureType: "water", elementType: "geometry", stylers: [{ color: "#c9c9c9" }] },
            ];
        },

        destroy() {
            this.stopAutoRefresh();
            this.stopLocationSharing();
        }
    }
}
</script>
@endpush
