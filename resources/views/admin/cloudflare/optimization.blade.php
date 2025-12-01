@extends('layouts.admin')

@section('title', $pageTitle ?? 'One-Click Optimization')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="cloudflareOptimization()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <i class="fas fa-magic text-purple-500"></i>
                One-Click Optimization
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                ตั้งค่า Cloudflare ให้เหมาะสมที่สุดด้วยคลิกเดียว - Performance, SEO, Security & Caching
            </p>
        </div>
        <a href="{{ route('admin.cloudflare.index') }}"
           class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition flex items-center gap-2">
            <i class="fas fa-arrow-left"></i>
            กลับ Dashboard
        </a>
    </div>

    @if(!$isConfigured)
        {{-- Not Configured Warning --}}
        <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-700 rounded-xl p-6 mb-8">
            <div class="flex items-start gap-4">
                <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">
                        Cloudflare ยังไม่ได้ตั้งค่า
                    </h3>
                    <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                        กรุณาเพิ่ม Zone ID และ API Token ในไฟล์ .env หรือหน้า Settings
                    </p>
                    <a href="{{ route('admin.cloudflare.settings') }}"
                       class="inline-block mt-3 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition">
                        <i class="fas fa-cog mr-2"></i>
                        ไปที่ Settings
                    </a>
                </div>
            </div>
        </div>
    @else
        {{-- Step 1: Test Connection --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm">1</span>
                ทดสอบการเชื่อมต่อ API
            </h2>

            <div class="flex flex-wrap items-center gap-4">
                <button @click="testConnection()"
                        :disabled="isTesting"
                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-all disabled:opacity-50 flex items-center gap-2">
                    <template x-if="!isTesting">
                        <span><i class="fas fa-plug mr-2"></i>ทดสอบการเชื่อมต่อ</span>
                    </template>
                    <template x-if="isTesting">
                        <span><i class="fas fa-spinner fa-spin mr-2"></i>กำลังทดสอบ...</span>
                    </template>
                </button>

                {{-- Connection Status --}}
                <div x-show="connectionTested" x-transition class="flex items-center gap-3">
                    <template x-if="connectionResult.success">
                        <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                            <i class="fas fa-check-circle text-2xl"></i>
                            <div>
                                <span class="font-semibold" x-text="connectionResult.message"></span>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    <span x-text="'Zone: ' + (connectionResult.details?.zone_name || '-')"></span>
                                    <span class="mx-2">|</span>
                                    <span x-text="'Plan: ' + (connectionResult.details?.plan || 'Free')"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template x-if="!connectionResult.success">
                        <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <i class="fas fa-times-circle text-2xl"></i>
                            <div>
                                <span class="font-semibold" x-text="connectionResult.message"></span>
                                <div class="text-sm" x-show="connectionResult.details">
                                    <template x-if="connectionResult.details?.errors?.length">
                                        <span x-text="connectionResult.details.errors[0]?.message || 'Unknown error'"></span>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Step 2: Current Status --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="w-8 h-8 bg-purple-500 text-white rounded-full flex items-center justify-center text-sm">2</span>
                    สถานะปัจจุบัน
                </h2>
                <button @click="refreshStatus()"
                        :disabled="isRefreshing"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-sync-alt" :class="isRefreshing && 'fa-spin'"></i>
                    รีเฟรช
                </button>
            </div>

            {{-- Optimization Progress --}}
            <div class="mb-6 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-700 dark:text-gray-300 font-medium">ระดับ Optimization</span>
                    <span class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="optimizationPercentage + '%'">0%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-500"
                         :style="'width: ' + optimizationPercentage + '%'"></div>
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2" x-text="optimizationMessage"></p>
            </div>

            {{-- Settings Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/50">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">Setting</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">หมวด</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">ค่าปัจจุบัน</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">ค่าที่แนะนำ</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(setting, key) in statusDetails" :key="key">
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-white" x-text="setting.name"></div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400" x-text="setting.description"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300': setting.category === 'performance',
                                              'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300': setting.category === 'seo',
                                              'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300': setting.category === 'security',
                                              'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300': setting.category === 'ssl',
                                              'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300': setting.category === 'cache',
                                          }"
                                          x-text="setting.category"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-mono text-sm" x-text="setting.current_display"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-mono text-sm text-purple-600 dark:text-purple-400" x-text="setting.optimal_display"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <template x-if="setting.optimized">
                                        <span class="text-green-500"><i class="fas fa-check-circle text-lg"></i></span>
                                    </template>
                                    <template x-if="!setting.optimized">
                                        <span class="text-yellow-500"><i class="fas fa-exclamation-circle text-lg"></i></span>
                                    </template>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Step 3: Run Optimization --}}
        <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-8 mb-8 text-white shadow-xl">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                <div class="flex-1 text-center lg:text-left">
                    <h2 class="text-2xl font-bold mb-2 flex items-center gap-2 justify-center lg:justify-start">
                        <span class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center text-sm">3</span>
                        รัน Optimization
                    </h2>
                    <p class="text-lg opacity-90 mb-2">
                        กดปุ่มด้านล่างเพื่อตั้งค่าทั้งหมดให้เหมาะสมที่สุดโดยอัตโนมัติ
                    </p>
                    <ul class="text-sm opacity-80 space-y-1">
                        <li><i class="fas fa-check mr-2"></i>ตั้งค่า Performance, SEO, Security, SSL, Cache</li>
                        <li><i class="fas fa-check mr-2"></i>ล้าง Cache อัตโนมัติหลังตั้งค่าเสร็จ</li>
                        <li><i class="fas fa-clock mr-2"></i>ใช้เวลาประมาณ 10-20 วินาที</li>
                    </ul>
                </div>

                <div class="flex flex-col items-center gap-4">
                    <button @click="runOptimization()"
                            :disabled="isRunning || !connectionResult.success"
                            class="px-8 py-4 bg-white text-purple-700 hover:bg-gray-100 rounded-xl font-bold text-lg shadow-lg transform hover:scale-105 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                        <template x-if="!isRunning">
                            <span>
                                <i class="fas fa-magic mr-2"></i>
                                One-Click Optimize
                            </span>
                        </template>
                        <template x-if="isRunning">
                            <span>
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                กำลัง Optimize...
                            </span>
                        </template>
                    </button>
                    <span x-show="!connectionResult.success" class="text-sm text-yellow-200">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        กรุณาทดสอบการเชื่อมต่อก่อน
                    </span>
                </div>
            </div>
        </div>

        {{-- Results Panel (shown after optimization) --}}
        <div x-show="showResults" x-transition class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-list-check mr-2 text-purple-500"></i>
                        ผลลัพธ์ Optimization
                    </h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="results.summary?.success || 0" class="text-green-500 font-bold"></span> สำเร็จ /
                        <span x-text="results.summary?.failed || 0" class="text-red-500 font-bold"></span> ล้มเหลว
                    </span>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
                    <template x-for="(result, index) in results.results" :key="index">
                        <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <div class="flex items-center gap-3">
                                <span :class="result.success ? 'text-green-500' : 'text-red-500'">
                                    <i :class="result.success ? 'fas fa-check-circle' : 'fas fa-times-circle'"></i>
                                </span>
                                <div>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="result.name"></span>
                                    <span class="ml-2 text-xs px-2 py-0.5 rounded-full"
                                          :class="{
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': result.category === 'performance',
                                              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': result.category === 'seo',
                                              'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': result.category === 'security',
                                              'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200': result.category === 'ssl',
                                              'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200': result.category === 'cache',
                                          }"
                                          x-text="result.category"></span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-sm" :class="result.success ? 'text-gray-500 dark:text-gray-400' : 'text-red-500'" x-text="result.message"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Info Box --}}
        <div class="bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-xl p-6">
            <div class="flex items-start gap-4">
                <i class="fas fa-info-circle text-blue-500 text-2xl mt-1"></i>
                <div>
                    <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200">
                        หมายเหตุ
                    </h3>
                    <ul class="text-blue-700 dark:text-blue-300 mt-2 space-y-1 text-sm">
                        <li><i class="fas fa-check mr-2"></i>บาง settings อาจต้องการ Cloudflare Plan ที่สูงกว่า Free</li>
                        <li><i class="fas fa-check mr-2"></i>API Token ต้องมีสิทธิ์ "Zone Settings:Edit" และ "Cache Purge:Purge"</li>
                        <li><i class="fas fa-check mr-2"></i>หาก API Token ไม่มีสิทธิ์เพียงพอ รายการนั้นจะแสดง error</li>
                        <li><i class="fas fa-check mr-2"></i>คุณสามารถสร้าง API Token ใหม่ได้ที่ <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" class="underline">Cloudflare Dashboard</a></li>
                    </ul>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function cloudflareOptimization() {
    return {
        // Connection test
        isTesting: false,
        connectionTested: false,
        connectionResult: { success: false },

        // Status
        isRefreshing: false,
        optimizationPercentage: {{ $optimizationStatus['optimized_percentage'] ?? 0 }},
        optimizedCount: {{ $optimizationStatus['optimized_count'] ?? 0 }},
        totalCount: {{ $optimizationStatus['total_count'] ?? 0 }},
        optimizationMessage: '{{ $optimizationStatus['message'] ?? 'กำลังโหลด...' }}',
        statusDetails: @json($optimizationStatus['status'] ?? []),

        // Optimization
        isRunning: false,
        showResults: false,
        results: {},

        async testConnection() {
            if (this.isTesting) return;

            this.isTesting = true;
            this.connectionTested = false;

            try {
                const response = await fetch('{{ route('admin.cloudflare.test-connection') }}');
                const data = await response.json();

                this.connectionResult = data;
                this.connectionTested = true;

                // If connection successful, refresh status
                if (data.success) {
                    await this.refreshStatus();
                }
            } catch (error) {
                console.error('Test connection error:', error);
                this.connectionResult = {
                    success: false,
                    message: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message
                };
                this.connectionTested = true;
            } finally {
                this.isTesting = false;
            }
        },

        async refreshStatus() {
            if (this.isRefreshing) return;

            this.isRefreshing = true;

            try {
                const response = await fetch('{{ route('admin.cloudflare.optimization.status') }}');
                const data = await response.json();

                if (data.success) {
                    this.optimizationPercentage = data.optimized_percentage;
                    this.optimizedCount = data.optimized_count;
                    this.totalCount = data.total_count;
                    this.optimizationMessage = data.message;
                    this.statusDetails = data.status;
                } else {
                    console.error('Refresh status failed:', data.message);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สามารถโหลดสถานะได้',
                            text: data.message,
                        });
                    }
                }
            } catch (error) {
                console.error('Refresh status error:', error);
            } finally {
                this.isRefreshing = false;
            }
        },

        async runOptimization() {
            if (this.isRunning) return;

            // Confirm before running
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    icon: 'question',
                    title: 'ยืนยันการ Optimization?',
                    text: 'ระบบจะตั้งค่า Cloudflare ทั้งหมดให้เหมาะสมที่สุด',
                    showCancelButton: true,
                    confirmButtonText: 'ตกลง, เริ่มเลย!',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#7C3AED',
                });

                if (!result.isConfirmed) return;
            }

            this.isRunning = true;
            this.showResults = false;

            try {
                const response = await fetch('{{ route('admin.cloudflare.optimization.run') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.results !== undefined) {
                    this.results = data;
                    this.showResults = true;

                    // Refresh status
                    await this.refreshStatus();

                    // Show notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: data.summary?.failed === 0 ? 'success' : 'warning',
                            title: data.summary?.failed === 0 ? 'Optimization สำเร็จ!' : 'Optimization เสร็จสิ้น',
                            html: `<p>${data.message}</p>
                                   <div class="mt-3 text-left text-sm">
                                       <div class="text-green-600">✅ สำเร็จ: ${data.summary?.success || 0} รายการ</div>
                                       <div class="text-red-600">❌ ล้มเหลว: ${data.summary?.failed || 0} รายการ</div>
                                   </div>`,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#7C3AED',
                        });
                    }
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('Optimization error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: error.message || 'ไม่สามารถทำการ Optimization ได้',
                        confirmButtonText: 'ตกลง'
                    });
                } else {
                    alert('เกิดข้อผิดพลาด: ' + (error.message || 'Unknown error'));
                }
            } finally {
                this.isRunning = false;
            }
        },

        init() {
            // Auto test connection on load
            this.testConnection();
        }
    }
}
</script>
@endpush
