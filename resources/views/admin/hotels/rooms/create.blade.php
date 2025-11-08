@extends('layouts.admin')

@section('title', 'เพิ่มห้องพัก - ' . $hotel->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="h3 mb-2">เพิ่มห้องพักใหม่</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.index') }}">โรงแรม</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.show', $hotel->id) }}">{{ $hotel->name }}</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}">ห้องพัก</a></li>
                <li class="breadcrumb-item active">เพิ่มห้องใหม่</li>
            </ol>
        </nav>
    </div>

    <form action="{{ route('admin.hotels.rooms.store', $hotel->id) }}" method="POST" enctype="multipart/form-data">
        @csrf

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
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">เช่น: Superior Room, Deluxe Suite, Family Room</small>
                        </div>

                        <div class="form-group">
                            <label for="description">รายละเอียดห้องพัก <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="6" required>{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="size_sqm">ขนาดห้อง (ตร.ม.) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('size_sqm') is-invalid @enderror"
                                           id="size_sqm" name="size_sqm" value="{{ old('size_sqm') }}" step="0.01" required>
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
                                        <option value="single" {{ old('bed_type') == 'single' ? 'selected' : '' }}>Single Bed (เตียงเดี่ยว)</option>
                                        <option value="twin" {{ old('bed_type') == 'twin' ? 'selected' : '' }}>Twin Beds (เตียงคู่แยก)</option>
                                        <option value="double" {{ old('bed_type') == 'double' ? 'selected' : '' }}>Double Bed (เตียงคู่)</option>
                                        <option value="queen" {{ old('bed_type') == 'queen' ? 'selected' : '' }}>Queen Bed (เตียงควีน)</option>
                                        <option value="king" {{ old('bed_type') == 'king' ? 'selected' : '' }}>King Bed (เตียงคิง)</option>
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
                                        <option value="city" {{ old('view_type') == 'city' ? 'selected' : '' }}>City View (วิวเมือง)</option>
                                        <option value="sea" {{ old('view_type') == 'sea' ? 'selected' : '' }}>Sea View (วิวทะเล)</option>
                                        <option value="mountain" {{ old('view_type') == 'mountain' ? 'selected' : '' }}>Mountain View (วิวภูเขา)</option>
                                        <option value="garden" {{ old('view_type') == 'garden' ? 'selected' : '' }}>Garden View (วิวสวน)</option>
                                        <option value="pool" {{ old('view_type') == 'pool' ? 'selected' : '' }}>Pool View (วิวสระน้ำ)</option>
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
                                           id="base_price" name="base_price" value="{{ old('base_price') }}" step="0.01" required>
                                    @error('base_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="weekend_price">ราคาวันหยุด (บาท/คืน)</label>
                                    <input type="number" class="form-control @error('weekend_price') is-invalid @enderror"
                                           id="weekend_price" name="weekend_price" value="{{ old('weekend_price') }}" step="0.01">
                                    @error('weekend_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">ราคาสำหรับวันเสาร์-อาทิตย์ (ถ้าไม่ระบุจะใช้ราคาพื้นฐาน)</small>
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
                                           id="max_adults" name="max_adults" value="{{ old('max_adults', 2) }}" min="1" required>
                                    @error('max_adults')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_children">เด็กสูงสุด</label>
                                    <input type="number" class="form-control @error('max_children') is-invalid @enderror"
                                           id="max_children" name="max_children" value="{{ old('max_children', 0) }}" min="0">
                                    @error('max_children')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="total_rooms">จำนวนห้องทั้งหมด <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('total_rooms') is-invalid @enderror"
                                           id="total_rooms" name="total_rooms" value="{{ old('total_rooms', 1) }}" min="1" required>
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
                        <div class="row">
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_wifi" name="amenities[]" value="Free WiFi">
                                    <label class="custom-control-label" for="amenity_wifi">
                                        <i class="fas fa-wifi"></i> Free WiFi
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_ac" name="amenities[]" value="Air Conditioning">
                                    <label class="custom-control-label" for="amenity_ac">
                                        <i class="fas fa-snowflake"></i> เครื่องปรับอากาศ
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_tv" name="amenities[]" value="TV">
                                    <label class="custom-control-label" for="amenity_tv">
                                        <i class="fas fa-tv"></i> ทีวี
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_minibar" name="amenities[]" value="Minibar">
                                    <label class="custom-control-label" for="amenity_minibar">
                                        <i class="fas fa-glass-martini"></i> มินิบาร์
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_safe" name="amenities[]" value="Safe">
                                    <label class="custom-control-label" for="amenity_safe">
                                        <i class="fas fa-lock"></i> ตู้เซฟ
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_balcony" name="amenities[]" value="Balcony">
                                    <label class="custom-control-label" for="amenity_balcony">
                                        <i class="fas fa-door-open"></i> ระเบียง
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_bathtub" name="amenities[]" value="Bathtub">
                                    <label class="custom-control-label" for="amenity_bathtub">
                                        <i class="fas fa-bath"></i> อ่างอาบน้ำ
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_desk" name="amenities[]" value="Desk">
                                    <label class="custom-control-label" for="amenity_desk">
                                        <i class="fas fa-desk"></i> โต๊ะทำงาน
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_coffee" name="amenities[]" value="Coffee Maker">
                                    <label class="custom-control-label" for="amenity_coffee">
                                        <i class="fas fa-coffee"></i> เครื่องชงกาแฟ
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_hairdryer" name="amenities[]" value="Hairdryer">
                                    <label class="custom-control-label" for="amenity_hairdryer">
                                        <i class="fas fa-wind"></i> ไดร์เป่าผม
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_shower" name="amenities[]" value="Shower">
                                    <label class="custom-control-label" for="amenity_shower">
                                        <i class="fas fa-shower"></i> ฝักบัว
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-4 col-sm-6">
                                <div class="custom-control custom-checkbox mb-2">
                                    <input type="checkbox" class="custom-control-input" id="amenity_phone" name="amenities[]" value="Telephone">
                                    <label class="custom-control-label" for="amenity_phone">
                                        <i class="fas fa-phone"></i> โทรศัพท์
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Images -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-images"></i> รูปภาพ</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="main_image">รูปหลัก</label>
                            <input type="file" class="form-control-file @error('main_image') is-invalid @enderror"
                                   id="main_image" name="main_image" accept="image/*" onchange="previewMainImage(this)">
                            @error('main_image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div id="main_image_preview" class="mt-2"></div>
                        </div>

                        <div class="form-group">
                            <label for="gallery_images">รูปแกลเลอรี่ (หลายรูป)</label>
                            <input type="file" class="form-control-file @error('gallery_images') is-invalid @enderror"
                                   id="gallery_images" name="gallery_images[]" accept="image/*" multiple onchange="previewGallery(this)">
                            @error('gallery_images')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">สามารถเลือกหลายรูปพร้อมกันได้</small>
                            <div id="gallery_preview" class="mt-2"></div>
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
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="is_active">
                                <strong>เปิดใช้งาน</strong>
                                <br><small class="text-muted">ห้องพักนี้จะแสดงให้ลูกค้าเห็นและสามารถจองได้</small>
                            </label>
                        </div>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1">
                            <label class="custom-control-label" for="is_featured">
                                <strong>แนะนำพิเศษ</strong>
                                <br><small class="text-muted">แสดงห้องนี้ในส่วนแนะนำ</small>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> บันทึกห้องพัก
                        </button>
                        <a href="{{ route('admin.hotels.rooms.index', $hotel->id) }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i> ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function previewMainImage(input) {
    const preview = document.getElementById('main_image_preview');
    preview.innerHTML = '';

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" style="max-height: 200px;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewGallery(input) {
    const preview = document.getElementById('gallery_preview');
    preview.innerHTML = '';

    if (input.files) {
        const files = Array.from(input.files);
        files.forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'd-inline-block mr-2 mb-2';
                div.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;">`;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}
</script>
@endsection
