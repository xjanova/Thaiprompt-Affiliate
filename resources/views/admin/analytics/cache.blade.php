@extends('layouts.admin-v3')

@section('title', 'Cache Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-teal-600 via-cyan-600 to-blue-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Cache Analytics</h1>
                    <p class="text-teal-100 text-sm">Monitor cache performance and hit rates</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="hoursFilter" class="px-4 py-2 glass-fusion text-teal-600 rounded-xl font-semibold">
                    <option value="6">Last 6 Hours</option>
                    <option value="24" selected>Last 24 Hours</option>
                    <option value="168">Last 7 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 glass-fusion text-teal-600 rounded-xl hover:bg-teal-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="glass-fusion rounded-xl shadow-lg p-8 text-center" border border-white/20 dark:border-white/10>
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-teal-600"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading cache analytics...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Cache Metrics -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Hit Rate</span>
                    <span class="text-green-600 text-2xl">✅</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="hitRate">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div id="hitBar" class="bg-green-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Miss Rate</span>
                    <span class="text-red-600 text-2xl">❌</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="missRate">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div id="missBar" class="bg-red-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Total Keys</span>
                    <span class="text-blue-600 text-2xl">🔑</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="totalKeys">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Cached items</div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Memory Used</span>
                    <span class="text-orange-600 text-2xl">💾</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="memoryUsed">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" id="memoryPercent">-</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Hit Rate Trend</h3>
                <canvas id="hitRateChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Memory Usage</h3>
                <canvas id="memoryChart" height="250"></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Cache Operations</h3>
                <canvas id="operationsChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-808 mb-4">Key Count Trend</h3>
                <canvas id="keysChart" height="250"></canvas>
            </div>
        </div>

        <!-- Cache Stats -->
        <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Cache Statistics</h3>
            <div class="grid md:grid-cols-3 gap-4" id="cacheStats">
                <!-- Will be populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-bold text-red-800 mb-2">Failed to Load Data</h3>
        <p class="text-red-600 mb-4">Unable to fetch cache analytics.</p>
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
        const response = await fetch(`{{ route('admin.analytics.cache') }}?hours=${hours}`);
        const result = await response.json();

        if (result.success) {
            updateMetrics(result.data);
            updateCharts(result.data);
            updateStats(result.data);
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
    const hitRate = data.hit_rate || 0;
    const missRate = data.miss_rate || 0;

    document.getElementById('hitRate').textContent = hitRate.toFixed(1);
    document.getElementById('hitBar').style.width = hitRate + '%';

    document.getElementById('missRate').textContent = missRate.toFixed(1);
    document.getElementById('missBar').style.width = missRate + '%';

    document.getElementById('totalKeys').textContent = (data.total_keys || 0).toLocaleString();
    document.getElementById('memoryUsed').textContent = data.memory_used || '-';
    document.getElementById('memoryPercent').textContent = data.memory_percent || '-';
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

    // Hit Rate Chart
    const hitCtx = document.getElementById('hitRateChart').getContext('2d');
    charts.hitRate = new Chart(hitCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Hit Rate %',
                data: data.hit_rates || [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });

    // Memory Chart
    const memCtx = document.getElementById('memoryChart').getContext('2d');
    charts.memory = new Chart(memCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Memory MB',
                data: data.memory_usage || [],
                borderColor: 'rgb(249, 115, 22)',
                backgroundColor: 'rgba(249, 115, 22, 0.1)',
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

    // Operations Chart
    const opsCtx = document.getElementById('operationsChart').getContext('2d');
    charts.operations = new Chart(opsCtx, {
        type: 'bar',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Hits',
                data: data.hits || [],
                backgroundColor: 'rgba(34, 197, 94, 0.8)'
            }, {
                label: 'Misses',
                data: data.misses || [],
                backgroundColor: 'rgba(239, 68, 68, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Keys Chart
    const keysCtx = document.getElementById('keysChart').getContext('2d');
    charts.keys = new Chart(keysCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Total Keys',
                data: data.key_counts || [],
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
}

function updateStats(data) {
    const container = document.getElementById('cacheStats');
    const stats = data.stats || {};

    container.innerHTML = Object.entries(stats).map(([key, value]) => `
        <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 rounded-xl p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">${key.replace(/_/g, ' ').toUpperCase()}</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${value}</div>
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
