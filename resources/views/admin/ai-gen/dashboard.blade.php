@extends('layouts.admin')

@section('title', 'AI Gen System Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-magic text-primary"></i> AI Gen System Dashboard
            </h1>
            <p class="text-muted mb-0">ภาพรวมระบบสร้างภาพและวีดีโอด้วย AI</p>
        </div>
        <div>
            <button class="btn btn-primary" onclick="refreshDashboard()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <!-- Total Providers -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Active Providers
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-providers">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-server fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Subscriptions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Active Subscriptions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-subscriptions">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Generations -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Generations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-generations">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-images fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Success Rate
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-success-rate">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Usage Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-area"></i> Usage Overview (Last 30 Days)
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary active" data-period="30">30 Days</button>
                        <button type="button" class="btn btn-outline-primary" data-period="7">7 Days</button>
                        <button type="button" class="btn btn-outline-primary" data-period="1">Today</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="usageChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Distribution Pie Chart -->
        <div class="col-xl-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-pie"></i> Generation Types
                    </h6>
                </div>
                <div class="card-body">
                    <canvas id="typeDistChart"></canvas>
                    <div class="mt-4 text-center small" id="type-legend">
                        <span class="mr-3">
                            <i class="fas fa-circle text-primary"></i> Images
                        </span>
                        <span>
                            <i class="fas fa-circle text-success"></i> Videos
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider Status & Recent Activity -->
    <div class="row">
        <!-- Provider Status -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-server"></i> Provider Status
                    </h6>
                </div>
                <div class="card-body">
                    <div id="provider-status-list">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted mt-2">Loading providers...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-xl-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history"></i> Recent Activity
                    </h6>
                    <a href="{{ route('admin.ai-gen.usage-logs') }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <div id="recent-activity-list" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                            <p class="text-muted mt-2">Loading activity...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-trophy"></i> Top Users (This Month)
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="top-users-table">
                            <thead>
                                <tr>
                                    <th width="50">#</th>
                                    <th>User</th>
                                    <th>Total Generations</th>
                                    <th>Images</th>
                                    <th>Videos</th>
                                    <th>Success Rate</th>
                                    <th>Package</th>
                                </tr>
                            </thead>
                            <tbody id="top-users-body">
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                        <p class="text-muted mt-2">Loading data...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }
    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }
    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }
    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }

    .provider-item {
        padding: 15px;
        border-bottom: 1px solid #e3e6f0;
    }

    .provider-item:last-child {
        border-bottom: none;
    }

    .activity-item {
        padding: 12px;
        border-left: 3px solid transparent;
        margin-bottom: 10px;
        border-radius: 4px;
        background: #f8f9fc;
    }

    .activity-item.success {
        border-left-color: #1cc88a;
    }

    .activity-item.failed {
        border-left-color: #e74a3b;
    }

    .activity-item.pending {
        border-left-color: #f6c23e;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let usageChart, typeDistChart;

async function loadDashboardData() {
    try {
        const response = await fetch('/admin/ai-gen/dashboard', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            updateStatistics(data.data);
            updateCharts(data.data);
            updateProviderStatus(data.data.providers || []);
            updateRecentActivity(data.data.recent_activity || []);
            updateTopUsers(data.data.top_users || []);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        showError('Failed to load dashboard data');
    }
}

function updateStatistics(data) {
    document.getElementById('stat-providers').textContent = data.active_providers || 0;
    document.getElementById('stat-subscriptions').textContent = data.active_subscriptions || 0;
    document.getElementById('stat-generations').textContent = (data.total_generations || 0).toLocaleString();

    const successRate = data.completed_generations && data.total_generations
        ? ((data.completed_generations / data.total_generations) * 100).toFixed(1)
        : 0;
    document.getElementById('stat-success-rate').textContent = successRate + '%';
}

function updateCharts(data) {
    // Usage Chart
    const ctx1 = document.getElementById('usageChart');
    if (usageChart) usageChart.destroy();

    usageChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: data.usage_labels || [],
            datasets: [{
                label: 'Images',
                data: data.usage_images || [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.3
            }, {
                label: 'Videos',
                data: data.usage_videos || [],
                borderColor: '#1cc88a',
                backgroundColor: 'rgba(28, 200, 138, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Type Distribution Chart
    const ctx2 = document.getElementById('typeDistChart');
    if (typeDistChart) typeDistChart.destroy();

    typeDistChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Images', 'Videos'],
            datasets: [{
                data: [data.total_images || 0, data.total_videos || 0],
                backgroundColor: ['#4e73df', '#1cc88a']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

function updateProviderStatus(providers) {
    const container = document.getElementById('provider-status-list');

    if (providers.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">No providers configured</p>';
        return;
    }

    container.innerHTML = providers.map(provider => `
        <div class="provider-item">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${provider.name}</h6>
                    <small class="text-muted">
                        <span class="badge badge-${provider.is_active ? 'success' : 'secondary'}">
                            ${provider.is_active ? 'Active' : 'Inactive'}
                        </span>
                        <span class="ml-2">${provider.type}</span>
                    </small>
                </div>
                <div class="text-right">
                    <div class="text-muted small">Generations</div>
                    <div class="font-weight-bold">${(provider.generations_count || 0).toLocaleString()}</div>
                </div>
            </div>
        </div>
    `).join('');
}

function updateRecentActivity(activities) {
    const container = document.getElementById('recent-activity-list');

    if (activities.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">No recent activity</p>';
        return;
    }

    container.innerHTML = activities.map(activity => `
        <div class="activity-item ${activity.status}">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="font-weight-bold">${activity.user_name}</div>
                    <div class="small text-muted">
                        ${activity.generation_type} • ${activity.provider_name}
                    </div>
                    <div class="small mt-1">${activity.prompt.substring(0, 80)}...</div>
                </div>
                <div class="text-right ml-3">
                    <span class="badge badge-${getStatusBadge(activity.status)}">
                        ${activity.status}
                    </span>
                    <div class="small text-muted mt-1">${formatTime(activity.created_at)}</div>
                </div>
            </div>
        </div>
    `).join('');
}

function updateTopUsers(users) {
    const tbody = document.getElementById('top-users-body');

    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">No data available</td></tr>';
        return;
    }

    tbody.innerHTML = users.map((user, index) => `
        <tr>
            <td>
                <span class="badge badge-${index < 3 ? 'warning' : 'secondary'}">
                    ${index + 1}
                </span>
            </td>
            <td>
                <div class="font-weight-bold">${user.name}</div>
                <small class="text-muted">${user.email}</small>
            </td>
            <td>${user.total_generations}</td>
            <td>${user.total_images}</td>
            <td>${user.total_videos}</td>
            <td>
                <span class="badge badge-${user.success_rate >= 90 ? 'success' : user.success_rate >= 70 ? 'warning' : 'danger'}">
                    ${user.success_rate}%
                </span>
            </td>
            <td>
                <span class="badge badge-primary">${user.package_name || 'Free'}</span>
            </td>
        </tr>
    `).join('');
}

function getStatusBadge(status) {
    const badges = {
        'completed': 'success',
        'failed': 'danger',
        'pending': 'warning',
        'processing': 'info'
    };
    return badges[status] || 'secondary';
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);

    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
    return Math.floor(diff / 86400) + 'd ago';
}

function refreshDashboard() {
    loadDashboardData();
}

function showError(message) {
    // Implement your error notification here
    alert(message);
}

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();

    // Refresh every 60 seconds
    setInterval(loadDashboardData, 60000);
});
</script>
@endpush
