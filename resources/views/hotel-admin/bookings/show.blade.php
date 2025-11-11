@extends('layouts.hotel-admin')

@section('title', 'Booking Details')
@section('page-title', 'Booking Details')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-2"><i class="fas fa-file-alt me-2"></i>Booking #{{ $id ?? 'XXXXX' }}</h2>
                            <p class="mb-0 opacity-75">View and manage booking details</p>
                        </div>
                        <div>
                            <a href="{{ route('hotel-admin.bookings.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left me-2"></i>Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Cards --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                    <h6>Status</h6>
                    <span class="badge bg-warning">Pending</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x text-success mb-2"></i>
                    <h6>Total Amount</h6>
                    <h4 class="mb-0">฿0.00</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-bed fa-2x text-info mb-2"></i>
                    <h6>Rooms</h6>
                    <h4 class="mb-0">1</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-moon fa-2x text-purple mb-2"></i>
                    <h6>Nights</h6>
                    <h4 class="mb-0">1</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Guest Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Name</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Phone</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Number of Guests</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-hotel me-2"></i>Booking Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Hotel</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Room Type</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Check-In</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Check-Out</label>
                            <p class="mb-0 fw-bold">-</p>
                        </div>
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Special Requests</label>
                            <p class="mb-0">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success">
                            <i class="fas fa-check me-2"></i>Confirm Booking
                        </button>
                        <button class="btn btn-info">
                            <i class="fas fa-door-open me-2"></i>Check In
                        </button>
                        <button class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Edit Booking
                        </button>
                        <button class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Cancel Booking
                        </button>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Activity Log</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <p class="text-muted small text-center">No activity yet</p>
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
.text-purple {
    color: #764ba2;
}
</style>
@endsection
