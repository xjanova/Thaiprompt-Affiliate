{{--
    Web GPS Tracker Component

    ใช้ติดตาม GPS ผ่าน Web Browser โดยไม่ต้องลงแอพ
    รองรับทั้ง Desktop และ Mobile Browser

    @props
    - booking-id: รหัสการจอง
    - entity-type: 'user' หรือ 'provider'
    - auto-start: true/false - เริ่มติดตามอัตโนมัติ
    - show-map: true/false - แสดงแผนที่
    - compact: true/false - แสดงแบบกระชับ

    @usage
    <x-web-gps-tracker :booking-id="$booking->id" entity-type="user" />
--}}

@props([
    'bookingId' => null,
    'entityType' => 'user',
    'autoStart' => false,
    'showMap' => true,
    'compact' => false,
    'apiEndpoint' => '/api/v1/service-bookings',
])

<div x-data="webGpsTracker({
        bookingId: {{ $bookingId ?? 'null' }},
        entityType: '{{ $entityType }}',
        apiEndpoint: '{{ $apiEndpoint }}',
        debug: {{ config('app.debug') ? 'true' : 'false' }}
    })"
     x-init="init()"
     {{ $attributes->merge(['class' => 'web-gps-tracker']) }}>

    {{-- Permission Request Banner (แสดงเมื่อยังไม่ได้ให้สิทธิ์) --}}
    <template x-if="hasPermission === false">
        <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-xl">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        ต้องการสิทธิ์เข้าถึง GPS
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>เพื่อให้ระบบติดตามตำแหน่งทำงานได้ กรุณาอนุญาตให้เว็บไซต์เข้าถึงตำแหน่งของคุณ</p>
                        <p class="mt-1 text-xs">
                            <strong>วิธีอนุญาต:</strong> คลิกที่ไอคอนกุญแจ/ล็อค ข้างๆ URL แล้วเลือก "Allow" หรือ "อนุญาต"
                        </p>
                    </div>
                    <div class="mt-3">
                        <button @click="startTracking()"
                                type="button"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                            <i class="fas fa-location-arrow mr-2"></i>
                            ขอสิทธิ์เข้าถึง GPS
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Unsupported Browser --}}
    <template x-if="!isSupported">
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-times-circle text-red-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Browser ไม่รองรับ GPS
                    </h3>
                    <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                        <p>Browser ของคุณไม่รองรับการติดตาม GPS</p>
                        <p class="mt-1">กรุณาใช้ Browser อื่น เช่น:</p>
                        <ul class="list-disc list-inside mt-1">
                            <li>Google Chrome (แนะนำ)</li>
                            <li>Mozilla Firefox</li>
                            <li>Safari</li>
                            <li>Microsoft Edge</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </template>

    {{-- Error Message --}}
    <template x-if="errorMessage && status === 'error'">
        <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-xl">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-700 dark:text-red-300" x-text="errorMessage"></span>
            </div>
        </div>
    </template>

    {{-- Main Tracking UI --}}
    <template x-if="isSupported">
        @if($compact)
            {{-- Compact View --}}
            <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl shadow">
                {{-- Status Indicator --}}
                <div class="flex items-center">
                    <div class="relative">
                        <div :class="{
                                'bg-green-500': status === 'tracking',
                                'bg-blue-500': status === 'located',
                                'bg-red-500': status === 'error',
                                'bg-yellow-500': status === 'locating' || status === 'starting',
                                'bg-gray-500': status === 'idle' || status === 'stopped'
                            }"
                             class="w-3 h-3 rounded-full animate-pulse"></div>
                        <div x-show="isTracking"
                             class="absolute inset-0 w-3 h-3 rounded-full bg-green-500 animate-ping"></div>
                    </div>
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300" x-text="statusMessage || 'พร้อมติดตาม'"></span>
                </div>

                {{-- Control Buttons --}}
                <div class="flex items-center space-x-2">
                    <button @click="getMyLocation()"
                            type="button"
                            :disabled="status === 'locating'"
                            class="p-2 text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400 disabled:opacity-50">
                        <i class="fas fa-crosshairs"></i>
                    </button>
                    <button @click="toggleTracking()"
                            type="button"
                            :class="isTracking ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'"
                            class="px-4 py-2 text-white text-sm font-medium rounded-lg transition">
                        <i :class="isTracking ? 'fas fa-stop' : 'fas fa-play'" class="mr-1"></i>
                        <span x-text="isTracking ? 'หยุด' : 'เริ่ม'"></span>
                    </button>
                </div>
            </div>
        @else
            {{-- Full View --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                {{-- Header --}}
                <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <i :class="getStatusIcon()"
                               :style="'color: ' + getStatusColor()"
                               class="text-2xl mr-3"></i>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                    ติดตาม GPS
                                </h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="statusMessage || 'พร้อมติดตาม'"></p>
                            </div>
                        </div>
                        <div :class="{
                                'bg-green-500': status === 'tracking',
                                'bg-blue-500': status === 'located',
                                'bg-red-500': status === 'error',
                                'bg-yellow-500': status === 'locating',
                                'bg-gray-500': status === 'idle'
                            }"
                             class="w-4 h-4 rounded-full animate-pulse"></div>
                    </div>
                </div>

                {{-- Location Info --}}
                <div class="p-4">
                    <div class="grid grid-cols-2 gap-4">
                        {{-- Coordinates --}}
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium mb-1">พิกัด</p>
                            <p class="text-sm font-mono text-gray-900 dark:text-white" x-text="formatCoords(lastLocation)"></p>
                        </div>

                        {{-- Accuracy --}}
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium mb-1">ความแม่นยำ</p>
                            <p class="text-sm text-gray-900 dark:text-white" x-text="formatAccuracy(accuracy)"></p>
                        </div>

                        {{-- Update Count --}}
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium mb-1">อัพเดท</p>
                            <p class="text-sm text-gray-900 dark:text-white">
                                <span x-text="updateCount"></span> ครั้ง
                            </p>
                        </div>

                        {{-- Last Update --}}
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase font-medium mb-1">ล่าสุด</p>
                            <p class="text-sm text-gray-900 dark:text-white"
                               x-text="lastUpdateTime ? new Date(lastUpdateTime).toLocaleTimeString('th-TH') : '-'"></p>
                        </div>
                    </div>

                    {{-- Control Buttons --}}
                    <div class="mt-4 flex items-center justify-center space-x-3">
                        {{-- Get Current Location --}}
                        <button @click="getMyLocation()"
                                type="button"
                                :disabled="status === 'locating'"
                                class="flex items-center px-4 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 disabled:opacity-50 transition">
                            <i class="fas fa-crosshairs mr-2"></i>
                            ค้นหาตำแหน่ง
                        </button>

                        {{-- Toggle Tracking --}}
                        <button @click="toggleTracking()"
                                type="button"
                                :class="isTracking ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-500 hover:bg-green-600 text-white'"
                                class="flex items-center px-6 py-2 rounded-lg transition font-medium">
                            <i :class="isTracking ? 'fas fa-stop' : 'fas fa-play'" class="mr-2"></i>
                            <span x-text="isTracking ? 'หยุดติดตาม' : 'เริ่มติดตาม'"></span>
                        </button>
                    </div>

                    {{-- Tips --}}
                    <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <p class="text-xs text-blue-700 dark:text-blue-300">
                            <i class="fas fa-lightbulb mr-1"></i>
                            <strong>เคล็ดลับ:</strong> เปิด GPS บนมือถือ และให้หน้าจอเปิดอยู่เพื่อความแม่นยำสูงสุด
                        </p>
                    </div>
                </div>

                {{-- Map Preview --}}
                @if($showMap)
                <div class="border-t border-gray-200 dark:border-gray-700">
                    <div x-show="lastLocation"
                         x-cloak
                         class="h-48 bg-gray-100 dark:bg-gray-700 relative">
                        {{-- Mini Map will be shown here --}}
                        <div id="gps-mini-map" class="w-full h-full"></div>

                        {{-- Placeholder when no location --}}
                        <div x-show="!lastLocation"
                             class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center text-gray-400 dark:text-gray-500">
                                <i class="fas fa-map-marked-alt text-4xl mb-2"></i>
                                <p class="text-sm">ยังไม่มีข้อมูลตำแหน่ง</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        @endif
    </template>
</div>

@push('scripts')
<script src="{{ asset('js/web-gps-tracker.js') }}"></script>
@endpush
