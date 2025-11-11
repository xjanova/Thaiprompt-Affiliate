@extends('layouts.admin')

@section('title', 'Real-time Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-green-600 via-emerald-600 to-teal-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Real-time Analytics</h1>
                    <p class="text-green-100 text-sm">Live system monitoring</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="w-3 h-3 bg-green-400 rounded-full animate-pulse"></span>
                        <span>Live</span>
                    </div>
                    <div class="text-xs text-green-200 mt-1">Updates every 5s</div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-green-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Real-time Stats Grid -->
    <div class="grid md:grid-cols-4 gap-4" id="statsGrid">
        <!-- Active Users -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-600 text-sm">Active Users</span>
                <span class="text-blue-600 text-2xl">👥</span>
            </div>
            <div class="text-3xl font-bold text-gray-800" id="activeUsers">-</div>
            <div class="mt-2 text-xs text-gray-500">Online now</div>
        </div>

        <!-- Request Rate -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-600 text-sm">Request Rate</span>
                <span class="text-green-600 text-2xl">📊</span>
            </div>
            <div class="text-3xl font-bold text-gray-800"><span id="requestRate">-</span>/s</div>
            <div class="mt-2 text-xs text-gray-500">Requests per second</div>
        </div>

        <!-- CPU Usage -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-600 text-sm">CPU Usage</span>
                <span class="text-purple-600 text-2xl">⚡</span>
            </div>
            <div class="text-3xl font-bold text-gray-800"><span id="cpuUsage">-</span>%</div>
            <div class="mt-2">
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div id="cpuBar" class="bg-purple-600 h-1.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Memory Usage -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-600 text-sm">Memory Usage</span>
                <span class="text-orange-600 text-2xl">💾</span>
            </div>
            <div class="text-3xl font-bold text-gray-800"><span id="memoryUsage">-</span>%</div>
            <div class="mt-2">
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div id="memoryBar" class="bg-orange-600 h-1.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid md:grid-cols-2 gap-6">
        <!-- CPU & Memory Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">System Resources (Last 10min)</h3>
            <canvas id="systemChart" height="200"></canvas>
        </div>

        <!-- Request Rate Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Request Rate (Last 10min)</h3>
            <canvas id="requestChart" height="200"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid md:grid-cols-2 gap-6">
        <!-- Response Time Chart -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Response Time (Last 10min)</h3>
            <canvas id="responseTimeChart" height="200"></canvas>
        </div>

        <!-- HTTP Status Codes -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">HTTP Status Codes (Last 10min)</h3>
            <canvas id="httpStatusChart" height="200"></canvas>
        </div>
    </div>

    <!-- System Health Status -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <span id="healthIcon">🟢</span> System Health Status
        </h3>
        <div id="healthStatus" class="grid md:grid-cols-3 gap-4">
            <!-- Will be populated dynamically -->
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="text-sm text-gray-600">LOADING</div>
                <div class="text-lg font-bold text-gray-400">Fetching data...</div>
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

// Initialize Charts
function initCharts() {
    // System Resources Chart
    const systemCtx = document.getElementById('systemChart').getContext('2d');
    charts.system = new Chart(systemCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'CPU %',
                data: [],
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4
            }, {
                label: 'Memory %',
                data: [],
                borderColor: 'rgb(249, 115, 22)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, max: 100 }
            },
            plugins: {
                legend: { position: 'top' }
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
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
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
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
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
                { label: '2xx Success', data: [], backgroundColor: 'rgba(34, 197, 94, 0.8)' },
                { label: '3xx Redirect', data: [], backgroundColor: 'rgba(59, 130, 246, 0.8)' },
                { label: '4xx Client Error', data: [], backgroundColor: 'rgba(251, 191, 36, 0.8)' },
                { label: '5xx Server Error', data: [], backgroundColor: 'rgba(239, 68, 68, 0.8)' }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, stacked: true },
                x: { stacked: true }
            }
        }
    });
}

// Fetch and Update Data
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

function updateCharts(metrics) {
    const timestamp = new Date().toLocaleTimeString();

    // Add new data points
    dataPoints.timestamps.push(timestamp);
    dataPoints.cpu.push(metrics.cpu_usage || 0);
    dataPoints.memory.push(metrics.memory_usage || 0);
    dataPoints.requestRate.push(metrics.request_rate || 0);
    dataPoints.responseTime.push(metrics.avg_response_time || 0);
    dataPoints.httpStatus['2xx'].push(metrics.http_2xx || 0);
    dataPoints.httpStatus['3xx'].push(metrics.http_3xx || 0);
    dataPoints.httpStatus['4xx'].push(metrics.http_4xx || 0);
    dataPoints.httpStatus['5xx'].push(metrics.http_5xx || 0);

    // Keep only last maxDataPoints
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

    // Update all charts
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

function updateHealth(health) {
    const container = document.getElementById('healthStatus');
    const icon = document.getElementById('healthIcon');

    if (!health) return;

    // Update health icon
    if (health.status === 'healthy') {
        icon.textContent = '🟢';
    } else if (health.status === 'warning') {
        icon.textContent = '🟡';
    } else {
        icon.textContent = '🔴';
    }

    // Update health details
    container.innerHTML = Object.entries(health.details || {}).map(([key, value]) => `
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">${key.replace(/_/g, ' ').toUpperCase()}</div>
            <div class="text-lg font-bold ${value.status === 'ok' ? 'text-green-600' : 'text-red-600'}">
                ${value.status === 'ok' ? '✓' : '✗'} ${value.message || value.status}
            </div>
        </div>
    `).join('');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initCharts();
    fetchRealtimeData();

    // Update every 5 seconds
    setInterval(fetchRealtimeData, 5000);
});
</script>
@endpush
@endsection
