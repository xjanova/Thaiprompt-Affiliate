@extends('layouts.admin-v3')

@section('title', 'Historical Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Historical Analytics</h1>
                    <p class="text-purple-100 text-sm">Analyze historical data and trends over time</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="periodFilter" class="px-4 py-2 glass-fusion text-purple-600 rounded-xl font-semibold">
                    <option value="24h">Last 24 Hours</option>
                    <option value="7d" selected>Last 7 Days</option>
                    <option value="30d">Last 30 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 glass-fusion text-purple-600 rounded-xl hover:bg-purple-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="glass-fusion rounded-xl shadow-lg p-8 text-center" border border-white/20 dark:border-white/10>
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading historical data...</p>
    </div>

    <!-- Content (Hidden Initially) -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Summary Stats -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Total Requests</span>
                    <span class="text-blue-600 text-2xl">📊</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="totalRequests">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="requestChange">-</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Avg Response Time</span>
                    <span class="text-green-600 text-2xl">⚡</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="avgResponseTime">-</span>ms</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="responseChange">-</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Error Rate</span>
                    <span class="text-red-600 text-2xl">⚠️</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="errorRate">-</span>%</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="errorChange">-</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Active Users</span>
                    <span class="text-purple-600 text-2xl">👥</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="activeUsers">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="userChange">-</div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Request Volume Over Time</h3>
                <canvas id="requestChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Response Time Trend</h3>
                <canvas id="responseTimeChart" height="250"></canvas>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">HTTP Status Distribution</h3>
                <canvas id="statusChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">User Activity Trend</h3>
                <canvas id="userChart" height="250"></canvas>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-bold text-red-800 mb-2">Failed to Load Data</h3>
        <p class="text-red-600 mb-4">Unable to fetch historical analytics data.</p>
        <button onclick="loadData()" class="px-6 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">
            Retry
        </button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let charts = {};

async function loadData() {
    const period = document.getElementById('periodFilter').value;
    const loadingState = document.getElementById('loadingState');
    const contentArea = document.getElementById('contentArea');
    const errorState = document.getElementById('errorState');

    loadingState.classList.remove('hidden');
    contentArea.classList.add('hidden');
    errorState.classList.add('hidden');

    try {
        const response = await fetch(`{{ route('admin.analytics.historical') }}?period=${period}`);
        const result = await response.json();

        if (result.success) {
            updateStats(result.data);
            updateCharts(result.data);
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

function updateStats(data) {
    document.getElementById('totalRequests').textContent = (data.total_requests || 0).toLocaleString();
    document.getElementById('requestChange').textContent = data.request_change || 'No change';

    document.getElementById('avgResponseTime').textContent = (data.avg_response_time || 0).toFixed(0);
    document.getElementById('responseChange').textContent = data.response_change || 'No change';

    document.getElementById('errorRate').textContent = (data.error_rate || 0).toFixed(2);
    document.getElementById('errorChange').textContent = data.error_change || 'No change';

    document.getElementById('activeUsers').textContent = (data.active_users || 0).toLocaleString();
    document.getElementById('userChange').textContent = data.user_change || 'No change';
}

function updateCharts(data) {
    // Destroy existing charts
    Object.values(charts).forEach(chart => chart.destroy());

    // Request Volume Chart
    const requestCtx = document.getElementById('requestChart').getContext('2d');
    charts.request = new Chart(requestCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Requests',
                data: data.requests || [],
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

    // Response Time Chart
    const responseCtx = document.getElementById('responseTimeChart').getContext('2d');
    charts.response = new Chart(responseCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Response Time (ms)',
                data: data.response_times || [],
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

    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    charts.status = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['2xx Success', '3xx Redirect', '4xx Client Error', '5xx Server Error'],
            datasets: [{
                data: [
                    data.status_2xx || 0,
                    data.status_3xx || 0,
                    data.status_4xx || 0,
                    data.status_5xx || 0
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

    // User Activity Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    charts.user = new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Active Users',
                data: data.users || [],
                backgroundColor: 'rgba(147, 51, 234, 0.8)'
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
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadData();
    document.getElementById('periodFilter').addEventListener('change', loadData);
});
</script>
@endpush
@endsection
