@extends('layouts.admin-v3')

@section('title', 'Real-time Analytics')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="glass-fusion rounded-2xl shadow-2xl p-6 border-2"
         style="background: var(--arrow-x-success-gradient); border-color: var(--arrow-x-success)">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 dark:bg-black/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Real-time Analytics</h1>
                    <p class="text-white/80 text-sm">Live system monitoring</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="flex items-center gap-2 text-sm text-white">
                        <span class="w-3 h-3 rounded-full animate-pulse"
                              style="background-color: var(--arrow-x-success)"></span>
                        <span>Live</span>
                    </div>
                    <div class="text-xs text-white/70 mt-1">Updates every 5s</div>
                </div>
                <a href="{{ route('admin.dashboard') }}"
                   class="px-4 py-2 bg-white/90 dark:bg-black/30 text-gray-900 dark:text-white rounded-xl hover:bg-white dark:hover:bg-black/50 transition font-semibold shadow-lg">
                    Back
                </a>
            </div>
        </div>
    </div>

    {{-- Real-time Stats Grid --}}
    <div class="grid md:grid-cols-4 gap-4" id="statsGrid">
        {{-- Active Users --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-700 dark:text-gray-300 text-sm font-semibold">Active Users</span>
                <span class="text-2xl" style="color: var(--arrow-x-primary-start)">👥</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white" id="activeUsers">-</div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Online now</div>
        </div>

        {{-- Request Rate --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-700 dark:text-gray-300 text-sm font-semibold">Request Rate</span>
                <span class="text-2xl" style="color: var(--arrow-x-success)">📊</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="requestRate">-</span>/s</div>
            <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Requests per second</div>
        </div>

        {{-- CPU Usage --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-700 dark:text-gray-300 text-sm font-semibold">CPU Usage</span>
                <span class="text-2xl" style="color: var(--arrow-x-accent)">⚡</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="cpuUsage">-</span>%</div>
            <div class="mt-2">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                    <div id="cpuBar" class="h-1.5 rounded-full transition-all duration-300"
                         style="width: 0%; background-color: var(--arrow-x-accent)"></div>
                </div>
            </div>
        </div>

        {{-- Memory Usage --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10 hover:scale-105 transition-transform">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-700 dark:text-gray-300 text-sm font-semibold">Memory Usage</span>
                <span class="text-2xl" style="color: var(--arrow-x-warning)">💾</span>
            </div>
            <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="memoryUsage">-</span>%</div>
            <div class="mt-2">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                    <div id="memoryBar" class="h-1.5 rounded-full transition-all duration-300"
                         style="width: 0%; background-color: var(--arrow-x-warning)"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid md:grid-cols-2 gap-6">
        {{-- CPU & Memory Chart --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">System Resources (Last 10min)</h3>
            <canvas id="systemChart" height="200"></canvas>
        </div>

        {{-- Request Rate Chart --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Request Rate (Last 10min)</h3>
            <canvas id="requestChart" height="200"></canvas>
        </div>
    </div>

    {{-- Charts Row 2 --}}
    <div class="grid md:grid-cols-2 gap-6">
        {{-- Response Time Chart --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Response Time (Last 10min)</h3>
            <canvas id="responseTimeChart" height="200"></canvas>
        </div>

        {{-- HTTP Status Codes --}}
        <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">HTTP Status Codes (Last 10min)</h3>
            <canvas id="httpStatusChart" height="200"></canvas>
        </div>
    </div>

    {{-- System Health Status --}}
    <div class="glass-fusion rounded-xl shadow-xl p-6 border border-white/20 dark:border-white/10">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span id="healthIcon">🟢</span> System Health Status
        </h3>
        <div id="healthStatus" class="grid md:grid-cols-3 gap-4">
            {{-- Will be populated dynamically --}}
            <div class="bg-gray-100/50 dark:bg-gray-800/50 rounded-lg p-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">LOADING</div>
                <div class="text-lg font-bold text-gray-400 dark:text-gray-500">Fetching data...</div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let charts = {};
let dataPoints = {
    cpu: [],
    memory: [],
    requestRate: [],
    responseTime: [],
    httpStatus: { '2xx': [], '3xx': [], '4xx': [], '5xx': [] },
    timestamps: []
};

const maxDataPoints = 60; // 10 minutes at 10s intervals

/**
 * รับ CSS Variable color สำหรับ charts
 */
function getCSSColor(varName) {
    return getComputedStyle(document.documentElement).getPropertyValue(varName).trim();
}

/**
 * เริ่มต้น Charts พร้อมรองรับ Dark Mode
 */
function initCharts() {
    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#e5e7eb' : '#374151';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    // System Resources Chart
    const systemCtx = document.getElementById('systemChart').getContext('2d');
    charts.system = new Chart(systemCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'CPU %',
                data: [],
                borderColor: getCSSColor('--arrow-x-accent'),
                backgroundColor: getCSSColor('--arrow-x-accent') + '20',
                tension: 0.4
            }, {
                label: 'Memory %',
                data: [],
                borderColor: getCSSColor('--arrow-x-warning'),
                backgroundColor: getCSSColor('--arrow-x-warning') + '20',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100, ticks: { color: textColor }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { color: gridColor } }
            },
            plugins: {
                legend: { position: 'top', labels: { color: textColor } }
            }
        }
    });

    // Request Rate Chart
    const requestCtx = document.getElementById('requestChart').getContext('2d');
    charts.request = new Chart(requestCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Requests/sec',
                data: [],
                borderColor: getCSSColor('--arrow-x-success'),
                backgroundColor: getCSSColor('--arrow-x-success') + '20',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { color: gridColor } }
            },
            plugins: {
                legend: { labels: { color: textColor } }
            }
        }
    });

    // Response Time Chart
    const responseCtx = document.getElementById('responseTimeChart').getContext('2d');
    charts.responseTime = new Chart(responseCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Avg Response (ms)',
                data: [],
                borderColor: getCSSColor('--arrow-x-primary-start'),
                backgroundColor: getCSSColor('--arrow-x-primary-start') + '20',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { color: textColor }, grid: { color: gridColor } },
                x: { ticks: { color: textColor }, grid: { color: gridColor } }
            },
            plugins: {
                legend: { labels: { color: textColor } }
            }
        }
    });

    // HTTP Status Chart
    const httpCtx = document.getElementById('httpStatusChart').getContext('2d');
    charts.httpStatus = new Chart(httpCtx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [
                { label: '2xx Success', data: [], backgroundColor: getCSSColor('--arrow-x-success') + 'cc' },
                { label: '3xx Redirect', data: [], backgroundColor: getCSSColor('--arrow-x-primary-start') + 'cc' },
                { label: '4xx Client Error', data: [], backgroundColor: getCSSColor('--arrow-x-warning') + 'cc' },
                { label: '5xx Server Error', data: [], backgroundColor: getCSSColor('--arrow-x-error') + 'cc' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, stacked: true, ticks: { color: textColor }, grid: { color: gridColor } },
                x: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor } }
            },
            plugins: {
                legend: { labels: { color: textColor } }
            }
        }
    });
}

/**
 * ดึงข้อมูล Real-time จาก API
 */
async function fetchRealtimeData() {
    try {
        const response = await fetch('{{ route("admin.analytics.realtime") }}');
        const result = await response.json();

        if (result.success) {
            updateStats(result.data.metrics);
            updateCharts(result.data.metrics);
            updateHealth(result.data.health);
        }
    } catch (error) {
        console.error('Error fetching realtime data:', error);
    }
}

/**
 * อัพเดท Stats Cards
 */
function updateStats(metrics) {
    document.getElementById('activeUsers').textContent = metrics.active_users || 0;
    document.getElementById('requestRate').textContent = (metrics.request_rate || 0).toFixed(1);

    const cpu = metrics.cpu_usage || 0;
    const memory = metrics.memory_usage || 0;

    document.getElementById('cpuUsage').textContent = cpu.toFixed(1);
    document.getElementById('cpuBar').style.width = cpu + '%';

    document.getElementById('memoryUsage').textContent = memory.toFixed(1);
    document.getElementById('memoryBar').style.width = memory + '%';
}

/**
 * อัพเดท Charts
 */
function updateCharts(metrics) {
    const timestamp = new Date().toLocaleTimeString();

    // เพิ่ม data points ใหม่
    dataPoints.timestamps.push(timestamp);
    dataPoints.cpu.push(metrics.cpu_usage || 0);
    dataPoints.memory.push(metrics.memory_usage || 0);
    dataPoints.requestRate.push(metrics.request_rate || 0);
    dataPoints.responseTime.push(metrics.avg_response_time || 0);
    dataPoints.httpStatus['2xx'].push(metrics.http_2xx || 0);
    dataPoints.httpStatus['3xx'].push(metrics.http_3xx || 0);
    dataPoints.httpStatus['4xx'].push(metrics.http_4xx || 0);
    dataPoints.httpStatus['5xx'].push(metrics.http_5xx || 0);

    // เก็บเฉพาะ maxDataPoints จุดล่าสุด
    if (dataPoints.timestamps.length > maxDataPoints) {
        dataPoints.timestamps.shift();
        dataPoints.cpu.shift();
        dataPoints.memory.shift();
        dataPoints.requestRate.shift();
        dataPoints.responseTime.shift();
        dataPoints.httpStatus['2xx'].shift();
        dataPoints.httpStatus['3xx'].shift();
        dataPoints.httpStatus['4xx'].shift();
        dataPoints.httpStatus['5xx'].shift();
    }

    // อัพเดททุก charts
    charts.system.data.labels = dataPoints.timestamps;
    charts.system.data.datasets[0].data = dataPoints.cpu;
    charts.system.data.datasets[1].data = dataPoints.memory;
    charts.system.update('none');

    charts.request.data.labels = dataPoints.timestamps;
    charts.request.data.datasets[0].data = dataPoints.requestRate;
    charts.request.update('none');

    charts.responseTime.data.labels = dataPoints.timestamps;
    charts.responseTime.data.datasets[0].data = dataPoints.responseTime;
    charts.responseTime.update('none');

    charts.httpStatus.data.labels = dataPoints.timestamps;
    charts.httpStatus.data.datasets[0].data = dataPoints.httpStatus['2xx'];
    charts.httpStatus.data.datasets[1].data = dataPoints.httpStatus['3xx'];
    charts.httpStatus.data.datasets[2].data = dataPoints.httpStatus['4xx'];
    charts.httpStatus.data.datasets[3].data = dataPoints.httpStatus['5xx'];
    charts.httpStatus.update('none');
}

/**
 * อัพเดท System Health Status
 */
function updateHealth(health) {
    const container = document.getElementById('healthStatus');
    const icon = document.getElementById('healthIcon');

    if (!health) return;

    // อัพเดท health icon
    if (health.status === 'healthy') {
        icon.textContent = '🟢';
    } else if (health.status === 'warning') {
        icon.textContent = '🟡';
    } else {
        icon.textContent = '🔴';
    }

    // อัพเดท health details
    const isDark = document.documentElement.classList.contains('dark');
    container.innerHTML = Object.entries(health.details || {}).map(([key, value]) => `
        <div class="bg-gray-100/50 ${isDark ? 'dark:bg-gray-800/50' : ''} rounded-lg p-4">
            <div class="text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}">${key.replace(/_/g, ' ').toUpperCase()}</div>
            <div class="text-lg font-bold ${value.status === 'ok' ? (isDark ? 'text-green-400' : 'text-green-600') : (isDark ? 'text-red-400' : 'text-red-600')}">
                ${value.status === 'ok' ? '✓' : '✗'} ${value.message || value.status}
            </div>
        </div>
    `).join('');
}

/**
 * เริ่มต้นเมื่อโหลดหน้า
 */
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    fetchRealtimeData();

    // อัพเดททุก 5 วินาที
    setInterval(fetchRealtimeData, 5000);

    // รีเฟรช charts เมื่อเปลี่ยน theme
    window.addEventListener('theme-changed', function() {
        // ลบ charts เก่า
        Object.values(charts).forEach(chart => chart.destroy());
        charts = {};
        // สร้างใหม่
        initCharts();
        updateCharts({ cpu_usage: 0, memory_usage: 0, request_rate: 0, avg_response_time: 0 });
    });
});
</script>
@endpush
@endsection
