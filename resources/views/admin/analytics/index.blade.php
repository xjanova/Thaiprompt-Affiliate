@extends('layouts.admin')

@section('title', 'การวิเคราะห์ระบบ - System Analytics')

@section('content')
<div class="space-y-6" x-data="analyticsManager()">
    <!-- Header with System Health Status -->
    <div class="bg-gradient-to-r from-cyan-600 via-blue-600 to-indigo-600 rounded-2xl shadow-2xl p-8 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-40 h-40 bg-white opacity-10 rounded-full"></div>
        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white opacity-10 rounded-full"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold mb-2">📊 การวิเคราะห์ระบบ</h1>
                    <p class="text-cyan-100">ติดตามประสิทธิภาพและสถานะของระบบแบบเรียลไทม์</p>
                </div>
                <div class="text-right">
                    @php
                        $health = $summary['health'] ?? ['status' => 'unknown', 'color' => 'gray', 'message' => 'Unknown'];
                        $statusEmoji = match($health['status']) {
                            'healthy' => '🟢',
                            'warning' => '🟡',
                            'critical' => '🔴',
                            default => '⚪'
                        };
                    @endphp
                    <div class="text-5xl mb-2">{{ $statusEmoji }}</div>
                    <p class="text-sm font-semibold uppercase tracking-wide">{{ $health['message'] }}</p>
                    <p class="text-xs text-cyan-100 mt-1">อัปเดตล่าสุด: <span x-text="lastUpdate">กำลังโหลด...</span></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Real-time Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Active Users -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-4xl mb-3">👥</div>
                <p class="text-white text-opacity-80 text-sm mb-1">ผู้ใช้งาน Active</p>
                <p class="text-4xl font-bold" x-text="metrics.active_users ?? '{{ $summary['current']->active_users ?? 0 }}'">
                    {{ $summary['current']->active_users ?? 0 }}
                </p>
                <p class="text-xs text-white text-opacity-70 mt-2">Peak 24h: {{ $summary['peak_users_24h'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Request Rate -->
        <div class="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-4xl mb-3">⚡</div>
                <p class="text-white text-opacity-80 text-sm mb-1">Requests/sec</p>
                <p class="text-4xl font-bold" x-text="(metrics.request_rate ?? '{{ $summary['current']->request_rate ?? 0 }}').toFixed(1)">
                    {{ number_format($summary['current']->request_rate ?? 0, 1) }}
                </p>
                <p class="text-xs text-white text-opacity-70 mt-2">Peak 24h: {{ number_format($summary['peak_requests_24h'] ?? 0, 1) }}</p>
            </div>
        </div>

        <!-- Response Time -->
        <div class="relative overflow-hidden bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-4xl mb-3">⏱️</div>
                <p class="text-white text-opacity-80 text-sm mb-1">Avg Response Time</p>
                <p class="text-4xl font-bold" x-text="(metrics.avg_response_time ?? '{{ $summary['current']->avg_response_time ?? 0 }}') + ' ms'">
                    {{ number_format($summary['current']->avg_response_time ?? 0, 0) }} ms
                </p>
                <p class="text-xs text-white text-opacity-70 mt-2">
                    P95: {{ number_format($summary['current']->p95_response_time ?? 0, 0) }} ms
                </p>
            </div>
        </div>

        <!-- CPU Usage -->
        <div class="relative overflow-hidden bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl shadow-xl p-6 text-white">
            <div class="absolute top-0 right-0 -mt-4 -mr-4">
                <div class="w-24 h-24 bg-white opacity-10 rounded-full"></div>
            </div>
            <div class="relative z-10">
                <div class="text-4xl mb-3">🖥️</div>
                <p class="text-white text-opacity-80 text-sm mb-1">CPU Usage</p>
                <p class="text-4xl font-bold" x-text="(metrics.cpu_usage ?? '{{ $summary['current']->cpu_usage ?? 0 }}') + '%'">
                    {{ number_format($summary['current']->cpu_usage ?? 0, 1) }}%
                </p>
                <div class="w-full bg-white bg-opacity-20 rounded-full h-2 mt-3">
                    <div class="bg-white rounded-full h-2 transition-all duration-500"
                         :style="`width: ${metrics.cpu_usage ?? '{{ $summary['current']->cpu_usage ?? 0 }}'}%`"
                         style="width: {{ $summary['current']->cpu_usage ?? 0 }}%">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Metrics Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Memory Usage -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">💾</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">MEMORY</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="(metrics.memory_usage ?? '{{ $summary['current']->memory_usage ?? 0 }}') + '%'">
                {{ number_format($summary['current']->memory_usage ?? 0, 1) }}%
            </p>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                <div class="bg-blue-500 rounded-full h-1.5 transition-all duration-500"
                     :style="`width: ${metrics.memory_usage ?? '{{ $summary['current']->memory_usage ?? 0 }}'}%`"
                     style="width: {{ $summary['current']->memory_usage ?? 0 }}%">
                </div>
            </div>
        </div>

        <!-- Cache Hit Rate -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">📦</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">CACHE</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="(metrics.cache_hit_rate ?? '{{ $summary['current']->cache_hit_rate ?? 0 }}') + '%'">
                {{ number_format($summary['current']->cache_hit_rate ?? 0, 1) }}%
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Hit Rate</p>
        </div>

        <!-- Database Connections -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">🗄️</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">DATABASE</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white">
                <span x-text="metrics.db_connections_active ?? '{{ $summary['current']->db_connections_active ?? 0 }}'">{{ $summary['current']->db_connections_active ?? 0 }}</span>
                <span class="text-sm text-gray-500">/ {{ $summary['current']->db_connections_total ?? 151 }}</span>
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Active Connections</p>
        </div>

        <!-- Error Rate -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">⚠️</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">ERRORS</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="(metrics.error_rate ?? '{{ $summary['current']->error_rate ?? 0 }}') + '%'">
                {{ number_format($summary['current']->error_rate ?? 0, 2) }}%
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Error Rate</p>
        </div>

        <!-- Security Events -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-5">
            <div class="flex items-center justify-between mb-2">
                <span class="text-2xl">🔒</span>
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">SECURITY</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="metrics.blocked_ips ?? '{{ $summary['current']->blocked_ips ?? 0 }}'">
                {{ $summary['current']->blocked_ips ?? 0 }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Blocked IPs</p>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Traffic Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">📈 Traffic & Users (24h)</h3>
                <select x-model="trafficPeriod" @change="loadTrafficData()" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1 dark:bg-slate-700 dark:text-white">
                    <option value="1">1 Hour</option>
                    <option value="24" selected>24 Hours</option>
                    <option value="168">7 Days</option>
                </select>
            </div>
            <div class="relative h-64">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <!-- System Performance Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">🖥️ System Performance (24h)</h3>
                <select x-model="perfPeriod" @change="loadPerformanceData()" class="text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-1 dark:bg-slate-700 dark:text-white">
                    <option value="1">1 Hour</option>
                    <option value="24" selected>24 Hours</option>
                    <option value="168">7 Days</option>
                </select>
            </div>
            <div class="relative h-64">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Response Time Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">⏱️ Response Time Distribution</h3>
            </div>
            <div class="relative h-64">
                <canvas id="responseTimeChart"></canvas>
            </div>
        </div>

        <!-- HTTP Status Codes Chart -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">📊 HTTP Status Codes (24h)</h3>
            </div>
            <div class="relative h-64">
                <canvas id="httpStatusChart"></canvas>
            </div>
        </div>

        <!-- Database Performance -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">🗄️ Database Connections (24h)</h3>
            </div>
            <div class="relative h-64">
                <canvas id="databaseChart"></canvas>
            </div>
        </div>

        <!-- Cache Performance -->
        <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">📦 Cache Performance (24h)</h3>
            </div>
            <div class="relative h-64">
                <canvas id="cacheChart"></canvas>
            </div>
        </div>
    </div>

    <!-- System Capacity Report -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🎯 ความสามารถในการรองรับผู้ใช้งาน</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-slate-700 dark:to-slate-600 rounded-xl p-5 border border-blue-200 dark:border-slate-500">
                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">การใช้งานปัจจุบัน</div>
                <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mb-1">
                    {{ $summary['current']->active_users ?? 0 }} users
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ number_format(($summary['current']->active_users ?? 0) / 2000 * 100, 1) }}% ของความจุที่แนะนำ
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-slate-700 dark:to-slate-600 rounded-xl p-5 border border-green-200 dark:border-slate-500">
                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">ความจุที่แนะนำ</div>
                <div class="text-3xl font-bold text-green-600 dark:text-green-400 mb-1">
                    1,000 - 2,000
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Concurrent users (การตั้งค่าปัจจุบัน)
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-slate-700 dark:to-slate-600 rounded-xl p-5 border border-purple-200 dark:border-slate-500">
                <div class="text-sm text-gray-600 dark:text-gray-300 mb-2">ความจุสูงสุด (หลังปรับปรุง)</div>
                <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mb-1">
                    5,000 - 10,000
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    With Redis + Octane/Swoole
                </div>
            </div>
        </div>

        <div class="bg-gray-50 dark:bg-slate-700 rounded-xl p-5">
            <h4 class="font-semibold text-gray-900 dark:text-white mb-3">📋 คำแนะนำในการปรับปรุง</h4>
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Phase 1 (Quick Wins):</strong> เปิดใช้ Redis, เพิ่ม Database Indexes → +50% capacity</span>
                </li>
                <li class="flex items-start">
                    <span class="text-blue-500 mr-2">⚡</span>
                    <span><strong>Phase 2 (Medium):</strong> ติดตั้ง Laravel Octane/Swoole → +200-300% performance</span>
                </li>
                <li class="flex items-start">
                    <span class="text-purple-500 mr-2">🚀</span>
                    <span><strong>Phase 3 (Long-term):</strong> Horizontal Scaling, Read Replicas → Support 10,000+ users</span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex gap-4">
        <button @click="refreshMetrics()"
                :disabled="loading"
                class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-semibold shadow-lg transform hover:scale-105 transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
            <span x-show="!loading">🔄 Refresh Data</span>
            <span x-show="loading">⏳ Loading...</span>
        </button>

        <a href="{{ route('admin.analytics.export') }}?days=7"
           class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold shadow-lg transform hover:scale-105 transition duration-200">
            📥 Export CSV (7 Days)
        </a>

        <button @click="collectMetrics()"
                class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-xl font-semibold shadow-lg transform hover:scale-105 transition duration-200">
            📊 Collect Metrics Now
        </button>
    </div>
</div>

@push('scripts')
<script>
function analyticsManager() {
    return {
        metrics: @json($summary['current'] ?? []),
        loading: false,
        lastUpdate: '{{ now()->format('H:i:s') }}',
        trafficPeriod: 24,
        perfPeriod: 24,
        charts: {},

        init() {
            this.initCharts();
            this.loadAllData();
            this.startAutoRefresh();
        },

        initCharts() {
            // Traffic Chart
            const trafficCtx = document.getElementById('trafficChart').getContext('2d');
            this.charts.traffic = new Chart(trafficCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'Active Users',
                            data: [],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'Request Rate',
                            data: [],
                            borderColor: 'rgb(168, 85, 247)',
                            backgroundColor: 'rgba(168, 85, 247, 0.1)',
                            tension: 0.4,
                            fill: true,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            title: { display: true, text: 'Users' }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            title: { display: true, text: 'Req/sec' },
                            grid: { drawOnChartArea: false }
                        }
                    }
                }
            });

            // Performance Chart
            const perfCtx = document.getElementById('performanceChart').getContext('2d');
            this.charts.performance = new Chart(perfCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [
                        {
                            label: 'CPU %',
                            data: [],
                            borderColor: 'rgb(239, 68, 68)',
                            tension: 0.4
                        },
                        {
                            label: 'Memory %',
                            data: [],
                            borderColor: 'rgb(59, 130, 246)',
                            tension: 0.4
                        },
                        {
                            label: 'Disk %',
                            data: [],
                            borderColor: 'rgb(34, 197, 94)',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: { display: true, text: 'Percentage (%)' }
                        }
                    }
                }
            });

            // Response Time Chart
            const responseCtx = document.getElementById('responseTimeChart').getContext('2d');
            this.charts.responseTime = new Chart(responseCtx, {
                type: 'bar',
                data: {
                    labels: ['Average', 'P50', 'P95', 'P99'],
                    datasets: [{
                        label: 'Response Time (ms)',
                        data: [
                            {{ $summary['current']->avg_response_time ?? 0 }},
                            {{ $summary['current']->p50_response_time ?? 0 }},
                            {{ $summary['current']->p95_response_time ?? 0 }},
                            {{ $summary['current']->p99_response_time ?? 0 }}
                        ],
                        backgroundColor: [
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // HTTP Status Chart
            const httpCtx = document.getElementById('httpStatusChart').getContext('2d');
            this.charts.httpStatus = new Chart(httpCtx, {
                type: 'doughnut',
                data: {
                    labels: ['2xx Success', '3xx Redirect', '4xx Client Error', '5xx Server Error'],
                    datasets: [{
                        data: [
                            {{ $summary['current']->http_2xx ?? 0 }},
                            {{ $summary['current']->http_3xx ?? 0 }},
                            {{ $summary['current']->http_4xx ?? 0 }},
                            {{ $summary['current']->http_5xx ?? 0 }}
                        ],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(251, 191, 36, 0.8)',
                            'rgba(239, 68, 68, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Database Chart
            const dbCtx = document.getElementById('databaseChart').getContext('2d');
            this.charts.database = new Chart(dbCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Active Connections',
                        data: [],
                        borderColor: 'rgb(168, 85, 247)',
                        backgroundColor: 'rgba(168, 85, 247, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Cache Chart
            const cacheCtx = document.getElementById('cacheChart').getContext('2d');
            this.charts.cache = new Chart(cacheCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Hit Rate %',
                        data: [],
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        },

        async loadAllData() {
            await Promise.all([
                this.loadTrafficData(),
                this.loadPerformanceData(),
                this.loadDatabaseData(),
                this.loadCacheData()
            ]);
        },

        async loadTrafficData() {
            try {
                const response = await fetch(`/admin/analytics/traffic?hours=${this.trafficPeriod}`);
                const data = await response.json();

                if (data.success) {
                    const labels = data.data.map(m => new Date(m.recorded_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }));
                    this.charts.traffic.data.labels = labels;
                    this.charts.traffic.data.datasets[0].data = data.data.map(m => m.active_users);
                    this.charts.traffic.data.datasets[1].data = data.data.map(m => m.request_rate);
                    this.charts.traffic.update();
                }
            } catch (error) {
                console.error('Failed to load traffic data:', error);
            }
        },

        async loadPerformanceData() {
            try {
                const response = await fetch(`/admin/analytics/performance?hours=${this.perfPeriod}`);
                const data = await response.json();

                if (data.success) {
                    const labels = data.data.map(m => new Date(m.recorded_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }));
                    this.charts.performance.data.labels = labels;
                    this.charts.performance.data.datasets[0].data = data.data.map(m => m.cpu_usage);
                    this.charts.performance.data.datasets[1].data = data.data.map(m => m.memory_usage);
                    this.charts.performance.data.datasets[2].data = data.data.map(m => m.disk_usage);
                    this.charts.performance.update();
                }
            } catch (error) {
                console.error('Failed to load performance data:', error);
            }
        },

        async loadDatabaseData() {
            try {
                const response = await fetch('/admin/analytics/database?hours=24');
                const data = await response.json();

                if (data.success) {
                    const labels = data.data.map(m => new Date(m.recorded_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }));
                    this.charts.database.data.labels = labels;
                    this.charts.database.data.datasets[0].data = data.data.map(m => m.db_connections_active);
                    this.charts.database.update();
                }
            } catch (error) {
                console.error('Failed to load database data:', error);
            }
        },

        async loadCacheData() {
            try {
                const response = await fetch('/admin/analytics/cache?hours=24');
                const data = await response.json();

                if (data.success) {
                    const labels = data.data.map(m => new Date(m.recorded_at).toLocaleTimeString('th-TH', { hour: '2-digit', minute: '2-digit' }));
                    this.charts.cache.data.labels = labels;
                    this.charts.cache.data.datasets[0].data = data.data.map(m => m.cache_hit_rate);
                    this.charts.cache.update();
                }
            } catch (error) {
                console.error('Failed to load cache data:', error);
            }
        },

        async refreshMetrics() {
            this.loading = true;
            try {
                const response = await fetch('/admin/analytics/realtime');
                const data = await response.json();

                if (data.success) {
                    this.metrics = data.data.metrics;
                    this.lastUpdate = new Date().toLocaleTimeString('th-TH');
                }
            } catch (error) {
                console.error('Failed to refresh metrics:', error);
            } finally {
                this.loading = false;
            }
        },

        async collectMetrics() {
            try {
                const response = await fetch('/admin/analytics/collect', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();

                if (data.success) {
                    alert('✅ Metrics collected successfully!');
                    await this.refreshMetrics();
                    await this.loadAllData();
                }
            } catch (error) {
                console.error('Failed to collect metrics:', error);
                alert('❌ Failed to collect metrics');
            }
        },

        startAutoRefresh() {
            // Refresh metrics every 30 seconds
            setInterval(() => {
                this.refreshMetrics();
            }, 30000);

            // Refresh charts every 5 minutes
            setInterval(() => {
                this.loadAllData();
            }, 300000);
        }
    };
}
</script>
@endpush
@endsection
