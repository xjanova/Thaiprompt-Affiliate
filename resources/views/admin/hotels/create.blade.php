@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-1 font-weight-bold text-primary">
                <i class="fas fa-hotel mr-2"></i>เพิ่มโรงแรมใหม่
            </h1>
            <p class="text-muted mb-0">กรอกข้อมูลโรงแรมทั้งหมดเพื่อเพิ่มเข้าสู่ระบบ</p>
        </div>
        <a href="{{ route('admin.hotels.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> กลับสู่รายการ
        </a>
    </div>

    <form action="{{ route('admin.hotels.store') }}" method="POST" enctype="multipart/form-data" id="hotelForm">
        @csrf

        <div class="row">
            <div class="col-lg-8">
                <!-- Tabbed Interface -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white border-bottom">
                        <ul class="nav nav-tabs card-header-tabs" id="hotelTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab">
                                    <i class="fas fa-info-circle mr-1"></i> ข้อมูลพื้นฐาน
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="location-tab" data-toggle="tab" href="#location" role="tab">
                                    <i class="fas fa-map-marker-alt mr-1"></i> ที่ตั้ง
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab">
                                    <i class="fas fa-phone mr-1"></i> ข้อมูลติดต่อ
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="policies-tab" data-toggle="tab" href="#policies" role="tab">
                                    <i class="fas fa-file-contract mr-1"></i> นโยบาย
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="seo-tab" data-toggle="tab" href="#seo" role="tab">
                                    <i class="fas fa-search mr-1"></i> SEO
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="hotelTabsContent">
                            <!-- Basic Information Tab -->
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <h5 class="text-primary mb-4">
                                    <i class="fas fa-info-circle mr-2"></i>ข้อมูลพื้นฐานของโรงแรม
                                </h5>
                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-building text-primary mr-1"></i>ชื่อโรงแรม
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="name" class="form-control form-control-lg @error('name') is-invalid @enderror"
                                           value="{{ old('name') }}" placeholder="เช่น Grand Palace Hotel Bangkok" required>
                                    <small class="form-text text-muted">ชื่อที่จะแสดงบนเว็บไซต์และผลการค้นหา</small>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-link text-info mr-1"></i>Slug (URL)
                                    </label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-globe"></i></span>
                                        </div>
                                        <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror"
                                               value="{{ old('slug') }}" placeholder="auto-generate จากชื่อโรงแรม">
                                    </div>
                                    <small class="form-text text-muted">ปล่อยว่างเพื่อสร้างอัตโนมัติจากชื่อโรงแรม</small>
                                    @error('slug')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-align-left text-success mr-1"></i>คำอธิบายแบบย่อ
                                    </label>
                                    <textarea name="short_description" rows="2" class="form-control"
                                              placeholder="สรุปสั้นๆ เกี่ยวกับโรงแรม (1-2 ประโยค)">{{ old('short_description') }}</textarea>
                                    <small class="form-text text-muted">แสดงในรายการค้นหาและการ์ดแสดงโรงแรม</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-align-justify text-success mr-1"></i>คำอธิบายแบบเต็ม
                                    </label>
                                    <textarea name="description" rows="6" class="form-control"
                                              placeholder="รายละเอียดเกี่ยวกับโรงแรม สิ่งอำนวยความสะดวก และบริการต่างๆ">{{ old('description') }}</textarea>
                                    <small class="form-text text-muted">รายละเอียดแบบเต็มที่แสดงในหน้าข้อมูลโรงแรม</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-home text-warning mr-1"></i>ประเภท
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="type" class="form-control custom-select" required>
                                                <option value="hotel">🏨 โรงแรม</option>
                                                <option value="resort">🏖️ รีสอร์ท</option>
                                                <option value="hostel">🛏️ โฮสเทล</option>
                                                <option value="apartment">🏢 อพาร์ทเมนท์</option>
                                                <option value="villa">🏡 วิลล่า</option>
                                                <option value="guesthouse">🏠 เกสต์เฮาส์</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-star text-warning mr-1"></i>ระดับดาว
                                            </label>
                                            <select name="star_rating" class="form-control custom-select">
                                                <option value="">ไม่ระบุ</option>
                                                <option value="1">⭐ 1 ดาว</option>
                                                <option value="2">⭐⭐ 2 ดาว</option>
                                                <option value="3">⭐⭐⭐ 3 ดาว</option>
                                                <option value="4">⭐⭐⭐⭐ 4 ดาว</option>
                                                <option value="5">⭐⭐⭐⭐⭐ 5 ดาว</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Location Tab -->
                            <div class="tab-pane fade" id="location" role="tabpanel">
                                <h5 class="text-primary mb-4">
                                    <i class="fas fa-map-marker-alt mr-2"></i>ที่ตั้งและแผนที่
                                </h5>
                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-map-signs text-danger mr-1"></i>ที่อยู่
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="address" class="form-control"
                                           value="{{ old('address') }}"
                                           placeholder="เช่น 123 ถนนสุขุมวิท" required>
                                    <small class="form-text text-muted">ที่อยู่ตามถนน เลขที่อาคาร</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-city text-info mr-1"></i>เมือง/อำเภอ
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="city" class="form-control"
                                                   value="{{ old('city') }}"
                                                   placeholder="เช่น กรุงเทพมหานคร" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-map text-info mr-1"></i>จังหวัด/รัฐ
                                            </label>
                                            <input type="text" name="state" class="form-control"
                                                   value="{{ old('state') }}"
                                                   placeholder="เช่น กรุงเทพมหานคร">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-globe-asia text-success mr-1"></i>ประเทศ
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="country" class="form-control"
                                                   value="{{ old('country', 'Thailand') }}"
                                                   placeholder="Thailand" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-mail-bulk text-secondary mr-1"></i>รหัสไปรษณีย์
                                            </label>
                                            <input type="text" name="postal_code" class="form-control"
                                                   value="{{ old('postal_code') }}"
                                                   placeholder="เช่น 10110">
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-map-pin mr-2"></i>พิกัดแผนที่ (ไม่บังคับ)
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-compass text-warning mr-1"></i>Latitude (ละติจูด)
                                            </label>
                                            <input type="number" step="0.000001" name="latitude" class="form-control"
                                                   value="{{ old('latitude') }}"
                                                   placeholder="13.7563">
                                            <small class="form-text text-muted">สำหรับแสดงตำแหน่งบนแผนที่</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-compass text-warning mr-1"></i>Longitude (ลองจิจูด)
                                            </label>
                                            <input type="number" step="0.000001" name="longitude" class="form-control"
                                                   value="{{ old('longitude') }}"
                                                   placeholder="100.5018">
                                            <small class="form-text text-muted">สำหรับแสดงตำแหน่งบนแผนที่</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Tab -->
                            <div class="tab-pane fade" id="contact" role="tabpanel">
                                <h5 class="text-primary mb-4">
                                    <i class="fas fa-phone mr-2"></i>ข้อมูลติดต่อ
                                </h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-phone-alt text-success mr-1"></i>เบอร์โทรศัพท์
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                                </div>
                                                <input type="text" name="phone" class="form-control"
                                                       value="{{ old('phone') }}"
                                                       placeholder="02-123-4567">
                                            </div>
                                            <small class="form-text text-muted">เบอร์โทรศัพท์ติดต่อโรงแรม</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-envelope text-danger mr-1"></i>อีเมล
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                                </div>
                                                <input type="email" name="email" class="form-control"
                                                       value="{{ old('email') }}"
                                                       placeholder="info@hotel.com">
                                            </div>
                                            <small class="form-text text-muted">อีเมลสำหรับติดต่อ</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-globe text-info mr-1"></i>เว็บไซต์
                                            </label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                </div>
                                                <input type="url" name="website" class="form-control"
                                                       value="{{ old('website') }}"
                                                       placeholder="https://hotel.com">
                                            </div>
                                            <small class="form-text text-muted">เว็บไซต์ของโรงแรม</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Policies Tab -->
                            <div class="tab-pane fade" id="policies" role="tabpanel">
                                <h5 class="text-primary mb-4">
                                    <i class="fas fa-file-contract mr-2"></i>นโยบายและกฎระเบียบ
                                </h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-clock text-success mr-1"></i>เวลาเช็คอิน
                                            </label>
                                            <input type="time" name="check_in_time" class="form-control"
                                                   value="{{ old('check_in_time', '14:00') }}">
                                            <small class="form-text text-muted">เวลาที่ผู้เข้าพักสามารถเช็คอินได้</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="font-weight-bold">
                                                <i class="fas fa-clock text-warning mr-1"></i>เวลาเช็คเอาท์
                                            </label>
                                            <input type="time" name="check_out_time" class="form-control"
                                                   value="{{ old('check_out_time', '12:00') }}">
                                            <small class="form-text text-muted">เวลาที่ต้องเช็คเอาท์</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-ban text-danger mr-1"></i>นโยบายการยกเลิก
                                    </label>
                                    <textarea name="cancellation_policy" rows="3" class="form-control"
                                              placeholder="เช่น ยกเลิกฟรีก่อน 24 ชั่วโมง, หลังจากนั้นคิด 1 คืน">{{ old('cancellation_policy') }}</textarea>
                                    <small class="form-text text-muted">เงื่อนไขการยกเลิกการจอง</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-clipboard-list text-info mr-1"></i>กฎระเบียบของที่พัก
                                    </label>
                                    <textarea name="house_rules" rows="3" class="form-control"
                                              placeholder="เช่น ห้ามสูบบุหรี่, ห้ามนำสัตว์เลี้ยง, เงียบหลัง 22:00 น.">{{ old('house_rules') }}</textarea>
                                    <small class="form-text text-muted">กฎและข้อบังคับที่ผู้เข้าพักต้องปฏิบัติตาม</small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-credit-card text-success mr-1"></i>นโยบายการชำระเงิน
                                    </label>
                                    <textarea name="payment_policy" rows="3" class="form-control"
                                              placeholder="เช่น ชำระเต็มจำนวนเมื่อเช็คอิน, รับบัตรเครดิต, QR Code">{{ old('payment_policy') }}</textarea>
                                    <small class="form-text text-muted">วิธีการและเงื่อนไขการชำระเงิน</small>
                                </div>
                            </div>

                            <!-- SEO Tab -->
                            <div class="tab-pane fade" id="seo" role="tabpanel">
                                <h5 class="text-primary mb-4">
                                    <i class="fas fa-search mr-2"></i>การตั้งค่า SEO
                                </h5>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>คำแนะนำ:</strong> ข้อมูล SEO จะช่วยให้โรงแรมของคุณปรากฏในผลการค้นหาของ Google และ Search Engine อื่นๆ
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-heading text-primary mr-1"></i>Meta Title
                                    </label>
                                    <input type="text" name="meta_title" class="form-control"
                                           value="{{ old('meta_title') }}"
                                           placeholder="เช่น Grand Palace Hotel Bangkok - โรงแรมหรู 5 ดาว ใจกลางกรุงเทพฯ"
                                           maxlength="60">
                                    <small class="form-text text-muted">
                                        <span class="char-count">0</span>/60 ตัวอักษร - ชื่อที่แสดงในผลการค้นหา
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-align-left text-info mr-1"></i>Meta Description
                                    </label>
                                    <textarea name="meta_description" rows="3" class="form-control"
                                              placeholder="คำอธิบายย่อเกี่ยวกับโรงแรม เพื่อดึงดูดผู้เข้าชมจากผลการค้นหา"
                                              maxlength="160">{{ old('meta_description') }}</textarea>
                                    <small class="form-text text-muted">
                                        <span class="desc-count">0</span>/160 ตัวอักษร - คำอธิบายที่แสดงในผลการค้นหา
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label class="font-weight-bold">
                                        <i class="fas fa-tags text-success mr-1"></i>Meta Keywords
                                    </label>
                                    <input type="text" name="meta_keywords" class="form-control"
                                           value="{{ old('meta_keywords') }}"
                                           placeholder="เช่น โรงแรม, กรุงเทพ, ที่พัก, 5 ดาว (คั่นด้วยจุลภาค)">
                                    <small class="form-text text-muted">คำสำคัญที่เกี่ยวข้องกับโรงแรม คั่นด้วยจุลภาค (,)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Images -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-images mr-2"></i>รูปภาพและสื่อ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fas fa-image text-primary mr-1"></i>รูปหลัก
                                <span class="badge badge-info badge-sm">แนะนำ</span>
                            </label>
                            <div class="custom-file">
                                <input type="file" name="main_image" class="custom-file-input" id="mainImage" accept="image/*">
                                <label class="custom-file-label" for="mainImage">เลือกไฟล์...</label>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle mr-1"></i>ขนาดแนะนำ: 1200x800px (JPG, PNG)
                            </small>
                            <div id="mainImagePreview" class="mt-2"></div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fas fa-photo-video text-success mr-1"></i>แกลเลอรี่
                                <span class="badge badge-secondary badge-sm">หลายรูป</span>
                            </label>
                            <div class="custom-file">
                                <input type="file" name="gallery_images[]" class="custom-file-input" id="galleryImages" accept="image/*" multiple>
                                <label class="custom-file-label" for="galleryImages">เลือกไฟล์...</label>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-images mr-1"></i>เลือกหลายรูปพร้อมกัน (Ctrl+Click)
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Facilities -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-concierge-bell mr-2"></i>สิ่งอำนวยความสะดวก
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                        <div class="facility-grid">
                            @foreach($facilities as $facility)
                                <div class="facility-item">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="facility{{ $facility->id }}"
                                               name="facilities[]" value="{{ $facility->id }}">
                                        <label class="custom-control-label d-flex align-items-center" for="facility{{ $facility->id }}">
                                            <i class="fas fa-check-circle text-success mr-2" style="font-size: 0.9rem;"></i>
                                            <span>{{ $facility->name }}</span>
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Settings -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-gradient-warning text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cog mr-2"></i>การตั้งค่า
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="bg-light p-3 rounded mb-3">
                            <div class="custom-control custom-switch mb-3">
                                <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" checked>
                                <label class="custom-control-label font-weight-bold" for="is_active">
                                    <i class="fas fa-toggle-on text-success mr-1"></i>เปิดใช้งาน
                                </label>
                                <small class="d-block text-muted ml-4">เปิดให้ผู้ใช้สามารถจองได้</small>
                            </div>

                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1">
                                <label class="custom-control-label font-weight-bold" for="is_featured">
                                    <i class="fas fa-star text-warning mr-1"></i>โรงแรมแนะนำ
                                </label>
                                <small class="d-block text-muted ml-4">แสดงในหน้าแรกและรายการแนะนำ</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fas fa-user-tie text-info mr-1"></i>เจ้าของโรงแรม
                            </label>
                            <select name="owner_id" class="form-control custom-select">
                                <option value="">-- ไม่ระบุ --</option>
                                @if(isset($owners))
                                    @foreach($owners as $owner)
                                        <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="form-text text-muted">เจ้าของหรือผู้จัดการโรงแรม</small>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">
                                <i class="fas fa-percentage text-success mr-1"></i>Commission Rate
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="commission_rate" class="form-control"
                                       value="{{ old('commission_rate', 0) }}" min="0" max="100">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">ค่าคอมมิชชั่นสำหรับ Affiliate (0-100%)</small>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <button type="submit" class="btn btn-success btn-lg btn-block mb-2">
                            <i class="fas fa-save mr-2"></i> บันทึกโรงแรม
                        </button>
                        <a href="{{ route('admin.hotels.index') }}" class="btn btn-outline-secondary btn-block">
                            <i class="fas fa-times mr-1"></i> ยกเลิก
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('styles')
<style>
    /* Custom Styling for Hotel Settings */
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    .bg-gradient-success {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    }
    .bg-gradient-warning {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    }

    .card {
        border-radius: 10px;
        overflow: hidden;
    }

    .card-header {
        border-bottom: 2px solid rgba(0,0,0,0.05);
    }

    .nav-tabs .nav-link {
        border: none;
        color: #6c757d;
        padding: 12px 20px;
        transition: all 0.3s;
    }

    .nav-tabs .nav-link:hover {
        color: #667eea;
        background-color: #f8f9fa;
    }

    .nav-tabs .nav-link.active {
        color: #667eea;
        font-weight: 600;
        border-bottom: 3px solid #667eea;
        background-color: #f8f9ff;
    }

    .form-control:focus, .custom-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-success {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
        transition: all 0.3s;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(102, 126, 234, 0.4);
    }

    .facility-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 10px;
    }

    .facility-item {
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        transition: all 0.2s;
    }

    .facility-item:hover {
        background: #e9ecef;
        transform: translateX(5px);
    }

    .custom-file-label::after {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    #mainImagePreview img {
        max-width: 100%;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Custom file input labels
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').addClass("selected").html(fileName || 'เลือกไฟล์...');
    });

    // Main image preview
    $('#mainImage').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#mainImagePreview').html('<img src="' + e.target.result + '" class="img-thumbnail mt-2" style="max-width: 100%;">');
            }
            reader.readAsDataURL(file);
        }
    });

    // Character counter for meta title
    $('input[name="meta_title"]').on('input', function() {
        let length = $(this).val().length;
        $(this).closest('.form-group').find('.char-count').text(length);
    });

    // Character counter for meta description
    $('textarea[name="meta_description"]').on('input', function() {
        let length = $(this).val().length;
        $(this).closest('.form-group').find('.desc-count').text(length);
    });

    // Form validation before submit
    $('#hotelForm').on('submit', function(e) {
        let isValid = true;
        let errorMessage = '';

        // Check required fields
        if (!$('input[name="name"]').val()) {
            isValid = false;
            errorMessage += '- กรุณากรอกชื่อโรงแรม\n';
        }

        if (!$('input[name="address"]').val()) {
            isValid = false;
            errorMessage += '- กรุณากรอกที่อยู่\n';
        }

        if (!$('input[name="city"]').val()) {
            isValid = false;
            errorMessage += '- กรุณากรอกเมือง\n';
        }

        if (!isValid) {
            e.preventDefault();
            alert('กรุณากรอกข้อมูลให้ครบถ้วน:\n\n' + errorMessage);
            return false;
        }
    });

    // Smooth scroll to tab on validation error
    @if ($errors->any())
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('.is-invalid:first').offset().top - 100
            }, 500);
        }, 100);
    @endif
});
</script>
@endpush
@endsection
