@extends('layouts.hotel-admin')

@section('title', 'Hotel Details')
@section('page-title', 'Hotel Details')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2"><i class="fas fa-hotel me-2"></i>Hotel Details</h2>
                            <p class="mb-0 opacity-75">View and manage hotel information</p>
                        </div>
                        <div>
                            <a href="{{ route('hotel-admin.hotels.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                    <h4 class="mb-0">0</h4>
                    <p class="text-muted mb-0">Total Bookings</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                    <h4 class="mb-0">฿0</h4>
                    <p class="text-muted mb-0">Total Revenue</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                    <h4 class="mb-0">0.0</h4>
                    <p class="text-muted mb-0">Rating</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-comment fa-2x text-info mb-2"></i>
                    <h4 class="mb-0">0</h4>
                    <p class="text-muted mb-0">Reviews</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            {{-- Hotel Information --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hotel Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Hotel Name</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Star Rating</label>
                            <p class="mb-0">
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-warning"></i>
                                <i class="fas fa-star text-muted"></i>
                                <i class="fas fa-star text-muted"></i>
                            </p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Description</label>
                            <p class="mb-0">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Phone</label>
                            <p class="mb-0">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0">-</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Location --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Address</label>
                            <p class="mb-0">-</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">City</label>
                            <p class="mb-0">-</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Province</label>
                            <p class="mb-0">-</p>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="text-muted small">Postal Code</label>
                            <p class="mb-0">-</p>
                        </div>
                    </div>
                    {{-- Map placeholder --}}
                    <div class="mt-3" style="height: 300px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <p class="text-muted mb-0"><i class="fas fa-map me-2"></i>Map view will appear here</p>
                    </div>
                </div>
            </div>

            {{-- Rooms --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Rooms</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center py-3">No rooms added yet</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            {{-- Actions --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Hotel
                        </button>
                        <button class="btn btn-success">
                            <i class="fas fa-bed me-2"></i>Manage Rooms
                        </button>
                        <button class="btn btn-info">
                            <i class="fas fa-images me-2"></i>Manage Gallery
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-cog me-2"></i>Settings
                        </button>
                        <button class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Hotel
                        </button>
                    </div>
                </div>
            </div>

            {{-- Status --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Status</label>
                        <p class="mb-0"><span class="badge bg-success">Active</span></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Featured</label>
                        <p class="mb-0"><span class="badge bg-secondary">No</span></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Created</label>
                        <p class="mb-0 small">-</p>
                    </div>
                    <div class="mb-0">
                        <label class="text-muted small">Last Updated</label>
                        <p class="mb-0 small">-</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
@endsection
