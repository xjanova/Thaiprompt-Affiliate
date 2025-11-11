@extends('layouts.admin')

@section('title', 'AI Gen Usage Logs')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-history text-primary"></i> Usage Logs
            </h1>
            <p class="text-muted mb-0">ดูบันทึกการใช้งาน AI Gen ทั้งหมด</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="loadLogs()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <button class="btn btn-outline-success" onclick="exportLogs()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Successful
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-success">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Failed
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-failed">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
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
                                Pending
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-pending">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-warning"></i>
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
                                Total Credits Used
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-credits">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-coins fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-filter"></i> Filters
            </h6>
            <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                <i class="fas fa-redo"></i> Clear
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" id="filter-type">
                            <option value="">All Types</option>
                            <option value="image">Image</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="filter-status">
                            <option value="">All Status</option>
                            <option value="success">Success</option>
                            <option value="failed">Failed</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Provider</label>
                        <select class="form-control" id="filter-provider">
                            <option value="">All Providers</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Free Quota</label>
                        <select class="form-control" id="filter-quota">
                            <option value="">All</option>
                            <option value="1">Free Quota Only</option>
                            <option value="0">Paid Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" class="form-control" id="filter-from">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" class="form-control" id="filter-to">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-10">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" class="form-control" id="filter-search" placeholder="Search by user, prompt, or ID...">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-block" onclick="applyFilters()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Activity Logs
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="logsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th width="120">Time</th>
                            <th width="150">User</th>
                            <th width="100">Provider</th>
                            <th width="80">Type</th>
                            <th>Prompt</th>
                            <th width="80">Credits</th>
                            <th width="80">Status</th>
                            <th width="100">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody">
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
                                <p class="text-muted mt-3">Loading logs...</p>
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

<!-- View Log Details Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle"></i> Log Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="log-details">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.success {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.failed {
        background: #f8d7da;
        color: #721c24;
    }

    .status-badge.pending {
        background: #fff3cd;
        color: #856404;
    }

    .type-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .type-badge.image {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .type-badge.video {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .free-badge {
        background: #17a2b8;
        color: white;
        padding: 2px 6px;
        border-radius: 8px;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .table td {
        vertical-align: middle;
        font-size: 0.85rem;
    }

    .prompt-preview {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .detail-section {
        margin-bottom: 20px;
    }

    .detail-section h6 {
        border-bottom: 2px solid #4e73df;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e3e6f0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .error-box {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 8px;
        padding: 15px;
        color: #721c24;
    }
</style>
@endpush

@push('scripts')
<script>
let logs = [];
let providers = [];
let currentPage = 1;
let perPage = 50;
let filters = {};

async function loadLogs() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
            ...filters
        });

        const response = await fetch(`/admin/ai-gen/usage-logs?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            logs = data.data.logs || [];
            updateStats(data.data.stats || {});
            renderLogs();
            renderPagination(data.data.pagination || {});
        }
    } catch (error) {
        console.error('Error loading logs:', error);
        showError('Failed to load logs');
    }
}

async function loadProviders() {
    try {
        const response = await fetch('/admin/ai-gen/providers', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            providers = data.data;
            renderProviderFilter();
        }
    } catch (error) {
        console.error('Error loading providers:', error);
    }
}

function renderProviderFilter() {
    const select = document.getElementById('filter-provider');
    providers.forEach(provider => {
        const option = document.createElement('option');
        option.value = provider.id;
        option.textContent = provider.name;
        select.appendChild(option);
    });
}

function updateStats(stats) {
    document.getElementById('stat-success').textContent = (stats.success || 0).toLocaleString();
    document.getElementById('stat-failed').textContent = (stats.failed || 0).toLocaleString();
    document.getElementById('stat-pending').textContent = (stats.pending || 0).toLocaleString();
    document.getElementById('stat-credits').textContent = (stats.total_credits || 0).toLocaleString();
}

function renderLogs() {
    const tbody = document.getElementById('logs-tbody');

    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No logs found</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = logs.map(log => `
        <tr>
            <td class="text-center">#${log.id}</td>
            <td>
                <div class="small">${formatDateTime(log.created_at)}</div>
            </td>
            <td>
                <div class="font-weight-bold">${log.user_name}</div>
                <div class="small text-muted">${log.user_email}</div>
            </td>
            <td>
                <span class="badge badge-secondary">${log.provider_name}</span>
            </td>
            <td>
                <span class="type-badge ${log.generation_type}">${log.generation_type}</span>
            </td>
            <td>
                <div class="prompt-preview" title="${escapeHtml(log.prompt)}">
                    ${escapeHtml(log.prompt)}
                </div>
            </td>
            <td class="text-center">
                <strong>${log.credits_used}</strong>
                ${log.is_free_quota ? '<div class="free-badge">FREE</div>' : ''}
            </td>
            <td>
                <span class="status-badge ${log.status}">${log.status}</span>
            </td>
            <td class="text-center">
                <button class="btn btn-sm btn-info" onclick="viewLog(${log.id})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');
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

    // Previous button
    if (pagination.current_page > 1) {
        pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1}); return false;">Previous</a></li>`;
    }

    // Page numbers (show max 5 pages)
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

    if (startPage > 1) {
        pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(1); return false;">1</a></li>`;
        if (startPage > 2) pages += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            pages += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a></li>`;
        }
    }

    if (endPage < pagination.last_page) {
        if (endPage < pagination.last_page - 1) pages += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.last_page}); return false;">${pagination.last_page}</a></li>`;
    }

    // Next button
    if (pagination.current_page < pagination.last_page) {
        pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.current_page + 1}); return false;">Next</a></li>`;
    }

    paginationEl.innerHTML = pages;
}

function goToPage(page) {
    currentPage = page;
    loadLogs();
}

function applyFilters() {
    filters = {
        type: document.getElementById('filter-type').value,
        status: document.getElementById('filter-status').value,
        provider_id: document.getElementById('filter-provider').value,
        is_free_quota: document.getElementById('filter-quota').value,
        date_from: document.getElementById('filter-from').value,
        date_to: document.getElementById('filter-to').value,
        search: document.getElementById('filter-search').value
    };
    currentPage = 1;
    loadLogs();
}

function clearFilters() {
    document.getElementById('filter-type').value = '';
    document.getElementById('filter-status').value = '';
    document.getElementById('filter-provider').value = '';
    document.getElementById('filter-quota').value = '';
    document.getElementById('filter-from').value = '';
    document.getElementById('filter-to').value = '';
    document.getElementById('filter-search').value = '';
    filters = {};
    currentPage = 1;
    loadLogs();
}

async function viewLog(logId) {
    const log = logs.find(l => l.id === logId);
    if (!log) return;

    const details = document.getElementById('log-details');
    details.innerHTML = `
        <div class="detail-section">
            <h6><i class="fas fa-user"></i> User Information</h6>
            <div class="detail-row">
                <span class="text-muted">User:</span>
                <strong>${log.user_name} (${log.user_email})</strong>
            </div>
            <div class="detail-row">
                <span class="text-muted">User ID:</span>
                <strong>#${log.user_id}</strong>
            </div>
        </div>

        <div class="detail-section">
            <h6><i class="fas fa-cog"></i> Generation Details</h6>
            <div class="detail-row">
                <span class="text-muted">Provider:</span>
                <strong>${log.provider_name}</strong>
            </div>
            <div class="detail-row">
                <span class="text-muted">Type:</span>
                <span class="type-badge ${log.generation_type}">${log.generation_type}</span>
            </div>
            <div class="detail-row">
                <span class="text-muted">Status:</span>
                <span class="status-badge ${log.status}">${log.status}</span>
            </div>
            <div class="detail-row">
                <span class="text-muted">Credits Used:</span>
                <strong>${log.credits_used} ${log.is_free_quota ? '<span class="free-badge ml-2">FREE QUOTA</span>' : ''}</strong>
            </div>
            <div class="detail-row">
                <span class="text-muted">Created At:</span>
                <strong>${formatDateTime(log.created_at)}</strong>
            </div>
        </div>

        <div class="detail-section">
            <h6><i class="fas fa-comment-dots"></i> Prompt</h6>
            <div class="bg-light p-3 rounded">
                ${escapeHtml(log.prompt)}
            </div>
        </div>

        ${log.parameters ? `
        <div class="detail-section">
            <h6><i class="fas fa-sliders-h"></i> Parameters</h6>
            <pre class="bg-light p-3 rounded"><code>${JSON.stringify(log.parameters, null, 2)}</code></pre>
        </div>
        ` : ''}

        ${log.error_message ? `
        <div class="detail-section">
            <h6><i class="fas fa-exclamation-triangle"></i> Error Details</h6>
            <div class="error-box">
                <strong>Error Message:</strong><br>
                ${escapeHtml(log.error_message)}
            </div>
        </div>
        ` : ''}

        ${log.response_data ? `
        <div class="detail-section">
            <h6><i class="fas fa-code"></i> Response Data</h6>
            <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>${JSON.stringify(log.response_data, null, 2)}</code></pre>
        </div>
        ` : ''}
    `;

    $('#viewModal').modal('show');
}

async function exportLogs() {
    try {
        const params = new URLSearchParams(filters);
        window.location.href = `/admin/ai-gen/usage-logs/export?${params}`;
        showSuccess('Export started. Download will begin shortly.');
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to export logs');
    }
}

function formatDateTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleString('th-TH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert(message);
}

// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
    loadProviders();
    loadLogs();
});

// Search on Enter key
document.getElementById('filter-search').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        applyFilters();
    }
});

// Auto-refresh every 30 seconds
setInterval(() => {
    if (document.visibilityState === 'visible') {
        loadLogs();
    }
}, 30000);
</script>
@endpush
