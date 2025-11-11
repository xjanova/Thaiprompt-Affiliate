@extends('layouts.hotel-admin')

@section('title', 'Create Room')
@section('page-title', 'New Room')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <h2 class="mb-2"><i class="fas fa-plus-circle me-2"></i>Add New Room</h2>
                    <p class="mb-0 opacity-75">Create a new room type for your hotel</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('hotel-admin.my-rooms.store') }}" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Room Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Room Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Deluxe Double Room" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Describe the room features and amenities..." required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Room Type</label>
                                <select name="room_type" class="form-select">
                                    <option value="single">Single</option>
                                    <option value="double">Double</option>
                                    <option value="twin">Twin</option>
                                    <option value="suite">Suite</option>
                                    <option value="family">Family</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Size (sq.m.)</label>
                                <input type="number" name="size" class="form-control" min="0" step="0.01">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Max Occupancy</label>
                                <input type="number" name="max_occupancy" class="form-control" min="1" value="2">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Number of Beds</label>
                                <input type="number" name="number_of_beds" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Bed Type</label>
                                <select name="bed_type" class="form-select">
                                    <option value="single">Single Bed</option>
                                    <option value="double">Double Bed</option>
                                    <option value="queen">Queen Bed</option>
                                    <option value="king">King Bed</option>
                                    <option value="twin">Twin Beds</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pricing --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign me-2"></i>Pricing</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Base Price (per night) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" name="base_price" class="form-control" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Weekend Price (per night)</label>
                                <div class="input-group">
                                    <span class="input-group-text">฿</span>
                                    <input type="number" name="weekend_price" class="form-control" min="0" step="0.01">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Amenities --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Room Amenities</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="wifi" id="amenity_wifi">
                                    <label class="form-check-label" for="amenity_wifi">
                                        <i class="fas fa-wifi me-1"></i> WiFi
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="tv" id="amenity_tv">
                                    <label class="form-check-label" for="amenity_tv">
                                        <i class="fas fa-tv me-1"></i> TV
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="ac" id="amenity_ac">
                                    <label class="form-check-label" for="amenity_ac">
                                        <i class="fas fa-snowflake me-1"></i> Air Conditioning
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="minibar" id="amenity_minibar">
                                    <label class="form-check-label" for="amenity_minibar">
                                        <i class="fas fa-glass-martini me-1"></i> Mini Bar
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="safe" id="amenity_safe">
                                    <label class="form-check-label" for="amenity_safe">
                                        <i class="fas fa-lock me-1"></i> Safe
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="balcony" id="amenity_balcony">
                                    <label class="form-check-label" for="amenity_balcony">
                                        <i class="fas fa-door-open me-1"></i> Balcony
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="bathtub" id="amenity_bathtub">
                                    <label class="form-check-label" for="amenity_bathtub">
                                        <i class="fas fa-bath me-1"></i> Bathtub
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="coffee" id="amenity_coffee">
                                    <label class="form-check-label" for="amenity_coffee">
                                        <i class="fas fa-coffee me-1"></i> Coffee Maker
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="amenities[]" value="sea_view" id="amenity_sea_view">
                                    <label class="form-check-label" for="amenity_sea_view">
                                        <i class="fas fa-water me-1"></i> Sea View
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Availability --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Availability</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Total Rooms</label>
                            <input type="number" name="total_rooms" class="form-control" min="1" value="1">
                            <small class="text-muted">Number of rooms of this type</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Active for Booking
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Images --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-images me-2"></i>Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Room Images</label>
                            <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">Select multiple images</small>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Create Room
                            </button>
                            <a href="{{ route('hotel-admin.rooms.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style>
@endsection
