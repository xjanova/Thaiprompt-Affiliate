@extends('layouts.admin')

@section('title', 'Security Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-red-600 via-rose-600 to-pink-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Security Analytics</h1>
                    <p class="text-red-100 text-sm">Monitor security events and threat detection</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="hoursFilter" class="px-4 py-2 bg-white text-red-600 rounded-lg font-semibold">
                    <option value="6">Last 6 Hours</option>
                    <option value="24" selected>Last 24 Hours</option>
                    <option value="168">Last 7 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white text-red-600 rounded-lg hover:bg-red-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-red-600"></div>
        <p class="mt-4 text-gray-600">Loading security analytics...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- Security Metrics -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Failed Logins</span>
                    <span class="text-red-600 text-2xl">🔒</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="failedLogins">-</div>
                <div class="mt-2 text-xs text-gray-500">Failed attempts</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Blocked IPs</span>
                    <span class="text-orange-600 text-2xl">🚫</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="blockedIPs">-</div>
                <div class="mt-2 text-xs text-gray-500">Active blocks</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">User Bans</span>
                    <span class="text-purple-600 text-2xl">⛔</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="userBans">-</div>
                <div class="mt-2 text-xs text-gray-500">Banned accounts</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Captcha Requests</span>
                    <span class="text-blue-600 text-2xl">🤖</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="captchaRequests">-</div>
                <div class="mt-2 text-xs text-gray-500">Bot challenges</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Failed Login Attempts</h3>
                <canvas id="failedLoginsChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Security Events</h3>
                <canvas id="eventsChart" height="250"></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Top Blocked IPs</h3>
                <canvas id="blockedIPsChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Event Types Distribution</h3>
                <canvas id="eventTypesChart" height="250"></canvas>
            </div>
        </div>

        <!-- Security Events Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-800">Recent Security Events</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">IP Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                        </tr>
                    </thead>
                    <tbody id="eventsTable" class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">Loading...</td>
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
        <p class="text-red-600 mb-4">Unable to fetch security analytics.</p>
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
        const response = await fetch(`{{ route('admin.analytics.security') }}?hours=${hours}`);
        const result = await response.json();

        if (result.success) {
            updateMetrics(result.data);
            updateCharts(result.data);
            updateEventsTable(result.data.recent_events || []);
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
    document.getElementById('failedLogins').textContent = (data.failed_logins || 0).toLocaleString();
    document.getElementById('blockedIPs').textContent = (data.blocked_ips || 0).toLocaleString();
    document.getElementById('userBans').textContent = (data.user_bans || 0).toLocaleString();
    document.getElementById('captchaRequests').textContent = (data.captcha_requests || 0).toLocaleString();
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

    // Failed Logins Chart
    const loginsCtx = document.getElementById('failedLoginsChart').getContext('2d');
    charts.logins = new Chart(loginsCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Failed Logins',
                data: data.failed_login_counts || [],
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

    // Events Chart
    const eventsCtx = document.getElementById('eventsChart').getContext('2d');
    charts.events = new Chart(eventsCtx, {
        type: 'bar',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Security Events',
                data: data.event_counts || [],
                backgroundColor: 'rgba(249, 115, 22, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Blocked IPs Chart
    const ipsCtx = document.getElementById('blockedIPsChart').getContext('2d');
    charts.ips = new Chart(ipsCtx, {
        type: 'bar',
        data: {
            labels: (data.top_blocked_ips || []).slice(0, 10).map(ip => ip.address || 'Unknown'),
            datasets: [{
                label: 'Block Count',
                data: (data.top_blocked_ips || []).slice(0, 10).map(ip => ip.count || 0),
                backgroundColor: 'rgba(147, 51, 234, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            indexAxis: 'y'
        }
    });

    // Event Types Chart
    const typesCtx = document.getElementById('eventTypesChart').getContext('2d');
    charts.types = new Chart(typesCtx, {
        type: 'doughnut',
        data: {
            labels: ['Failed Login', 'Blocked IP', 'Ban', 'Captcha', 'Other'],
            datasets: [{
                data: [
                    data.failed_logins || 0,
                    data.blocked_ips || 0,
                    data.user_bans || 0,
                    data.captcha_requests || 0,
                    data.other_events || 0
                ],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(147, 51, 234, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
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

function updateEventsTable(events) {
    const tbody = document.getElementById('eventsTable');
    if (events.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">No security events found</td></tr>';
        return;
    }

    tbody.innerHTML = events.slice(0, 20).map(e => `
        <tr>
            <td class="px-6 py-4 text-sm text-gray-900">${e.time || 'N/A'}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${e.event || 'N/A'}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${e.ip || 'N/A'}</td>
            <td class="px-6 py-4 text-sm text-gray-900">${e.count || 1}</td>
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
