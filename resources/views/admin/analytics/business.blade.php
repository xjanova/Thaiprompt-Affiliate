@extends('layouts.admin')

@section('title', 'Business Analytics')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-gradient-to-r from-pink-600 via-rose-600 to-red-600 rounded-xl shadow-2xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold">Business Analytics</h1>
                    <p class="text-pink-100 text-sm">Key business metrics and performance indicators</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <select id="daysFilter" class="px-4 py-2 bg-white text-pink-600 rounded-lg font-semibold">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 90 Days</option>
                </select>
                <a href="{{ route('admin.dashboard') }}" class="px-4 py-2 bg-white text-pink-600 rounded-lg hover:bg-pink-50 transition font-semibold">
                    Back
                </a>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loadingState" class="bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-pink-600"></div>
        <p class="mt-4 text-gray-600">Loading business analytics...</p>
    </div>

    <!-- Content -->
    <div id="contentArea" class="hidden space-y-6">
        <!-- KPIs -->
        <div class="grid md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Total Revenue</span>
                    <span class="text-green-600 text-2xl">💰</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="totalRevenue">-</div>
                <div class="mt-2 text-xs text-gray-500" id="revenueTrend">-</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Total Users</span>
                    <span class="text-blue-600 text-2xl">👥</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="totalUsers">-</div>
                <div class="mt-2 text-xs text-gray-500" id="usersTrend">-</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Total Affiliates</span>
                    <span class="text-purple-600 text-2xl">🤝</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="totalAffiliates">-</div>
                <div class="mt-2 text-xs text-gray-500" id="affiliatesTrend">-</div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-gray-600 text-sm">Transactions</span>
                    <span class="text-orange-600 text-2xl">💳</span>
                </div>
                <div class="text-3xl font-bold text-gray-800" id="totalTransactions">-</div>
                <div class="mt-2 text-xs text-gray-500" id="transactionsTrend">-</div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Revenue Trend</h3>
                <canvas id="revenueChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">User Growth</h3>
                <canvas id="userGrowthChart" height="250"></canvas>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Transactions Over Time</h3>
                <canvas id="transactionsChart" height="250"></canvas>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Revenue by Category</h3>
                <canvas id="categoryChart" height="250"></canvas>
            </div>
        </div>

        <!-- Business Metrics -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Key Business Metrics</h3>
            <div class="grid md:grid-cols-3 gap-4" id="businessMetrics">
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
        <p class="text-red-600 mb-4">Unable to fetch business analytics.</p>
        <button onclick="loadData()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">Retry</button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let charts = {};

async function loadData() {
    const days = document.getElementById('daysFilter').value;
    const loadingState = document.getElementById('loadingState');
    const contentArea = document.getElementById('contentArea');
    const errorState = document.getElementById('errorState');

    loadingState.classList.remove('hidden');
    contentArea.classList.add('hidden');
    errorState.classList.add('hidden');

    try {
        const response = await fetch(`{{ route('admin.analytics.business') }}?days=${days}`);
        const result = await response.json();

        if (result.success) {
            updateKPIs(result.data);
            updateCharts(result.data);
            updateMetrics(result.data);
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

function updateKPIs(data) {
    document.getElementById('totalRevenue').textContent = data.total_revenue || '-';
    document.getElementById('revenueTrend').textContent = data.revenue_trend || 'No change';
    document.getElementById('totalUsers').textContent = (data.total_users || 0).toLocaleString();
    document.getElementById('usersTrend').textContent = data.users_trend || 'No change';
    document.getElementById('totalAffiliates').textContent = (data.total_affiliates || 0).toLocaleString();
    document.getElementById('affiliatesTrend').textContent = data.affiliates_trend || 'No change';
    document.getElementById('totalTransactions').textContent = (data.total_transactions || 0).toLocaleString();
    document.getElementById('transactionsTrend').textContent = data.transactions_trend || 'No change';
}

function updateCharts(data) {
    Object.values(charts).forEach(chart => chart.destroy());

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    charts.revenue = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Revenue',
                data: data.revenue_data || [],
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

    // User Growth Chart
    const userCtx = document.getElementById('userGrowthChart').getContext('2d');
    charts.userGrowth = new Chart(userCtx, {
        type: 'line',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'New Users',
                data: data.new_users || [],
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

    // Transactions Chart
    const transCtx = document.getElementById('transactionsChart').getContext('2d');
    charts.transactions = new Chart(transCtx, {
        type: 'bar',
        data: {
            labels: data.timeline || [],
            datasets: [{
                label: 'Transactions',
                data: data.transaction_counts || [],
                backgroundColor: 'rgba(249, 115, 22, 0.8)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Category Chart
    const catCtx = document.getElementById('categoryChart').getContext('2d');
    charts.category = new Chart(catCtx, {
        type: 'pie',
        data: {
            labels: data.categories || [],
            datasets: [{
                data: data.category_revenues || [],
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

function updateMetrics(data) {
    const container = document.getElementById('businessMetrics');
    const metrics = data.metrics || {};

    container.innerHTML = Object.entries(metrics).map(([key, value]) => `
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="text-sm text-gray-600">${key.replace(/_/g, ' ').toUpperCase()}</div>
            <div class="text-2xl font-bold text-gray-800 mt-1">${value}</div>
        </div>
    `).join('');
}

document.addEventListener('DOMContentLoaded', function() {
    loadData();
    document.getElementById('daysFilter').addEventListener('change', loadData);
});
</script>
@endpush
@endsection
