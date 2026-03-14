{{--
    หน้าจัดการงานจัดส่งสำหรับไรเดอร์ (Active Job)

    ฟีเจอร์หลัก:
    - บังคับเปิด GPS ตลอดเวลา (ปิดไม่ได้จนกว่าจะเปิดกลับ)
    - แผนที่ Google Maps แสดงตำแหน่งจริง + เส้นทาง
    - ปุ่มเปลี่ยนสถานะงานตามลำดับ
    - โทรหาลูกค้า/ร้านค้า
    - ส่งพิกัด GPS ไปเซิร์ฟเวอร์ทุก N วินาที

    ตัวแปรจาก Controller:
    - $job (RiderJob), $rider (Rider)
    - $customerLocation, $pickupLocation (array: latitude, longitude, address, contact_name, contact_phone)
    - $googleMapsApiKey, $gpsUpdateInterval, $gpsLostTimeout, $maxWarnings
--}}

@extends('layouts.taladsod')

@section('title', 'งานจัดส่ง #' . $job->id . ' - ตลาดสดไทยพร๊อม')

@section('meta')
    @php
        $user = auth()->user();
        $user->tokens()->where('name', 'rider-gps')->delete();
        $token = $user->createToken('rider-gps')->plainTextToken;
    @endphp
    <meta name="api-token" content="{{ $token }}">
@endsection

@section('content')
<div class="min-h-screen pb-32"
     x-data="riderActiveJob()"
     x-init="init()"
     x-cloak>

    {{-- ===== GPS Warning Overlay — เต็มจอ ปิดไม่ได้ ===== --}}
    <div x-show="gpsLost"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-[9999] bg-red-600 flex flex-col items-center justify-center p-6 text-white"
         style="display: none;">

        {{-- ไอคอนเตือน GPS --}}
        <div class="w-24 h-24 rounded-full bg-white/20 flex items-center justify-center mb-6 animate-pulse">
            <i class="fas fa-location-crosshairs text-5xl text-white"></i>
        </div>

        <h2 class="text-2xl font-bold text-center mb-3">GPS ไม่ทำงาน</h2>
        <p class="text-lg text-center text-red-100 mb-2">
            กรุณาเปิด GPS เพื่อดำเนินการจัดส่ง
        </p>
        <p class="text-sm text-center text-red-200 mb-8 max-w-sm">
            ระบบต้องการตำแหน่งของคุณเพื่อติดตามการจัดส่ง
            หากปิด GPS จะไม่สามารถทำงานต่อได้
        </p>

        {{-- แสดงจำนวนครั้งที่เตือน --}}
        <div class="bg-white/10 rounded-xl px-6 py-3 mb-6">
            <p class="text-sm text-center">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                เตือนครั้งที่ <span class="font-bold text-xl" x-text="warningCount"></span>
                / <span x-text="maxWarnings"></span>
            </p>
        </div>

        {{-- วิธีเปิด GPS --}}
        <div class="bg-white/10 rounded-xl p-4 mb-6 max-w-sm w-full">
            <h3 class="font-semibold mb-2 text-center">
                <i class="fas fa-info-circle mr-1"></i> วิธีเปิด GPS
            </h3>
            <ul class="text-sm text-red-100 space-y-1.5">
                <li class="flex items-start gap-2">
                    <span class="font-bold mt-0.5">1.</span>
                    <span>ปัดแถบสถานะลงมาจากด้านบน</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="font-bold mt-0.5">2.</span>
                    <span>กดเปิดไอคอน "ตำแหน่ง" หรือ "Location"</span>
                </li>
                <li class="flex items-start gap-2">
                    <span class="font-bold mt-0.5">3.</span>
                    <span>กลับมาที่แอปนี้ ระบบจะตรวจจับอัตโนมัติ</span>
                </li>
            </ul>
        </div>

        {{-- ปุ่มลองใหม่ --}}
        <button @click="retryGps()"
                class="w-full max-w-sm px-6 py-4 bg-white text-red-600 font-bold rounded-xl shadow-lg hover:bg-red-50 transition-colors mb-4">
            <i class="fas fa-sync-alt mr-2"></i> ลองเปิด GPS อีกครั้ง
        </button>

        {{-- ปุ่มยืนยันปิด GPS จริง (เมื่อเตือนถึงจำนวนสูงสุด) --}}
        <template x-if="warningCount >= maxWarnings">
            <button @click="confirmGpsOff()"
                    class="w-full max-w-sm px-6 py-3 bg-white/10 border border-white/30 text-white font-medium rounded-xl hover:bg-white/20 transition-colors text-sm">
                <i class="fas fa-power-off mr-2"></i> ปิด GPS จริง ๆ (หยุดงาน)
            </button>
        </template>
    </div>

    {{-- ===== แถบสถานะ GPS ด้านบน ===== --}}
    <div class="sticky top-[65px] z-40 px-4 py-2"
         :class="gpsActive ? 'bg-green-500 dark:bg-green-600' : 'bg-red-500 dark:bg-red-600'">
        <div class="max-w-3xl mx-auto flex items-center justify-between text-white text-sm">
            <div class="flex items-center gap-2">
                {{-- จุดแสดงสถานะ GPS --}}
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                          :class="gpsActive ? 'bg-green-200' : 'bg-red-200'"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3"
                          :class="gpsActive ? 'bg-white' : 'bg-red-200'"></span>
                </span>
                <span x-text="gpsActive ? 'GPS ทำงานปกติ' : 'GPS ไม่ทำงาน'"></span>
            </div>
            <div class="flex items-center gap-3">
                <span x-show="accuracy" class="text-xs opacity-80">
                    <i class="fas fa-crosshairs mr-1"></i>
                    <span x-text="accuracy ? (accuracy.toFixed(0) + ' ม.') : '-'"></span>
                </span>
                <span x-show="speed !== null && speed > 0" class="text-xs opacity-80">
                    <i class="fas fa-gauge-high mr-1"></i>
                    <span x-text="speed ? ((speed * 3.6).toFixed(0) + ' กม./ชม.') : '-'"></span>
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 py-4 space-y-4">

        {{-- ===== แผนที่ Google Maps ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden">
            {{-- พื้นที่แผนที่ --}}
            <div id="rider-map" class="w-full h-64 sm:h-80 bg-gray-200 dark:bg-gray-700 relative">
                {{-- แสดง Loading ระหว่างโหลดแผนที่ --}}
                <div x-show="!mapReady" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-700">
                    <i class="fas fa-spinner fa-spin text-3xl text-green-500 mb-3"></i>
                    <span class="text-sm text-gray-500 dark:text-gray-400">กำลังโหลดแผนที่...</span>
                </div>
            </div>

            {{-- ปุ่มนำทาง --}}
            <div class="p-3 flex items-center gap-2">
                <button @click="navigateToDestination()"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-medium transition-colors shadow-sm">
                    <i class="fas fa-diamond-turn-right"></i>
                    <span>นำทาง</span>
                </button>
                <button @click="centerOnRider()"
                        class="px-4 py-3 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-xl transition-colors"
                        title="ศูนย์กลางที่ตำแหน่งไรเดอร์">
                    <i class="fas fa-location-crosshairs"></i>
                </button>
            </div>
        </div>

        {{-- ===== การ์ดรายละเอียดงาน ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-clipboard-list text-green-500 mr-2"></i>
                    งาน #{{ $job->id }}
                </h2>
                {{-- แสดงสถานะปัจจุบัน --}}
                <span class="px-3 py-1 rounded-full text-xs font-semibold"
                      :class="statusBadgeClass">
                    <span x-text="statusLabel"></span>
                </span>
            </div>

            {{-- รายละเอียดงาน --}}
            <dl class="space-y-3 text-sm">
                @if($job->freshMarketOrder)
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">เลขออเดอร์</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">
                        #{{ $job->freshMarketOrder->order_number ?? '-' }}
                    </dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">ระยะทาง</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">
                        {{ number_format($job->distance_km ?? 0, 1) }} กม.
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">เวลาโดยประมาณ</dt>
                    <dd class="text-gray-900 dark:text-white font-medium">
                        {{ $job->estimated_duration_minutes ?? '-' }} นาที
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500 dark:text-gray-400">ค่าจัดส่ง</dt>
                    <dd class="text-orange-600 dark:text-orange-400 font-bold text-base">
                        &#3647;{{ number_format($job->total_fee ?? 0) }}
                    </dd>
                </div>
            </dl>
        </div>

        {{-- ===== การ์ดจุดรับของ (ร้านค้า) ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5"
             x-show="['accepted', 'picking_up'].includes(jobStatus)">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-store text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">จุดรับของ</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ร้านค้า / ผู้ขาย</p>
                </div>
            </div>

            <div class="space-y-2 text-sm mb-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-user text-gray-400 mt-0.5 w-4 text-center"></i>
                    <span class="text-gray-900 dark:text-white">{{ $pickupLocation['contact_name'] ?? '-' }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-map-marker-alt text-gray-400 mt-0.5 w-4 text-center"></i>
                    <span class="text-gray-600 dark:text-gray-300">{{ $pickupLocation['address'] ?? '-' }}</span>
                </div>
            </div>

            {{-- ปุ่มโทรหาร้านค้า --}}
            @if(!empty($pickupLocation['contact_phone']))
            <a href="tel:{{ $pickupLocation['contact_phone'] }}"
               class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-xl font-medium hover:bg-green-100 dark:hover:bg-green-900/50 transition-colors">
                <i class="fas fa-phone"></i>
                <span>โทรหาร้านค้า {{ $pickupLocation['contact_phone'] }}</span>
            </a>
            @endif
        </div>

        {{-- ===== การ์ดจุดส่งของ (ลูกค้า) ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5"
             x-show="['picked_up', 'delivering'].includes(jobStatus)">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/40 flex items-center justify-center text-lg flex-shrink-0">
                    <i class="fas fa-house text-orange-600 dark:text-orange-400"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">จุดส่งของ</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ลูกค้า / ผู้สั่งซื้อ</p>
                </div>
            </div>

            <div class="space-y-2 text-sm mb-4">
                <div class="flex items-start gap-2">
                    <i class="fas fa-user text-gray-400 mt-0.5 w-4 text-center"></i>
                    <span class="text-gray-900 dark:text-white">{{ $customerLocation['contact_name'] ?? '-' }}</span>
                </div>
                <div class="flex items-start gap-2">
                    <i class="fas fa-map-marker-alt text-gray-400 mt-0.5 w-4 text-center"></i>
                    <span class="text-gray-600 dark:text-gray-300">{{ $customerLocation['address'] ?? '-' }}</span>
                </div>
            </div>

            {{-- ปุ่มโทรหาลูกค้า --}}
            @if(!empty($customerLocation['contact_phone']))
            <a href="tel:{{ $customerLocation['contact_phone'] }}"
               class="flex items-center justify-center gap-2 w-full px-4 py-3 bg-orange-50 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 rounded-xl font-medium hover:bg-orange-100 dark:hover:bg-orange-900/50 transition-colors">
                <i class="fas fa-phone"></i>
                <span>โทรหาลูกค้า {{ $customerLocation['contact_phone'] }}</span>
            </a>
            @endif
        </div>

        {{-- ===== สถานะงานแบบ Timeline ===== --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-5">
            <h3 class="font-bold text-gray-900 dark:text-white mb-4">
                <i class="fas fa-route text-blue-500 mr-2"></i> ขั้นตอนการจัดส่ง
            </h3>
            <div class="space-y-0">
                @php
                    $steps = [
                        ['status' => 'accepted', 'label' => 'รับงานแล้ว', 'icon' => 'fa-check-circle'],
                        ['status' => 'picking_up', 'label' => 'ถึงร้านแล้ว', 'icon' => 'fa-store'],
                        ['status' => 'picked_up', 'label' => 'รับของแล้ว', 'icon' => 'fa-box'],
                        ['status' => 'delivering', 'label' => 'กำลังจัดส่ง', 'icon' => 'fa-motorcycle'],
                        ['status' => 'delivered', 'label' => 'ส่งแล้ว', 'icon' => 'fa-flag-checkered'],
                    ];
                    $statusOrder = ['accepted', 'picking_up', 'picked_up', 'delivering', 'delivered'];
                @endphp

                @foreach($steps as $index => $step)
                <div class="flex items-start gap-3">
                    {{-- ไอคอนวงกลมและเส้นต่อ --}}
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs transition-colors"
                             :class="stepClass('{{ $step['status'] }}')">
                            <i class="fas {{ $step['icon'] }}"></i>
                        </div>
                        @if($index < count($steps) - 1)
                        <div class="w-0.5 h-8 transition-colors"
                             :class="stepLineClass('{{ $step['status'] }}')"></div>
                        @endif
                    </div>
                    {{-- ข้อความ --}}
                    <div class="pt-1.5">
                        <span class="text-sm font-medium"
                              :class="stepTextClass('{{ $step['status'] }}')">
                            {{ $step['label'] }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ===== Fixed Bottom Action Bar ===== --}}
    <div class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-[0_-4px_12px_rgba(0,0,0,0.1)] p-4 safe-area-bottom">
        <div class="max-w-3xl mx-auto">

            {{-- สถานะ: accepted → ปุ่ม "ถึงร้านแล้ว" --}}
            <template x-if="jobStatus === 'accepted'">
                <button @click="updateJobStatus('picking_up')"
                        :disabled="updatingStatus"
                        class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-green-600 hover:bg-green-700 disabled:bg-green-400 text-white rounded-2xl font-bold text-lg transition-colors shadow-lg">
                    <template x-if="!updatingStatus">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-store"></i>
                            <span>ถึงร้านแล้ว</span>
                        </span>
                    </template>
                    <template x-if="updatingStatus">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>กำลังอัปเดต...</span>
                        </span>
                    </template>
                </button>
            </template>

            {{-- สถานะ: picking_up → ปุ่ม "รับของแล้ว" (พร้อมถ่ายรูป) --}}
            <template x-if="jobStatus === 'picking_up'">
                <div class="space-y-3">
                    {{-- ช่องถ่ายรูป --}}
                    <div class="flex items-center gap-3">
                        <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-camera"></i>
                            <span x-text="pickupPhotoName || 'ถ่ายรูปรับของ (ไม่บังคับ)'"></span>
                            <input type="file" accept="image/*" capture="environment" class="hidden"
                                   @change="pickupPhotoName = $event.target.files[0]?.name || ''; pickupPhoto = $event.target.files[0] || null">
                        </label>
                    </div>
                    <button @click="updateJobStatus('picked_up')"
                            :disabled="updatingStatus"
                            class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white rounded-2xl font-bold text-lg transition-colors shadow-lg">
                        <template x-if="!updatingStatus">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-box"></i>
                                <span>รับของแล้ว</span>
                            </span>
                        </template>
                        <template x-if="updatingStatus">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>กำลังอัปเดต...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </template>

            {{-- สถานะ: picked_up → ปุ่ม "เริ่มจัดส่ง" --}}
            <template x-if="jobStatus === 'picked_up'">
                <button @click="updateJobStatus('delivering')"
                        :disabled="updatingStatus"
                        class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-orange-500 hover:bg-orange-600 disabled:bg-orange-300 text-white rounded-2xl font-bold text-lg transition-colors shadow-lg">
                    <template x-if="!updatingStatus">
                        <span class="flex items-center gap-3">
                            <i class="fas fa-motorcycle"></i>
                            <span>เริ่มจัดส่ง</span>
                        </span>
                    </template>
                    <template x-if="updatingStatus">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            <span>กำลังอัปเดต...</span>
                        </span>
                    </template>
                </button>
            </template>

            {{-- สถานะ: delivering → ปุ่ม "ส่งแล้ว" (พร้อมถ่ายรูป) --}}
            <template x-if="jobStatus === 'delivering'">
                <div class="space-y-3">
                    {{-- ช่องถ่ายรูป --}}
                    <div class="flex items-center gap-3">
                        <label class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-xl cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <i class="fas fa-camera"></i>
                            <span x-text="deliveryPhotoName || 'ถ่ายรูปส่งของ (ไม่บังคับ)'"></span>
                            <input type="file" accept="image/*" capture="environment" class="hidden"
                                   @change="deliveryPhotoName = $event.target.files[0]?.name || ''; deliveryPhoto = $event.target.files[0] || null">
                        </label>
                    </div>
                    <button @click="updateJobStatus('delivered')"
                            :disabled="updatingStatus"
                            class="w-full flex items-center justify-center gap-3 px-6 py-4 bg-purple-600 hover:bg-purple-700 disabled:bg-purple-400 text-white rounded-2xl font-bold text-lg transition-colors shadow-lg">
                        <template x-if="!updatingStatus">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-flag-checkered"></i>
                                <span>ส่งแล้ว</span>
                            </span>
                        </template>
                        <template x-if="updatingStatus">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>กำลังอัปเดต...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </template>

            {{-- สถานะ: delivered → แสดงข้อความสำเร็จ --}}
            <template x-if="jobStatus === 'delivered'">
                <div class="text-center py-2">
                    <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400 mb-2">
                        <i class="fas fa-check-circle text-2xl"></i>
                        <span class="text-lg font-bold">จัดส่งสำเร็จ!</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                        ค่าจัดส่งจะเข้ากระเป๋าเงินของคุณเร็ว ๆ นี้
                    </p>
                    <a href="{{ route('taladsod.home') }}"
                       class="inline-flex items-center gap-2 px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-medium transition-colors">
                        <i class="fas fa-home"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                </div>
            </template>

        </div>
    </div>

    {{-- ===== Toast แจ้งเตือน ===== --}}
    <div x-show="toastMessage"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed top-24 left-1/2 -translate-x-1/2 z-[100] max-w-sm w-full mx-4"
         style="display: none;">
        <div class="rounded-xl px-4 py-3 shadow-lg flex items-center gap-3"
             :class="toastType === 'error'
                 ? 'bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800'
                 : 'bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800'">
            <i class="text-lg"
               :class="toastType === 'error'
                   ? 'fas fa-exclamation-circle text-red-500'
                   : 'fas fa-check-circle text-green-500'"></i>
            <span class="text-sm"
                  :class="toastType === 'error'
                      ? 'text-red-800 dark:text-red-200'
                      : 'text-green-800 dark:text-green-200'"
                  x-text="toastMessage"></span>
        </div>
    </div>

</div>
@endsection

@section('styles')
<style>
    /* ปรับ safe area สำหรับ iPhone ที่มี notch */
    .safe-area-bottom {
        padding-bottom: max(1rem, env(safe-area-inset-bottom, 0px));
    }
</style>
@endsection

@section('scripts')
{{-- โหลด Google Maps API --}}
<script src="https://maps.googleapis.com/maps/api/js?key={{ $googleMapsApiKey }}&libraries=geometry&callback=Function.prototype" async defer></script>

<script>
/**
 * Alpine.js Component สำหรับหน้าจัดการงานจัดส่ง
 *
 * จัดการ GPS tracking, แผนที่, และอัปเดตสถานะงาน
 */
function riderActiveJob() {
    return {
        // === ค่าคอนฟิก ===
        jobId: {{ $job->id }},
        gpsUpdateInterval: {{ $gpsUpdateInterval ?? 30 }},
        gpsLostTimeout: {{ $gpsLostTimeout ?? 120 }},
        maxWarnings: {{ $maxWarnings ?? 3 }},
        apiToken: '',
        csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',

        // === สถานะ GPS ===
        watchId: null,
        gpsActive: false,
        gpsLost: false,
        warningCount: 0,
        riderLat: null,
        riderLng: null,
        accuracy: null,
        speed: null,
        heading: null,
        lastGpsTime: null,
        gpsLostTimer: null,
        sendLocationTimer: null,

        // === สถานะงาน ===
        jobStatus: '{{ $job->status ?? "accepted" }}',
        updatingStatus: false,

        // === แผนที่ ===
        map: null,
        mapReady: false,
        riderMarker: null,
        destinationMarker: null,
        routeLine: null,

        // === รูปภาพ ===
        pickupPhoto: null,
        pickupPhotoName: '',
        deliveryPhoto: null,
        deliveryPhotoName: '',

        // === Toast ===
        toastMessage: '',
        toastType: 'success',
        toastTimer: null,

        // === ข้อมูลตำแหน่ง ===
        pickupLocation: {
            lat: {{ $pickupLocation['latitude'] ?? 0 }},
            lng: {{ $pickupLocation['longitude'] ?? 0 }}
        },
        customerLocation: {
            lat: {{ $customerLocation['latitude'] ?? 0 }},
            lng: {{ $customerLocation['longitude'] ?? 0 }}
        },

        // === ลำดับสถานะสำหรับ timeline ===
        statusOrder: ['accepted', 'picking_up', 'picked_up', 'delivering', 'delivered'],

        /**
         * เริ่มต้น component
         * ขอสิทธิ์ GPS และเริ่ม tracking
         */
        init() {
            // ดึง API token
            const tokenMeta = document.querySelector('meta[name="api-token"]');
            this.apiToken = tokenMeta ? tokenMeta.content : '';

            // รอ Google Maps โหลดเสร็จ
            this.waitForGoogleMaps();

            // เริ่ม GPS tracking
            this.startGpsTracking();
        },

        /**
         * รอจนกว่า Google Maps API จะโหลดเสร็จ
         */
        waitForGoogleMaps() {
            const checkMap = () => {
                if (typeof google !== 'undefined' && google.maps) {
                    this.initMap();
                } else {
                    setTimeout(checkMap, 500);
                }
            };
            checkMap();
        },

        /**
         * สร้าง Google Maps
         */
        initMap() {
            const mapEl = document.getElementById('rider-map');
            if (!mapEl) return;

            // กำหนดจุดศูนย์กลางเริ่มต้น (ใช้จุดรับของ)
            const center = this.riderLat
                ? { lat: this.riderLat, lng: this.riderLng }
                : { lat: this.pickupLocation.lat, lng: this.pickupLocation.lng };

            this.map = new google.maps.Map(mapEl, {
                center: center,
                zoom: 15,
                disableDefaultUI: true,
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                styles: this.getMapStyles(),
            });

            // วาง marker จุดหมาย
            this.updateDestinationMarker();
            this.mapReady = true;
        },

        /**
         * สไตล์แผนที่ให้ดูสวย
         */
        getMapStyles() {
            return [
                { featureType: 'poi', elementType: 'labels', stylers: [{ visibility: 'off' }] },
                { featureType: 'transit', stylers: [{ visibility: 'off' }] },
            ];
        },

        /**
         * อัปเดต marker จุดหมายตามสถานะ
         */
        updateDestinationMarker() {
            if (!this.map) return;

            // ลบ marker เดิม
            if (this.destinationMarker) {
                this.destinationMarker.setMap(null);
            }

            // กำหนดจุดหมายตามสถานะ
            const dest = ['accepted', 'picking_up'].includes(this.jobStatus)
                ? this.pickupLocation
                : this.customerLocation;

            if (!dest.lat || !dest.lng) return;

            const isPickup = ['accepted', 'picking_up'].includes(this.jobStatus);

            this.destinationMarker = new google.maps.Marker({
                position: { lat: dest.lat, lng: dest.lng },
                map: this.map,
                title: isPickup ? 'จุดรับของ' : 'จุดส่งของ',
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 12,
                    fillColor: isPickup ? '#22C55E' : '#F97316',
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 3,
                },
            });

            this.updateRouteLine();
        },

        /**
         * วาดเส้นทางระหว่างไรเดอร์กับจุดหมาย
         */
        updateRouteLine() {
            if (!this.map || !this.riderLat) return;

            // ลบเส้นเดิม
            if (this.routeLine) {
                this.routeLine.setMap(null);
            }

            const dest = ['accepted', 'picking_up'].includes(this.jobStatus)
                ? this.pickupLocation
                : this.customerLocation;

            if (!dest.lat || !dest.lng) return;

            this.routeLine = new google.maps.Polyline({
                path: [
                    { lat: this.riderLat, lng: this.riderLng },
                    { lat: dest.lat, lng: dest.lng },
                ],
                geodesic: true,
                strokeColor: '#3B82F6',
                strokeOpacity: 0.8,
                strokeWeight: 4,
                map: this.map,
            });
        },

        /**
         * เริ่ม GPS Tracking
         */
        startGpsTracking() {
            if (!navigator.geolocation) {
                this.showToast('เบราว์เซอร์ไม่รองรับ GPS', 'error');
                this.gpsLost = true;
                return;
            }

            // เริ่ม watchPosition
            this.watchId = navigator.geolocation.watchPosition(
                (pos) => this.onPositionUpdate(pos),
                (err) => this.onPositionError(err),
                {
                    enableHighAccuracy: true,
                    maximumAge: 10000,
                    timeout: 15000,
                }
            );

            // ตั้ง timer ตรวจสอบ GPS หาย
            this.startGpsLostDetection();

            // ตั้ง timer ส่งพิกัดไปเซิร์ฟเวอร์
            this.sendLocationTimer = setInterval(() => {
                this.sendLocationToServer();
            }, this.gpsUpdateInterval);
        },

        /**
         * ตรวจสอบ GPS หายทุก N วินาที
         */
        startGpsLostDetection() {
            this.gpsLostTimer = setInterval(() => {
                if (!this.lastGpsTime) return;
                const elapsedMs = Date.now() - this.lastGpsTime;
                if (elapsedMs > this.gpsLostTimeout && !this.gpsLost) {
                    this.gpsLost = true;
                    this.gpsActive = false;
                    this.reportGpsLost();
                }
            }, 5000);
        },

        /**
         * เมื่อได้รับตำแหน่ง GPS ใหม่
         */
        onPositionUpdate(pos) {
            this.riderLat = pos.coords.latitude;
            this.riderLng = pos.coords.longitude;
            this.accuracy = pos.coords.accuracy;
            this.speed = pos.coords.speed;
            this.heading = pos.coords.heading;
            this.lastGpsTime = Date.now();

            // อัปเดตสถานะ
            this.gpsActive = true;
            this.gpsLost = false;

            // อัปเดต marker ไรเดอร์บนแผนที่
            this.updateRiderMarker();
            this.updateRouteLine();
        },

        /**
         * เมื่อ GPS มีปัญหา
         */
        onPositionError(err) {
            console.error('GPS Error:', err.code, err.message);
            this.gpsActive = false;

            if (err.code === 1) {
                // PERMISSION_DENIED — แสดงคำเตือนเต็มจอทันที
                this.gpsLost = true;
                this.reportGpsLost();
            } else if (err.code === 2 || err.code === 3) {
                // POSITION_UNAVAILABLE หรือ TIMEOUT
                // รอสักครู่ก่อนแสดงคำเตือน (อาจเป็นแค่ชั่วคราว)
                setTimeout(() => {
                    if (!this.gpsActive) {
                        this.gpsLost = true;
                        this.reportGpsLost();
                    }
                }, 10000);
            }
        },

        /**
         * อัปเดต marker ตำแหน่งไรเดอร์บนแผนที่
         */
        updateRiderMarker() {
            if (!this.map || !this.riderLat) return;

            const position = { lat: this.riderLat, lng: this.riderLng };

            if (this.riderMarker) {
                this.riderMarker.setPosition(position);
            } else {
                this.riderMarker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    title: 'ตำแหน่งของคุณ',
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 10,
                        fillColor: '#3B82F6',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 3,
                    },
                    zIndex: 999,
                });

                // ครั้งแรก — ย้ายแผนที่มาที่ตำแหน่งไรเดอร์
                this.map.panTo(position);
            }
        },

        /**
         * ศูนย์กลางแผนที่ที่ตำแหน่งไรเดอร์
         */
        centerOnRider() {
            if (this.map && this.riderLat) {
                this.map.panTo({ lat: this.riderLat, lng: this.riderLng });
                this.map.setZoom(16);
            }
        },

        /**
         * เปิด Google Maps นำทางไปจุดหมาย
         */
        navigateToDestination() {
            const dest = ['accepted', 'picking_up'].includes(this.jobStatus)
                ? this.pickupLocation
                : this.customerLocation;

            if (!dest.lat || !dest.lng) {
                this.showToast('ไม่พบตำแหน่งจุดหมาย', 'error');
                return;
            }

            this.navigateTo(dest.lat, dest.lng);
        },

        /**
         * เปิด Google Maps ไปยังพิกัดที่กำหนด
         */
        navigateTo(lat, lng) {
            const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}&travelmode=driving`;
            window.open(url, '_blank');
        },

        /**
         * ส่งตำแหน่ง GPS ไปเซิร์ฟเวอร์
         */
        async sendLocationToServer() {
            if (!this.riderLat || !this.riderLng) return;

            try {
                const response = await fetch('/api/v1/fresh-market/rider/gps/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Authorization': this.apiToken ? `Bearer ${this.apiToken}` : '',
                    },
                    body: JSON.stringify({
                        job_id: this.jobId,
                        latitude: this.riderLat,
                        longitude: this.riderLng,
                        accuracy: this.accuracy,
                        speed: this.speed,
                        heading: this.heading,
                    }),
                });

                if (!response.ok) {
                    console.warn('ส่งพิกัดไม่สำเร็จ:', response.status);
                }
            } catch (error) {
                console.warn('ส่งพิกัดผิดพลาด:', error);
            }
        },

        /**
         * แจ้งเซิร์ฟเวอร์ว่า GPS หาย
         */
        async reportGpsLost() {
            try {
                const response = await fetch('/api/v1/fresh-market/rider/gps/lost', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Authorization': this.apiToken ? `Bearer ${this.apiToken}` : '',
                    },
                    body: JSON.stringify({
                        job_id: this.jobId,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.warning_count !== undefined) {
                        this.warningCount = data.warning_count;
                    }
                }
            } catch (error) {
                console.warn('แจ้ง GPS หายผิดพลาด:', error);
            }
        },

        /**
         * ลองเปิด GPS อีกครั้ง
         */
        retryGps() {
            // หยุด watch เดิมก่อน
            if (this.watchId !== null) {
                navigator.geolocation.clearWatch(this.watchId);
            }

            // เริ่ม watch ใหม่
            this.watchId = navigator.geolocation.watchPosition(
                (pos) => this.onPositionUpdate(pos),
                (err) => this.onPositionError(err),
                {
                    enableHighAccuracy: true,
                    maximumAge: 10000,
                    timeout: 15000,
                }
            );
        },

        /**
         * ยืนยันว่าไรเดอร์ปิด GPS จริง (หยุดงาน)
         */
        async confirmGpsOff() {
            if (!confirm('คุณต้องการหยุดงานจริง ๆ ใช่ไหม? งานจะถูกยกเลิกและอาจมีผลต่อคะแนนของคุณ')) {
                return;
            }

            try {
                const response = await fetch('/api/v1/fresh-market/rider/gps/confirm-off', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Authorization': this.apiToken ? `Bearer ${this.apiToken}` : '',
                    },
                    body: JSON.stringify({
                        job_id: this.jobId,
                        warning_count: this.warningCount,
                    }),
                });

                if (response.ok) {
                    // หยุด GPS tracking ทั้งหมด
                    this.stopTracking();
                    window.location.href = '{{ route("taladsod.home") }}';
                } else {
                    this.showToast('ไม่สามารถหยุดงานได้ กรุณาลองใหม่', 'error');
                }
            } catch (error) {
                this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            }
        },

        /**
         * หยุด GPS tracking ทั้งหมด
         */
        stopTracking() {
            if (this.watchId !== null) {
                navigator.geolocation.clearWatch(this.watchId);
                this.watchId = null;
            }
            if (this.gpsLostTimer) {
                clearInterval(this.gpsLostTimer);
                this.gpsLostTimer = null;
            }
            if (this.sendLocationTimer) {
                clearInterval(this.sendLocationTimer);
                this.sendLocationTimer = null;
            }
        },

        /**
         * อัปเดตสถานะงานผ่าน API
         */
        async updateJobStatus(newStatus) {
            if (this.updatingStatus) return;
            this.updatingStatus = true;

            try {
                // เตรียม FormData สำหรับส่งรูป
                const formData = new FormData();
                formData.append('status', newStatus);
                formData.append('job_id', this.jobId);

                if (this.riderLat) {
                    formData.append('latitude', this.riderLat);
                    formData.append('longitude', this.riderLng);
                }

                // แนบรูปถ้ามี
                if (newStatus === 'picked_up' && this.pickupPhoto) {
                    formData.append('proof_photo', this.pickupPhoto);
                }
                if (newStatus === 'delivered' && this.deliveryPhoto) {
                    formData.append('proof_photo', this.deliveryPhoto);
                }

                const response = await fetch('/api/v1/fresh-market/rider/job/update-status', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Authorization': this.apiToken ? `Bearer ${this.apiToken}` : '',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.jobStatus = newStatus;
                    this.updateDestinationMarker();

                    // แสดง toast สำเร็จ
                    const messages = {
                        'picking_up': 'ถึงร้านแล้ว!',
                        'picked_up': 'รับของสำเร็จ!',
                        'delivering': 'เริ่มจัดส่งแล้ว!',
                        'delivered': 'ส่งของสำเร็จ!',
                    };
                    this.showToast(messages[newStatus] || 'อัปเดตสำเร็จ', 'success');

                    // หยุด tracking เมื่อส่งเสร็จ
                    if (newStatus === 'delivered') {
                        this.stopTracking();
                    }
                } else {
                    this.showToast(data.message || 'ไม่สามารถอัปเดตสถานะได้', 'error');
                }
            } catch (error) {
                console.error('อัปเดตสถานะผิดพลาด:', error);
                this.showToast('เกิดข้อผิดพลาด กรุณาลองใหม่', 'error');
            } finally {
                this.updatingStatus = false;
            }
        },

        /**
         * แสดง Toast message
         */
        showToast(message, type = 'success') {
            this.toastMessage = message;
            this.toastType = type;

            if (this.toastTimer) clearTimeout(this.toastTimer);
            this.toastTimer = setTimeout(() => {
                this.toastMessage = '';
            }, 4000);
        },

        // === คลาสสำหรับ Badge สถานะ ===
        get statusBadgeClass() {
            const classes = {
                'accepted': 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400',
                'picking_up': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-400',
                'picked_up': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-400',
                'delivering': 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-400',
                'delivered': 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400',
            };
            return classes[this.jobStatus] || 'bg-gray-100 text-gray-800';
        },

        get statusLabel() {
            const labels = {
                'accepted': 'รับงานแล้ว',
                'picking_up': 'ถึงร้านแล้ว',
                'picked_up': 'รับของแล้ว',
                'delivering': 'กำลังจัดส่ง',
                'delivered': 'ส่งแล้ว',
            };
            return labels[this.jobStatus] || this.jobStatus;
        },

        // === คลาสสำหรับ Timeline ===
        stepClass(stepStatus) {
            const currentIdx = this.statusOrder.indexOf(this.jobStatus);
            const stepIdx = this.statusOrder.indexOf(stepStatus);

            if (stepIdx < currentIdx) {
                // ขั้นตอนที่ผ่านแล้ว
                return 'bg-green-500 text-white';
            } else if (stepIdx === currentIdx) {
                // ขั้นตอนปัจจุบัน
                return 'bg-blue-500 text-white ring-4 ring-blue-200 dark:ring-blue-900';
            } else {
                // ขั้นตอนที่ยังไม่ถึง
                return 'bg-gray-200 dark:bg-gray-600 text-gray-400 dark:text-gray-500';
            }
        },

        stepLineClass(stepStatus) {
            const currentIdx = this.statusOrder.indexOf(this.jobStatus);
            const stepIdx = this.statusOrder.indexOf(stepStatus);

            if (stepIdx < currentIdx) {
                return 'bg-green-500';
            } else {
                return 'bg-gray-200 dark:bg-gray-600';
            }
        },

        stepTextClass(stepStatus) {
            const currentIdx = this.statusOrder.indexOf(this.jobStatus);
            const stepIdx = this.statusOrder.indexOf(stepStatus);

            if (stepIdx < currentIdx) {
                return 'text-green-600 dark:text-green-400';
            } else if (stepIdx === currentIdx) {
                return 'text-blue-600 dark:text-blue-400 font-bold';
            } else {
                return 'text-gray-400 dark:text-gray-500';
            }
        },

        /**
         * Cleanup เมื่อ component ถูกทำลาย
         */
        destroy() {
            this.stopTracking();
        },
    };
}
</script>
@endsection
