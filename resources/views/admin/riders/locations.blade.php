{{--
    ตำแหน่ง GPS และประวัติการเดินทางของไรเดอร์
    แสดงตำแหน่งปัจจุบัน และ Playback ย้อนหลัง 24 ชม.
--}}
@extends('layouts.admin')

@section('title', $pageTitle)

@push('styles')
<style>
    #location-map {
        height: calc(100vh - 280px);
        min-height: 500px;
    }

    .timeline-slider {
        -webkit-appearance: none;
        height: 8px;
        border-radius: 4px;
        background: linear-gradient(to right, #10b981 0%, #3b82f6 50%, #8b5cf6 100%);
    }
    .timeline-slider::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        box-shadow: 0 0 10px rgba(139, 92, 246, 0.5);
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 p-6" x-data="riderLocationTracker()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.riders.show', $rider) }}"
               class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition backdrop-blur-lg border border-white/10">
                <i class="fas fa-arrow-left text-white"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                    <span>GPS: {{ $rider->full_name }}</span>
                    <span class="px-3 py-1 bg-green-500/20 text-green-400 text-sm rounded-full border border-green-500/30">
                        LIVE
                    </span>
                </h1>
                <p class="text-gray-400 text-sm mt-1">ติดตามตำแหน่งและประวัติการเดินทาง</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-gray-400 text-sm">
                อัพเดทล่าสุด: <span x-text="lastUpdate" class="text-green-400">-</span>
            </span>
            <button @click="refreshLocation()"
                    class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition"
                    :class="{ 'animate-spin': loading }">
                <i class="fas fa-sync-alt text-white"></i>
            </button>
            <a href="{{ route('admin.riders.map') }}"
               class="px-4 py-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl transition">
                <i class="fas fa-map mr-2"></i> แผนที่รวม
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Left Column - Info & History --}}
        <div class="space-y-6">
            {{-- Current Status --}}
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-user text-purple-400"></i>
                    ข้อมูลไรเดอร์
                </h4>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-gray-400">สถานะ</span>
                        <span class="px-3 py-1 rounded-full text-sm
                            {{ $rider->availability === 'online' ? 'bg-green-500/20 text-green-400' : ($rider->availability === 'busy' ? 'bg-yellow-500/20 text-yellow-400' : 'bg-gray-500/20 text-gray-400') }}">
                            {{ $rider->availability === 'online' ? 'ออนไลน์' : ($rider->availability === 'busy' ? 'กำลังส่งงาน' : 'ออฟไลน์') }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-white/10">
                        <span class="text-gray-400">ประเภทรถ</span>
                        @php
                            $vehicleLabels = [
                                'motorcycle' => 'มอเตอร์ไซค์',
                                'car' => 'รถยนต์',
                                'pickup' => 'รถกระบะ',
                                'van' => 'รถตู้',
                                'truck' => 'รถบรรทุก',
                            ];
                        @endphp
                        <span class="text-white">{{ $vehicleLabels[$rider->vehicle_type] ?? $rider->vehicle_type }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-400">ทะเบียน</span>
                        <span class="text-white font-mono">{{ $rider->vehicle_plate ?? '-' }}</span>
                    </div>
                </div>
            </div>

            {{-- Current Location --}}
            @if($latestLocation)
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                    <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-map-marker-alt text-green-400"></i>
                        ตำแหน่งล่าสุด
                    </h4>
                    <div class="space-y-3 text-sm">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ละติจูด</p>
                                <p class="text-white font-mono">{{ number_format($latestLocation->latitude, 6) }}</p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ลองจิจูด</p>
                                <p class="text-white font-mono">{{ number_format($latestLocation->longitude, 6) }}</p>
                            </div>
                        </div>
                        @if($latestLocation->speed)
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ความเร็ว</p>
                                <p class="text-white">{{ number_format($latestLocation->speed, 1) }} km/h</p>
                            </div>
                        @endif
                        @if($latestLocation->accuracy)
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ความแม่นยำ</p>
                                <p class="text-white">{{ number_format($latestLocation->accuracy, 0) }} m</p>
                            </div>
                        @endif
                        @if($latestLocation->battery_level)
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">แบตเตอรี่</p>
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-2 bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $latestLocation->battery_level > 20 ? 'bg-green-500' : 'bg-red-500' }}"
                                             style="width: {{ $latestLocation->battery_level }}%"></div>
                                    </div>
                                    <span class="text-white text-xs">{{ $latestLocation->battery_level }}%</span>
                                </div>
                            </div>
                        @endif
                        <p class="text-gray-500 text-xs">
                            บันทึกเมื่อ: {{ $latestLocation->created_at->thaidate('j M Y H:i:s') }}
                        </p>
                    </div>
                </div>
            @endif

            {{-- History Stats --}}
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-6 border border-white/10">
                <h4 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-chart-line text-blue-400"></i>
                    สถิติ 24 ชม.
                </h4>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-blue-500/10 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-400">{{ $locationHistory->count() }}</p>
                        <p class="text-gray-400 text-xs">จุด GPS</p>
                    </div>
                    <div class="bg-green-500/10 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-400" x-text="totalDistance">-</p>
                        <p class="text-gray-400 text-xs">กม.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right Column - Map --}}
        <div class="lg:col-span-3">
            <div class="bg-white/10 backdrop-blur-xl rounded-2xl overflow-hidden border border-white/10">
                {{-- Map --}}
                <div id="location-map" class="w-full"></div>

                {{-- Playback Controls --}}
                @if($locationHistory->count() > 1)
                    <div class="p-4 bg-gray-900/50 border-t border-white/10">
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-sm text-gray-400 mb-2">
                                <span x-text="formatTime(startTime)">-</span>
                                <span x-text="formatTime(currentTime)">-</span>
                                <span x-text="formatTime(endTime)">-</span>
                            </div>
                            <input type="range"
                                   x-model="currentIndex"
                                   min="0"
                                   :max="points.length - 1"
                                   @input="seekTo($event.target.value)"
                                   class="w-full timeline-slider">
                        </div>

                        <div class="flex items-center justify-center gap-4">
                            <button @click="seekTo(0)"
                                    class="p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition">
                                <i class="fas fa-fast-backward"></i>
                            </button>
                            <button @click="stepBackward()"
                                    class="p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition">
                                <i class="fas fa-step-backward"></i>
                            </button>
                            <button @click="togglePlay()"
                                    class="p-4 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 rounded-xl text-white transition">
                                <i :class="isPlaying ? 'fas fa-pause' : 'fas fa-play'" class="text-xl"></i>
                            </button>
                            <button @click="stepForward()"
                                    class="p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition">
                                <i class="fas fa-step-forward"></i>
                            </button>
                            <button @click="seekTo(points.length - 1)"
                                    class="p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white transition">
                                <i class="fas fa-fast-forward"></i>
                            </button>

                            <div class="ml-4 flex items-center gap-2 bg-white/10 rounded-xl px-3 py-2">
                                <span class="text-gray-400 text-sm">ความเร็ว:</span>
                                <select x-model="playbackSpeed"
                                        @change="updatePlaybackSpeed()"
                                        class="bg-transparent text-white border-none focus:ring-0 text-sm">
                                    <option value="0.5">0.5x</option>
                                    <option value="1">1x</option>
                                    <option value="2">2x</option>
                                    <option value="5">5x</option>
                                    <option value="10">10x</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function riderLocationTracker() {
    return {
        // State
        loading: false,
        lastUpdate: '{{ $latestLocation ? $latestLocation->created_at->toTimeString() : "-" }}',
        map: null,
        currentMarker: null,
        pathPolyline: null,

        // Playback
        isPlaying: false,
        currentIndex: 0,
        playbackSpeed: 1,
        playIntervalId: null,

        // Data
        points: @json($locationHistory->map(function($log) {
            return [
                'lat' => (float)$log->latitude,
                'lng' => (float)$log->longitude,
                'timestamp' => $log->created_at->toIso8601String(),
                'speed' => $log->speed,
                'heading' => $log->heading,
                'accuracy' => $log->accuracy,
                'battery' => $log->battery_level,
            ];
        })),

        get startTime() {
            return this.points[0]?.timestamp || null;
        },

        get endTime() {
            return this.points[this.points.length - 1]?.timestamp || null;
        },

        get currentTime() {
            return this.points[this.currentIndex]?.timestamp || null;
        },

        get totalDistance() {
            let distance = 0;
            for (let i = 1; i < this.points.length; i++) {
                distance += this.calculateDistance(
                    this.points[i-1].lat, this.points[i-1].lng,
                    this.points[i].lat, this.points[i].lng
                );
            }
            return distance.toFixed(2);
        },

        init() {
            this.loadGoogleMaps();
        },

        loadGoogleMaps() {
            if (typeof google !== 'undefined') {
                this.initMap();
            } else {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initRiderLocationMap`;
                script.async = true;
                document.head.appendChild(script);
                window.initRiderLocationMap = () => this.initMap();
            }
        },

        initMap() {
            const darkStyle = [
                { elementType: "geometry", stylers: [{ color: "#1a1a2e" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#8b5cf6" }] },
                { featureType: "road", elementType: "geometry", stylers: [{ color: "#374151" }] },
                { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] },
            ];

            let center = { lat: 13.7563, lng: 100.5018 };
            if (this.points.length > 0) {
                center = { lat: this.points[0].lat, lng: this.points[0].lng };
            }

            this.map = new google.maps.Map(document.getElementById('location-map'), {
                center: center,
                zoom: 15,
                styles: darkStyle,
                disableDefaultUI: true,
                zoomControl: true,
            });

            this.drawPath();
            this.updateCurrentPosition();
        },

        drawPath() {
            if (this.points.length < 2) return;

            this.pathPolyline = new google.maps.Polyline({
                path: this.points.map(p => ({ lat: p.lat, lng: p.lng })),
                geodesic: true,
                strokeColor: '#8B5CF6',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map: this.map,
            });

            // Start marker
            new google.maps.Marker({
                position: { lat: this.points[0].lat, lng: this.points[0].lng },
                map: this.map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#22C55E',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                },
                title: 'จุดเริ่มต้น',
            });

            // End marker
            const last = this.points[this.points.length - 1];
            new google.maps.Marker({
                position: { lat: last.lat, lng: last.lng },
                map: this.map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 8,
                    fillColor: '#EF4444',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                },
                title: 'จุดสิ้นสุด',
            });

            // Fit bounds
            const bounds = new google.maps.LatLngBounds();
            this.points.forEach(p => bounds.extend({ lat: p.lat, lng: p.lng }));
            this.map.fitBounds(bounds);
        },

        updateCurrentPosition() {
            if (this.points.length === 0) return;

            const point = this.points[this.currentIndex];
            const position = { lat: point.lat, lng: point.lng };

            if (this.currentMarker) {
                this.currentMarker.setPosition(position);
                this.currentMarker.setIcon({
                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                    scale: 6,
                    fillColor: '#10B981',
                    fillOpacity: 1,
                    strokeColor: '#fff',
                    strokeWeight: 2,
                    rotation: point.heading || 0,
                });
            } else {
                this.currentMarker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    icon: {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 6,
                        fillColor: '#10B981',
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                        rotation: point.heading || 0,
                    },
                });
            }

            this.map.panTo(position);
        },

        togglePlay() {
            this.isPlaying = !this.isPlaying;
            this.isPlaying ? this.play() : this.pause();
        },

        play() {
            const interval = 1000 / this.playbackSpeed;
            this.playIntervalId = setInterval(() => {
                if (this.currentIndex < this.points.length - 1) {
                    this.currentIndex++;
                    this.updateCurrentPosition();
                } else {
                    this.pause();
                }
            }, interval);
        },

        pause() {
            this.isPlaying = false;
            if (this.playIntervalId) {
                clearInterval(this.playIntervalId);
                this.playIntervalId = null;
            }
        },

        seekTo(index) {
            this.currentIndex = parseInt(index);
            this.updateCurrentPosition();
        },

        stepForward() {
            if (this.currentIndex < this.points.length - 1) {
                this.currentIndex++;
                this.updateCurrentPosition();
            }
        },

        stepBackward() {
            if (this.currentIndex > 0) {
                this.currentIndex--;
                this.updateCurrentPosition();
            }
        },

        updatePlaybackSpeed() {
            if (this.isPlaying) {
                this.pause();
                this.play();
            }
        },

        async refreshLocation() {
            this.loading = true;
            try {
                const response = await fetch('{{ route('admin.riders.latest-location', $rider) }}', {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.lastUpdate = new Date(data.data.updated_at).toLocaleTimeString('th-TH');
                    // Update current marker
                    if (this.map) {
                        const newPos = { lat: parseFloat(data.data.latitude), lng: parseFloat(data.data.longitude) };
                        if (this.currentMarker) {
                            this.currentMarker.setPosition(newPos);
                        }
                        this.map.panTo(newPos);
                    }
                }
            } catch (error) {
                console.error('Error refreshing location:', error);
            } finally {
                this.loading = false;
            }
        },

        formatTime(isoString) {
            if (!isoString) return '-';
            return new Date(isoString).toLocaleTimeString('th-TH');
        },

        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371;
            const dLat = this.deg2rad(lat2 - lat1);
            const dLon = this.deg2rad(lon2 - lon1);
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        },

        deg2rad(deg) {
            return deg * (Math.PI/180);
        },

        destroy() {
            this.pause();
        },
    }
}
</script>
@endpush
