@extends('layouts.user-arrow-x')

@section('title', 'ซ่อมสิทธิ์ - Repair Membership')

@section('content')
{{-- หน้าซ่อมสิทธิ์ - V3 Tailwind + Alpine.js --}}
<div class="min-h-screen p-4 md:p-6 lg:p-8"
     x-data="repairPage()"
     x-init="init()">

    {{-- Hero Header --}}
    <div class="relative overflow-hidden rounded-2xl md:rounded-3xl mb-6 md:mb-8
                bg-gradient-to-br from-amber-500 via-orange-500 to-red-500
                dark:from-amber-800 dark:via-orange-800 dark:to-red-800
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
                    <i class="fas fa-tools text-white text-3xl md:text-4xl"></i>
                </div>

                {{-- Title --}}
                <div>
                    <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white drop-shadow-lg">
                        ซ่อมสิทธิ์
                    </h1>
                    <p class="text-white/80 text-sm md:text-base mt-1">
                        ซื้อซ่อมเดือนที่ไม่ผ่านเกณฑ์เพื่อกู้สิทธิ์การรักษายอด
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

    {{-- Repair Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($repairablePeriods as $period)
            <div class="group relative overflow-hidden bg-white dark:bg-gray-800 rounded-2xl
                        shadow-lg hover:shadow-2xl transition-all duration-300
                        border border-gray-100 dark:border-gray-700">

                {{-- Header --}}
                <div class="bg-gradient-to-r from-amber-500 to-orange-600
                            dark:from-amber-700 dark:to-orange-800
                            p-5 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-white"></i>
                        </div>
                        <span class="text-white font-bold text-lg">{{ $period['period_month'] }}</span>
                    </div>
                    <span class="px-4 py-2 bg-white/20 backdrop-blur-xl rounded-lg
                                 text-white font-semibold text-sm">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        ไม่ผ่านเกณฑ์
                    </span>
                </div>

                {{-- Body --}}
                <div class="p-6">
                    {{-- Points Info --}}
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-900/50
                                    rounded-xl border border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 text-sm">แต้มที่ต้องการ:</span>
                            <span class="text-gray-900 dark:text-white font-bold">
                                {{ number_format($period['required_points'], 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-900/50
                                    rounded-xl border border-gray-100 dark:border-gray-700">
                            <span class="text-gray-600 dark:text-gray-400 text-sm">แต้มที่ได้:</span>
                            <span class="text-gray-900 dark:text-white font-bold">
                                {{ number_format($period['earned_points'], 0) }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-amber-50 dark:bg-amber-900/20
                                    rounded-xl border border-amber-200 dark:border-amber-800">
                            <span class="text-amber-800 dark:text-amber-300 font-medium">แต้มที่ต้องซ่อม:</span>
                            <span class="text-amber-900 dark:text-amber-200 font-bold text-lg">
                                {{ number_format($period['points_needed'], 0) }}
                            </span>
                        </div>
                    </div>

                    {{-- Cost Section --}}
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/30 dark:to-orange-900/30
                                rounded-xl p-6 text-center mb-6 border border-amber-200 dark:border-amber-800">
                        <div class="text-amber-800 dark:text-amber-300 text-sm mb-2">ค่าใช้จ่ายในการซ่อม</div>
                        <div class="text-amber-900 dark:text-amber-200 text-4xl font-bold">
                            ฿{{ number_format($period['repair_cost'], 2) }}
                        </div>
                    </div>

                    {{-- Repair Button --}}
                    <button @click="repairPeriod('{{ $period['period_month'] }}', {{ $period['repair_cost'] }})"
                            :disabled="loading"
                            class="w-full flex items-center justify-center gap-3 px-6 py-4
                                   bg-gradient-to-r from-amber-500 to-orange-600
                                   hover:from-amber-600 hover:to-orange-700
                                   text-white font-bold rounded-xl
                                   shadow-lg hover:shadow-xl
                                   transform hover:scale-[1.02] transition-all duration-300
                                   disabled:opacity-50 disabled:cursor-not-allowed">
                        <template x-if="!loading">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-shopping-cart"></i>
                                <span>ซื้อซ่อมเดือนนี้</span>
                            </span>
                        </template>
                        <template x-if="loading">
                            <span class="flex items-center gap-2">
                                <i class="fas fa-spinner fa-spin"></i>
                                <span>กำลังดำเนินการ...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        @empty
            {{-- Empty State --}}
            <div class="col-span-full">
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-12 text-center
                            border border-gray-100 dark:border-gray-700">
                    <div class="w-24 h-24 bg-green-100 dark:bg-green-900/30 rounded-full
                                flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check-circle text-green-500 text-5xl"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                        ไม่มีเดือนที่ต้องซ่อม
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-8">
                        คุณผ่านเกณฑ์การรักษายอดทุกเดือน ยอดเยี่ยม!
                    </p>
                    <a href="{{ route('user.retention.index') }}"
                       class="inline-flex items-center gap-2 px-6 py-3
                              bg-gradient-to-r from-green-500 to-emerald-600
                              text-white font-semibold rounded-xl
                              shadow-lg hover:shadow-xl
                              transform hover:scale-105 transition-all">
                        <i class="fas fa-arrow-left"></i>
                        <span>กลับหน้าหลัก</span>
                    </a>
                </div>
            </div>
        @endforelse
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
                ซ่อมสิทธิ์สำเร็จ!
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6" x-text="successMessage">
            </p>
            <button @click="showSuccessModal = false; window.location.reload();"
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
 * Alpine.js component สำหรับหน้าซ่อมสิทธิ์
 */
function repairPage() {
    return {
        loading: false,
        showSuccessModal: false,
        showErrorModal: false,
        successMessage: '',
        errorMessage: '',

        init() {
            console.log('Repair page initialized');
        },

        /**
         * ดำเนินการซ่อมสิทธิ์
         */
        async repairPeriod(periodMonth, cost) {
            // ยืนยันก่อนดำเนินการ
            if (!confirm(`คุณต้องการซ่อมสิทธิ์เดือน ${periodMonth} ด้วยค่าใช้จ่าย ฿${cost.toFixed(2)} ใช่หรือไม่?`)) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route("user.retention.repair.process") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        period_month: periodMonth,
                        amount: cost
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.successMessage = result.message;
                    this.showSuccessModal = true;
                } else {
                    this.errorMessage = result.message || 'เกิดข้อผิดพลาดในการซ่อมสิทธิ์';
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
