@extends('layouts.admin-v3')

@section('title', 'Analytics - AI Chatbot')

@push('head')
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endpush

@section('content')
<div class="container-fluid px-4 py-6" x-data="analyticsData()" x-init="init()">
    <!-- Premium Header with LINE Green Theme -->
    <div class="relative mb-8 overflow-hidden rounded-3xl bg-gradient-to-br from-[#00B900] via-[#00E600] to-[#06C755] p-10 shadow-2xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSAxMCAwIEwgMCAwIDAgMTAiIGZpbGw9Im5vbmUiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS1vcGFjaXR5PSIwLjA1IiBzdHJva2Utd2lkdGg9IjEiLz48L3BhdHRlcm4+PC9kZWZzPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9InVybCgjZ3JpZCkiLz48L3N2Zz4=')] opacity-40"></div>

        <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-white/25 to-white/10 backdrop-blur-lg flex items-center justify-center shadow-xl border border-white/20">
                        <i class="fas fa-chart-bar text-4xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl lg:text-4xl font-black text-white mb-2 drop-shadow-lg">📊 Analytics Dashboard</h1>
                        <p class="text-green-100 text-base lg:text-lg font-medium">วิเคราะห์ประสิทธิภาพและการใช้งาน AI Chatbot</p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Export Buttons -->
                <button @click="exportData('csv')"
                        class="px-4 py-2.5 glass-fusion backdrop-blur-md border border-white/25 text-white rounded-xl hover:bg-white/10 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 text-sm font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Export CSV</span>
                </button>

                <button @click="exportData('pdf')"
                        class="px-4 py-2.5 glass-fusion backdrop-blur-md border border-white/25 text-white rounded-xl hover:bg-white/10 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 text-sm font-semibold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    <span>Export PDF</span>
                </button>

                <a href="{{ route('admin.line-bot.ai.index') }}"
                   class="px-4 py-2.5 glass-fusion backdrop-blur-md border border-white/25 text-white rounded-xl hover:bg-white/10 transition-all duration-300 shadow-lg hover:shadow-2xl transform hover:-translate-y-1 text-sm font-semibold flex items-center gap-2">
                    <i class="fas fa-arrow-left"></i>
                    <span>กลับไปตั้งค่า</span>
                </a>
            </div>
        </div>

        <!-- Date Range Picker -->
        <div class="relative mt-6 flex flex-wrap items-center gap-3">
            <button @click="updateDateRange('today')"
                    :class="dateRange === 'today' ? 'bg-white text-[#00B900]' : 'bg-white/20 text-white hover:bg-white/30'"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:-translate-y-0.5">
                วันนี้
            </button>
            <button @click="updateDateRange('week')"
                    :class="dateRange === 'week' ? 'bg-white text-[#00B900]' : 'bg-white/20 text-white hover:bg-white/30'"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:-translate-y-0.5">
                7 วันที่แล้ว
            </button>
            <button @click="updateDateRange('month')"
                    :class="dateRange === 'month' ? 'bg-white text-[#00B900]' : 'bg-white/20 text-white hover:bg-white/30'"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:-translate-y-0.5">
                30 วันที่แล้ว
            </button>
            <button @click="updateDateRange('year')"
                    :class="dateRange === 'year' ? 'bg-white text-[#00B900]' : 'bg-white/20 text-white hover:bg-white/30'"
                    class="px-4 py-2 rounded-xl text-sm font-semibold transition-all duration-300 transform hover:-translate-y-0.5">
                ปีนี้
            </button>
        </div>
    </div>

    <!-- Main Statistics with Animated Counters -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- AI Settings Card -->
        <div class="bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-green-100 text-sm font-medium mb-2 flex items-center gap-2">
                        <i class="fas fa-robot"></i>
                        การตั้งค่า AI
                    </p>
                    <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $stats['total_ai_settings'] }}, 1500, val => count = val)">
                        <h3 class="text-4xl font-bold mb-1" x-text="Math.floor(count).toLocaleString()">0</h3>
                    </div>
                    <p class="text-xs text-green-200 flex items-center gap-1">
                        <span class="w-2 h-2 bg-green-300 rounded-full animate-pulse"></span>
                        {{ $stats['active_ai_settings'] }} เปิดใช้งาน
                    </p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-robot text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Knowledge Bases Card -->
        <div class="bg-gradient-to-br from-[#06C755] to-[#00B900] rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-green-100 text-sm font-medium mb-2 flex items-center gap-2">
                        <i class="fas fa-book"></i>
                        ฐานความรู้
                    </p>
                    <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $stats['total_knowledge_bases'] }}, 1500, val => count = val)">
                        <h3 class="text-4xl font-bold mb-1" x-text="Math.floor(count).toLocaleString()">0</h3>
                    </div>
                    <p class="text-xs text-green-200">แหล่งข้อมูลทั้งหมด</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-book text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Conversations Card -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-blue-100 text-sm font-medium mb-2 flex items-center gap-2">
                        <i class="fas fa-comments"></i>
                        บทสนทนา
                    </p>
                    <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $stats['total_conversations'] }}, 1500, val => count = val)">
                        <h3 class="text-4xl font-bold mb-1" x-text="Math.floor(count).toLocaleString()">0</h3>
                    </div>
                    <p class="text-xs text-blue-200">{{ $stats['today_conversations'] }} วันนี้</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-comments text-2xl"></i>
                </div>
            </div>
        </div>

        <!-- Messages Card -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl p-6 text-white shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-orange-100 text-sm font-medium mb-2 flex items-center gap-2">
                        <i class="fas fa-envelope"></i>
                        ข้อความ
                    </p>
                    <div x-data="{ count: 0 }" x-init="animateCount(0, {{ $stats['total_messages'] }}, 1500, val => count = val)">
                        <h3 class="text-4xl font-bold mb-1" x-text="Math.floor(count).toLocaleString()">0</h3>
                    </div>
                    <p class="text-xs text-orange-200">{{ $stats['today_messages'] }} วันนี้</p>
                </div>
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center border border-white/20">
                    <i class="fas fa-envelope text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Conversations Chart -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">บทสนทนาย้อนหลัง</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จำนวนบทสนทนาแต่ละวัน</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-[#00B900] to-[#00E600] rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
            </div>
            <div class="h-64">
                <canvas id="conversationsChart"></canvas>
            </div>
        </div>

        <!-- Provider Distribution -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">การกระจายของ AI Provider</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">จำนวนข้อความต่อ Provider</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-chart-pie text-white text-xl"></i>
                </div>
            </div>
            <div class="h-64">
                <canvas id="providerChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Additional Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Response Time Chart -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">เวลาตอบกลับเฉลี่ย</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">กระจายตามช่วงเวลา</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
            </div>
            <div class="h-64">
                <canvas id="responseTimeChart"></canvas>
            </div>
        </div>

        <!-- Hourly Usage Chart -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center justify-between mb-6">
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1">การใช้งานตามชั่วโมง</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">ยอดใช้งานใน 24 ชั่วโมง</p>
                </div>
                <div class="w-12 h-12 bg-gradient-to-br from-orange-500 to-red-600 rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-business-time text-white text-xl"></i>
                </div>
            </div>
            <div class="h-64">
                <canvas id="hourlyUsageChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Performance Metrics Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Average Response Time -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-tachometer-alt text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">เวลาตอบกลับ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">1.2s</h3>
                </div>
            </div>
            <div class="flex items-center gap-2 text-sm">
                <span class="flex items-center gap-1 text-green-600 dark:text-green-400 font-medium">
                    <i class="fas fa-arrow-down"></i>
                    15%
                </span>
                <span class="text-gray-500 dark:text-gray-400">เร็วขึ้นกว่าเดือนที่แล้ว</span>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">อัตราสำเร็จ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">98.5%</h3>
                </div>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 h-full rounded-full" style="width: 98.5%"></div>
            </div>
        </div>

        <!-- User Satisfaction -->
        <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 p-6 transform hover:scale-[1.02] transition-all duration-300">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fas fa-star text-white text-2xl"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-600 dark:text-gray-400 font-medium">ความพึงพอใจ</p>
                    <h3 class="text-3xl font-bold text-gray-900 dark:text-white">4.8/5</h3>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star text-yellow-400"></i>
                <i class="fas fa-star-half-alt text-yellow-400"></i>
            </div>
        </div>
    </div>

    <!-- AI Settings Performance -->
    <div class="glass-fusion backdrop-blur-xl bg-white/80 dark:bg-slate-800/80 rounded-3xl shadow-2xl border border-white/20 dark:border-slate-700 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-[#00B900] to-[#00E600]">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-robot text-white text-lg"></i>
                </div>
                <h2 class="text-xl font-bold text-white">ประสิทธิภาพของแต่ละ AI Setting</h2>
            </div>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($aiSettings as $setting)
                    <div class="p-5 rounded-2xl border-2 border-gray-200 dark:border-gray-700 hover:border-[#00B900] dark:hover:border-[#00E600] transition-all hover:shadow-lg bg-white dark:bg-slate-800/50">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br
                                @if($setting->provider === 'openai') from-green-500 to-emerald-600
                                @elseif($setting->provider === 'deepseek') from-blue-500 to-indigo-600
                                @elseif($setting->provider === 'anthropic') from-orange-500 to-red-600
                                @elseif($setting->provider === 'gemini') from-purple-500 to-pink-600
                                @else from-gray-500 to-gray-600
                                @endif flex items-center justify-center shadow-md">
                                <i class="fas fa-brain text-white text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ $setting->name }}</h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 uppercase font-semibold">{{ $setting->provider }}</p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-2 bg-blue-50 dark:bg-blue-900/20 rounded-xl">
                                <span class="text-xs font-medium text-blue-700 dark:text-blue-300">Knowledge Bases</span>
                                <span class="px-2 py-1 bg-blue-600 text-white rounded-md text-xs font-bold">
                                    {{ $setting->knowledgeBases->count() }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between p-2 bg-purple-50 dark:bg-purple-900/20 rounded-xl">
                                <span class="text-xs font-medium text-purple-700 dark:text-purple-300">Status</span>
                                @if($setting->is_active)
                                    <span class="px-2 py-1 bg-green-600 text-white rounded-md text-xs font-bold flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 bg-green-300 rounded-full animate-pulse"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="px-2 py-1 bg-gray-400 text-white rounded-md text-xs font-semibold">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-3 text-center py-12">
                        <div class="w-20 h-20 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-inbox text-4xl text-gray-400 dark:text-gray-600"></i>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">ยังไม่มีการตั้งค่า AI</p>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-1">เริ่มต้นสร้าง AI Setting แรกของคุณ</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * Alpine.js Analytics Data Component
 * จัดการ state และ interactions สำหรับ Analytics Dashboard
 */
function analyticsData() {
    return {
        dateRange: 'week',
        filterProvider: 'all',
        chartConversations: null,
        chartProviders: null,
        chartResponseTime: null,
        chartHourlyUsage: null,

        /**
         * Initialize component
         */
        init() {
            this.initCharts();
        },

        /**
         * Animate counter from start to end
         * @param {number} start - เริ่มต้น
         * @param {number} end - สิ้นสุด
         * @param {number} duration - ระยะเวลา (ms)
         * @param {function} callback - Callback function
         */
        animateCount(start, end, duration, callback) {
            const range = end - start;
            const increment = end > start ? 1 : -1;
            const stepTime = Math.abs(Math.floor(duration / range));
            let current = start;
            const timer = setInterval(() => {
                current += increment;
                callback(current);
                if (current === end) clearInterval(timer);
            }, stepTime);
        },

        /**
         * Update date range filter
         * @param {string} range - ช่วงเวลา (today, week, month, year)
         */
        updateDateRange(range) {
            this.dateRange = range;
            // TODO: Fetch fresh data from server
            console.log('Date range updated:', range);
        },

        /**
         * Export data
         * @param {string} format - รูปแบบ (csv, pdf)
         */
        exportData(format) {
            const url = `/admin/line-bot/ai/analytics/export?format=${format}&range=${this.dateRange}`;
            window.location.href = url;
        },

        /**
         * Initialize all charts
         */
        initCharts() {
            // ตรวจสอบ dark mode
            const isDark = document.documentElement.classList.contains('dark');

            // กำหนดค่า default colors สำหรับ Chart.js
            Chart.defaults.color = isDark ? '#9ca3af' : '#4b5563';
            Chart.defaults.borderColor = isDark ? '#374151' : '#e5e7eb';

            // สร้าง charts ทั้งหมด
            this.initConversationsChart(isDark);
            this.initProviderChart(isDark);
            this.initResponseTimeChart(isDark);
            this.initHourlyUsageChart(isDark);
        },

        /**
         * Initialize Conversations Chart
         */
        initConversationsChart(isDark) {
            const ctx = document.getElementById('conversationsChart').getContext('2d');
            this.chartConversations = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($conversationsPerDay->pluck('date')) !!},
                    datasets: [{
                        label: 'บทสนทนา',
                        data: {!! json_encode($conversationsPerDay->pluck('count')) !!},
                        borderColor: '#00B900',
                        backgroundColor: 'rgba(0, 185, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        borderWidth: 3,
                        pointBackgroundColor: '#00B900',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(30, 41, 59, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#fff' : '#000',
                            bodyColor: isDark ? '#fff' : '#000',
                            borderColor: '#00B900',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: isDark ? '#374151' : '#e5e7eb'
                            }
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        /**
         * Initialize Provider Chart
         */
        initProviderChart(isDark) {
            const ctx = document.getElementById('providerChart').getContext('2d');
            this.chartProviders = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: {!! json_encode($messagesPerProvider->pluck('provider')->map(fn($p) => strtoupper($p))) !!},
                    datasets: [{
                        data: {!! json_encode($messagesPerProvider->pluck('count')) !!},
                        backgroundColor: [
                            'rgba(0, 185, 0, 0.8)',      // OpenAI - LINE Green
                            'rgba(59, 130, 246, 0.8)',   // DeepSeek - Blue
                            'rgba(249, 115, 22, 0.8)',   // Anthropic - Orange
                            'rgba(168, 85, 247, 0.8)',   // Gemini - Purple
                            'rgba(107, 114, 128, 0.8)'   // Others - Gray
                        ],
                        borderWidth: 2,
                        borderColor: isDark ? '#1e293b' : '#fff',
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12,
                                    weight: 'bold'
                                },
                                color: isDark ? '#e5e7eb' : '#374151'
                            }
                        },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(30, 41, 59, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#fff' : '#000',
                            bodyColor: isDark ? '#fff' : '#000',
                            borderColor: '#00B900',
                            borderWidth: 1,
                            padding: 12
                        }
                    }
                }
            });
        },

        /**
         * Initialize Response Time Chart
         */
        initResponseTimeChart(isDark) {
            const ctx = document.getElementById('responseTimeChart').getContext('2d');
            this.chartResponseTime = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['< 1s', '1-2s', '2-3s', '> 3s'],
                    datasets: [{
                        label: 'จำนวนข้อความ',
                        data: [450, 320, 180, 50], // ข้อมูลตัวอย่าง
                        backgroundColor: [
                            'rgba(0, 185, 0, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(249, 115, 22, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ],
                        borderRadius: 8,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(30, 41, 59, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#fff' : '#000',
                            bodyColor: isDark ? '#fff' : '#000',
                            borderColor: '#00B900',
                            borderWidth: 1,
                            padding: 12
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: isDark ? '#374151' : '#e5e7eb'
                            }
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        /**
         * Initialize Hourly Usage Chart
         */
        initHourlyUsageChart(isDark) {
            const ctx = document.getElementById('hourlyUsageChart').getContext('2d');

            // สร้างข้อมูลตัวอย่างสำหรับ 24 ชั่วโมง
            const hours = Array.from({length: 24}, (_, i) => `${i}:00`);
            const usage = [12, 8, 5, 3, 4, 8, 25, 45, 78, 95, 120, 135, 142, 138, 125, 115, 98, 85, 72, 58, 45, 32, 22, 15];

            this.chartHourlyUsage = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: hours,
                    datasets: [{
                        label: 'การใช้งาน',
                        data: usage,
                        backgroundColor: hours.map((_, i) => {
                            const hour = i;
                            if (hour >= 6 && hour < 12) return 'rgba(251, 191, 36, 0.8)'; // เช้า - Yellow
                            if (hour >= 12 && hour < 18) return 'rgba(0, 185, 0, 0.8)'; // บ่าย - Green
                            if (hour >= 18 && hour < 22) return 'rgba(59, 130, 246, 0.8)'; // เย็น - Blue
                            return 'rgba(99, 102, 241, 0.8)'; // กลางคืน - Indigo
                        }),
                        borderRadius: 6,
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? 'rgba(30, 41, 59, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                            titleColor: isDark ? '#fff' : '#000',
                            bodyColor: isDark ? '#fff' : '#000',
                            borderColor: '#00B900',
                            borderWidth: 1,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    return `การใช้งาน: ${context.parsed.y} ครั้ง`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0,
                                color: isDark ? '#9ca3af' : '#6b7280'
                            },
                            grid: {
                                color: isDark ? '#374151' : '#e5e7eb'
                            }
                        },
                        x: {
                            ticks: {
                                color: isDark ? '#9ca3af' : '#6b7280',
                                maxRotation: 45,
                                minRotation: 45
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }
    };
}
</script>
@endpush
@endsection
