{{--
    Admin GPS Monitoring Dashboard
    แสดงตำแหน่ง GPS ทั้งหมดบนแผนที่แบบ Real-time
    รองรับการกรองตามเงื่อนไขต่างๆ
--}}
@extends('layouts.admin')

@section('title', 'GPS Monitoring Center')

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
    .neon-blue { box-shadow: 0 0 10px #3b82f6, 0 0 20px rgba(59, 130, 246, 0.5); }
    .neon-purple { box-shadow: 0 0 10px #8b5cf6, 0 0 20px rgba(139, 92, 246, 0.5); }
    .neon-red { box-shadow: 0 0 10px #ef4444, 0 0 20px rgba(239, 68, 68, 0.5); }

    /* Map Container */
    #gps-monitor-map {
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
    .gps-fullscreen-mode {
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
        max-width: none !important;
        max-height: none !important;
        border-radius: 0 !important;
    }

    .gps-fullscreen-mode #gps-monitor-map {
        height: 100vh !important;
        min-height: 100vh !important;
        border-radius: 0 !important;
    }

    .gps-fullscreen-mode .gps-header-section {
        display: none !important;
    }

    .gps-fullscreen-mode .gps-sidebar-section {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        z-index: 10001;
        transition: transform 0.3s ease;
    }

    .gps-fullscreen-mode .gps-sidebar-section.sidebar-hidden {
        transform: translateX(-100%);
    }

    .gps-fullscreen-mode .gps-map-section {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100vw;
        height: 100vh;
    }

    /* Fullscreen Exit Button (ปุ่มลอยที่มุมบนขวา) */
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

    /* Fullscreen Toggle Sidebar Button */
    .fullscreen-toggle-sidebar {
        position: fixed;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        z-index: 10001;
        padding: 16px 8px;
        background: rgba(139, 92, 246, 0.9);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-left: none;
        border-radius: 0 12px 12px 0;
        color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .fullscreen-toggle-sidebar:hover {
        background: rgba(139, 92, 246, 1);
        padding-right: 12px;
    }

    /* Fullscreen button active state */
    .fullscreen-btn-active {
        ring: 2px;
        ring-color: #a855f7;
        background: rgba(139, 92, 246, 0.3) !important;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-gray-900 gps-monitor-wrapper"
     x-data="gpsMonitoringCenter()"
     :class="{ 'gps-fullscreen-mode': isFullscreen }">
    {{-- Header --}}
    <div class="sticky top-0 z-50 data-flow-bg gps-header-section">
        <div class="bg-black/50 backdrop-blur-xl border-b border-white/10">
            <div class="max-w-full mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    {{-- Title --}}
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="w-12 h-12 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center">
                                <i class="fas fa-satellite text-white text-xl"></i>
                            </div>
                            <div class="absolute inset-0 w-12 h-12 rounded-full bg-green-400/50 pulse-ring"></div>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                                <span>GPS Monitoring Center</span>
                                <span class="px-2 py-1 bg-green-500/20 text-green-400 text-xs rounded-full border border-green-500/30">
                                    LIVE
                                </span>
                            </h1>
                            <p class="text-gray-400 text-sm">Real-time GPS Tracking Dashboard</p>
                        </div>
                    </div>

                    {{-- Quick Stats --}}
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-green-400" x-text="stats.activeProviders">0</p>
                            <p class="text-xs text-gray-400">Providers Online</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-blue-400" x-text="stats.activeUsers">0</p>
                            <p class="text-xs text-gray-400">Users Tracking</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-400" x-text="stats.activeBookings">0</p>
                            <p class="text-xs text-gray-400">Active Bookings</p>
                        </div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-yellow-400" x-text="stats.totalUpdates">0</p>
                            <p class="text-xs text-gray-400">Updates/min</p>
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
        <div class="w-80 flex-shrink-0 h-[calc(100vh-100px)] overflow-y-auto custom-scrollbar p-4 space-y-4 gps-sidebar-section"
             :class="{ 'sidebar-hidden': isFullscreen && sidebarHidden }">
            {{-- Entity Type Filter --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-filter text-purple-400"></i>
                    ประเภทที่แสดง
                </h3>
                <div class="space-y-2">
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showProviders"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-green-500 focus:ring-green-500">
                        <div class="w-4 h-4 rounded-full bg-green-500 neon-green"></div>
                        <span class="text-gray-300">ผู้ให้บริการ</span>
                        <span class="ml-auto text-green-400 text-sm" x-text="'(' + providerMarkers.length + ')'"></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showUsers"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-blue-500 focus:ring-blue-500">
                        <div class="w-4 h-4 rounded-full bg-blue-500 neon-blue"></div>
                        <span class="text-gray-300">ผู้ใช้บริการ</span>
                        <span class="ml-auto text-blue-400 text-sm" x-text="'(' + userMarkers.length + ')'"></span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showServiceLocations"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-red-500 focus:ring-red-500">
                        <div class="w-4 h-4 bg-red-500 neon-red" style="clip-path: polygon(50% 0%, 100% 100%, 0% 100%);"></div>
                        <span class="text-gray-300">สถานที่ให้บริการ</span>
                    </label>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                        <input type="checkbox" x-model="filters.showRoutes"
                               class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-purple-500 focus:ring-purple-500">
                        <div class="w-4 h-2 bg-gradient-to-r from-green-500 to-red-500 rounded-full"></div>
                        <span class="text-gray-300">เส้นทางเดินทาง</span>
                    </label>
                </div>
            </div>

            {{-- Booking Status Filter --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-tasks text-blue-400"></i>
                    สถานะการจอง
                </h3>
                <div class="space-y-2">
                    <template x-for="status in bookingStatuses" :key="status.value">
                        <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-white/5 cursor-pointer transition">
                            <input type="checkbox" :value="status.value" x-model="filters.statuses"
                                   class="w-5 h-5 rounded bg-gray-700 border-gray-600 focus:ring-purple-500"
                                   :class="'text-' + status.color + '-500'">
                            <span class="w-3 h-3 rounded-full" :class="'bg-' + status.color + '-500'"></span>
                            <span class="text-gray-300" x-text="status.label"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Limit & Time Filter --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-sliders-h text-yellow-400"></i>
                    ตัวกรองเพิ่มเติม
                </h3>

                {{-- Limit --}}
                <div class="mb-4">
                    <label class="text-gray-400 text-sm block mb-2">จำนวนที่แสดง</label>
                    <select x-model="filters.limit"
                            class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="10">10 รายการ</option>
                        <option value="25">25 รายการ</option>
                        <option value="50">50 รายการ</option>
                        <option value="100">100 รายการ</option>
                        <option value="all">ทั้งหมด</option>
                    </select>
                </div>

                {{-- Time Range --}}
                <div class="mb-4">
                    <label class="text-gray-400 text-sm block mb-2">ช่วงเวลา</label>
                    <select x-model="filters.timeRange"
                            class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="live">Live (ตอนนี้)</option>
                        <option value="1h">1 ชั่วโมงที่ผ่านมา</option>
                        <option value="3h">3 ชั่วโมงที่ผ่านมา</option>
                        <option value="today">วันนี้</option>
                        <option value="yesterday">เมื่อวาน</option>
                        <option value="week">7 วันที่ผ่านมา</option>
                    </select>
                </div>

                {{-- Auto Refresh --}}
                <div class="mb-4">
                    <label class="text-gray-400 text-sm block mb-2">รีเฟรชอัตโนมัติ</label>
                    <select x-model="filters.refreshInterval"
                            @change="setupAutoRefresh()"
                            class="w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-purple-500 focus:border-purple-500">
                        <option value="0">ปิด</option>
                        <option value="5">ทุก 5 วินาที</option>
                        <option value="10">ทุก 10 วินาที</option>
                        <option value="30">ทุก 30 วินาที</option>
                        <option value="60">ทุก 1 นาที</option>
                    </select>
                </div>

                {{-- Apply Button --}}
                <button @click="applyFilters()"
                        class="w-full py-3 bg-gradient-to-r from-purple-600 to-blue-600 hover:from-purple-500 hover:to-blue-500 text-white rounded-xl font-semibold transition">
                    <i class="fas fa-check mr-2"></i>
                    ใช้ตัวกรอง
                </button>
            </div>

            {{-- Active Bookings List --}}
            <div class="glass-panel rounded-2xl p-4">
                <h3 class="text-white font-semibold mb-3 flex items-center gap-2">
                    <i class="fas fa-list text-green-400"></i>
                    การจองที่กำลังดำเนินการ
                </h3>
                <div class="space-y-2 max-h-64 overflow-y-auto custom-scrollbar">
                    <template x-for="booking in activeBookings" :key="booking.id">
                        <div @click="focusOnBooking(booking)"
                             class="p-3 bg-white/5 hover:bg-white/10 rounded-xl cursor-pointer transition border border-transparent hover:border-purple-500/30">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-white font-medium text-sm" x-text="'#' + booking.booking_number"></span>
                                <span class="px-2 py-0.5 text-xs rounded-full"
                                      :class="getStatusBadgeClass(booking.status)"
                                      x-text="booking.status_label"></span>
                            </div>
                            <div class="text-gray-400 text-xs">
                                <p x-text="booking.service_name"></p>
                                <p class="flex items-center gap-1 mt-1">
                                    <i class="fas fa-user text-blue-400"></i>
                                    <span x-text="booking.user_name"></span>
                                </p>
                                <p class="flex items-center gap-1">
                                    <i class="fas fa-motorcycle text-green-400"></i>
                                    <span x-text="booking.provider_name || 'รอผู้ให้บริการ'"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                    <div x-show="activeBookings.length === 0" class="text-center text-gray-500 py-4">
                        <i class="fas fa-inbox text-2xl mb-2"></i>
                        <p class="text-sm">ไม่มีการจองที่กำลังดำเนินการ</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Map Area --}}
        <div class="flex-1 relative gps-map-section">
            {{-- Map --}}
            <div id="gps-monitor-map" class="w-full rounded-l-3xl overflow-hidden border-l border-white/10"></div>

            {{-- Fullscreen Exit Button (แสดงเฉพาะตอน fullscreen) --}}
            <button x-show="isFullscreen"
                    x-cloak
                    @click="toggleFullscreen()"
                    class="fullscreen-exit-btn flex items-center gap-2"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 3v3a2 2 0 0 1-2 2H3"/>
                    <path d="M21 8h-3a2 2 0 0 1-2-2V3"/>
                    <path d="M3 16h3a2 2 0 0 1 2 2v3"/>
                    <path d="M16 21v-3a2 2 0 0 1 2-2h3"/>
                </svg>
                <span>ออกจากเต็มจอ (ESC)</span>
            </button>

            {{-- Fullscreen Toggle Sidebar Button (แสดงเฉพาะตอน fullscreen) --}}
            <button x-show="isFullscreen"
                    x-cloak
                    @click="sidebarHidden = !sidebarHidden"
                    class="fullscreen-toggle-sidebar"
                    x-transition>
                <i class="fas" :class="sidebarHidden ? 'fa-chevron-right' : 'fa-chevron-left'"></i>
            </button>

            {{-- Floating Radar Overlay --}}
            <div class="absolute top-4 right-4 w-32 h-32 pointer-events-none">
                <div class="relative w-full h-full">
                    {{-- Radar Background --}}
                    <div class="absolute inset-0 rounded-full bg-green-900/30 border border-green-500/30"></div>
                    <div class="absolute inset-2 rounded-full bg-green-900/20 border border-green-500/20"></div>
                    <div class="absolute inset-4 rounded-full bg-green-900/10 border border-green-500/10"></div>

                    {{-- Radar Sweep --}}
                    <div class="absolute inset-0 rounded-full overflow-hidden">
                        <div class="radar-sweep absolute inset-0 origin-center"
                             style="background: conic-gradient(from 0deg, transparent 0deg, rgba(16, 185, 129, 0.3) 30deg, transparent 60deg);"></div>
                    </div>

                    {{-- Center Dot --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    </div>

                    {{-- Blips (Active Markers) --}}
                    <template x-for="(blip, index) in radarBlips.slice(0, 8)" :key="index">
                        <div class="absolute w-1.5 h-1.5 rounded-full animate-pulse"
                             :class="blip.type === 'provider' ? 'bg-green-400' : 'bg-blue-400'"
                             :style="{ top: blip.y + '%', left: blip.x + '%' }"></div>
                    </template>
                </div>
            </div>

            {{-- Map Controls --}}
            <div class="absolute bottom-4 right-4 flex flex-col gap-2">
                {{-- Fullscreen Button --}}
                <button @click="toggleFullscreen()"
                        class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                        :class="{ 'ring-2 ring-purple-500 bg-purple-600/30': isFullscreen }"
                        :title="isFullscreen ? 'ออกจากเต็มจอ (ESC)' : 'เต็มจอ'">
                    {{-- Enter Fullscreen Icon --}}
                    <svg x-show="!isFullscreen" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 3H5a2 2 0 0 0-2 2v3"/>
                        <path d="M21 8V5a2 2 0 0 0-2-2h-3"/>
                        <path d="M3 16v3a2 2 0 0 0 2 2h3"/>
                        <path d="M16 21h3a2 2 0 0 0 2-2v-3"/>
                    </svg>
                    {{-- Exit Fullscreen Icon --}}
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
                <button @click="toggleHeatmap()"
                        class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                        :class="{ 'ring-2 ring-purple-500': showHeatmap }"
                        title="Heatmap">
                    <i class="fas fa-fire"></i>
                </button>
                <button @click="toggleClustering()"
                        class="p-3 bg-gray-900/80 hover:bg-gray-800 text-white rounded-xl backdrop-blur-lg transition"
                        :class="{ 'ring-2 ring-blue-500': useClustering }"
                        title="Clustering">
                    <i class="fas fa-object-group"></i>
                </button>
            </div>

            {{-- Selected Booking Info Panel --}}
            <div x-show="selectedBooking"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-4"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-4"
                 class="absolute top-4 left-4 w-80 glass-panel rounded-2xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-white font-bold">รายละเอียดการจอง</h4>
                    <button @click="selectedBooking = null" class="text-gray-400 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <template x-if="selectedBooking">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">หมายเลข</span>
                            <span class="text-white font-mono" x-text="'#' + selectedBooking.booking_number"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">สถานะ</span>
                            <span class="px-2 py-1 text-xs rounded-full"
                                  :class="getStatusBadgeClass(selectedBooking.status)"
                                  x-text="selectedBooking.status_label"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">บริการ</span>
                            <span class="text-white" x-text="selectedBooking.service_name"></span>
                        </div>
                        <div class="border-t border-white/10 pt-3">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-full bg-blue-500/20 flex items-center justify-center">
                                    <i class="fas fa-user text-blue-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm" x-text="selectedBooking.user_name"></p>
                                    <p class="text-gray-400 text-xs">ผู้ใช้บริการ</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center">
                                    <i class="fas fa-motorcycle text-green-400 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-white text-sm" x-text="selectedBooking.provider_name || 'รอผู้ให้บริการ'"></p>
                                    <p class="text-gray-400 text-xs">ผู้ให้บริการ</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-2">
                            <a :href="'/admin/service-bookings/' + selectedBooking.id"
                               class="flex-1 py-2 bg-purple-600 hover:bg-purple-500 text-white text-center rounded-lg transition text-sm">
                                <i class="fas fa-eye mr-1"></i> ดูรายละเอียด
                            </a>
                            <button @click="focusOnBooking(selectedBooking)"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition text-sm">
                                <i class="fas fa-crosshairs"></i>
                            </button>
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
function gpsMonitoringCenter() {
    return {
        // State
        loading: false,
        lastRefresh: '-',
        map: null,
        heatmap: null,
        showHeatmap: false,
        useClustering: true,
        markerClusterer: null,
        isFullscreen: false,
        sidebarHidden: false,
        wrapper: null,

        // Data
        providerMarkers: [],
        userMarkers: [],
        serviceMarkers: [],
        routePolylines: [],
        activeBookings: [],
        selectedBooking: null,
        radarBlips: [],

        // Stats
        stats: {
            activeProviders: 0,
            activeUsers: 0,
            activeBookings: 0,
            totalUpdates: 0,
        },

        // Filters
        filters: {
            showProviders: true,
            showUsers: true,
            showServiceLocations: true,
            showRoutes: false,
            statuses: ['provider_accepted', 'provider_on_way', 'in_progress'],
            limit: '50',
            timeRange: 'live',
            refreshInterval: '10',
        },

        // Booking Statuses
        bookingStatuses: [
            { value: 'pending', label: 'รอยืนยัน', color: 'yellow' },
            { value: 'confirmed', label: 'ยืนยันแล้ว', color: 'blue' },
            { value: 'provider_accepted', label: 'ผู้ให้บริการรับงาน', color: 'purple' },
            { value: 'provider_on_way', label: 'กำลังเดินทาง', color: 'indigo' },
            { value: 'in_progress', label: 'กำลังดำเนินการ', color: 'green' },
            { value: 'completed', label: 'เสร็จสิ้น', color: 'gray' },
        ],

        // Refresh interval ID
        refreshIntervalId: null,

        init() {
            // เก็บ reference ของ wrapper element
            this.wrapper = this.$el;

            // โหลด Google Maps และตั้งค่าการ refresh อัตโนมัติ
            this.loadGoogleMaps();
            this.setupAutoRefresh();

            // ตั้งค่า event listeners สำหรับ fullscreen
            this.setupFullscreenListeners();
        },

        /**
         * ตั้งค่า event listeners สำหรับการเปลี่ยนแปลง fullscreen
         */
        setupFullscreenListeners() {
            // รองรับ vendor prefixes
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('webkitfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('mozfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('MSFullscreenChange', () => this.handleFullscreenChange());

            // รองรับการกด ESC เพื่อออกจาก fullscreen (สำหรับ CSS fallback)
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isFullscreen) {
                    this.exitFullscreen();
                }
            });
        },

        /**
         * Toggle fullscreen mode
         */
        toggleFullscreen() {
            if (this.isFullscreen) {
                this.exitFullscreen();
            } else {
                this.enterFullscreen();
            }
        },

        /**
         * เข้าสู่โหมด fullscreen
         */
        enterFullscreen() {
            const element = this.wrapper;

            // ฟังก์ชันสำหรับ CSS fallback
            const useCSSFullscreen = () => {
                this.isFullscreen = true;
                this.sidebarHidden = true; // ซ่อน sidebar โดย default
                this.resizeMapAfterFullscreen();
            };

            // ลองใช้ Browser Fullscreen API
            if (element.requestFullscreen) {
                element.requestFullscreen().catch(() => useCSSFullscreen());
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen();
                useCSSFullscreen();
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            } else {
                // Fallback: ใช้ CSS fullscreen
                useCSSFullscreen();
            }
        },

        /**
         * ออกจากโหมด fullscreen
         */
        exitFullscreen() {
            // ตรวจสอบว่าอยู่ใน Browser fullscreen หรือไม่
            const fullscreenElement = document.fullscreenElement ||
                                      document.webkitFullscreenElement ||
                                      document.mozFullScreenElement ||
                                      document.msFullscreenElement;

            if (fullscreenElement) {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            } else {
                // CSS fallback - ออกจาก fullscreen โดยตรง
                this.isFullscreen = false;
                this.sidebarHidden = false;
                this.resizeMapAfterFullscreen();
            }
        },

        /**
         * จัดการเมื่อ fullscreen state เปลี่ยนแปลง
         */
        handleFullscreenChange() {
            const fullscreenElement = document.fullscreenElement ||
                                      document.webkitFullscreenElement ||
                                      document.mozFullScreenElement ||
                                      document.msFullscreenElement;

            const wasFullscreen = this.isFullscreen;
            this.isFullscreen = (fullscreenElement === this.wrapper);

            // ถ้าเพิ่งเข้า fullscreen - ซ่อน sidebar โดย default
            if (this.isFullscreen && !wasFullscreen) {
                this.sidebarHidden = true;
            }

            // ถ้าออกจาก fullscreen - แสดง sidebar
            if (!this.isFullscreen && wasFullscreen) {
                this.sidebarHidden = false;
            }

            // ปรับขนาด map หลังจาก fullscreen เปลี่ยนแปลง
            this.resizeMapAfterFullscreen();
        },

        /**
         * ปรับขนาด map หลัง fullscreen เปลี่ยนแปลง
         */
        resizeMapAfterFullscreen() {
            // รอให้ DOM อัปเดตก่อน
            setTimeout(() => {
                if (this.map) {
                    google.maps.event.trigger(this.map, 'resize');

                    // ถ้ามี markers - fit bounds
                    const allMarkers = [...this.providerMarkers, ...this.userMarkers, ...this.serviceMarkers];
                    if (allMarkers.length > 0) {
                        // เก็บ center และ zoom เดิม
                        const currentCenter = this.map.getCenter();
                        const currentZoom = this.map.getZoom();

                        // รีเซ็ตกลับไปที่เดิม
                        setTimeout(() => {
                            this.map.setCenter(currentCenter);
                            this.map.setZoom(currentZoom);
                        }, 100);
                    }
                }
            }, 150);
        },

        loadGoogleMaps() {
            if (typeof google !== 'undefined') {
                this.initMap();
            } else {
                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=visualization&callback=initGpsMonitorMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
                window.initGpsMonitorMap = () => this.initMap();
            }
        },

        initMap() {
            // Dark mode map style
            const darkStyle = [
                { elementType: "geometry", stylers: [{ color: "#1a1a2e" }] },
                { elementType: "labels.text.fill", stylers: [{ color: "#8b5cf6" }] },
                { elementType: "labels.text.stroke", stylers: [{ color: "#1a1a2e" }] },
                { featureType: "administrative", elementType: "geometry", stylers: [{ color: "#4a5568" }] },
                { featureType: "poi", elementType: "geometry", stylers: [{ color: "#2d3748" }] },
                { featureType: "poi", elementType: "labels.text.fill", stylers: [{ color: "#6b7280" }] },
                { featureType: "road", elementType: "geometry", stylers: [{ color: "#374151" }] },
                { featureType: "road", elementType: "geometry.stroke", stylers: [{ color: "#1a1a2e" }] },
                { featureType: "road.highway", elementType: "geometry", stylers: [{ color: "#4a5568" }] },
                { featureType: "transit", elementType: "geometry", stylers: [{ color: "#2d3748" }] },
                { featureType: "water", elementType: "geometry", stylers: [{ color: "#0f172a" }] },
            ];

            this.map = new google.maps.Map(document.getElementById('gps-monitor-map'), {
                center: { lat: 13.7563, lng: 100.5018 }, // Bangkok
                zoom: 11,
                styles: darkStyle,
                disableDefaultUI: true,
                zoomControl: true,
                gestureHandling: 'greedy',
            });

            // Load initial data
            this.refreshData();
        },

        async refreshData() {
            if (this.loading) return;
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    show_providers: this.filters.showProviders ? '1' : '0',
                    show_users: this.filters.showUsers ? '1' : '0',
                    statuses: this.filters.statuses.join(','),
                    limit: this.filters.limit,
                    time_range: this.filters.timeRange,
                });

                const response = await fetch(`/admin/gps-monitoring/data?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.updateMarkers(data.data);
                    this.activeBookings = data.data.bookings || [];
                    this.stats = data.data.stats || this.stats;
                    this.updateRadarBlips();
                }

                this.lastRefresh = new Date().toLocaleTimeString('th-TH');
            } catch (error) {
                console.error('Failed to refresh GPS data:', error);
            } finally {
                this.loading = false;
            }
        },

        updateMarkers(data) {
            // Clear existing markers
            this.clearMarkers();

            // Add provider markers
            if (this.filters.showProviders && data.providers) {
                data.providers.forEach(provider => {
                    if (provider.latitude && provider.longitude) {
                        const marker = this.createProviderMarker(provider);
                        this.providerMarkers.push(marker);
                    }
                });
            }

            // Add user markers
            if (this.filters.showUsers && data.users) {
                data.users.forEach(user => {
                    if (user.latitude && user.longitude) {
                        const marker = this.createUserMarker(user);
                        this.userMarkers.push(marker);
                    }
                });
            }

            // Add service location markers
            if (this.filters.showServiceLocations && data.serviceLocations) {
                data.serviceLocations.forEach(location => {
                    if (location.latitude && location.longitude) {
                        const marker = this.createServiceMarker(location);
                        this.serviceMarkers.push(marker);
                    }
                });
            }

            // Add routes
            if (this.filters.showRoutes && data.routes) {
                data.routes.forEach(route => {
                    const polyline = this.createRoutePolyline(route);
                    this.routePolylines.push(polyline);
                });
            }

            // Update heatmap if active
            if (this.showHeatmap) {
                this.updateHeatmap(data);
            }
        },

        createProviderMarker(provider) {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(provider.latitude), lng: parseFloat(provider.longitude) },
                map: this.map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 40 40">
                            <circle cx="20" cy="20" r="18" fill="#10B981" stroke="#fff" stroke-width="3"/>
                            <circle cx="20" cy="20" r="8" fill="white"/>
                            <circle cx="20" cy="20" r="4" fill="#10B981"/>
                        </svg>
                    `),
                    anchor: new google.maps.Point(20, 20),
                    scaledSize: new google.maps.Size(40, 40),
                },
                title: provider.name,
            });

            marker.addListener('click', () => {
                this.showProviderInfo(provider);
            });

            return marker;
        },

        createUserMarker(user) {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(user.latitude), lng: parseFloat(user.longitude) },
                map: this.map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="16" fill="#3B82F6" stroke="#fff" stroke-width="2"/>
                            <circle cx="18" cy="18" r="6" fill="white"/>
                        </svg>
                    `),
                    anchor: new google.maps.Point(18, 18),
                    scaledSize: new google.maps.Size(36, 36),
                },
                title: user.name,
            });

            marker.addListener('click', () => {
                this.showUserInfo(user);
            });

            return marker;
        },

        createServiceMarker(location) {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(location.latitude), lng: parseFloat(location.longitude) },
                map: this.map,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="40" viewBox="0 0 30 40">
                            <path d="M15 0 C6.716 0 0 6.716 0 15 C0 26.25 15 40 15 40 S30 26.25 30 15 C30 6.716 23.284 0 15 0 Z" fill="#EF4444"/>
                            <circle cx="15" cy="15" r="7" fill="white"/>
                        </svg>
                    `),
                    anchor: new google.maps.Point(15, 40),
                    scaledSize: new google.maps.Size(30, 40),
                },
                title: location.address,
            });

            return marker;
        },

        createRoutePolyline(route) {
            const polyline = new google.maps.Polyline({
                path: route.path,
                geodesic: true,
                strokeColor: '#8B5CF6',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map: this.map,
            });

            return polyline;
        },

        clearMarkers() {
            this.providerMarkers.forEach(m => m.setMap(null));
            this.userMarkers.forEach(m => m.setMap(null));
            this.serviceMarkers.forEach(m => m.setMap(null));
            this.routePolylines.forEach(p => p.setMap(null));

            this.providerMarkers = [];
            this.userMarkers = [];
            this.serviceMarkers = [];
            this.routePolylines = [];
        },

        updateRadarBlips() {
            const blips = [];
            const allMarkers = [...this.providerMarkers, ...this.userMarkers];

            allMarkers.forEach((marker, index) => {
                // Random position in radar (simplified)
                blips.push({
                    x: 20 + Math.random() * 60,
                    y: 20 + Math.random() * 60,
                    type: index < this.providerMarkers.length ? 'provider' : 'user',
                });
            });

            this.radarBlips = blips;
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

        applyFilters() {
            this.refreshData();
        },

        focusOnBooking(booking) {
            this.selectedBooking = booking;

            if (booking.provider_latitude && booking.provider_longitude) {
                this.map.panTo({
                    lat: parseFloat(booking.provider_latitude),
                    lng: parseFloat(booking.provider_longitude)
                });
                this.map.setZoom(16);
            } else if (booking.service_latitude && booking.service_longitude) {
                this.map.panTo({
                    lat: parseFloat(booking.service_latitude),
                    lng: parseFloat(booking.service_longitude)
                });
                this.map.setZoom(16);
            }
        },

        showProviderInfo(provider) {
            // Find booking for this provider
            const booking = this.activeBookings.find(b => b.provider_id === provider.id);
            if (booking) {
                this.selectedBooking = booking;
            }
        },

        showUserInfo(user) {
            const booking = this.activeBookings.find(b => b.user_id === user.id);
            if (booking) {
                this.selectedBooking = booking;
            }
        },

        fitAllMarkers() {
            const bounds = new google.maps.LatLngBounds();
            let hasMarkers = false;

            [...this.providerMarkers, ...this.userMarkers, ...this.serviceMarkers].forEach(marker => {
                bounds.extend(marker.getPosition());
                hasMarkers = true;
            });

            if (hasMarkers) {
                this.map.fitBounds(bounds);
            }
        },

        centerOnBangkok() {
            this.map.panTo({ lat: 13.7563, lng: 100.5018 });
            this.map.setZoom(11);
        },

        toggleHeatmap() {
            this.showHeatmap = !this.showHeatmap;

            if (this.showHeatmap) {
                this.createHeatmap();
            } else if (this.heatmap) {
                this.heatmap.setMap(null);
                this.heatmap = null;
            }
        },

        createHeatmap() {
            const heatmapData = [
                ...this.providerMarkers.map(m => ({
                    location: m.getPosition(),
                    weight: 2
                })),
                ...this.userMarkers.map(m => ({
                    location: m.getPosition(),
                    weight: 1
                })),
            ];

            this.heatmap = new google.maps.visualization.HeatmapLayer({
                data: heatmapData,
                map: this.map,
                radius: 50,
                gradient: [
                    'rgba(0, 0, 0, 0)',
                    'rgba(139, 92, 246, 0.2)',
                    'rgba(139, 92, 246, 0.4)',
                    'rgba(59, 130, 246, 0.6)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(16, 185, 129, 1)',
                ],
            });
        },

        updateHeatmap(data) {
            if (this.heatmap) {
                this.heatmap.setMap(null);
                this.createHeatmap();
            }
        },

        toggleClustering() {
            this.useClustering = !this.useClustering;
            this.refreshData();
        },

        getStatusBadgeClass(status) {
            const classes = {
                pending: 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
                confirmed: 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
                provider_accepted: 'bg-purple-500/20 text-purple-400 border border-purple-500/30',
                provider_on_way: 'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30',
                in_progress: 'bg-green-500/20 text-green-400 border border-green-500/30',
                completed: 'bg-gray-500/20 text-gray-400 border border-gray-500/30',
                cancelled: 'bg-red-500/20 text-red-400 border border-red-500/30',
            };
            return classes[status] || classes.pending;
        },

        destroy() {
            // ออกจาก fullscreen ถ้ายังอยู่
            if (this.isFullscreen) {
                this.exitFullscreen();
            }

            // ยกเลิก auto refresh
            if (this.refreshIntervalId) {
                clearInterval(this.refreshIntervalId);
            }
        },
    }
}
</script>
@endpush
