@extends('layouts.admin-v3')

@section('title', $pageTitle)

@push('styles')
<style>
    /* Custom Chart.js canvas container */
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    /* Glassmorphism card */
    .glass-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.18);
    }

    .dark .glass-card {
        background: rgba(17, 24, 39, 0.6);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8" x-data="lineAnalyticsDashboard()">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                📊 LINE Message Analytics
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                วิเคราะห์ข้อมูลการส่งข้อความ LINE แบบเรียลไทม์
            </p>
        </div>

        {{-- Period Selector --}}
        <div class="mt-4 md:mt-0">
            <div class="flex items-center space-x-2 bg-white dark:bg-gray-800 rounded-lg p-1 shadow-md">
                <template x-for="p in periods" :key="p.value">
                    <button
                        @click="changePeriod(p.value)"
                        :class="{
                            'bg-blue-600 text-white': period === p.value,
                            'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': period !== p.value
                        }"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                        x-text="p.label"
                    ></button>
                </template>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-600"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">กำลังโหลดข้อมูล...</p>
    </div>

    {{-- Main Content --}}
    <div x-show="!loading" class="space-y-6">
        {{-- Overview Cards (KPIs) --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            {{-- Total Messages --}}
            <div class="glass-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">ข้อความทั้งหมด</p>
                        <h3 class="text-3xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(overview.total_messages)"></h3>
                    </div>
                    <div class="bg-blue-500 bg-opacity-20 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Success Rate --}}
            <div class="glass-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Success Rate</p>
                        <h3 class="text-3xl font-bold text-green-600 dark:text-green-400" x-text="overview.success_rate + '%'"></h3>
                    </div>
                    <div class="bg-green-500 bg-opacity-20 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Avg Retry Count --}}
            <div class="glass-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Avg Retry Count</p>
                        <h3 class="text-3xl font-bold text-orange-600 dark:text-orange-400" x-text="overview.avg_retry_count"></h3>
                    </div>
                    <div class="bg-orange-500 bg-opacity-20 p-3 rounded-full">
                        <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Recovery Rate --}}
            <div class="glass-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Recovery Rate</p>
                        <h3 class="text-3xl font-bold text-purple-600 dark:text-purple-400" x-text="overview.recovery_rate + '%'"></h3>
                    </div>
                    <div class="bg-purple-500 bg-opacity-20 p-3 rounded-full">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Trending Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📈 Message Trending</h3>
                <div class="chart-container">
                    <canvas id="trendingChart"></canvas>
                </div>
            </div>

            {{-- Message Types Chart --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">📊 Message Types</h3>
                <div class="chart-container">
                    <canvas id="messageTypesChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Error Analysis & Recovery --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Error Patterns --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔍 Error Patterns</h3>
                <div class="space-y-3">
                    <template x-for="error in errorPatterns" :key="error.error_type">
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-700 dark:text-gray-300" x-text="formatErrorType(error.error_type)"></span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatNumber(error.count)"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="error.percentage + '%'"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Recovery Metrics --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">⚡ Recovery Metrics</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Total Errors</span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white" x-text="formatNumber(recoveryMetrics.total_errors)"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Recovered</span>
                        <span class="text-lg font-semibold text-green-600 dark:text-green-400" x-text="formatNumber(recoveryMetrics.recovered_errors)"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Avg Recovery Time</span>
                        <span class="text-lg font-semibold text-blue-600 dark:text-blue-400" x-text="recoveryMetrics.avg_recovery_time_minutes + ' min'"></span>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-200 dark:border-gray-700">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Recovery Rate</span>
                        <span class="text-xl font-bold text-purple-600 dark:text-purple-400" x-text="recoveryMetrics.recovery_rate + '%'"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- User Engagement --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">👥 User Engagement</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900 dark:to-blue-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Active Users</p>
                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="formatNumber(userEngagement.active_users)"></p>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900 dark:to-green-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Total Messages</p>
                    <p class="text-3xl font-bold text-green-600 dark:text-green-400" x-text="formatNumber(userEngagement.total_messages)"></p>
                </div>
                <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900 dark:to-purple-800 rounded-lg">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Msgs/User Avg</p>
                    <p class="text-3xl font-bold text-purple-600 dark:text-purple-400" x-text="userEngagement.messages_per_user"></p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-end space-x-3">
            <button
                @click="clearCache()"
                :disabled="cacheClearLoading"
                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 text-white rounded-lg transition-colors disabled:opacity-50"
            >
                <span x-show="!cacheClearLoading">🗑️ Clear Cache</span>
                <span x-show="cacheClearLoading">Loading...</span>
            </button>
            <button
                @click="refreshData()"
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg transition-colors"
            >
                🔄 Refresh Data
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function lineAnalyticsDashboard() {
    return {
        // State
        period: '{{ $period }}',
        loading: true,
        cacheClearLoading: false,

        // Period options
        periods: [
            { value: 'today', label: 'วันนี้' },
            { value: 'week', label: '7 วัน' },
            { value: 'month', label: '30 วัน' },
            { value: 'all', label: 'ทั้งหมด' }
        ],

        // Data
        overview: @json($data['overview']),
        messageTypes: @json($data['message_types']),
        errorPatterns: @json($data['error_patterns']),
        recoveryMetrics: @json($data['recovery_metrics']),
        trending: @json($data['trending']),
        userEngagement: @json($data['user_engagement']),

        // Charts
        trendingChart: null,
        messageTypesChart: null,

        // Initialize
        init() {
            this.loading = false;
            this.$nextTick(() => {
                this.initCharts();
            });
        },

        // Init charts
        initCharts() {
            this.initTrendingChart();
            this.initMessageTypesChart();
        },

        // Trending chart
        initTrendingChart() {
            const ctx = document.getElementById('trendingChart');
            if (!ctx) return;

            this.trendingChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: this.trending.map(t => t.period),
                    datasets: [
                        {
                            label: 'Succeeded',
                            data: this.trending.map(t => t.succeeded),
                            borderColor: 'rgb(34, 197, 94)',
                            backgroundColor: 'rgba(34, 197, 94, 0.1)',
                            tension: 0.4
                        },
                        {
                            label: 'Failed',
                            data: this.trending.map(t => t.failed),
                            borderColor: 'rgb(239, 68, 68)',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        // Message types chart
        initMessageTypesChart() {
            const ctx = document.getElementById('messageTypesChart');
            if (!ctx) return;

            this.messageTypesChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: this.messageTypes.map(t => t.type),
                    datasets: [{
                        data: this.messageTypes.map(t => t.count),
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(251, 146, 60, 0.8)',
                            'rgba(168, 85, 247, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                        }
                    }
                }
            });
        },

        // Change period
        changePeriod(newPeriod) {
            if (this.period === newPeriod) return;
            window.location.href = `{{ route('admin.line-analytics.dashboard') }}?period=${newPeriod}`;
        },

        // Refresh data
        refreshData() {
            window.location.reload();
        },

        // Clear cache
        async clearCache() {
            if (!confirm('ต้องการล้าง cache analytics หรือไม่?')) return;

            this.cacheClearLoading = true;

            try {
                const response = await fetch('{{ route('admin.line-analytics.clear-cache') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('ล้าง cache สำเร็จ!');
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                alert('เกิดข้อผิดพลาด: ' + error.message);
            } finally {
                this.cacheClearLoading = false;
            }
        },

        // Helpers
        formatNumber(num) {
            return num.toLocaleString();
        },

        formatErrorType(type) {
            const map = {
                'network': '🌐 Network',
                'rate_limit': '⏱️ Rate Limit',
                'timeout': '⏰ Timeout',
                'invalid_token': '🔑 Invalid Token',
                'user_not_found': '👤 User Not Found',
                'blocked': '🚫 Blocked',
                'unknown': '❓ Unknown'
            };
            return map[type] || type;
        }
    };
}
</script>
@endpush
