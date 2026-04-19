{{--
    GPS Playback View
    แสดงการเล่นย้อนหลังประวัติ GPS ของ booking
--}}
@extends('layouts.admin-v3')

@section('title', 'GPS Playback - #' . $booking->booking_number)

@push('styles')
<style>
    #playback-map {
        height: calc(100vh - 280px);
        min-height: 500px;
    }

    /* Timeline slider */
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

    /* Playback controls */
    .control-btn {
        transition: all 0.2s;
    }
    .control-btn:hover {
        transform: scale(1.1);
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 p-6" x-data="gpsPlayback()">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.gps-monitoring.index') }}"
               class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition">
                <i class="fas fa-arrow-left text-white"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-history text-purple-400"></i>
                    GPS Playback
                </h1>
                <p class="text-gray-400 text-sm">
                    การจอง #{{ $booking->booking_number }} | {{ $booking->service->name ?? 'ไม่ระบุบริการ' }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-4">
            <span class="px-4 py-2 bg-white/10 rounded-lg text-gray-300">
                <i class="fas fa-map-marker-alt mr-2"></i>
                <span x-text="totalPoints">{{ $logs->count() }}</span> จุด
            </span>
            <span class="px-4 py-2 bg-white/10 rounded-lg text-gray-300">
                <i class="fas fa-clock mr-2"></i>
                <span x-text="formatDuration(totalDuration)">-</span>
            </span>
        </div>
    </div>

    {{-- Booking Info Card --}}
    <div class="mb-6 grid grid-cols-4 gap-4">
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/10">
            <p class="text-gray-400 text-sm mb-1">ผู้ใช้บริการ</p>
            <p class="text-white font-medium">{{ $booking->user->name ?? 'ไม่ระบุ' }}</p>
        </div>
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/10">
            <p class="text-gray-400 text-sm mb-1">ผู้ให้บริการ</p>
            <p class="text-white font-medium">{{ $booking->provider->display_name ?? 'ไม่ระบุ' }}</p>
        </div>
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/10">
            <p class="text-gray-400 text-sm mb-1">สถานะ</p>
            <span class="px-3 py-1 bg-{{ $booking->status === 'completed' ? 'green' : 'blue' }}-500/20 text-{{ $booking->status === 'completed' ? 'green' : 'blue' }}-400 rounded-full text-sm">
                {{ $booking->status_text ?? $booking->status }}
            </span>
        </div>
        <div class="bg-white/10 backdrop-blur-lg rounded-xl p-4 border border-white/10">
            <p class="text-gray-400 text-sm mb-1">ระยะทางรวม</p>
            <p class="text-white font-medium">
                <span x-text="formatDistance(totalDistance)">คำนวณ...</span>
            </p>
        </div>
    </div>

    <div class="flex gap-6">
        {{-- Map --}}
        <div class="flex-1">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl overflow-hidden border border-white/10">
                <div id="playback-map" class="w-full"></div>

                {{-- Playback Controls --}}
                <div class="p-4 bg-gray-900/50 border-t border-white/10">
                    {{-- Timeline --}}
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

                    {{-- Controls --}}
                    <div class="flex items-center justify-center gap-4">
                        <button @click="seekTo(0)"
                                class="control-btn p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white">
                            <i class="fas fa-fast-backward"></i>
                        </button>
                        <button @click="stepBackward()"
                                class="control-btn p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button @click="togglePlay()"
                                class="control-btn p-4 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 rounded-xl text-white">
                            <i :class="isPlaying ? 'fas fa-pause' : 'fas fa-play'" class="text-xl"></i>
                        </button>
                        <button @click="stepForward()"
                                class="control-btn p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white">
                            <i class="fas fa-step-forward"></i>
                        </button>
                        <button @click="seekTo(points.length - 1)"
                                class="control-btn p-3 bg-white/10 hover:bg-white/20 rounded-xl text-white">
                            <i class="fas fa-fast-forward"></i>
                        </button>

                        {{-- Speed Control --}}
                        <div class="ml-4 flex items-center gap-2 bg-white/10 rounded-xl px-3 py-2">
                            <span class="text-gray-400 text-sm">ความเร็ว:</span>
                            <select x-model="playbackSpeed"
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
            </div>
        </div>

        {{-- Sidebar - Point Details --}}
        <div class="w-80 flex-shrink-0">
            <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-4 border border-white/10 sticky top-6">
                <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
                    <i class="fas fa-info-circle text-blue-400"></i>
                    รายละเอียดจุด
                </h3>

                <template x-if="currentPoint">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ประเภท</p>
                                <p class="text-white font-medium text-sm" x-text="currentPoint.actor_type === 'user' ? 'ผู้ใช้' : 'ผู้ให้บริการ'"></p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">แหล่งที่มา</p>
                                <p class="text-white font-medium text-sm" x-text="currentPoint.source || '-'"></p>
                            </div>
                        </div>

                        <div class="bg-white/5 rounded-lg p-3">
                            <p class="text-gray-400 text-xs mb-1">พิกัด</p>
                            <p class="text-white font-mono text-sm">
                                <span x-text="currentPoint.latitude.toFixed(6)"></span>,
                                <span x-text="currentPoint.longitude.toFixed(6)"></span>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ความแม่นยำ</p>
                                <p class="text-white font-medium text-sm" x-text="currentPoint.accuracy ? currentPoint.accuracy.toFixed(0) + ' m' : '-'"></p>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ความเร็ว</p>
                                <p class="text-white font-medium text-sm" x-text="currentPoint.speed ? currentPoint.speed.toFixed(1) + ' km/h' : '-'"></p>
                            </div>
                        </div>

                        <div class="bg-white/5 rounded-lg p-3">
                            <p class="text-gray-400 text-xs mb-1">เวลา</p>
                            <p class="text-white font-medium text-sm" x-text="formatDateTime(currentPoint.recorded_at)"></p>
                        </div>

                        <div x-show="currentPoint.battery_level !== null" class="bg-white/5 rounded-lg p-3">
                            <p class="text-gray-400 text-xs mb-1">แบตเตอรี่</p>
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-gray-700 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full"
                                         :class="currentPoint.battery_level > 20 ? 'bg-green-500' : 'bg-red-500'"
                                         :style="'width: ' + (currentPoint.battery_level || 0) + '%'"></div>
                                </div>
                                <span class="text-white text-sm" x-text="(currentPoint.battery_level || 0) + '%'"></span>
                                <i x-show="currentPoint.is_charging" class="fas fa-bolt text-yellow-400"></i>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="!currentPoint">
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-map-marker-alt text-3xl mb-2"></i>
                        <p class="text-sm">เลือกจุดเพื่อดูรายละเอียด</p>
                    </div>
                </template>

                {{-- Legend --}}
                <div class="mt-6 pt-4 border-t border-white/10">
                    <h4 class="text-gray-400 text-sm mb-3">สัญลักษณ์</h4>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full bg-green-500"></div>
                            <span class="text-gray-300 text-sm">ผู้ให้บริการ</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full bg-blue-500"></div>
                            <span class="text-gray-300 text-sm">ผู้ใช้บริการ</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-0.5 bg-gradient-to-r from-green-500 to-blue-500"></div>
                            <span class="text-gray-300 text-sm">เส้นทาง</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function gpsPlayback() {
    return {
        // Data
        points: @json($logs->map(function($log) {
            return [
                'id' => $log->id,
                'latitude' => (float)$log->latitude,
                'longitude' => (float)$log->longitude,
                'accuracy' => $log->accuracy,
                'speed' => $log->speed,
                'heading' => $log->heading,
                'altitude' => $log->altitude,
                'source' => $log->source,
                'battery_level' => $log->battery_level,
                'is_charging' => $log->is_charging,
                'actor_type' => $log->actor_type,
                'recorded_at' => $log->recorded_at->toISOString(),
            ];
        })),

        // Map
        map: null,
        currentMarker: null,
        pathPolyline: null,
        userPath: [],
        providerPath: [],

        // Playback state
        isPlaying: false,
        currentIndex: 0,
        playbackSpeed: 1,
        playIntervalId: null,

        // Computed
        get currentPoint() {
            return this.points[this.currentIndex] || null;
        },

        get totalPoints() {
            return this.points.length;
        },

        get startTime() {
            return this.points[0]?.recorded_at || null;
        },

        get endTime() {
            return this.points[this.points.length - 1]?.recorded_at || null;
        },

        get currentTime() {
            return this.currentPoint?.recorded_at || null;
        },

        get totalDuration() {
            if (!this.startTime || !this.endTime) return 0;
            return new Date(this.endTime) - new Date(this.startTime);
        },

        get totalDistance() {
            let distance = 0;
            for (let i = 1; i < this.points.length; i++) {
                distance += this.calculateDistance(
                    this.points[i-1].latitude, this.points[i-1].longitude,
                    this.points[i].latitude, this.points[i].longitude
                );
            }
            return distance;
        },

        init() {
            this.loadGoogleMaps();
        },

        loadGoogleMaps() {
            if (typeof google !== 'undefined') {
                this.initMap();
            } else {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initPlaybackMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
                window.initPlaybackMap = () => this.initMap();
            }
        },

        initMap() {
            // Dark style
            const darkStyle = [
                { elementType: "geometry", stylers: [{ color: "#1a1a2e" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#8b5cf6" }] },
                { elementType: "labels.text.stroke", stylers: [{ color: "#1a1a2e" }] },
                { featureType: "road", elementType: "geometry", stylers: [{ color: "#374151" }] },
                { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] },
            ];

            // Calculate center
            let centerLat = 13.7563, centerLng = 100.5018;
            if (this.points.length > 0) {
                centerLat = this.points[0].latitude;
                centerLng = this.points[0].longitude;
            }

            this.map = new google.maps.Map(document.getElementById('playback-map'), {
                center: { lat: centerLat, lng: centerLng },
                zoom: 15,
                styles: darkStyle,
                disableDefaultUI: true,
                zoomControl: true,
            });

            // Draw paths
            this.drawPaths();

            // Set initial marker
            if (this.points.length > 0) {
                this.updateCurrentPosition();
            }
        },

        drawPaths() {
            // Separate paths by actor type
            const userPoints = this.points.filter(p => p.actor_type === 'user');
            const providerPoints = this.points.filter(p => p.actor_type === 'provider');

            // Draw user path
            if (userPoints.length > 1) {
                new google.maps.Polyline({
                    path: userPoints.map(p => ({ lat: p.latitude, lng: p.longitude })),
                    geodesic: true,
                    strokeColor: '#3B82F6',
                    strokeOpacity: 0.8,
                    strokeWeight: 4,
                    map: this.map,
                });
            }

            // Draw provider path
            if (providerPoints.length > 1) {
                new google.maps.Polyline({
                    path: providerPoints.map(p => ({ lat: p.latitude, lng: p.longitude })),
                    geodesic: true,
                    strokeColor: '#10B981',
                    strokeOpacity: 0.8,
                    strokeWeight: 4,
                    map: this.map,
                });
            }

            // Draw markers at start/end
            if (this.points.length > 0) {
                // Start marker
                new google.maps.Marker({
                    position: { lat: this.points[0].latitude, lng: this.points[0].longitude },
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
                const lastPoint = this.points[this.points.length - 1];
                new google.maps.Marker({
                    position: { lat: lastPoint.latitude, lng: lastPoint.longitude },
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
            }
        },

        updateCurrentPosition() {
            const point = this.currentPoint;
            if (!point) return;

            const position = { lat: point.latitude, lng: point.longitude };
            const color = point.actor_type === 'user' ? '#3B82F6' : '#10B981';

            if (this.currentMarker) {
                this.currentMarker.setPosition(position);
                this.currentMarker.setIcon({
                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                    scale: 6,
                    fillColor: color,
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
                        fillColor: color,
                        fillOpacity: 1,
                        strokeColor: '#fff',
                        strokeWeight: 2,
                        rotation: point.heading || 0,
                    },
                });
            }

            // Pan to current position
            this.map.panTo(position);
        },

        togglePlay() {
            this.isPlaying = !this.isPlaying;

            if (this.isPlaying) {
                this.play();
            } else {
                this.pause();
            }
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

        // Helpers
        formatTime(isoString) {
            if (!isoString) return '-';
            return new Date(isoString).toLocaleTimeString('th-TH');
        },

        formatDateTime(isoString) {
            if (!isoString) return '-';
            return new Date(isoString).toLocaleString('th-TH');
        },

        formatDuration(ms) {
            if (!ms) return '-';
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);

            if (hours > 0) {
                return `${hours}ชม. ${minutes % 60}น.`;
            }
            return `${minutes}น. ${seconds % 60}วิ.`;
        },

        formatDistance(km) {
            if (km < 1) {
                return (km * 1000).toFixed(0) + ' m';
            }
            return km.toFixed(2) + ' km';
        },

        calculateDistance(lat1, lon1, lat2, lon2) {
            const R = 6371; // km
            const dLat = this.deg2rad(lat2 - lat1);
            const dLon = this.deg2rad(lon2 - lon1);
            const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                      Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) *
                      Math.sin(dLon/2) * Math.sin(dLon/2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
            return R * c;
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
