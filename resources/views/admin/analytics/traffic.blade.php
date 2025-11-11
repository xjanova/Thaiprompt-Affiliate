@extends('layouts.admin')

@section('title', 'Traffic Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-orange-600 via-amber-600 to-yellow-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Traffic Analytics</h1>
                    <p class="text-orange-100 text-sm">Analyze visitor traffic and user behavior patterns</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="hoursFilter" class="px-4 py-2 bg-white text-orange-600 rounded-lg font-semibold">
                    <option value="6">Last 6 Hours</option>
                    <option value="24" selected>Last 24 Hours</option>
                    <option value="168">Last 7 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white text-orange-600 rounded-lg hover:bg-orange-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
        <p class="mt-4 text-gray-600">Loading traffic analytics...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Traffic Stats -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Total Requests</span>
                    <span class="text-blue-600 text-2xl">🌐</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="totalRequests">-</div>
                <div class="mt-2 text-xs text-gray-500" id="requestTrend">-</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Unique Visitors</span>
                    <span class="text-green-600 text-2xl">👥</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="uniqueVisitors">-</div>
                <div class="mt-2 text-xs text-gray-500" id="visitorTrend">-</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Status 2xx</span>
                    <span class="text-green-600 text-2xl">✅</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="status2xx">-</div>
                <div class="mt-2 text-xs text-gray-500">Success responses</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Status 4xx/5xx</span>
                    <span class="text-red-600 text-2xl">⚠️</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="statusErrors">-</div>
                <div class="mt-2 text-xs text-gray-500">Error responses</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Traffic Volume</h3>
                <canvas id="trafficChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">HTTP Status Codes</h3>
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Top Endpoints</h3>
                <canvas id="endpointsChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Request Methods</h3>
                <canvas id="methodsChart" height="250"></canvas>
            </div>
        </div>

        <!-- Top Endpoints Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Top Endpoints (by Request Count)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Endpoint</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requests</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Avg Response (ms)</th>
                        </tr>
                    </thead>
                    <tbody id="endpointsTable" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Error State -->
    <div id="errorState" class="hidden bg-red-50 border border-red-200 rounded-lg p-6 text-center">
        <svg class="w-12 h-12 text-red-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        <h3 class="text-lg font-bold text-red-800 mb-2">Failed to Load Data</h3>
        <p class="text-red-600 mb-4">Unable to fetch traffic analytics.</p>
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
        const response = await fetch(`{{ route('admin.analytics.traffic') }}?hours=${hours}`);
        const result = await response.json();

        if (result.success) {
            updateStats(result.data);
            updateCharts(result.data);
            updateEndpointsTable(result.data.top_endpoints || []);
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
    document.getElementById('requestTrend').textContent = data.request_trend || 'No change';
    document.getElementById('uniqueVisitors').textContent = (data.unique_visitors || 0).toLocaleString();
    document.getElementById('visitorTrend').textContent = data.visitor_trend || 'No change';
    document.getElementById('status2xx').textContent = (data.status_2xx || 0).toLocaleString();
    document.getElementById('statusErrors').textContent = ((data.status_4xx || 0) + (data.status_5xx || 0)).toLocaleString();
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

    // Traffic Chart
    const trafficCtx = document.getElementById('trafficChart').getContext('2d');
    charts.traffic = new Chart(trafficCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Requests',
                data: data.request_counts || [],
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

    // Endpoints Chart
    const endCtx = document.getElementById('endpointsChart').getContext('2d');
    charts.endpoints = new Chart(endCtx, {
        type: 'bar',
        data: {
            labels: (data.top_endpoints || []).slice(0, 10).map(e => e.path || 'Unknown'),
            datasets: [{
                label: 'Requests',
                data: (data.top_endpoints || []).slice(0, 10).map(e => e.count || 0),
                backgroundColor: 'rgba(251, 146, 60, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            indexAxis: 'y'
        }
    });

    // Methods Chart
    const methodCtx = document.getElementById('methodsChart').getContext('2d');
    charts.methods = new Chart(methodCtx, {
        type: 'pie',
        data: {
            labels: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
            datasets: [{
                data: [
                    data.method_get || 0,
                    data.method_post || 0,
                    data.method_put || 0,
                    data.method_delete || 0,
                    data.method_patch || 0
                ],
                backgroundColor: [
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 191, 36, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(147, 51, 234, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

function updateEndpointsTable(endpoints) {
    const tbody = document.getElementById('endpointsTable');
    if (endpoints.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-gray-500">No data available</td></tr>';
        return;
    }

    tbody.innerHTML = endpoints.slice(0, 10).map(e => `
        <tr>
            <td class="px-6 py-4 text-sm text-gray-900">${e.path || 'N/A'}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${(e.count || 0).toLocaleString()}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${(e.avg_response || 0).toFixed(2)}</td>
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
