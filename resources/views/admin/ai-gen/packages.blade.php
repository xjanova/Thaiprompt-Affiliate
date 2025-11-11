@extends('layouts.admin')

@section('title', 'AI Gen Packages Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-box text-primary"></i> Packages Management
            </h1>
            <p class="text-muted mb-0">จัดการแพคเกจสำหรับขายและ Subscription</p>
        </div>
        <button class="btn btn-primary" data-toggle="modal" data-target="#packageModal">
            <i class="fas fa-plus"></i> Add Package
        </button>
    </div>

    <!-- Packages Grid -->
    <div class="row" id="packages-grid">
        <div class="col-12 text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="text-muted mt-3">Loading packages...</p>
        </div>
    </div>
</div>

<!-- Package Modal (Add/Edit) -->
<div class="modal fade" id="packageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-box"></i> <span id="modal-title">Add New Package</span>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="packageForm">
                <input type="hidden" id="package-id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Package Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Slug *</label>
                                <input type="text" class="form-control" name="slug" required>
                                <small class="form-text text-muted">URL-friendly name</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price (฿) *</label>
                                <input type="number" class="form-control" name="price" min="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Image Credits *</label>
                                <input type="number" class="form-control" name="image_credits" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Video Credits *</label>
                                <input type="number" class="form-control" name="video_credits" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Duration (Days) *</label>
                                <input type="number" class="form-control" name="duration_days" min="1" value="30" required>
                                <small class="form-text text-muted">How long the package is valid</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Sort Order</label>
                                <input type="number" class="form-control" name="sort_order" value="0">
                                <small class="form-text text-muted">Display order (lower first)</small>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Features (one per line)</label>
                        <textarea class="form-control" name="features" rows="4" placeholder="Feature 1&#10;Feature 2&#10;Feature 3"></textarea>
                        <small class="form-text text-muted">จะแสดงเป็น bullet points</small>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_recurring" name="is_recurring">
                                <label class="custom-control-label" for="is_recurring">
                                    Recurring Subscription
                                </label>
                            </div>
                            <small class="form-text text-muted">ต่ออายุอัตโนมัติ</small>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_popular" name="is_popular">
                                <label class="custom-control-label" for="is_popular">
                                    Popular Badge
                                </label>
                            </div>
                            <small class="form-text text-muted">แสดงป้าย "Popular"</small>
                        </div>
                        <div class="col-md-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                <label class="custom-control-label" for="is_active">
                                    Active
                                </label>
                            </div>
                            <small class="form-text text-muted">เปิดใช้งาน</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Package
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .package-card {
        transition: all 0.3s ease;
        border: 2px solid #e3e6f0;
        border-radius: 15px;
        overflow: hidden;
    }

    .package-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .package-card.inactive {
        opacity: 0.6;
    }

    .package-card.popular {
        border-color: #f6c23e;
        box-shadow: 0 5px 15px rgba(246, 194, 62, 0.3);
    }

    .popular-badge {
        position: absolute;
        top: -10px;
        right: 20px;
        background: linear-gradient(135deg, #f6c23e 0%, #f4b942 100%);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        box-shadow: 0 4px 6px rgba(246, 194, 62, 0.4);
        transform: rotate(3deg);
    }

    .package-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px 20px;
        text-align: center;
    }

    .package-card.inactive .package-header {
        background: linear-gradient(135deg, #858796 0%, #5a5c69 100%);
    }

    .package-price {
        font-size: 3rem;
        font-weight: 800;
        line-height: 1;
        margin: 10px 0;
    }

    .package-price-currency {
        font-size: 1.5rem;
        vertical-align: super;
    }

    .package-duration {
        font-size: 0.9rem;
        opacity: 0.9;
    }

    .credits-display {
        background: #f8f9fc;
        border-radius: 10px;
        padding: 15px;
        margin: 15px 0;
    }

    .credit-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #e3e6f0;
    }

    .credit-item:last-child {
        border-bottom: none;
    }

    .credit-icon {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }

    .credit-icon.image {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .credit-icon.video {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .features-list {
        list-style: none;
        padding: 0;
        margin: 15px 0;
    }

    .features-list li {
        padding: 8px 0;
        padding-left: 30px;
        position: relative;
    }

    .features-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #1cc88a;
        font-weight: bold;
        font-size: 1.2rem;
    }

    .stats-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        margin: 2px;
    }
</style>
@endpush

@push('scripts')
<script>
let packages = [];
let editingPackage = null;

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
            renderPackages();
        }
    } catch (error) {
        console.error('Error loading packages:', error);
        showError('Failed to load packages');
    }
}

function renderPackages() {
    const grid = document.getElementById('packages-grid');

    if (packages.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-box fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No packages created yet</h5>
                <p class="text-muted">Click "Add Package" to create your first package</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = packages.map(pkg => `
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card package-card ${pkg.is_active ? '' : 'inactive'} ${pkg.is_popular ? 'popular' : ''} h-100">
                ${pkg.is_popular ? '<div class="popular-badge">⭐ POPULAR</div>' : ''}

                <div class="package-header">
                    <h4 class="mb-0">${pkg.name}</h4>
                    <div class="package-price">
                        <span class="package-price-currency">฿</span>${parseFloat(pkg.price).toLocaleString()}
                    </div>
                    <div class="package-duration">
                        <i class="fas fa-calendar-alt"></i> ${pkg.duration_days} Days
                    </div>
                </div>

                <div class="card-body">
                    <div class="credits-display">
                        <div class="credit-item">
                            <div class="d-flex align-items-center">
                                <div class="credit-icon image">
                                    <i class="fas fa-image"></i>
                                </div>
                                <span class="ml-2">Image Credits</span>
                            </div>
                            <strong>${pkg.image_credits.toLocaleString()}</strong>
                        </div>
                        <div class="credit-item">
                            <div class="d-flex align-items-center">
                                <div class="credit-icon video">
                                    <i class="fas fa-video"></i>
                                </div>
                                <span class="ml-2">Video Credits</span>
                            </div>
                            <strong>${pkg.video_credits.toLocaleString()}</strong>
                        </div>
                    </div>

                    ${pkg.description ? `<p class="text-muted small mb-2">${pkg.description}</p>` : ''}

                    ${pkg.features && pkg.features.length > 0 ? `
                        <ul class="features-list small">
                            ${pkg.features.slice(0, 3).map(feature => `<li>${feature}</li>`).join('')}
                            ${pkg.features.length > 3 ? `<li class="text-muted">+${pkg.features.length - 3} more...</li>` : ''}
                        </ul>
                    ` : ''}

                    <div class="mt-3">
                        ${pkg.is_recurring ? '<span class="stats-badge bg-info text-white">Recurring</span>' : ''}
                        <span class="stats-badge ${pkg.is_active ? 'bg-success' : 'bg-secondary'} text-white">
                            ${pkg.is_active ? 'Active' : 'Inactive'}
                        </span>
                    </div>
                </div>

                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">
                            <i class="fas fa-users"></i> ${pkg.subscribers_count || 0} subscribers
                        </small>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editPackage(${pkg.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-outline-danger" onclick="deletePackage(${pkg.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Form handling
document.getElementById('packageForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const packageId = document.getElementById('package-id').value;

    const data = {
        name: formData.get('name'),
        slug: formData.get('slug'),
        description: formData.get('description'),
        price: parseFloat(formData.get('price')),
        image_credits: parseInt(formData.get('image_credits')),
        video_credits: parseInt(formData.get('video_credits')),
        duration_days: parseInt(formData.get('duration_days')),
        sort_order: parseInt(formData.get('sort_order') || 0),
        features: formData.get('features') ? formData.get('features').split('\n').filter(f => f.trim()) : [],
        is_recurring: formData.has('is_recurring'),
        is_popular: formData.has('is_popular'),
        is_active: formData.has('is_active')
    };

    try {
        const url = packageId ? `/admin/ai-gen/packages/${packageId}` : '/admin/ai-gen/packages';
        const method = packageId ? 'PUT' : 'POST';

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
            $('#packageModal').modal('hide');
            e.target.reset();
            loadPackages();
            showSuccess(packageId ? 'Package updated successfully!' : 'Package created successfully!');
        } else {
            showError(result.error || 'Failed to save package');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to save package');
    }
});

function editPackage(packageId) {
    const pkg = packages.find(p => p.id === packageId);
    if (!pkg) return;

    document.getElementById('modal-title').textContent = 'Edit Package';
    document.getElementById('package-id').value = pkg.id;
    document.querySelector('[name="name"]').value = pkg.name;
    document.querySelector('[name="slug"]').value = pkg.slug;
    document.querySelector('[name="description"]').value = pkg.description || '';
    document.querySelector('[name="price"]').value = pkg.price;
    document.querySelector('[name="image_credits"]').value = pkg.image_credits;
    document.querySelector('[name="video_credits"]').value = pkg.video_credits;
    document.querySelector('[name="duration_days"]').value = pkg.duration_days;
    document.querySelector('[name="sort_order"]').value = pkg.sort_order || 0;
    document.querySelector('[name="features"]').value = (pkg.features || []).join('\n');
    document.getElementById('is_recurring').checked = pkg.is_recurring;
    document.getElementById('is_popular').checked = pkg.is_popular;
    document.getElementById('is_active').checked = pkg.is_active;

    $('#packageModal').modal('show');
}

async function deletePackage(packageId) {
    if (!confirm('Are you sure you want to delete this package? This action cannot be undone.')) {
        return;
    }

    try {
        const response = await fetch(`/admin/ai-gen/packages/${packageId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            loadPackages();
            showSuccess('Package deleted successfully!');
        } else {
            showError(result.error || 'Failed to delete package');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to delete package');
    }
}

// Reset form when modal is closed
$('#packageModal').on('hidden.bs.modal', function () {
    document.getElementById('modal-title').textContent = 'Add New Package';
    document.getElementById('package-id').value = '';
    document.getElementById('packageForm').reset();
    document.getElementById('is_active').checked = true;
});

function showSuccess(message) {
    // TODO: Implement with your notification system
    alert(message);
}

function showError(message) {
    // TODO: Implement with your notification system
    alert(message);
}

// Load packages on page load
document.addEventListener('DOMContentLoaded', loadPackages);
</script>
@endpush
