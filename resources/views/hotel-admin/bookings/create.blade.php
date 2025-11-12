@extends('layouts.hotel-admin')

@section('title', 'Create Booking')
@section('page-title', 'New Booking')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <h2 class="mb-2"><i class="fas fa-plus-circle me-2"></i>Create New Booking</h2>
                    <p class="mb-0 opacity-75">Add a new hotel booking to the system</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('hotel-admin.bookings.store') }}">
                        @csrf

                        <h5 class="mb-3">Guest Information</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Guest Name *</label>
                                <input type="text" name="guest_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" name="guest_email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Phone *</label>
                                <input type="tel" name="guest_phone" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Number of Guests</label>
                                <input type="number" name="number_of_guests" class="form-control" min="1" value="1">
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Booking Details</h5>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Hotel *</label>
                                <select name="hotel_id" class="form-select" required>
                                    <option value="">Select Hotel</option>
                                    {{-- Add hotel options here --}}
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Room Type *</label>
                                <select name="room_type_id" class="form-select" required>
                                    <option value="">Select Room Type</option>
                                    {{-- Add room type options here --}}
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Check-In Date *</label>
                                <input type="date" name="check_in_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Check-Out Date *</label>
                                <input type="date" name="check_out_date" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Number of Rooms</label>
                                <input type="number" name="number_of_rooms" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Special Requests</label>
                            <textarea name="special_requests" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Booking
                            </button>
                            <a href="{{ route('hotel-admin.bookings.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Tips</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Verify guest details</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Check room availability</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Confirm booking dates</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Review special requests</li>
                    </ul>
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
