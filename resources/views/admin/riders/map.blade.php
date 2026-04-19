{{--
    แผนที่ GPS ไรเดอร์ทั้งหมด
    ใช้ระบบ GPS Monitoring เดียวกับ ServiceBooking
    แสดงไรเดอร์ที่ออนไลน์/ไม่ว่าง/ออฟไลน์ บนแผนที่แบบ Real-time
--}}
@extends('layouts.admin-v3')

@section('title', 'แผนที่ GPS ไรเดอร์')

@push('styles')
<style>
    /* Futuristic Radar Animation */
    @keyframes radar-sweep {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes pulse-ring {
        0% { transform: scale(0.8); opacity: 1; }
        100% { transform: scale(2); opacity: 0; }
    }

    @keyframes data-flow {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    .radar-sweep {
        animation: radar-sweep 4s linear infinite;
    }

    .pulse-ring {
        animation: pulse-ring 2s ease-out infinite;
    }

    .data-flow-bg {
        background: linear-gradient(270deg, #10b981, #3b82f6, #8b5cf6, #10b981);
        background-size: 400% 400%;
        animation: data-flow 8s ease infinite;
    }

    /* Glassmorphism Panels */
    .glass-panel {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .dark .glass-panel {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Neon Glow Effects */
    .neon-green { box-shadow: 0 0 10px #10b981, 0 0 20px rgba(16, 185, 129, 0.5); }
    .neon-yellow { box-shadow: 0 0 10px #f59e0b, 0 0 20px rgba(245, 158, 11, 0.5); }
    .neon-gray { box-shadow: 0 0 10px #6b7280, 0 0 20px rgba(107, 114, 128, 0.5); }

    /* Map Container */
    #rider-map {
        height: calc(100vh - 200px);
        min-height: 600px;
    }

    /* Custom Scrollbar */
    .custom-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.1);
        border-radius: 3px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(139, 92, 246, 0.5);
        border-radius: 3px;
    }

    /* Fullscreen Mode */
    .rider-fullscreen-mode {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 9999 !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .rider-fullscreen-mode #rider-map {
        height: 100vh !important;
        min-height: 100vh !important;
        border-radius: 0 !important;
    }

    .rider-fullscreen-mode .map-header {
        display: none !important;
    }

    .fullscreen-exit-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 10002;
        padding: 12px 20px;
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.9), rgba(220, 38, 38, 0.9));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 12px;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.4);
    }

    .fullscreen-exit-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 30px rgba(239, 68, 68, 0.6);
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 rider-map-wrapper"
     x-data="riderGpsMonitor()"
     :class="{ 'rider-fullscreen-mode': isFullscreen }">
    {{-- Header --}}
    <div class="sticky top-0 z-50 data-flow-bg map-header">
        <div class="bg-black/50 backdrop-blur-xl border-b border-white/10">
            <div class="max-w-full mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    {{-- Title --}}
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.riders.index') }}"
                           class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition">
                            <i class="fas fa-arrow-left text-white"></i>
                        </a>
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                                <i class="fas fa-motorcycle text-white text-xl"></i>
                            </div>
                            <div class="absolute inset-0 w-12 h-12 rounded-full bg-green-400/50 pulse-ring"></div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                                <span>แผนที่ GPS ไรเดอร์</span>
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full border border-green-500/30">
                                    LIVE
                                </span>
                            </h1>
                            <p class="text-gray-400 text-sm">ติดตามตำแหน่งไรเดอร์แบบ Real-time</p>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-400" x-text="stats.online">0</p>
                            <p class="text-xs text-gray-400">ออนไลน์</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-yellow-400" x-text="stats.busy">0</p>
                            <p class="text-xs text-gray-400">กำลังส่งงาน</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-gray-400" x-text="stats.offline">0</p>
                            <p class="text-xs text-gray-400">ออฟไลน์</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-400" x-text="stats.total">0</p>
                            <p class="text-xs text-gray-400">ทั้งหมด</p>
                        </div>
                    </div>

                    {{-- Refresh Control --}}
                    <div class="flex items-center gap-3">
                        <span class="text-gray-400 text-sm">
                            อัพเดทล่าสุด: <span x-text="lastRefresh" class="text-green-400">-</span>
                        </span>
                        <button @click="refreshData()"
                                class="p-3 bg-white/10 hover:bg-white/20 rounded-xl transition"
                                :class="{ 'animate-spin': loading }">
                            <i class="fas fa-sync-alt text-white"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex">
        {{-- Sidebar Filters --}}
        <div class="w-80 flex-shrink-0 h-[calc(100vh-100px)] overflow-y-auto custom-scrollbar p-4 space-y-4"
             :class="{ 'hidden': isFullscreen }">
            {{-- Entity Type Filter --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-filter text-purple-400"></i>
                    สถานะที่แสดง
                </h3>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showOnline"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-green-500 focus:ring-green-500">
                        <div class="w-4 h-4 rounded-full bg-green-500 neon-green"></div>
                        <span class="text-gray-300">ออนไลน์ (พร้อมรับงาน)</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showBusy"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-yellow-500 focus:ring-yellow-500">
                        <div class="w-4 h-4 rounded-full bg-yellow-500 neon-yellow"></div>
                        <span class="text-gray-300">กำลังส่งงาน</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showOffline"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-gray-500 focus:ring-gray-500">
                        <div class="w-4 h-4 rounded-full bg-gray-500 neon-gray"></div>
                        <span class="text-gray-300">ออฟไลน์</span>
                    </label>
                </div>
            </div>

            {{-- Vehicle Type Filter --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-car text-blue-400"></i>
                    ประเภทรถ
                </h3>
                <select x-model="filters.vehicleType"
                        class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-purple-500 focus:border-purple-500">
                    <option value="">ทั้งหมด</option>
                    <option value="motorcycle">มอเตอร์ไซค์</option>
                    <option value="car">รถยนต์</option>
                    <option value="pickup">รถกระบะ</option>
                    <option value="van">รถตู้</option>
                    <option value="truck">รถบรรทุก</option>
                </select>
            </div>

            {{-- Auto Refresh --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-clock text-yellow-400"></i>
                    รีเฟรชอัตโนมัติ
                </h3>
                <select x-model="filters.refreshInterval"
                        @change="setupAutoRefresh()"
                        class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-purple-500 focus:border-purple-500">
                    <option value="0">ปิด</option>
                    <option value="5">ทุก 5 วินาที</option>
                    <option value="10">ทุก 10 วินาที</option>
                    <option value="30">ทุก 30 วินาที</option>
                    <option value="60">ทุก 1 นาที</option>
                </select>

                <button @click="refreshData()"
                        class="w-full mt-3 py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-sync-alt mr-2"></i>
                    รีเฟรชตอนนี้
                </button>
            </div>

            {{-- Rider List --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-list text-green-400"></i>
                    รายการไรเดอร์
                </h3>
                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                    <template x-for="rider in riders" :key="rider.id">
                        <div @click="focusOnRider(rider)"
                             class="p-3 bg-white/5 hover:bg-white/10 rounded-xl cursor-pointer transition border border-transparent hover:border-purple-500/30">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-white font-medium text-sm" x-text="rider.name"></span>
                                <span class="px-2 py-0.5 text-xs rounded-full"
                                      :class="getAvailabilityBadgeClass(rider.availability)"
                                      x-text="getAvailabilityLabel(rider.availability)"></span>
                            </div>
                            <div class="text-gray-400 text-xs">
                                <p class="flex items-center gap-1">
                                    <i class="fas fa-phone text-blue-400"></i>
                                    <span x-text="rider.phone"></span>
                                </p>
                                <p class="flex items-center gap-1 mt-1">
                                    <i class="fas" :class="getVehicleIcon(rider.vehicle_type)"></i>
                                    <span x-text="getVehicleLabel(rider.vehicle_type)"></span>
                                </p>
                                <p x-show="rider.current_job" class="flex items-center gap-1 mt-1 text-yellow-400">
                                    <i class="fas fa-shipping-fast"></i>
                                    <span>กำลังส่งงาน</span>
                                </p>
                            </div>
                        </div>
                    </template>
                    <div x-show="riders.length === 0" class="text-center text-gray-500 py-4">
                        <i class="fas fa-motorcycle text-2xl mb-2"></i>
                        <p class="text-sm">ไม่พบไรเดอร์ที่มีตำแหน่ง GPS</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Map Area --}}
        <div class="flex-1 relative">
            {{-- Map --}}
            <div id="rider-map" class="w-full rounded-l-3xl overflow-hidden border-l border-white/10"></div>

            {{-- Fullscreen Exit Button --}}
            <button x-show="isFullscreen"
                    x-cloak
                    @click="toggleFullscreen()"
                    class="fullscreen-exit-btn flex items-center gap-2"
                    x-transition>
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 3v3a2 2 0 0 1-2 2H3"/>
                    <path d="M21 8h-3a2 2 0 0 1-2-2V3"/>
                    <path d="M3 16h3a2 2 0 0 1 2 2v3"/>
                    <path d="M16 21v-3a2 2 0 0 1 2-2h3"/>
                </svg>
                <span>ออกจากเต็มจอ (ESC)</span>
            </button>

            {{-- Floating Radar Overlay --}}
            <div class="absolute top-4 right-4 w-32 h-32 pointer-events-none" :class="{ 'hidden': isFullscreen }">
                <div class="relative w-full h-full">
                    <div class="absolute inset-0 rounded-full bg-green-900/30 border border-green-500/30"></div>
                    <div class="absolute inset-2 rounded-full bg-green-900/20 border border-green-500/20"></div>
                    <div class="absolute inset-4 rounded-full bg-green-900/10 border border-green-500/10"></div>
                    <div class="absolute inset-0 rounded-full overflow-hidden">
                        <div class="radar-sweep absolute inset-0 origin-center"
                             style="background: conic-gradient(from 0deg, transparent 0deg, rgba(16, 185, 129, 0.3) 30deg, transparent 60deg);"></div>
                    </div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>
                </div>
            </div>

            {{-- Map Controls --}}
            <div class="absolute bottom-4 right-4 flex flex-col gap-2">
                <button @click="toggleFullscreen()"
                        class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                        :class="{ 'ring-2 ring-purple-500': isFullscreen }">
                    <svg x-show="!isFullscreen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
                        <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
                    </svg>
                    <svg x-show="isFullscreen" x-cloak class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 3v3a2 2 0 0 1-2 2H3"/>
                        <path d="M21 8h-3a2 2 0 0 1-2-2V3"/>
                        <path d="M3 16h3a2 2 0 0 1 2 2v3"/>
                        <path d="M16 21v-3a2 2 0 0 1 2-2h3"/>
                    </svg>
                </button>
                <div class="h-px bg-white/20 my-1"></div>
                <button @click="fitAllMarkers()"
                        class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                        title="แสดงทั้งหมด">
                    <i class="fas fa-expand-arrows-alt"></i>
                </button>
                <button @click="centerOnBangkok()"
                        class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                        title="กรุงเทพฯ">
                    <i class="fas fa-crosshairs"></i>
                </button>
                <a href="{{ route('admin.gps-monitoring.index') }}"
                   class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                   title="GPS Monitoring Center">
                    <i class="fas fa-satellite"></i>
                </a>
            </div>

            {{-- Selected Rider Info Panel --}}
            <div x-show="selectedRider"
                 x-cloak
                 x-transition
                 class="absolute top-4 left-4 w-80 glass-panel rounded-2xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-white font-bold">รายละเอียดไรเดอร์</h4>
                    <button @click="selectedRider = null" class="text-gray-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <template x-if="selectedRider">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <p class="text-white font-medium" x-text="selectedRider.name"></p>
                                <p class="text-gray-400 text-sm" x-text="selectedRider.phone"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">สถานะ</p>
                                <span class="px-2 py-0.5 text-xs rounded-full"
                                      :class="getAvailabilityBadgeClass(selectedRider.availability)"
                                      x-text="getAvailabilityLabel(selectedRider.availability)"></span>
                            </div>
                            <div class="bg-white/5 rounded-lg p-3">
                                <p class="text-gray-400 text-xs mb-1">ประเภทรถ</p>
                                <p class="text-white text-sm" x-text="getVehicleLabel(selectedRider.vehicle_type)"></p>
                            </div>
                        </div>

                        <div x-show="selectedRider.vehicle_plate" class="bg-white/5 rounded-lg p-3">
                            <p class="text-gray-400 text-xs mb-1">ทะเบียนรถ</p>
                            <p class="text-white font-mono" x-text="selectedRider.vehicle_plate"></p>
                        </div>

                        <div x-show="selectedRider.current_job" class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3">
                            <p class="text-yellow-400 text-xs mb-1">
                                <i class="fas fa-shipping-fast mr-1"></i>
                                กำลังส่งงาน
                            </p>
                            <p class="text-white text-sm" x-text="selectedRider.current_job?.dropoff_address || 'ไม่ระบุ'"></p>
                        </div>

                        <div class="flex gap-2 pt-2">
                            <a :href="'/admin/riders/' + selectedRider.id"
                               class="flex-1 py-2 bg-purple-600 hover:bg-purple-500 text-white text-center rounded-lg transition text-sm">
                                <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                            </a>
                            <a :href="'/admin/riders/' + selectedRider.id + '/locations'"
                               class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition text-sm">
                                <i class="fas fa-history"></i>
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function riderGpsMonitor() {
    return {
        // State
        loading: false,
        lastRefresh: '-',
        map: null,
        isFullscreen: false,

        // Data
        riders: [],
        riderMarkers: [],
        selectedRider: null,

        // Stats
        stats: {
            total: 0,
            online: 0,
            busy: 0,
            offline: 0,
        },

        // Filters
        filters: {
            showOnline: true,
            showBusy: true,
            showOffline: false,
            vehicleType: '',
            refreshInterval: '10',
        },

        refreshIntervalId: null,

        init() {
            this.loadGoogleMaps();
            this.setupAutoRefresh();

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isFullscreen) {
                    this.toggleFullscreen();
                }
            });
        },

        loadGoogleMaps() {
            if (typeof google !== 'undefined') {
                this.initMap();
            } else {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&callback=initRiderMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
                window.initRiderMap = () => this.initMap();
            }
        },

        initMap() {
            const darkStyle = [
                { elementType: "geometry", stylers: [{ color: "#1a1a2e" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#8b5cf6" }] },
                { elementType: "labels.text.stroke", stylers: [{ color: "#1a1a2e" }] },
                { featureType: "road", elementType: "geometry", stylers: [{ color: "#374151" }] },
                { featureType: "road.highway", elementType: "geometry", stylers: [{ color: "#4a5568" }] },
                { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] },
            ];

            this.map = new google.maps.Map(document.getElementById('rider-map'), {
                center: { lat: 13.7563, lng: 100.5018 },
                zoom: 11,
                styles: darkStyle,
                disableDefaultUI: true,
                zoomControl: true,
                gestureHandling: 'greedy',
            });

            this.refreshData();
        },

        async refreshData() {
            if (this.loading) return;
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    show_online: this.filters.showOnline ? '1' : '0',
                    show_busy: this.filters.showBusy ? '1' : '0',
                    show_offline: this.filters.showOffline ? '1' : '0',
                    vehicle_type: this.filters.vehicleType,
                });

                const response = await fetch(`/admin/riders/gps-data?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.riders = data.data.riders || [];
                    this.stats = data.data.stats || this.stats;
                    this.updateMarkers();
                }

                this.lastRefresh = new Date().toLocaleTimeString('th-TH');
            } catch (error) {
                console.error('Failed to refresh rider GPS data:', error);
            } finally {
                this.loading = false;
            }
        },

        updateMarkers() {
            // ลบ markers เก่า
            this.riderMarkers.forEach(m => m.setMap(null));
            this.riderMarkers = [];

            // เพิ่ม markers ใหม่
            this.riders.forEach(rider => {
                const marker = this.createRiderMarker(rider);
                this.riderMarkers.push(marker);
            });
        },

        createRiderMarker(rider) {
            const color = rider.availability === 'online' ? '#10B981' :
                         rider.availability === 'busy' ? '#F59E0B' : '#6B7280';

            const iconContent = rider.vehicle_type === 'car' ? 'fa-car' :
                               rider.vehicle_type === 'pickup' ? 'fa-truck-pickup' :
                               rider.vehicle_type === 'van' ? 'fa-shuttle-van' :
                               rider.vehicle_type === 'truck' ? 'fa-truck' : 'fa-motorcycle';

            const marker = new google.maps.Marker({
                position: { lat: rider.latitude, lng: rider.longitude },
                map: this.map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
                            <circle cx="20" cy="20" r="18" fill="${color}" stroke="#fff" stroke-width="3"/>
                            <circle cx="20" cy="20" r="8" fill="white"/>
                            <circle cx="20" cy="20" r="4" fill="${color}"/>
                        </svg>
                    `),
                    anchor: new google.maps.Point(20, 20),
                    scaledSize: new google.maps.Size(40, 40),
                },
                title: rider.name,
            });

            marker.addListener('click', () => {
                this.selectedRider = rider;
                this.map.panTo({ lat: rider.latitude, lng: rider.longitude });
                this.map.setZoom(16);
            });

            return marker;
        },

        focusOnRider(rider) {
            this.selectedRider = rider;
            this.map.panTo({ lat: rider.latitude, lng: rider.longitude });
            this.map.setZoom(16);
        },

        setupAutoRefresh() {
            if (this.refreshIntervalId) {
                clearInterval(this.refreshIntervalId);
            }

            const interval = parseInt(this.filters.refreshInterval) * 1000;
            if (interval > 0) {
                this.refreshIntervalId = setInterval(() => this.refreshData(), interval);
            }
        },

        toggleFullscreen() {
            this.isFullscreen = !this.isFullscreen;
            setTimeout(() => {
                if (this.map) {
                    google.maps.event.trigger(this.map, 'resize');
                }
            }, 150);
        },

        fitAllMarkers() {
            if (this.riderMarkers.length === 0) return;

            const bounds = new google.maps.LatLngBounds();
            this.riderMarkers.forEach(marker => bounds.extend(marker.getPosition()));
            this.map.fitBounds(bounds);
        },

        centerOnBangkok() {
            this.map.panTo({ lat: 13.7563, lng: 100.5018 });
            this.map.setZoom(11);
        },

        getAvailabilityBadgeClass(availability) {
            const classes = {
                online: 'bg-green-500/20 text-green-400 border border-green-500/30',
                busy: 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
                offline: 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
            };
            return classes[availability] || classes.offline;
        },

        getAvailabilityLabel(availability) {
            const labels = {
                online: 'ออนไลน์',
                busy: 'กำลังส่งงาน',
                offline: 'ออฟไลน์',
            };
            return labels[availability] || 'ไม่ทราบ';
        },

        getVehicleIcon(type) {
            const icons = {
                motorcycle: 'fa-motorcycle',
                car: 'fa-car',
                pickup: 'fa-truck-pickup',
                van: 'fa-shuttle-van',
                truck: 'fa-truck',
            };
            return icons[type] || 'fa-motorcycle';
        },

        getVehicleLabel(type) {
            const labels = {
                motorcycle: 'มอเตอร์ไซค์',
                car: 'รถยนต์',
                pickup: 'รถกระบะ',
                van: 'รถตู้',
                truck: 'รถบรรทุก',
            };
            return labels[type] || 'ไม่ระบุ';
        },

        destroy() {
            if (this.refreshIntervalId) {
                clearInterval(this.refreshIntervalId);
            }
        },
    }
}
</script>
@endpush
