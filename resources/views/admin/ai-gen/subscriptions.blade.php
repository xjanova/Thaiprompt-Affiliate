@extends('layouts.admin-v3')

@section('title', 'AI Gen Subscriptions')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-crown text-warning"></i> Subscriptions Management
            </h1>
            <p class="text-muted mb-0">ดูและจัดการ Subscriptions ของผู้ใช้งาน</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="loadSubscriptions()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Active Subscriptions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-active">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Expiring Soon
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-expiring">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-revenue">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                This Month
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-month">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filters
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="filter-status">
                            <option value="">All Status</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Package</label>
                        <select class="form-control" id="filter-package">
                            <option value="">All Packages</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Search User</label>
                        <input type="text" class="form-control" id="filter-search" placeholder="Name or email...">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> All Subscriptions
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="subscriptionsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Package</th>
                            <th>Credits</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>Expires At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="subscriptions-tbody">
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-3">Loading subscriptions...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted" id="pagination-info">
                    Showing 0 to 0 of 0 entries
                </div>
                <nav>
                    <ul class="pagination mb-0" id="pagination">
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- View Subscription Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Subscription Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="subscription-details">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Extend Subscription Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus"></i> Extend Subscription
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="extendForm">
                <input type="hidden" id="extend-subscription-id">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Extend by (days) *</label>
                        <input type="number" class="form-control" name="days" min="1" value="30" required>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea class="form-control" name="reason" rows="3" placeholder="e.g., Customer service compensation"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check"></i> Extend
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .status-badge {
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.active {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.expired {
        background: #f8d7da;
        color: #721c24;
    }

    .status-badge.cancelled {
        background: #e2e3e5;
        color: #383d41;
    }

    .credits-bar {
        height: 6px;
        background: #e3e6f0;
        border-radius: 3px;
        overflow: hidden;
    }

    .credits-bar-fill {
        height: 100%;
        transition: width 0.3s ease;
    }

    .credits-bar-fill.high {
        background: linear-gradient(90deg, #1cc88a 0%, #17a673 100%);
    }

    .credits-bar-fill.medium {
        background: linear-gradient(90deg, #f6c23e 0%, #dda20a 100%);
    }

    .credits-bar-fill.low {
        background: linear-gradient(90deg, #e74a3b 0%, #be2617 100%);
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .table td {
        vertical-align: middle;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #e3e6f0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }
</style>
@endpush

@push('scripts')
<script>
let subscriptions = [];
let packages = [];
let currentPage = 1;
let perPage = 20;
let filters = {};

async function loadSubscriptions() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
            ...filters
        });

        const response = await fetch(`/admin/ai-gen/subscriptions?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            subscriptions = data.data.subscriptions || [];
            updateStats(data.data.stats || {});
            renderSubscriptions();
            renderPagination(data.data.pagination || {});
        }
    } catch (error) {
        console.error('Error loading subscriptions:', error);
        showError('Failed to load subscriptions');
    }
}

async function loadPackages() {
    try {
        const response = await fetch('/admin/ai-gen/packages', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            packages = data.data;
            renderPackageFilter();
        }
    } catch (error) {
        console.error('Error loading packages:', error);
    }
}

function renderPackageFilter() {
    const select = document.getElementById('filter-package');
    packages.forEach(pkg => {
        const option = document.createElement('option');
        option.value = pkg.id;
        option.textContent = pkg.name;
        select.appendChild(option);
    });
}

function updateStats(stats) {
    document.getElementById('stat-active').textContent = (stats.active || 0).toLocaleString();
    document.getElementById('stat-expiring').textContent = (stats.expiring_soon || 0).toLocaleString();
    document.getElementById('stat-revenue').textContent = '฿' + (stats.total_revenue || 0).toLocaleString();
    document.getElementById('stat-month').textContent = (stats.this_month || 0).toLocaleString();
}

function renderSubscriptions() {
    const tbody = document.getElementById('subscriptions-tbody');

    if (subscriptions.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No subscriptions found</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = subscriptions.map(sub => {
        const totalCredits = (sub.image_credits || 0) + (sub.video_credits || 0);
        const usedCredits = (sub.image_used || 0) + (sub.video_used || 0);
        const remainingPercent = totalCredits > 0 ? ((totalCredits - usedCredits) / totalCredits * 100) : 0;
        const creditsClass = remainingPercent > 50 ? 'high' : remainingPercent > 20 ? 'medium' : 'low';

        const expiresAt = new Date(sub.expires_at);
        const now = new Date();
        const daysLeft = Math.ceil((expiresAt - now) / (1000 * 60 * 60 * 24));
        const isExpiringSoon = daysLeft <= 7 && daysLeft > 0;

        return `
            <tr>
                <td>${sub.id}</td>
                <td>
                    <div class="d-flex align-items-center">
                        ${sub.user_avatar ?
                            `<img src="${sub.user_avatar}" class="user-avatar mr-2" alt="${sub.user_name}">` :
                            `<div class="user-avatar mr-2 bg-primary text-white d-flex align-items-center justify-content-center">
                                ${sub.user_name.charAt(0).toUpperCase()}
                            </div>`
                        }
                        <div>
                            <div class="font-weight-bold">${sub.user_name}</div>
                            <small class="text-muted">${sub.user_email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <strong>${sub.package_name}</strong>
                    <div class="small text-muted">฿${parseFloat(sub.package_price).toLocaleString()}</div>
                </td>
                <td>
                    <div class="small mb-1">
                        <i class="fas fa-image text-primary"></i> ${(sub.image_credits - sub.image_used).toLocaleString()}/${sub.image_credits.toLocaleString()}
                        <i class="fas fa-video text-danger ml-2"></i> ${(sub.video_credits - sub.video_used).toLocaleString()}/${sub.video_credits.toLocaleString()}
                    </div>
                    <div class="credits-bar">
                        <div class="credits-bar-fill ${creditsClass}" style="width: ${remainingPercent}%"></div>
                    </div>
                </td>
                <td>
                    <span class="status-badge ${sub.status}">
                        ${sub.status}
                    </span>
                    ${isExpiringSoon ? '<div class="small text-warning mt-1"><i class="fas fa-exclamation-triangle"></i> Expiring soon</div>' : ''}
                </td>
                <td>
                    <small>${new Date(sub.created_at).toLocaleDateString('th-TH')}</small>
                </td>
                <td>
                    <small>${expiresAt.toLocaleDateString('th-TH')}</small>
                    ${sub.status === 'active' ? `<div class="small text-muted">${daysLeft} days left</div>` : ''}
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info" onclick="viewSubscription(${sub.id})" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        ${sub.status === 'active' ? `
                            <button class="btn btn-warning" onclick="extendSubscription(${sub.id})" title="Extend">
                                <i class="fas fa-calendar-plus"></i>
                            </button>
                        ` : ''}
                        <button class="btn btn-danger" onclick="cancelSubscription(${sub.id})" title="Cancel">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function renderPagination(pagination) {
    const info = document.getElementById('pagination-info');
    const paginationEl = document.getElementById('pagination');

    if (!pagination.total) {
        info.textContent = 'No entries';
        paginationEl.innerHTML = '';
        return;
    }

    const from = (pagination.current_page - 1) * perPage + 1;
    const to = Math.min(from + perPage - 1, pagination.total);
    info.textContent = `Showing ${from} to ${to} of ${pagination.total} entries`;

    let pages = '';
    for (let i = 1; i <= pagination.last_page; i++) {
        if (i === pagination.current_page) {
            pages += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a></li>`;
        }
    }

    paginationEl.innerHTML = pages;
}

function goToPage(page) {
    currentPage = page;
    loadSubscriptions();
}

function applyFilters() {
    filters = {
        status: document.getElementById('filter-status').value,
        package_id: document.getElementById('filter-package').value,
        search: document.getElementById('filter-search').value
    };
    currentPage = 1;
    loadSubscriptions();
}

async function viewSubscription(subscriptionId) {
    const sub = subscriptions.find(s => s.id === subscriptionId);
    if (!sub) return;

    const details = document.getElementById('subscription-details');
    details.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="font-weight-bold mb-3">User Information</h6>
                <div class="detail-row">
                    <span class="text-muted">Name:</span>
                    <strong>${sub.user_name}</strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Email:</span>
                    <strong>${sub.user_email}</strong>
                </div>
            </div>
            <div class="col-md-6">
                <h6 class="font-weight-bold mb-3">Package Information</h6>
                <div class="detail-row">
                    <span class="text-muted">Package:</span>
                    <strong>${sub.package_name}</strong>
                </div>
                <div class="detail-row">
                    <span class="text-muted">Price:</span>
                    <strong>฿${parseFloat(sub.package_price).toLocaleString()}</strong>
                </div>
            </div>
        </div>

        <hr>

        <h6 class="font-weight-bold mb-3">Credits Usage</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h6 class="text-primary"><i class="fas fa-image"></i> Image Credits</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total:</span>
                            <strong>${sub.image_credits.toLocaleString()}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Used:</span>
                            <strong>${sub.image_used.toLocaleString()}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Remaining:</span>
                            <strong class="text-success">${(sub.image_credits - sub.image_used).toLocaleString()}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-light mb-3">
                    <div class="card-body">
                        <h6 class="text-danger"><i class="fas fa-video"></i> Video Credits</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total:</span>
                            <strong>${sub.video_credits.toLocaleString()}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Used:</span>
                            <strong>${sub.video_used.toLocaleString()}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Remaining:</span>
                            <strong class="text-success">${(sub.video_credits - sub.video_used).toLocaleString()}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="font-weight-bold mb-3">Subscription Details</h6>
        <div class="detail-row">
            <span class="text-muted">Status:</span>
            <span class="status-badge ${sub.status}">${sub.status}</span>
        </div>
        <div class="detail-row">
            <span class="text-muted">Start Date:</span>
            <strong>${new Date(sub.created_at).toLocaleDateString('th-TH')}</strong>
        </div>
        <div class="detail-row">
            <span class="text-muted">Expires At:</span>
            <strong>${new Date(sub.expires_at).toLocaleDateString('th-TH')}</strong>
        </div>
        ${sub.cancelled_at ? `
        <div class="detail-row">
            <span class="text-muted">Cancelled At:</span>
            <strong class="text-danger">${new Date(sub.cancelled_at).toLocaleDateString('th-TH')}</strong>
        </div>
        ` : ''}
    `;

    $('#viewModal').modal('show');
}

function extendSubscription(subscriptionId) {
    document.getElementById('extend-subscription-id').value = subscriptionId;
    $('#extendModal').modal('show');
}

document.getElementById('extendForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const subscriptionId = document.getElementById('extend-subscription-id').value;
    const formData = new FormData(e.target);

    try {
        const response = await fetch(`/admin/ai-gen/subscriptions/${subscriptionId}/extend`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                days: parseInt(formData.get('days')),
                reason: formData.get('reason')
            })
        });

        const result = await response.json();

        if (result.success) {
            $('#extendModal').modal('hide');
            e.target.reset();
            loadSubscriptions();
            showSuccess('Subscription extended successfully!');
        } else {
            showError(result.error || 'Failed to extend subscription');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to extend subscription');
    }
});

async function cancelSubscription(subscriptionId) {
    if (!confirm('Are you sure you want to cancel this subscription?')) {
        return;
    }

    try {
        const response = await fetch(`/admin/ai-gen/subscriptions/${subscriptionId}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            loadSubscriptions();
            showSuccess('Subscription cancelled successfully!');
        } else {
            showError(result.error || 'Failed to cancel subscription');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to cancel subscription');
    }
}

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert(message);
}

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
    loadPackages();
    loadSubscriptions();
});

// Search on Enter key
document.getElementById('filter-search').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        applyFilters();
    }
});
</script>
@endpush
