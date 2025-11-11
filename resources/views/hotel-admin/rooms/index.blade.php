@extends('layouts.hotel-admin')

@section('title', 'Rooms Management')
@section('page-title', 'Rooms')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2"><i class="fas fa-bed me-2"></i>Room Management</h2>
                            <p class="mb-0 opacity-75">Manage your hotel rooms and availability</p>
                        </div>
                        <a href="{{ route('hotel-admin.rooms.create') }}" class="btn btn-light">
                            <i class="fas fa-plus me-2"></i>Add New Room
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
                    <i class="fas fa-door-open fa-3x text-primary mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Total Rooms</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Available</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-door-closed fa-3x text-warning mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Occupied</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-3x text-danger mb-3"></i>
                    <h3 class="mb-0">0</h3>
                    <p class="text-muted mb-0">Maintenance</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Rooms Grid --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-4">
                        {{-- Sample Room Card --}}
                        <div class="col-12 text-center py-5">
                            <i class="fas fa-bed fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No Rooms Found</h4>
                            <p class="text-muted">Add your first room to get started</p>
                            <a href="{{ route('hotel-admin.rooms.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Add New Room
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
