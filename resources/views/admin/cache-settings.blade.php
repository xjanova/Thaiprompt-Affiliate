{{--
/**
 * Admin Cache Settings V3 - หน้าการตั้งค่าระบบแคช
 *
 * @uses layouts/admin-v3.blade.php - Layout หลัก
 *
 * @data จาก Admin\CacheSettingsController:
 * - $driversStatus - สถานะของ cache drivers ทั้งหมด
 * - $currentStats - สถิติ cache ปัจจุบัน
 * - $currentDriver - cache driver ที่ใช้งานอยู่
 *
 * @tip View นี้ใช้ V3 Coding Guidelines 100%
 * @tip รองรับ dark mode อัตโนมัติ
 * @tip Responsive ทุก breakpoint
 * @tip ใช้ Alpine.js สำหรับ interactivity
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
            <h1 class="text-3xl font-bold text-white drop-shadow-lg">
                <i class="fas fa-hdd mr-2"></i>
                ตั้งค่าระบบแคช
            </h1>
            <p class="text-white/70 text-sm mt-1">จัดการและเลือก Cache Driver สำหรับเพิ่มประสิทธิภาพเว็บไซต์</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button @click="refreshStatus" class="btn-secondary" :disabled="refreshing">
                <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': refreshing }"></i>
                <span x-text="refreshing ? 'กำลังรีเฟรช...' : 'รีเฟรชสถานะ'"></span>
            </button>
            <button @click="clearCache('all')" class="btn-danger" :disabled="clearing">
                <i class="fas fa-trash-alt mr-2"></i>
                <span x-text="clearing ? 'กำลังล้าง...' : 'ล้างแคชทั้งหมด'"></span>
            </button>
            <button @click="optimizeCache" class="btn-success" :disabled="optimizing">
                <i class="fas fa-rocket mr-2"></i>
                <span x-text="optimizing ? 'กำลังปรับแต่ง...' : 'ปรับแต่งแคช'"></span>
            </button>
        </div>
    </div>

    {{-- Current Cache Stats --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-chart-line"></i>
            สถิติแคชปัจจุบัน
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Current Driver --}}
            <div class="card-glass p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center shadow-lg">
                        <i class="fas fa-database text-white text-xl"></i>
                    </div>
                    <div>
                        <p class="text-white/70 text-sm">Driver ปัจจุบัน</p>
                        <p class="text-white font-bold text-lg" x-text="stats.current_driver_name"></p>
                    </div>
                </div>
            </div>

            {{-- Additional Stats based on driver --}}
            <template x-if="stats.cache_size">
                <div class="card-glass p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-teal-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-save text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white/70 text-sm">ขนาดแคช</p>
                            <p class="text-white font-bold text-lg" x-text="stats.cache_size"></p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="stats.used_memory">
                <div class="card-glass p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-red-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-memory text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white/70 text-sm">RAM ที่ใช้</p>
                            <p class="text-white font-bold text-lg" x-text="stats.used_memory"></p>
                        </div>
                    </div>
                </div>
            </template>

            <template x-if="stats.entries_count">
                <div class="card-glass p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-pink-500 to-purple-600 flex items-center justify-center shadow-lg">
                            <i class="fas fa-list text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-white/70 text-sm">จำนวน Entries</p>
                            <p class="text-white font-bold text-lg" x-text="stats.entries_count"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Cache Drivers List --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-server"></i>
            เลือก Cache Driver
        </h2>
        <p class="text-white/70 mb-6">เลือก cache driver ที่เหมาะสมกับเว็บไซต์ของคุณ (สีเขียว = ใช้งานได้ | สีแดง = ใช้งานไม่ได้)</p>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <template x-for="(driver, key) in drivers" :key="key">
                <div class="card-glass p-6 relative overflow-hidden"
                     :class="{
                         'ring-2 ring-blue-500': driver.is_current,
                         'opacity-75': !driver.status
                     }">
                    {{-- Current Badge --}}
                    <div x-show="driver.is_current"
                         class="absolute top-4 right-4 px-3 py-1 bg-blue-500 text-white text-xs font-bold rounded-full">
                        กำลังใช้งาน
                    </div>

                    {{-- Status Badge --}}
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <i class="fas" :class="{
                                'fa-file': key === 'file',
                                'fa-database': key === 'database',
                                'fa-bolt': key === 'redis',
                                'fa-server': key === 'memcached'
                            }"></i>
                            <span x-text="driver.name"></span>
                        </h3>
                        <div class="flex items-center gap-2 px-3 py-1 rounded-full text-sm font-bold"
                             :class="{
                                 'bg-green-500/20 text-green-300': driver.status,
                                 'bg-red-500/20 text-red-300': !driver.status
                             }">
                            <div class="w-2 h-2 rounded-full"
                                 :class="{
                                     'bg-green-400 animate-pulse': driver.status,
                                     'bg-red-400': !driver.status
                                 }"></div>
                            <span x-text="driver.status ? 'ใช้งานได้' : 'ใช้งานไม่ได้'"></span>
                        </div>
                    </div>

                    {{-- Description --}}
                    <p class="text-white/70 text-sm mb-4" x-text="driver.description"></p>

                    {{-- Message --}}
                    <div class="mb-4 p-3 rounded-lg"
                         :class="{
                             'bg-green-500/10 border border-green-500/30': driver.status,
                             'bg-red-500/10 border border-red-500/30': !driver.status
                         }">
                        <p class="text-sm" :class="{
                            'text-green-200': driver.status,
                            'text-red-200': !driver.status
                        }" x-text="driver.message"></p>
                    </div>

                    {{-- Pros & Cons --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        {{-- Pros --}}
                        <div>
                            <h4 class="text-white font-semibold mb-2 text-sm">
                                <i class="fas fa-check-circle text-green-400 mr-1"></i>
                                ข้อดี
                            </h4>
                            <ul class="space-y-1">
                                <template x-for="(pro, index) in driver.pros" :key="index">
                                    <li class="text-white/70 text-xs" x-text="pro"></li>
                                </template>
                            </ul>
                        </div>

                        {{-- Cons --}}
                        <div>
                            <h4 class="text-white font-semibold mb-2 text-sm">
                                <i class="fas fa-exclamation-triangle text-orange-400 mr-1"></i>
                                ข้อควรระวัง
                            </h4>
                            <ul class="space-y-1">
                                <template x-for="(con, index) in driver.cons" :key="index">
                                    <li class="text-white/70 text-xs" x-text="con"></li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    {{-- Recommended --}}
                    <div class="mb-4 p-3 rounded-lg bg-purple-500/10 border border-purple-500/30">
                        <p class="text-purple-200 text-sm">
                            <i class="fas fa-lightbulb mr-1"></i>
                            <span x-text="driver.recommended"></span>
                        </p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-2 flex-wrap">
                        <template x-if="driver.status && !driver.is_current">
                            <button @click="changeDriver(key)"
                                    class="btn-primary flex-1"
                                    :disabled="switching">
                                <i class="fas fa-check mr-2"></i>
                                ใช้ Driver นี้
                            </button>
                        </template>
                        <button @click="testConnection(key)"
                                class="btn-secondary"
                                :disabled="testing[key]">
                            <i class="fas fa-plug mr-2" :class="{ 'fa-spin': testing[key] }"></i>
                            <span x-text="testing[key] ? 'กำลังทดสอบ...' : 'ทดสอบการเชื่อมต่อ'"></span>
                        </button>
                        <button @click="showInstallGuide(key)"
                                class="btn-info">
                            <i class="fas fa-book mr-2"></i>
                            คู่มือติดตั้ง
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="glass-fusion rounded-2xl p-6 border border-white/30 shadow-2xl">
        <h2 class="text-2xl font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-tools"></i>
            เครื่องมือจัดการแคช
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button @click="clearCache('config')" class="btn-secondary">
                <i class="fas fa-cog mr-2"></i>
                ล้าง Config Cache
            </button>
            <button @click="clearCache('route')" class="btn-secondary">
                <i class="fas fa-route mr-2"></i>
                ล้าง Route Cache
            </button>
            <button @click="clearCache('view')" class="btn-secondary">
                <i class="fas fa-eye mr-2"></i>
                ล้าง View Cache
            </button>
            <button @click="clearCache('all')" class="btn-danger">
                <i class="fas fa-trash-alt mr-2"></i>
                ล้างทั้งหมด
            </button>
        </div>
    </div>

    {{-- Installation Guide Modal --}}
    <div x-show="showGuide"
         x-cloak
         @click.self="showGuide = false"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4"
         x-transition>
        <div class="card-glass max-w-3xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                        <i class="fas fa-book"></i>
                        คู่มือติดตั้ง <span x-text="guideData.title"></span>
                    </h3>
                    <button @click="showGuide = false" class="text-white/70 hover:text-white">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                {{-- Requirements --}}
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-white mb-2">
                        <i class="fas fa-list-check mr-2"></i>
                        สิ่งที่ต้องมี
                    </h4>
                    <ul class="space-y-2">
                        <template x-for="(req, index) in guideData.requirements" :key="index">
                            <li class="text-white/80 flex items-start gap-2">
                                <i class="fas fa-check text-green-400 mt-1"></i>
                                <span x-text="req"></span>
                            </li>
                        </template>
                    </ul>
                </div>

                {{-- Installation Steps --}}
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-white mb-2">
                        <i class="fas fa-terminal mr-2"></i>
                        ขั้นตอนการติดตั้ง
                    </h4>
                    <div class="bg-gray-900 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                        <pre class="text-green-400" x-text="guideData.installation_steps.join('\n')"></pre>
                    </div>
                </div>

                {{-- .env Example --}}
                <div class="mb-6">
                    <h4 class="text-lg font-semibold text-white mb-2">
                        <i class="fas fa-code mr-2"></i>
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
 * Cache Manager Alpine Component
 *
 * จัดการ UI และ AJAX requests สำหรับ cache settings
 */
function cacheManager() {
    return {
        // Data
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

        /**
         * รีเฟรชสถานะ drivers ทั้งหมด
         */
        async refreshStatus() {
            this.refreshing = true;
            try {
                const response = await fetch('{{ route("admin.cache.status") }}');
                const data = await response.json();

                if (data.success) {
                    this.drivers = data.data;

                    // รีเฟรช stats ด้วย
                    const statsResponse = await fetch('{{ route("admin.cache.stats") }}');
                    const statsData = await statsResponse.json();
                    if (statsData.success) {
                        this.stats = statsData.data;
                    }

                    this.showNotification('รีเฟรชสถานะสำเร็จ', 'success');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.refreshing = false;
            }
        },

        /**
         * ทดสอบการเชื่อมต่อ
         */
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
                    this.showNotification('✅ ' + data.message, 'success');
                    // อัพเดทสถานะ
                    if (this.drivers[driver]) {
                        this.drivers[driver].status = true;
                        this.drivers[driver].message = data.message;
                    }
                } else {
                    this.showNotification('❌ ' + data.message, 'error');
                    if (this.drivers[driver]) {
                        this.drivers[driver].status = false;
                        this.drivers[driver].message = data.message;
                    }
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.testing[driver] = false;
            }
        },

        /**
         * เปลี่ยน cache driver
         */
        async changeDriver(driver) {
            if (!confirm(`ต้องการเปลี่ยน Cache Driver เป็น ${this.drivers[driver].name} หรือไม่?\n\nระบบจะล้างแคชเดิมและรีสตาร์ทอัตโนมัติ`)) {
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
                    this.showNotification(data.message, 'success');

                    // รีโหลดหน้าเพื่ออัพเดทสถานะ
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    this.showNotification('ไม่สามารถเปลี่ยน driver ได้: ' + data.message, 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.switching = false;
            }
        },

        /**
         * ล้างแคช
         */
        async clearCache(type) {
            const messages = {
                config: 'Config Cache',
                route: 'Route Cache',
                view: 'View Cache',
                all: 'Cache ทั้งหมด'
            };

            if (!confirm(`ต้องการล้าง ${messages[type]} หรือไม่?`)) {
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
                    this.showNotification(data.message, 'success');
                    // รีเฟรชสถานะ
                    await this.refreshStatus();
                } else {
                    this.showNotification('ไม่สามารถล้างแคชได้: ' + data.message, 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.clearing = false;
            }
        },

        /**
         * ปรับแต่งแคช (optimize)
         */
        async optimizeCache() {
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
                    this.showNotification(data.message, 'success');
                } else {
                    this.showNotification('ไม่สามารถปรับแต่งแคชได้: ' + data.message, 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            } finally {
                this.optimizing = false;
            }
        },

        /**
         * แสดงคู่มือติดตั้ง
         */
        async showInstallGuide(driver) {
            try {
                const response = await fetch(`{{ route("admin.cache.installation-guide") }}?driver=${driver}`);
                const data = await response.json();

                if (data.success) {
                    this.guideData = data.data;
                    this.showGuide = true;
                } else {
                    this.showNotification('ไม่สามารถโหลดคู่มือได้', 'error');
                }
            } catch (error) {
                this.showNotification('เกิดข้อผิดพลาด: ' + error.message, 'error');
            }
        },

        /**
         * แสดง notification
         */
        showNotification(message, type = 'info') {
            // ใช้ notification system ของเว็บ (ถ้ามี)
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
