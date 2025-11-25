@extends('layouts.user-arrow-x')

@section('title', 'เติมวันล่วงหน้า - Advance Renewal')

@section('content')
{{-- หน้าเติมวันล่วงหน้า - V3 Tailwind + Alpine.js --}}
<div class="min-h-screen p-4 md:p-6 lg:p-8"
     x-data="advanceRenewalPage()"
     x-init="init()">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl md:rounded-3xl mb-6 md:mb-8
                bg-gradient-to-br from-green-500 via-emerald-500 to-teal-500
                dark:from-green-800 dark:via-emerald-800 dark:to-teal-800
                p-6 md:p-8 lg:p-10 shadow-2xl">

        {{-- Animated Background --}}
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -right-20 w-40 h-40 md:w-60 md:h-60
                        bg-white/10 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute -bottom-20 -left-20 w-40 h-40 md:w-60 md:h-60
                        bg-white/10 rounded-full blur-3xl animate-pulse"
                 style="animation-delay: 1s;"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6">
            <div class="flex items-center gap-4">
                {{-- Icon --}}
                <div class="w-16 h-16 md:w-20 md:h-20 bg-white/20 backdrop-blur-xl
                            rounded-2xl flex items-center justify-center
                            shadow-lg border border-white/20">
                    <i class="fas fa-plus-circle text-white text-3xl md:text-4xl"></i>
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white drop-shadow-lg">
                        เติมวันล่วงหน้า
                    </h1>
                    <p class="text-white/80 text-sm md:text-base mt-1">
                        ซื้อสิทธิ์ล่วงหน้าเพื่อป้องกันการหมดอายุ พร้อมส่วนลดพิเศษ!
                    </p>
                </div>
            </div>

            {{-- Back Button --}}
            <a href="{{ route('user.retention.index') }}"
               class="inline-flex items-center gap-3 px-6 py-3 bg-white/20 backdrop-blur-xl
                      border border-white/30 rounded-xl text-white font-semibold
                      hover:bg-white/30 transition-all duration-300
                      shadow-lg hover:shadow-xl transform hover:scale-105">
                <i class="fas fa-arrow-left"></i>
                <span>กลับหน้าหลัก</span>
            </a>
        </div>
    </div>

    {{-- Pricing Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        @foreach($renewalOptions as $option)
            <div class="group relative {{ $option['months'] == 6 ? 'ring-4 ring-green-400 dark:ring-green-500 scale-105' : '' }}">
                {{-- Popular Badge --}}
                @if($option['months'] == 6)
                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                        <span class="px-4 py-1 bg-gradient-to-r from-green-500 to-emerald-600
                                     text-white text-xs font-bold rounded-full shadow-lg
                                     flex items-center gap-1">
                            <i class="fas fa-star"></i>
                            แนะนำ
                        </span>
                    </div>
                @endif

                <div class="h-full bg-white dark:bg-gray-800 rounded-2xl shadow-lg
                            hover:shadow-2xl transition-all duration-300
                            border border-gray-100 dark:border-gray-700
                            transform hover:-translate-y-2 overflow-hidden
                            {{ $option['months'] == 6 ? 'border-green-400 dark:border-green-500' : '' }}">

                    {{-- Header --}}
                    <div class="bg-gradient-to-r from-green-500 to-emerald-600
                                dark:from-green-700 dark:to-emerald-800
                                p-8 text-center">
                        <div class="text-white">
                            <div class="text-5xl font-bold mb-1">{{ $option['months'] }}</div>
                            <div class="text-lg opacity-90">เดือน</div>
                        </div>
                    </div>

                    {{-- Body --}}
                    <div class="p-6">
                        {{-- Price --}}
                        <div class="text-center mb-6">
                            <div class="text-gray-500 dark:text-gray-400 text-sm mb-2">ราคาทั้งหมด</div>
                            <div class="text-4xl font-bold text-green-600 dark:text-green-400 mb-2">
                                ฿{{ number_format($option['cost'], 0) }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400 text-sm">
                                ฿{{ number_format($option['cost_per_month'], 0) }}/เดือน
                            </div>
                        </div>

                        {{-- Benefits --}}
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>รักษาสิทธิ์การรับคอมมิชชั่น</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>ไม่ต้องกังวลเรื่องการหมดอายุ</span>
                            </div>
                            <div class="flex items-center gap-3 text-sm text-gray-600 dark:text-gray-400">
                                <i class="fas fa-check-circle text-green-500"></i>
                                <span>ส่วนลด 10% จากราคาปกติ</span>
                            </div>
                            @if($option['months'] >= 6)
                                <div class="flex items-center gap-3 p-3 bg-green-50 dark:bg-green-900/20
                                            rounded-xl border border-green-200 dark:border-green-800">
                                    <i class="fas fa-gift text-green-600 dark:text-green-400"></i>
                                    <span class="text-green-700 dark:text-green-300 font-semibold text-sm">
                                        คุ้มค่าที่สุด!
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Purchase Button --}}
                        <button @click="purchaseRenewal({{ $option['months'] }}, {{ $option['cost'] }})"
                                :disabled="loading"
                                class="w-full flex items-center justify-center gap-3 px-6 py-4
                                       bg-gradient-to-r from-green-500 to-emerald-600
                                       hover:from-green-600 hover:to-emerald-700
                                       text-white font-bold rounded-xl
                                       shadow-lg hover:shadow-xl
                                       transform hover:scale-[1.02] transition-all duration-300
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!loading">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-shopping-cart"></i>
                                    <span>ซื้อแพ็กเกจนี้</span>
                                </span>
                            </template>
                            <template x-if="loading">
                                <span class="flex items-center gap-2">
                                    <i class="fas fa-spinner fa-spin"></i>
                                    <span>กำลังดำเนินการ...</span>
                                </span>
                            </template>
                        </button>

                        {{-- Savings Badge --}}
                        @if($option['months'] >= 3)
                            <div class="mt-4 text-center p-2 bg-amber-50 dark:bg-amber-900/20
                                        rounded-lg border border-amber-200 dark:border-amber-800">
                                <span class="text-amber-700 dark:text-amber-300 text-sm font-semibold">
                                    <i class="fas fa-tag mr-1"></i>
                                    ประหยัดได้ถึง ฿{{ number_format($option['cost'] * 0.1, 0) }}!
                                </span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Info Box --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8
                border border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-4 mb-6">
            <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600
                        rounded-xl flex items-center justify-center shadow-lg">
                <i class="fas fa-info-circle text-white text-2xl"></i>
            </div>
            <h3 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">
                เกี่ยวกับการเติมวันล่วงหน้า
            </h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                        border border-gray-100 dark:border-gray-700">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg
                            flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-percentage text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">ส่วนลดพิเศษ 10%</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ราคาถูกกว่าการรักษายอดปกติ</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                        border border-gray-100 dark:border-gray-700">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg
                            flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-shield-alt text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">ป้องกันการหมดอายุ</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ไม่ต้องกังวลเรื่องการลืมรักษายอด</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                        border border-gray-100 dark:border-gray-700">
                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg
                            flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-coins text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">รักษาสิทธิ์การรับคอมมิชชั่น</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">คอมมิชชั่นจะถูกคำนวณตามปกติ</p>
                </div>
            </div>

            <div class="flex items-start gap-3 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-xl
                        border border-gray-100 dark:border-gray-700">
                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg
                            flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-sliders-h text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-1">ยืดหยุ่น</h4>
                    <p class="text-sm text-gray-600 dark:text-gray-400">เลือกแพ็กเกจที่เหมาะกับคุณได้</p>
                </div>
            </div>
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
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showSuccessModal = false"
         style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
            <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full
                        flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-check text-green-500 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                เติมวันล่วงหน้าสำเร็จ!
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="successMessage">
            </p>
            <button @click="showSuccessModal = false; window.location.href = '{{ route("user.retention.index") }}';"
                    class="px-6 py-3 bg-gradient-to-r from-green-500 to-emerald-600
                           text-white font-semibold rounded-xl
                           hover:shadow-lg transform hover:scale-105 transition-all">
                ตกลง
            </button>
        </div>
    </div>

    {{-- Error Modal --}}
    <div x-show="showErrorModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
         @click.self="showErrorModal = false"
         style="display: none;">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
            <div class="w-20 h-20 bg-red-100 dark:bg-red-900/30 rounded-full
                        flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-times text-red-500 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                เกิดข้อผิดพลาด
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="errorMessage">
            </p>
            <button @click="showErrorModal = false"
                    class="px-6 py-3 bg-gradient-to-r from-red-500 to-pink-600
                           text-white font-semibold rounded-xl
                           hover:shadow-lg transform hover:scale-105 transition-all">
                ตกลง
            </button>
        </div>
    </div>
</div>

<script>
/**
 * Alpine.js component สำหรับหน้าเติมวันล่วงหน้า
 */
function advanceRenewalPage() {
    return {
        loading: false,
        showSuccessModal: false,
        showErrorModal: false,
        successMessage: '',
        errorMessage: '',

        init() {
            console.log('Advance Renewal page initialized');
        },

        /**
         * ดำเนินการซื้อแพ็กเกจ
         */
        async purchaseRenewal(months, cost) {
            // ยืนยันก่อนดำเนินการ
            if (!confirm(`คุณต้องการซื้อแพ็กเกจ ${months} เดือน ด้วยราคา ฿${cost.toFixed(2)} ใช่หรือไม่?`)) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route("user.retention.advance-renewal.process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        months: months,
                        amount: cost
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.successMessage = result.message;
                    this.showSuccessModal = true;
                } else {
                    this.errorMessage = result.message || 'เกิดข้อผิดพลาดในการซื้อแพ็กเกจ';
                    this.showErrorModal = true;
                }
            } catch (error) {
                this.errorMessage = 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message;
                this.showErrorModal = true;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
