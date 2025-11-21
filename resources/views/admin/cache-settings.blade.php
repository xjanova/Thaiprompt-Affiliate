{{--
/**
 * Admin Cache Settings V3 - หน้าการตั้งค่าระบบแคช (ปรับปรุงใหม่)
 *
 * @uses layouts/admin-v3.blade.php - Layout หลัก
 * @tip UI ที่สวยงาม ใช้งานง่าย พร้อมแสดงสถานะแบบ Real-time
 */
--}}

@extends('layouts.admin-v3')

@section('title', 'ตั้งค่าระบบแคช')
@section('page-title', 'ระบบแคช')

@section('content')
<div class="space-y-6" x-data="cacheManager()">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white drop-shadow-lg flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center shadow-lg">
                    <i class="fas fa-rocket text-white text-xl"></i>
                </div>
                ระบบแคช
            </h1>
            <p class="text-white/70 text-sm mt-2">เพิ่มความเร็วเว็บไซต์ด้วยระบบ Cache ที่เหมาะสม</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button @click="refreshStatus" class="btn-secondary" :disabled="refreshing">
                <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': refreshing }"></i>
                <span x-text="refreshing ? 'กำลังรีเฟรช...' : 'รีเฟรช'"></span>
            </button>
            <button @click="optimizeCache" class="btn-success" :disabled="optimizing">
                <i class="fas fa-rocket mr-2"></i>
                <span x-text="optimizing ? 'กำลังปรับแต่ง...' : 'ปรับแต่งแคช'"></span>
            </button>
        </div>
    </div>

    {{-- Current Status Card --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                <i class="fas fa-database text-white text-2xl"></i>
            </div>
            <div class="flex-1">
                <h2 class="text-2xl font-bold text-white">Cache ปัจจุบัน</h2>
                <p class="text-white/70 text-sm">สถานะและข้อมูลการใช้งาน</p>
            </div>
            <div class="hidden sm:block px-6 py-3 rounded-xl font-bold text-lg"
                 :class="{
                     'bg-green-500/20 text-green-300': currentDriver !== 'file',
                     'bg-blue-500/20 text-blue-300': currentDriver === 'file'
                 }">
                <span x-text="stats.current_driver_name"></span>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            {{-- Driver Name (Mobile) --}}
            <div class="sm:hidden col-span-2 card-glass p-4 text-center">
                <p class="text-white/70 text-xs mb-1">Driver ที่ใช้</p>
                <p class="text-white font-bold text-lg" x-text="stats.current_driver_name"></p>
            </div>

            {{-- Stats --}}
            <template x-if="stats.cache_size">
                <div class="card-glass p-4 text-center">
                    <i class="fas fa-save text-green-400 text-2xl mb-2"></i>
                    <p class="text-white/70 text-xs mb-1">ขนาดแคช</p>
                    <p class="text-white font-bold" x-text="stats.cache_size"></p>
                </div>
            </template>

            <template x-if="stats.used_memory">
                <div class="card-glass p-4 text-center">
                    <i class="fas fa-memory text-orange-400 text-2xl mb-2"></i>
                    <p class="text-white/70 text-xs mb-1">RAM ที่ใช้</p>
                    <p class="text-white font-bold" x-text="stats.used_memory"></p>
                </div>
            </template>

            <template x-if="stats.entries_count">
                <div class="card-glass p-4 text-center">
                    <i class="fas fa-list text-purple-400 text-2xl mb-2"></i>
                    <p class="text-white/70 text-xs mb-1">จำนวน Entries</p>
                    <p class="text-white font-bold" x-text="stats.entries_count"></p>
                </div>
            </template>

            <template x-if="stats.uptime_days">
                <div class="card-glass p-4 text-center">
                    <i class="fas fa-clock text-blue-400 text-2xl mb-2"></i>
                    <p class="text-white/70 text-xs mb-1">Uptime</p>
                    <p class="text-white font-bold" x-text="stats.uptime_days + ' วัน'"></p>
                </div>
            </template>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <h3 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-bolt text-yellow-400"></i>
            เครื่องมือจัดการแคช
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <button @click="clearCache('config')" class="btn-secondary" :disabled="clearing">
                <i class="fas fa-cog mr-2"></i>
                Config
            </button>
            <button @click="clearCache('route')" class="btn-secondary" :disabled="clearing">
                <i class="fas fa-route mr-2"></i>
                Route
            </button>
            <button @click="clearCache('view')" class="btn-secondary" :disabled="clearing">
                <i class="fas fa-eye mr-2"></i>
                View
            </button>
            <button @click="clearCache('all')" class="btn-danger" :disabled="clearing">
                <i class="fas fa-trash-alt mr-2"></i>
                ล้างทั้งหมด
            </button>
        </div>
    </div>

    {{-- Cache Drivers Selection --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white flex items-center gap-2">
                    <i class="fas fa-server text-purple-400"></i>
                    เลือก Cache Driver
                </h2>
                <p class="text-white/70 text-sm mt-1">เลือก driver ที่เหมาะสมกับเว็บไซต์ของคุณ</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="(driver, key) in drivers" :key="key">
                <div class="card-glass p-6 relative overflow-hidden transition-all hover:scale-[1.02]"
                     :class="{
                         'ring-2 ring-blue-500 shadow-xl shadow-blue-500/20': driver.is_current,
                         'opacity-60': !driver.status
                     }">

                    {{-- Current Badge --}}
                    <div x-show="driver.is_current"
                         class="absolute top-3 right-3 px-3 py-1 bg-gradient-to-r from-blue-500 to-purple-600 text-white text-xs font-bold rounded-full shadow-lg animate-pulse">
                        <i class="fas fa-check mr-1"></i>
                        กำลังใช้งาน
                    </div>

                    {{-- Driver Header --}}
                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-14 h-14 rounded-xl flex items-center justify-center text-3xl shadow-lg"
                             :class="{
                                 'bg-gradient-to-br from-blue-400 to-blue-600': key === 'file',
                                 'bg-gradient-to-br from-purple-400 to-purple-600': key === 'database',
                                 'bg-gradient-to-br from-red-400 to-red-600': key === 'redis',
                                 'bg-gradient-to-br from-green-400 to-green-600': key === 'memcached'
                             }">
                            <i class="text-white" :class="{
                                'fas fa-file': key === 'file',
                                'fas fa-database': key === 'database',
                                'fas fa-bolt': key === 'redis',
                                'fas fa-server': key === 'memcached'
                            }"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-1" x-text="driver.name"></h3>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold"
                                     :class="{
                                         'bg-green-500/20 text-green-300': driver.status,
                                         'bg-red-500/20 text-red-300': !driver.status
                                     }">
                                    <div class="w-2 h-2 rounded-full"
                                         :class="{
                                             'bg-green-400': driver.status,
                                             'bg-red-400': !driver.status
                                         }"></div>
                                    <span x-text="driver.status ? 'พร้อมใช้งาน' : 'ต้องติดตั้ง'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="text-white/80 text-sm mb-4" x-text="driver.description"></p>

                    {{-- Recommended Tag --}}
                    <div class="mb-4 p-3 rounded-lg bg-purple-500/10 border border-purple-500/30">
                        <p class="text-purple-200 text-sm flex items-center gap-2">
                            <i class="fas fa-star"></i>
                            <span x-text="driver.recommended"></span>
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2">
                        <template x-if="driver.status && !driver.is_current">
                            <button @click="changeDriver(key)"
                                    class="flex-1 btn-primary"
                                    :disabled="switching">
                                <i class="fas fa-check mr-2"></i>
                                เลือก Driver นี้
                            </button>
                        </template>
                        <button @click="testConnection(key)"
                                class="btn-secondary flex-1"
                                :disabled="testing[key]">
                            <i class="fas fa-plug mr-1" :class="{ 'fa-spin': testing[key] }"></i>
                            <span x-text="testing[key] ? 'ทดสอบ...' : 'ทดสอบ'"></span>
                        </button>
                        <button @click="showInstallGuide(key)"
                                class="btn-info">
                            <i class="fas fa-book"></i>
                        </button>
                    </div>
                </div>
            </template>
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
<script>
/**
 * Cache Manager Alpine Component (ปรับปรุงใหม่)
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
                const response = await fetch(`{{ route("admin.cache.installation-guide") }}?driver=${driver}`);
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
@endsection
