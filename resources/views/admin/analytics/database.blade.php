@extends('layouts.admin-v3')

@section('title', 'Database Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Database Analytics</h1>
                    <p class="text-indigo-100 text-sm">Monitor database performance and query optimization</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="hoursFilter" class="px-4 py-2 glass-fusion text-indigo-600 rounded-xl font-semibold">
                    <option value="6">Last 6 Hours</option>
                    <option value="24" selected>Last 24 Hours</option>
                    <option value="168">Last 7 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 glass-fusion text-indigo-600 rounded-xl hover:bg-indigo-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="glass-fusion rounded-xl shadow-lg p-8 text-center" border border-white/20 dark:border-white/10>
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading database analytics...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Database Stats -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Total Queries</span>
                    <span class="text-blue-600 text-2xl">📊</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="totalQueries">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="queryTrend">-</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Slow Queries</span>
                    <span class="text-red-600 text-2xl">🐌</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="slowQueries">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Over 1000ms</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Connections</span>
                    <span class="text-green-600 text-2xl">🔗</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="connections">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Active connections</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Avg Query Time</span>
                    <span class="text-purple-600 text-2xl">⏱️</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="avgQueryTime">-</span>ms</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Average execution</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Query Volume</h3>
                <canvas id="queryChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Query Performance</h3>
                <canvas id="performanceChart" height="250"></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Connection Pool Usage</h3>
                <canvas id="connectionChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Query Types Distribution</h3>
                <canvas id="queryTypeChart" height="250"></canvas>
            </div>
        </div>

        <!-- Slow Queries Table -->
        <div class="glass-fusion rounded-xl shadow-lg overflow-hidden" border border-white/20 dark:border-white/10>
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Slow Queries (Top 10)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Query</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Time (ms)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Count</th>
                        </tr>
                    </thead>
                    <tbody id="slowQueriesTable" class="glass-fusion divide-y divide-gray-200">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-bold text-red-800 mb-2">Failed to Load Data</h3>
        <p class="text-red-600 mb-4">Unable to fetch database analytics.</p>
        <button onclick="loadData()" class="px-6 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">Retry</button>
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
        const response = await fetch(`{{ route('admin.analytics.database') }}?hours=${hours}`);
        const result = await response.json();

        if (result.success) {
            updateStats(result.data);
            updateCharts(result.data);
            updateSlowQueries(result.data.slow_queries || []);
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
    document.getElementById('totalQueries').textContent = (data.total_queries || 0).toLocaleString();
    document.getElementById('queryTrend').textContent = data.query_trend || 'No change';
    document.getElementById('slowQueries').textContent = (data.slow_queries_count || 0).toLocaleString();
    document.getElementById('connections').textContent = data.active_connections || 0;
    document.getElementById('avgQueryTime').textContent = (data.avg_query_time || 0).toFixed(1);
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

    // Query Volume Chart
    const queryCtx = document.getElementById('queryChart').getContext('2d');
    charts.query = new Chart(queryCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Queries',
                data: data.query_counts || [],
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

    // Performance Chart
    const perfCtx = document.getElementById('performanceChart').getContext('2d');
    charts.performance = new Chart(perfCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Avg Query Time (ms)',
                data: data.query_times || [],
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
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

    // Connection Chart
    const connCtx = document.getElementById('connectionChart').getContext('2d');
    charts.connection = new Chart(connCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Active Connections',
                data: data.connection_counts || [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
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

    // Query Type Chart
    const typeCtx = document.getElementById('queryTypeChart').getContext('2d');
    charts.type = new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'OTHER'],
            datasets: [{
                data: [
                    data.select_count || 0,
                    data.insert_count || 0,
                    data.update_count || 0,
                    data.delete_count || 0,
                    data.other_count || 0
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(156, 163, 175, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function updateSlowQueries(queries) {
    const tbody = document.getElementById('slowQueriesTable');
    if (queries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">No slow queries found</td></tr>';
        return;
    }

    tbody.innerHTML = queries.map(q => `
        <tr>
            <td class="px-6 py-4 text-sm text-gray-900">${q.query || 'N/A'}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${(q.time || 0).toFixed(2)}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${q.count || 0}</td>
        </tr>
    `).join('');
}

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    document.getElementById('hoursFilter').addEventListener('change', loadData);
});
</script>
@endpush
@endsection
