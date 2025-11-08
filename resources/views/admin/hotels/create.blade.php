@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">เพิ่มโรงแรมใหม่</h1>
        <a href="{{ route('admin.hotels.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> กลับ
        </a>
    </div>

    <form action="{{ route('admin.hotels.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">ข้อมูลพื้นฐาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>ชื่อโรงแรม <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Slug (URL)</label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                   value="{{ old('slug') }}" placeholder="auto-generate จากชื่อโรงแรม">
                            @error('slug')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>คำอธิบายแบบย่อ</label>
                            <textarea name="short_description" rows="2" class="form-control">{{ old('short_description') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>คำอธิบายแบบเต็ม</label>
                            <textarea name="description" rows="6" class="form-control">{{ old('description') }}</textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ประเภท <span class="text-danger">*</span></label>
                                    <select name="type" class="form-control" required>
                                        <option value="hotel">โรงแรม</option>
                                        <option value="resort">รีสอร์ท</option>
                                        <option value="hostel">โฮสเทล</option>
                                        <option value="apartment">อพาร์ทเมนท์</option>
                                        <option value="villa">วิลล่า</option>
                                        <option value="guesthouse">เกสต์เฮาส์</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ระดับดาว</label>
                                    <select name="star_rating" class="form-control">
                                        <option value="">ไม่ระบุ</option>
                                        <option value="1">1 ดาว</option>
                                        <option value="2">2 ดาว</option>
                                        <option value="3">3 ดาว</option>
                                        <option value="4">4 ดาว</option>
                                        <option value="5">5 ดาว</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">ที่ตั้ง</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>ที่อยู่ <span class="text-danger">*</span></label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>เมือง <span class="text-danger">*</span></label>
                                    <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>จังหวัด/รัฐ</label>
                                    <input type="text" name="state" class="form-control" value="{{ old('state') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ประเทศ <span class="text-danger">*</span></label>
                                    <input type="text" name="country" class="form-control" value="{{ old('country', 'Thailand') }}" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>รหัสไปรษณีย์</label>
                                    <input type="text" name="postal_code" class="form-control" value="{{ old('postal_code') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Latitude (ละติจูด)</label>
                                    <input type="number" step="0.000001" name="latitude" class="form-control" value="{{ old('latitude') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Longitude (ลองจิจูด)</label>
                                    <input type="number" step="0.000001" name="longitude" class="form-control" value="{{ old('longitude') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">ข้อมูลติดต่อ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>เบอร์โทรศัพท์</label>
                                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>อีเมล</label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>เว็บไซต์</label>
                                    <input type="url" name="website" class="form-control" value="{{ old('website') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Policies -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">นโยบายและกฎระเบียบ</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>เวลาเช็คอิน</label>
                                    <input type="time" name="check_in_time" class="form-control" value="{{ old('check_in_time', '14:00') }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>เวลาเช็คเอาท์</label>
                                    <input type="time" name="check_out_time" class="form-control" value="{{ old('check_out_time', '12:00') }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>นโยบายการยกเลิก</label>
                            <textarea name="cancellation_policy" rows="3" class="form-control">{{ old('cancellation_policy') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>กฎระเบียบของที่พัก</label>
                            <textarea name="house_rules" rows="3" class="form-control">{{ old('house_rules') }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>นโยบายการชำระเงิน</label>
                            <textarea name="payment_policy" rows="3" class="form-control">{{ old('payment_policy') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- SEO -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">SEO</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Meta Title</label>
                            <input type="text" name="meta_title" class="form-control" value="{{ old('meta_title') }}">
                        </div>
                        <div class="form-group">
                            <label>Meta Description</label>
                            <textarea name="meta_description" rows="2" class="form-control">{{ old('meta_description') }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Meta Keywords</label>
                            <input type="text" name="meta_keywords" class="form-control" value="{{ old('meta_keywords') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Images -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">รูปภาพ</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>รูปหลัก</label>
                            <input type="file" name="main_image" class="form-control-file" accept="image/*">
                            <small class="form-text text-muted">แนะนำ 1200x800px</small>
                        </div>

                        <div class="form-group">
                            <label>แกลเลอรี่ (หลายรูป)</label>
                            <input type="file" name="gallery_images[]" class="form-control-file" accept="image/*" multiple>
                            <small class="form-text text-muted">สามารถเลือกหลายรูปพร้อมกัน</small>
                        </div>
                    </div>
                </div>

                <!-- Facilities -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">สิ่งอำนวยความสะดวก</h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        @foreach($facilities as $facility)
                            <div class="custom-control custom-checkbox mb-2">
                                <input type="checkbox" class="custom-control-input" id="facility{{ $facility->id }}"
                                       name="facilities[]" value="{{ $facility->id }}">
                                <label class="custom-control-label" for="facility{{ $facility->id }}">
                                    {{ $facility->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">การตั้งค่า</h5>
                    </div>
                    <div class="card-body">
                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                            <label class="custom-control-label" for="is_active">เปิดใช้งาน</label>
                        </div>

                        <div class="custom-control custom-switch mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1">
                            <label class="custom-control-label" for="is_featured">โรงแรมแนะนำ</label>
                        </div>

                        <div class="form-group">
                            <label>เจ้าของโรงแรม</label>
                            <select name="owner_id" class="form-control">
                                <option value="">ไม่มี</option>
                                @if(isset($owners))
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label>% Commission สำหรับ Affiliate</label>
                            <input type="number" step="0.01" name="commission_rate" class="form-control" value="{{ old('commission_rate', 0) }}">
                            <small class="form-text text-muted">0-100</small>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="card">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> บันทึกโรงแรม
                        </button>
                        <a href="{{ route('admin.hotels.index') }}" class="btn btn-secondary btn-block">
                            ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
