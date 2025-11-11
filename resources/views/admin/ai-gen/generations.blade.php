@extends('layouts.admin')

@section('title', 'AI Gen Gallery')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-images text-primary"></i> All Generations
            </h1>
            <p class="text-muted mb-0">ดูภาพและวีดีโอที่ถูกสร้างทั้งหมดในระบบ</p>
        </div>
        <div>
            <button class="btn btn-outline-primary" onclick="loadGenerations()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
            <div class="btn-group ml-2">
                <button class="btn btn-outline-secondary" onclick="setView('grid')" id="btn-grid">
                    <i class="fas fa-th"></i>
                </button>
                <button class="btn btn-outline-secondary" onclick="setView('list')" id="btn-list">
                    <i class="fas fa-list"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Generations
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-total">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-magic fa-2x text-primary"></i>
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
                                Images
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-images">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-image fa-2x text-info"></i>
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
                                Videos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-videos">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-danger"></i>
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
                                Today
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-today">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-day fa-2x text-warning"></i>
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
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Type</label>
                        <select class="form-control" id="filter-type">
                            <option value="">All Types</option>
                            <option value="image">Images</option>
                            <option value="video">Videos</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" id="filter-status">
                            <option value="">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="processing">Processing</option>
                            <option value="failed">Failed</option>
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
                        <label>Favorites</label>
                        <select class="form-control" id="filter-favorite">
                            <option value="">All</option>
                            <option value="1">Favorites Only</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="filter-search" placeholder="Search prompt or user...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" onclick="applyFilters()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gallery -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div id="generations-container" class="row">
                <div class="col-12 text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
                    <p class="text-muted mt-3">Loading generations...</p>
                </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4">
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

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-eye"></i> Generation Details
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="generation-details">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-danger" onclick="deleteGeneration()">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .generation-card {
        position: relative;
        border-radius: 15px;
        overflow: hidden;
        transition: all 0.3s ease;
        cursor: pointer;
        background: white;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .generation-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }

    .generation-image-wrapper {
        position: relative;
        padding-top: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        overflow: hidden;
    }

    .generation-image {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .generation-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.7) 100%);
        opacity: 0;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: flex-end;
        padding: 15px;
    }

    .generation-card:hover .generation-overlay {
        opacity: 1;
    }

    .generation-prompt {
        color: white;
        font-size: 0.85rem;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .generation-info {
        padding: 12px;
    }

    .generation-type-badge {
        position: absolute;
        top: 10px;
        left: 10px;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        color: white;
        z-index: 2;
    }

    .generation-type-badge.image {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .generation-type-badge.video {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .favorite-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        background: rgba(255,255,255,0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 2;
    }

    .favorite-badge i {
        color: #f6c23e;
        font-size: 1rem;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 10px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-badge.completed {
        background: #d4edda;
        color: #155724;
    }

    .status-badge.processing {
        background: #fff3cd;
        color: #856404;
    }

    .status-badge.failed {
        background: #f8d7da;
        color: #721c24;
    }

    /* List View Styles */
    .list-view .generation-item {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 1px solid #e3e6f0;
        border-radius: 10px;
        margin-bottom: 10px;
        background: white;
        transition: all 0.3s ease;
    }

    .list-view .generation-item:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .list-view .generation-thumbnail {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
        margin-right: 20px;
    }

    .list-view .generation-details {
        flex: 1;
    }

    .preview-large {
        max-width: 100%;
        max-height: 600px;
        border-radius: 10px;
    }

    .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .detail-card {
        background: #f8f9fc;
        padding: 15px;
        border-radius: 10px;
    }

    .detail-card h6 {
        margin-bottom: 10px;
        color: #4e73df;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 5px 0;
    }
</style>
@endpush

@push('scripts')
<script>
let generations = [];
let providers = [];
let currentPage = 1;
let perPage = 24;
let filters = {};
let viewMode = 'grid';
let currentGenerationId = null;

async function loadGenerations() {
    try {
        const params = new URLSearchParams({
            page: currentPage,
            per_page: perPage,
            ...filters
        });

        const response = await fetch(`/admin/ai-gen/generations?${params}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            generations = data.data.generations || [];
            updateStats(data.data.stats || {});
            renderGenerations();
            renderPagination(data.data.pagination || {});
        }
    } catch (error) {
        console.error('Error loading generations:', error);
        showError('Failed to load generations');
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
    document.getElementById('stat-total').textContent = (stats.total || 0).toLocaleString();
    document.getElementById('stat-images').textContent = (stats.images || 0).toLocaleString();
    document.getElementById('stat-videos').textContent = (stats.videos || 0).toLocaleString();
    document.getElementById('stat-today').textContent = (stats.today || 0).toLocaleString();
}

function renderGenerations() {
    const container = document.getElementById('generations-container');

    if (generations.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-images fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No generations found</h5>
                <p class="text-muted">Try adjusting your filters</p>
            </div>
        `;
        return;
    }

    if (viewMode === 'grid') {
        container.className = 'row';
        container.innerHTML = generations.map(gen => `
            <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="generation-card" onclick="viewGeneration(${gen.id})">
                    <div class="generation-image-wrapper">
                        <span class="generation-type-badge ${gen.type}">${gen.type}</span>
                        ${gen.is_favorite ? '<div class="favorite-badge"><i class="fas fa-star"></i></div>' : ''}
                        ${gen.file_url ?
                            gen.type === 'video' ?
                                `<video class="generation-image" src="${gen.file_url}" muted></video>` :
                                `<img class="generation-image" src="${gen.file_url}" alt="Generation">` :
                            `<div class="generation-image d-flex align-items-center justify-content-center">
                                <i class="fas fa-${gen.type === 'video' ? 'video' : 'image'} fa-3x text-white"></i>
                            </div>`
                        }
                        <div class="generation-overlay">
                            <p class="generation-prompt mb-0">${escapeHtml(gen.prompt)}</p>
                        </div>
                    </div>
                    <div class="generation-info">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">
                                <i class="fas fa-user"></i> ${gen.user_name}
                            </small>
                            <span class="status-badge ${gen.status}">${gen.status}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                <i class="fas fa-server"></i> ${gen.provider_name}
                            </small>
                            <small class="text-muted">
                                ${formatDate(gen.created_at)}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    } else {
        container.className = 'list-view';
        container.innerHTML = generations.map(gen => `
            <div class="generation-item" onclick="viewGeneration(${gen.id})">
                ${gen.file_url ?
                    gen.type === 'video' ?
                        `<video class="generation-thumbnail" src="${gen.file_url}" muted></video>` :
                        `<img class="generation-thumbnail" src="${gen.file_url}" alt="Generation">` :
                    `<div class="generation-thumbnail d-flex align-items-center justify-content-center bg-light">
                        <i class="fas fa-${gen.type === 'video' ? 'video' : 'image'} fa-2x text-muted"></i>
                    </div>`
                }
                <div class="generation-details">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1">
                                <span class="generation-type-badge ${gen.type}">${gen.type}</span>
                                ${gen.is_favorite ? '<i class="fas fa-star text-warning ml-2"></i>' : ''}
                            </h6>
                            <p class="mb-2">${escapeHtml(gen.prompt)}</p>
                        </div>
                        <span class="status-badge ${gen.status}">${gen.status}</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="fas fa-user"></i> ${gen.user_name} (${gen.user_email})
                            <span class="ml-3"><i class="fas fa-server"></i> ${gen.provider_name}</span>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-calendar"></i> ${formatDateTime(gen.created_at)}
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }
}

function setView(mode) {
    viewMode = mode;
    document.getElementById('btn-grid').classList.toggle('btn-primary', mode === 'grid');
    document.getElementById('btn-grid').classList.toggle('btn-outline-secondary', mode !== 'grid');
    document.getElementById('btn-list').classList.toggle('btn-primary', mode === 'list');
    document.getElementById('btn-list').classList.toggle('btn-outline-secondary', mode !== 'list');
    renderGenerations();
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

    if (pagination.current_page > 1) {
        pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.current_page - 1}); return false;">Previous</a></li>`;
    }

    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            pages += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
        } else {
            pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a></li>`;
        }
    }

    if (pagination.current_page < pagination.last_page) {
        pages += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${pagination.current_page + 1}); return false;">Next</a></li>`;
    }

    paginationEl.innerHTML = pages;
}

function goToPage(page) {
    currentPage = page;
    loadGenerations();
}

function applyFilters() {
    filters = {
        type: document.getElementById('filter-type').value,
        status: document.getElementById('filter-status').value,
        provider_id: document.getElementById('filter-provider').value,
        is_favorite: document.getElementById('filter-favorite').value,
        search: document.getElementById('filter-search').value
    };
    currentPage = 1;
    loadGenerations();
}

async function viewGeneration(generationId) {
    const gen = generations.find(g => g.id === generationId);
    if (!gen) return;

    currentGenerationId = generationId;

    const details = document.getElementById('generation-details');
    details.innerHTML = `
        <div class="row">
            <div class="col-lg-6">
                <div class="text-center mb-4">
                    ${gen.file_url ?
                        gen.type === 'video' ?
                            `<video controls class="preview-large" src="${gen.file_url}"></video>` :
                            `<img class="preview-large" src="${gen.file_url}" alt="Generation">` :
                        `<div class="preview-large d-flex align-items-center justify-content-center bg-light" style="height: 400px;">
                            <i class="fas fa-${gen.type === 'video' ? 'video' : 'image'} fa-5x text-muted"></i>
                        </div>`
                    }
                </div>
                ${gen.file_url ? `
                <div class="text-center">
                    <a href="${gen.file_url}" download class="btn btn-primary">
                        <i class="fas fa-download"></i> Download
                    </a>
                </div>
                ` : ''}
            </div>
            <div class="col-lg-6">
                <div class="detail-grid">
                    <div class="detail-card">
                        <h6><i class="fas fa-info-circle"></i> Basic Info</h6>
                        <div class="detail-row">
                            <span class="text-muted">ID:</span>
                            <strong>#${gen.id}</strong>
                        </div>
                        <div class="detail-row">
                            <span class="text-muted">Type:</span>
                            <span class="generation-type-badge ${gen.type}">${gen.type}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-muted">Status:</span>
                            <span class="status-badge ${gen.status}">${gen.status}</span>
                        </div>
                        <div class="detail-row">
                            <span class="text-muted">Provider:</span>
                            <strong>${gen.provider_name}</strong>
                        </div>
                    </div>

                    <div class="detail-card">
                        <h6><i class="fas fa-user"></i> User Info</h6>
                        <div class="detail-row">
                            <span class="text-muted">Name:</span>
                            <strong>${gen.user_name}</strong>
                        </div>
                        <div class="detail-row">
                            <span class="text-muted">Email:</span>
                            <strong>${gen.user_email}</strong>
                        </div>
                        <div class="detail-row">
                            <span class="text-muted">Favorite:</span>
                            ${gen.is_favorite ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-muted"></i>'}
                        </div>
                    </div>
                </div>

                <div class="detail-card mt-3">
                    <h6><i class="fas fa-comment-dots"></i> Prompt</h6>
                    <p class="mb-0">${escapeHtml(gen.prompt)}</p>
                </div>

                ${gen.parameters ? `
                <div class="detail-card mt-3">
                    <h6><i class="fas fa-sliders-h"></i> Parameters</h6>
                    <pre class="mb-0"><code>${JSON.stringify(gen.parameters, null, 2)}</code></pre>
                </div>
                ` : ''}

                <div class="detail-card mt-3">
                    <h6><i class="fas fa-clock"></i> Timestamps</h6>
                    <div class="detail-row">
                        <span class="text-muted">Created:</span>
                        <strong>${formatDateTime(gen.created_at)}</strong>
                    </div>
                    ${gen.completed_at ? `
                    <div class="detail-row">
                        <span class="text-muted">Completed:</span>
                        <strong>${formatDateTime(gen.completed_at)}</strong>
                    </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;

    $('#viewModal').modal('show');
}

async function deleteGeneration() {
    if (!currentGenerationId) return;

    if (!confirm('Are you sure you want to delete this generation? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch(`/admin/ai-gen/generations/${currentGenerationId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            $('#viewModal').modal('hide');
            loadGenerations();
            showSuccess('Generation deleted successfully!');
        } else {
            showError(result.error || 'Failed to delete generation');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to delete generation');
    }
}

function formatDate(datetime) {
    const date = new Date(datetime);
    return date.toLocaleDateString('th-TH', { month: 'short', day: 'numeric' });
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

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    setView('grid');
    loadProviders();
    loadGenerations();
});

// Search on Enter
document.getElementById('filter-search').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
        applyFilters();
    }
});
</script>
@endpush
