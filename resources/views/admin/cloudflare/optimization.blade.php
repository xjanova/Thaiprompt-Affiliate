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
        {{-- Optimization Status Card --}}
        <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-8 mb-8 text-white shadow-xl">
            <div class="flex flex-col lg:flex-row items-center gap-8">
                {{-- Progress Circle --}}
                <div class="relative">
                    <svg class="w-48 h-48 transform -rotate-90">
                        <circle cx="96" cy="96" r="88" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="12"/>
                        <circle cx="96" cy="96" r="88" fill="none" stroke="white" stroke-width="12"
                                stroke-linecap="round"
                                :stroke-dasharray="2 * Math.PI * 88"
                                :stroke-dashoffset="2 * Math.PI * 88 * (1 - optimizationPercentage / 100)"
                                class="transition-all duration-1000"/>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                        <span class="text-5xl font-bold" x-text="optimizationPercentage + '%'">
                            {{ $optimizationStatus['optimized_percentage'] ?? 0 }}%
                        </span>
                        <span class="text-sm opacity-80">Optimized</span>
                    </div>
                </div>

                {{-- Info --}}
                <div class="flex-1 text-center lg:text-left">
                    <h2 class="text-3xl font-bold mb-2">
                        <span x-show="optimizationPercentage === 100">
                            <i class="fas fa-check-circle mr-2"></i> Fully Optimized!
                        </span>
                        <span x-show="optimizationPercentage >= 70 && optimizationPercentage < 100">
                            <i class="fas fa-thumbs-up mr-2"></i> เกือบสมบูรณ์แล้ว
                        </span>
                        <span x-show="optimizationPercentage < 70">
                            <i class="fas fa-rocket mr-2"></i> พร้อมที่จะ Optimize!
                        </span>
                    </h2>
                    <p class="text-lg opacity-90 mb-4" x-text="optimizationMessage">
                        {{ $optimizationStatus['message'] ?? 'กำลังโหลด...' }}
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center lg:justify-start">
                        <div class="bg-white/20 rounded-lg px-4 py-2">
                            <span class="text-2xl font-bold" x-text="optimizedCount">{{ $optimizationStatus['optimized_count'] ?? 0 }}</span>
                            <span class="text-sm opacity-80 ml-1">ตั้งค่าแล้ว</span>
                        </div>
                        <div class="bg-white/20 rounded-lg px-4 py-2">
                            <span class="text-2xl font-bold" x-text="totalCount">{{ $optimizationStatus['total_count'] ?? 0 }}</span>
                            <span class="text-sm opacity-80 ml-1">ทั้งหมด</span>
                        </div>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="flex flex-col items-center gap-4">
                    <button @click="runOptimization()"
                            :disabled="isRunning"
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
                    <span class="text-sm opacity-80">
                        <i class="fas fa-clock mr-1"></i>
                        ใช้เวลาประมาณ 10-20 วินาที
                    </span>
                </div>
            </div>
        </div>

        {{-- What Will Be Optimized --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            {{-- Performance --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border-t-4 border-blue-500">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-tachometer-alt text-blue-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Performance</h3>
                </div>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Auto Minify (JS, CSS, HTML)</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Brotli Compression</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Early Hints</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>HTTP/2 & HTTP/3</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>0-RTT Connection</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Rocket Loader</li>
                </ul>
            </div>

            {{-- SEO --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border-t-4 border-green-500">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-search text-green-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">SEO & Bots</h3>
                </div>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Crawler Hints (IndexNow)</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Always Online</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Search Engine Friendly</li>
                </ul>
            </div>

            {{-- Security --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border-t-4 border-red-500">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-red-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Security</h3>
                </div>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Security Level: Medium</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Browser Integrity Check</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Email Obfuscation</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Hotlink Protection</li>
                </ul>
            </div>

            {{-- SSL & Cache --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg border-t-4 border-orange-500">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900 rounded-lg flex items-center justify-center">
                        <i class="fas fa-lock text-orange-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">SSL & Cache</h3>
                </div>
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Full SSL Mode</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Always HTTPS</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>TLS 1.2+</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Aggressive Caching</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Browser Cache TTL</li>
                </ul>
            </div>
        </div>

        {{-- Results Panel (shown after optimization) --}}
        <div x-show="showResults" x-transition class="mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-list-check mr-2 text-purple-500"></i>
                        Optimization Results
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
                            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="result.description"></span>
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
                        ข้อมูลเพิ่มเติม
                    </h3>
                    <ul class="text-blue-700 dark:text-blue-300 mt-2 space-y-1 text-sm">
                        <li><i class="fas fa-check mr-2"></i>การ Optimize จะไม่ลบการตั้งค่าที่คุณทำไว้ก่อนหน้า</li>
                        <li><i class="fas fa-check mr-2"></i>Settings บางอย่างอาจต้องการ Plan ที่สูงกว่า Free</li>
                        <li><i class="fas fa-check mr-2"></i>Cache จะถูก Purge หลังจาก Optimize เสร็จ</li>
                        <li><i class="fas fa-check mr-2"></i>คุณสามารถกดปุ่ม Optimize ซ้ำได้เมื่อต้องการ</li>
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
        isRunning: false,
        showResults: false,
        results: {},
        optimizationPercentage: {{ $optimizationStatus['optimized_percentage'] ?? 0 }},
        optimizedCount: {{ $optimizationStatus['optimized_count'] ?? 0 }},
        totalCount: {{ $optimizationStatus['total_count'] ?? 0 }},
        optimizationMessage: '{{ $optimizationStatus['message'] ?? 'กำลังโหลด...' }}',

        async runOptimization() {
            if (this.isRunning) return;

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

                if (data.success !== undefined) {
                    this.results = data;
                    this.showResults = true;

                    // Refresh optimization status
                    await this.refreshStatus();

                    // Show success notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: data.success ? 'success' : 'warning',
                            title: data.success ? 'Optimization สำเร็จ!' : 'Optimization มีบางรายการล้มเหลว',
                            text: data.message,
                            confirmButtonText: 'ตกลง'
                        });
                    } else {
                        alert(data.message);
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

        async refreshStatus() {
            try {
                const response = await fetch('{{ route('admin.cloudflare.optimization.status') }}');
                const data = await response.json();

                if (data.success) {
                    this.optimizationPercentage = data.optimized_percentage;
                    this.optimizedCount = data.optimized_count;
                    this.totalCount = data.total_count;
                    this.optimizationMessage = data.message;
                }
            } catch (error) {
                console.error('Failed to refresh status:', error);
            }
        }
    }
}
</script>
@endpush
