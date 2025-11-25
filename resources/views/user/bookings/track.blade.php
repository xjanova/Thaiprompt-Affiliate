{{--
    หน้าติดตามการจอง (Real-time GPS Tracking)
    แสดงตำแหน่งผู้ให้บริการบน Google Maps พร้อม real-time updates
--}}
@extends('layouts.app')

@section('title', 'ติดตามการจอง #' . $booking->booking_number)

@section('content')
<div class="min-h-screen bg-gray-100 dark:bg-gray-900" x-data="bookingTracker()">
    {{-- Header (Fixed) --}}
    <div class="fixed top-0 left-0 right-0 z-40 backdrop-blur-xl bg-white/90 dark:bg-gray-800/90 border-b border-gray-200 dark:border-gray-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="{{ route('user.bookings.show', $booking) }}"
                       class="p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white">
                            ติดตามการจอง
                        </h1>
                        <p class="text-xs text-gray-600 dark:text-gray-400">
                            #{{ $booking->booking_number }}
                        </p>
                    </div>
                </div>

                {{-- Status Badge --}}
                <div class="px-4 py-2 rounded-full font-semibold text-sm
                    @if($booking->status === 'provider_on_way')
                        bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400
                    @elseif($booking->status === 'in_progress')
                        bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400
                    @else
                        bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300
                    @endif">
                    <i class="fas fa-circle text-xs mr-1 animate-pulse"></i>
                    <span x-text="statusLabel">{{ $booking->getStatusLabel() ?? $booking->status }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Map Container --}}
    <div class="pt-20 pb-72 md:pb-48">
        <div id="tracking-map" class="w-full h-[calc(100vh-320px)] md:h-[calc(100vh-250px)]"></div>
    </div>

    {{-- Bottom Panel (Fixed) --}}
    <div class="fixed bottom-0 left-0 right-0 z-40">
        {{-- Pull Handle --}}
        <div class="flex justify-center py-2 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 rounded-t-2xl cursor-pointer"
             @click="panelExpanded = !panelExpanded">
            <div class="w-12 h-1 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
        </div>

        {{-- Panel Content --}}
        <div class="backdrop-blur-xl bg-white/95 dark:bg-gray-800/95 border-t border-gray-200 dark:border-gray-700 shadow-xl"
             :class="panelExpanded ? 'h-80' : 'h-56'">
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

                {{-- Service Info --}}
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
                </div>
            </div>
        </div>
    </div>

    {{-- Refresh Button (Floating) --}}
    <button @click="refreshLocation()"
            class="fixed right-4 bottom-64 md:bottom-52 z-50 p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            :class="{ 'animate-spin': loading }">
        <i class="fas fa-sync-alt text-purple-600 dark:text-purple-400"></i>
    </button>

    {{-- Center on Provider Button --}}
    <button @click="centerOnProvider()"
            class="fixed right-4 bottom-80 md:bottom-68 z-50 p-4 bg-white dark:bg-gray-800 rounded-full shadow-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
        <i class="fas fa-crosshairs text-purple-600 dark:text-purple-400"></i>
    </button>
</div>
@endsection

@push('scripts')
<script>
function bookingTracker() {
    return {
        panelExpanded: false,
        loading: false,
        map: null,
        providerMarker: null,
        customerMarker: null,
        directionsRenderer: null,
        directionsService: null,

        // ข้อมูล Booking
        bookingId: {{ $booking->id }},
        status: '{{ $booking->status }}',
        statusLabel: '{{ $booking->getStatusLabel() ?? $booking->status }}',

        // ข้อมูล Location
        providerLat: {{ $booking->provider->current_latitude ?? 'null' }},
        providerLng: {{ $booking->provider->current_longitude ?? 'null' }},
        customerLat: {{ $serviceLocation->latitude ?? 'null' }},
        customerLng: {{ $serviceLocation->longitude ?? 'null' }},

        // ข้อมูล ETA
        eta: 'กำลังคำนวณ...',
        distance: '--',
        progress: 0,

        // Refresh interval
        refreshInterval: null,

        init() {
            // โหลด Google Maps
            this.loadGoogleMaps();

            // เริ่ม auto refresh ทุก 10 วินาที
            this.startAutoRefresh();
        },

        loadGoogleMaps() {
            if (typeof google !== 'undefined') {
                this.initMap();
            } else {
                // Load Google Maps Script
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initTrackingMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);

                window.initTrackingMap = () => this.initMap();
            }
        },

        initMap() {
            // กำหนดจุดกึ่งกลาง (ใช้ตำแหน่งลูกค้าถ้ามี)
            const center = this.customerLat && this.customerLng
                ? { lat: this.customerLat, lng: this.customerLng }
                : { lat: 13.7563, lng: 100.5018 }; // Bangkok default

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

            // เพิ่ม Marker สถานที่ลูกค้า
            if (this.customerLat && this.customerLng) {
                this.customerMarker = new google.maps.Marker({
                    position: { lat: this.customerLat, lng: this.customerLng },
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

            // เพิ่ม Marker ผู้ให้บริการ
            if (this.providerLat && this.providerLng) {
                this.providerMarker = new google.maps.Marker({
                    position: { lat: this.providerLat, lng: this.providerLng },
                    map: this.map,
                    icon: {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                                <circle cx="25" cy="25" r="20" fill="#8B5CF6" stroke="white" stroke-width="3"/>
                                <circle cx="25" cy="25" r="8" fill="white"/>
                            </svg>
                        `),
                        anchor: new google.maps.Point(25, 25),
                        scaledSize: new google.maps.Size(50, 50),
                    },
                    title: 'ผู้ให้บริการ',
                });

                // คำนวณเส้นทาง
                this.calculateRoute();
            }
        },

        calculateRoute() {
            if (!this.providerLat || !this.providerLng || !this.customerLat || !this.customerLng) {
                return;
            }

            const request = {
                origin: { lat: this.providerLat, lng: this.providerLng },
                destination: { lat: this.customerLat, lng: this.customerLng },
                travelMode: google.maps.TravelMode.DRIVING,
            };

            this.directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    this.directionsRenderer.setDirections(result);

                    const leg = result.routes[0].legs[0];
                    this.distance = (leg.distance.value / 1000).toFixed(1);
                    this.eta = leg.duration.text;

                    // คำนวณ progress (สมมติว่าระยะทางเริ่มต้น 10 km)
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
                const response = await fetch(`/api/v1/bookings/${this.bookingId}/tracking`);
                const data = await response.json();

                if (data.success) {
                    // อัพเดทข้อมูล
                    this.status = data.data.status;
                    this.statusLabel = data.data.status_label;

                    if (data.data.provider_location) {
                        this.providerLat = data.data.provider_location.latitude;
                        this.providerLng = data.data.provider_location.longitude;

                        // อัพเดท marker
                        if (this.providerMarker) {
                            this.providerMarker.setPosition({
                                lat: this.providerLat,
                                lng: this.providerLng
                            });
                        }

                        // อัพเดทเส้นทาง
                        this.calculateRoute();
                    }

                    // ตรวจสอบว่าบริการเสร็จสิ้นหรือยกเลิก
                    if (['completed', 'cancelled'].includes(this.status)) {
                        this.stopAutoRefresh();
                        // Redirect ไปหน้ารายละเอียด
                        window.location.href = `/user/bookings/${this.bookingId}`;
                    }
                }
            } catch (error) {
                console.error('Failed to refresh location:', error);
            } finally {
                this.loading = false;
            }
        },

        startAutoRefresh() {
            // Refresh ทุก 10 วินาที
            this.refreshInterval = setInterval(() => {
                this.refreshLocation();
            }, 10000);
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

        openChat() {
            // TODO: เปิดหน้าแชท
            alert('ฟีเจอร์แชทกำลังพัฒนา');
        },

        getMapStyles() {
            // ตรวจสอบ dark mode
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
        }
    }
}
</script>
@endpush
