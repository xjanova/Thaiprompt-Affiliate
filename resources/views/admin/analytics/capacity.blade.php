@extends('layouts.admin-v3')

@section('title', 'Capacity Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-cyan-600 via-sky-600 to-blue-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 glass-fusion rounded-xl flex items-center justify-center backdrop-blur-sm" border border-white/20 dark:border-white/10>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Capacity Analytics</h1>
                    <p class="text-cyan-100 text-sm">Monitor resource utilization and capacity planning</p>
                </div>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 glass-fusion text-cyan-600 rounded-xl hover:bg-cyan-50 transition font-semibold">
                Back
            </a>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="glass-fusion rounded-xl shadow-lg p-8 text-center" border border-white/20 dark:border-white/10>
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-cyan-600"></div>
        <p class="mt-4 text-gray-600 dark:text-gray-400">Loading capacity analytics...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Resource Usage -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">CPU Usage</span>
                    <span class="text-purple-600 text-2xl">💻</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="cpuUsage">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div id="cpuBar" class="bg-purple-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Memory Usage</span>
                    <span class="text-blue-600 text-2xl">💾</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="memoryUsage">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div id="memoryBar" class="bg-blue-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Disk Usage</span>
                    <span class="text-orange-600 text-2xl">💿</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white"><span id="diskUsage">-</span>%</div>
                <div class="mt-2">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div id="diskBar" class="bg-orange-600 h-1.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                </div>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 dark:text-gray-400 text-sm">Network Load</span>
                    <span class="text-green-600 text-2xl">🌐</span>
                </div>
                <div class="text-3xl font-bold text-gray-900 dark:text-white" id="networkLoad">-</div>
                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">Current throughput</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Resource Utilization Trend</h3>
                <canvas id="resourceChart" height="250"></canvas>
            </div>

            <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Peak Load Times</h3>
                <canvas id="peakLoadChart" height="250"></canvas>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Capacity Recommendations</h3>
            <div id="recommendations" class="space-y-4">
                <!-- Will be populated dynamically -->
            </div>
        </div>

        <!-- Current Capacity -->
        <div class="glass-fusion rounded-xl shadow-lg p-6" hover:scale-105 transition-transform border border-white/20 dark:border-white/10>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Current Capacity Status</h3>
            <div class="grid md:grid-cols-3 gap-4" id="capacityStatus">
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
        <p class="text-red-600 mb-4">Unable to fetch capacity analytics.</p>
        <button onclick="loadData()" class="px-6 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700">Retry</button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let charts = {};

async function loadData() {
    const loadingState = document.getElementById('loadingState');
    const contentArea = document.getElementById('contentArea');
    const errorState = document.getElementById('errorState');

    loadingState.classList.remove('hidden');
    contentArea.classList.add('hidden');
    errorState.classList.add('hidden');

    try {
        const response = await fetch(`{{ route('admin.analytics.capacity') }}`);
        const result = await response.json();

        if (result.success) {
            updateMetrics(result.data);
            updateCharts(result.data);
            updateRecommendations(result.data.recommendations || []);
            updateCapacityStatus(result.data.status || {});
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
    const cpu = data.cpu_usage || 0;
    const memory = data.memory_usage || 0;
    const disk = data.disk_usage || 0;

    document.getElementById('cpuUsage').textContent = cpu.toFixed(1);
    document.getElementById('cpuBar').style.width = cpu + '%';

    document.getElementById('memoryUsage').textContent = memory.toFixed(1);
    document.getElementById('memoryBar').style.width = memory + '%';

    document.getElementById('diskUsage').textContent = disk.toFixed(1);
    document.getElementById('diskBar').style.width = disk + '%';

    document.getElementById('networkLoad').textContent = data.network_load || '-';
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

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
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }, {
                label: 'Disk %',
                data: data.disk_history || [],
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

    // Peak Load Chart
    const peakCtx = document.getElementById('peakLoadChart').getContext('2d');
    charts.peak = new Chart(peakCtx, {
        type: 'bar',
        data: {
            labels: data.peak_hours || [],
            datasets: [{
                label: 'Load Level',
                data: data.peak_loads || [],
                backgroundColor: 'rgba(6, 182, 212, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });
}

function updateRecommendations(recommendations) {
    const container = document.getElementById('recommendations');
    if (recommendations.length === 0) {
        container.innerHTML = '<div class="p-4 bg-green-50 border border-green-200 rounded-xl"><p class="text-green-700">All systems operating within optimal capacity. No recommendations at this time.</p></div>';
        return;
    }

    container.innerHTML = recommendations.map(rec => {
        const colorClass = rec.priority === 'high' ? 'red' : rec.priority === 'medium' ? 'yellow' : 'blue';
        return `
            <div class="p-4 bg-${colorClass}-50 border border-${colorClass}-200 rounded-xl">
                <div class="flex items-start">
                    <span class="text-2xl mr-3">${rec.icon || '⚠️'}</span>
                    <div>
                        <h4 class="font-semibold text-${colorClass}-900 mb-1">${rec.title || 'Recommendation'}</h4>
                        <p class="text-sm text-${colorClass}-700">${rec.message || ''}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function updateCapacityStatus(status) {
    const container = document.getElementById('capacityStatus');
    container.innerHTML = Object.entries(status).map(([key, value]) => `
        <div class="bg-gray-100/50 dark:bg-gray-800/50/50 dark:bg-gray-800/50 rounded-xl p-4">
            <div class="text-sm text-gray-600 dark:text-gray-400">${key.replace(/_/g, ' ').toUpperCase()}</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${value}</div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    // Refresh every 30 seconds
    setInterval(loadData, 30000);
});
</script>
@endpush
@endsection
