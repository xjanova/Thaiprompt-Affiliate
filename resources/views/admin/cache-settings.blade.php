{{--
/**
 * Admin Cache Settings V3 - หน้าการตั้งค่าระบบแคช (V3 Design)
 *
 * @uses layouts/admin-v3.blade.php - Layout หลัก
 * @uses arrow-x/stats/card-3d.blade.php - 3D Stat Cards
 * @uses arrow-x/charts/line.blade.php - Performance Charts
 * @tip UI ที่สวยงาม ใช้งานง่าย พร้อมกราฟและปุ่ม V3 Design
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบแคช')
@section('page-title', 'ระบบแคช')

@section('content')
<div class="space-y-6" x-data="cacheManager()">
    {{-- Header with Quick Actions --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white drop-shadow-lg mb-2">
                    <i class="fas fa-rocket mr-3"></i>ระบบจัดการแคช
                </h1>
                <p class="text-white/80 drop-shadow">เลือกและจัดการ Cache Driver สำหรับเพิ่มประสิทธิภาพระบบ</p>
            </div>

            {{-- Quick Action Buttons --}}
            <div class="flex flex-wrap gap-3">
                <button @click="clearCache('all')"
                    class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600 hover:from-red-600 hover:to-pink-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 active:scale-95"
                    :disabled="clearing">
                    <i class="fas fa-trash-alt mr-2"></i>ล้างแคชทั้งหมด
                </button>

                <button @click="optimizeCache()"
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 active:scale-95"
                    :disabled="optimizing">
                    <i class="fas fa-rocket mr-2"></i>ปรับแต่งแคช
                </button>

                <button @click="refreshStatus()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 hover:from-blue-600 hover:to-purple-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-2xl transition-all transform hover:scale-105 active:scale-95"
                    :disabled="refreshing">
                    <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': refreshing }"></i>รีเฟรช
                </button>
            </div>
        </div>
    </div>

    {{-- Current Cache Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6">
        {{-- Current Driver --}}
        <x-arrow-x.stats.card-3d
            :value="strtoupper($currentDriver)"
            label="Driver ที่ใช้งาน"
            icon="fas fa-server"
            gradient="from-blue-500 to-cyan-600"
        />

        {{-- Cache Size --}}
        <x-arrow-x.stats.card-3d
            :value="$currentStats['size'] ?? 'N/A'"
            label="ขนาด Cache"
            icon="fas fa-database"
            gradient="from-purple-500 to-pink-600"
        />

        {{-- Cache Hits (if available) --}}
        <x-arrow-x.stats.card-3d
            :value="isset($currentStats['hits']) ? number_format($currentStats['hits']) : 'N/A'"
            label="Cache Hits"
            icon="fas fa-check-circle"
            gradient="from-green-500 to-emerald-600"
        />

        {{-- Memory Usage or Entries --}}
        <x-arrow-x.stats.card-3d
            :value="$currentStats['memory'] ?? $currentStats['entries_count'] ?? 'N/A'"
            label="{{ isset($currentStats['memory']) ? 'หน่วยความจำ' : 'จำนวน Entries' }}"
            icon="fas fa-memory"
            gradient="from-orange-500 to-red-600"
        />
    </div>

    {{-- Performance Chart & Quick Actions --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        {{-- Chart (2/3 width) --}}
        <div class="lg:col-span-2">
            <x-arrow-x.charts.line
                id="cache-performance-chart"
                title="ประสิทธิภาพแคช (24 ชั่วโมงล่าสุด)"
                icon="fas fa-chart-area"
                gradient="from-blue-500 to-purple-600"
                :height="350"
            />
        </div>

        {{-- Quick Cache Actions (1/3 width) --}}
        <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
            <div class="px-6 py-4 border-b border-white/30 bg-gradient-to-r from-purple-500/20 to-pink-600/20">
                <h3 class="text-xl font-bold text-white drop-shadow flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-lg flex items-center justify-center shadow-lg">
                        <i class="fas fa-bolt text-white drop-shadow"></i>
                    </div>
                    จัดการแคช
                </h3>
            </div>

            <div class="p-6 space-y-3">
                {{-- Clear Config Cache --}}
                <button @click="clearCache('config')"
                    class="w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition-all transform hover:scale-105 border border-white/20 text-left flex items-center gap-3"
                    :disabled="clearing">
                    <i class="fas fa-cog w-8 text-blue-400 text-xl"></i>
                    <span>ล้าง Config Cache</span>
                </button>

                {{-- Clear Route Cache --}}
                <button @click="clearCache('route')"
                    class="w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition-all transform hover:scale-105 border border-white/20 text-left flex items-center gap-3"
                    :disabled="clearing">
                    <i class="fas fa-route w-8 text-green-400 text-xl"></i>
                    <span>ล้าง Route Cache</span>
                </button>

                {{-- Clear View Cache --}}
                <button @click="clearCache('view')"
                    class="w-full px-4 py-3 bg-white/10 hover:bg-white/20 text-white font-medium rounded-xl transition-all transform hover:scale-105 border border-white/20 text-left flex items-center gap-3"
                    :disabled="clearing">
                    <i class="fas fa-eye w-8 text-purple-400 text-xl"></i>
                    <span>ล้าง View Cache</span>
                </button>

                {{-- Cache Stats --}}
                <div class="mt-6 p-4 bg-white/5 rounded-xl border border-white/10">
                    <p class="text-xs text-white/60 mb-3 font-semibold">สถิติแคช</p>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-white/80">Hit Rate:</span>
                            <span class="text-sm font-bold text-green-400" x-text="hitRate + '%'">0%</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-white/80">Memory:</span>
                            <span class="text-sm font-bold text-blue-400" x-text="memoryUsage">N/A</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cache Drivers Selection --}}
    <div class="glass-fusion rounded-2xl overflow-hidden border border-white/30 shadow-2xl">
        <div class="px-6 py-4 border-b border-white/30 bg-gradient-to-r from-blue-500/20 to-purple-600/20">
            <h3 class="text-xl font-bold text-white drop-shadow flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center shadow-lg">
                    <i class="fas fa-server text-white drop-shadow"></i>
                </div>
                เลือก Cache Driver
            </h3>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                @foreach(['file' => ['name' => 'File Cache', 'icon' => 'fa-file', 'gradient' => 'from-blue-500 to-cyan-600', 'desc' => 'เก็บแคชในไฟล์ - เหมาะสำหรับเริ่มต้น'],
                          'database' => ['name' => 'Database Cache', 'icon' => 'fa-database', 'gradient' => 'from-green-500 to-emerald-600', 'desc' => 'เก็บแคชใน Database - ใช้งานง่าย'],
                          'redis' => ['name' => 'Redis Cache', 'icon' => 'fa-bolt', 'gradient' => 'from-red-500 to-orange-600', 'desc' => 'เร็วมาก - แนะนำสำหรับ Production'],
                          'memcached' => ['name' => 'Memcached', 'icon' => 'fa-memory', 'gradient' => 'from-purple-500 to-pink-600', 'desc' => 'เร็วและมีประสิทธิภาพสูง']] as $driverKey => $driver)
                    <div class="glass-fusion rounded-xl p-6 border border-white/30 shadow-xl hover:shadow-2xl transition-all transform hover:scale-105">
                        {{-- Driver Icon & Status --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="w-14 h-14 bg-gradient-to-br {{ $driver['gradient'] }} rounded-xl flex items-center justify-center shadow-lg">
                                <i class="fas {{ $driver['icon'] }} text-2xl text-white drop-shadow"></i>
                            </div>

                            {{-- Status Badge --}}
                            @php
                                $status = $driversStatus[$driverKey] ?? ['status' => false];
                                $isActive = $currentDriver === $driverKey;
                            @endphp

                            <div class="flex flex-col items-end gap-2">
                                @if($isActive)
                                    <span class="px-3 py-1 bg-gradient-to-r from-green-400 to-emerald-500 text-white text-xs font-bold rounded-full shadow-lg animate-pulse">
                                        <i class="fas fa-check-circle mr-1"></i>กำลังใช้งาน
                                    </span>
                                @endif

                                <span class="px-3 py-1 {{ $status['status'] ? 'bg-green-500' : 'bg-red-500' }} text-white text-xs font-bold rounded-full shadow-lg">
                                    <i class="fas fa-circle mr-1 {{ $status['status'] ? 'animate-pulse' : '' }}"></i>
                                    {{ $status['status'] ? 'พร้อมใช้งาน' : 'ไม่พร้อม' }}
                                </span>
                            </div>
                        </div>

                        {{-- Driver Name --}}
                        <h4 class="text-lg font-bold text-white mb-2 drop-shadow">{{ $driver['name'] }}</h4>

                        {{-- Description --}}
                        <p class="text-sm text-white/80 mb-4 drop-shadow">{{ $driver['desc'] }}</p>

                        {{-- Status Message --}}
                        @if(!$status['status'])
                            <div class="mb-4 p-3 bg-red-500/20 border border-red-500/50 rounded-lg">
                                <p class="text-xs text-red-200">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    {{ $status['message'] ?? 'ไม่สามารถใช้งานได้' }}
                                </p>
                            </div>
                        @endif

                        {{-- Action Buttons --}}
                        <div class="flex gap-2">
                            {{-- Test Connection --}}
                            <button @click="testConnection('{{ $driverKey }}')"
                                class="flex-1 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-sm font-medium rounded-lg transition-all border border-white/20"
                                :disabled="testing['{{ $driverKey }}']">
                                <i class="fas fa-vial mr-1" :class="{ 'fa-spin': testing['{{ $driverKey }}'] }"></i>
                                <span x-text="testing['{{ $driverKey }}'] ? 'ทดสอบ...' : 'ทดสอบ'">ทดสอบ</span>
                            </button>

                            {{-- Select Driver --}}
                            @if($status['status'])
                                <button @click="changeDriver('{{ $driverKey }}')"
                                    :disabled="currentDriver === '{{ $driverKey }}' || switching"
                                    class="flex-1 px-4 py-2 bg-gradient-to-r {{ $driver['gradient'] }} hover:opacity-90 text-white text-sm font-bold rounded-lg shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="fas {{ $isActive ? 'fa-check' : 'fa-hand-pointer' }} mr-1"></i>
                                    {{ $isActive ? 'กำลังใช้' : 'เลือก' }}
                                </button>
                            @else
                                <button @click="showInstallGuide('{{ $driverKey }}')"
                                    class="flex-1 px-4 py-2 bg-gradient-to-r from-yellow-500 to-orange-600 hover:opacity-90 text-white text-sm font-bold rounded-lg shadow-lg transition-all">
                                    <i class="fas fa-book mr-1"></i>คู่มือติดตั้ง
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recommendations --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                <i class="fas fa-lightbulb text-white text-xl drop-shadow"></i>
            </div>

            <div>
                <h3 class="text-xl font-bold text-white mb-3 drop-shadow">💡 คำแนะนำ</h3>
                <div class="space-y-2">
                    <p class="text-white/90 drop-shadow">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <strong>สำหรับเว็บขนาดเล็ก:</strong> ใช้ File Cache หรือ Database Cache
                    </p>
                    <p class="text-white/90 drop-shadow">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <strong>สำหรับ Production:</strong> ใช้ Redis Cache (แนะนำ) หรือ Memcached
                    </p>
                    <p class="text-white/90 drop-shadow">
                        <i class="fas fa-check text-green-400 mr-2"></i>
                        <strong>ประสิทธิภาพสูงสุด:</strong> Redis Cache พร้อม persistent storage
                    </p>
                    <p class="text-white/90 drop-shadow">
                        <i class="fas fa-info-circle text-blue-400 mr-2"></i>
                        ควรล้างแคชทุกครั้งหลังจากเปลี่ยน Cache Driver
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Installation Guide Modal --}}
    <div x-show="showGuide"
         x-cloak
         @click.self="showGuide = false"
         class="fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition>
        <div class="glass-fusion max-w-4xl w-full max-h-[90vh] overflow-y-auto rounded-2xl border border-white/30 shadow-2xl" @click.stop>
            <div class="p-6">
                {{-- Header --}}
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                            <i class="fas fa-book text-purple-400"></i>
                            คู่มือติดตั้ง <span class="text-purple-400" x-text="guideData.title"></span>
                        </h3>
                        <p class="text-white/70 text-sm mt-1">ทำตามขั้นตอนเหล่านี้เพื่อเริ่มใช้งาน</p>
                    </div>
                    <button @click="showGuide = false" class="text-white/70 hover:text-white transition">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                {{-- Requirements --}}
                <div class="mb-6 card-glass p-4 rounded-xl">
                    <h4 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-list-check text-green-400"></i>
                        สิ่งที่ต้องมี
                    </h4>
                    <ul class="space-y-2">
                        <template x-for="(req, index) in guideData.requirements" :key="index">
                            <li class="text-white/80 flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span x-text="req"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Installation Steps --}}
                <div class="mb-6 card-glass p-4 rounded-xl">
                    <h4 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-terminal text-blue-400"></i>
                        ขั้นตอนการติดตั้ง
                    </h4>
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <pre class="text-green-400" x-text="guideData.installation_steps.join('\n')"></pre>
                    </div>
                </div>

                {{-- .env Example --}}
                <div class="mb-6 card-glass p-4 rounded-xl">
                    <h4 class="text-lg font-semibold text-white mb-3 flex items-center gap-2">
                        <i class="fas fa-code text-yellow-400"></i>
                        ตัวอย่างการตั้งค่าใน .env
                    </h4>
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <pre class="text-blue-400" x-text="guideData.env_example"></pre>
                    </div>
                </div>

                <button @click="showGuide = false" class="btn-primary w-full">
                    <i class="fas fa-check mr-2"></i>
                    เข้าใจแล้ว
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
{{-- ApexCharts Library --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>

<script>
/**
 * Cache Manager Alpine Component (V3 Design)
 *
 * จัดการ cache settings พร้อมกราฟ performance
 */
function cacheManager() {
    return {
        drivers: @json($driversStatus),
        stats: @json($currentStats),
        currentDriver: '{{ $currentDriver }}',
        testing: {},
        switching: false,
        clearing: false,
        optimizing: false,
        refreshing: false,
        showGuide: false,
        guideData: {},
        hitRate: {{ round((($currentStats['hits'] ?? 0) / max(($currentStats['hits'] ?? 0) + ($currentStats['misses'] ?? 0), 1)) * 100, 2) }},
        memoryUsage: '{{ $currentStats['memory'] ?? 'N/A' }}',

        /**
         * เริ่มต้น component
         */
        init() {
            this.initPerformanceChart();
            this.startAutoRefresh();
        },

        /**
         * เริ่มต้นกราฟ performance
         */
        initPerformanceChart() {
            const options = {
                series: [
                    {
                        name: 'Cache Hits',
                        data: [30, 40, 45, 50, 49, 60, 70, 91, 85, 95, 100, 110]
                    },
                    {
                        name: 'Cache Misses',
                        data: [10, 15, 12, 8, 10, 5, 8, 4, 6, 3, 5, 4]
                    }
                ],
                chart: {
                    type: 'area',
                    height: 350,
                    background: 'transparent',
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    }
                },
                colors: ['#10b981', '#ef4444'],
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.7,
                        opacityTo: 0.2,
                    }
                },
                xaxis: {
                    categories: ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'],
                    labels: {
                        style: {
                            colors: '#ffffff90'
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#ffffff90'
                        }
                    }
                },
                legend: {
                    labels: {
                        colors: '#ffffff'
                    }
                },
                grid: {
                    borderColor: '#ffffff20'
                },
                tooltip: {
                    theme: 'dark',
                    x: {
                        show: true
                    }
                }
            };

            const chart = new ApexCharts(document.querySelector('#cache-performance-chart'), options);
            chart.render();
        },

        async refreshStatus() {
            this.refreshing = true;
            try {
                const [statusRes, statsRes] = await Promise.all([
                    fetch('{{ route("admin.cache.status") }}'),
                    fetch('{{ route("admin.cache.stats") }}')
                ]);

                const statusData = await statusRes.json();
                const statsData = await statsRes.json();

                if (statusData.success) {
                    this.drivers = statusData.data;
                }
                if (statsData.success) {
                    this.stats = statsData.data;
                }

                this.showNotification('✅ รีเฟรชสถานะสำเร็จ', 'success');
            } catch (error) {
                this.showNotification('❌ เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.refreshing = false;
            }
        },

        async testConnection(driver) {
            this.testing[driver] = true;
            try {
                const response = await fetch('{{ route("admin.cache.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ driver })
                });

                const data = await response.json();

                if (data.status) {
                    this.showNotification(data.message, 'success');
                    if (this.drivers[driver]) {
                        this.drivers[driver].status = true;
                        this.drivers[driver].message = data.message;
                    }
                } else {
                    this.showNotification(data.message, 'error');
                    if (this.drivers[driver]) {
                        this.drivers[driver].status = false;
                        this.drivers[driver].message = data.message;
                    }
                }
            } catch (error) {
                this.showNotification('❌ เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.testing[driver] = false;
            }
        },

        async changeDriver(driver) {
            const driverName = this.drivers[driver].name;

            if (!confirm(`ต้องการเปลี่ยน Cache Driver เป็น ${driverName} หรือไม่?\n\n⚠️ ระบบจะล้างแคชเดิมและอัพเดท .env อัตโนมัติ`)) {
                return;
            }

            this.switching = true;
            try {
                const response = await fetch('{{ route("admin.cache.change-driver") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ driver })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('✅ ' + data.message, 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showNotification('❌ ' + data.message, 'error');
                }
            } catch (error) {
                this.showNotification('❌ เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.switching = false;
            }
        },

        async clearCache(type) {
            const messages = {
                config: 'Config Cache',
                route: 'Route Cache',
                view: 'View Cache',
                all: 'Cache ทั้งหมด'
            };

            if (type === 'all' && !confirm(`⚠️ ต้องการล้าง ${messages[type]} หรือไม่?`)) {
                return;
            }

            this.clearing = true;
            try {
                const response = await fetch('{{ route("admin.cache.clear-specific") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ type })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('✅ ' + data.message, 'success');
                    await this.refreshStatus();
                } else {
                    this.showNotification('❌ ' + data.message, 'error');
                }
            } catch (error) {
                this.showNotification('❌ เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.clearing = false;
            }
        },

        async optimizeCache() {
            if (!confirm('🚀 ต้องการปรับแต่งแคชหรือไม่?\n\nระบบจะ cache config, routes และ views เพื่อเพิ่มความเร็ว')) {
                return;
            }

            this.optimizing = true;
            try {
                const response = await fetch('{{ route("admin.cache.optimize") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('✅ ' + data.message, 'success');
                } else {
                    this.showNotification('❌ ' + data.message, 'error');
                }
            } catch (error) {
                this.showNotification('❌ เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.optimizing = false;
            }
        },

        async showInstallGuide(driver) {
            try {
                const response = await fetch(`{{ route("admin.cache.guide") }}?driver=${driver}`);
                const data = await response.json();

                if (data.success) {
                    this.guideData = data.data;
                    this.showGuide = true;
                } else {
                    this.showNotification('❌ ไม่สามารถโหลดคู่มือได้', 'error');
                }
            } catch (error) {
                this.showNotification('❌ เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * Auto refresh สถิติทุก 30 วินาที
         */
        startAutoRefresh() {
            setInterval(async () => {
                try {
                    const response = await fetch('{{ route("admin.cache.stats") }}');
                    const result = await response.json();

                    if (result.success) {
                        const stats = result.data;
                        this.hitRate = Math.round((stats.hits / Math.max(stats.hits + stats.misses, 1)) * 100);
                        this.memoryUsage = stats.memory || 'N/A';
                    }
                } catch (error) {
                    // Silent fail for auto-refresh
                }
            }, 30000); // 30 seconds
        },

        showNotification(message, type = 'info') {
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                alert(message);
            }
        }
    };
}
</script>
@endpush

@push('styles')
<style>
/**
 * Glass Fusion Effect - V3 Design
 */
.glass-fusion {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.glass-neu {
    background: rgba(255, 255, 255, 0.1);
}

[x-cloak] {
    display: none !important;
}
</style>
@endpush
@endsection
