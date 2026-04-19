{{--
    Admin Riders Playback - เล่นย้อนหลังตำแหน่ง GPS ของไรเดอร์
    แสดงเส้นทางและประวัติการเคลื่อนที่
--}}

@extends('layouts.admin-v3')

@section('title', 'เล่นย้อนหลัง GPS: ' . $rider->full_name)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900" x-data="riderPlayback()">
    {{-- Header --}}
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.riders.locations', $rider) }}"
                   class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition backdrop-blur-lg border border-white/10">
                    <i class="fas fa-arrow-left text-white"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                        <i class="fas fa-play-circle text-purple-400"></i>
                        เล่นย้อนหลัง GPS: {{ $rider->full_name }}
                    </h1>
                    <p class="text-gray-400 text-sm mt-1">
                        {{ $logs->count() }} จุด | ข้อมูล 24 ชม.ล่าสุด
                    </p>
                </div>
            </div>

            {{-- Controls --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 bg-white/10 backdrop-blur-lg rounded-xl px-4 py-2 border border-white/10">
                    <label class="text-gray-300 text-sm">ความเร็ว:</label>
                    <select x-model="playbackSpeed"
                            class="bg-transparent text-white border-0 focus:ring-0 text-sm">
                        <option value="0.5" class="text-gray-900">0.5x</option>
                        <option value="1" class="text-gray-900">1x</option>
                        <option value="2" class="text-gray-900">2x</option>
                        <option value="5" class="text-gray-900">5x</option>
                        <option value="10" class="text-gray-900">10x</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Map & Timeline --}}
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            {{-- Map --}}
            <div class="lg:col-span-3">
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl overflow-hidden border border-white/10 relative">
                    <div id="playbackMap" class="h-[600px] w-full"></div>

                    {{-- Playback Controls --}}
                    <div class="absolute bottom-4 left-4 right-4 bg-gray-900/90 backdrop-blur-lg rounded-xl p-4 border border-white/10">
                        <div class="flex items-center gap-4">
                            {{-- Play/Pause Button --}}
                            <button @click="togglePlayback()"
                                    class="w-12 h-12 rounded-full bg-purple-600 hover:bg-purple-500 text-white flex items-center justify-center transition">
                                <i :class="isPlaying ? 'fas fa-pause' : 'fas fa-play'" class="text-lg"></i>
                            </button>

                            {{-- Progress Bar --}}
                            <div class="flex-1">
                                <input type="range" min="0" :max="totalPoints - 1" x-model="currentIndex"
                                       @input="seekTo(currentIndex)"
                                       class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-purple-500">
                            </div>

                            {{-- Time Display --}}
                            <div class="text-white text-sm min-w-[120px] text-right">
                                <span x-text="currentTime"></span>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="flex items-center gap-6 mt-3 text-sm">
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-route text-purple-400"></i>
                                <span>ระยะทาง: <span x-text="distanceTraveled" class="text-white font-bold"></span> กม.</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-tachometer-alt text-green-400"></i>
                                <span>ความเร็ว: <span x-text="currentSpeed" class="text-white font-bold"></span> กม./ชม.</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                <i class="fas fa-map-pin text-yellow-400"></i>
                                <span>จุดที่ <span x-text="parseInt(currentIndex) + 1" class="text-white font-bold"></span> / <span x-text="totalPoints"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Timeline Sidebar --}}
            <div class="lg:col-span-1">
                <div class="bg-white/10 backdrop-blur-xl rounded-2xl p-4 border border-white/10 h-[600px] overflow-hidden flex flex-col">
                    <h3 class="text-white font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-history text-purple-400"></i>
                        ประวัติตำแหน่ง
                    </h3>

                    <div class="flex-1 overflow-y-auto space-y-2 pr-2" id="timelineContainer">
                        @forelse($logs as $index => $log)
                            <div class="timeline-item p-3 rounded-lg bg-white/5 hover:bg-white/10 cursor-pointer transition"
                                 data-index="{{ $index }}"
                                 @click="seekTo({{ $index }})">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-purple-600/30 flex items-center justify-center text-purple-400 text-xs font-bold">
                                        {{ $index + 1 }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-white text-sm">{{ $log->created_at->format('H:i:s') }}</p>
                                        <p class="text-gray-400 text-xs">
                                            @if($log->speed)
                                                {{ number_format($log->speed, 1) }} กม./ชม.
                                            @else
                                                หยุดนิ่ง
                                            @endif
                                        </p>
                                    </div>
                                    @if($log->battery_level)
                                        <div class="text-xs {{ $log->battery_level < 20 ? 'text-red-400' : ($log->battery_level < 50 ? 'text-yellow-400' : 'text-green-400') }}">
                                            <i class="fas fa-battery-{{ $log->battery_level < 20 ? 'empty' : ($log->battery_level < 50 ? 'half' : 'full') }}"></i>
                                            {{ $log->battery_level }}%
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-gray-400 py-8">
                                <i class="fas fa-map-marked-alt text-4xl mb-4"></i>
                                <p>ไม่พบข้อมูลตำแหน่ง</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key', '') }}&libraries=geometry"></script>
<script>
    function riderPlayback() {
        return {
            map: null,
            marker: null,
            path: null,
            pathCoordinates: [],
            isPlaying: false,
            currentIndex: 0,
            playbackSpeed: 1,
            playInterval: null,
            totalPoints: {{ $logs->count() }},
            currentTime: '--:--:--',
            currentSpeed: 0,
            distanceTraveled: 0,

            init() {
                // เตรียมข้อมูล path
                this.pathCoordinates = @json($logs->map(function($log) {
                    return [
                        'lat' => (float) $log->latitude,
                        'lng' => (float) $log->longitude,
                        'timestamp' => $log->created_at->format('H:i:s'),
                        'speed' => $log->speed ?? 0,
                        'battery' => $log->battery_level,
                    ];
                })->values());

                this.initMap();
            },

            initMap() {
                if (this.pathCoordinates.length === 0) return;

                const center = this.pathCoordinates[0];

                // Dark mode style
                const darkStyle = [
                    { elementType: 'geometry', stylers: [{ color: '#242f3e' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#242f3e' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#746855' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
                    { featureType: 'road', elementType: 'geometry.stroke', stylers: [{ color: '#212a37' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#17263c' }] },
                ];

                this.map = new google.maps.Map(document.getElementById('playbackMap'), {
                    zoom: 15,
                    center: center,
                    styles: darkStyle,
                    disableDefaultUI: true,
                    zoomControl: true,
                });

                // Draw full path
                this.path = new google.maps.Polyline({
                    path: this.pathCoordinates,
                    geodesic: true,
                    strokeColor: '#9333ea',
                    strokeOpacity: 0.8,
                    strokeWeight: 4,
                });
                this.path.setMap(this.map);

                // Rider marker
                this.marker = new google.maps.Marker({
                    position: center,
                    map: this.map,
                    icon: {
                        path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                        scale: 6,
                        fillColor: '#22c55e',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                        rotation: 0,
                    },
                });

                // Start marker
                new google.maps.Marker({
                    position: this.pathCoordinates[0],
                    map: this.map,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: '#22c55e',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 2,
                    },
                    label: {
                        text: 'เริ่ม',
                        color: '#ffffff',
                        fontSize: '10px',
                    },
                });

                // End marker
                if (this.pathCoordinates.length > 1) {
                    new google.maps.Marker({
                        position: this.pathCoordinates[this.pathCoordinates.length - 1],
                        map: this.map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 10,
                            fillColor: '#ef4444',
                            fillOpacity: 1,
                            strokeColor: '#ffffff',
                            strokeWeight: 2,
                        },
                        label: {
                            text: 'จบ',
                            color: '#ffffff',
                            fontSize: '10px',
                        },
                    });
                }

                // Fit bounds
                const bounds = new google.maps.LatLngBounds();
                this.pathCoordinates.forEach(coord => bounds.extend(coord));
                this.map.fitBounds(bounds);

                this.updateDisplay();
            },

            togglePlayback() {
                if (this.isPlaying) {
                    this.pausePlayback();
                } else {
                    this.startPlayback();
                }
            },

            startPlayback() {
                this.isPlaying = true;
                this.playInterval = setInterval(() => {
                    if (this.currentIndex < this.totalPoints - 1) {
                        this.currentIndex++;
                        this.updateDisplay();
                    } else {
                        this.pausePlayback();
                    }
                }, 1000 / this.playbackSpeed);
            },

            pausePlayback() {
                this.isPlaying = false;
                if (this.playInterval) {
                    clearInterval(this.playInterval);
                    this.playInterval = null;
                }
            },

            seekTo(index) {
                this.currentIndex = parseInt(index);
                this.updateDisplay();

                // Scroll timeline item into view
                const item = document.querySelector(`.timeline-item[data-index="${index}"]`);
                if (item) {
                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },

            updateDisplay() {
                if (this.pathCoordinates.length === 0) return;

                const point = this.pathCoordinates[this.currentIndex];

                // Update marker position
                if (this.marker) {
                    this.marker.setPosition(point);

                    // Calculate rotation if there's a next point
                    if (this.currentIndex < this.pathCoordinates.length - 1) {
                        const nextPoint = this.pathCoordinates[this.currentIndex + 1];
                        const heading = google.maps.geometry.spherical.computeHeading(
                            new google.maps.LatLng(point.lat, point.lng),
                            new google.maps.LatLng(nextPoint.lat, nextPoint.lng)
                        );
                        const icon = this.marker.getIcon();
                        icon.rotation = heading;
                        this.marker.setIcon(icon);
                    }
                }

                // Update time display
                this.currentTime = point.timestamp;
                this.currentSpeed = point.speed ? point.speed.toFixed(1) : 0;

                // Calculate distance traveled
                let distance = 0;
                for (let i = 1; i <= this.currentIndex; i++) {
                    const p1 = this.pathCoordinates[i - 1];
                    const p2 = this.pathCoordinates[i];
                    distance += google.maps.geometry.spherical.computeDistanceBetween(
                        new google.maps.LatLng(p1.lat, p1.lng),
                        new google.maps.LatLng(p2.lat, p2.lng)
                    );
                }
                this.distanceTraveled = (distance / 1000).toFixed(2);

                // Highlight timeline item
                document.querySelectorAll('.timeline-item').forEach((el, i) => {
                    el.classList.toggle('bg-purple-600/30', i === this.currentIndex);
                    el.classList.toggle('border-purple-500', i === this.currentIndex);
                });
            },
        }
    }
</script>
@endpush
@endsection
