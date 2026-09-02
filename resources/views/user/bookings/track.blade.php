{{--
    หน้าติดตามการจอง (Real-time GPS Tracking)
    แสดงตำแหน่งทั้งผู้ให้บริการและผู้ใช้บน Google Maps พร้อม real-time updates
    รองรับการใช้งานผ่าน Web Browser บนมือถือโดยไม่ต้องลงแอพ
--}}
@extends('layouts.user-v4')

@section('title', 'ติดตามการจอง #' . $booking->booking_number)

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900" x-data="liveTracker()">
    {{-- GPS Permission Banner (แสดงเมื่อยังไม่ได้ให้สิทธิ์) --}}
    <div x-show="!gpsPermissionGranted && !gpsPermissionDenied"
         x-cloak
         class="fixed top-0 left-0 right-0 z-50 bg-gradient-to-r from-blue-600 to-purple-600 text-white p-4 shadow-lg">
        <div class="max-w-lg mx-auto">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 pt-1">
                    <i class="fas fa-location-arrow text-2xl animate-bounce"></i>
                </div>
                <div class="flex-1">
                    <p class="font-semibold">อนุญาตให้เข้าถึง GPS</p>
                    <p class="text-sm text-white/80 mt-1">
                        เพื่อให้ผู้ให้บริการเห็นตำแหน่งของคุณ และคำนวณเวลาถึงได้แม่นยำ
                    </p>
                </div>
                <button @click="requestGpsPermission()"
                        class="flex-shrink-0 px-4 py-2 bg-white text-purple-600 rounded-lg font-semibold hover:bg-gray-100 transition">
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
                        กรุณาเปิดการอนุญาตในการตั้งค่า Browser หรือคลิกไอคอนกุญแจข้างๆ URL
                    </p>
                </div>
                <button @click="retryGpsPermission()"
                        class="flex-shrink-0 px-4 py-2 bg-white text-red-600 rounded-lg font-semibold hover:bg-gray-100 transition">
                    ลองใหม่
                </button>
            </div>
        </div>
    </div>

    {{-- Premium Hero Header (Fixed - Purple-Indigo for Live Tracking) --}}
    <div class="fixed top-0 left-0 right-0 z-40 backdrop-blur-xl bg-gradient-to-r from-purple-600 to-indigo-600 dark:from-purple-800 dark:to-indigo-800 shadow-2xl border-b border-purple-400/20"
         :class="{ 'mt-16': !gpsPermissionGranted || gpsPermissionDenied }">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('user.bookings.show', $booking) }}"
                       class="p-2 bg-white/10 hover:bg-white/20 rounded-lg transition-colors">
                        <i class="fas fa-arrow-left text-lg text-white"></i>
                    </a>
                    <div class="flex items-center gap-3">
                        <div class="glass-fusion p-2 rounded-lg">
                            <i class="fas fa-satellite-dish text-2xl text-white animate-pulse"></i>
                        </div>
                        <div>
                            <h1 class="text-lg font-bold text-white drop-shadow-lg">
                                🛰️ Live Tracking
                            </h1>
                            <p class="text-xs text-purple-100">
                                #{{ $booking->booking_number }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="px-4 py-2 rounded-full font-semibold text-sm glass-fusion text-white"
                    :class="{
                        'animate-pulse': ['provider_on_way', 'in_progress'].includes(status)
                    }">
                    <i class="fas fa-circle text-xs mr-1"></i>
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
            <div class="w-4 h-4 rounded-full bg-purple-500"></div>
            <span class="text-gray-700 dark:text-gray-300">ผู้ให้บริการ</span>
        </div>
        <div class="flex items-center gap-2 mb-2">
            <div class="w-4 h-4 rounded-full bg-blue-500"></div>
            <span class="text-gray-700 dark:text-gray-300">ตำแหน่งของคุณ</span>
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
        <div class="tp-card flex justify-center py-2 border-t border-gray-200 dark:border-gray-700 rounded-t-2xl cursor-pointer"
             @click="panelExpanded = !panelExpanded">
            <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        {{-- Panel Content --}}
        <div class="backdrop-blur-xl bg-white/95 dark:bg-gray-800/95 border-t border-gray-200 dark:border-gray-700 shadow-xl transition-all duration-300"
             :class="panelExpanded ? 'h-96' : 'h-56'">
            <div class="max-w-lg mx-auto px-4 py-4 h-full overflow-y-auto">
                {{-- ETA Card --}}
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 rounded-xl p-4 mb-4 text-white">
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
                            <span>ผู้ให้บริการ</span>
                            <span>ที่หมาย</span>
                        </div>
                    </div>
                </div>

                {{-- Location Sharing Status --}}
                <div class="flex items-center justify-between p-3 rounded-lg mb-4"
                     :class="sharingLocation ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'">
                    <div class="flex items-center gap-2">
                        <i class="fas" :class="sharingLocation ? 'fa-broadcast-tower text-green-500 animate-pulse' : 'fa-exclamation-triangle text-yellow-500'"></i>
                        <span class="text-sm" :class="sharingLocation ? 'text-green-700 dark:text-green-400' : 'text-yellow-700 dark:text-yellow-400'"
                              x-text="sharingLocation ? 'กำลังแชร์ตำแหน่งของคุณ' : 'ยังไม่ได้แชร์ตำแหน่ง'"></span>
                    </div>
                    <button @click="toggleLocationSharing()"
                            class="px-3 py-1 rounded-lg text-sm font-medium transition-colors"
                            :class="sharingLocation ? 'bg-red-100 text-red-600 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400'">
                        <span x-text="sharingLocation ? 'หยุดแชร์' : 'แชร์ตำแหน่ง'"></span>
                    </button>
                </div>

                {{-- Provider Info --}}
                @if($booking->provider)
                    <div class="flex items-center gap-4 mb-4">
                        <div class="flex-shrink-0">
                            @if($booking->provider->profile_image)
                                <img src="{{ asset('storage/' . $booking->provider->profile_image) }}"
                                     alt="{{ $booking->provider->display_name }}"
                                     class="w-14 h-14 rounded-full object-cover ring-2 ring-purple-500">
                            @else
                                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-purple-400 to-pink-400 flex items-center justify-center ring-2 ring-purple-500">
                                    <i class="fas fa-user text-white text-xl"></i>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-900 dark:text-white">
                                {{ $booking->provider->display_name }}
                            </h3>
                            @if($booking->provider->average_rating)
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-star text-yellow-400 text-sm"></i>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ number_format($booking->provider->average_rating, 1) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Contact Actions --}}
                        <div class="flex gap-2">
                            @if($booking->provider->phone)
                                <a href="tel:{{ $booking->provider->phone }}"
                                   class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors">
                                    <i class="fas fa-phone"></i>
                                </a>
                            @endif
                            <button @click="openChat()"
                                    class="p-3 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors">
                                <i class="fas fa-comment"></i>
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Service Info (Expanded) --}}
                <div x-show="panelExpanded" x-collapse>
                    <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-3">ข้อมูลบริการ</h4>

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
                                <span class="text-gray-600 dark:text-gray-400">ราคา</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400">
                                    ฿{{ number_format($booking->total_amount, 2) }}
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
                                {{ $serviceLocation->address }}
                            </p>
                        </div>
                    @endif

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
                class="tp-card p-4 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                :class="{ 'animate-spin': loading }">
            <i class="fas fa-sync-alt text-purple-600 dark:text-purple-400"></i>
        </button>

        {{-- Center on Provider --}}
        <button @click="centerOnProvider()"
                class="tp-card p-4 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="ตำแหน่งผู้ให้บริการ">
            <i class="fas fa-motorcycle text-purple-600 dark:text-purple-400"></i>
        </button>

        {{-- Center on Me --}}
        <button @click="centerOnMe()"
                class="tp-card p-4 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="ตำแหน่งของฉัน">
            <i class="fas fa-user text-blue-600 dark:text-blue-400"></i>
        </button>

        {{-- Show Both --}}
        <button @click="fitAllMarkers()"
                class="tp-card p-4 rounded-full border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                title="แสดงทั้งหมด">
            <i class="fas fa-expand text-gray-600 dark:text-gray-400"></i>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function liveTracker() {
    return {
        panelExpanded: false,
        loading: false,
        map: null,
        providerMarker: null,
        serviceMarker: null,
        userMarker: null,
        directionsRenderer: null,
        directionsService: null,

        // ข้อมูล Booking
        bookingId: {{ $booking->id }},
        status: '{{ $booking->status }}',
        statusLabel: '{{ $booking->getStatusLabel() ?? $booking->status }}',

        // ข้อมูล Location
        providerLat: {{ $booking->provider->current_latitude ?? 'null' }},
        providerLng: {{ $booking->provider->current_longitude ?? 'null' }},
        serviceLat: {{ $serviceLocation->latitude ?? 'null' }},
        serviceLng: {{ $serviceLocation->longitude ?? 'null' }},
        userLat: null,
        userLng: null,

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
                        // เริ่มแชร์ตำแหน่งอัตโนมัติถ้าได้สิทธิ์แล้ว
                        this.startLocationSharing();
                    } else if (result.state === 'denied') {
                        this.gpsPermissionDenied = true;
                        this.gpsPermissionGranted = false;
                    }
                    // Listen การเปลี่ยนแปลง
                    result.onchange = () => {
                        this.gpsPermissionGranted = result.state === 'granted';
                        this.gpsPermissionDenied = result.state === 'denied';
                        if (this.gpsPermissionGranted) {
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
                    this.userLat = position.coords.latitude;
                    this.userLng = position.coords.longitude;
                    this.gpsAccuracy = position.coords.accuracy;
                    this.updateUserMarker();
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
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initTrackingMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
                window.initTrackingMap = () => this.initMap();
            }
        },

        initMap() {
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
                    strokeColor: '#8B5CF6',
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

            // Provider Marker (Purple Circle)
            if (this.providerLat && this.providerLng) {
                this.providerMarker = new google.maps.Marker({
                    position: { lat: this.providerLat, lng: this.providerLng },
                    map: this.map,
                    icon: this.getProviderIcon(),
                    title: 'ผู้ให้บริการ',
                });
                this.calculateRoute();
            }

            // Get user location initially
            this.getCurrentLocation();
        },

        getProviderIcon() {
            return {
                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                    <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="22" fill="#8B5CF6" stroke="white" stroke-width="3"/>
                        <circle cx="25" cy="25" r="8" fill="white"/>
                        <circle cx="25" cy="25" r="4" fill="#8B5CF6"/>
                    </svg>
                `),
                anchor: new google.maps.Point(25, 25),
                scaledSize: new google.maps.Size(50, 50),
            };
        },

        getUserIcon() {
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
                        this.userLat = position.coords.latitude;
                        this.userLng = position.coords.longitude;
                        this.updateUserMarker();
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

            this.watchId = navigator.geolocation.watchPosition(
                async (position) => {
                    this.userLat = position.coords.latitude;
                    this.userLng = position.coords.longitude;
                    this.updateUserMarker();

                    // Send to server
                    try {
                        await fetch(`/api/v1/bookings/${this.bookingId}/update-location`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                latitude: this.userLat,
                                longitude: this.userLng,
                            }),
                        });
                    } catch (error) {
                        console.error('Failed to update location:', error);
                    }
                },
                (error) => {
                    console.error('Watch location error:', error);
                    this.sharingLocation = false;
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

        updateUserMarker() {
            if (!this.userLat || !this.userLng || !this.map) return;

            if (this.userMarker) {
                this.userMarker.setPosition({ lat: this.userLat, lng: this.userLng });
            } else {
                this.userMarker = new google.maps.Marker({
                    position: { lat: this.userLat, lng: this.userLng },
                    map: this.map,
                    icon: this.getUserIcon(),
                    title: 'ตำแหน่งของคุณ',
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
                const response = await fetch(`/api/v1/bookings/${this.bookingId}/live-tracking`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await response.json();

                if (data.success) {
                    this.status = data.data.booking.status;
                    this.statusLabel = data.data.booking.status_label;

                    if (data.data.provider_location) {
                        this.providerLat = parseFloat(data.data.provider_location.latitude);
                        this.providerLng = parseFloat(data.data.provider_location.longitude);

                        if (this.providerMarker) {
                            this.providerMarker.setPosition({
                                lat: this.providerLat,
                                lng: this.providerLng
                            });
                        }

                        this.calculateRoute();
                    }

                    this.lastUpdate = new Date().toLocaleTimeString('th-TH');

                    if (['completed', 'cancelled'].includes(this.status)) {
                        this.stopAutoRefresh();
                        this.stopLocationSharing();
                        setTimeout(() => {
                            window.location.href = `/user/bookings/${this.bookingId}`;
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
            this.refreshInterval = setInterval(() => this.refreshLocation(), 10000);
        },

        stopAutoRefresh() {
            if (this.refreshInterval) {
                clearInterval(this.refreshInterval);
            }
        },

        centerOnProvider() {
            if (this.map && this.providerLat && this.providerLng) {
                this.map.setCenter({ lat: this.providerLat, lng: this.providerLng });
                this.map.setZoom(16);
            }
        },

        centerOnMe() {
            if (this.map && this.userLat && this.userLng) {
                this.map.setCenter({ lat: this.userLat, lng: this.userLng });
                this.map.setZoom(16);
            } else {
                this.getCurrentLocation();
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
            if (this.serviceLat && this.serviceLng) {
                bounds.extend({ lat: this.serviceLat, lng: this.serviceLng });
                hasMarkers = true;
            }
            if (this.userLat && this.userLng) {
                bounds.extend({ lat: this.userLat, lng: this.userLng });
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

@push('styles')
<style>
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-20px); }
}
</style>
@endpush
