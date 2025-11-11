@extends('layouts.admin')

@section('title', 'สร้างโปรโมชั่นพิเศษ')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-2">สร้างโปรโมชั่นพิเศษ</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.hotels.special-offers.index') }}">โปรโมชั่นพิเศษ</a></li>
                    <li class="breadcrumb-item active">สร้างใหม่</li>
                </ol>
            </nav>
        </div>
    </div>

    <form action="{{ route('admin.hotels.special-offers.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> ข้อมูลพื้นฐาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">โรงแรม <span class="text-danger">*</span></label>
                                <select name="hotel_id" class="form-control" required id="hotelSelect">
                                    <option value="">เลือกโรงแรม</option>
                                    @foreach($hotels as $hotel)
                                        <option value="{{ $hotel->id }}" {{ old('hotel_id') == $hotel->id ? 'selected' : '' }}>
                                            {{ $hotel->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('hotel_id')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">ชื่อโปรโมชั่น <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required
                                       placeholder="เช่น ลดพิเศษวันหยุดยาว">
                                @error('name')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">โค้ดส่วนลด <span class="text-danger">*</span></label>
                                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required
                                       placeholder="เช่น SUMMER2024" style="text-transform: uppercase;">
                                <small class="text-muted">ตัวพิมพ์ใหญ่, ไม่มีช่องว่าง</small>
                                @error('code')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">คำอธิบาย</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="อธิบายรายละเอียดโปรโมชั่น...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Discount Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-percent"></i> การตั้งค่าส่วนลด</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">ประเภทส่วนลด <span class="text-danger">*</span></label>
                                <select name="discount_type" class="form-control" required id="discountType">
                                    <option value="percentage" {{ old('discount_type') == 'percentage' ? 'selected' : '' }}>เปอร์เซ็นต์ (%)</option>
                                    <option value="fixed" {{ old('discount_type') == 'fixed' ? 'selected' : '' }}>จำนวนเงินคงที่ (฿)</option>
                                    <option value="free_nights" {{ old('discount_type') == 'free_nights' ? 'selected' : '' }}>ฟรีจำนวนคืน</option>
                                </select>
                                @error('discount_type')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3" id="discountValueField">
                                <label class="form-label">มูลค่าส่วนลด <span class="text-danger">*</span></label>
                                <input type="number" name="discount_value" class="form-control" value="{{ old('discount_value') }}"
                                       step="0.01" min="0" placeholder="เช่น 20">
                                @error('discount_value')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3 d-none" id="freeNightsField">
                                <label class="form-label">จำนวนคืนฟรี <span class="text-danger">*</span></label>
                                <input type="number" name="free_nights" class="form-control" value="{{ old('free_nights') }}"
                                       min="1" placeholder="เช่น 1">
                                @error('free_nights')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">จำนวนคืนขั้นต่ำ</label>
                                <input type="number" name="min_nights" class="form-control" value="{{ old('min_nights') }}"
                                       min="1" placeholder="เช่น 2">
                                <small class="text-muted">จำนวนคืนขั้นต่ำที่ต้องจองเพื่อใช้โปรโมชั่น</small>
                                @error('min_nights')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label class="form-label">ยอดขั้นต่ำ (฿)</label>
                                <input type="number" name="min_amount" class="form-control" value="{{ old('min_amount') }}"
                                       step="0.01" min="0" placeholder="เช่น 5000">
                                <small class="text-muted">ยอดการจองขั้นต่ำเพื่อใช้โปรโมชั่น</small>
                                @error('min_amount')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validity Period -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar"></i> ระยะเวลาใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">วันที่เริ่มต้น <span class="text-danger">*</span></label>
                                <input type="date" name="valid_from" class="form-control" value="{{ old('valid_from') }}" required>
                                @error('valid_from')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">วันที่สิ้นสุด <span class="text-danger">*</span></label>
                                <input type="date" name="valid_to" class="form-control" value="{{ old('valid_to') }}" required>
                                @error('valid_to')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label">วันที่ใช้ได้</label>
                                <div class="row">
                                    @php
                                        $days = ['monday' => 'จันทร์', 'tuesday' => 'อังคาร', 'wednesday' => 'พุธ',
                                                 'thursday' => 'พฤหัสบดี', 'friday' => 'ศุกร์', 'saturday' => 'เสาร์', 'sunday' => 'อาทิตย์'];
                                    @endphp
                                    @foreach($days as $key => $label)
                                        <div class="col-md-3 col-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="day_{{ $key }}"
                                                       name="applicable_days[]" value="{{ $key }}"
                                                       {{ in_array($key, old('applicable_days', [])) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="day_{{ $key }}">{{ $label }}</label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <small class="text-muted">หากไม่เลือก จะใช้ได้ทุกวัน</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage Limits -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> ข้อจำกัดการใช้งาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">จำนวนครั้งที่ใช้ได้ทั้งหมด</label>
                                <input type="number" name="max_uses" class="form-control" value="{{ old('max_uses') }}"
                                       min="1" placeholder="ไม่จำกัด">
                                <small class="text-muted">เว้นว่างหากไม่จำกัด</small>
                                @error('max_uses')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">จำนวนครั้งที่ใช้ได้ต่อผู้ใช้</label>
                                <input type="number" name="max_uses_per_user" class="form-control" value="{{ old('max_uses_per_user') }}"
                                       min="1" placeholder="ไม่จำกัด">
                                <small class="text-muted">เว้นว่างหากไม่จำกัด</small>
                                @error('max_uses_per_user')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="col-lg-4">
                <!-- Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-toggle-on"></i> สถานะ</h5>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">เปิดใช้งาน</label>
                        </div>

                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1"
                                   {{ old('is_featured') ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_featured">แนะนำพิเศษ</label>
                        </div>
                    </div>
                </div>

                <!-- Banner Image -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-image"></i> รูปแบนเนอร์</h5>
                    </div>
                    <div class="card-body">
                        <input type="file" name="banner_image" class="form-control" accept="image/*" id="bannerImage">
                        <small class="text-muted">รองรับไฟล์ JPG, PNG (สูงสุด 5MB)</small>

                        <div id="imagePreview" class="mt-3"></div>

                        @error('banner_image')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Room Types -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bed"></i> ห้องพักที่ใช้ได้</h5>
                    </div>
                    <div class="card-body">
                        <div id="roomTypesList">
                            <p class="text-muted small">กรุณาเลือกโรงแรมก่อน</p>
                        </div>
                        <small class="text-muted">หากไม่เลือก จะใช้ได้กับทุกห้อง</small>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block mb-2">
                            <i class="fas fa-save"></i> บันทึกโปรโมชั่น
                        </button>
                        <a href="{{ route('admin.hotels.special-offers.index') }}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times"></i> ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Discount type change handler
document.getElementById('discountType').addEventListener('change', function() {
    const valueField = document.getElementById('discountValueField');
    const freeNightsField = document.getElementById('freeNightsField');

    if (this.value === 'free_nights') {
        valueField.classList.add('d-none');
        freeNightsField.classList.remove('d-none');
    } else {
        valueField.classList.remove('d-none');
        freeNightsField.classList.add('d-none');
    }
});

// Hotel change handler - load room types
document.getElementById('hotelSelect').addEventListener('change', function() {
    const hotelId = this.value;
    const roomTypesList = document.getElementById('roomTypesList');

    if (!hotelId) {
        roomTypesList.innerHTML = '<p class="text-muted small">กรุณาเลือกโรงแรมก่อน</p>';
        return;
    }

    roomTypesList.innerHTML = '<p class="text-muted small">กำลังโหลด...</p>';

    fetch(`/admin/hotels/${hotelId}/room-types`)
        .then(response => response.json())
        .then(data => {
            if (data.length === 0) {
                roomTypesList.innerHTML = '<p class="text-muted small">ไม่มีห้องพัก</p>';
                return;
            }

            let html = '';
            data.forEach(room => {
                html += `
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="room_${room.id}"
                               name="room_type_ids[]" value="${room.id}">
                        <label class="custom-control-label" for="room_${room.id}">
                            ${room.name}
                        </label>
                    </div>
                `;
            });
            roomTypesList.innerHTML = html;
        })
        .catch(error => {
            console.error('Error:', error);
            roomTypesList.innerHTML = '<p class="text-danger small">เกิดข้อผิดพลาด</p>';
        });
});

// Image preview
document.getElementById('bannerImage').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" class="img-fluid rounded" alt="Preview">`;
        }
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
});

// Code uppercase
document.querySelector('input[name="code"]').addEventListener('input', function() {
    this.value = this.value.toUpperCase().replace(/\s/g, '');
});
</script>
@endpush
@endsection
