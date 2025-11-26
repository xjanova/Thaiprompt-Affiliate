{{--
/**
 * Auto-Renewal Settings Page
 *
 * หน้าตั้งค่าระบบรักษายอดอัตโนมัติแบบเทพ
 * ผู้ใช้สามารถปรับแต่งการตั้งค่าได้ตามความต้องการ
 *
 * @version 3.0 (V3 Theme - Tailwind + Alpine.js)
 */
--}}

@extends('layouts.user-arrow-x')

@section('title', 'ตั้งค่ารักษายอดอัตโนมัติ')

@section('content')
<div x-data="autoRenewalSettings()" class="space-y-6">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 p-6 lg:p-8 shadow-2xl">
        {{-- Background Effects --}}
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-0 left-0 w-72 h-72 bg-white/10 rounded-full filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-72 h-72 bg-white/10 rounded-full filter blur-3xl animate-pulse" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
                <i class="fas fa-robot text-[200px] text-white/5"></i>
            </div>
        </div>

        <div class="relative z-10">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <a href="{{ route('user.retention.index') }}"
                           class="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                            <i class="fas fa-arrow-left text-white"></i>
                        </a>
                        <div class="p-3 bg-white/20 backdrop-blur-sm rounded-xl">
                            <i class="fas fa-robot text-2xl text-white"></i>
                        </div>
                    </div>
                    <h1 class="text-2xl lg:text-3xl font-bold text-white mb-2">
                        ตั้งค่ารักษายอดอัตโนมัติ
                    </h1>
                    <p class="text-white/80">
                        ปรับแต่งระบบให้ดูแลสิทธิ์ของคุณโดยอัตโนมัติ
                    </p>
                </div>

                {{-- Current Status Badge --}}
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl p-4 text-center">
                    <div class="text-sm text-white/70 mb-1">สถานะปัจจุบัน</div>
                    <div class="flex items-center justify-center gap-2">
                        @if($status->isAutoRenewalEnabled())
                            <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                            <span class="text-lg font-bold text-emerald-300">เปิดใช้งาน</span>
                        @else
                            <div class="w-3 h-3 bg-gray-400 rounded-full"></div>
                            <span class="text-lg font-bold text-gray-300">ปิดอยู่</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Settings Form --}}
    <form @submit.prevent="saveSettings" class="space-y-6">

        {{-- Main Toggle Card --}}
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg">
                            <i class="fas fa-power-off text-2xl text-white"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                                เปิด/ปิด ระบบอัตโนมัติ
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                ระบบจะรักษายอดให้คุณอัตโนมัติเมื่อใกล้หมดอายุ
                            </p>
                        </div>
                    </div>

                    {{-- Toggle Switch --}}
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               x-model="settings.enabled"
                               class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:start-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-indigo-500 peer-checked:to-purple-600"></div>
                    </label>
                </div>
            </div>

            {{-- Settings Details (shown when enabled) --}}
            <div x-show="settings.enabled"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 max-h-0"
                 x-transition:enter-end="opacity-100 max-h-[2000px]"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="p-6 space-y-8 bg-gradient-to-b from-indigo-50/50 to-white dark:from-indigo-900/20 dark:to-gray-800">

                {{-- Source Selection --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-wallet text-indigo-500"></i>
                        แหล่งเงินที่ใช้หัก
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {{-- Wallet Only --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" x-model="settings.source" value="wallet" class="sr-only peer">
                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all duration-200 group-hover:border-indigo-300">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                        <i class="fas fa-wallet text-green-600 dark:text-green-400"></i>
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-white">กระเป๋าเงิน</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">หักจาก Wallet เท่านั้น</p>
                            </div>
                            <div class="absolute top-2 right-2 w-5 h-5 rounded-full border-2 border-gray-300 peer-checked:border-indigo-500 peer-checked:bg-indigo-500 flex items-center justify-center">
                                <i class="fas fa-check text-white text-xs hidden peer-checked:block"></i>
                            </div>
                        </label>

                        {{-- Commission Only --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" x-model="settings.source" value="commission" class="sr-only peer">
                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all duration-200 group-hover:border-indigo-300">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                        <i class="fas fa-coins text-purple-600 dark:text-purple-400"></i>
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-white">คอมมิชชั่น</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">หักจาก Commission เท่านั้น</p>
                            </div>
                        </label>

                        {{-- Both --}}
                        <label class="relative cursor-pointer group">
                            <input type="radio" x-model="settings.source" value="both" class="sr-only peer">
                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all duration-200 group-hover:border-indigo-300">
                                <div class="flex items-center gap-3 mb-2">
                                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                        <i class="fas fa-layer-group text-indigo-600 dark:text-indigo-400"></i>
                                    </div>
                                    <span class="font-semibold text-gray-900 dark:text-white">ทั้งสองแหล่ง</span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400">เลือกลำดับการหักได้</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Priority Selection (shown when both) --}}
                <div x-show="settings.source === 'both'" x-transition>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-sort-amount-down text-indigo-500"></i>
                        ลำดับการหักเงิน
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="relative cursor-pointer group">
                            <input type="radio" x-model="settings.priority" value="wallet_first" class="sr-only peer">
                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1">
                                        <span class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                                        <i class="fas fa-wallet text-green-500"></i>
                                        <i class="fas fa-arrow-right text-gray-400 mx-2"></i>
                                        <span class="w-8 h-8 bg-purple-500 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                                        <i class="fas fa-coins text-purple-500"></i>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-medium">หัก Wallet ก่อน ถ้าไม่พอค่อยหัก Commission</p>
                            </div>
                        </label>

                        <label class="relative cursor-pointer group">
                            <input type="radio" x-model="settings.priority" value="commission_first" class="sr-only peer">
                            <div class="p-4 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-900/30 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1">
                                        <span class="w-8 h-8 bg-purple-500 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                                        <i class="fas fa-coins text-purple-500"></i>
                                        <i class="fas fa-arrow-right text-gray-400 mx-2"></i>
                                        <span class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                                        <i class="fas fa-wallet text-green-500"></i>
                                    </div>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 mt-2 font-medium">หัก Commission ก่อน ถ้าไม่พอค่อยหัก Wallet</p>
                            </div>
                        </label>
                    </div>
                </div>

                {{-- Months Selection --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-calendar-alt text-indigo-500"></i>
                        ต่ออายุล่วงหน้ากี่เดือน
                    </h3>
                    <div class="grid grid-cols-4 md:grid-cols-6 gap-3">
                        <template x-for="month in [1, 2, 3, 6, 9, 12]" :key="month">
                            <label class="relative cursor-pointer">
                                <input type="radio" :value="month" x-model.number="settings.months" class="sr-only peer">
                                <div class="p-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 peer-checked:border-indigo-500 peer-checked:bg-indigo-500 peer-checked:text-white text-center transition-all hover:border-indigo-300">
                                    <div class="text-2xl font-bold" x-text="month"></div>
                                    <div class="text-xs opacity-80">เดือน</div>
                                </div>
                            </label>
                        </template>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        เลือก 6 เดือนขึ้นไปจะได้ส่วนลด 10%
                    </p>
                </div>

                {{-- Max Amount --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-shield-halved text-indigo-500"></i>
                        จำกัดยอดหักสูงสุด
                    </h3>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" x-model="settings.hasMaxAmount"
                                   class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="text-gray-700 dark:text-gray-300">กำหนดยอดสูงสุด</span>
                        </label>
                        <div x-show="settings.hasMaxAmount" x-transition class="flex-1 max-w-xs">
                            <div class="relative">
                                <input type="number"
                                       x-model="settings.maxAmount"
                                       min="100"
                                       step="100"
                                       class="w-full pl-12 pr-4 py-3 rounded-xl border-2 border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">฿</span>
                            </div>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        ถ้าไม่กำหนด จะหักตามจำนวนที่ต้องจ่ายจริง
                    </p>
                </div>

                {{-- Notification Days --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-bell text-indigo-500"></i>
                        แจ้งเตือนก่อนหักเงิน
                    </h3>
                    <div class="flex items-center gap-4">
                        <input type="range"
                               x-model="settings.notifyDays"
                               min="1" max="7" step="1"
                               class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                        <div class="w-24 text-center">
                            <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400" x-text="settings.notifyDays"></span>
                            <span class="text-gray-600 dark:text-gray-400 text-sm">วัน</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        ระบบจะส่งการแจ้งเตือนก่อนหักเงินอัตโนมัติ
                    </p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-xl p-6">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-clock mr-1"></i>
                อัพเดทล่าสุด: {{ $status->updated_at?->diffForHumans() ?? 'ยังไม่เคยบันทึก' }}
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('user.retention.index') }}"
                   class="px-6 py-3 rounded-xl border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all font-semibold">
                    ยกเลิก
                </a>
                <button type="submit"
                        :disabled="saving"
                        class="px-8 py-3 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-bold shadow-lg hover:shadow-xl transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                    <i class="fas fa-save" x-show="!saving"></i>
                    <i class="fas fa-spinner fa-spin" x-show="saving"></i>
                    <span x-text="saving ? 'กำลังบันทึก...' : 'บันทึกการตั้งค่า'"></span>
                </button>
            </div>
        </div>
    </form>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- How it Works --}}
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <i class="fas fa-question-circle text-blue-600 dark:text-blue-400"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">ทำงานอย่างไร?</h3>
            </div>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <span>ระบบจะตรวจสอบทุกวัน</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <span>เมื่อใกล้หมดอายุจะต่ออัตโนมัติ</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-check text-green-500 mt-0.5"></i>
                    <span>แจ้งเตือนก่อนหักเงินเสมอ</span>
                </li>
            </ul>
        </div>

        {{-- Benefits --}}
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <i class="fas fa-gift text-green-600 dark:text-green-400"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">ข้อดี</h3>
            </div>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-2">
                    <i class="fas fa-star text-yellow-500 mt-0.5"></i>
                    <span>ไม่พลาดสิทธิ์รับคอมมิชชั่น</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-star text-yellow-500 mt-0.5"></i>
                    <span>ไม่ต้องคอยดูแลเอง</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-star text-yellow-500 mt-0.5"></i>
                    <span>ประหยัดเวลา สะดวกสบาย</span>
                </li>
            </ul>
        </div>

        {{-- Safety --}}
        <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl border border-gray-200/50 dark:border-gray-700/50 shadow-xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                    <i class="fas fa-shield-alt text-amber-600 dark:text-amber-400"></i>
                </div>
                <h3 class="font-semibold text-gray-900 dark:text-white">ความปลอดภัย</h3>
            </div>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <li class="flex items-start gap-2">
                    <i class="fas fa-lock text-indigo-500 mt-0.5"></i>
                    <span>กำหนดยอดสูงสุดได้</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-lock text-indigo-500 mt-0.5"></i>
                    <span>หยุดพักชั่วคราวได้</span>
                </li>
                <li class="flex items-start gap-2">
                    <i class="fas fa-lock text-indigo-500 mt-0.5"></i>
                    <span>ปิดได้ตลอดเวลา</span>
                </li>
            </ul>
        </div>
    </div>

    {{-- Success Modal --}}
    <div x-show="showSuccessModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-md w-full p-8 text-center"
             @click.away="showSuccessModal = false">
            <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-4xl text-white"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">บันทึกสำเร็จ!</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">การตั้งค่ารักษายอดอัตโนมัติได้รับการบันทึกแล้ว</p>
            <button @click="showSuccessModal = false"
                    class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-green-500 text-white font-bold rounded-xl hover:opacity-90 transition-all">
                ตกลง
            </button>
        </div>
    </div>
</div>

<script>
function autoRenewalSettings() {
    return {
        settings: {
            enabled: {{ $status->auto_renewal_enabled ? 'true' : 'false' }},
            source: '{{ $status->auto_renewal_source ?? "wallet" }}',
            priority: '{{ $status->auto_renewal_priority ?? "wallet_first" }}',
            months: {{ $status->auto_renewal_months ?? 1 }},
            hasMaxAmount: {{ $status->auto_renewal_max_amount ? 'true' : 'false' }},
            maxAmount: {{ $status->auto_renewal_max_amount ?? 1000 }},
            notifyDays: {{ $status->auto_renewal_notify_before_days ?? 3 }},
        },
        saving: false,
        showSuccessModal: false,

        async saveSettings() {
            this.saving = true;

            try {
                const response = await fetch('{{ route("user.retention.auto-settings.save") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        enabled: this.settings.enabled,
                        source: this.settings.source,
                        priority: this.settings.priority,
                        months: this.settings.months,
                        max_amount: this.settings.hasMaxAmount ? this.settings.maxAmount : null,
                        notify_days: this.settings.notifyDays,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccessModal = true;
                } else {
                    alert(data.message || 'เกิดข้อผิดพลาด');
                }
            } catch (error) {
                console.error('Error saving settings:', error);
                alert('เกิดข้อผิดพลาดในการบันทึก');
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endsection
