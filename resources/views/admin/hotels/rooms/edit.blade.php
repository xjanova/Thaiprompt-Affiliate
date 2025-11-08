@extends('layouts.admin')

@section('title', 'แก้ไขห้องพัก - ' . $roomType->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 mb-2">แก้ไขห้องพัก: {{ $roomType->name }}</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.index') }}">โรงแรม</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.show', $hotel->id) }}">{{ $hotel->name }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}">ห้องพัก</a></li>
                <li class="breadcrumb-item active">แก้ไข</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('admin.hotels.rooms.update', [$hotel->id, $roomType->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลพื้นฐาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="name">ชื่อห้องพัก <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $roomType->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">รายละเอียดห้องพัก <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="6" required>{{ old('description', $roomType->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="size_sqm">ขนาดห้อง (ตร.ม.) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('size_sqm') is-invalid @enderror"
                                           id="size_sqm" name="size_sqm" value="{{ old('size_sqm', $roomType->size_sqm) }}" step="0.01" required>
                                    @error('size_sqm')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="bed_type">ประเภทเตียง</label>
                                    <select class="form-control @error('bed_type') is-invalid @enderror" id="bed_type" name="bed_type">
                                        <option value="">-- เลือกประเภทเตียง --</option>
                                        <option value="single" {{ old('bed_type', $roomType->bed_type) == 'single' ? 'selected' : '' }}>Single Bed</option>
                                        <option value="twin" {{ old('bed_type', $roomType->bed_type) == 'twin' ? 'selected' : '' }}>Twin Beds</option>
                                        <option value="double" {{ old('bed_type', $roomType->bed_type) == 'double' ? 'selected' : '' }}>Double Bed</option>
                                        <option value="queen" {{ old('bed_type', $roomType->bed_type) == 'queen' ? 'selected' : '' }}>Queen Bed</option>
                                        <option value="king" {{ old('bed_type', $roomType->bed_type) == 'king' ? 'selected' : '' }}>King Bed</option>
                                    </select>
                                    @error('bed_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="view_type">วิวจากห้อง</label>
                                    <select class="form-control @error('view_type') is-invalid @enderror" id="view_type" name="view_type">
                                        <option value="">-- เลือกวิว --</option>
                                        <option value="city" {{ old('view_type', $roomType->view_type) == 'city' ? 'selected' : '' }}>City View</option>
                                        <option value="sea" {{ old('view_type', $roomType->view_type) == 'sea' ? 'selected' : '' }}>Sea View</option>
                                        <option value="mountain" {{ old('view_type', $roomType->view_type) == 'mountain' ? 'selected' : '' }}>Mountain View</option>
                                        <option value="garden" {{ old('view_type', $roomType->view_type) == 'garden' ? 'selected' : '' }}>Garden View</option>
                                        <option value="pool" {{ old('view_type', $roomType->view_type) == 'pool' ? 'selected' : '' }}>Pool View</option>
                                    </select>
                                    @error('view_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pricing -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> ราคา</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="base_price">ราคาพื้นฐาน (บาท/คืน) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('base_price') is-invalid @enderror"
                                           id="base_price" name="base_price" value="{{ old('base_price', $roomType->base_price) }}" step="0.01" required>
                                    @error('base_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="weekend_price">ราคาวันหยุด (บาท/คืน)</label>
                                    <input type="number" class="form-control @error('weekend_price') is-invalid @enderror"
                                           id="weekend_price" name="weekend_price" value="{{ old('weekend_price', $roomType->weekend_price) }}" step="0.01">
                                    @error('weekend_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Capacity -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users"></i> จำนวนผู้เข้าพัก</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_adults">ผู้ใหญ่สูงสุด <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('max_adults') is-invalid @enderror"
                                           id="max_adults" name="max_adults" value="{{ old('max_adults', $roomType->max_adults) }}" min="1" required>
                                    @error('max_adults')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_children">เด็กสูงสุด</label>
                                    <input type="number" class="form-control @error('max_children') is-invalid @enderror"
                                           id="max_children" name="max_children" value="{{ old('max_children', $roomType->max_children) }}" min="0">
                                    @error('max_children')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_rooms">จำนวนห้องทั้งหมด <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_rooms') is-invalid @enderror"
                                           id="total_rooms" name="total_rooms" value="{{ old('total_rooms', $roomType->total_rooms) }}" min="1" required>
                                    @error('total_rooms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-concierge-bell"></i> สิ่งอำนวยความสะดวกในห้อง</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $currentAmenities = $roomType->amenities ?? [];
                            $amenitiesList = [
                                ['id' => 'wifi', 'label' => 'Free WiFi', 'icon' => 'wifi'],
                                ['id' => 'ac', 'label' => 'Air Conditioning', 'icon' => 'snowflake'],
                                ['id' => 'tv', 'label' => 'TV', 'icon' => 'tv'],
                                ['id' => 'minibar', 'label' => 'Minibar', 'icon' => 'glass-martini'],
                                ['id' => 'safe', 'label' => 'Safe', 'icon' => 'lock'],
                                ['id' => 'balcony', 'label' => 'Balcony', 'icon' => 'door-open'],
                                ['id' => 'bathtub', 'label' => 'Bathtub', 'icon' => 'bath'],
                                ['id' => 'desk', 'label' => 'Desk', 'icon' => 'desk'],
                                ['id' => 'coffee', 'label' => 'Coffee Maker', 'icon' => 'coffee'],
                                ['id' => 'hairdryer', 'label' => 'Hairdryer', 'icon' => 'wind'],
                                ['id' => 'shower', 'label' => 'Shower', 'icon' => 'shower'],
                                ['id' => 'phone', 'label' => 'Telephone', 'icon' => 'phone'],
                            ];
                        @endphp
                        <div class="row">
                            @foreach($amenitiesList as $amenity)
                                <div class="col-md-4 col-sm-6">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="amenity_{{ $amenity['id'] }}"
                                               name="amenities[]" value="{{ $amenity['label'] }}"
                                               {{ in_array($amenity['label'], $currentAmenities) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="amenity_{{ $amenity['id'] }}">
                                            <i class="fas fa-{{ $amenity['icon'] }}"></i> {{ $amenity['label'] }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Current Images -->
                @if($roomType->main_image || ($roomType->gallery_images && count($roomType->gallery_images) > 0))
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-images"></i> รูปภาพปัจจุบัน</h5>
                        </div>
                        <div class="card-body">
                            @if($roomType->main_image)
                                <div class="mb-3">
                                    <label class="d-block mb-2"><strong>รูปหลัก:</strong></label>
                                    <img src="{{ $roomType->main_image_url }}" alt="{{ $roomType->name }}" class="img-fluid rounded">
                                </div>
                            @endif
                            @if($roomType->gallery_images && count($roomType->gallery_images) > 0)
                                <div>
                                    <label class="d-block mb-2"><strong>แกลเลอรี่:</strong></label>
                                    <div class="row g-2">
                                        @foreach($roomType->gallery_images as $image)
                                            <div class="col-6">
                                                <img src="{{ Storage::url($image) }}" alt="Gallery" class="img-fluid rounded">
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Upload New Images -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-upload"></i> อัปโหลดรูปใหม่</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="main_image">รูปหลักใหม่</label>
                            <input type="file" class="form-control-file @error('main_image') is-invalid @enderror"
                                   id="main_image" name="main_image" accept="image/*">
                            @error('main_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">อัปโหลดเฉพาะเมื่อต้องการเปลี่ยนรูปหลัก</small>
                        </div>

                        <div class="form-group">
                            <label for="gallery_images">รูปแกลเลอรี่เพิ่มเติม</label>
                            <input type="file" class="form-control-file @error('gallery_images') is-invalid @enderror"
                                   id="gallery_images" name="gallery_images[]" accept="image/*" multiple>
                            @error('gallery_images')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">รูปใหม่จะถูกเพิ่มเข้าไปในแกลเลอรี่</small>
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> การตั้งค่า</h5>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', $roomType->is_active) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">
                                <strong>เปิดใช้งาน</strong>
                            </label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1"
                                   {{ old('is_featured', $roomType->is_featured) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_featured">
                                <strong>แนะนำพิเศษ</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> บันทึกการแก้ไข
                        </button>
                        <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i> ยกเลิก
                        </a>
                        <button type="button" class="btn btn-danger btn-block" onclick="deleteRoom()">
                            <i class="fas fa-trash"></i> ลบห้องพักนี้
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<form id="deleteForm" action="{{ route('admin.hotels.rooms.destroy', [$hotel->id, $roomType->id]) }}" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function deleteRoom() {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบห้องพักนี้? การกระทำนี้ไม่สามารถยกเลิกได้')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>
@endsection
