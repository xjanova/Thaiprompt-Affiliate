@extends('layouts.admin-v3')

@section('title', 'AI Providers Management')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="fas fa-server text-primary"></i> AI Providers Management
            </h1>
            <p class="text-muted mb-0">จัดการ AI Providers และการเชื่อมต่อ API</p>
        </div>
        <button class="btn btn-primary" data-toggle="modal" data-target="#addProviderModal">
            <i class="fas fa-plus"></i> Add Provider
        </button>
    </div>

    <!-- Providers Grid -->
    <div class="row" id="providers-grid">
        <div class="col-12 text-center py-5">
            <i class="fas fa-spinner fa-spin fa-3x text-muted"></i>
            <p class="text-muted mt-3">Loading providers...</p>
        </div>
    </div>
</div>

<!-- Add Provider Modal -->
<div class="modal fade" id="addProviderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle"></i> Add New Provider
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addProviderForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Provider Name *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Slug *</label>
                        <input type="text" class="form-control" name="slug" required>
                        <small class="form-text text-muted">URL-friendly name (e.g., freepik, vidu)</small>
                    </div>
                    <div class="form-group">
                        <label>Type *</label>
                        <select class="form-control" name="type" required>
                            <option value="image">Image Only</option>
                            <option value="video">Video Only</option>
                            <option value="both">Both Image & Video</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Logo URL</label>
                        <input type="url" class="form-control" name="logo_url">
                    </div>
                    <div class="form-group">
                        <label>Supported Features</label>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="feat1" name="features[]" value="text-to-image">
                                    <label class="custom-control-label" for="feat1">Text to Image</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="feat2" name="features[]" value="text-to-video">
                                    <label class="custom-control-label" for="feat2">Text to Video</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="feat3" name="features[]" value="image-editing">
                                    <label class="custom-control-label" for="feat3">Image Editing</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                            <label class="custom-control-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Provider
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Config Modal -->
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-cog"></i> Provider Configuration
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="configForm">
                <input type="hidden" id="config-provider-id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Configure API credentials and settings for this provider
                    </div>

                    <div class="form-group">
                        <label>API Key *</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="api_key" name="api_key" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('api_key')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Will be encrypted in database</small>
                    </div>

                    <div class="form-group">
                        <label>API Endpoint *</label>
                        <input type="url" class="form-control" id="api_endpoint" name="api_endpoint" required>
                        <small class="form-text text-muted">Base URL for API requests</small>
                    </div>

                    <div id="additional-configs"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="testConnection()">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .provider-card {
        transition: all 0.3s ease;
        border: 2px solid #e3e6f0;
    }

    .provider-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .provider-card.inactive {
        opacity: 0.6;
    }

    .provider-logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
        border-radius: 10px;
        background: #f8f9fc;
        padding: 10px;
    }

    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
    }

    .status-dot.active {
        background: #1cc88a;
        box-shadow: 0 0 10px rgba(28, 200, 138, 0.5);
    }

    .status-dot.inactive {
        background: #e74a3b;
    }

    .feature-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        margin: 0.2rem;
        border-radius: 0.25rem;
    }
</style>
@endpush

@push('scripts')
<script>
let providers = [];

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
            renderProviders();
        }
    } catch (error) {
        console.error('Error loading providers:', error);
        showError('Failed to load providers');
    }
}

function renderProviders() {
    const grid = document.getElementById('providers-grid');

    if (providers.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fas fa-server fa-4x text-muted mb-3"></i>
                <h5 class="text-muted">No providers configured yet</h5>
                <p class="text-muted">Click "Add Provider" to get started</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = providers.map(provider => `
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card provider-card ${provider.is_active ? '' : 'inactive'} h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            ${provider.logo_url ?
                                `<img src="${provider.logo_url}" class="provider-logo" alt="${provider.name}">` :
                                `<div class="provider-logo">
                                    <i class="fas fa-server fa-2x text-muted"></i>
                                </div>`
                            }
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link text-muted" data-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item" onclick="openConfig(${provider.id})">
                                    <i class="fas fa-cog"></i> Configure
                                </a>
                                <a class="dropdown-item" onclick="testProvider(${provider.id})">
                                    <i class="fas fa-plug"></i> Test Connection
                                </a>
                                <a class="dropdown-item" onclick="toggleProvider(${provider.id})">
                                    <i class="fas fa-power-off"></i> ${provider.is_active ? 'Deactivate' : 'Activate'}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" onclick="deleteProvider(${provider.id})">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                    </div>

                    <h5 class="card-title mb-1">${provider.name}</h5>
                    <div class="mb-2">
                        <span class="status-dot ${provider.is_active ? 'active' : 'inactive'}"></span>
                        <span class="text-muted small">${provider.is_active ? 'Active' : 'Inactive'}</span>
                    </div>

                    <p class="text-muted small mb-3">${provider.description || 'No description'}</p>

                    <div class="mb-3">
                        <span class="badge badge-primary">${provider.type}</span>
                        ${(provider.supported_features || []).map(feature =>
                            `<span class="badge badge-secondary feature-badge">${feature}</span>`
                        ).join('')}
                    </div>

                    <div class="row text-center border-top pt-3">
                        <div class="col-6">
                            <div class="text-muted small">Generations</div>
                            <div class="font-weight-bold">${(provider.generations_count || 0).toLocaleString()}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Success Rate</div>
                            <div class="font-weight-bold">${provider.success_rate || 0}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Form submissions
document.getElementById('addProviderForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    // Handle features array
    data.supported_features = formData.getAll('features[]');
    data.is_active = formData.has('is_active');

    try {
        const response = await fetch('/admin/ai-gen/providers', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            $('#addProviderModal').modal('hide');
            e.target.reset();
            loadProviders();
            showSuccess('Provider added successfully!');
        } else {
            showError(result.error || 'Failed to add provider');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to add provider');
    }
});

document.getElementById('configForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const providerId = document.getElementById('config-provider-id').value;
    const formData = new FormData(e.target);

    const configs = [{
        key: 'api_key',
        value: formData.get('api_key'),
        is_encrypted: true
    }, {
        key: 'api_endpoint',
        value: formData.get('api_endpoint'),
        is_encrypted: false
    }];

    try {
        const response = await fetch(`/admin/ai-gen/providers/${providerId}/config`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ configs })
        });

        const result = await response.json();

        if (result.success) {
            $('#configModal').modal('hide');
            showSuccess('Configuration saved successfully!');
        } else {
            showError(result.error || 'Failed to save configuration');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to save configuration');
    }
});

function openConfig(providerId) {
    document.getElementById('config-provider-id').value = providerId;
    $('#configModal').modal('show');
}

async function testProvider(providerId) {
    try {
        const response = await fetch(`/admin/ai-gen/providers/${providerId}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            showSuccess('Connection successful!');
        } else {
            showError(result.message || 'Connection failed');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('Failed to test connection');
    }
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function showSuccess(message) {
    // Implement your success notification
    alert(message);
}

function showError(message) {
    // Implement your error notification
    alert(message);
}

// Load providers on page load
document.addEventListener('DOMContentLoaded', loadProviders);
</script>
@endpush
