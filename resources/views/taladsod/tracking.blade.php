{{--
    หน้าติดตามไรเดอร์แบบ Real-time บน Google Maps
    ลูกค้าเปิดหน้านี้เพื่อดูตำแหน่งไรเดอร์และสถานะงาน

    ข้อมูลที่ส่งมาจาก RiderTrackingController@show:
    - $job, $rider, $order, $location, $customerLocation, $pickupLocation
    - $token, $googleMapsApiKey, $pollInterval, $customerPollInterval
--}}
@extends('layouts.taladsod')

@section('title', 'ติดตามไรเดอร์ - งาน #' . $job->job_number)

@section('meta')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('styles')
<style>
    /* สไตล์สำหรับ Google Maps */
    #tracking-map {
        width: 100%;
        height: 100%;
        min-height: 350px;
    }

    /* แอนิเมชันจุดกระพริบสำหรับสถานะ GPS */
    @keyframes gps-pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.2); }
    }
    .gps-pulse {
        animation: gps-pulse 2s ease-in-out infinite;
    }

    /* แอนิเมชันไรเดอร์เคลื่อนที่ */
    @keyframes rider-move {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }
    .rider-bounce {
        animation: rider-move 1.5s ease-in-out infinite;
    }

    /* Progress bar สีไล่ */
    .progress-gradient {
        background: linear-gradient(90deg, #22C55E 0%, #F97316 100%);
    }

    /* Custom info window สำหรับ Google Maps */
    .gm-style-iw-d { overflow: hidden !important; }
    .gm-style-iw { padding: 0 !important; }
</style>
@endsection

@section('content')
<div x-data="riderTracking()" x-init="init()" class="max-w-4xl mx-auto px-4 py-4 sm:py-6">

    {{-- ===== แบนเนอร์เตือน GPS ไม่ทำงาน ===== --}}
    <div x-show="!gpsActive && jobStatus !== 'completed' && jobStatus !== 'delivered'"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="mb-4 bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-700 rounded-xl px-4 py-3 flex items-start gap-3">
        <div class="flex-shrink-0 mt-0.5">
            <i class="fas fa-exclamation-triangle text-amber-500 text-lg"></i>
        </div>
        <div>
            <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                GPS ไรเดอร์ไม่ได้เปิดอยู่
            </p>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                ตำแหน่งที่แสดงอาจไม่ใช่ตำแหน่งปัจจุบัน กรุณาโทรหาไรเดอร์เพื่อสอบถามสถานะ
            </p>
        </div>
    </div>

    {{-- ===== แผนที่ Google Maps ===== --}}
    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden mb-4">
        {{-- Loading overlay --}}
        <div x-show="mapLoading"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 z-10 bg-white/90 dark:bg-gray-800/90 flex flex-col items-center justify-center">
            <div class="w-12 h-12 border-4 border-green-200 border-t-green-500 rounded-full animate-spin mb-3"></div>
            <p class="text-sm text-gray-500 dark:text-gray-400">กำลังโหลดแผนที่...</p>
        </div>

        {{-- แผนที่ --}}
        <div id="tracking-map" class="h-[350px] sm:h-[400px] lg:h-[450px]"></div>

        {{-- ปุ่มควบคุมแผนที่ --}}
        <div class="absolute top-3 right-3 z-10 flex flex-col gap-2">
            {{-- ปุ่มปรับมุมมองให้เห็นทุก marker --}}
            <button @click="fitAllMarkers()"
                    class="w-10 h-10 bg-white dark:bg-gray-700 rounded-lg shadow-md flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-600 transition-colors"
                    title="แสดงทั้งหมด">
                <i class="fas fa-expand"></i>
            </button>
            {{-- ปุ่มติดตามไรเดอร์ --}}
            <button @click="centerOnRider()"
                    class="w-10 h-10 bg-white dark:bg-gray-700 rounded-lg shadow-md flex items-center justify-center text-gray-600 dark:text-gray-300 hover:bg-green-50 dark:hover:bg-gray-600 transition-colors"
                    title="ติดตามไรเดอร์">
                <i class="fas fa-motorcycle text-green-500"></i>
            </button>
        </div>

        {{-- แถบข้อมูลระยะทางด้านล่างแผนที่ --}}
        <div class="absolute bottom-3 left-3 right-3 z-10">
            <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm rounded-xl px-4 py-2.5 shadow-md flex items-center justify-between"
                 x-show="riderLat !== null">
                <div class="flex items-center gap-2">
                    <i class="fas fa-route text-green-500"></i>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        ระยะห่าง:
                        <span class="text-green-600 dark:text-green-400 font-bold" x-text="distanceText"></span>
                    </span>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                    <span class="w-2 h-2 rounded-full gps-pulse" :class="gpsActive ? 'bg-green-500' : 'bg-amber-500'"></span>
                    <span x-text="gpsActive ? 'GPS ทำงาน' : 'GPS ปิดอยู่'"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Progress Bar สถานะการจัดส่ง ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 mb-4">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">สถานะการจัดส่ง</h2>

        {{-- Progress Steps --}}
        <div class="relative">
            {{-- เส้น Progress พื้นหลัง --}}
            <div class="absolute top-4 left-4 right-4 h-1 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
            {{-- เส้น Progress ที่เติมสี --}}
            <div class="absolute top-4 left-4 h-1 rounded-full progress-gradient transition-all duration-700 ease-out"
                 :style="'width: ' + progressPercent + '%'"></div>

            {{-- Steps --}}
            <div class="relative flex justify-between">
                <template x-for="(step, index) in statusSteps" :key="step.key">
                    <div class="flex flex-col items-center" style="width: 14.28%;">
                        {{-- วงกลมสถานะ --}}
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300 relative z-10"
                             :class="getStepClass(step.key)">
                            <i :class="step.icon" x-show="isStepCompleted(step.key) || isStepActive(step.key)"></i>
                            <span x-show="!isStepCompleted(step.key) && !isStepActive(step.key)" x-text="index + 1"></span>
                        </div>
                        {{-- ป้ายชื่อสถานะ (ซ่อนบน mobile เล็ก) --}}
                        <span class="hidden sm:block text-[10px] sm:text-xs text-center mt-2 leading-tight"
                              :class="isStepActive(step.key) ? 'text-green-600 dark:text-green-400 font-semibold' : (isStepCompleted(step.key) ? 'text-gray-500 dark:text-gray-400' : 'text-gray-400 dark:text-gray-500')"
                              x-text="step.label">
                        </span>
                    </div>
                </template>
            </div>
        </div>

        {{-- สถานะปัจจุบัน (แสดงชัดบน mobile) --}}
        <div class="mt-5 flex items-center justify-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium"
                  :class="statusBadgeClass">
                <i :class="currentStatusIcon"></i>
                <span x-text="currentStatusText"></span>
            </span>
        </div>
    </div>

    {{-- ===== การ์ดข้อมูลไรเดอร์ ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 mb-4">
        <div class="flex items-start gap-4">
            {{-- รูปโปรไฟล์ไรเดอร์ --}}
            <div class="flex-shrink-0">
                @if($rider->profile_image)
                    <img src="{{ $rider->profile_image }}"
                         alt="{{ $rider->full_name }}"
                         class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl object-cover ring-2 ring-green-200 dark:ring-green-800">
                @else
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl bg-gradient-to-br from-green-400 to-green-600 flex items-center justify-center ring-2 ring-green-200 dark:ring-green-800">
                        <i class="fas fa-motorcycle text-white text-xl sm:text-2xl"></i>
                    </div>
                @endif
            </div>

            {{-- ข้อมูลไรเดอร์ --}}
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white truncate">
                    {{ $rider->full_name }}
                </h3>
                <div class="mt-1 space-y-1">
                    {{-- ข้อมูลยานพาหนะ --}}
                    @if($rider->vehicle_type)
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                            <i class="fas fa-{{ $rider->vehicle_type === 'motorcycle' ? 'motorcycle' : ($rider->vehicle_type === 'car' ? 'car' : 'bicycle') }} text-gray-400 w-4 text-center"></i>
                            <span>
                                {{ ['motorcycle' => 'มอเตอร์ไซค์', 'car' => 'รถยนต์', 'bicycle' => 'จักรยาน'][$rider->vehicle_type] ?? $rider->vehicle_type }}
                                @if($rider->vehicle_color)
                                    <span class="text-gray-400">- {{ $rider->vehicle_color }}</span>
                                @endif
                            </span>
                        </p>
                    @endif
                    {{-- ป้ายทะเบียน --}}
                    @if($rider->vehicle_plate)
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                            <i class="fas fa-id-card text-gray-400 w-4 text-center"></i>
                            <span class="font-mono font-medium text-gray-700 dark:text-gray-300">{{ $rider->vehicle_plate }}</span>
                        </p>
                    @endif
                </div>
            </div>

            {{-- ปุ่มโทรหาไรเดอร์ --}}
            @if($rider->phone)
                <a href="tel:{{ $rider->phone }}"
                   class="flex-shrink-0 w-12 h-12 sm:w-14 sm:h-14 bg-green-500 hover:bg-green-600 active:bg-green-700 text-white rounded-xl flex items-center justify-center shadow-lg shadow-green-500/25 transition-all hover:scale-105 active:scale-95">
                    <i class="fas fa-phone text-lg sm:text-xl"></i>
                </a>
            @endif
        </div>

        {{-- ปุ่มโทรเต็มความกว้าง (แสดงเฉพาะ mobile) --}}
        @if($rider->phone)
            <a href="tel:{{ $rider->phone }}"
               class="sm:hidden mt-4 w-full flex items-center justify-center gap-2 px-4 py-3 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl font-medium text-sm hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors">
                <i class="fas fa-phone"></i>
                โทรหาไรเดอร์ {{ $rider->phone }}
            </a>
        @endif
    </div>

    {{-- ===== ข้อมูลจุดรับ-ส่ง ===== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        {{-- จุดรับสินค้า --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/40 rounded-lg flex items-center justify-center">
                    <i class="fas fa-store text-orange-500 text-sm"></i>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">จุดรับสินค้า</h4>
            </div>
            @if($pickupLocation['contact_name'] ?? null)
                <p class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $pickupLocation['contact_name'] }}</p>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                {{ $pickupLocation['address'] ?? 'ไม่ระบุที่อยู่' }}
            </p>
        </div>

        {{-- จุดส่งสินค้า --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/40 rounded-lg flex items-center justify-center">
                    <i class="fas fa-house text-green-500 text-sm"></i>
                </div>
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">จุดส่งสินค้า</h4>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
                {{ $customerLocation['address'] ?? 'ไม่ระบุที่อยู่' }}
            </p>
        </div>
    </div>

    {{-- ===== ข้อมูลงานและออเดอร์ ===== --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-4 sm:p-6 mb-4">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">รายละเอียดงาน</h3>
        <dl class="space-y-2.5 text-sm">
            <div class="flex justify-between items-center">
                <dt class="text-gray-500 dark:text-gray-400">หมายเลขงาน</dt>
                <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $job->job_number }}</dd>
            </div>
            @if($order)
                <div class="flex justify-between items-center">
                    <dt class="text-gray-500 dark:text-gray-400">หมายเลขออเดอร์</dt>
                    <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</dd>
                </div>
            @endif
            <div class="flex justify-between items-center">
                <dt class="text-gray-500 dark:text-gray-400">อัปเดตล่าสุด</dt>
                <dd class="text-gray-900 dark:text-white flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full gps-pulse" :class="gpsActive ? 'bg-green-500' : 'bg-gray-400'"></span>
                    <span x-text="lastUpdatedText">{{ $location['updated_ago'] ?? 'ไม่ทราบ' }}</span>
                </dd>
            </div>
            <div class="flex justify-between items-center">
                <dt class="text-gray-500 dark:text-gray-400">รีเฟรชอัตโนมัติ</dt>
                <dd class="text-gray-900 dark:text-white">
                    ทุก <span x-text="Math.round(pollIntervalMs / 1000)"></span> วินาที
                </dd>
            </div>
        </dl>
    </div>

    {{-- ===== ปุ่มรีเฟรชด้วยตนเอง ===== --}}
    <div class="text-center mb-8">
        <button @click="fetchLocation()"
                :disabled="fetching"
                class="inline-flex items-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 rounded-xl text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700 transition-all shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
            <i class="fas fa-sync-alt" :class="fetching ? 'animate-spin' : ''"></i>
            <span x-text="fetching ? 'กำลังอัปเดต...' : 'รีเฟรชตำแหน่ง'"></span>
        </button>
    </div>
</div>
@endsection

@section('scripts')
{{-- โหลด Google Maps API --}}
<script>
    /**
     * ฟังก์ชัน callback เมื่อ Google Maps โหลดสำเร็จ
     */
    function initGoogleMaps() {
        window.googleMapsReady = true;
        window.dispatchEvent(new Event('google-maps-ready'));
    }
</script>
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&callback=initGoogleMaps&libraries=geometry" async defer></script>

<script>
    /**
     * Alpine.js component สำหรับติดตามไรเดอร์
     * จัดการแผนที่ Google Maps, การ polling ตำแหน่ง และ UI ทั้งหมด
     */
    function riderTracking() {
        return {
            /* ===== State ===== */

            /** @type {number|null} ละติจูดไรเดอร์ */
            riderLat: {{ $location['available'] && $location['latitude'] ? $location['latitude'] : 'null' }},
            /** @type {number|null} ลองจิจูดไรเดอร์ */
            riderLng: {{ $location['available'] && $location['longitude'] ? $location['longitude'] : 'null' }},
            /** @type {boolean} GPS ไรเดอร์เปิดอยู่หรือไม่ */
            gpsActive: {{ $location['gps_active'] ?? false ? 'true' : 'false' }},
            /** @type {string} สถานะงานปัจจุบัน */
            jobStatus: '{{ $job->status }}',
            /** @type {string} ข้อความอัปเดตล่าสุด */
            lastUpdatedText: '{{ $location['updated_ago'] ?? 'ไม่ทราบ' }}',
            /** @type {boolean} กำลังโหลดแผนที่ */
            mapLoading: true,
            /** @type {boolean} กำลัง fetch ตำแหน่ง */
            fetching: false,
            /** @type {number} ระยะทาง (เมตร) */
            distanceMeters: 0,
            /** @type {number} Polling interval (ms) */
            pollIntervalMs: {{ $customerPollInterval ?? 180000 }},
            /** @type {number|null} Polling timer ID */
            pollTimer: null,

            /* ===== Google Maps Objects ===== */

            /** @type {google.maps.Map|null} */
            map: null,
            /** @type {google.maps.Marker|null} */
            riderMarker: null,
            /** @type {google.maps.Marker|null} */
            customerMarker: null,
            /** @type {google.maps.Marker|null} */
            pickupMarker: null,

            /* ===== ข้อมูลตำแหน่งคงที่ ===== */

            customerLat: {{ $customerLocation['latitude'] ?? 'null' }},
            customerLng: {{ $customerLocation['longitude'] ?? 'null' }},
            pickupLat: {{ $pickupLocation['latitude'] ?? 'null' }},
            pickupLng: {{ $pickupLocation['longitude'] ?? 'null' }},

            /* ===== สถานะ Steps ===== */

            /** ขั้นตอนการจัดส่งทั้งหมด */
            statusSteps: [
                { key: 'pending', label: 'รอรับงาน', icon: 'fas fa-clock' },
                { key: 'accepted', label: 'รับงาน', icon: 'fas fa-check' },
                { key: 'picking_up', label: 'กำลังไปรับ', icon: 'fas fa-store' },
                { key: 'picked_up', label: 'รับแล้ว', icon: 'fas fa-box' },
                { key: 'delivering', label: 'กำลังส่ง', icon: 'fas fa-shipping-fast' },
                { key: 'delivered', label: 'ส่งแล้ว', icon: 'fas fa-check-double' },
                { key: 'completed', label: 'สำเร็จ', icon: 'fas fa-flag-checkered' },
            ],

            /** สถานะแปลเป็นภาษาไทย */
            statusTexts: {
                'pending': 'รอไรเดอร์รับงาน',
                'accepted': 'ไรเดอร์รับงานแล้ว',
                'picking_up': 'ไรเดอร์กำลังไปรับสินค้า',
                'picked_up': 'ไรเดอร์รับสินค้าแล้ว',
                'delivering': 'กำลังจัดส่งถึงคุณ',
                'delivered': 'ส่งสินค้าแล้ว',
                'completed': 'จัดส่งสำเร็จ',
                'cancelled': 'ยกเลิกงาน',
            },

            /* ===== Computed Properties ===== */

            /** ข้อความระยะทาง */
            get distanceText() {
                if (this.riderLat === null) return 'ไม่ทราบ';
                if (this.distanceMeters < 1000) {
                    return Math.round(this.distanceMeters) + ' ม.';
                }
                return (this.distanceMeters / 1000).toFixed(1) + ' กม.';
            },

            /** เปอร์เซ็นต์ progress bar */
            get progressPercent() {
                const order = ['pending', 'accepted', 'picking_up', 'picked_up', 'delivering', 'delivered', 'completed'];
                const idx = order.indexOf(this.jobStatus);
                if (idx === -1) return 0;
                return Math.min(100, (idx / (order.length - 1)) * 100);
            },

            /** ข้อความสถานะปัจจุบัน */
            get currentStatusText() {
                return this.statusTexts[this.jobStatus] || this.jobStatus;
            },

            /** ไอคอนสถานะปัจจุบัน */
            get currentStatusIcon() {
                const step = this.statusSteps.find(s => s.key === this.jobStatus);
                return step ? step.icon : 'fas fa-info-circle';
            },

            /** คลาส CSS สำหรับ badge สถานะ */
            get statusBadgeClass() {
                const map = {
                    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400',
                    'accepted': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400',
                    'picking_up': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-400',
                    'picked_up': 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-400',
                    'delivering': 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400',
                    'delivered': 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-400',
                    'completed': 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400',
                    'cancelled': 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400',
                };
                return map[this.jobStatus] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
            },

            /* ===== Methods ===== */

            /**
             * เริ่มต้น component: โหลดแผนที่และตั้ง polling
             */
            init() {
                if (window.googleMapsReady) {
                    this.initMap();
                } else {
                    window.addEventListener('google-maps-ready', () => this.initMap());
                }
            },

            /**
             * สร้าง Google Maps พร้อม markers ทั้งหมด
             */
            initMap() {
                /* สไตล์แผนที่แบบ clean modern */
                const mapStyles = [
                    { elementType: 'geometry', stylers: [{ color: '#f5f5f5' }] },
                    { elementType: 'labels.icon', stylers: [{ visibility: 'on' }] },
                    { elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
                    { elementType: 'labels.text.stroke', stylers: [{ color: '#f5f5f5' }] },
                    { featureType: 'administrative.land_parcel', elementType: 'labels.text.fill', stylers: [{ color: '#bdbdbd' }] },
                    { featureType: 'poi', elementType: 'geometry', stylers: [{ color: '#eeeeee' }] },
                    { featureType: 'poi', elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
                    { featureType: 'poi.park', elementType: 'geometry', stylers: [{ color: '#c8e6c9' }] },
                    { featureType: 'poi.park', elementType: 'labels.text.fill', stylers: [{ color: '#388e3c' }] },
                    { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#ffffff' }] },
                    { featureType: 'road.arterial', elementType: 'labels.text.fill', stylers: [{ color: '#757575' }] },
                    { featureType: 'road.highway', elementType: 'geometry', stylers: [{ color: '#dadada' }] },
                    { featureType: 'road.highway', elementType: 'labels.text.fill', stylers: [{ color: '#616161' }] },
                    { featureType: 'road.local', elementType: 'labels.text.fill', stylers: [{ color: '#9e9e9e' }] },
                    { featureType: 'transit.line', elementType: 'geometry', stylers: [{ color: '#e5e5e5' }] },
                    { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#bbdefb' }] },
                    { featureType: 'water', elementType: 'labels.text.fill', stylers: [{ color: '#64b5f6' }] },
                ];

                /* จุดกลางเริ่มต้น: ใช้ตำแหน่งไรเดอร์ หรือตำแหน่งลูกค้า หรือกรุงเทพฯ */
                const centerLat = this.riderLat || this.customerLat || 13.7563;
                const centerLng = this.riderLng || this.customerLng || 100.5018;

                this.map = new google.maps.Map(document.getElementById('tracking-map'), {
                    center: { lat: centerLat, lng: centerLng },
                    zoom: 14,
                    styles: mapStyles,
                    disableDefaultUI: true,
                    zoomControl: true,
                    zoomControlOptions: {
                        position: google.maps.ControlPosition.LEFT_BOTTOM,
                    },
                    gestureHandling: 'greedy',
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                });

                /* สร้าง Marker: จุดรับสินค้า (ร้านค้า) */
                if (this.pickupLat && this.pickupLng) {
                    this.pickupMarker = new google.maps.Marker({
                        position: { lat: this.pickupLat, lng: this.pickupLng },
                        map: this.map,
                        title: '{{ addslashes($pickupLocation['contact_name'] ?? 'จุดรับสินค้า') }}',
                        icon: {
                            url: 'data:image/svg+xml,' + encodeURIComponent(`
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="48" viewBox="0 0 40 48">
                                    <path d="M20 0C8.95 0 0 8.95 0 20c0 14 20 28 20 28s20-14 20-28C40 8.95 31.05 0 20 0z" fill="#F97316"/>
                                    <circle cx="20" cy="18" r="10" fill="white"/>
                                    <text x="20" y="23" text-anchor="middle" font-size="16" fill="#F97316">🏪</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(40, 48),
                            anchor: new google.maps.Point(20, 48),
                        },
                        zIndex: 1,
                    });

                    /* Info window สำหรับจุดรับสินค้า */
                    const pickupInfo = new google.maps.InfoWindow({
                        content: `<div style="font-family: 'Kanit', sans-serif; padding: 8px;">
                            <p style="font-weight: 600; margin: 0 0 4px;">{{ addslashes($pickupLocation['contact_name'] ?? 'จุดรับสินค้า') }}</p>
                            <p style="font-size: 12px; color: #666; margin: 0;">{{ addslashes(Str::limit($pickupLocation['address'] ?? '', 60)) }}</p>
                        </div>`,
                    });
                    this.pickupMarker.addListener('click', () => pickupInfo.open(this.map, this.pickupMarker));
                }

                /* สร้าง Marker: จุดส่งสินค้า (บ้านลูกค้า) */
                if (this.customerLat && this.customerLng) {
                    this.customerMarker = new google.maps.Marker({
                        position: { lat: this.customerLat, lng: this.customerLng },
                        map: this.map,
                        title: 'ตำแหน่งของคุณ',
                        icon: {
                            url: 'data:image/svg+xml,' + encodeURIComponent(`
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="48" viewBox="0 0 40 48">
                                    <path d="M20 0C8.95 0 0 8.95 0 20c0 14 20 28 20 28s20-14 20-28C40 8.95 31.05 0 20 0z" fill="#22C55E"/>
                                    <circle cx="20" cy="18" r="10" fill="white"/>
                                    <text x="20" y="23" text-anchor="middle" font-size="16" fill="#22C55E">🏠</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(40, 48),
                            anchor: new google.maps.Point(20, 48),
                        },
                        zIndex: 1,
                    });

                    const customerInfo = new google.maps.InfoWindow({
                        content: `<div style="font-family: 'Kanit', sans-serif; padding: 8px;">
                            <p style="font-weight: 600; margin: 0 0 4px;">ตำแหน่งของคุณ</p>
                            <p style="font-size: 12px; color: #666; margin: 0;">{{ addslashes(Str::limit($customerLocation['address'] ?? '', 60)) }}</p>
                        </div>`,
                    });
                    this.customerMarker.addListener('click', () => customerInfo.open(this.map, this.customerMarker));
                }

                /* สร้าง Marker: ไรเดอร์ */
                if (this.riderLat && this.riderLng) {
                    this.createRiderMarker();
                    this.calculateDistance();
                }

                /* ปรับมุมมองให้เห็นทุก marker */
                this.fitAllMarkers();

                /* ซ่อน loading */
                this.mapLoading = false;

                /* เริ่ม polling ตำแหน่งไรเดอร์ */
                this.startPolling();
            },

            /**
             * สร้าง marker ไรเดอร์ (ไอคอนมอเตอร์ไซค์)
             */
            createRiderMarker() {
                if (this.riderMarker) {
                    this.riderMarker.setPosition({ lat: this.riderLat, lng: this.riderLng });
                    return;
                }

                this.riderMarker = new google.maps.Marker({
                    position: { lat: this.riderLat, lng: this.riderLng },
                    map: this.map,
                    title: '{{ addslashes($rider->full_name) }}',
                    icon: {
                        url: 'data:image/svg+xml,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 48 48">
                                <circle cx="24" cy="24" r="22" fill="#22C55E" stroke="white" stroke-width="3"/>
                                <text x="24" y="30" text-anchor="middle" font-size="22" fill="white">🏍️</text>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(48, 48),
                        anchor: new google.maps.Point(24, 24),
                    },
                    zIndex: 10,
                    optimized: false,
                });

                const riderInfo = new google.maps.InfoWindow({
                    content: `<div style="font-family: 'Kanit', sans-serif; padding: 8px;">
                        <p style="font-weight: 600; margin: 0;">{{ addslashes($rider->full_name) }}</p>
                        <p style="font-size: 12px; color: #22C55E; margin: 4px 0 0;">ไรเดอร์ของคุณ</p>
                    </div>`,
                });
                this.riderMarker.addListener('click', () => riderInfo.open(this.map, this.riderMarker));
            },

            /**
             * เริ่ม polling ตำแหน่งไรเดอร์ทุก customerPollInterval
             */
            startPolling() {
                /* หยุด polling ถ้างานเสร็จแล้ว */
                const doneStatuses = ['delivered', 'completed', 'cancelled'];
                if (doneStatuses.includes(this.jobStatus)) return;

                this.pollTimer = setInterval(() => {
                    this.fetchLocation();
                }, this.pollIntervalMs);
            },

            /**
             * หยุด polling
             */
            stopPolling() {
                if (this.pollTimer) {
                    clearInterval(this.pollTimer);
                    this.pollTimer = null;
                }
            },

            /**
             * ดึงตำแหน่งไรเดอร์ล่าสุดจาก API
             */
            async fetchLocation() {
                if (this.fetching) return;
                this.fetching = true;

                try {
                    const response = await fetch('/taladsod/track/{{ $token }}/location', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('ไม่สามารถดึงตำแหน่งได้ (HTTP ' + response.status + ')');
                    }

                    const data = await response.json();

                    /* อัปเดตข้อมูลตำแหน่ง */
                    if (data.location && data.location.available) {
                        const newLat = parseFloat(data.location.latitude);
                        const newLng = parseFloat(data.location.longitude);

                        /* ตรวจสอบว่าตำแหน่งเปลี่ยนจริง */
                        if (newLat !== this.riderLat || newLng !== this.riderLng) {
                            this.riderLat = newLat;
                            this.riderLng = newLng;
                            this.updateMarker();
                            this.calculateDistance();
                        }

                        this.gpsActive = data.location.gps_active || false;
                        this.lastUpdatedText = data.location.updated_ago || 'เมื่อสักครู่';
                    }

                    /* อัปเดตสถานะงาน (API คืน job_status ไม่ใช่ status) */
                    if (data.job_status) {
                        const oldStatus = this.jobStatus;
                        this.jobStatus = data.job_status;

                        /* หยุด polling ถ้างานเสร็จแล้ว */
                        const doneStatuses = ['delivered', 'completed', 'cancelled'];
                        if (doneStatuses.includes(data.job_status) && !doneStatuses.includes(oldStatus)) {
                            this.stopPolling();
                        }
                    }

                } catch (error) {
                    console.error('ดึงตำแหน่งไรเดอร์ผิดพลาด:', error);
                } finally {
                    this.fetching = false;
                }
            },

            /**
             * ย้าย marker ไรเดอร์ไปตำแหน่งใหม่แบบ smooth
             */
            updateMarker() {
                if (!this.map) return;

                if (this.riderLat && this.riderLng) {
                    if (!this.riderMarker) {
                        this.createRiderMarker();
                    } else {
                        /* ย้ายตำแหน่งแบบ smooth ด้วย animation */
                        const newPos = new google.maps.LatLng(this.riderLat, this.riderLng);
                        this.animateMarker(this.riderMarker, newPos, 800);
                    }
                }
            },

            /**
             * เคลื่อนย้าย marker แบบ smooth animation
             * @param {google.maps.Marker} marker
             * @param {google.maps.LatLng} newPosition
             * @param {number} duration ระยะเวลา (ms)
             */
            animateMarker(marker, newPosition, duration) {
                const startPos = marker.getPosition();
                const startLat = startPos.lat();
                const startLng = startPos.lng();
                const endLat = newPosition.lat();
                const endLng = newPosition.lng();
                const startTime = performance.now();

                const animate = (currentTime) => {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);

                    /* Easing function: ease-out cubic */
                    const eased = 1 - Math.pow(1 - progress, 3);

                    const lat = startLat + (endLat - startLat) * eased;
                    const lng = startLng + (endLng - startLng) * eased;

                    marker.setPosition({ lat, lng });

                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    }
                };

                requestAnimationFrame(animate);
            },

            /**
             * คำนวณระยะทางระหว่างไรเดอร์กับลูกค้า (Haversine)
             */
            calculateDistance() {
                if (!this.riderLat || !this.customerLat) {
                    this.distanceMeters = 0;
                    return;
                }

                /* ใช้ Google Maps Geometry Library ถ้ามี */
                if (google.maps.geometry) {
                    const riderPos = new google.maps.LatLng(this.riderLat, this.riderLng);
                    const customerPos = new google.maps.LatLng(this.customerLat, this.customerLng);
                    this.distanceMeters = google.maps.geometry.spherical.computeDistanceBetween(riderPos, customerPos);
                } else {
                    /* Haversine fallback */
                    this.distanceMeters = this.haversine(
                        this.riderLat, this.riderLng,
                        this.customerLat, this.customerLng
                    );
                }
            },

            /**
             * Haversine formula คำนวณระยะทาง (เมตร) ระหว่าง 2 จุดพิกัด
             */
            haversine(lat1, lon1, lat2, lon2) {
                const R = 6371000; // รัศมีโลก (เมตร)
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                          Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                          Math.sin(dLon / 2) * Math.sin(dLon / 2);
                const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
                return R * c;
            },

            /**
             * ปรับมุมมองแผนที่ให้เห็น markers ทั้งหมด
             */
            fitAllMarkers() {
                if (!this.map) return;

                const bounds = new google.maps.LatLngBounds();
                let hasPoints = false;

                if (this.riderLat && this.riderLng) {
                    bounds.extend({ lat: this.riderLat, lng: this.riderLng });
                    hasPoints = true;
                }
                if (this.customerLat && this.customerLng) {
                    bounds.extend({ lat: this.customerLat, lng: this.customerLng });
                    hasPoints = true;
                }
                if (this.pickupLat && this.pickupLng) {
                    bounds.extend({ lat: this.pickupLat, lng: this.pickupLng });
                    hasPoints = true;
                }

                if (hasPoints) {
                    this.map.fitBounds(bounds, { top: 60, right: 60, bottom: 60, left: 60 });

                    /* ไม่ให้ zoom เข้าไปเกินไป */
                    const listener = google.maps.event.addListener(this.map, 'idle', () => {
                        if (this.map.getZoom() > 16) {
                            this.map.setZoom(16);
                        }
                        google.maps.event.removeListener(listener);
                    });
                }
            },

            /**
             * เลื่อนแผนที่ไปที่ตำแหน่งไรเดอร์
             */
            centerOnRider() {
                if (!this.map || !this.riderLat) return;
                this.map.panTo({ lat: this.riderLat, lng: this.riderLng });
                this.map.setZoom(16);
            },

            /* ===== Step Progress Helpers ===== */

            /**
             * ตรวจสอบว่า step นี้ผ่านไปแล้วหรือยัง
             */
            isStepCompleted(stepKey) {
                const order = ['pending', 'accepted', 'picking_up', 'picked_up', 'delivering', 'delivered', 'completed'];
                const currentIdx = order.indexOf(this.jobStatus);
                const stepIdx = order.indexOf(stepKey);
                return stepIdx < currentIdx;
            },

            /**
             * ตรวจสอบว่า step นี้เป็นสถานะปัจจุบัน
             */
            isStepActive(stepKey) {
                return this.jobStatus === stepKey;
            },

            /**
             * คลาส CSS สำหรับวงกลม step
             */
            getStepClass(stepKey) {
                if (this.isStepActive(stepKey)) {
                    return 'bg-green-500 text-white ring-4 ring-green-200 dark:ring-green-800 shadow-lg';
                }
                if (this.isStepCompleted(stepKey)) {
                    return 'bg-green-500 text-white';
                }
                return 'bg-gray-200 dark:bg-gray-600 text-gray-400 dark:text-gray-500';
            },

            /**
             * ทำลาย component — หยุด polling
             */
            destroy() {
                this.stopPolling();
            },
        };
    }
</script>
@endsection
