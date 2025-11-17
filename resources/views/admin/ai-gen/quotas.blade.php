@extends('layouts.admin-v3')

@section('title', 'AI Gen Free Quotas')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-gift text-success"></i> Free Quotas Management
            </h1>
            <p class="text-muted mb-0">จัดการ Quota ฟรีรายวันและรายเดือนสำหรับผู้ใช้</p>
        </div>
        <button class="btn btn-success" data-toggle="modal" data-target="#quotaModal">
            <i class="fas fa-plus"></i> Add Quota Setting
        </button>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info shadow">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-2x mr-3"></i>
            <div>
                <strong>เกี่ยวกับ Free Quotas:</strong><br>
                ตั้งค่า quota ฟรีสำหรับผู้ใช้แต่ละ role เพื่อให้สามารถทดลองใช้งาน AI Gen ได้โดยไม่ต้องซื้อแพคเกจ
            </div>
        </div>
    </div>

    <!-- Current Quotas -->
    <div class="row mb-4" id="quotas-grid">
        <div class="col-12 text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="text-muted mt-3">Loading quotas...</p>
        </div>
    </div>

    <!-- Usage Stats -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-chart-bar"></i> Free Quota Usage Statistics
            </h6>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-primary h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Today's Free Usage
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-today">-</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-day fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-success h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        This Month
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-month">-</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-alt fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-info h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Active Users
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-users">-</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card border-left-warning h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Free Gens
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-900 dark:text-white" id="stat-total">-</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-gift fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="chart-area">
                <canvas id="usageChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Quota Modal -->
<div class="modal fade" id="quotaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-gift"></i> <span id="modal-title">Add Quota Setting</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="quotaForm">
                <input type="hidden" id="quota-id">
                <div class="modal-body">
                    <div class="alert alert-light border">
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> กำหนด quota สำหรับแต่ละ role ของผู้ใช้
                            (ตัวอย่าง: user, subscriber, vip, หรือ all สำหรับทุก role)
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Role *</label>
                        <input type="text" class="form-control" name="role" required placeholder="e.g., user, subscriber, vip, all">
                        <small class="form-text text-muted">ใช้ "all" สำหรับทุก role</small>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold mb-3">
                                <i class="fas fa-image text-primary"></i> Image Generation Quotas
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Daily Image Quota</label>
                                <input type="number" class="form-control" name="free_image_daily" min="0" value="0">
                                <small class="form-text text-muted">จำนวนภาพฟรีต่อวัน</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monthly Image Quota</label>
                                <input type="number" class="form-control" name="free_image_monthly" min="0" value="0">
                                <small class="form-text text-muted">จำนวนภาพฟรีต่อเดือน</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <h6 class="font-weight-bold mb-3">
                                <i class="fas fa-video text-danger"></i> Video Generation Quotas
                            </h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Daily Video Quota</label>
                                <input type="number" class="form-control" name="free_video_daily" min="0" value="0">
                                <small class="form-text text-muted">จำนวนวีดีโอฟรีต่อวัน</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monthly Video Quota</label>
                                <input type="number" class="form-control" name="free_video_monthly" min="0" value="0">
                                <small class="form-text text-muted">จำนวนวีดีโอฟรีต่อเดือน</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">
                                Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Quota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .quota-card {
        border-radius: 15px;
        border: 2px solid #e3e6f0;
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .quota-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .quota-card.inactive {
        opacity: 0.6;
    }

    .quota-header {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
        color: white;
        padding: 20px;
        text-align: center;
    }

    .quota-card.inactive .quota-header {
        background: linear-gradient(135deg, #858796 0%, #5a5c69 100%);
    }

    .role-badge {
        display: inline-block;
        padding: 8px 20px;
        background: rgba(255,255,255,0.2);
        border-radius: 20px;
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    .quota-item {
        padding: 15px;
        border-bottom: 1px solid #e3e6f0;
    }

    .quota-item:last-child {
        border-bottom: none;
    }

    .quota-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .quota-icon.image {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .quota-icon.video {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .quota-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #1cc88a;
    }

    .quota-label {
        font-size: 0.85rem;
        color: #858796;
        font-weight: 600;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
let quotas = [];
let usageChart = null;

async function loadQuotas() {
    try {
        const response = await fetch('/admin/ai-gen/quotas', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const data = await response.json();

        if (data.success) {
            quotas = data.data.quotas || [];
            updateStats(data.data.stats || {});
            renderQuotas();
            renderChart(data.data.chart_data || {});
        }
    } catch (error) {
        console.error('Error loading quotas:', error);
        showError('Failed to load quotas');
    }
}

function renderQuotas() {
    const grid = document.getElementById('quotas-grid');

    if (quotas.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-gift fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No quota settings yet</h5>
                <p class="text-muted">Click "Add Quota Setting" to create your first quota</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = quotas.map(quota => `
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card quota-card ${quota.is_active ? '' : 'inactive'} h-100">
                <div class="quota-header">
                    <div class="role-badge">${quota.role}</div>
                    <div class="text-white small">
                        ${quota.is_active ? '<i class="fas fa-check-circle"></i> Active' : '<i class="fas fa-times-circle"></i> Inactive'}
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Image Quotas -->
                    <div class="quota-item">
                        <div class="d-flex align-items-center">
                            <div class="quota-icon image mr-3">
                                <i class="fas fa-image"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="quota-label">Image Daily</div>
                                <div class="quota-value">${quota.free_image_daily}</div>
                            </div>
                        </div>
                    </div>
                    <div class="quota-item">
                        <div class="d-flex align-items-center">
                            <div class="quota-icon image mr-3">
                                <i class="fas fa-image"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="quota-label">Image Monthly</div>
                                <div class="quota-value">${quota.free_image_monthly}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Video Quotas -->
                    <div class="quota-item">
                        <div class="d-flex align-items-center">
                            <div class="quota-icon video mr-3">
                                <i class="fas fa-video"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="quota-label">Video Daily</div>
                                <div class="quota-value">${quota.free_video_daily}</div>
                            </div>
                        </div>
                    </div>
                    <div class="quota-item">
                        <div class="d-flex align-items-center">
                            <div class="quota-icon video mr-3">
                                <i class="fas fa-video"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="quota-label">Video Monthly</div>
                                <div class="quota-value">${quota.free_video_monthly}</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer glass-fusion" border border-white/20 dark:border-white/10>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-sm btn-primary" onclick="editQuota(${quota.id})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteQuota(${quota.id})">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function updateStats(stats) {
    document.getElementById('stat-today').textContent = (stats.today || 0).toLocaleString();
    document.getElementById('stat-month').textContent = (stats.this_month || 0).toLocaleString();
    document.getElementById('stat-users').textContent = (stats.active_users || 0).toLocaleString();
    document.getElementById('stat-total').textContent = (stats.total_free || 0).toLocaleString();
}

function renderChart(chartData) {
    const ctx = document.getElementById('usageChart');

    if (usageChart) {
        usageChart.destroy();
    }

    usageChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels || [],
            datasets: [{
                label: 'Free Image Generations',
                data: chartData.images || [],
                borderColor: '#4e73df',
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.3
            }, {
                label: 'Free Video Generations',
                data: chartData.videos || [],
                borderColor: '#e74a3b',
                backgroundColor: 'rgba(231, 74, 59, 0.1)',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Free Quota Usage (Last 30 Days)'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Form handling
document.getElementById('quotaForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const quotaId = document.getElementById('quota-id').value;

    const data = {
        role: formData.get('role'),
        free_image_daily: parseInt(formData.get('free_image_daily')),
        free_image_monthly: parseInt(formData.get('free_image_monthly')),
        free_video_daily: parseInt(formData.get('free_video_daily')),
        free_video_monthly: parseInt(formData.get('free_video_monthly')),
        is_active: formData.has('is_active')
    };

    try {
        const url = quotaId ? `/admin/ai-gen/quotas/${quotaId}` : '/admin/ai-gen/quotas';
        const method = quotaId ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            $('#quotaModal').modal('hide');
            e.target.reset();
            loadQuotas();
            showSuccess(quotaId ? 'Quota updated successfully!' : 'Quota created successfully!');
        } else {
            showError(result.error || 'Failed to save quota');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to save quota');
    }
});

function editQuota(quotaId) {
    const quota = quotas.find(q => q.id === quotaId);
    if (!quota) return;

    document.getElementById('modal-title').textContent = 'Edit Quota Setting';
    document.getElementById('quota-id').value = quota.id;
    document.querySelector('[name="role"]').value = quota.role;
    document.querySelector('[name="free_image_daily"]').value = quota.free_image_daily;
    document.querySelector('[name="free_image_monthly"]').value = quota.free_image_monthly;
    document.querySelector('[name="free_video_daily"]').value = quota.free_video_daily;
    document.querySelector('[name="free_video_monthly"]').value = quota.free_video_monthly;
    document.getElementById('is_active').checked = quota.is_active;

    $('#quotaModal').modal('show');
}

async function deleteQuota(quotaId) {
    if (!confirm('Are you sure you want to delete this quota setting?')) {
        return;
    }

    try {
        const response = await fetch(`/admin/ai-gen/quotas/${quotaId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            loadQuotas();
            showSuccess('Quota deleted successfully!');
        } else {
            showError(result.error || 'Failed to delete quota');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to delete quota');
    }
}

// Reset form when modal is closed
$('#quotaModal').on('hidden.bs.modal', function () {
    document.getElementById('modal-title').textContent = 'Add Quota Setting';
    document.getElementById('quota-id').value = '';
    document.getElementById('quotaForm').reset();
    document.getElementById('is_active').checked = true;
});

function showSuccess(message) {
    alert(message);
}

function showError(message) {
    alert(message);
}

// Load quotas on page load
document.addEventListener('DOMContentLoaded', loadQuotas);
</script>
@endpush
