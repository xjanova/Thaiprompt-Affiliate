@extends('layouts.hotel-admin')

@section('title', 'Hotels Management')
@section('page-title', 'Hotels')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2"><i class="fas fa-building me-2"></i>Hotels Management</h2>
                            <p class="mb-0 opacity-75">Manage all hotels in the system</p>
                        </div>
                        <a href="{{ route('hotel-admin.hotels.create') }}" class="btn btn-light">
                            <i class="fas fa-plus me-2"></i>Add New Hotel
                        </a>
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
                    <i class="fas fa-hotel fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Total Hotels</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Active Hotels</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-pause-circle fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Inactive Hotels</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-star fa-3x text-info mb-3"></i>
                    <h3 class="mb-0">0.0</h3>
                    <p class="text-muted mb-0">Avg Rating</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Hotels Grid --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Sample Hotel Card - replace with actual data --}}
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-building fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Hotels Found</h4>
                            <p class="text-muted">Add your first hotel to get started</p>
                            <a href="{{ route('hotel-admin.hotels.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Hotel
                            </a>
                        </div>
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
