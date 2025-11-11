@extends('layouts.admin')

@section('title', 'Performance Analytics')

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
                    <h1 class="text-2xl font-bold">Performance Analytics</h1>
                    <p class="text-green-100 text-sm">Monitor application performance and optimization metrics</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="hoursFilter" class="px-4 py-2 bg-white text-green-600 rounded-lg font-semibold">
                    <option value="1">Last Hour</option>
                    <option value="6">Last 6 Hours</option>
                    <option value="24" selected>Last 24 Hours</option>
                    <option value="168">Last 7 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white text-green-600 rounded-lg hover:bg-green-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
        <p class="mt-4 text-gray-600">Loading performance data...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Performance Metrics -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Avg Response Time</span>
                    <span class="text-blue-600 text-2xl">⚡</span>
                </div>
                <div class="text-3xl font-bold text-gray-800"><span id="avgResponse">-</span>ms</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div id="responseBar" class="bg-blue-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">CPU Usage</span>
                    <span class="text-purple-600 text-2xl">💻</span>
                </div>
                <div class="text-3xl font-bold text-gray-800"><span id="cpuUsage">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div id="cpuBar" class="bg-purple-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Memory Usage</span>
                    <span class="text-orange-600 text-2xl">💾</span>
                </div>
                <div class="text-3xl font-bold text-gray-800"><span id="memoryUsage">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div id="memoryBar" class="bg-orange-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Throughput</span>
                    <span class="text-green-600 text-2xl">📈</span>
                </div>
                <div class="text-3xl font-bold text-gray-800"><span id="throughput">-</span>/s</div>
                <div class="mt-2 text-xs text-gray-500">Requests per second</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Response Time Trend</h3>
                <canvas id="responseChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">CPU & Memory Usage</h3>
                <canvas id="resourceChart" height="250"></canvas>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Request Rate</h3>
                <canvas id="throughputChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Error Rate</h3>
                <canvas id="errorChart" height="250"></canvas>
            </div>
        </div>

        <!-- Performance Details -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Performance Breakdown</h3>
            <div class="grid md:grid-cols-3 gap-4" id="performanceDetails">
                <!-- Will be populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <svg class="w-12 h-12 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-bold text-red-800 mb-2">Failed to Load Data</h3>
        <p class="text-red-600 mb-4">Unable to fetch performance analytics.</p>
        <button onclick="loadData()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Retry</button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let charts = {};

async function loadData() {
    const hours = document.getElementById('hoursFilter').value;
    const loadingState = document.getElementById('loadingState');
    const contentArea = document.getElementById('contentArea');
    const errorState = document.getElementById('errorState');

    loadingState.classList.remove('hidden');
    contentArea.classList.add('hidden');
    errorState.classList.add('hidden');

    try {
        const response = await fetch(`{{ route('admin.analytics.performance') }}?hours=${hours}`);
        const result = await response.json();

        if (result.success) {
            updateMetrics(result.data);
            updateCharts(result.data);
            updateDetails(result.data);
            loadingState.classList.add('hidden');
            contentArea.classList.remove('hidden');
        } else {
            throw new Error('Failed to load data');
        }
    } catch (error) {
        console.error('Error:', error);
        loadingState.classList.add('hidden');
        errorState.classList.remove('hidden');
    }
}

function updateMetrics(data) {
    document.getElementById('avgResponse').textContent = (data.avg_response || 0).toFixed(0);
    document.getElementById('responseBar').style.width = Math.min((data.avg_response / 1000) * 100, 100) + '%';

    document.getElementById('cpuUsage').textContent = (data.cpu_usage || 0).toFixed(1);
    document.getElementById('cpuBar').style.width = (data.cpu_usage || 0) + '%';

    document.getElementById('memoryUsage').textContent = (data.memory_usage || 0).toFixed(1);
    document.getElementById('memoryBar').style.width = (data.memory_usage || 0) + '%';

    document.getElementById('throughput').textContent = (data.throughput || 0).toFixed(1);
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

    // Response Time Chart
    const responseCtx = document.getElementById('responseChart').getContext('2d');
    charts.response = new Chart(responseCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Response Time (ms)',
                data: data.response_times || [],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Resource Chart
    const resourceCtx = document.getElementById('resourceChart').getContext('2d');
    charts.resource = new Chart(resourceCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'CPU %',
                data: data.cpu_history || [],
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4
            }, {
                label: 'Memory %',
                data: data.memory_history || [],
                borderColor: 'rgb(249, 115, 22)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });

    // Throughput Chart
    const throughputCtx = document.getElementById('throughputChart').getContext('2d');
    charts.throughput = new Chart(throughputCtx, {
        type: 'bar',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Requests/sec',
                data: data.request_rates || [],
                backgroundColor: 'rgba(34, 197, 94, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Error Chart
    const errorCtx = document.getElementById('errorChart').getContext('2d');
    charts.error = new Chart(errorCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Error Rate %',
                data: data.error_rates || [],
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
}

function updateDetails(data) {
    const container = document.getElementById('performanceDetails');
    const details = data.details || {};

    container.innerHTML = Object.entries(details).map(([key, value]) => `
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">${key.replace(/_/g, ' ').toUpperCase()}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">${value}</div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    document.getElementById('hoursFilter').addEventListener('change', loadData);
});
</script>
@endpush
@endsection
