@extends('layouts.hotel-admin')

@section('title', 'Hotel Settings')
@section('page-title', 'My Hotel Settings')

@section('content')
<div class="container-fluid p-4">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body bg-gradient-primary text-white rounded">
                    <h2 class="mb-2"><i class="fas fa-cog me-2"></i>Hotel Settings</h2>
                    <p class="mb-0 opacity-75">Manage your hotel information and settings</p>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('hotel-admin.my-hotel.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                {{-- Basic Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Hotel Name *</label>
                            <input type="text" name="name" class="form-control" value="" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="4" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Star Rating</label>
                                <select name="star_rating" class="form-select">
                                    <option value="1">1 Star</option>
                                    <option value="2">2 Stars</option>
                                    <option value="3">3 Stars</option>
                                    <option value="4">4 Stars</option>
                                    <option value="5">5 Stars</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" name="phone" class="form-control" value="">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Website</label>
                            <input type="url" name="website" class="form-control" value="">
                        </div>
                    </div>
                </div>

                {{-- Location Information --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Location</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Address *</label>
                            <textarea name="address" class="form-control" rows="2" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" name="city" class="form-control" value="">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Province</label>
                                <input type="text" name="province" class="form-control" value="">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" value="">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Latitude</label>
                                <input type="text" name="latitude" class="form-control" step="any" value="">
                                <small class="text-muted">For map location</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Longitude</label>
                                <input type="text" name="longitude" class="form-control" step="any" value="">
                                <small class="text-muted">For map location</small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Facilities & Amenities --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-swimming-pool me-2"></i>Facilities & Amenities</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="wifi" id="wifi">
                                    <label class="form-check-label" for="wifi">
                                        <i class="fas fa-wifi me-1"></i> Free WiFi
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="parking" id="parking">
                                    <label class="form-check-label" for="parking">
                                        <i class="fas fa-parking me-1"></i> Parking
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="pool" id="pool">
                                    <label class="form-check-label" for="pool">
                                        <i class="fas fa-swimming-pool me-1"></i> Swimming Pool
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="gym" id="gym">
                                    <label class="form-check-label" for="gym">
                                        <i class="fas fa-dumbbell me-1"></i> Gym
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="restaurant" id="restaurant">
                                    <label class="form-check-label" for="restaurant">
                                        <i class="fas fa-utensils me-1"></i> Restaurant
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="spa" id="spa">
                                    <label class="form-check-label" for="spa">
                                        <i class="fas fa-spa me-1"></i> Spa
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="bar" id="bar">
                                    <label class="form-check-label" for="bar">
                                        <i class="fas fa-glass-martini me-1"></i> Bar
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="room_service" id="room_service">
                                    <label class="form-check-label" for="room_service">
                                        <i class="fas fa-concierge-bell me-1"></i> Room Service
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="facilities[]" value="laundry" id="laundry">
                                    <label class="form-check-label" for="laundry">
                                        <i class="fas fa-soap me-1"></i> Laundry
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Policies --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Policies</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check-In Time</label>
                                <input type="time" name="check_in_time" class="form-control" value="14:00">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Check-Out Time</label>
                                <input type="time" name="check_out_time" class="form-control" value="12:00">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cancellation Policy</label>
                            <textarea name="cancellation_policy" class="form-control" rows="3" placeholder="Describe your cancellation policy..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">House Rules</label>
                            <textarea name="house_rules" class="form-control" rows="3" placeholder="Describe your house rules..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                {{-- Images --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-images me-2"></i>Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Main Image</label>
                            <div class="image-preview mb-2" style="height: 200px; background: #f0f0f0; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                            <input type="file" name="main_image" class="form-control" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gallery Images</label>
                            <input type="file" name="gallery_images[]" class="form-control" accept="image/*" multiple>
                            <small class="text-muted">Select multiple images</small>
                        </div>
                    </div>
                </div>

                {{-- Save Actions --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                            <a href="{{ route('hotel-admin.dashboard') }}" class="btn btn-secondary">
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
